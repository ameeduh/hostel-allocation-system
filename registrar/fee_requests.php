<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/mail_config.php';

$db = new Database();
$registrarName = $_SESSION['name'];

$message = '';
$messageType = '';
$showApproved = isset($_GET['view']) && $_GET['view'] == 'approved';

// Handle approve request
if(isset($_GET['approve'])) {
    $requestID = (int)$_GET['approve'];
    
    $requestSql = "SELECT * FROM fee_commitment_requests WHERE requestID = $requestID";
    $requestResult = $db->query($requestSql);
    $request = $requestResult->fetch_assoc();
    
    if($request) {
        $updateRequestSql = "UPDATE fee_commitment_requests SET status = 'approved', approvedBy = '$registrarName', approvedDate = CURDATE() WHERE requestID = $requestID";
        $db->query($updateRequestSql);
        
        $expiryDate = date('Y-m-d', strtotime('+4 weeks'));
        $updateStudentSql = "UPDATE students SET 
                                fee_commitment = 1,
                                fee_commitment_status = 'pending',
                                fee_commitment_expiry = '$expiryDate',
                                applicationStatus = 'pending'
                             WHERE studentID = " . $request['studentID'];
        $db->query($updateStudentSql);
        
        // Send email to the contact email provided by student
        if($request['contact_email']) {
            $subject = "Fee Commitment Request Approved";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $request['studentName'] . ",</p>
                     <p>Your fee commitment request has been APPROVED.</p>
                     <p>You have 4 weeks from today to complete your fee payment.</p>
                     <p><strong>Deadline:</strong> " . $expiryDate . "</p>
                     <p>Please ensure you pay before the deadline.</p>
                     </body></html>";
            sendEmail($request['contact_email'], $subject, $body);
        }
        
        header("Location: fee_requests.php?msg=approved");
        exit();
    }
}

// Handle reject request
if(isset($_GET['reject'])) {
    $requestID = (int)$_GET['reject'];
    
    $requestSql = "SELECT * FROM fee_commitment_requests WHERE requestID = $requestID";
    $requestResult = $db->query($requestSql);
    $request = $requestResult->fetch_assoc();
    
    if($request) {
        $updateRequestSql = "UPDATE fee_commitment_requests SET status = 'rejected' WHERE requestID = $requestID";
        $db->query($updateRequestSql);
        
        if($request['contact_email']) {
            $subject = "Fee Commitment Request Update";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $request['studentName'] . ",</p>
                     <p>Your fee commitment request has been REJECTED.</p>
                     <p>Please contact the Registrar's office for further clarification.</p>
                     </body></html>";
            sendEmail($request['contact_email'], $subject, $body);
        }
        
        header("Location: fee_requests.php?msg=rejected");
        exit();
    }
}

// Show message from redirect
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'approved') {
        $message = "Fee commitment request approved successfully.";
        $messageType = "success";
    } elseif($_GET['msg'] == 'rejected') {
        $message = "Fee commitment request rejected.";
        $messageType = "success";
    }
}

// Get pending requests
$pendingSql = "SELECT * FROM fee_commitment_requests WHERE status = 'pending' ORDER BY requestDate ASC";
$pendingResult = $db->query($pendingSql);
$pendingRequests = array();
if($pendingResult) {
    while($row = $pendingResult->fetch_assoc()) {
        $pendingRequests[] = $row;
    }
}

// Get approved requests
$approvedSql = "SELECT * FROM fee_commitment_requests WHERE status = 'approved' ORDER BY approvedDate DESC";
$approvedResult = $db->query($approvedSql);
$approvedRequests = array();
if($approvedResult) {
    while($row = $approvedResult->fetch_assoc()) {
        $approvedRequests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Requests - Registrar Portal</title>
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
        .welcome-section{background-color:white;margin:15px 40px;padding:15px 25px;border-radius:8px;border-left:5px solid #FFD700;}
        .welcome-section h1{font-size:20px;color:#333;}
        .content-area{margin:15px 40px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;}
        .sub-tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;}
        .sub-tab.active{background:#8B4513;color:white;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;font-size:13px;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;font-size:13px;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
        .btn-approve{background-color:#2e7d32;color:white;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;}
        .btn-approve:hover{background-color:#1b5e20;}
        .btn-reject{background-color:#c62828;color:white;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;font-size:12px;text-decoration:none;display:inline-block;}
        .btn-reject:hover{background-color:#b71c1c;}
        
        .reason-text{max-width:300px;word-wrap:break-word;}
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;}
            .nav-bar{flex-direction:column;}
            .data-table{overflow-x:auto;display:block;}
            .sub-tabs{flex-direction:column;}
        }
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
        <a href="dashboard.php?page=pending">Pending Students</a>
        <a href="blacklist.php">Blacklist</a>
        <a href="fee_requests.php" class="active">Fee Requests</a>
        <a href="dashboard.php?page=approved">Approved Students</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
</div>
<div class="content-area">
    <?php if($message): ?>
        <div class="success-message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="sub-tabs">
        <a href="fee_requests.php" class="sub-tab <?php echo (!$showApproved) ? 'active' : ''; ?>">Pending Requests</a>
        <a href="fee_requests.php?view=approved" class="sub-tab <?php echo ($showApproved) ? 'active' : ''; ?>">Approved Requests</a>
    </div>
    
    <?php if(!$showApproved): ?>
        <div class="content-card">
            <h2>Pending Requests</h2>
            <?php if(count($pendingRequests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Contact Email</th>
                                <th>Request Date</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['studentName']); ?></td>
                                    <td><?php echo htmlspecialchars($request['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($request['contact_email']); ?></td>
                                    <td><?php echo $request['requestDate']; ?></td>
                                    <td class="reason-text"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                                    <td>
                                        <a href="fee_requests.php?approve=<?php echo $request['requestID']; ?>" class="btn-approve" onclick="return confirm('Approve this request?')">Approve</a>
                                        <a href="fee_requests.php?reject=<?php echo $request['requestID']; ?>" class="btn-reject" onclick="return confirm('Reject this request?')">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending fee commitment requests.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if($showApproved): ?>
        <div class="content-card">
            <h2>Approved Requests</h2>
            <?php if(count($approvedRequests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Contact Email</th>
                                <th>Request Date</th>
                                <th>Approved By</th>
                                <th>Approved Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approvedRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['studentName']); ?></td>
                                    <td><?php echo htmlspecialchars($request['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($request['contact_email']); ?></td>
                                    <td><?php echo $request['requestDate']; ?></td>
                                    <td><?php echo $request['approvedBy']; ?></td>
                                    <td><?php echo $request['approvedDate']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved fee commitment requests.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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