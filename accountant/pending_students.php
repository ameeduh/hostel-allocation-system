<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'accounts') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../classes/Accountant.php';

$accountant = new Accountant();
$accountant->login($_SESSION['username'], 'password123');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['approve'])) {
        $studentID = $_POST['studentID'];
        $accountant->verifyFees($studentID);
        header("Location: pending_students.php?approved=1");
        exit();
    }
    if(isset($_POST['reject'])) {
        $studentID = $_POST['studentID'];
        $accountant->rejectStudent($studentID);
        header("Location: pending_students.php?rejected=1");
        exit();
    }
}

$pendingStudents = $accountant->viewPendingStudents();
$approved = isset($_GET['approved']);
$rejected = isset($_GET['rejected']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Students - Accountant Portal</title>
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
                    <h1>Pending Students</h1>
                    <p>Students waiting for fee verification</p>
                </div>
            </div>

            <?php if($approved): ?>
                <div class="success-message">Student approved successfully!</div>
            <?php endif; ?>
            <?php if($rejected): ?>
                <div class="success-message">Student rejected successfully!</div>
            <?php endif; ?>

            <div class="content-card">
                <?php if(count($pendingStudents) > 0): ?>
                    <div class="students-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reg Number</th>
                                    <th>Student Name</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pendingStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['regNumber']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year']; ?></td>
                                        <td><?php echo $student['gender']; ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                                <button type="submit" name="approve" class="btn-approve" onclick="return confirm('Approve this student?')">Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                                <button type="submit" name="reject" class="btn-reject" onclick="return confirm('Reject this student?')">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Pending Students</h3>
                        <p>All students have been processed.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>