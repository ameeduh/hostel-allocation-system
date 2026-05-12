<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
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
    <title>Approved Students - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=14">
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Approved Students</h1>

            <?php if(count($approvedStudents) > 0): ?>
                <div class="students-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approvedStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['program']; ?></td>
                                <td>
                                    <?php 
                                    $year = $student['year'];
                                    if($year == 1) echo '1st Year';
                                    elseif($year == 2) echo '2nd Year';
                                    elseif($year == 3) echo '3rd Year';
                                    elseif($year == 4) echo '4th Year';
                                    else echo 'N/A';
                                    ?>
                                </td>
                                <td><?php echo $student['gender']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved students waiting for allocation.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>