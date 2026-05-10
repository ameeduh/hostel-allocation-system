<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT applicationStatus FROM students WHERE studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();

$status = $studentData['applicationStatus'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Status - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=6">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="dashboard.php" class="logout-link">
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Approval Status</h1>
                </div>
            </div>

            <div class="content-card">
                <?php if($status == 'pending'): ?>
                    <div class="status-box pending">
                        <h3>Pending Approval</h3>
                        <p>Your application has been submitted. Awaiting Accountant approval.</p>
                    </div>
                <?php elseif($status == 'approved'): ?>
                    <div class="status-box approved">
                        <h3>Approved</h3>
                        <p>Your application has been approved. Waiting for Warden to allocate a room.</p>
                    </div>
                <?php elseif($status == 'rejected'): ?>
                    <div class="status-box rejected">
                        <h3>Application Rejected</h3>
                        <p>Please contact Accounts office for more information.</p>
                    </div>
                <?php elseif($status == 'allocated'): ?>
                    <div class="status-box allocated">
                        <h3>Room Allocated</h3>
                        <p>Congratulations! A room has been allocated to you.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>