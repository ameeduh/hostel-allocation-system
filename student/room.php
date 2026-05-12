<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT allocatedRoomID, applicationStatus, allocatedDate FROM students WHERE studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();

$allocatedRoomID = $studentData['allocatedRoomID'];
$status = $studentData['applicationStatus'];

$room = null;
if($status == 'allocated' && $allocatedRoomID) {
    $roomSql = "SELECT * FROM rooms WHERE roomID = $allocatedRoomID";
    $roomResult = $db->query($roomSql);
    $room = $roomResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Details - Student Portal</title>
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

        .room-card {
            background-color: white;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h1 {
            color: #8B4513;
            margin-bottom: 25px;
            text-align: center;
        }

        .room-info {
            margin: 20px 0;
        }

        .room-info p {
            margin: 12px 0;
            padding: 10px;
            background-color: #FFF8DC;
            border-radius: 8px;
        }

        .room-info strong {
            color: #8B4513;
            display: inline-block;
            width: 140px;
        }

        .clearance-btn {
            display: block;
            text-align: center;
            background-color: #FFD700;
            color: #000;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }

        .clearance-btn:hover {
            background-color: #e6c200;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="room-card">
            <h1>Room Details</h1>
            
            <?php if($status == 'allocated' && $room): ?>
                <div class="room-info">
                    <p><strong>Room Number:</strong> <?php echo $room['roomNumber']; ?></p>
                    <p><strong>Hostel Name:</strong> <?php echo $room['hostelName']; ?></p>
                    <p><strong>Gender:</strong> <?php echo $room['gender']; ?></p>
                    <p><strong>Allocation Date:</strong> <?php echo $studentData['allocatedDate']; ?></p>
                </div>
                <a href="request_clearance.php" class="clearance-btn" onclick="return confirm('Request clearance? This will vacate your room.')">Request Clearance</a>
            <?php else: ?>
                <div class="empty-state">
                    <p>No room has been allocated to you yet.</p>
                    <p>Please wait for the Warden.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>