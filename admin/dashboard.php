<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Autoload classes
spl_autoload_register(function($class_name) {
    include '../classes/' . $class_name . '.php';
});

$admin = new Admin();
$admin->login($_SESSION['username'], 'password123');

$users = $admin->viewAllUsers();
$rooms = $admin->viewAllRooms();
$students = $admin->viewAllStudentsReport();
$dashboard = $admin->viewDashboard();

// Handle add room
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $data = [
        'roomNumber' => $_POST['roomNumber'],
        'hostelName' => $_POST['hostelName'],
        'gender' => $_POST['gender'],
        'capacity' => $_POST['capacity']
    ];
    $admin->addRoom($data);
    header("Location: dashboard.php?room_added=1");
    exit();
}

// Handle delete room
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_room'])) {
    $roomID = $_POST['roomID'];
    $admin->deleteRoom($roomID);
    header("Location: dashboard.php?room_deleted=1");
    exit();
}

// Handle delete user
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $userID = $_POST['userID'];
    $admin->deleteUser($userID);
    header("Location: dashboard.php?user_deleted=1");
    exit();
}

$room_added = isset($_GET['room_added']);
$room_deleted = isset($_GET['room_deleted']);
$user_deleted = isset($_GET['user_deleted']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="navbar">
            <h2>👑 Admin Dashboard</h2>
            <div>
                <span>Welcome, <?php echo $_SESSION['name']; ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if($room_added): ?>
            <div class="success">✓ Room added successfully!</div>
        <?php endif; ?>
        <?php if($room_deleted): ?>
            <div class="success">✓ Room deleted successfully!</div>
        <?php endif; ?>
        <?php if($user_deleted): ?>
            <div class="success">✓ User deleted successfully!</div>
        <?php endif; ?>

        <!-- System Overview Cards -->
        <h3>📊 System Overview</h3>
        <div class="card-container">
            <div class="card">
                <h3>Total Students</h3>
                <div class="number"><?php echo $dashboard['totalStudents']; ?></div>
            </div>
            <div class="card">
                <h3>Total Rooms</h3>
                <div class="number"><?php echo $dashboard['totalRooms']; ?></div>
            </div>
            <div class="card">
                <h3>Allocated Students</h3>
                <div class="number"><?php echo $dashboard['allocatedStudents']; ?></div>
            </div>
            <div class="card">
                <h3>Pending Approvals</h3>
                <div class="number"><?php echo $dashboard['pendingApprovals']; ?></div>
            </div>
            <div class="card">
                <h3>Total Beds</h3>
                <div class="number"><?php echo $dashboard['totalBeds']; ?></div>
            </div>
            <div class="card">
                <h3>Available Beds</h3>
                <div class="number"><?php echo $dashboard['availableBeds']; ?></div>
            </div>
        </div>

        <!-- Add New Room Section -->
        <h3>🏠 Add New Room</h3>
        <div class="form-container">
            <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" name="roomNumber" placeholder="Room Number" required>
                <input type="text" name="hostelName" placeholder="Hostel Name" required>
                <select name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <input type="number" name="capacity" placeholder="Capacity" required>
                <button type="submit" name="add_room" class="btn-approve">Add Room</button>
            </form>
        </div>

        <!-- All Rooms Section -->
        <h3>🏘️ All Rooms</h3>
        <?php if(count($rooms) > 0): ?>
            <table>
                <tr>
                    <th>Room Number</th>
                    <th>Hostel Name</th>
                    <th>Gender</th>
                    <th>Capacity</th>
                    <th>Available Beds</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach($rooms as $room): ?>
                <tr>
                    <td><?php echo $room['roomNumber']; ?></td>
                    <td><?php echo $room['hostelName']; ?></td>
                    <td><?php echo $room['gender']; ?></td>
                    <td><?php echo $room['capacity']; ?></td>
                    <td><?php echo $room['availableBeds']; ?></td>
                    <td><?php echo $room['status']; ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="roomID" value="<?php echo $room['roomID']; ?>">
                            <button type="submit" name="delete_room" class="btn-reject" onclick="return confirm('Delete this room?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="error">📭 No rooms found.</div>
        <?php endif; ?>

        <!-- All Users Section -->
        <h3>👥 All Users</h3>
        <?php if(count($users) > 0): ?>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['phone']; ?></td>
                    <td><?php echo $user['role']; ?></td>
                    <td>
                        <?php if($user['role'] != 'admin'): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="userID" value="<?php echo $user['userID']; ?>">
                            <button type="submit" name="delete_user" class="btn-reject" onclick="return confirm('Delete this user?')">Delete</button>
                        </form>
                        <?php else: ?>
                        <span style="color: gray;">Cannot delete</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="error">📭 No users found.</div>
        <?php endif; ?>

        <!-- All Students Report Section -->
        <h3>📚 All Students Report</h3>
        <?php if(count($students) > 0): ?>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Reg Number</th>
                    <th>Program</th>
                    <th>Year</th>
                    <th>Fee %</th>
                    <th>Status</th>
                    <th>Gender</th>
                </tr>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><?php echo $student['name']; ?></td>
                    <td><?php echo $student['regNumber']; ?></td>
                    <td><?php echo $student['program']; ?></td>
                    <td><?php echo $student['year']; ?></td>
                    <td><?php echo $student['feePercentage']; ?>%</td>
                    <td><?php echo $student['applicationStatus']; ?></td>
                    <td><?php echo $student['gender']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <div class="error">📭 No students found.</div>
        <?php endif; ?>
    </div>
</body>
</html>