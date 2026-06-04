<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$sql = "SELECT name, email, phone FROM users WHERE userID = {$_SESSION['user_id']}";
$result = $db->query($sql);
$userData = $result->fetch_assoc();

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$selectedReport = isset($_GET['report']) ? $_GET['report'] : 'students';
$userAction = isset($_GET['action']) ? $_GET['action'] : '';
$roomFilter = isset($_GET['room_filter']) ? $_GET['room_filter'] : 'all';
$userListFilter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all';
$studentGenderFilter = isset($_GET['gender_filter']) ? $_GET['gender_filter'] : 'all';

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

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

// Handle add user
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $defaultPassword = 'password123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    $checkSql = "SELECT * FROM users WHERE username = '$username'";
    $checkResult = $db->query($checkSql);
    if($checkResult && $checkResult->num_rows > 0) {
        $userError = "Username already exists!";
    } else {
        $insertSql = "INSERT INTO users (username, password, name, email, phone, role) 
                      VALUES ('$username', '$hashedPassword', '$name', '$email', '$phone', '$role')";
        if($db->query($insertSql)) {
            $userID = $db->getInsertId();
            
            if($role == 'accounts') {
                $department = $_POST['department'];
                $db->query("INSERT INTO accountants (userID, department) VALUES ($userID, '$department')");
            } elseif($role == 'warden') {
                $hostelAssigned = $_POST['hostelAssigned'];
                $db->query("INSERT INTO wardens (userID, hostelAssigned) VALUES ($userID, '$hostelAssigned')");
            } elseif($role == 'registrar') {
                $office = $_POST['office'];
                $db->query("INSERT INTO registrars (userID, office) VALUES ($userID, '$office')");
            }
            $userSuccess = "User added successfully! Default password: password123";
        } else {
            $userError = "Failed to add user!";
        }
    }
}

// Handle edit user
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $editID = (int)$_POST['edit_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    $updateSql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone' WHERE userID = $editID";
    if($db->query($updateSql)) {
        $editSuccess = "User updated successfully!";
    } else {
        $editError = "Failed to update user!";
    }
}

// Handle reset password
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $resetID = (int)$_POST['reset_id'];
    $newPassword = $_POST['new_password'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE userID = $resetID";
    if($db->query($updateSql)) {
        $resetSuccess = "Password reset successfully! New password: $newPassword";
    } else {
        $resetError = "Failed to reset password!";
    }
}

// Handle add room
if($page == 'rooms' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $roomNumber = $_POST['roomNumber'];
    $hostelName = $_POST['hostelName'];
    $gender = $_POST['gender'];
    $capacity = (int)$_POST['capacity'];
    $availableBeds = $capacity;
    
    $sql = "INSERT INTO rooms (roomNumber, hostelName, gender, capacity, availableBeds, status) 
            VALUES ('$roomNumber', '$hostelName', '$gender', $capacity, $availableBeds, 'available')";
    $db->query($sql);
    header("Location: dashboard.php?page=rooms&added=1");
    exit();
}

// Handle delete room
if($page == 'rooms' && isset($_GET['delete'])) {
    $roomID = (int)$_GET['delete'];
    
    $db->query("DELETE FROM room_preferences WHERE preferredRoomID = $roomID");
    
    $checkSql = "SELECT COUNT(*) as count FROM students WHERE allocatedRoomID = $roomID AND allocationStatus = 'active'";
    $checkResult = $db->query($checkSql);
    $allocated = $checkResult->fetch_assoc()['count'];
    
    if($allocated > 0) {
        header("Location: dashboard.php?page=rooms&error=allocated");
        exit();
    }
    
    $sql = "DELETE FROM rooms WHERE roomID = $roomID";
    $db->query($sql);
    header("Location: dashboard.php?page=rooms&deleted=1");
    exit();
}

// Handle delete user
if($page == 'users' && isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    if($userID != $_SESSION['user_id']) {
        $roleSql = "SELECT role FROM users WHERE userID = $userID";
        $roleResult = $db->query($roleSql);
        if($roleResult) {
            $role = $roleResult->fetch_assoc()['role'];
            if($role == 'accounts') $db->query("DELETE FROM accountants WHERE userID = $userID");
            elseif($role == 'warden') $db->query("DELETE FROM wardens WHERE userID = $userID");
            elseif($role == 'registrar') $db->query("DELETE FROM registrars WHERE userID = $userID");
            elseif($role == 'student') $db->query("DELETE FROM students WHERE userID = $userID");
            elseif($role == 'admin') $db->query("DELETE FROM admins WHERE userID = $userID");
        }
        $sql = "DELETE FROM users WHERE userID = $userID";
        $db->query($sql);
        header("Location: dashboard.php?page=users&deleted=1");
        exit();
    } else {
        header("Location: dashboard.php?page=users&error=1");
        exit();
    }
}

$profileUpdated = isset($_GET['updated']);
$added = isset($_GET['added']);
$deleted = isset($_GET['deleted']);
$roomError = isset($_GET['error']) && $_GET['error'] == 'allocated';
$userDeleted = isset($_GET['deleted']);
$userError = isset($_GET['error']);

// Build filter conditions
$roomWhere = "";
if($roomFilter == 'male') {
    $roomWhere = " WHERE gender = 'Male'";
} elseif($roomFilter == 'female') {
    $roomWhere = " WHERE gender = 'Female'";
}

$userWhere = "";
if($userListFilter != 'all') {
    $userWhere = " WHERE role = '$userListFilter'";
}

$studentGenderWhere = "";
if($studentGenderFilter == 'male') {
    $studentGenderWhere = " AND s.gender = 'Male'";
} elseif($studentGenderFilter == 'female') {
    $studentGenderWhere = " AND s.gender = 'Female'";
}

// Fetch all users
$usersResult = $db->query("SELECT userID, username, name, email, phone, role FROM users $userWhere ORDER BY role, name");
$users = [];
if($usersResult) {
    while($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch data for reports
$allStudents = [];
$allRooms = [];
$pendingStudents = [];

$studentsResult = $db->query("SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID WHERE 1=1 $studentGenderWhere ORDER BY u.name");
if($studentsResult) {
    while($row = $studentsResult->fetch_assoc()) {
        $allStudents[] = $row;
    }
}

$roomsResult = $db->query("SELECT * FROM rooms $roomWhere ORDER BY hostelName, roomNumber");
if($roomsResult) {
    while($row = $roomsResult->fetch_assoc()) {
        $allRooms[] = $row;
    }
}

$pendingResult = $db->query("SELECT s.*, u.name FROM students s 
                            JOIN users u ON s.userID = u.userID 
                            WHERE s.applicationStatus = 'pending' $studentGenderWhere");
if($pendingResult) {
    while($row = $pendingResult->fetch_assoc()) {
        $pendingStudents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Daeyang University</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; }
        .top-bar { background-color: #8B4513; color: white; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; }
        .prayer-love { font-size: 14px; }
        .solideo { font-size: 14px; font-weight: 500; }
        .nav-bar { background-color: white; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; border-bottom: 2px solid #8B4513; }
        .university-name { font-size: 20px; font-weight: 700; color: #8B4513; }
        .nav-links { display: flex; gap: 20px; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: #333; font-size: 14px; font-weight: 500; padding: 6px 12px; transition: all 0.3s; }
        .nav-links a:hover { color: #8B4513; }
        .nav-links a.active { color: #8B4513; border-bottom: 2px solid #8B4513; }
        .welcome-section { background-color: white; margin: 20px 40px; padding: 15px 25px; border-radius: 5px; border-left: 5px solid #8B4513; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .welcome-section h1 { font-size: 20px; color: #333; margin-bottom: 5px; }
        .content-area { margin: 20px 40px; min-height: 400px; }
        .content-card { background: white; border-radius: 5px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e0e0e0; }
        .content-card h2 { color: #8B4513; font-size: 20px; margin-bottom: 20px; border-left: 4px solid #8B4513; padding-left: 15px; }
        
        .sub-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; flex-wrap: wrap; }
        .sub-tab { padding: 8px 20px; text-decoration: none; border-radius: 5px; background: #f0f0f0; color: #333; font-size: 14px; }
        .sub-tab.active { background: #8B4513; color: white; }
        .sub-tab:hover { background: #8B4513; color: white; }
        
        .filter-dropdown { margin-bottom: 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid #eee; }
        .filter-dropdown label { font-weight: 600; color: #8B4513; font-size: 13px; }
        .filter-dropdown select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white; color: #333; cursor: pointer; }
        .filter-dropdown select:focus { border-color: #8B4513; outline: none; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { background-color: #8B4513; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
        .data-table tr:hover { background-color: #f9f9f9; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #8B4513; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        
        .btn-add { background-color: #8B4513; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-add:hover { background-color: #6d3710; }
        .btn-delete { background-color: #8B4513; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-delete:hover { background-color: #6d3710; }
        .btn-edit { background-color: #8B4513; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-edit:hover { background-color: #6d3710; }
        .btn-reset { background-color: #8B4513; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-reset:hover { background-color: #6d3710; }
        .submit-btn { background-color: #8B4513; color: white; border: none; padding: 8px 25px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .submit-btn:hover { background-color: #6d3710; }
        
        .report-tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #8B4513; }
        .report-tab { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 14px; color: #666; transition: all 0.3s; }
        .report-tab:hover { color: #8B4513; }
        .report-tab.active { color: #8B4513; border-bottom: 2px solid #8B4513; font-weight: 600; }
        
        .profile-field { padding: 10px 0; border-bottom: 1px solid #eee; }
        .profile-field label { font-size: 12px; color: #888; display: block; margin-bottom: 3px; }
        .profile-field p { font-size: 15px; font-weight: 500; color: #333; }
        
        .success-message { background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        .error-message { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-pending { background: #fff3e0; color: #e65100; }
        .badge-approved { background: #e8f5e9; color: #2e7d32; }
        .badge-allocated { background: #e8f5e9; color: #2e7d32; }
        .badge-available { background: #e8f5e9; color: #2e7d32; }
        .badge-full { background: #ffebee; color: #c62828; }
        
        .stats-cards { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 150px; background: #f8f9fa; border-radius: 5px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; }
        .stat-number { font-size: 28px; font-weight: bold; color: #8B4513; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 10% auto; padding: 25px; border-radius: 8px; width: 500px; max-width: 90%; }
        .modal-content h3 { color: #8B4513; margin-bottom: 20px; }
        .modal-content .close { float: right; font-size: 24px; cursor: pointer; }
        .modal-content .close:hover { color: #8B4513; }
        
        .footer { background-color: #8B4513; color: white; padding: 25px 40px; margin-top: 30px; }
        .footer-content { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .footer-section h3 { font-size: 14px; margin-bottom: 10px; color: #FFD700; }
        .footer-section p, .footer-section a { font-size: 11px; color: #f0f0f0; text-decoration: none; line-height: 1.6; }
        .footer-section a:hover { color: #FFD700; }
        .copyright { text-align: center; padding-top: 15px; margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 10px; color: #e0e0e0; }
        
        @media (max-width: 768px) {
            .top-bar, .nav-bar, .welcome-section, .content-area { padding-left: 20px; padding-right: 20px; margin-left: 20px; margin-right: 20px; }
            .nav-bar { flex-direction: column; gap: 10px; }
            .stats-cards { flex-direction: column; }
            .data-table { overflow-x: auto; display: block; }
            .sub-tabs { flex-direction: column; }
            .filter-dropdown { flex-direction: column; align-items: flex-start; }
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
            <a href="?page=rooms" class="<?php echo ($page == 'rooms') ? 'active' : ''; ?>">Manage Rooms</a>
            <a href="?page=users" class="<?php echo ($page == 'users') ? 'active' : ''; ?>">Manage Users</a>
            <a href="?page=reports" class="<?php echo ($page == 'reports') ? 'active' : ''; ?>">Reports</a>
            <a href="?page=profile" class="<?php echo ($page == 'profile') ? 'active' : ''; ?>">Profile</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="welcome-section">
        <h1>Welcome, <?php echo $userData['name']; ?>!</h1>
    </div>

    <div class="content-area">
        <?php if($profileUpdated): ?>
            <div class="success-message">Profile updated successfully!</div>
        <?php endif; ?>
        <?php if(isset($userSuccess)): ?>
            <div class="success-message"><?php echo $userSuccess; ?></div>
        <?php endif; ?>
        <?php if(isset($userError)): ?>
            <div class="error-message"><?php echo $userError; ?></div>
        <?php endif; ?>
        <?php if(isset($editSuccess)): ?>
            <div class="success-message"><?php echo $editSuccess; ?></div>
        <?php endif; ?>
        <?php if(isset($editError)): ?>
            <div class="error-message"><?php echo $editError; ?></div>
        <?php endif; ?>
        <?php if(isset($resetSuccess)): ?>
            <div class="success-message"><?php echo $resetSuccess; ?></div>
        <?php endif; ?>
        <?php if(isset($resetError)): ?>
            <div class="error-message"><?php echo $resetError; ?></div>
        <?php endif; ?>

        <!-- HOME PAGE -->
        <?php if($page == 'home'): ?>
            <div class="content-card">
                <h2>Dashboard Overview</h2>
                <div class="stats-cards">
                    <div class="stat-card"><div class="stat-number"><?php echo count($allStudents); ?></div><div class="stat-label">Total Students</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo count($allRooms); ?></div><div class="stat-label">Total Rooms</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo count($pendingStudents); ?></div><div class="stat-label">Pending Approvals</div></div>
                    <?php 
                    $allocated = 0;
                    foreach($allStudents as $s) { 
                        if($s['applicationStatus'] == 'allocated') $allocated++; 
                    }
                    ?>
                    <div class="stat-card"><div class="stat-number"><?php echo $allocated; ?></div><div class="stat-label">Allocated Rooms</div></div>
                </div>
                <p style="color: #666;">Use the navigation menu to manage rooms, users, and view reports.</p>
            </div>
        <?php endif; ?>

        <!-- MANAGE ROOMS PAGE -->
        <?php if($page == 'rooms'): ?>
            <div class="content-card">
                <h2>Manage Rooms</h2>
                
                <div class="sub-tabs">
                    <a href="?page=rooms&action=add" class="sub-tab <?php echo (!isset($_GET['action']) || $_GET['action'] == 'add') ? 'active' : ''; ?>">Add New Room</a>
                    <a href="?page=rooms&action=list" class="sub-tab <?php echo (isset($_GET['action']) && $_GET['action'] == 'list') ? 'active' : ''; ?>">All Rooms</a>
                </div>
                
                <?php if($added): ?>
                    <div class="success-message">Room added successfully!</div>
                <?php endif; ?>
                <?php if($deleted): ?>
                    <div class="success-message">Room deleted successfully!</div>
                <?php endif; ?>
                <?php if($roomError): ?>
                    <div class="error-message">Cannot delete room because it has active student allocations!</div>
                <?php endif; ?>
                
                <!-- ADD NEW ROOM FORM -->
                <?php if(!isset($_GET['action']) || $_GET['action'] == 'add'): ?>
                    <h3 style="margin:0 0 15px 0; color:#8B4513; font-size:16px;">Add New Room</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group"><label>Room Number</label><input type="text" name="roomNumber" required></div>
                            <div class="form-group">
                                <label>Hostel Name</label>
                                <select name="hostelName" required>
                                    <option value="Eswanthini">Eswanthini</option>
                                    <option value="Seychells">Seychells</option>
                                    <option value="Namibia">Namibia</option>
                                    <option value="Botswana">Botswana</option>
                                    <option value="Lesotho">Lesotho</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Capacity</label><input type="number" name="capacity" required></div>
                        </div>
                        <button type="submit" name="add_room" class="btn-add">Add Room</button>
                    </form>
                <?php endif; ?>
                
                <!-- ALL ROOMS LIST WITH GENDER FILTER -->
                <?php if(isset($_GET['action']) && $_GET['action'] == 'list'): ?>
                    <div class="filter-dropdown">
                        <label>Filter by Gender:</label>
                        <select id="roomGenderFilter" onchange="window.location.href='?page=rooms&action=list&room_filter='+this.value">
                            <option value="all" <?php echo ($roomFilter == 'all') ? 'selected' : ''; ?>>All Rooms</option>
                            <option value="male" <?php echo ($roomFilter == 'male') ? 'selected' : ''; ?>>Male Rooms</option>
                            <option value="female" <?php echo ($roomFilter == 'female') ? 'selected' : ''; ?>>Female Rooms</option>
                        </select>
                    </div>
                    
                    <?php if(count($allRooms) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Hostel Name</th>
                                        <th>Gender</th>
                                        <th>Capacity</th>
                                        <th>Available Beds</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($allRooms as $room): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                                            <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                                            <td><?php echo htmlspecialchars($room['gender']); ?></td>
                                            <td><?php echo $room['capacity']; ?></td>
                                            <td><?php echo $room['availableBeds']; ?></td>
                                            <td><?php echo ($room['availableBeds'] > 0) ? '<span class="badge badge-available">Available</span>' : '<span class="badge badge-full">Full</span>'; ?></td>
                                            <td><a href="?page=rooms&action=list&delete=<?php echo $room['roomID']; ?>&room_filter=<?php echo $roomFilter; ?>" class="btn-delete" onclick="return confirm(\'Delete this room?\')">Delete</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No rooms found.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- MANAGE USERS PAGE -->
        <?php if($page == 'users'): ?>
            <div class="content-card">
                <h2>Manage Users</h2>
                
                <div class="sub-tabs">
                    <a href="?page=users&action=list" class="sub-tab <?php echo ($userAction == 'list' || $userAction == '') ? 'active' : ''; ?>">User List</a>
                    <a href="?page=users&action=add" class="sub-tab <?php echo ($userAction == 'add') ? 'active' : ''; ?>">Add New User</a>
                </div>
                
                <?php if($userDeleted): ?>
                    <div class="success-message">User deleted successfully!</div>
                <?php endif; ?>
                <?php if($userError): ?>
                    <div class="error-message">You cannot delete your own account.</div>
                <?php endif; ?>
                
                <!-- ADD NEW USER FORM -->
                <?php if($userAction == 'add'): ?>
                    <h3 style="margin:0 0 15px 0; color:#8B4513; font-size:16px;">Add New User</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                            <div class="form-group"><label>Full Name</label><input type="text" name="name" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="phone" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" id="roleSelect" onchange="toggleRoleFields()" required>
                                    <option value="">Select Role</option>
                                    <option value="accounts">Accountant</option>
                                    <option value="warden">Warden</option>
                                    <option value="registrar">Registrar</option>
                                </select>
                            </div>
                            <div class="form-group" id="departmentField" style="display:none;">
                                <label>Department</label>
                                <input type="text" name="department" placeholder="e.g., Finance Department">
                            </div>
                            <div class="form-group" id="hostelField" style="display:none;">
                                <label>Hostel Assigned</label>
                                <input type="text" name="hostelAssigned" placeholder="e.g., Main Hostel">
                            </div>
                            <div class="form-group" id="officeField" style="display:none;">
                                <label>Office</label>
                                <input type="text" name="office" placeholder="e.g., Main Registrar Office">
                            </div>
                        </div>
                        <div class="form-group">
                            <p style="font-size:12px; color:#666;">Default password for all new users: <strong>password123</strong></p>
                        </div>
                        <button type="submit" name="add_user" class="btn-add">Add User</button>
                    </form>
                <?php endif; ?>
                
                <!-- USER LIST WITH FILTER -->
                <?php if($userAction == 'list' || $userAction == ''): ?>
                    <div class="filter-dropdown">
                        <label>Filter by User Type:</label>
                        <select id="userListFilter" onchange="window.location.href='?page=users&action=list&user_filter='+this.value">
                            <option value="all" <?php echo ($userListFilter == 'all') ? 'selected' : ''; ?>>All Users</option>
                            <option value="student" <?php echo ($userListFilter == 'student') ? 'selected' : ''; ?>>Students</option>
                            <option value="accounts" <?php echo ($userListFilter == 'accounts') ? 'selected' : ''; ?>>Accountant</option>
                            <option value="registrar" <?php echo ($userListFilter == 'registrar') ? 'selected' : ''; ?>>Registrar</option>
                            <option value="warden" <?php echo ($userListFilter == 'warden') ? 'selected' : ''; ?>>Warden</option>
                            <option value="admin" <?php echo ($userListFilter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <?php if(count($users) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo $user['email'] ?: 'N/A'; ?></td>
                                            <td><?php echo $user['phone'] ?: 'N/A'; ?></td>
                                            <td><?php echo ucfirst($user['role']); ?></td>
                                            <td>
                                                <?php if($user['userID'] != $_SESSION['user_id']): ?>
                                                    <button onclick="openEditModal(<?php echo $user['userID']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['phone']); ?>')" class="btn-edit">Edit</button>
                                                    <button onclick="openResetModal(<?php echo $user['userID']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn-reset">Reset Pwd</button>
                                                    <a href="?page=users&action=list&delete=<?php echo $user['userID']; ?>&user_filter=<?php echo $userListFilter; ?>" class="btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                                                <?php else: ?>
                                                    <span style="color:#999;">Current</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No users found.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- REPORTS PAGE -->
        <?php if($page == 'reports'): ?>
            <div class="content-card">
                <h2>Reports</h2>
                
                <div class="report-tabs">
                    <button class="report-tab <?php echo ($selectedReport == 'students') ? 'active' : ''; ?>" onclick="window.location.href='?page=reports&report=students'">All Students</button>
                    <button class="report-tab <?php echo ($selectedReport == 'rooms') ? 'active' : ''; ?>" onclick="window.location.href='?page=reports&report=rooms'">Room Occupancy</button>
                    <button class="report-tab <?php echo ($selectedReport == 'pending') ? 'active' : ''; ?>" onclick="window.location.href='?page=reports&report=pending'">Pending Approvals</button>
                </div>
                
                <div style="margin-bottom: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="preview_report.php?type=<?php echo $selectedReport; ?>" target="_blank" style="background-color: #8B4513; color: white; padding: 6px 15px; text-decoration: none; border-radius: 4px; font-size: 13px;">Preview Report</a>
                </div>
                
                <!-- ALL STUDENTS REPORT WITH GENDER FILTER -->
                <?php if($selectedReport == 'students'): ?>
                    <div class="filter-dropdown">
                        <label>Filter by Gender:</label>
                        <select id="studentGenderFilter" onchange="window.location.href='?page=reports&report=students&gender_filter='+this.value">
                            <option value="all" <?php echo ($studentGenderFilter == 'all') ? 'selected' : ''; ?>>All Students</option>
                            <option value="male" <?php echo ($studentGenderFilter == 'male') ? 'selected' : ''; ?>>Male Students</option>
                            <option value="female" <?php echo ($studentGenderFilter == 'female') ? 'selected' : ''; ?>>Female Students</option>
                        </select>
                    </div>
                    
                    <h3 style="margin-bottom:15px; color:#8B4513;">All Students</h3>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Reg Number</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allStudents as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                        <td><?php echo $student['program'] ?: 'N/A'; ?></td>
                                        <td><?php 
                                            $y = $student['year'];
                                            if($y == 1) echo '1st Year';
                                            elseif($y == 2) echo '2nd Year';
                                            elseif($y == 3) echo '3rd Year';
                                            elseif($y == 4) echo '4th Year';
                                            else echo 'N/A';
                                        ?></td>
                                        <td><?php echo $student['gender'] ?: 'N/A'; ?></td>
                                        <td><?php echo ucfirst($student['applicationStatus']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- ROOM OCCUPANCY REPORT WITH GENDER FILTER -->
                <?php if($selectedReport == 'rooms'): ?>
                    <div class="filter-dropdown">
                        <label>Filter by Gender:</label>
                        <select id="roomOccupancyFilter" onchange="window.location.href='?page=reports&report=rooms&gender_filter='+this.value">
                            <option value="all" <?php echo ($studentGenderFilter == 'all') ? 'selected' : ''; ?>>All Rooms</option>
                            <option value="male" <?php echo ($studentGenderFilter == 'male') ? 'selected' : ''; ?>>Male Rooms</option>
                            <option value="female" <?php echo ($studentGenderFilter == 'female') ? 'selected' : ''; ?>>Female Rooms</option>
                        </select>
                    </div>
                    
                    <h3 style="margin-bottom:15px; color:#8B4513;">Room Occupancy</h3>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Hostel Name</th>
                                    <th>Gender</th>
                                    <th>Capacity</th>
                                    <th>Available Beds</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allRooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                                        <td><?php echo htmlspecialchars($room['gender']); ?></td>
                                        <td><?php echo $room['capacity']; ?></td>
                                        <td><?php echo $room['availableBeds']; ?></td>
                                        <td><?php echo ($room['availableBeds'] > 0) ? '<span class="badge badge-available">Available</span>' : '<span class="badge badge-full">Full</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- PENDING APPROVALS REPORT WITH GENDER FILTER -->
                <?php if($selectedReport == 'pending'): ?>
                    <div class="filter-dropdown">
                        <label>Filter by Gender:</label>
                        <select id="pendingGenderFilter" onchange="window.location.href='?page=reports&report=pending&gender_filter='+this.value">
                            <option value="all" <?php echo ($studentGenderFilter == 'all') ? 'selected' : ''; ?>>All</option>
                            <option value="male" <?php echo ($studentGenderFilter == 'male') ? 'selected' : ''; ?>>Male Students</option>
                            <option value="female" <?php echo ($studentGenderFilter == 'female') ? 'selected' : ''; ?>>Female Students</option>
                        </select>
                    </div>
                    
                    <h3 style="margin-bottom:15px; color:#8B4513;">Pending Approvals</h3>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Reg Number</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pendingStudents as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year']; ?> Year</td
                                        <td><?php echo $student['gender']; ?></td>
                                        <td><span class="badge badge-pending">Pending Accountant</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </tr>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PROFILE PAGE -->
        <?php if($page == 'profile'): ?>
            <div class="content-card">
                <h2>My Profile</h2>
                <div class="profile-field"><label>Full Name</label><p><?php echo htmlspecialchars($userData['name']); ?></p></div>
                <div class="profile-field"><label>Email Address</label><p><?php echo $userData['email'] ?: 'Not set'; ?></p></div>
                <div class="profile-field"><label>Phone Number</label><p><?php echo $userData['phone'] ?: 'Not set'; ?></p></div>
                <div class="profile-field"><label>Role</label><p>Administrator</p></div>
                
                <h3 style="margin:25px 0 15px 0; color:#8B4513;">Update Contact Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" value="<?php echo $userData['phone']; ?>"></div>
                        <div class="form-group"><label>Email Address</label><input type="email" name="email" value="<?php echo $userData['email']; ?>"></div>
                    </div>
                    <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                </form>
                
                <h3 style="margin:25px 0 15px 0; color:#8B4513;">Change Password</h3>
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

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit User</h3>
            <form method="POST">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_phone" required>
                </div>
                <button type="submit" name="edit_user" class="btn-add">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeResetModal()">&times;</span>
            <h3>Reset Password</h3>
            <form method="POST">
                <input type="hidden" name="reset_id" id="reset_id">
                <div class="form-group">
                    <label>User</label>
                    <input type="text" id="reset_username" readonly style="background:#f5f5f5;">
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="text" name="new_password" id="new_password" required value="password123">
                </div>
                <p style="font-size:12px; color:#666;">Default password is: <strong>password123</strong></p>
                <button type="submit" name="reset_password" class="btn-add">Reset Password</button>
            </form>
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

    <script>
        function toggleRoleFields() {
            var role = document.getElementById('roleSelect').value;
            document.getElementById('departmentField').style.display = role == 'accounts' ? 'block' : 'none';
            document.getElementById('hostelField').style.display = role == 'warden' ? 'block' : 'none';
            document.getElementById('officeField').style.display = role == 'registrar' ? 'block' : 'none';
        }
        
        function openEditModal(id, name, email, phone) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function openResetModal(id, username) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_username').value = username;
            document.getElementById('resetModal').style.display = 'block';
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('resetModal')) {
                closeResetModal();
            }
        }
    </script>
</body>
</html>