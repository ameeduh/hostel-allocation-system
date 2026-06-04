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

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
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
    $sql = "DELETE FROM rooms WHERE roomID = $roomID";
    $db->query($sql);
    header("Location: dashboard.php?page=rooms&deleted=1");
    exit();
}

// Handle delete user
if($page == 'users' && isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    if($userID != $_SESSION['user_id']) {
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
$userDeleted = isset($_GET['deleted']);
$userError = isset($_GET['error']);

// Fetch data for reports
$allStudents = [];
$allRooms = [];
$pendingStudents = [];

$studentsResult = $db->query("SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID ORDER BY u.name");
if($studentsResult) {
    while($row = $studentsResult->fetch_assoc()) {
        $allStudents[] = $row;
    }
}

$roomsResult = $db->query("SELECT roomID, roomNumber, hostelName, gender, capacity, availableBeds, 
                                (capacity - availableBeds) as occupiedBeds, status 
                         FROM rooms ORDER BY hostelName, roomNumber");
if($roomsResult) {
    while($row = $roomsResult->fetch_assoc()) {
        $allRooms[] = $row;
    }
}

$pendingResult = $db->query("SELECT s.*, u.name FROM students s 
                            JOIN users u ON s.userID = u.userID 
                            WHERE s.applicationStatus = 'pending'");
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
        .nav-links a.active { color: #8B4513; border-bottom: 2px solid #FFD700; }
        .welcome-section { background-color: white; margin: 20px 40px; padding: 15px 25px; border-radius: 5px; border-left: 5px solid #FFD700; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .welcome-section h1 { font-size: 20px; color: #333; margin-bottom: 5px; }
        .content-area { margin: 20px 40px; min-height: 400px; }
        .content-card { background: white; border-radius: 5px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .content-card h2 { color: #8B4513; font-size: 20px; margin-bottom: 20px; border-left: 4px solid #FFD700; padding-left: 15px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { background-color: #8B4513; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
        .data-table tr:hover { background-color: #f9f9f9; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #8B4513; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        
        .btn-add { background-color: #2e7d32; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-add:hover { background-color: #1b5e20; }
        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-delete:hover { background-color: #c82333; }
        .submit-btn { background-color: #8B4513; color: white; border: none; padding: 8px 25px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .submit-btn:hover { background-color: #6d3710; }
        
        .report-tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #8B4513; }
        .report-tab { padding: 10px 20px; background: none; border: none; cursor: pointer; font-size: 14px; color: #666; transition: all 0.3s; }
        .report-tab:hover { color: #8B4513; }
        .report-tab.active { color: #8B4513; border-bottom: 2px solid #FFD700; font-weight: 600; }
        
        .profile-field { padding: 10px 0; border-bottom: 1px solid #eee; }
        .profile-field label { font-size: 12px; color: #888; display: block; margin-bottom: 3px; }
        .profile-field p { font-size: 15px; font-weight: 500; color: #333; }
        
        .success-message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        .error-message { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d1ecf1; color: #0c5460; }
        .badge-allocated { background: #d4edda; color: #155724; }
        .badge-available { background: #d4edda; color: #155724; }
        .badge-full { background: #f8d7da; color: #721c24; }
        
        .stats-cards { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 150px; background: #f8f9fa; border-radius: 5px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; }
        .stat-number { font-size: 28px; font-weight: bold; color: #8B4513; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        
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
                <?php if($added): ?>
                    <div class="success-message">Room added successfully!</div>
                <?php endif; ?>
                <?php if($deleted): ?>
                    <div class="success-message">Room deleted successfully!</div>
                <?php endif; ?>
                
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
                
                <h3 style="margin:30px 0 15px 0; color:#8B4513; font-size:16px;">All Rooms</h3>
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
                                        <td>
                                            <?php if(!empty($room['roomID'])): ?>
                                                <a href="?page=rooms&delete=<?php echo $room['roomID']; ?>" class="btn-delete" onclick="return confirm('Delete this room?')">Delete</a>
                                            <?php else: ?>
                                                <span class="text-muted">No action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No rooms found.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- MANAGE USERS PAGE -->
        <?php if($page == 'users'): ?>
            <?php
            $usersResult = $db->query("SELECT userID, username, name, email, phone, role FROM users ORDER BY role, name");
            $users = [];
            if($usersResult) {
                while($row = $usersResult->fetch_assoc()) {
                    $users[] = $row;
                }
            }
            ?>
            <div class="content-card">
                <h2>Manage Users</h2>
                <?php if($userDeleted): ?>
                    <div class="success-message">User deleted successfully!</div>
                <?php endif; ?>
                <?php if($userError): ?>
                    <div class="error-message">You cannot delete your own account.</div>
                <?php endif; ?>
                
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
                                    <th>Action</th>
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
                                                <a href="?page=users&delete=<?php echo $user['userID']; ?>" class="btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
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
                    <a href="preview_report.php?type=<?php echo $selectedReport; ?>" target="_blank" style="background-color: #17a2b8; color: white; padding: 6px 15px; text-decoration: none; border-radius: 5px; font-size: 13px;">Preview Report</a>
                </div>
                
                <?php if($selectedReport == 'students'): ?>
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
                
                <?php if($selectedReport == 'rooms'): ?>
                    <h3 style="margin-bottom:15px; color:#8B4513;">Room Occupancy</h3>
                    <div style="overflow-x: auto;">
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allRooms as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                                        <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                                        <td><?php echo $room['gender']; ?></td>
                                        <td><?php echo $room['capacity']; ?></td>
                                        <td><?php echo $room['occupiedBeds']; ?></td>
                                        <td><?php echo $room['availableBeds']; ?></td>
                                        <td><?php echo ucfirst($room['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if($selectedReport == 'pending'): ?>
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
                                        <td><?php echo $student['gender']; ?></td
                                        <td><span class="badge badge-pending">Pending Accountant</span></td
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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