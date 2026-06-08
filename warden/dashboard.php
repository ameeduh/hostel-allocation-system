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
$reportHostel = isset($_GET['report_hostel']) ? $_GET['report_hostel'] : '';
$reportRoom = isset($_GET['report_room']) ? $_GET['report_room'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

$allocatedHostel = isset($_GET['allocated_hostel']) ? $_GET['allocated_hostel'] : '';
$allocatedRoom = isset($_GET['allocated_room']) ? $_GET['allocated_room'] : '';

// For Available Rooms tab
$availableTab = isset($_GET['available_tab']) ? $_GET['available_tab'] : '';
$availableHostel = isset($_GET['available_hostel']) ? $_GET['available_hostel'] : 'Eswanthini';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $sql = "UPDATE users SET phone = '$phone', email = '$email' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql);
    header("Location: dashboard.php?page=profile&updated=1");
    exit();
}

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

$profileUpdated = isset($_GET['updated']);

$totalAllocated = 0;
$allocatedResult = $db->query("SELECT COUNT(*) as total FROM students WHERE applicationStatus = 'allocated' AND allocationStatus = 'active'");
if($allocatedResult) {
    $row = $allocatedResult->fetch_assoc();
    $totalAllocated = $row['total'];
}

$availableRooms = 0;
$roomsResult = $db->query("SELECT COUNT(*) as total FROM rooms WHERE availableBeds > 0 AND status = 'available'");
if($roomsResult) {
    $row = $roomsResult->fetch_assoc();
    $availableRooms = $row['total'];
}

$pendingClearance = 0;
$clearanceResult = $db->query("SELECT COUNT(*) as total FROM clearance WHERE status = 'pending'");
if($clearanceResult) {
    $row = $clearanceResult->fetch_assoc();
    $pendingClearance = $row['total'];
}

$totalStudents = 0;
$studentsResult = $db->query("SELECT COUNT(*) as total FROM students");
if($studentsResult) {
    $row = $studentsResult->fetch_assoc();
    $totalStudents = $row['total'];
}

$hostels = ['Eswanthini', 'Seychells', 'Namibia', 'Botswana', 'Lesotho'];

$roomsList = array();
if($allocatedHostel) {
    $roomSql = "SELECT DISTINCT roomNumber, roomID FROM rooms WHERE hostelName = '$allocatedHostel' ORDER BY roomNumber";
    $roomResult = $db->query($roomSql);
    if($roomResult) {
        while($row = $roomResult->fetch_assoc()) {
            $roomsList[] = $row;
        }
    }
}

$reportRoomsList = array();
if($reportHostel) {
    $roomSql = "SELECT DISTINCT roomNumber, roomID FROM rooms WHERE hostelName = '$reportHostel' ORDER BY roomNumber";
    $roomResult = $db->query($roomSql);
    if($roomResult) {
        while($row = $roomResult->fetch_assoc()) {
            $reportRoomsList[] = $row;
        }
    }
}

$allocatedWhere = " WHERE s.applicationStatus = 'allocated' AND s.allocationStatus = 'active'";
if($allocatedHostel) {
    $allocatedWhere .= " AND r.hostelName = '$allocatedHostel'";
}
if($allocatedRoom) {
    $allocatedWhere .= " AND r.roomNumber = '$allocatedRoom'";
}

$allocatedSql = "SELECT s.studentID, s.regNumber, u.name as studentName, s.allocatedRoomID, s.allocatedDate, s.gender, s.program, s.year, r.roomNumber, r.hostelName 
                FROM students s 
                JOIN users u ON s.userID = u.userID 
                LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID 
                $allocatedWhere
                ORDER BY s.allocatedDate DESC";
$allocatedResult = $db->query($allocatedSql);
$allocatedStudents = array();
if($allocatedResult) {
    while($row = $allocatedResult->fetch_assoc()) {
        $allocatedStudents[] = $row;
    }
}

// Get available rooms for the selected hostel
$availableRoomsList = array();
$totalAvailableBeds = 0;
$roomsWithBeds = 0;

if($availableHostel) {
    $roomSql = "SELECT * FROM rooms WHERE hostelName = '$availableHostel' ORDER BY roomNumber";
    $roomResult = $db->query($roomSql);
    if($roomResult) {
        while($row = $roomResult->fetch_assoc()) {
            $availableRoomsList[] = $row;
            if($row['availableBeds'] > 0) {
                $totalAvailableBeds += $row['availableBeds'];
                $roomsWithBeds++;
            }
        }
    }
}

$reportWhere = " WHERE s.applicationStatus = 'allocated' AND s.allocationStatus = 'active'";
if($reportHostel) {
    $reportWhere .= " AND r.hostelName = '$reportHostel'";
}
if($reportRoom) {
    $reportWhere .= " AND r.roomNumber = '$reportRoom'";
}
if($from_date && $to_date) {
    $reportWhere .= " AND s.allocatedDate BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $reportWhere .= " AND s.allocatedDate >= '$from_date'";
} elseif($to_date) {
    $reportWhere .= " AND s.allocatedDate <= '$to_date'";
}

$reportSql = "SELECT s.studentID, s.regNumber, u.name as studentName, s.allocatedRoomID, s.allocatedDate, s.gender, s.program, s.year, r.roomNumber, r.hostelName 
              FROM students s 
              JOIN users u ON s.userID = u.userID 
              LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID 
              $reportWhere
              ORDER BY s.allocatedDate DESC";
$reportResult = $db->query($reportSql);
$reportStudents = array();
if($reportResult) {
    while($row = $reportResult->fetch_assoc()) {
        $reportStudents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Portal - Daeyang University</title>
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
        
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;}
        .sub-tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;cursor:pointer;}
        .sub-tab.active{background:#8B4513;color:white;}
        .sub-tab:hover{background:#8B4513;color:white;}
        
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;align-items:center;padding:10px 0;border-bottom:1px solid #eee;}
        .filter-group{display:flex;align-items:center;gap:8px;}
        .filter-group label{font-weight:600;color:#8B4513;font-size:13px;}
        .filter-group select,.filter-group input{padding:6px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:white;}
        .filter-group select:focus,.filter-group input:focus{border-color:#8B4513;outline:none;}
        .btn-filter{background-color:#8B4513;color:white;border:none;padding:6px 15px;border-radius:4px;cursor:pointer;}
        .btn-filter:hover{background-color:#6d3710;}
        
        .export-buttons{display:flex;gap:10px;justify-content:flex-end;margin-bottom:20px;}
        .btn-export{background-color:#8B4513;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block;}
        .btn-export:hover{background-color:#6d3710;}
        
        .stats-cards{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap;}
        .stat-card{flex:1;min-width:150px;background:#f8f9fa;border-radius:5px;padding:15px;text-align:center;border:1px solid #e0e0e0;}
        .stat-number{font-size:28px;font-weight:bold;color:#8B4513;}
        .stat-label{font-size:12px;color:#666;margin-top:5px;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;font-size:13px;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;font-size:13px;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500;}
        .badge-available{background:#d4edda;color:#155724;}
        .badge-full{background:#f8d7da;color:#721c24;}
        
        .summary-box{background:#e3f2fd;padding:12px 20px;border-radius:8px;margin-top:20px;border-left:4px solid #2196f3;}
        .summary-box strong{color:#1565c0;}
        
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
            .stats-cards{flex-direction:column;}
            .data-table{overflow-x:auto;display:block;}
            .filter-bar{flex-direction:column;align-items:flex-start;}
            .sub-tabs{flex-direction:column;}
        }
    </style>
    <script>
        function updateRooms() {
            var hostel = document.getElementById('allocatedHostel').value;
            if(hostel) {
                window.location.href = 'dashboard.php?page=allocated&allocated_hostel=' + hostel;
            } else {
                window.location.href = 'dashboard.php?page=allocated';
            }
        }
        
        function updateReportRooms() {
            var hostel = document.getElementById('reportHostel').value;
            var fromDate = document.getElementById('fromDate').value;
            var toDate = document.getElementById('toDate').value;
            if(hostel) {
                window.location.href = 'dashboard.php?page=reports&report_hostel=' + hostel + '&from_date=' + fromDate + '&to_date=' + toDate;
            } else {
                window.location.href = 'dashboard.php?page=reports&from_date=' + fromDate + '&to_date=' + toDate;
            }
        }
        
        function applyReportFilter() {
            var hostel = document.getElementById('reportHostel').value;
            var room = document.getElementById('reportRoom').value;
            var fromDate = document.getElementById('fromDate').value;
            var toDate = document.getElementById('toDate').value;
            window.location.href = 'dashboard.php?page=reports&report_hostel=' + hostel + '&report_room=' + room + '&from_date=' + fromDate + '&to_date=' + toDate;
        }
        
        function applyAllocatedFilter() {
            var hostel = document.getElementById('allocatedHostel').value;
            var room = document.getElementById('allocatedRoom').value;
            window.location.href = 'dashboard.php?page=allocated&allocated_hostel=' + hostel + '&allocated_room=' + room;
        }
        
        function clearAllocatedFilter() {
            window.location.href = 'dashboard.php?page=allocated';
        }
        
        function clearReportFilter() {
            window.location.href = 'dashboard.php?page=reports';
        }
        
        // Tab switching for Allocated Students page
        function showAllocatedTab() {
            document.getElementById('allocatedTabContent').style.display = 'block';
            document.getElementById('availableTabContent').style.display = 'none';
            document.getElementById('allocatedTabBtn').classList.add('active');
            document.getElementById('availableTabBtn').classList.remove('active');
        }
        
        function showAvailableTab() {
            document.getElementById('allocatedTabContent').style.display = 'none';
            document.getElementById('availableTabContent').style.display = 'block';
            document.getElementById('availableTabBtn').classList.add('active');
            document.getElementById('allocatedTabBtn').classList.remove('active');
        }
        
        function filterAvailableRooms() {
            var hostel = document.getElementById('availableHostelSelect').value;
            window.location.href = 'dashboard.php?page=allocated&available_tab=1&available_hostel=' + hostel;
        }
    </script>
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
        <a href="?page=allocated" class="<?php echo ($page=='allocated')?'active':''; ?>">Allocated Students</a>
        <a href="?page=reports" class="<?php echo ($page=='reports')?'active':''; ?>">Reports</a>
        <a href="?page=clearance" class="<?php echo ($page=='clearance')?'active':''; ?>">Clearance Requests</a>
        <a href="blacklist.php">Blacklist</a>
        <a href="?page=profile" class="<?php echo ($page=='profile')?'active':''; ?>">Profile</a>
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
                <div class="stat-card"><div class="stat-number"><?php echo $totalStudents; ?></div><div class="stat-label">Total Students</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $totalAllocated; ?></div><div class="stat-label">Allocated Students</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $availableRooms; ?></div><div class="stat-label">Available Rooms</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $pendingClearance; ?></div><div class="stat-label">Pending Clearance</div></div>
            </div>
        </div>
        
    <!-- ALLOCATED STUDENTS PAGE (with tabs) -->
    <?php elseif($page == 'allocated'): ?>
        <div class="content-card">
            <h2>Hostel Management</h2>
            
            <!-- Tabs -->
            <div class="sub-tabs">
                <a href="javascript:void(0)" id="allocatedTabBtn" onclick="showAllocatedTab()" class="sub-tab <?php echo (!$availableTab) ? 'active' : ''; ?>">Allocated Students</a>
                <a href="javascript:void(0)" id="availableTabBtn" onclick="showAvailableTab()" class="sub-tab <?php echo ($availableTab) ? 'active' : ''; ?>">Available Rooms</a>
            </div>
            
            <!-- TAB 1: Allocated Students (Existing content) -->
            <div id="allocatedTabContent" style="display: <?php echo (!$availableTab) ? 'block' : 'none'; ?>;">
                <div class="filter-bar">
                    <div class="filter-group">
                        <label>Hostel:</label>
                        <select id="allocatedHostel" onchange="updateRooms()">
                            <option value="">All Hostels</option>
                            <?php foreach($hostels as $hostel): ?>
                                <option value="<?php echo $hostel; ?>" <?php echo ($allocatedHostel == $hostel) ? 'selected' : ''; ?>><?php echo $hostel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Room:</label>
                        <select id="allocatedRoom" onchange="applyAllocatedFilter()">
                            <option value="">All Rooms</option>
                            <?php foreach($roomsList as $room): ?>
                                <option value="<?php echo $room['roomNumber']; ?>" <?php echo ($allocatedRoom == $room['roomNumber']) ? 'selected' : ''; ?>><?php echo $room['roomNumber']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn-filter" onclick="clearAllocatedFilter()">Clear Filter</button>
                </div>
                
                <?php if(count($allocatedStudents) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Reg Number</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Gender</th>
                                    <th>Room Number</th>
                                    <th>Hostel</th>
                                    <th>Allocated Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allocatedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year']); ?> Year</td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($student['roomNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['hostelName']); ?></td>
                                    <td><?php echo htmlspecialchars($student['allocatedDate']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No allocated students found for the selected criteria.</p>
                <?php endif; ?>
            </div>
            
            <!-- TAB 2: Available Rooms (NEW FEATURE) -->
            <div id="availableTabContent" style="display: <?php echo ($availableTab) ? 'block' : 'none'; ?>;">
                <!-- Hostel Filter Dropdown -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <label>Select Hostel:</label>
                        <select id="availableHostelSelect" onchange="filterAvailableRooms()">
                            <?php foreach($hostels as $hostel): ?>
                                <option value="<?php echo $hostel; ?>" <?php echo ($availableHostel == $hostel) ? 'selected' : ''; ?>><?php echo $hostel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if(count($availableRoomsList) > 0): ?>
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
                                <?php foreach($availableRoomsList as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                                    <td><?php echo htmlspecialchars($room['gender']); ?></td>
                                    <td><?php echo $room['capacity']; ?></td>
                                    <td><?php echo $room['availableBeds']; ?></td>
                                    <td>
                                        <?php if($room['availableBeds'] > 0): ?>
                                            <span class="badge badge-available">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-full">Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Summary Box -->
                    <div class="summary-box">
                        <strong>Summary for <?php echo $availableHostel; ?> Hostel:</strong><br>
                        <?php echo $roomsWithBeds; ?> room(s) have available beds<br>
                        <?php echo $totalAvailableBeds; ?> total bed(s) available
                    </div>
                <?php else: ?>
                    <p>No rooms found for <?php echo $availableHostel; ?> hostel.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <!-- REPORTS PAGE -->
    <?php elseif($page == 'reports'): ?>
        <div class="content-card">
            <h2>Allocated Students Reports</h2>
            
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Hostel:</label>
                    <select id="reportHostel" onchange="updateReportRooms()">
                        <option value="">All Hostels</option>
                        <?php foreach($hostels as $hostel): ?>
                            <option value="<?php echo $hostel; ?>" <?php echo ($reportHostel == $hostel) ? 'selected' : ''; ?>><?php echo $hostel; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Room:</label>
                    <select id="reportRoom">
                        <option value="">All Rooms</option>
                        <?php foreach($reportRoomsList as $room): ?>
                            <option value="<?php echo $room['roomNumber']; ?>" <?php echo ($reportRoom == $room['roomNumber']) ? 'selected' : ''; ?>><?php echo $room['roomNumber']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" id="fromDate" value="<?php echo $from_date; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" id="toDate" value="<?php echo $to_date; ?>">
                </div>
                <button class="btn-filter" onclick="applyReportFilter()">Apply Filter</button>
                <button class="btn-filter" onclick="clearReportFilter()">Clear Filter</button>
            </div>
            
            <div class="export-buttons">
                <button onclick="window.print()" class="btn-export">Print / Save as PDF</button>
                <a href="export_csv.php?hostel=<?php echo $reportHostel; ?>&room=<?php echo $reportRoom; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn-export">Export Excel</a>
            </div>
            
            <?php if(count($reportStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="reportTable">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Room Number</th>
                                <th>Hostel</th>
                                <th>Allocated Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reportStudents as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['program']); ?></td>
                                <td><?php echo htmlspecialchars($student['year']); ?> Year</td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td><?php echo htmlspecialchars($student['roomNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['hostelName']); ?></td>
                                <td><?php echo htmlspecialchars($student['allocatedDate']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No allocated students found for the selected criteria.</p>
            <?php endif; ?>
        </div>
        
    <!-- CLEARANCE REQUESTS PAGE -->
    <?php elseif($page == 'clearance'): ?>
        <?php
        $clearanceSql = "SELECT c.*, s.regNumber, u.name as studentName, s.program, s.year, r.roomNumber, r.hostelName 
                        FROM clearance c 
                        JOIN students s ON c.studentID = s.studentID 
                        JOIN users u ON s.userID = u.userID 
                        LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID 
                        WHERE c.status = 'pending' 
                        ORDER BY c.requestDate";
        $clearanceResult = $db->query($clearanceSql);
        $clearanceRequests = array();
        if($clearanceResult) {
            while($row = $clearanceResult->fetch_assoc()) {
                $clearanceRequests[] = $row;
            }
        }
        ?>
        <div class="content-card">
            <h2>Clearance Requests</h2>
            <?php if(count($clearanceRequests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Room Number</th>
                                <th>Hostel</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clearanceRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['studentName']); ?></td>
                                <td><?php echo htmlspecialchars($request['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($request['roomNumber']); ?></td>
                                <td><?php echo htmlspecialchars($request['hostelName']); ?></td>
                                <td><?php echo $request['requestDate']; ?></td>
                                <td>
                                    <form method="POST" action="process_clearance.php" style="display:inline;">
                                        <input type="hidden" name="clearanceID" value="<?php echo $request['clearanceID']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-export" style="background-color:#28a745;" onclick="return confirm('Approve this clearance request?')">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn-export" style="background-color:#dc3545;" onclick="return confirm('Reject this clearance request?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending clearance requests.</p>
            <?php endif; ?>
        </div>
        
    <!-- PROFILE PAGE -->
    <?php elseif($page == 'profile'): ?>
        <div class="content-card">
            <h2>My Profile</h2>
            <div class="profile-field"><label>Full Name</label><p><?php echo $userData['name']; ?></p></div>
            <div class="profile-field"><label>Email Address</label><p><?php echo $userData['email'] ?: 'Not set'; ?></p></div>
            <div class="profile-field"><label>Phone Number</label><p><?php echo $userData['phone'] ?: 'Not set'; ?></p></div>
            <div class="profile-field"><label>Role</label><p>Warden</p></div>
            
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