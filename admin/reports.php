<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get all students
$studentsSql = "SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID ORDER BY u.name";
$studentsResult = $db->query($studentsSql);
$students = $studentsResult->fetch_all(MYSQLI_ASSOC);

// Get room occupancy
$roomsSql = "SELECT roomNumber, hostelName, gender, capacity, availableBeds, 
                    (capacity - availableBeds) as occupiedBeds, status 
             FROM rooms ORDER BY hostelName, roomNumber";
$roomsResult = $db->query($roomsSql);
$rooms = $roomsResult->fetch_all(MYSQLI_ASSOC);

// Get pending approvals
$pendingSql = "SELECT s.*, u.name FROM students s 
               JOIN users u ON s.userID = u.userID 
               WHERE s.applicationStatus = 'pending'";
$pendingResult = $db->query($pendingSql);
$pendingStudents = $pendingResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=16");
    <style>
        .report-section {
            margin-bottom: 40px;
        }
        .report-section h2 {
            color: #8B4513;
            border-bottom: 2px solid #FFD700;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #8B4513;
            color: white;
        }
        .status-pending {
            background-color: #FFF8DC;
            color: #8B4513;
            padding: 2px 8px;
            border-radius: 5px;
        }
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Reports</h1>

            <!-- All Students Report -->
            <div class="report-section">
                <h2>All Students</h2>
                <?php if(count($students) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Registration Number</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['regNumber']; ?></td>
                                <td><?php echo $student['program'] ?: 'N/A'; ?></td>
                                <td>
                                    <?php 
                                    $year = $student['year'];
                                    if($year == 1) echo '1st Year';
                                    elseif($year == 2) echo '2nd Year';
                                    elseif($year == 3) echo '3rd Year';
                                    elseif($year == 4) echo '4th Year';
                                    else echo 'N/A';
                                    ?>
                                </td>
                                <td><?php echo $student['gender'] ?: 'N/A'; ?></td>
                                <td><span class="status-<?php echo $student['applicationStatus']; ?>"><?php echo ucfirst($student['applicationStatus']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No students found.</p>
                <?php endif; ?>
            </div>

            <!-- Room Occupancy Report -->
            <div class="report-section">
                <h2>Room Occupancy</h2>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rooms as $room): ?>
                            <tr>
                                <td><?php echo $room['roomNumber']; ?></td>
                                <td><?php echo $room['hostelName']; ?></td>
                                <td><?php echo $room['gender']; ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td><?php echo $room['occupiedBeds']; ?></td>
                                <td><?php echo $room['availableBeds']; ?></td>
                                <td><?php echo ucfirst($room['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No rooms found.</p>
                <?php endif; ?>
            </div>

            <!-- Pending Approvals Report -->
            <div class="report-section">
                <h2>Pending Approvals</h2>
                <?php if(count($pendingStudents) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Registration Number</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Gender</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['regNumber']; ?></td>
                                <td><?php echo $student['program']; ?></td>
                                <td>
                                    <?php 
                                    $year = $student['year'];
                                    if($year == 1) echo '1st Year';
                                    elseif($year == 2) echo '2nd Year';
                                    elseif($year == 3) echo '3rd Year';
                                    elseif($year == 4) echo '4th Year';
                                    else echo 'N/A';
                                    ?>
                                </td>
                                <td><?php echo $student['gender']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending approvals.</p>
                <?php endif; ?>
            </div>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>