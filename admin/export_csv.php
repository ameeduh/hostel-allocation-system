<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$type = isset($_GET['type']) ? $_GET['type'] : 'students';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if($type == 'students') {
    fputcsv($output, ['Name', 'Reg Number', 'Program', 'Year', 'Gender', 'Status', 'Allocation Date']);
    
    $sql = "SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID ORDER BY u.name";
    $result = $db->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $yearText = '';
            if($row['year'] == 1) $yearText = '1st Year';
            elseif($row['year'] == 2) $yearText = '2nd Year';
            elseif($row['year'] == 3) $yearText = '3rd Year';
            elseif($row['year'] == 4) $yearText = '4th Year';
            else $yearText = 'N/A';
            
            fputcsv($output, [
                $row['name'],
                $row['regNumber'],
                $row['program'],
                $yearText,
                $row['gender'],
                ucfirst($row['applicationStatus']),
                $row['allocatedDate'] ?? 'N/A'
            ]);
        }
    }
} 
elseif($type == 'rooms') {
    fputcsv($output, ['Room Number', 'Hostel Name', 'Gender', 'Capacity', 'Occupied', 'Available', 'Status']);
    
    $sql = "SELECT roomNumber, hostelName, gender, capacity, availableBeds, 
                   (capacity - availableBeds) as occupiedBeds, status 
            FROM rooms ORDER BY hostelName, roomNumber";
    $result = $db->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['roomNumber'],
                $row['hostelName'],
                $row['gender'],
                $row['capacity'],
                $row['occupiedBeds'],
                $row['availableBeds'],
                ucfirst($row['status'])
            ]);
        }
    }
} 
elseif($type == 'pending') {
    fputcsv($output, ['Name', 'Reg Number', 'Program', 'Year', 'Gender', 'Action Needed', 'Submission Date']);
    
    $sql = "SELECT s.*, u.name FROM students s 
            JOIN users u ON s.userID = u.userID 
            WHERE s.applicationStatus = 'pending'";
    $result = $db->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $yearText = '';
            if($row['year'] == 1) $yearText = '1st Year';
            elseif($row['year'] == 2) $yearText = '2nd Year';
            elseif($row['year'] == 3) $yearText = '3rd Year';
            elseif($row['year'] == 4) $yearText = '4th Year';
            else $yearText = 'N/A';
            
            fputcsv($output, [
                $row['name'],
                $row['regNumber'],
                $row['program'],
                $yearText,
                $row['gender'],
                'Accountant Verification',
                $row['allocatedDate'] ?? 'N/A'
            ]);
        }
    }
}

fclose($output);
exit();
?>