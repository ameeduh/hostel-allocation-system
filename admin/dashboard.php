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
$selectedHostel = isset($_GET['hostel']) ? $_GET['hostel'] : '';

// Get stats
$totalStudents = 0;
$totalRooms = 0;
$allocatedRooms = 0;

$sResult = $db->query("SELECT COUNT(*) as total FROM students");
if($sResult) { $totalStudents = $sResult->fetch_assoc()['total']; }

$rResult = $db->query("SELECT COUNT(*) as total FROM rooms");
if($rResult) { $totalRooms = $rResult->fetch_assoc()['total']; }

$aResult = $db->query("SELECT COUNT(*) as total FROM students WHERE applicationStatus = 'allocated'");
if($aResult) { $allocatedRooms = $aResult->fetch_assoc()['total']; }

// Handle add room
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $roomNumber = $_POST['roomNumber'];
    $hostelName = $_POST['hostelName'];
    $gender = $_POST['gender'];
    $capacity = (int)$_POST['capacity'];
    $availableBeds = $capacity;
    $db->query("INSERT INTO rooms (roomNumber, hostelName, gender, capacity, availableBeds, status) VALUES ('$roomNumber', '$hostelName', '$gender', $capacity, $availableBeds, 'available')");
    $added = true;
}

// Handle delete room
if(isset($_GET['delete'])) {
    $roomID = (int)$_GET['delete'];
    $db->query("DELETE FROM room_preferences WHERE preferredRoomID = $roomID");
    $db->query("DELETE FROM rooms WHERE roomID = $roomID");
    $deleted = true;
}

// Handle add user
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    
    $checkSql = "SELECT * FROM users WHERE username = '$username'";
    $checkResult = $db->query($checkSql);
    if($checkResult && $checkResult->num_rows > 0) {
        $userError = "Username already exists!";
    } else {
        $insertSql = "INSERT INTO users (username, password, name, email, phone, role) VALUES ('$username', '$hashedPassword', '$name', '$email', '$phone', '$role')";
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
        }
    }
}

// Get users for manage users
$userFilter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all';
$studentProgramFilter = isset($_GET['student_program']) ? $_GET['student_program'] : '';

$userWhere = "";
if($userFilter == 'students') {
    $userWhere = " WHERE role = 'student'";
} elseif($userFilter == 'staff') {
    $userWhere = " WHERE role IN ('accounts', 'registrar', 'warden', 'admin')";
} elseif($userFilter && $userFilter != 'all' && $userFilter != 'students' && $userFilter != 'staff') {
    $userWhere = " WHERE role = '$userFilter'";
}

$users = [];
$uResult = $db->query("SELECT userID, username, name, email, phone, role FROM users $userWhere ORDER BY role, name");
if($uResult) {
    while($row = $uResult->fetch_assoc()) {
        if($row['role'] == 'student') {
            $studentSql = "SELECT program FROM students WHERE userID = " . $row['userID'];
            $studentResult = $db->query($studentSql);
            if($studentResult) {
                $studentData = $studentResult->fetch_assoc();
                $row['program'] = $studentData['program'] ?? 'N/A';
            } else {
                $row['program'] = 'N/A';
            }
        } else {
            $row['program'] = '';
        }
        $users[] = $row;
    }
}

if($userFilter == 'students' && $studentProgramFilter) {
    $filteredUsers = [];
    foreach($users as $user) {
        if($user['program'] == $studentProgramFilter) {
            $filteredUsers[] = $user;
        }
    }
    $users = $filteredUsers;
}

$programs = ['ICT', 'Nursing', 'Business Administration'];

// Get students for home stats
$allStudents = [];
$sResult = $db->query("SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID");
if($sResult) {
    while($row = $sResult->fetch_assoc()) {
        $allStudents[] = $row;
    }
}

// Get rooms with occupancy info
$hostels = ['Eswanthini', 'Seychells', 'Namibia', 'Botswana', 'Lesotho'];
$rooms = [];

$roomWhere = "";
if($selectedHostel) {
    $roomWhere = " WHERE hostelName = '$selectedHostel'";
}
$roomResult = $db->query("SELECT *, (capacity - availableBeds) as occupiedBeds FROM rooms $roomWhere ORDER BY hostelName, roomNumber");
if($roomResult) {
    while($row = $roomResult->fetch_assoc()) {
        $rooms[] = $row;
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
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f5f5;}
        .top-bar{background:#8B4513;color:white;padding:15px 40px;display:flex;justify-content:space-between;}
        .nav-bar{background:white;padding:15px 40px;display:flex;justify-content:space-between;flex-wrap:wrap;border-bottom:2px solid #8B4513;}
        .university-name{font-size:20px;font-weight:700;color:#8B4513;}
        .nav-links{display:flex;gap:20px;flex-wrap:wrap;}
        .nav-links a{text-decoration:none;color:#333;padding:6px 12px;}
        .nav-links a:hover{color:#8B4513;}
        .nav-links a.active{color:#8B4513;border-bottom:2px solid #8B4513;}
        .welcome-section{background:white;margin:15px 40px;padding:15px 25px;border-left:5px solid #8B4513;}
        .content-area{margin:15px 40px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #ddd;}
        .content-card h2{color:#8B4513;border-bottom:2px solid #8B4513;padding-bottom:10px;margin-bottom:20px;}
        .stats-cards{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:20px;}
        .stat-card{flex:1;min-width:150px;background:#f8f9fa;padding:20px;text-align:center;border:1px solid #ddd;border-radius:8px;}
        .stat-number{font-size:32px;font-weight:bold;color:#8B4513;}
        .stat-label{font-size:13px;color:#666;margin-top:5px;}
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;}
        .sub-tab{padding:8px 20px;background:#f0f0f0;color:#333;text-decoration:none;border-radius:5px;}
        .sub-tab.active{background:#8B4513;color:white;}
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;align-items:center;flex-wrap:wrap;padding:10px 0;border-bottom:1px solid #eee;}
        .filter-bar label{font-weight:600;color:#8B4513;font-size:13px;}
        .filter-bar select{padding:8px 15px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:white;cursor:pointer;}
        .filter-bar button{background:#8B4513;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;}
        .export-buttons{display:flex;gap:10px;justify-content:flex-end;margin-bottom:20px;}
        .data-table{width:100%;border-collapse:collapse;margin-top:10px;}
        .data-table th{background:#8B4513;color:white;padding:10px;text-align:left;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;}
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;}
        .badge-available{background:#e8f5e9;color:#2e7d32;}
        .badge-full{background:#ffebee;color:#c62828;}
        .form-row{display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
        .form-group{flex:1;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group select{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;}
        .btn-add{background:#8B4513;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;}
        .btn-delete{background:#8B4513;color:white;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block;}
        .submit-btn{background:#8B4513;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;}
        .success-message{background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin-bottom:15px;text-align:center;}
        .error-message{background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;margin-bottom:15px;text-align:center;}
        .profile-field{padding:10px 0;border-bottom:1px solid #eee;}
        .profile-field label{font-size:12px;color:#888;display:block;}
        .profile-field p{font-size:15px;font-weight:500;color:#333;}
        .footer{background:#8B4513;color:white;padding:25px 40px;margin-top:20px;text-align:center;}
        @media(max-width:768px){.top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;}.nav-bar{flex-direction:column;}.stats-cards{flex-direction:column;}.export-buttons{justify-content:flex-start;}}
    </style>
</head>
<body>
<div class="top-bar">
    <div>Prayer | Love | Servantship</div>
    <div>Solideo</div>
</div>
<div class="nav-bar">
    <div class="university-name">Daeyang University</div>
    <div class="nav-links">
        <a href="?page=home" class="<?php echo ($page=='home')?'active':''; ?>">Home</a>
        <a href="?page=rooms" class="<?php echo ($page=='rooms')?'active':''; ?>">Manage Rooms</a>
        <a href="?page=users" class="<?php echo ($page=='users')?'active':''; ?>">Manage Users</a>
        <a href="?page=profile" class="<?php echo ($page=='profile')?'active':''; ?>">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $userData['name']; ?>!</h1>
</div>
<div class="content-area">

<?php if($page == 'home'): ?>
    <div class="content-card">
        <h2>Dashboard Overview</h2>
        <div class="stats-cards">
            <div class="stat-card"><div class="stat-number"><?php echo count($allStudents); ?></div><div class="stat-label">Total Students</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $totalRooms; ?></div><div class="stat-label">Total Rooms</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $allocatedRooms; ?></div><div class="stat-label">Allocated Rooms</div></div>
        </div>
    </div>

<?php elseif($page == 'rooms'): ?>
    <div class="content-card">
        <h2>Manage Rooms</h2>
        <?php if(isset($added)): ?>
            <div class="success-message">Room added successfully!</div>
        <?php endif; ?>
        <?php if(isset($deleted)): ?>
            <div class="success-message">Room deleted successfully!</div>
        <?php endif; ?>
        
        <div class="sub-tabs">
            <a href="?page=rooms&action=add" class="sub-tab <?php echo (!isset($_GET['action']) || $_GET['action']=='add')?'active':''; ?>">Add New Room</a>
            <a href="?page=rooms&action=list" class="sub-tab <?php echo (isset($_GET['action']) && $_GET['action']=='list')?'active':''; ?>">All Rooms</a>
        </div>
        
        <?php if(!isset($_GET['action']) || $_GET['action'] == 'add'): ?>
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
        
        <?php if(isset($_GET['action']) && $_GET['action'] == 'list'): ?>
            <div class="filter-bar">
                <label>Filter by Hostel:</label>
                <select id="hostelFilter" onchange="window.location.href='?page=rooms&action=list&hostel='+this.value">
                    <option value="">All Hostels</option>
                    <?php foreach($hostels as $hostel): ?>
                        <option value="<?php echo $hostel; ?>" <?php echo ($selectedHostel == $hostel) ? 'selected' : ''; ?>><?php echo $hostel; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if($selectedHostel): ?>
                    <button onclick="window.location.href='?page=rooms&action=list'">Clear Filter</button>
                <?php endif; ?>
            </div>
            
            <div class="export-buttons">
                <a href="export_rooms_pdf.php?hostel=<?php echo $selectedHostel; ?>" target="_blank" class="btn-add" style="background-color: #dc3545;">Export PDF</a>
                <a href="export_rooms_csv.php?hostel=<?php echo $selectedHostel; ?>" class="btn-add" style="background-color: #28a745;">Export Excel</a>
            </div>
            
            <?php if(count($rooms) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Hostel Name</th>
                            <th>Gender</th>
                            <th>Capacity</th>
                            <th>Occupied</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rooms as $room): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                            <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                            <td><?php echo htmlspecialchars($room['gender']); ?></td>
                            <td><?php echo $room['capacity']; ?></td>
                            <td><?php echo $room['occupiedBeds']; ?></td>
                            <td><?php echo $room['availableBeds']; ?></td>
                            <td><?php echo ($room['availableBeds'] > 0) ? '<span class="badge badge-available">Available</span>' : '<span class="badge badge-full">Full</span>'; ?></td>
                            <td><a href="?page=rooms&action=list&delete=<?php echo $room['roomID']; ?>&hostel=<?php echo $selectedHostel; ?>" class="btn-delete" onclick="return confirm('Delete this room?')">Delete</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No rooms found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<?php elseif($page == 'users'): ?>
    <div class="content-card">
        <h2>Manage Users</h2>
        <?php if(isset($userSuccess)): ?>
            <div class="success-message"><?php echo $userSuccess; ?></div>
        <?php endif; ?>
        <?php if(isset($userError)): ?>
            <div class="error-message"><?php echo $userError; ?></div>
        <?php endif; ?>
        
        <div class="sub-tabs">
            <a href="?page=users&action=list" class="sub-tab <?php echo (!isset($_GET['action']) || $_GET['action']=='list')?'active':''; ?>">User List</a>
            <a href="?page=users&action=add" class="sub-tab <?php echo (isset($_GET['action']) && $_GET['action']=='add')?'active':''; ?>">Add New User</a>
        </div>
        
        <?php if(isset($_GET['action']) && $_GET['action'] == 'add'): ?>
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
                        <input type="text" name="department" placeholder="Finance Department">
                    </div>
                    <div class="form-group" id="hostelField" style="display:none;">
                        <label>Hostel Assigned</label>
                        <input type="text" name="hostelAssigned" placeholder="Main Hostel">
                    </div>
                    <div class="form-group" id="officeField" style="display:none;">
                        <label>Office</label>
                        <input type="text" name="office" placeholder="Main Registrar Office">
                    </div>
                </div>
                <div class="form-group">
                    <p style="font-size:12px; color:#666;">Default password: <strong>password123</strong></p>
                </div>
                <button type="submit" name="add_user" class="btn-add">Add User</button>
            </form>
        <?php endif; ?>
        
        <?php if(!isset($_GET['action']) || $_GET['action'] == 'list'): ?>
            <div class="filter-bar">
                <label>Filter by User Type:</label>
                <select id="userFilter" onchange="applyUserFilter()">
                    <option value="all" <?php echo ($userFilter == 'all') ? 'selected' : ''; ?>>All Users</option>
                    <option value="students" <?php echo ($userFilter == 'students') ? 'selected' : ''; ?>>Students</option>
                    <option value="staff" <?php echo ($userFilter == 'staff') ? 'selected' : ''; ?>>Staff</option>
                    <option value="accounts" <?php echo ($userFilter == 'accounts') ? 'selected' : ''; ?>>Accountant</option>
                    <option value="registrar" <?php echo ($userFilter == 'registrar') ? 'selected' : ''; ?>>Registrar</option>
                    <option value="warden" <?php echo ($userFilter == 'warden') ? 'selected' : ''; ?>>Warden</option>
                    <option value="admin" <?php echo ($userFilter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <?php if($userFilter != 'all'): ?>
                    <button onclick="clearUserFilter()">Clear Filter</button>
                <?php endif; ?>
            </div>
            
            <?php if($userFilter == 'students'): ?>
                <div class="filter-bar" style="margin-top: -10px;">
                    <label>Filter by Program:</label>
                    <select id="programFilter" onchange="applyProgramFilter()">
                        <option value="">All Programs</option>
                        <?php foreach($programs as $program): ?>
                            <option value="<?php echo $program; ?>" <?php echo ($studentProgramFilter == $program) ? 'selected' : ''; ?>><?php echo $program; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($studentProgramFilter): ?>
                        <button onclick="clearProgramFilter()">Clear Program Filter</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if(count($users) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <?php if($userFilter == 'students'): ?>
                                <th>Program</th>
                            <?php endif; ?>
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
                            <?php if($userFilter == 'students'): ?>
                                <td><?php echo $user['program']; ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<?php elseif($page == 'profile'): ?>
    <?php
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $db->query("UPDATE users SET phone='$phone', email='$email' WHERE userID={$_SESSION['user_id']}");
        echo '<div class="success-message">Profile updated successfully!</div>';
    }
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $pResult = $db->query("SELECT password FROM users WHERE userID={$_SESSION['user_id']}");
        $userPass = $pResult->fetch_assoc();
        
        if(password_verify($current, $userPass['password'])) {
            if($new == $confirm && strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password='$hashed' WHERE userID={$_SESSION['user_id']}");
                echo '<div class="success-message">Password changed successfully!</div>';
            } else {
                echo '<div class="error-message">Passwords do not match or too short!</div>';
            }
        } else {
            echo '<div class="error-message">Current password is incorrect!</div>';
        }
    }
    ?>
    <div class="content-card">
        <h2>My Profile</h2>
        <div class="profile-field"><label>Full Name</label><p><?php echo $userData['name']; ?></p></div>
        <div class="profile-field"><label>Email</label><p><?php echo $userData['email'] ?: 'Not set'; ?></p></div>
        <div class="profile-field"><label>Phone</label><p><?php echo $userData['phone'] ?: 'Not set'; ?></p></div>
        <div class="profile-field"><label>Role</label><p>Administrator</p></div>
        
        <h3>Update Contact Information</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?php echo $userData['phone']; ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo $userData['email']; ?>"></div>
            </div>
            <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
        </form>
        
        <h3>Change Password</h3>
        <form method="POST">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
            <div class="form-group"><label>New Password (min 6 chars)</label><input type="password" name="new_password" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
            <button type="submit" name="change_password" class="submit-btn">Change Password</button>
        </form>
    </div>
<?php endif; ?>

</div>

<script>
    function applyUserFilter() {
        var filter = document.getElementById('userFilter').value;
        window.location.href = '?page=users&action=list&user_filter=' + filter;
    }
    
    function clearUserFilter() {
        window.location.href = '?page=users&action=list';
    }
    
    function applyProgramFilter() {
        var program = document.getElementById('programFilter').value;
        var userFilter = document.getElementById('userFilter').value;
        window.location.href = '?page=users&action=list&user_filter=' + userFilter + '&student_program=' + program;
    }
    
    function clearProgramFilter() {
        var userFilter = document.getElementById('userFilter').value;
        window.location.href = '?page=users&action=list&user_filter=' + userFilter;
    }
    
    function toggleRoleFields() {
        var role = document.getElementById('roleSelect').value;
        document.getElementById('departmentField').style.display = role == 'accounts' ? 'block' : 'none';
        document.getElementById('hostelField').style.display = role == 'warden' ? 'block' : 'none';
        document.getElementById('officeField').style.display = role == 'registrar' ? 'block' : 'none';
    }
</script>

<div class="footer">
    <div>&copy; <?php echo date('Y'); ?> Daeyang University. All rights reserved.</div>
</div>
</body>
</html>