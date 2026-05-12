<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'accounts') {
    header("Location: ../login.php");
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
        header("Location: pending.php?approved=1");
        exit();
    }
    if(isset($_POST['reject'])) {
        $studentID = $_POST['studentID'];
        $accountant->rejectStudent($studentID);
        header("Location: pending.php?rejected=1");
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
    <link rel="stylesheet" href="../css/style.css?v=17">
    <style>
        .full-page-container {
            width: 100%;
            min-height: 100vh;
            background-color: #F5F5F5;
            padding: 40px;
        }
        .full-content-card {
            background-color: white;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #8B4513;
            color: white;
        }
        .btn-approve {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-reject {
            background-color: #000000;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .back-link-bottom {
            margin-top: 30px;
            text-align: right;
        }
        .back-btn {
            background-color: #8B4513;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Pending Students</h1>
            <p>Students approved by Registrar waiting for fee verification</p>

            <?php if($approved): ?>
                <div class="success-message">Student approved successfully!</div>
            <?php endif; ?>
            <?php if($rejected): ?>
                <div class="success-message">Student rejected successfully!</div>
            <?php endif; ?>

            <?php if(count($pendingStudents) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
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
            <?php else: ?>
                <p>No pending students. All students approved by Registrar have been processed.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>