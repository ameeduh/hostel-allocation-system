<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Debug - see what role is actually in session
// echo "Role: " . $_SESSION['role']; exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Dashboard - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=8">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
                    <p>Warden Portal</p>
                </div>
            </div>

            <div class="actions-grid">
                <div class="action-card" onclick="window.location.href='approved_students.php'">
                    <h3>Approved Students</h3>
                    <p>View students ready for allocation</p>
                    <span class="action-btn">View →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='allocate_room.php'">
                    <h3>Allocate Room</h3>
                    <p>Assign a room to a student</p>
                    <span class="action-btn">Allocate →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='allocated_students.php'">
                    <h3>Allocated Students</h3>
                    <p>View students with rooms</p>
                    <span class="action-btn">View →</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>