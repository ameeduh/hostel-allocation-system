<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
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
    <title>Approval Status - Student Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=21">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F5F5;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            background-color: #8B4513;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .back-btn:hover {
            background-color: #A0522D;
        }

        .status-card {
            background-color: white;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }

        h1 {
            color: #8B4513;
            margin-bottom: 30px;
        }

        .status-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .status-message {
            color: #666;
            line-height: 1.6;
        }

        .pending { color: #ed6c02; }
        .approved { color: #2e7d32; }
        .rejected { color: #c62828; }
        .allocated { color: #2e7d32; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="status-card">
            <h1>Approval Status</h1>
            
            <?php if($status == 'pending'): ?>
                <div class="status-icon">⏳</div>
                <div class="status-title pending">Pending Approval</div>
                <div class="status-message">Your application has been submitted. Awaiting Accountant approval.</div>
            <?php elseif($status == 'approved'): ?>
                <div class="status-icon">✅</div>
                <div class="status-title approved">Approved</div>
                <div class="status-message">Your application has been approved. Waiting for Warden to allocate a room.</div>
            <?php elseif($status == 'rejected'): ?>
                <div class="status-icon">❌</div>
                <div class="status-title rejected">Application Rejected</div>
                <div class="status-message">Please contact Accounts office for more information.</div>
            <?php elseif($status == 'allocated'): ?>
                <div class="status-icon">🎉</div>
                <div class="status-title allocated">Room Allocated</div>
                <div class="status-message">Congratulations! A room has been allocated to you.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>