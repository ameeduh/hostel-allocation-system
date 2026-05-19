<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$sql = "SELECT name, email, phone FROM users WHERE userID = {$_SESSION['user_id']}";
$result = $db->query($sql);
$userData = $result->fetch_assoc();

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

$profileUpdated = isset($_GET['updated']);

// Fetch data for stats cards
$totalStudents = 0;
$totalAllocated = 0;
$totalAvailableRooms = 0;
$totalPendingClearance = 0;

$studentsResult = $db->query("SELECT COUNT(*) as total FROM students");
if($studentsResult) {
    $row = $studentsResult->fetch_assoc();
    $totalStudents = $row['total'];
}

$allocatedResult = $db->query("SELECT COUNT(*) as total FROM students WHERE applicationStatus = 'allocated' AND allocationStatus = 'active'");
if($allocatedResult) {
    $row = $allocatedResult->fetch_assoc();
    $totalAllocated = $row['total'];
}

$roomsResult = $db->query("SELECT COUNT(*) as total FROM rooms WHERE availableBeds > 0 AND status = 'available'");
if($roomsResult) {
    $row = $roomsResult->fetch_assoc();
    $totalAvailableRooms = $row['total'];
}

$clearanceResult = $db->query("SELECT COUNT(*) as total FROM clearance WHERE status = 'pending'");
if($clearanceResult) {
    $row = $clearanceResult->fetch_assoc();
    $totalPendingClearance = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Portal - Daeyang University</title>
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
        
        .stats-cards { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { flex: 1; min-width: 150px; background: #f8f9fa; border-radius: 5px; padding: 15px; text-align: center; border: 1px solid #e0e0e0; }
        .stat-number { font-size: 28px; font-weight: bold; color: #8B4513; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { background-color: #8B4513; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
        .data-table tr:hover { background-color: #f9f9f9; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 180px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #8B4513; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        
        .btn-vacate { background-color: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-vacate:hover { background-color: #c82333; }
        .submit-btn { background-color: #8B4513; color: white; border: none; padding: 8px 25px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .submit-btn:hover { background-color: #6d3710; }
        
        .profile-field { padding: 10px 0; border-bottom: 1px solid #eee; }
        .profile-field label { font-size: 12px; color: #888; display: block; margin-bottom: 3px; }
        .profile-field p { font-size: 15px; font-weight: 500; color: #333; }
        
        .success-message { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        
        .gender-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .gender-tab { padding: 8px 20px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 5px; }
        .gender-tab.active { background: #8B4513; color: white; }
        
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
            .gender-tabs { flex-direction: column; }
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
            <a href="?page=approved" class="<?php echo ($page == 'approved') ? 'active' : ''; ?>">Approved Students</a>
            <a href="allocate.php" class="<?php echo ($page == 'allocate') ? 'active' : ''; ?>">Allocate Room</a>
            <a href="?page=allocated" class="<?php echo ($page == 'allocated') ? 'active' : ''; ?>">Allocated Students</a>
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
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalStudents; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalAllocated; ?></div>
                        <div class="stat-label">Allocated Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalAvailableRooms; ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalPendingClearance; ?></div>
                        <div class="stat-label">Pending Clearance</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- APPROVED STUDENTS PAGE -->
        <?php if($page == 'approved'): ?>
            <?php
            $approvedSql = "SELECT s.studentID, s.regNumber, s.program, s.year, s.gender, u.name 
                            FROM students s 
                            JOIN users u ON s.userID = u.userID 
                            WHERE s.applicationStatus = 'approved' 
                            AND s.registrar_status = 'approved'
                            AND (s.allocatedRoomID IS NULL OR s.allocatedRoomID = 0)
                            ORDER BY u.name";
            $approvedResult = $db->query($approvedSql);
            $approvedStudents = array();
            if($approvedResult) {
                while($row = $approvedResult->fetch_assoc()) {
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
                                    <th>Gender</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($approvedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo $student['year']; ?> Year</td>
                                    <td><?php echo $student['gender']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No approved students waiting for allocation.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ALLOCATED STUDENTS PAGE -->
        <?php if($page == 'allocated'): ?>
            <?php
            $selectedGender = isset($_GET['gender']) ? $_GET['gender'] : 'male';
            $genderCondition = ($selectedGender == 'male') ? 'Male' : 'Female';
            
            $allocatedSql = "SELECT s.studentID, s.regNumber, u.name as studentName, s.allocatedRoomID, s.allocatedDate, s.gender, s.program, s.year, r.roomNumber, r.hostelName 
                            FROM students s 
                            JOIN users u ON s.userID = u.userID 
                            LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID 
                            WHERE s.applicationStatus = 'allocated' AND s.allocationStatus = 'active' AND s.gender = '$genderCondition' 
                            ORDER BY s.regNumber";
            $allocatedResult = $db->query($allocatedSql);
            $allocatedStudents = array();
            if($allocatedResult) {
                while($row = $allocatedResult->fetch_assoc()) {
                    $allocatedStudents[] = $row;
                }
            }
            ?>
            <div class="content-card">
                <h2>Allocated Students</h2>
                <div class="gender-tabs">
                    <a href="?page=allocated&gender=male" class="gender-tab <?php echo ($selectedGender == 'male') ? 'active' : ''; ?>">Male Students</a>
                    <a href="?page=allocated&gender=female" class="gender-tab <?php echo ($selectedGender == 'female') ? 'active' : ''; ?>">Female Students</a>
                </div>
                <?php if(count($allocatedStudents) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Room Number</th>
                                    <th>Hostel</th>
                                    <th>Allocated Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allocatedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo $student['year']; ?> Year</td>
                                    <td><?php echo htmlspecialchars($student['roomNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['hostelName']); ?></td>
                                    <td><?php echo $student['allocatedDate']; ?></td>
                                    <td>
                                        <form method="POST" action="allocated.php" style="display:inline;">
                                            <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                            <button type="submit" name="vacate" class="btn-vacate" onclick="return confirm('Vacate this room?')">Vacate</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No <?php echo ($selectedGender == 'male') ? 'male' : 'female'; ?> students allocated yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- PROFILE PAGE -->
        <?php if($page == 'profile'): ?>
            <div class="content-card">
                <h2>My Profile</h2>
                <div class="profile-field">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($userData['name']); ?></p>
                </div>
                <div class="profile-field">
                    <label>Email Address</label>
                    <p><?php echo $userData['email'] ?: 'Not set'; ?></p>
                </div>
                <div class="profile-field">
                    <label>Phone Number</label>
                    <p><?php echo $userData['phone'] ?: 'Not set'; ?></p>
                </div>
                <div class="profile-field">
                    <label>Role</label>
                    <p>Warden</p>
                </div>
                
                <h3 style="margin:25px 0 15px 0; color:#8B4513;">Update Contact Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo $userData['phone']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo $userData['email']; ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Daeyang University is a Christian University founded by the Miracle for Africa Foundation.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <p><a href="#">Home</a></p>
                <p><a href="#">About Us</a></p>
                <p><a href="#">Contact Us</a></p>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>+265994000389</p>
                <p>registrar@dyuni.ac.mw</p>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Daeyang University. All rights reserved.
        </div>
    </div>
</body>
</html>