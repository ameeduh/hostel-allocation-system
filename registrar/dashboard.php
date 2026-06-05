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
$departmentFilter = isset($_GET['dept']) ? $_GET['dept'] : 'all';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $db->escape($_POST['phone']);
    $email = $db->escape($_POST['email']);
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = " . (int)$_SESSION['user_id'];
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

$profileUpdated = isset($_GET['updated']);

// Handle password change
$password_message = '';
$password_message_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $userSql = "SELECT password FROM users WHERE userID = " . (int)$_SESSION['user_id'];
    $userResult = $db->query($userSql);
    $user = $userResult->fetch_assoc();
    
    if(password_verify($current_password, $user['password'])) {
        if(strlen($new_password) < 6) {
            $password_message = "Password must be at least 6 characters long.";
            $password_message_type = "error";
        } elseif($new_password != $confirm_password) {
            $password_message = "New password and confirmation do not match.";
            $password_message_type = "error";
        } elseif(password_verify($new_password, $user['password'])) {
            $password_message = "New password must be different from your current password.";
            $password_message_type = "error";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE users SET password = '$hashed_password' WHERE userID = " . (int)$_SESSION['user_id'];
            if($db->query($updateSql)) {
                $password_message = "Password changed successfully!";
                $password_message_type = "success";
                
                $emailSql = "SELECT email, name FROM users WHERE userID = " . (int)$_SESSION['user_id'];
                $emailResult = $db->query($emailSql);
                $user = $emailResult->fetch_assoc();
                
                if($user && $user['email']) {
                    require_once '../config/mail_config.php';
                    $subject = "Password Changed Notification";
                    $body = "<html><body>
                             <h2>Hostel Allocation System</h2>
                             <p>Dear " . $user['name'] . ",</p>
                             <p>Your password was successfully changed on " . date('Y-m-d H:i:s') . ".</p>
                             <p>If you did not make this change, please contact the Administrator immediately.</p>
                             </body></html>";
                    sendEmail($user['email'], $subject, $body);
                }
            } else {
                $password_message = "Failed to change password. Please try again.";
                $password_message_type = "error";
            }
        }
    } else {
        $password_message = "Current password is incorrect.";
        $password_message_type = "error";
    }
}

// Function to build department filter WHERE clause
function getDepartmentFilter($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND s.regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND s.regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND s.regNumber LIKE '%BscBA%'";
    }
    return "";
}

$departmentWhere = getDepartmentFilter($departmentFilter);

// Get approved students (students who passed blacklist check)
$sql = "SELECT s.studentID, s.regNumber, s.program, s.year, u.name 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE (s.registrar_status = 'approved' OR s.registrar_status IS NULL) $departmentWhere
        ORDER BY s.studentID";
$result = $db->query($sql);
$approvedStudents = array();
if($result) {
    while($row = $result->fetch_assoc()) {
        $approvedStudents[] = $row;
    }
}

// Stats for home page
$totalStudents = 0;
$totalResult = $db->query("SELECT COUNT(*) as total FROM students");
if($totalResult) {
    $row = $totalResult->fetch_assoc();
    $totalStudents = $row['total'];
}

// Fee commitment count
$feeCommitmentCount = 0;
$feeCountSql = "SELECT COUNT(*) as total FROM students WHERE fee_commitment = 1";
$feeCountResult = $db->query($feeCountSql);
if($feeCountResult) {
    $row = $feeCountResult->fetch_assoc();
    $feeCommitmentCount = $row['total'];
}

// Approved students count
$approvedCount = count($approvedStudents);

// Blacklisted students count
$blacklistCount = 0;
$blacklistSql = "SELECT COUNT(*) as total FROM blacklist WHERE status = 'active'";
$blacklistResult = $db->query($blacklistSql);
if($blacklistResult) {
    $row = $blacklistResult->fetch_assoc();
    $blacklistCount = $row['total'];
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
        .welcome-section{background-color:white;margin:15px 40px;padding:15px 25px;border-radius:8px;border:1px solid #e0e0e0;border-left:5px solid #FFD700;}
        .welcome-section h1{font-size:20px;color:#333;margin-bottom:5px;}
        .content-area{margin:15px 40px;min-height:250px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .stats-cards{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap;}
        .stat-card{flex:1;min-width:150px;background:#f8f9fa;border-radius:5px;padding:15px;text-align:center;border:1px solid #e0e0e0;}
        .stat-number{font-size:28px;font-weight:bold;color:#8B4513;}
        .stat-label{font-size:12px;color:#666;margin-top:5px;}
        
        .filter-dropdown{margin-bottom:20px;display:flex;align-items:center;gap:15px;flex-wrap:wrap;}
        .filter-dropdown label{font-weight:600;color:#8B4513;font-size:14px;}
        .filter-dropdown select{padding:8px 15px;border:1px solid #ddd;border-radius:5px;font-size:13px;background:white;}
        .filter-dropdown select:focus{border-color:#8B4513;outline:none;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;font-size:13px;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;font-size:13px;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
        .profile-field{padding:10px;border-bottom:1px solid #eee;}
        .profile-field label{font-size:12px;color:#666;display:block;margin-bottom:3px;}
        .profile-field p{font-size:15px;font-weight:500;color:#333;}
        .form-row{display:flex;gap:15px;margin-bottom:12px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:4px;}
        .form-group input{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        .submit-btn{background-color:#8B4513;color:white;padding:8px 20px;border:none;border-radius:5px;cursor:pointer;font-size:14px;margin-top:10px;}
        
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;margin-left:20px;margin-right:20px;}
            .nav-bar{flex-direction:column;gap:10px;}
            .nav-links{justify-content:center;}
            .stats-cards{flex-direction:column;}
            .filter-dropdown{flex-direction:column;align-items:flex-start;}
            .data-table{overflow-x:auto;display:block;}
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
        <a href="blacklist.php">Blacklist</a>
        <a href="fee_requests.php">Fee Requests</a>
        <a href="?page=approved" class="<?php echo ($page=='approved')?'active':''; ?>">Approved Students</a>
        <a href="reports.php">Reports</a>
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
    
    <!-- HOME PAGE -->
    <?php if($page == 'home'): ?>
        <div class="content-card">
            <h2>Dashboard Overview</h2>
            <div class="stats-cards">
                <div class="stat-card"><div class="stat-number"><?php echo $totalStudents; ?></div><div class="stat-label">Total Students</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $feeCommitmentCount; ?></div><div class="stat-label">Fee Commitment</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $approvedCount; ?></div><div class="stat-label">Approved Students</div></div>
            </div>
            <p style="margin-top:15px; color:#666;">Use the menu above to manage blacklist, fee commitment requests, and view reports.</p>
        </div>
        
    <!-- APPROVED STUDENTS PAGE -->
    <?php elseif($page == 'approved'): ?>
        <div class="content-card">
            <h2>Approved Students</h2>
            
            <!-- Department Filter Dropdown -->
            <div class="filter-dropdown">
                <label>Filter by Department:</label>
                <select id="deptFilter" onchange="window.location.href='?page=approved&dept='+this.value">
                    <option value="all" <?php echo ($departmentFilter == 'all') ? 'selected' : ''; ?>>All Departments</option>
                    <option value="ict" <?php echo ($departmentFilter == 'ict') ? 'selected' : ''; ?>>ICT</option>
                    <option value="nursing" <?php echo ($departmentFilter == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
                    <option value="business" <?php echo ($departmentFilter == 'business') ? 'selected' : ''; ?>>Business Administration</option>
                </select>
            </div>
            
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
                <p>No approved students found for the selected department.</p>
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
            
            <h3 style="margin:20px 0 10px 0; color:#8B4513;">Update Contact Information</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo $userData['phone']; ?>"></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo $userData['email']; ?>"></div>
                </div>
                <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
            </form>
            
            <h3 style="margin:25px 0 10px 0; color:#8B4513;">Change Password</h3>
            <?php if($password_message): ?>
                <div class="<?php echo $password_message_type; ?>-message" style="margin-bottom:15px;"><?php echo $password_message; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                        <small style="color:#666;">Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="submit-btn">Change Password</button>
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
</body>
</html>