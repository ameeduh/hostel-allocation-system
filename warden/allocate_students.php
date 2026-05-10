<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../classes/Warden.php';

$warden = new Warden();
$warden->login($_SESSION['username'], 'password123');

// Handle vacate
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vacate'])) {
    $studentID = $_POST['studentID'];
    if($warden->vacateRoom($studentID)) {
        header("Location: allocated_students.php?vacated=1");
    } else {
        header("Location: allocated_students.php?error=1");
    }
    exit();
}

$allocatedStudents = $warden->viewAllocatedStudents();
$vacated = isset($_GET['vacated']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocated Students - Warden Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=4">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>🔑 Hostel</h2>
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
                    <h1>📋 Allocated Students</h1>
                    <p>Students who have been assigned rooms</p>
                </div>
            </div>

            <?php if($vacated): ?>
                <div class="success-message">✓ Room vacated successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">✗ Failed to vacate room.</div>
            <?php endif; ?>

            <div class="content-card">
                <?php if(count($allocatedStudents) > 0): ?>
                    <div class="students-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reg Number</th>
                                    <th>Student Name</th>
                                    <th>Room Number</th>
                                    <th>Hostel Name</th>
                                    <th>Allocation Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allocatedStudents as $student): ?>
                                <tr>
                                    <td><?php echo $student['regNumber']; ?></td>
                                    <td><?php echo $student['studentName']; ?></td>
                                    <td><?php echo $student['roomNumber']; ?></td>
                                    <td><?php echo $student['hostelName']; ?></td>
                                    <td><?php echo $student['allocatedDate']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                            <button type="submit" name="vacate" class="btn-vacate" onclick="return confirm('Vacate this room?')">Vacate</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>No Allocated Students</h3>
                        <p>No students have been allocated rooms yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>