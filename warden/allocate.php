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

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $studentID = $_POST['studentID'];
    $roomID = $_POST['roomID'];
    if($warden->allocateRoom($studentID, $roomID)) {
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
    <title>Allocate Room - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=14">
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Allocate Room</h1>

            <?php if($allocated): ?>
                <div class="success-message">Room allocated successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Allocation failed. Gender mismatch or room not available.</div>
            <?php endif; ?>

            <?php if(count($approvedStudents) > 0): ?>
                <form method="POST" id="allocationForm">
                    <div class="form-row">
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
                    </div>
                    <button type="submit" name="allocate" class="submit-btn" onclick="return confirm('Allocate this room?')">Allocate Room</button>
                </form>
            <?php else: ?>
                <p>No approved students ready for allocation.</p>
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