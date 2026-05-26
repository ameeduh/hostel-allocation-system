<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];
$regNumber = $_SESSION['username'];

// Check if student already has allocated room
$checkSql = "SELECT applicationStatus, allocatedRoomID FROM students WHERE studentID = $studentID";
$checkResult = $db->query($checkSql);
$studentStatus = $checkResult->fetch_assoc();

if($studentStatus['applicationStatus'] == 'allocated') {
    header("Location: dashboard.php?page=room");
    exit();
}

// Check if student has already selected a room preference
$prefSql = "SELECT * FROM room_preferences WHERE studentID = $studentID AND status = 'pending'";
$prefResult = $db->query($prefSql);
$existingPreference = $prefResult->fetch_assoc();

// Handle room selection
$message = '';
$messageType = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_room'])) {
    $selectedRoomID = (int)$_POST['room_id'];
    
    // Check if room still has available beds
    $roomSql = "SELECT * FROM rooms WHERE roomID = $selectedRoomID AND availableBeds > 0";
    $roomResult = $db->query($roomSql);
    
    if($roomResult && $roomResult->num_rows > 0) {
        $room = $roomResult->fetch_assoc();
        
        // Check gender compatibility
        $studentSql = "SELECT gender FROM students WHERE studentID = $studentID";
        $studentResult = $db->query($studentSql);
        $student = $studentResult->fetch_assoc();
        
        if($room['gender'] != $student['gender']) {
            $message = "You cannot select a room designated for the opposite gender.";
            $messageType = "error";
        } else {
            // Save preference
            $insertSql = "INSERT INTO room_preferences (studentID, preferredRoomID, selectedDate, status) 
                          VALUES ($studentID, $selectedRoomID, NOW(), 'pending')";
            $db->query($insertSql);
            
            $message = "Room selected successfully! Your room will be assigned after fee verification.";
            $messageType = "success";
            
            // Refresh to show selection
            header("Location: apply_room.php?selected=1");
            exit();
        }
    } else {
        $message = "This room is no longer available. Please choose another room.";
        $messageType = "error";
    }
}

// Handle change room
if(isset($_GET['change'])) {
    $deleteSql = "DELETE FROM room_preferences WHERE studentID = $studentID AND status = 'pending'";
    $db->query($deleteSql);
    header("Location: apply_room.php");
    exit();
}

// Get available rooms
$studentGenderSql = "SELECT gender FROM students WHERE studentID = $studentID";
$studentGenderResult = $db->query($studentGenderSql);
$studentGender = $studentGenderResult->fetch_assoc();

$roomsSql = "SELECT * FROM rooms WHERE availableBeds > 0 AND gender = '{$studentGender['gender']}' ORDER BY hostelName, roomNumber";
$roomsResult = $db->query($roomsSql);
$availableRooms = array();
if($roomsResult) {
    while($row = $roomsResult->fetch_assoc()) {
        $availableRooms[] = $row;
    }
}

// Get current preference
$currentPreference = null;
if($existingPreference) {
    $prefRoomSql = "SELECT r.* FROM rooms r JOIN room_preferences p ON r.roomID = p.preferredRoomID WHERE p.studentID = $studentID";
    $prefRoomResult = $db->query($prefRoomSql);
    $currentPreference = $prefRoomResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Room - Daeyang University</title>
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
        
        .room-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:20px;}
        .room-card{border:1px solid #e0e0e0;border-radius:8px;padding:15px;transition:all 0.3s;}
        .room-card:hover{box-shadow:0 5px 15px rgba(0,0,0,0.1);transform:translateY(-2px);}
        .room-card h3{color:#8B4513;margin-bottom:10px;font-size:16px;}
        .room-card p{margin:5px 0;font-size:13px;color:#666;}
        .room-badge{display:inline-block;padding:3px 8px;border-radius:4px;font-size:11px;margin-top:10px;}
        .badge-available{background:#d4edda;color:#155724;}
        .badge-limited{background:#fff3cd;color:#856404;}
        
        .btn-select{background-color:#8B4513;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;font-size:13px;margin-top:10px;width:100%;}
        .btn-select:hover{background-color:#6d3710;}
        .btn-change{background-color:#dc3545;color:white;border:none;padding:8px 20px;border-radius:5px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block;}
        .btn-change:hover{background-color:#c82333;}
        
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
            .room-grid{grid-template-columns:1fr;}
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
            <a href="dashboard.php?page=details">Complete Details</a>
            <a href="dashboard.php?page=room">Room Details</a>
            <a href="dashboard.php?page=profile">Profile</a>
            <a href="request_fee_commitment.php">Request Fee Commitment</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="welcome-section">
        <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
        <p>Registration Number: <?php echo $regNumber; ?></p>
    </div>

    <div class="content-area">
        <div class="content-card">
            <h2>Select Your Preferred Room</h2>
            
            <?php if($message): ?>
                <div class="<?php echo $messageType; ?>-message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($_GET['selected'])): ?>
                <div class="success-message">Room preference saved! After fee verification, your room will be automatically assigned.</div>
            <?php endif; ?>
            
            <?php if($currentPreference): ?>
                <div class="info-message">
                    <strong>Your current room preference:</strong> <?php echo $currentPreference['roomNumber']; ?> (<?php echo $currentPreference['hostelName']; ?> Hostel)
                    <br><br>
                    <a href="apply_room.php?change=1" class="btn-change" onclick="return confirm('Change your room preference?')">Change Room</a>
                </div>
            <?php endif; ?>
            
            <?php if(!$currentPreference): ?>
                <p style="margin-bottom:15px; color:#666;">Select a room from the options below. Your room will be automatically assigned after fee verification.</p>
                
                <?php if(count($availableRooms) > 0): ?>
                    <div class="room-grid">
                        <?php foreach($availableRooms as $room): ?>
                            <div class="room-card">
                                <h3>Room <?php echo $room['roomNumber']; ?></h3>
                                <p><strong>Hostel:</strong> <?php echo $room['hostelName']; ?></p>
                                <p><strong>Gender:</strong> <?php echo $room['gender']; ?></p>
                                <p><strong>Capacity:</strong> <?php echo $room['capacity']; ?> beds</p>
                                <p><strong>Available Beds:</strong> <?php echo $room['availableBeds']; ?></p>
                                <?php if($room['availableBeds'] <= 2): ?>
                                    <span class="room-badge badge-limited">Limited availability</span>
                                <?php else: ?>
                                    <span class="room-badge badge-available">Available</span>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="room_id" value="<?php echo $room['roomID']; ?>">
                                    <button type="submit" name="select_room" class="btn-select">Select This Room</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="error-message">
                        No rooms available at the moment. Please check back later or contact the Warden's office.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
</body>
</html>