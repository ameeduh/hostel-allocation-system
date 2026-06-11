<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

$program = isset($_GET['program']) ? $_GET['program'] : 'all';
$hostel = isset($_GET['hostel']) ? $_GET['hostel'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Determine current semester
$currentMonth = date('n');
$currentYear = date('Y');
if($currentMonth <= 6) {
    $currentSemester = 'Semester 1';
    $academicYear = ($currentYear - 1) . '/' . $currentYear;
} else {
    $currentSemester = 'Semester 2';
    $academicYear = $currentYear . '/' . ($currentYear + 1);
}

// Get statistics
$totalStudents = $db->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
$totalICT = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscICT%'")->fetch_assoc()['total'];
$totalNursing = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscNM%'")->fetch_assoc()['total'];
$totalBusiness = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscBA%'")->fetch_assoc()['total'];
$totalMale = $db->query("SELECT COUNT(*) as total FROM students WHERE gender = 'Male'")->fetch_assoc()['total'];
$totalFemale = $db->query("SELECT COUNT(*) as total FROM students WHERE gender = 'Female'")->fetch_assoc()['total'];

$totalRooms = $db->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$totalBeds = $db->query("SELECT SUM(capacity) as total FROM rooms")->fetch_assoc()['total'];
$totalAvailableBeds = $db->query("SELECT SUM(availableBeds) as total FROM rooms")->fetch_assoc()['total'];
$totalOccupiedBeds = $totalBeds - $totalAvailableBeds;

$paidStudents = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND semester = '$currentSemester'")->fetch_assoc()['total'];
$totalBlacklisted = $db->query("SELECT COUNT(*) as total FROM blacklist WHERE status = 'active'")->fetch_assoc()['total'];
$allocatedStudents = $db->query("SELECT COUNT(*) as total FROM students WHERE applicationStatus = 'allocated'")->fetch_assoc()['total'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="institutional_report_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write report header
fputcsv($output, ['Daeyang University - Institutional Report']);
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['Current Semester: ' . $currentSemester . ', Academic Year: ' . $academicYear]);
fputcsv($output, []);
fputcsv($output, ['Student Statistics']);
fputcsv($output, ['Total Students', $totalStudents]);
fputcsv($output, ['ICT', $totalICT]);
fputcsv($output, ['Nursing', $totalNursing]);
fputcsv($output, ['Business Administration', $totalBusiness]);
fputcsv($output, ['Male', $totalMale]);
fputcsv($output, ['Female', $totalFemale]);
fputcsv($output, []);
fputcsv($output, ['Room Statistics']);
fputcsv($output, ['Total Rooms', $totalRooms]);
fputcsv($output, ['Total Beds', $totalBeds]);
fputcsv($output, ['Occupied Beds', $totalOccupiedBeds]);
fputcsv($output, ['Available Beds', $totalAvailableBeds]);
fputcsv($output, []);
fputcsv($output, ['Payment Statistics (' . $currentSemester . ')']);
fputcsv($output, ['Paid Students', $paidStudents]);
fputcsv($output, []);
fputcsv($output, ['Blacklist Statistics']);
fputcsv($output, ['Total Blacklisted', $totalBlacklisted]);
fputcsv($output, []);
fputcsv($output, ['Allocation Statistics']);
fputcsv($output, ['Allocated Students', $allocatedStudents]);

fclose($output);
exit();
?>