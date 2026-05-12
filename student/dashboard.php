<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT u.name FROM students s JOIN users u ON s.userID = u.userID WHERE s.studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=22">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
        }

        /* Background Image for body */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../images/hans-beds-182964_1920.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
        }

        /* Dark overlay for readability */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 100vh;
        }

        /* Top Buttons Row */
        .buttons-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .nav-btn {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .nav-btn:hover {
            background-color: #A0522D;
        }

        .logout-btn {
            background-color: #000000;
        }

        .logout-btn:hover {
            background-color: #333333;
        }

        /* Divider */
        .divider {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, #FFD700, #8B4513, #FFD700);
            margin-bottom: 40px;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .welcome-section h2 {
            color: #8B4513;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #555;
            font-size: 16px;
        }

        /* Instruction Message */
        .instruction {
            text-align: center;
            color: white;
            font-size: 16px;
            margin-top: 50px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Top Buttons Row -->
        <div class="buttons-row">
            <a href="details.php" class="nav-btn">Complete Details</a>
            <a href="approval.php" class="nav-btn">Approval Status</a>
            <a href="room.php" class="nav-btn">Room Details</a>
            <a href="../logout.php" class="nav-btn logout-btn">Logout</a>
        </div>

        <!-- Divider Line -->
        <hr class="divider">

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome, <?php echo $studentData['name']; ?></h2>
            <p>Registration Number: <?php echo $_SESSION['username']; ?></p>
        </div>

        <!-- Instruction -->
        <div class="instruction">
            <p>Click on any button above to get started</p>
        </div>
    </div>
</body>
</html>