<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../classes/Warden.php';

$warden = new Warden();
$warden->login($_SESSION['username'], 'password123');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vacate'])) {
    $studentID = $_POST['studentID'];
    if($warden->vacateRoom($studentID)) {
        header("Location: allocated_students.php?vacated=1&gender=" . $_GET['gender']);
    } else {
        header("Location: allocated_students.php?error=1&gender=" . $_GET['gender']);
    }
    exit();
}

$selectedGender = isset($_GET['gender']) ? $_GET['gender'] : 'male';
$vacated = isset($_GET['vacated']);
$error = isset($_GET['error']);

$db = new Database();
$genderCondition = ($selectedGender == 'male') ? 'Male' : 'Female';
$sql = "SELECT s.studentID, s.regNumber, u.name as studentName, 
               s.allocatedRoomID, s.allocatedDate, s.gender, s.program, s.year,
               r.roomNumber, r.hostelName
        FROM students s 
        JOIN users u ON s.userID = u.userID
        LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID
        WHERE s.applicationStatus = 'allocated' 
        AND s.allocationStatus = 'active'
        AND s.gender = '$genderCondition'
        ORDER BY s.regNumber";
$result = $db->query($sql);
$allocatedStudents = $result->fetch_all(MYSQLI_ASSOC);
$totalCount = count($allocatedStudents);

// Function to format year
function formatYear($year) {
    if($year == 1) return '1st Year';
    elseif($year == 2) return '2nd Year';
    elseif($year == 3) return '3rd Year';
    elseif($year == 4) return '4th Year';
    else return 'N/A';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocated Students - Warden Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=6">
    <style>
        .filter-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            justify-content: center;
        }
        .filter-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .filter-btn.male {
            background-color: #2c3e66;
            color: white;
        }
        .filter-btn.male:hover, .filter-btn.male.active {
            background-color: #1a2a4a;
        }
        .filter-btn.female {
            background-color: #8B4513;
            color: white;
        }
        .filter-btn.female:hover, .filter-btn.female.active {
            background-color: #6b3410;
        }
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        .students-table th, .students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .students-table th {
            background-color: #8B4513;
            color: white;
        }
        .students-table tr:hover {
            background-color: #FFF8DC;
        }
        .vacate-btn {
            background-color: #ff9800;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .vacate-btn:hover {
            background-color: #e68900;
        }
        .section-header {
            margin-bottom: 20px;
        }
        .section-header h2 {
            font-size: 20px;
            color: #8B4513;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
            padding-bottom: 5px;
        }
        .count-badge {
            background-color: #8B4513;
            color: white;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
        @media (max-width: 768px) {
            .students-table th, .students-table td {
                display: block;
            }
            .filter-buttons {
                flex-direction: column;
            }
            .filter-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">
                <h2>Hostel</h2>
                <p>Allocation System</p>
            </div>
            <div class="sidebar-footer">
                <a href="dashboard.php" class="logout-link">
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Allocated Students</h1>
                    <p>Select gender to view allocated students</p>
                </div>
            </div>

            <?php if($vacated): ?>
                <div class="success-message">Room vacated successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Failed to vacate room.</div>
            <?php endif; ?>

            <div class="filter-buttons">
                <a href="?gender=male" class="filter-btn male <?php echo ($selectedGender == 'male') ? 'active' : ''; ?>">
                    Male Students
                </a>
                <a href="?gender=female" class="filter-btn female <?php echo ($selectedGender == 'female') ? 'active' : ''; ?>">
                    Female Students
                </a>
            </div>

            <?php if(count($allocatedStudents) > 0): ?>
                <div class="section-header">
                    <h2>
                        <?php echo ($selectedGender == 'male') ? 'Male Students' : 'Female Students'; ?>
                        <span class="count-badge"><?php echo $totalCount; ?></span>
                    </h2>
                </div>

                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Reg Number</th>
                            <th>Student Name</th>
                            <th>Program</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Room Number</th>
                            <th>Hostel</th>
                            <th>Allocated Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allocatedStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['regNumber']; ?></td>
                                <td><?php echo $student['studentName']; ?></td>
                                <td><?php echo $student['program'] ?: 'N/A'; ?></td>
                                <td><?php echo formatYear($student['year']); ?></td>
                                <td><?php echo $student['gender']; ?></td>
                                <td><?php echo $student['roomNumber']; ?></td>
                                <td><?php echo $student['hostelName']; ?></td>
                                <td><?php echo $student['allocatedDate']; ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                        <input type="hidden" name="gender" value="<?php echo $selectedGender; ?>">
                                        <button type="submit" name="vacate" class="vacate-btn" onclick="return confirm('Vacate room for <?php echo $student['studentName']; ?>?')">Vacate</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No <?php echo ($selectedGender == 'male') ? 'Male' : 'Female'; ?> Students Allocated</h3>
                    <p>There are no <?php echo ($selectedGender == 'male') ? 'male' : 'female'; ?> students with allocated rooms yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>