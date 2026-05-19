<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../classes/Warden.php';

$warden = new Warden();
$warden->login($_SESSION['username'], 'password123');

$approvedStudents = $warden->viewApprovedStudents();
$availableRooms = $warden->viewAvailableRooms();

$selectedStudentID = isset($_GET['studentID']) ? $_GET['studentID'] : null;
$filteredRooms = [];

if($selectedStudentID) {
    $db = new Database();
    $studentSql = "SELECT gender FROM students WHERE studentID = $selectedStudentID";
    $studentResult = $db->query($studentSql);
    $student = $studentResult->fetch_assoc();
    $studentGender = $student['gender'];
    
    $filteredRooms = array_filter($availableRooms, function($room) use ($studentGender) {
        return $room['gender'] == $studentGender;
    });
}

// Handle allocation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $studentID = $_POST['studentID'];
    $roomID = $_POST['roomID'];
    
    if($warden->allocateRoom($studentID, $roomID)) {
        // Get student email and room details
        $db = new Database();
        $emailSql = "SELECT u.email FROM students s JOIN users u ON s.userID = u.userID WHERE s.studentID = $studentID";
        $emailResult = $db->query($emailSql);
        $student = $emailResult->fetch_assoc();
        
        $roomSql = "SELECT roomNumber, hostelName FROM rooms WHERE roomID = $roomID";
        $roomResult = $db->query($roomSql);
        $room = $roomResult->fetch_assoc();
        
        // Send email notification
        if($student && $student['email'] && !empty($student['email'])) {
            require_once '../config/mail_config.php';
            $subject = "Hostel Application - Room Allocated";
            $body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { padding: 20px; }
                        .room { color: #2e7d32; font-weight: bold; }
                        .footer { font-size: 12px; color: #666; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>Hostel Allocation System</h2>
                        <p>Dear Student,</p>
                        <p>Congratulations! You have been allocated a room.</p>
                        <p><strong class='room'>Room Number:</strong> " . $room['roomNumber'] . "</p>
                        <p><strong>Hostel Name:</strong> " . $room['hostelName'] . "</p>
                        <p>Please visit the Warden's office to collect your keys.</p>
                        <div class='footer'>
                            <hr>
                            <p>This is an automated message from Hostel Allocation System.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            sendEmail($student['email'], $subject, $body);
        }
        
        header("Location: allocate.php?allocated=1");
    } else {
        header("Location: allocate.php?error=1");
    }
    exit();
}

$allocated = isset($_GET['allocated']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Room - Warden Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .full-page-container {
            width: 100%;
            min-height: 100vh;
            background-color: #F5F5F5;
            padding: 40px;
        }
        .full-content-card {
            background-color: white;
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            border-radius: 10px;
            border-top: 5px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #8B4513;
            margin-bottom: 8px;
        }
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .submit-btn {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
        }
        .submit-btn:hover {
            background-color: #A0522D;
        }
        .back-link-bottom {
            margin-top: 30px;
            text-align: right;
        }
        .back-btn {
            background-color: #8B4513;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            color: #8B4513;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Allocate Room</h1>

            <?php if($allocated): ?>
                <div class="success-message">Room allocated successfully! Email sent to student.</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Allocation failed. Gender mismatch or room not available.</div>
            <?php endif; ?>

            <?php if(count($approvedStudents) > 0 && count($availableRooms) > 0): ?>
                <form method="POST" id="allocationForm">
                    <div class="form-group">
                        <label>Select Student</label>
                        <select name="studentID" id="studentSelect" required>
                            <option value="">Select Student</option>
                            <?php foreach($approvedStudents as $student): ?>
                                <option value="<?php echo $student['studentID']; ?>" 
                                    <?php echo ($selectedStudentID == $student['studentID']) ? 'selected' : ''; ?>>
                                    <?php echo $student['name']; ?> (<?php echo $student['gender']; ?>) - <?php echo $student['program']; ?> - <?php echo $student['year']; ?> Year
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Room</label>
                        <select name="roomID" id="roomSelect" required>
                            <option value="">First select a student</option>
                            <?php if($selectedStudentID): ?>
                                <?php foreach($filteredRooms as $room): ?>
                                    <option value="<?php echo $room['roomID']; ?>">
                                        <?php echo $room['roomNumber']; ?> - <?php echo $room['hostelName']; ?> (<?php echo $room['gender']; ?>) - <?php echo $room['availableBeds']; ?> beds left
                                    </option>
                                <?php endforeach; ?>
                                <?php if(count($filteredRooms) == 0): ?>
                                    <option value="" disabled>No rooms available for this gender</option>
                                <?php endif; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" name="allocate" class="submit-btn" onclick="return confirm('Allocate this room?')">Allocate Room</button>
                </form>
            <?php elseif(count($approvedStudents) == 0): ?>
                <p>No approved students ready for allocation.</p>
            <?php elseif(count($availableRooms) == 0): ?>
                <p>No available rooms.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('studentSelect').addEventListener('change', function() {
            var studentID = this.value;
            if(studentID) {
                window.location.href = 'allocate.php?studentID=' + studentID;
            }
        });
    </script>
</body>
</html>