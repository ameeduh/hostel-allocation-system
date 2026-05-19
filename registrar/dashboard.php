<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user has correct role
if($_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get user data
$sql = "SELECT name, email, phone FROM users WHERE userID = " . (int)$_SESSION['user_id'];
$result = $db->query($sql);
if($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    $userData = array('name' => 'User', 'email' => '', 'phone' => '');
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$subpage = isset($_GET['subpage']) ? $_GET['subpage'] : 'add';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $db->escape($_POST['phone']);
    $email = $db->escape($_POST['email']);
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = " . (int)$_SESSION['user_id'];
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

// Handle approve and reject for regular students
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle APPROVE for regular students
    if(isset($_POST['approve'])) {
        $studentID = (int)$_POST['studentID'];
        $disciplinary = isset($_POST['disciplinary']) ? $_POST['disciplinary'] : 'no';
        $hostel_conduct = isset($_POST['hostel_conduct']) ? $_POST['hostel_conduct'] : 'no';
        
        $sql = "UPDATE students SET 
                    registrar_status = 'approved',
                    disciplinary = '$disciplinary',
                    hostel_conduct = '$hostel_conduct'
                WHERE studentID = $studentID";
        $db->query($sql);
        header("Location: dashboard.php?page=pending&approved=1");
        exit();
    }
    
    // Handle REJECT for regular students
    if(isset($_POST['reject'])) {
        $studentID = (int)$_POST['studentID'];
        $reason = $db->escape($_POST['reason']);
        
        $emailSql = "SELECT u.email, u.name FROM students s JOIN users u ON s.userID = u.userID WHERE s.studentID = $studentID";
        $emailResult = $db->query($emailSql);
        $student = $emailResult->fetch_assoc();
        
        $sql = "UPDATE students SET registrar_status = 'rejected', registrar_reason = '$reason' WHERE studentID = $studentID";
        $db->query($sql);
        
        if($student && $student['email'] && !empty($student['email'])) {
            require_once '../config/mail_config.php';
            $subject = "Hostel Application - Eligibility Review Result";
            $body = "<html><body><h2>Hostel Allocation System</h2><p>Dear " . htmlspecialchars($student['name']) . ",</p><p>Your hostel application has been REJECTED by the Registrar's office.</p><p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p><p>Please contact the Registrar's office for further clarification.</p></body></html>";
            sendEmail($student['email'], $subject, $body);
        }
        
        header("Location: dashboard.php?page=pending&rejected=1");
        exit();
    }
    
    // Handle ADD FEE COMMITMENT
    if(isset($_POST['add_fee_commitment'])) {
        $regNumber = $db->escape($_POST['regNumber']);
        $name = $db->escape($_POST['name']);
        $program = $db->escape($_POST['program']);
        $year = (int)$_POST['year'];
        $gender = $db->escape($_POST['gender']);
        $feeNote = $db->escape($_POST['fee_note']);
        
        // Check if student already exists
        $checkSql = "SELECT studentID, userID FROM students WHERE regNumber = '$regNumber'";
        $checkResult = $db->query($checkSql);
        
        if($checkResult && $checkResult->num_rows > 0) {
            // Student exists - UPDATE all fields
            $studentData = $checkResult->fetch_assoc();
            $studentID = $studentData['studentID'];
            
            $updateSql = "UPDATE students SET 
                            fee_commitment = 1,
                            fee_commitment_status = 'pending',
                            fee_commitment_note = '$feeNote',
                            program = '$program',
                            year = $year,
                            gender = '$gender',
                            applicationStatus = 'pending'
                          WHERE studentID = $studentID";
            $db->query($updateSql);
            
            // Also update users table name
            $userID = $studentData['userID'];
            $updateUserSql = "UPDATE users SET name = '$name' WHERE userID = $userID";
            $db->query($updateUserSql);
        } else {
            // Create new student - INSERT all fields
            $userSql = "INSERT INTO users (username, password, name, email, phone, role) 
                        VALUES ('$regNumber', '', '$name', '', '', 'student')";
            $db->query($userSql);
            $userID = $db->getInsertId();
            
            $studentSql = "INSERT INTO students (userID, regNumber, program, year, gender, fee_commitment, fee_commitment_status, fee_commitment_note, applicationStatus) 
                           VALUES ($userID, '$regNumber', '$program', $year, '$gender', 1, 'pending', '$feeNote', 'pending')";
            $db->query($studentSql);
        }
        
        header("Location: dashboard.php?page=fee_commitment&subpage=list&added=1");
        exit();
    }
}

$profileUpdated = isset($_GET['updated']);
$approved = isset($_GET['approved']);
$rejected = isset($_GET['rejected']);
$feeAdded = isset($_GET['added']);

// Get pending students
$sql = "SELECT s.studentID, s.regNumber, s.program, s.year, u.name 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.applicationStatus = 'approved' 
        AND (s.registrar_status IS NULL OR s.registrar_status = 'pending')
        ORDER BY s.studentID";
$result = $db->query($sql);
$pendingStudents = array();
if($result) {
    while($row = $result->fetch_assoc()) {
        $pendingStudents[] = $row;
    }
}

// Get students on fee commitment list
$feeCommitmentSql = "SELECT s.studentID, s.regNumber, s.program, s.year, s.gender, s.fee_commitment_status, s.fee_commitment_note 
                     FROM students s 
                     WHERE s.fee_commitment = 1 
                     ORDER BY s.studentID DESC";
$feeResult = $db->query($feeCommitmentSql);
$feeCommitmentStudents = array();
if($feeResult) {
    while($row = $feeResult->fetch_assoc()) {
        // Get name from users table
        $userSql = "SELECT u.name FROM users u 
                    JOIN students s ON u.userID = s.userID 
                    WHERE s.studentID = " . $row['studentID'];
        $userResult = $db->query($userSql);
        if($userResult) {
            $userRow = $userResult->fetch_assoc();
            $row['name'] = $userRow['name'];
        } else {
            $row['name'] = '';
        }
        $feeCommitmentStudents[] = $row;
    }
}

$selectedStudentID = isset($_GET['review']) ? (int)$_GET['review'] : null;
$selectedStudent = null;
if($selectedStudentID) {
    $sql = "SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID WHERE s.studentID = $selectedStudentID";
    $result = $db->query($sql);
    if($result) {
        $selectedStudent = $result->fetch_assoc();
    }
}

// Stats
$totalStudents = 0;
$totalResult = $db->query("SELECT COUNT(*) as total FROM students");
if($totalResult) {
    $row = $totalResult->fetch_assoc();
    $totalStudents = $row['total'];
}

$pendingCount = count($pendingStudents);
$feeCommitmentCount = count($feeCommitmentStudents);

$approvedCount = 0;
$approvedCountSql = "SELECT COUNT(*) as total FROM students WHERE registrar_status = 'approved'";
$approvedCountResult = $db->query($approvedCountSql);
if($approvedCountResult) {
    $row = $approvedCountResult->fetch_assoc();
    $approvedCount = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Portal - Daeyang University</title>
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
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .stats-cards{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap;}
        .stat-card{flex:1;min-width:150px;background:#f8f9fa;border-radius:5px;padding:15px;text-align:center;border:1px solid #e0e0e0;}
        .stat-number{font-size:28px;font-weight:bold;color:#8B4513;}
        .stat-label{font-size:12px;color:#666;margin-top:5px;}
        
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;}
        .sub-tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;}
        .sub-tab.active{background:#8B4513;color:white;}
        .sub-tab:hover{background:#8B4513;color:white;}
        
        .student-buttons{display:flex;flex-wrap:wrap;gap:10px;margin:15px 0;}
        .student-btn{background-color:#FFF8DC;border:1px solid #FFD700;padding:8px 15px;border-radius:5px;text-decoration:none;color:#333;}
        .student-btn.active{background-color:#8B4513;color:white;}
        .eligibility-question{margin:15px 0;display:flex;align-items:center;flex-wrap:wrap;}
        .question-text{width:320px;font-weight:500;}
        .radio-group{display:flex;gap:20px;}
        .btn-approve{background-color:#2e7d32;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;margin-right:10px;}
        .btn-reject{background-color:#c62828;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;}
        .btn-add{background-color:#8B4513;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;font-size:13px;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;font-size:13px;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
        .fee-badge{background-color:#fff3cd;color:#856404;padding:3px 8px;border-radius:4px;display:inline-block;font-size:12px;}
        .form-row{display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        .profile-field{padding:10px;border-bottom:1px solid #eee;}
        .profile-field label{font-size:12px;color:#666;display:block;margin-bottom:3px;}
        .profile-field p{font-size:15px;font-weight:500;color:#333;}
        .submit-btn{background-color:#8B4513;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;font-size:14px;margin-top:10px;}
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        .success-message{background-color:#e8f5e9;color:#2e7d32;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;margin-left:20px;margin-right:20px;}
            .nav-bar{flex-direction:column;gap:10px;}
            .nav-links{justify-content:center;}
            .stats-cards{flex-direction:column;}
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
        <a href="?page=home" class="<?php echo ($page=='home')?'active':''; ?>">Home</a>
        <a href="?page=pending" class="<?php echo ($page=='pending')?'active':''; ?>">Pending Students</a>
        <a href="?page=fee_commitment&subpage=add" class="<?php echo ($page=='fee_commitment')?'active':''; ?>">Fee Commitment</a>
        <a href="?page=approved" class="<?php echo ($page=='approved')?'active':''; ?>">Approved Students</a>
        <a href="?page=profile" class="<?php echo ($page=='profile')?'active':''; ?>">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h1>
</div>
<div class="content-area">
    <?php if($profileUpdated): ?>
        <div class="success-message">Profile updated successfully!</div>
    <?php endif; ?>
    <?php if($approved): ?>
        <div class="success-message">Student approved successfully!</div>
    <?php endif; ?>
    <?php if($rejected): ?>
        <div class="success-message">Student rejected successfully!</div>
    <?php endif; ?>
    <?php if($feeAdded): ?>
        <div class="success-message">Student added to Fee Commitment list successfully!</div>
    <?php endif; ?>
    
    <!-- HOME PAGE -->
    <?php if($page == 'home'): ?>
        <div class="content-card">
            <h2>Dashboard Overview</h2>
            <div class="stats-cards">
                <div class="stat-card"><div class="stat-number"><?php echo $totalStudents; ?></div><div class="stat-label">Total Students</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $pendingCount; ?></div><div class="stat-label">Pending Review</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $feeCommitmentCount; ?></div><div class="stat-label">Fee Commitment</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $approvedCount; ?></div><div class="stat-label">Approved Students</div></div>
            </div>
        </div>
        
    <!-- PENDING STUDENTS PAGE -->
    <?php elseif($page == 'pending'): ?>
        <div class="content-card">
            <h2>Pending Students (Approved by Accountant)</h2>
            <div class="student-buttons">
                <?php foreach($pendingStudents as $student): ?>
                    <a href="?page=pending&review=<?php echo $student['studentID']; ?>" class="student-btn <?php echo ($selectedStudentID==$student['studentID'])?'active':''; ?>"><?php echo htmlspecialchars($student['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <?php if($selectedStudent): ?>
                <form method="POST">
                    <input type="hidden" name="studentID" value="<?php echo $selectedStudent['studentID']; ?>">
                    <div class="eligibility-question">
                        <span class="question-text">1. Does the student have any disciplinary hearing?</span>
                        <div class="radio-group">
                            <label><input type="radio" name="disciplinary" value="yes"> Yes</label>
                            <label><input type="radio" name="disciplinary" value="no" checked> No</label>
                        </div>
                    </div>
                    <div class="eligibility-question">
                        <span class="question-text">2. Does the student have any previous hostel conduct violation?</span>
                        <div class="radio-group">
                            <label><input type="radio" name="hostel_conduct" value="yes"> Yes</label>
                            <label><input type="radio" name="hostel_conduct" value="no" checked> No</label>
                        </div>
                    </div>
                    <div style="margin-top:20px;">
                        <button type="submit" name="approve" class="btn-approve" onclick="return confirm('Approve this student?')">Approve Student</button>
                        <button type="button" class="btn-reject" onclick="showRejectForm()">Reject Student</button>
                    </div>
                    <div id="rejectSection" style="display:none; margin-top:15px;">
                        <textarea name="reason" id="reject_reason" rows="3" placeholder="Enter rejection reason..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px;"></textarea>
                        <button type="submit" name="reject" class="btn-reject" style="margin-top:10px;" onclick="return confirm('Reject this student?')">Confirm Reject</button>
                        <button type="button" class="btn-approve" style="margin-top:10px; background-color:#666;" onclick="hideRejectForm()">Cancel</button>
                    </div>
                </form>
            <?php elseif(count($pendingStudents) > 0): ?>
                <p>Click on a student name above to review their eligibility.</p>
            <?php else: ?>
                <p>No pending students.</p>
            <?php endif; ?>
        </div>
        
    <!-- FEE COMMITMENT PAGE -->
    <?php elseif($page == 'fee_commitment'): ?>
        <div class="content-card">
            <h2>Fee Commitment Management</h2>
            
            <div class="sub-tabs">
                <a href="?page=fee_commitment&subpage=add" class="sub-tab <?php echo ($subpage=='add')?'active':''; ?>">Add Fee Commitment</a>
                <a href="?page=fee_commitment&subpage=list" class="sub-tab <?php echo ($subpage=='list')?'active':''; ?>">Fee Commitment List</a>
            </div>
            
            <?php if($subpage == 'add'): ?>
                <h3 style="margin-bottom:15px; color:#8B4513;">Add Student to Fee Commitment</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="regNumber" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Program</label>
                            <input type="text" name="program" required>
                        </div>
                        <div class="form-group">
                            <label>Year of Study</label>
                            <select name="year" required>
                                <option value="">Select Year</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fee Commitment Note</label>
                            <textarea name="fee_note" rows="3" placeholder="Reason for fee commitment..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:5px; font-size:13px;" required></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_fee_commitment" class="btn-add">Add to Fee Commitment</button>
                </form>
            <?php endif; ?>
            
            <?php if($subpage == 'list'): ?>
                <h3 style="margin-bottom:15px; color:#8B4513;">Students on Fee Commitment</h3>
                <?php if(count($feeCommitmentStudents) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Reg Number</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                    <th>Fee Note</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($feeCommitmentStudents as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($student['program']); ?></td>
                                        <td><?php echo $student['year']; ?> Year</td>
                                        <td><?php echo $student['gender']; ?></td>
                                        <td><?php echo htmlspecialchars($student['fee_commitment_note']); ?></td>
                                        <td>
                                            <?php if($student['fee_commitment_status'] == 'approved'): ?>
                                                <span class="fee-badge" style="background:#d4edda; color:#155724;">Approved</span>
                                            <?php elseif($student['fee_commitment_status'] == 'rejected'): ?>
                                                <span class="fee-badge" style="background:#f8d7da; color:#721c24;">Rejected</span>
                                            <?php else: ?>
                                                <span class="fee-badge">Pending Accountant</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No students on Fee Commitment list.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
    <!-- APPROVED STUDENTS PAGE -->
    <?php elseif($page == 'approved'): ?>
        <?php
        $sql = "SELECT s.studentID, s.regNumber, s.program, s.year, u.name 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                WHERE s.registrar_status = 'approved'";
        $result = $db->query($sql);
        $approvedStudents = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $approvedStudents[] = $row;
            }
        }
        ?>
        <div class="content-card">
            <h2>Approved Students</h2>
            <?php if(count($approvedStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Reg Number</th>
                                <th>Program</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approvedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo $student['year']; ?> Year</td
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved students yet.</p>
            <?php endif; ?>
        </div>
        
    <!-- PROFILE PAGE -->
    <?php elseif($page == 'profile'): ?>
        <div class="content-card">
            <h2>My Profile</h2>
            <div class="profile-field"><label>Full Name</label><p><?php echo htmlspecialchars($userData['name']); ?></p></div>
            <div class="profile-field"><label>Email Address</label><p><?php echo $userData['email'] ?: 'Not set'; ?></p></div>
            <div class="profile-field"><label>Phone Number</label><p><?php echo $userData['phone'] ?: 'Not set'; ?></p></div>
            <div class="profile-field"><label>Role</label><p>Registrar</p></div>
            <h3>Update Contact Information</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo $userData['phone']; ?>"></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo $userData['email']; ?>"></div>
                </div>
                <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
            </form>
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

<script>
    function showRejectForm() {
        document.getElementById('rejectSection').style.display = 'block';
    }
    function hideRejectForm() {
        document.getElementById('rejectSection').style.display = 'none';
        document.getElementById('reject_reason').value = '';
    }
</script>
</body>
</html>