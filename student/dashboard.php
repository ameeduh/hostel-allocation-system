<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT s.*, u.name, u.phone, u.email 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();

$hasDetails = ($studentData['year'] > 0 && $studentData['gender'] != '' && $studentData['guardian_name'] != '');
$status = $studentData['applicationStatus'];
$registrarStatus = $studentData['registrar_status'];

$regNumber = $_SESSION['username'];
$detectedProgram = '';

if(strpos($regNumber, 'BscICT') !== false) {
    $detectedProgram = 'ICT';
} elseif(strpos($regNumber, 'BscNM') !== false) {
    $detectedProgram = 'Nursing';
} elseif(strpos($regNumber, 'BscBA') !== false) {
    $detectedProgram = 'Business Administration';
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

// Handle details form submission with blacklist check and auto-approval
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_details'])) {
    // Get email from form submission
    $submittedEmail = $_POST['email'];
    
    // FIRST: Check if student is blacklisted
    $blacklistSql = "SELECT * FROM blacklist WHERE regNumber = '$regNumber' AND status = 'active'";
    $blacklistResult = $db->query($blacklistSql);
    
    if($blacklistResult && $blacklistResult->num_rows > 0) {
        $blacklist = $blacklistResult->fetch_assoc();
        
        $updateSql = "UPDATE students SET 
                        applicationStatus = 'rejected', 
                        registrar_status = 'rejected', 
                        registrar_reason = 'Blacklisted: " . $blacklist['reason'] . "' 
                      WHERE studentID = $studentID";
        $db->query($updateSql);
        
        // Update user email if provided
        if($submittedEmail) {
            $db->query("UPDATE users SET email = '$submittedEmail' WHERE userID = {$_SESSION['user_id']}");
        }
        
        // Send rejection email
        if($submittedEmail) {
            require_once '../config/mail_config.php';
            $subject = "Hostel Application Status - Rejected";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $_SESSION['name'] . ",</p>
                     <p>Your hostel application has been <strong style='color:#c62828;'>REJECTED</strong>.</p>
                     <p><strong>Reason:</strong> " . $blacklist['reason'] . "</p>
                     <p>You have been blacklisted. Please contact the Registrar's office.</p>
                     </body></html>";
            sendEmail($submittedEmail, $subject, $body);
        }
        
        header("Location: dashboard.php?page=details&blacklisted=1");
        exit();
    }
    
    // SECOND: Check if student is in paid_students list (auto-approve)
    $paidSql = "SELECT * FROM paid_students WHERE regNumber = '$regNumber' AND status = 'active'";
    $paidResult = $db->query($paidSql);
    $isPaid = ($paidResult && $paidResult->num_rows > 0);
    
    // Save all student details first
    $program = $detectedProgram;
    $year = $_POST['year'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $medical_condition = $_POST['medical_condition'];
    $medical_condition_details = ($medical_condition == 'yes') ? $_POST['medical_condition_details'] : '';
    $guardian_name = $_POST['guardian_name'];
    $guardian_relationship = $_POST['guardian_relationship'];
    $guardian_phone = $_POST['guardian_phone'];
    $agreement = isset($_POST['agreement']) ? 1 : 0;
    
    if($agreement != 1) {
        header("Location: dashboard.php?page=details&error=1");
        exit();
    }
    
    $sql1 = "UPDATE students SET 
                program = '$program',
                year = '$year', 
                gender = '$gender', 
                address = '$address',
                medical_condition = '$medical_condition',
                medical_condition_details = '$medical_condition_details',
                guardian_name = '$guardian_name',
                guardian_relationship = '$guardian_relationship',
                guardian_phone = '$guardian_phone',
                agreement_confirmed = 1,
                applicationStatus = '" . ($isPaid ? 'approved' : 'pending') . "',
                approved_source = " . ($isPaid ? "'paid_list'" : "NULL") . "
            WHERE studentID = $studentID";
    $db->query($sql1);
    
    $sql2 = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql2);
    
    // If student is in paid list, send approval email
    if($isPaid && $submittedEmail) {
        require_once '../config/mail_config.php';
        $subject = "Hostel Application - Approved!";
        $body = "<html><body>
                 <h2>Hostel Allocation System</h2>
                 <p>Dear " . $_SESSION['name'] . ",</p>
                 <p>Congratulations! Your hostel application has been <strong style='color:#2e7d32;'>APPROVED</strong>.</p>
                 <p>You can now log in and select a room.</p>
                 <p>Please go to the <strong>Select Room</strong> page to choose your preferred room.</p>
                 <p>Once you select a room, it will be allocated to you immediately.</p>
                 <p>Regards,<br>Daeyang University</p>
                 </body></html>";
        sendEmail($submittedEmail, $subject, $body);
    }
    
    if($isPaid) {
        header("Location: dashboard.php?page=details&approved=1");
    } else {
        header("Location: dashboard.php?page=details&success=1");
    }
    exit();
}

$success = isset($_GET['success']);
$approved = isset($_GET['approved']);
$error = isset($_GET['error']);
$profileUpdated = isset($_GET['updated']);
$blacklisted = isset($_GET['blacklisted']);

// Handle password change
$password_message = '';
$password_message_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $userSql = "SELECT password FROM users WHERE userID = {$_SESSION['user_id']}";
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
            $updateSql = "UPDATE users SET password = '$hashed_password' WHERE userID = {$_SESSION['user_id']}";
            if($db->query($updateSql)) {
                $password_message = "Password changed successfully!";
                $password_message_type = "success";
                
                $emailSql = "SELECT email, name FROM users WHERE userID = {$_SESSION['user_id']}";
                $emailResult = $db->query($emailSql);
                $user = $emailResult->fetch_assoc();
                
                if($user && $user['email']) {
                    require_once '../config/mail_config.php';
                    $subject = "Password Changed Notification";
                    $body = "<html><body><h2>Hostel Allocation System</h2><p>Dear " . $user['name'] . ",</p><p>Your password was successfully changed on " . date('Y-m-d H:i:s') . ".</p><p>If you did not make this change, please contact the Administrator immediately.</p></body></html>";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Daeyang University</title>
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
        
        .form-row{display:flex;gap:15px;margin-bottom:12px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:4px;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        .submit-btn{background-color:#8B4513;color:white;padding:10px 20px;border:none;border-radius:5px;font-size:14px;font-weight:600;cursor:pointer;margin-top:10px;}
        
        .status-box{text-align:center;padding:20px;border-radius:8px;}
        .status-box.pending{background:#FFF8DC;}
        .status-box.approved{background:#e8f5e9;}
        .status-box.rejected{background:#ffebee;}
        .status-box.allocated{background:#e8f5e9;}
        .status-icon{font-size:40px;margin-bottom:10px;}
        .status-title{font-size:18px;font-weight:bold;margin-bottom:8px;}
        
        .room-info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;}
        .room-info-item label{font-size:11px;color:#666;display:block;margin-bottom:3px;}
        .room-info-item p{font-size:15px;font-weight:600;color:#8B4513;}
        .clearance-btn{display:inline-block;background-color:#FFD700;color:#000;padding:8px 16px;border-radius:5px;text-decoration:none;font-weight:600;margin-top:15px;font-size:13px;}
        
        .profile-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;margin-bottom:20px;}
        .profile-field{padding:8px;border-bottom:1px solid #eee;}
        .profile-field label{font-size:11px;color:#666;display:block;margin-bottom:3px;}
        .profile-field p{font-size:14px;font-weight:500;color:#333;}
        
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        .info-message{background-color:#d1ecf1;color:#0c5460;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;font-size:13px;}
        
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
            .form-row{flex-direction:column;}
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
            <a href="?page=home" class="<?php echo ($page == 'home') ? 'active' : ''; ?>">Home</a>
            <a href="?page=details" class="<?php echo ($page == 'details') ? 'active' : ''; ?>">Complete Details</a>
            <a href="apply_room.php">Select Room</a>
            <?php if($status == 'allocated'): ?>
                <a href="?page=room" class="<?php echo ($page == 'room') ? 'active' : ''; ?>">Room Details</a>
            <?php endif; ?>
            <a href="?page=profile" class="<?php echo ($page == 'profile') ? 'active' : ''; ?>">Profile</a>
            <a href="request_fee_commitment.php">Request Fee Commitment</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="welcome-section">
        <h1>Welcome, <?php echo $studentData['name']; ?>!</h1>
        <p>Reg No: <?php echo $_SESSION['username']; ?></p>
    </div>

    <div class="content-area">
        <?php if($success): ?>
            <div class="success-message">Details saved successfully! Your application is pending payment verification.</div>
        <?php endif; ?>
        <?php if($approved): ?>
            <div class="success-message">✅ Details saved and APPROVED! You can now select a room.</div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error-message">Please confirm that all information is correct.</div>
        <?php endif; ?>
        <?php if($profileUpdated): ?>
            <div class="success-message">Profile updated successfully!</div>
        <?php endif; ?>
        <?php if($blacklisted): ?>
            <div class="error-message">Your application has been rejected. You are on the Registrar's blacklist. Please contact the Registrar's office.</div>
        <?php endif; ?>

        <!-- HOME PAGE -->
        <?php if($page == 'home'): ?>
            <div class="content-card">
                <h2>Dashboard</h2>
                <p>Welcome to the Hostel Allocation System. Use the menu above to navigate.</p>
                <p style="margin-top:10px;">Your application status: 
                    <strong>
                        <?php 
                        if($status == 'pending') echo '⏳ Pending Payment Verification';
                        elseif($status == 'approved') echo '✅ Approved - You can select a room';
                        elseif($status == 'allocated') echo '🏠 Room Allocated';
                        elseif($status == 'rejected') echo '❌ Rejected';
                        else echo '📝 Not Submitted';
                        ?>
                    </strong>
                </p>
                <?php if($status == 'approved'): ?>
                    <div class="info-message" style="margin-top:15px;">
                        <strong>Next Step:</strong> Go to <strong>Select Room</strong> to choose your preferred room.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- COMPLETE DETAILS PAGE -->
        <?php if($page == 'details'): ?>
            <div class="content-card">
                <h2>Complete Your Registration Details</h2>
                <?php if(!$hasDetails): ?>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Program</label>
                                <input type="text" value="<?php echo $detectedProgram; ?>" readonly disabled>
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
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Home Address</label>
                            <textarea name="address" rows="2" required></textarea>
                        </div>
                        <h3 style="margin:15px 0 10px 0; color:#8B4513; font-size:16px;">Medical Information</h3>
                        <div class="form-group">
                            <label>Any medical conditions?</label>
                            <select name="medical_condition" id="medical_condition" onchange="toggleMedical()">
                                <option value="no">No</option>
                                <option value="yes">Yes</option>
                            </select>
                        </div>
                        <div id="medical_div" style="display:none;">
                            <div class="form-group">
                                <label>Please specify</label>
                                <textarea name="medical_condition_details" rows="2" placeholder="e.g., Asthma, Diabetes, Allergies"></textarea>
                            </div>
                        </div>
                        <h3 style="margin:15px 0 10px 0; color:#8B4513; font-size:16px;">Emergency Contact</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Guardian Name</label>
                                <input type="text" name="guardian_name" required>
                            </div>
                            <div class="form-group">
                                <label>Relationship</label>
                                <select name="guardian_relationship" required>
                                    <option value="">Select</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                    <option value="Guardian">Guardian</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Guardian Phone</label>
                            <input type="tel" name="guardian_phone" required>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; margin:15px 0;">
                            <input type="checkbox" name="agreement" required>
                            <label>I confirm that all information provided is correct.</label>
                        </div>
                        <button type="submit" name="save_details" class="submit-btn">Submit Application</button>
                    </form>
                <?php else: ?>
                    <p>Your details have been submitted. 
                    <?php if($status == 'pending'): ?>
                        Your application is pending payment verification. Please contact the Accounts office if you have paid.
                    <?php elseif($status == 'approved'): ?>
                        Your application is approved! Please go to <strong>Select Room</strong> to choose your room.
                    <?php elseif($status == 'allocated'): ?>
                        Your room has been allocated! Check your Room Details.
                    <?php else: ?>
                        Awaiting processing.
                    <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ROOM DETAILS PAGE -->
        <?php if($page == 'room'): ?>
            <div class="content-card">
                <h2>Room Details</h2>
                <?php
                $allocatedRoomID = $studentData['allocatedRoomID'];
                if($status == 'allocated' && $allocatedRoomID):
                    $roomSql = "SELECT * FROM rooms WHERE roomID = $allocatedRoomID";
                    $roomResult = $db->query($roomSql);
                    $room = $roomResult->fetch_assoc();
                ?>
                    <?php if($room): ?>
                        <div class="room-info-grid">
                            <div class="room-info-item"><label>Room Number</label><p><?php echo $room['roomNumber']; ?></p></div>
                            <div class="room-info-item"><label>Hostel Name</label><p><?php echo $room['hostelName']; ?></p></div>
                            <div class="room-info-item"><label>Gender</label><p><?php echo $room['gender']; ?></p></div>
                            <div class="room-info-item"><label>Allocation Date</label><p><?php echo $studentData['allocatedDate']; ?></p></div>
                        </div>
                        <a href="request_clearance.php" class="clearance-btn" onclick="return confirm('Request clearance? This will vacate your room.')">Request Clearance</a>
                    <?php else: ?>
                        <p>No room allocated yet.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No room has been allocated to you yet. 
                    <?php if($status == 'approved'): ?>
                        Please go to <strong>Select Room</strong> to choose your room.
                    <?php else: ?>
                        Complete your application and payment verification first.
                    <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PROFILE PAGE -->
        <?php if($page == 'profile'): ?>
            <div class="content-card">
                <h2>My Profile</h2>
                <div class="profile-grid">
                    <div class="profile-field"><label>Full Name</label><p><?php echo $studentData['name']; ?></p></div>
                    <div class="profile-field"><label>Registration Number</label><p><?php echo $_SESSION['username']; ?></p></div>
                    <div class="profile-field"><label>Program</label><p><?php echo $detectedProgram; ?></p></div>
                    <div class="profile-field"><label>Year of Study</label><p><?php $year = $studentData['year']; if($year==1) echo '1st Year'; elseif($year==2) echo '2nd Year'; elseif($year==3) echo '3rd Year'; elseif($year==4) echo '4th Year'; else echo 'Not set'; ?></p></div>
                    <div class="profile-field"><label>Gender</label><p><?php echo $studentData['gender'] ?: 'Not set'; ?></p></div>
                    <div class="profile-field"><label>Phone Number</label><p><?php echo $studentData['phone'] ?: 'Not set'; ?></p></div>
                    <div class="profile-field"><label>Email Address</label><p><?php echo $studentData['email'] ?: 'Not set'; ?></p></div>
                    <div class="profile-field"><label>Home Address</label><p><?php echo $studentData['address'] ?: 'Not set'; ?></p></div>
                </div>
                
                <h3 style="margin:15px 0 10px 0; color:#8B4513; font-size:16px;">Update Contact Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo $studentData['phone']; ?>"></div>
                        <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo $studentData['email']; ?>"></div>
                    </div>
                    <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                </form>
                
                <h3 style="margin:25px 0 10px 0; color:#8B4513; font-size:16px;">Change Password</h3>
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

    <script>
        function toggleMedical() {
            var select = document.getElementById('medical_condition');
            var div = document.getElementById('medical_div');
            div.style.display = select.value == 'yes' ? 'block' : 'none';
        }
    </script>
</body>
</html>