<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

$sql = "SELECT 
            s.studentID, 
            s.allocatedDate, 
            s.program, 
            s.year,
            u.name as studentName,
            r.roomNumber, 
            r.hostelName
        FROM students s 
        JOIN users u ON s.userID = u.userID
        LEFT JOIN rooms r ON s.allocatedRoomID = r.roomID
        WHERE s.applicationStatus = 'allocated' 
        AND s.allocationStatus = 'active'
        AND s.gender = 'Male'
        ORDER BY u.name";

$result = $db->query($sql);
$allocatedStudents = $result->fetch_all(MYSQLI_ASSOC);
$totalCount = count($allocatedStudents);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Male Allocated Students - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=16">
    <style>
        .back-to-gender {
            margin-bottom: 20px;
        }
        .back-gender-btn {
            display: inline-block;
            background-color: #8B4513;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #8B4513;
            color: white;
        }
        .vacate-btn {
            background-color: #000000;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .vacate-btn:hover {
            background-color: #333333;
        }
        .count-badge {
            background-color: #000000;
            color: white;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <div class="back-to-gender">
                <a href="allocated.php" class="back-gender-btn">← Back to Gender Selection</a>
            </div>
            
            <h1>Male Students <span class="count-badge"><?php echo $totalCount; ?></span></h1>

            <?php if($totalCount > 0): ?>
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
                            <td><?php echo $student['roomNumber'] ?: 'N/A'; ?></td>
                            <td><?php echo $student['hostelName'] ?: 'N/A'; ?></td>
                            <td><?php echo $student['allocatedDate'] ?: 'N/A'; ?></td>
                            <td>
                                <form method="POST" action="vacate_student.php">
                                    <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                    <input type="hidden" name="gender" value="male">
                                    <input type="hidden" name="return_page" value="allocated_male.php">
                                    <button type="submit" name="vacate" class="vacate-btn" onclick="return confirm('Vacate room?')">Vacate</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No male students allocated yet.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>