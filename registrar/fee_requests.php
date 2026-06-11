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
$departmentFilter = isset($_GET['dept']) ? $_GET['dept'] : 'all';

// Function to build department filter WHERE clause
function getDepartmentFilter($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND regNumber LIKE '%BscBA%'";
    }
    return "";
}

$departmentWhere = getDepartmentFilter($departmentFilter);

// Handle approve request
if(isset($_GET['approve'])) {
    $requestID = (int)$_GET['approve'];
    
    $requestSql = "SELECT * FROM fee_commitment_requests WHERE requestID = $requestID";
    $requestResult = $db->query($requestSql);
    $request = $requestResult->fetch_assoc();
    
    if($request) {
        // Update fee_commitment_requests status
        $updateRequestSql = "UPDATE fee_commitment_requests SET status = 'approved', approvedBy = '$registrarName', approvedDate = CURDATE() WHERE requestID = $requestID";
        $db->query($updateRequestSql);
        
        // Get current semester and academic year
        $currentSemester = date('n') <= 6 ? 'Semester 1' : 'Semester 2';
        $currentYear = date('Y');
        $academic_year = $currentYear . '/' . ($currentYear + 1);
        
        // Check if student already exists in paid_students
        $checkPaidSql = "SELECT * FROM paid_students WHERE regNumber = '{$request['regNumber']}'";
        $checkPaidResult = $db->query($checkPaidSql);
        
        if($checkPaidResult && $checkPaidResult->num_rows == 0) {
            // Add to paid_students table
            $insertPaidSql = "INSERT INTO paid_students (regNumber, studentName, semester, academic_year, uploaded_by, uploaded_date, status) 
                              VALUES ('{$request['regNumber']}', '{$request['studentName']}', '$currentSemester', '$academic_year', 0, CURDATE(), 'active')";
            $db->query($insertPaidSql);
        } else {
            // Update existing record to active
            $updatePaidSql = "UPDATE paid_students SET status = 'active', semester = '$currentSemester', academic_year = '$academic_year' 
                              WHERE regNumber = '{$request['regNumber']}'";
            $db->query($updatePaidSql);
        }
        
        // Calculate expiry date (4 weeks from now)
        $expiryDate = date('Y-m-d', strtotime('+4 weeks'));
        
        // Update student record
        $updateStudentSql = "UPDATE students SET 
                                fee_commitment = 1,
                                fee_commitment_status = 'approved',
                                fee_commitment_expiry = '$expiryDate',
                                applicationStatus = 'approved',
                                approved_source = 'fee_commitment'
                             WHERE studentID = " . $request['studentID'];
        $db->query($updateStudentSql);
        
        // Send email to student
        if($request['contact_email']) {
            $subject = "Fee Commitment Request Approved";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $request['studentName'] . ",</p>
                     <p>Your fee commitment request has been <strong style='color:#2e7d32;'>APPROVED</strong>.</p>
                     <p>You have 4 weeks from today to complete your fee payment.</p>
                     <p><strong>Deadline:</strong> " . $expiryDate . "</p>
                     <p>You can now log in and select a room.</p>
                     <p>Please go to the <strong>Select Room</strong> page to choose your preferred room.</p>
                     <p>Regards,<br>Daeyang University</p>
                     </body></html>";
            sendEmail($request['contact_email'], $subject, $body);
        }
        
        header("Location: fee_requests.php?msg=approved&dept=" . $departmentFilter);
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
        
        // Update student record
        $updateStudentSql = "UPDATE students SET 
                                fee_commitment = 0,
                                fee_commitment_status = 'rejected',
                                applicationStatus = 'pending'
                             WHERE studentID = " . $request['studentID'];
        $db->query($updateStudentSql);
        
        if($request['contact_email']) {
            $subject = "Fee Commitment Request Update";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $request['studentName'] . ",</p>
                     <p>Your fee commitment request has been <strong style='color:#c62828;'>REJECTED</strong>.</p>
                     <p>Please contact the Registrar's office for further clarification.</p>
                     <p>Regards,<br>Daeyang University</p>
                     </body></html>";
            sendEmail($request['contact_email'], $subject, $body);
        }
        
        header("Location: fee_requests.php?msg=rejected&dept=" . $departmentFilter);
        exit();
    }
}

// Show message from redirect
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'approved') {
        $message = "Fee commitment request approved successfully. Student has been added to paid list and can now select a room.";
        $messageType = "success";
    } elseif($_GET['msg'] == 'rejected') {
        $message = "Fee commitment request rejected.";
        $messageType = "success";
    }
}

// Get pending requests (with department filter)
$pendingSql = "SELECT * FROM fee_commitment_requests WHERE status = 'pending' $departmentWhere ORDER BY requestDate ASC";
$pendingResult = $db->query($pendingSql);
$pendingRequests = array();
if($pendingResult) {
    while($row = $pendingResult->fetch_assoc()) {
        $pendingRequests[] = $row;
    }
}

// Get approved requests (with department filter)
$approvedSql = "SELECT * FROM fee_commitment_requests WHERE status = 'approved' $departmentWhere ORDER BY approvedDate DESC";
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
        
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;flex-wrap:wrap;}
        .sub-tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;}
        .sub-tab.active{background:#8B4513;color:white;}
        
        .filter-dropdown{margin-bottom:20px;display:flex;align-items:center;gap:15px;flex-wrap:wrap;}
        .filter-dropdown label{font-weight:600;color:#8B4513;font-size:14px;}
        .filter-dropdown select{padding:8px 15px;border:1px solid #ddd;border-radius:5px;font-size:13px;background:white;}
        
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
            .filter-dropdown{flex-direction:column;align-items:flex-start;}
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
        <a href="blacklist.php">Blacklist</a>
        <a href="fee_requests.php" class="active">Fee Requests</a>
        <a href="reports.php">Reports</a>
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
    
    <!-- Department Filter Dropdown -->
    <div class="filter-dropdown">
        <label>Filter by Department:</label>
        <select id="deptFilter" onchange="window.location.href='fee_requests.php<?php echo ($showApproved) ? '?view=approved&dept=' : '?dept='; ?>'+this.value">
            <option value="all" <?php echo ($departmentFilter == 'all') ? 'selected' : ''; ?>>All Departments</option>
            <option value="ict" <?php echo ($departmentFilter == 'ict') ? 'selected' : ''; ?>>ICT</option>
            <option value="nursing" <?php echo ($departmentFilter == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
            <option value="business" <?php echo ($departmentFilter == 'business') ? 'selected' : ''; ?>>Business Administration</option>
        </select>
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
                                <td><?php echo htmlspecialchars($request['requestDate']); ?></td>
                                <td class="reason-text"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></td>
                                <td>
                                    <a href="fee_requests.php?approve=<?php echo $request['requestID']; ?>&dept=<?php echo $departmentFilter; ?>" class="btn-approve" onclick="return confirm('Approve this request? Student will be added to paid list and can select a room.')">Approve</a>
                                    <a href="fee_requests.php?reject=<?php echo $request['requestID']; ?>&dept=<?php echo $departmentFilter; ?>" class="btn-reject" onclick="return confirm('Reject this request?')">Reject</a>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending fee commitment requests for the selected department.</p>
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
                                <td><?php echo htmlspecialchars($request['requestDate']); ?></td>
                                <td><?php echo htmlspecialchars($request['approvedBy']); ?></td>
                                <td><?php echo htmlspecialchars($request['approvedDate']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved fee commitment requests for the selected department.</p>
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