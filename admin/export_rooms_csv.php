<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$selectedHostel = isset($_GET['hostel']) ? $_GET['hostel'] : '';

$roomWhere = "";
if($selectedHostel) {
    $roomWhere = " WHERE hostelName = '$selectedHostel'";
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="rooms_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['Room Number', 'Hostel Name', 'Gender', 'Capacity', 'Occupied', 'Available', 'Status']);

$result = $db->query("SELECT *, (capacity - availableBeds) as occupiedBeds FROM rooms $roomWhere ORDER BY hostelName, roomNumber");
if($result) {
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['roomNumber'],
            $row['hostelName'],
            $row['gender'],
            $row['capacity'],
            $row['occupiedBeds'],
            $row['availableBeds'],
            ($row['availableBeds'] > 0) ? 'Available' : 'Full'
        ]);
    }
}

fclose($output);
exit();
?>