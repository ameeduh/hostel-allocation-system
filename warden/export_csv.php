<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$hostel = isset($_GET['hostel']) ? $_GET['hostel'] : '';
$room = isset($_GET['room']) ? $_GET['room'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Build WHERE clause
$where = " WHERE s.applicationStatus = 'allocated' AND s.allocationStatus = 'active'";
if($hostel) {
    $where .= " AND r.hostelName = '$hostel'";
}
if($room) {
    $where .= " AND r.roomNumber = '$room'";
}
if($from_date && $to_date) {
    $where .= " AND s.allocatedDate BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $where .= " AND s.allocatedDate >= '$from_date'";
} elseif($to_date) {
    $where .= " AND s.allocatedDate <= '$to_date'";
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="allocated_students_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['Student Name', 'Reg Number', 'Program', 'Year', 'Gender', 'Room Number', 'Hostel', 'Allocated Date']);

// Data
$sql = "SELECT s.studentID, s.regNumber, u.name as studentName, s.allocatedRoomID, s.allocatedDate, s.gender, s.program, s.year, r.roomNumber, r.hostelName 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID 
        $where
        ORDER BY s.allocatedDate DESC";
$result = $db->query($sql);
if($result) {
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['studentName'],
            $row['regNumber'],
            $row['program'],
            $row['year'] . ' Year',
            $row['gender'],
            $row['roomNumber'],
            $row['hostelName'],
            $row['allocatedDate']
        ]);
    }
}

fclose($output);
exit();
?>