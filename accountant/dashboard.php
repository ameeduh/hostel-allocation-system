<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'accounts') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Portal - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar (No Icons) -->
        <div class="sidebar">
            <div class="logo">
                <h2>Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link">
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Welcome back, Accountant</h1>
                    <p>Accountant Portal</p>
                </div>
            </div>

            <!-- Horizontal Cards (No Icons) -->
            <div class="accountant-grid">
                <div class="action-card" onclick="window.location.href='pending_students.php'">
                    <h3>Pending Students</h3>
                    <p>View students waiting for fee verification</p>
                    <span class="action-btn">View Pending →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='verified_students.php'">
                    <h3>Verified Students</h3>
                    <p>View already approved students</p>
                    <span class="action-btn">View Verified →</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>