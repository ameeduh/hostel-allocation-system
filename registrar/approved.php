<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

$sql = "SELECT s.*, u.name 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.registrar_status = 'approved'
        ORDER BY s.studentID";
$result = $db->query($sql);
$approvedStudents = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Students - Registrar Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=17">
    <style>
        .student-card {
            background-color: #FFF8DC;
            border: 1px solid #FFD700;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .student-details {
            font-size: 14px;
        }
        .student-details strong {
            color: #8B4513;
        }
        .status-badge {
            display: inline-block;
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Approved Students</h1>
            <p>Students approved by Registrar</p>

            <?php if(count($approvedStudents) > 0): ?>
                <?php foreach($approvedStudents as $student): ?>
                    <div class="student-card">
                        <div class="student-details">
                            <strong><?php echo $student['name']; ?></strong><br>
                            <strong>Registration Number:</strong> <?php echo $student['regNumber']; ?><br>
                            <strong>Program:</strong> <?php echo $student['program'] ?: 'Not yet filled'; ?><br>
                            <strong>Year:</strong> <?php echo $student['year'] ?: 'Not yet filled'; ?><br>
                            <strong>Gender:</strong> <?php echo $student['gender'] ?: 'Not yet filled'; ?><br>
                            <strong>Status:</strong> <span class="status-badge">Approved by Registrar</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No approved students yet.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>