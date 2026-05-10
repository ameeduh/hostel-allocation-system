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

$approvedStudents = $warden->viewApprovedStudents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Students - Warden Portal</title>
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
                    <h1>Approved Students</h1>
                    <p>Students ready for room allocation</p>
                </div>
            </div>

            <div class="content-card">
                <?php if(count($approvedStudents) > 0): ?>
                    <div class="students-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reg Number</th>
                                    <th>Student Name</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($approvedStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['regNumber']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year']; ?></td>
                                        <td><?php echo $student['gender']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Approved Students</h3>
                        <p>No students are ready for allocation yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>