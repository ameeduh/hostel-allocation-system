<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

// Process clearance request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "INSERT INTO clearance (studentID, requestDate, status) VALUES ('$studentID', CURDATE(), 'pending')";
    
    if($db->query($sql)) {
        $success = "Clearance request submitted successfully. Please wait for Warden approval.";
    } else {
        $error = "Failed to submit clearance request. Error: " . $db->getConnection()->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Clearance - Daeyang University</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background-color:#f5f5f5;}
        .top-bar{background-color:#8B4513;color:white;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;}
        .prayer-love{font-size:16px;}
        .solideo{font-size:16px;font-weight:500;}
        .nav-bar{background-color:white;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;border-bottom:1px solid #e0e0e0;}
        .university-name{font-size:22px;font-weight:700;color:#8B4513;}
        .nav-links{display:flex;gap:25px;flex-wrap:wrap;}
        .nav-links a{text-decoration:none;color:#333;font-size:15px;font-weight:500;}
        .nav-links a:hover{color:#8B4513;}
        .nav-links a.active{color:#8B4513;font-weight:600;}
        .welcome-section{background-color:white;margin:15px 40px;padding:15px 25px;border-radius:8px;border:1px solid #e0e0e0;}
        .welcome-section h1{font-size:20px;color:#333;margin-bottom:5px;}
        .content-area{margin:15px 40px;min-height:250px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;text-align:center;}
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;}
        .btn-submit{background-color:#8B4513;color:white;border:none;padding:10px 25px;border-radius:5px;cursor:pointer;font-size:14px;}
        .btn-submit:hover{background-color:#6d3710;}
        .btn-back{background-color:#666;color:white;text-decoration:none;padding:10px 25px;border-radius:5px;display:inline-block;margin-top:10px;}
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
    </style>
</head>
<body>
<div class="top-bar">
    <div class="prayer-love">Prayer | Love | Servantship</div>
    <div class="solideo">Solideo</div>
</div>
<div class="nav-bar">
    <div class="university-name">Daeyang University</div>
    <div class="nav-links">
        <a href="dashboard.php?page=home">Home</a>
        <a href="dashboard.php?page=details">Complete Details</a>
        <a href="apply_room.php">Select Room</a>
        <a href="dashboard.php?page=room">Room Details</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="request_fee_commitment.php">Request Fee Commitment</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
</div>
<div class="content-area">
    <div class="content-card">
        <h2>Request Room Clearance</h2>
        <p style="margin-bottom:20px;">Are you sure you want to request clearance? This will vacate your room.</p>
        
        <?php if(isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
            <a href="dashboard.php?page=room" class="btn-back">Back to Room Details</a>
        <?php elseif(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <a href="dashboard.php?page=room" class="btn-back">Back to Room Details</a>
        <?php else: ?>
            <form method="POST">
                <button type="submit" class="btn-submit" onclick="return confirm('Are you sure you want to request clearance? This will vacate your room and make it available for others.')">Confirm Clearance Request</button>
                <br><br>
                <a href="dashboard.php?page=room" class="btn-back">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>
<div class="footer">
    <div class="footer-content">
        <div class="footer-section"><h3>About Us</h3><p>Daeyang University is a Christian University founded by the Miracle for Africa Foundation.</p></div>
        <div class="footer-section"><h3>Quick Links</h3><p><a href="#">Home</a></p><p><a href="#">About Us</a></p><p><a href="#">Contact Us</a></p></div>
        <div class="footer-section"><h3>Contact Us</h3><p>+265994000389</p><p>registrar@dyuni.ac.mw</p></div>
    </div>
    <div class="copyright">&copy; <?php echo date('Y'); ?> Daeyang University. All rights reserved.</div>
</div>
</body>
</html>