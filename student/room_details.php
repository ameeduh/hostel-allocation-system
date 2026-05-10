<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT s.allocatedRoomID, s.applicationStatus, s.allocatedDate
        FROM students s 
        WHERE s.studentID = $studentID";
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
    <title>Room Details - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=4">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>🏠 Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="dashboard.php" class="logout-link">
                    <span class="nav-icon">←</span>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Room Details</h1>
                </div>
            </div>

            <div class="content-card">
                <?php if($status == 'allocated' && $room): ?>
                    <div class="room-info-grid">
                        <div class="room-info-item">
                            <label>Room Number</label>
                            <p><?php echo $room['roomNumber']; ?></p>
                        </div>
                        <div class="room-info-item">
                            <label>Hostel Name</label>
                            <p><?php echo $room['hostelName']; ?></p>
                        </div>
                        <div class="room-info-item">
                            <label>Gender</label>
                            <p><?php echo $room['gender']; ?></p>
                        </div>
                        <div class="room-info-item">
                            <label>Allocation Date</label>
                            <p><?php echo date('d-m-Y'); ?></p>
                        </div>
                    </div>
                    <a href="request_clearance.php" class="clearance-btn" onclick="return confirm('Request clearance? This will vacate your room.')">Request Clearance</a>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🏠</div>
                        <h3>No Room Allocated Yet</h3>
                        <p>Your room will appear here once the Warden allocates one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>