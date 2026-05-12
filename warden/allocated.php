<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocated Students - Warden Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=16">
    <style>
        .gender-buttons {
            display: flex;
            gap: 30px;
            justify-content: center;
            margin-top: 50px;
        }
        .gender-btn {
            display: inline-block;
            padding: 15px 40px;
            background-color: #8B4513;
            color: white;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .gender-btn:hover {
            background-color: #A0522D;
        }
        .gender-btn.male {
            background-color: #000000;
        }
        .gender-btn.male:hover {
            background-color: #333333;
        }
        .gender-btn.female {
            background-color: #8B4513;
        }
        .instruction {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Allocated Students</h1>
            <p class="instruction">Select gender to view allocated students</p>
            
            <div class="gender-buttons">
                <a href="allocated_male.php" class="gender-btn male">Male Students</a>
                <a href="allocated_female.php" class="gender-btn female">Female Students</a>
            </div>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>