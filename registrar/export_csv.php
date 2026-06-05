<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$type = isset($_GET['type']) ? $_GET['type'] : 'approved';
$departmentFilter = isset($_GET['dept']) ? $_GET['dept'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Function to build department filter
function getDeptWhere($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND regNumber LIKE '%BscBA%'";
    }
    return "";
}

$deptWhere = getDeptWhere($departmentFilter);

$dateWhere = "";
if($from_date && $to_date) {
    $dateWhere = " AND approvedDate BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $dateWhere = " AND approvedDate >= '$from_date'";
} elseif($to_date) {
    $dateWhere = " AND approvedDate <= '$to_date'";
}

$blacklistDateWhere = "";
if($from_date && $to_date) {
    $blacklistDateWhere = " AND dateAdded BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $blacklistDateWhere = " AND dateAdded >= '$from_date'";
} elseif($to_date) {
    $blacklistDateWhere = " AND dateAdded <= '$to_date'";
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if($type == 'approved') {
    // Headers
    fputcsv($output, ['Student Name', 'Reg Number', 'Program', 'Year', 'Gender', 'Fee Note', 'Date Approved', 'Expiry Date', 'Days Remaining']);
    
    $sql = "SELECT fc.*, s.program, s.year, s.gender, s.regNumber 
            FROM fee_commitment_requests fc
            JOIN students s ON fc.studentID = s.studentID
            WHERE fc.status = 'approved' $deptWhere $dateWhere
            ORDER BY fc.approvedDate DESC";
    $result = $db->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $expiryDate = $row['approvedDate'];
            $expiry = strtotime($expiryDate . ' +4 weeks');
            $today = time();
            $daysRemaining = round(($expiry - $today) / (60 * 60 * 24));
            $expiryDateFormatted = date('Y-m-d', $expiry);
            
            fputcsv($output, [
                $row['studentName'],
                $row['regNumber'],
                $row['program'],
                $row['year'] . ' Year',
                $row['gender'],
                $row['reason'],
                $row['approvedDate'],
                $expiryDateFormatted,
                $daysRemaining . ' days'
            ]);
        }
    }
} 
elseif($type == 'blacklist') {
    // Headers
    fputcsv($output, ['Registration Number', 'Student Name', 'Reason', 'Date Added', 'Added By', 'Status']);
    
    $sql = "SELECT * FROM blacklist WHERE status = 'active' $deptWhere $blacklistDateWhere ORDER BY dateAdded DESC";
    $result = $db->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['regNumber'],
                $row['studentName'],
                $row['reason'],
                $row['dateAdded'],
                $row['addedBy'],
                'Active'
            ]);
        }
    }
}

fclose($output);
exit();
?>