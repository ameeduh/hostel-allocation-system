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

$verifiedStudents = $accountant->viewVerifiedStudents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verified Students - Accountant Portal</title>
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
        .status-badge {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 3px 8px;
            border-radius: 5px;
            display: inline-block;
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
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Verified Students</h1>
            <p>Students approved by Registrar AND Accountant (ready for room allocation)</p>

            <?php if(count($verifiedStudents) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($verifiedStudents as $student): ?>
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
                            <td><span class="status-badge">Approved</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No verified students yet. Students must be approved by Registrar first.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>