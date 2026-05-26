<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];
$regNumber = $_SESSION['username'];
$studentName = $_SESSION['name'];

$message = '';
$messageType = '';

// Check if student already has pending request
$checkSql = "SELECT * FROM fee_commitment_requests WHERE studentID = $studentID AND status = 'pending'";
$checkResult = $db->query($checkSql);
$hasPending = $checkResult && $checkResult->num_rows > 0;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $reason = $db->escape($_POST['reason']);
    $contactEmail = $db->escape($_POST['contact_email']);
    
    $insertSql = "INSERT INTO fee_commitment_requests (studentID, regNumber, studentName, reason, requestDate, status, contact_email) 
                  VALUES ($studentID, '$regNumber', '$studentName', '$reason', NOW(), 'pending', '$contactEmail')";
    
    if($db->query($insertSql)) {
        $message = "Your fee commitment request has been submitted. The Registrar will review it and you will be notified via email at $contactEmail.";
        $messageType = "success";
        $hasPending = true;
        
        // Send confirmation email to the provided email
        if($contactEmail) {
            require_once '../config/mail_config.php';
            $subject = "Fee Commitment Request Received";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear $studentName,</p>
                     <p>Your fee commitment request has been received.</p>
                     <p>The Registrar will review your request and you will receive an email at $contactEmail once a decision is made.</p>
                     <p><strong>Your Request:</strong></p>
                     <p>" . nl2br(htmlspecialchars($reason)) . "</p>
                     </body></html>";
            sendEmail($contactEmail, $subject, $body);
        }
    } else {
        $message = "Failed to submit request. Please try again.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Fee Commitment - Daeyang University</title>
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
        .welcome-section{background-color:white;margin:15px 40px;padding:15px 25px;border-radius:8px;border:1px solid #e0e0e0;border-left:5px solid #FFD700;}
        .welcome-section h1{font-size:20px;color:#333;margin-bottom:5px;}
        .content-area{margin:15px 40px;min-height:250px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .form-group{margin-bottom:15px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;font-size:13px;font-family:inherit;}
        .form-group textarea{resize:vertical;}
        .form-group input:focus,.form-group textarea:focus{border-color:#8B4513;outline:none;}
        
        .btn-submit{background-color:#8B4513;color:white;border:none;padding:10px 25px;border-radius:5px;cursor:pointer;font-size:14px;font-weight:600;}
        .btn-submit:hover{background-color:#6d3710;}
        
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        .info-message{background-color:#d1ecf1;color:#0c5460;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){.top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;}.nav-bar{flex-direction:column;gap:10px;}}
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
            <a href="request_fee_commitment.php" class="active">Request Fee Commitment</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="welcome-section">
        <h1>Welcome, <?php echo $studentName; ?>!</h1>
        <p>Registration Number: <?php echo $regNumber; ?></p>
    </div>

    <div class="content-area">
        <div class="content-card">
            <h2>Request Fee Commitment</h2>
            
            <?php if($message): ?>
                <div class="<?php echo $messageType; ?>-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($hasPending): ?>
                <div class="info-message">
                    You already have a pending fee commitment request. The Registrar will review it and you will be notified via email.
                </div>
            <?php else: ?>
                <p style="margin-bottom:20px; color:#666;">If you are unable to pay fees due to genuine reasons, please submit a request below. The Registrar will review your request and approve if eligible. If approved, you will have 4 weeks to complete payment.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address for Notification</label>
                        <input type="email" name="contact_email" required placeholder="Enter your email address where you want to receive updates">
                    </div>
                    <div class="form-group">
                        <label>Reason for Fee Commitment Request</label>
                        <textarea name="reason" rows="6" placeholder="Please explain in detail why you need fee commitment (e.g., awaiting sponsorship, family financial difficulties, scholarship delays, etc.)" required></textarea>
                    </div>
                    <button type="submit" name="submit_request" class="btn-submit">Submit Request</button>
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