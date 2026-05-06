<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../index.php");
    exit();
}

// Autoload classes
spl_autoload_register(function($class_name) {
    include '../classes/' . $class_name . '.php';
});

$warden = new Warden();
$warden->login($_SESSION['username'], 'password123');

// Handle allocation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $studentID = $_POST['studentID'];
    $roomID = $_POST['roomID'];
    if($warden->allocateRoom($studentID, $roomID)) {
        header("Location: dashboard.php?allocated=1");
    } else {
        header("Location: dashboard.php?error=1");
    }
    exit();
}

// Handle vacate
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vacate'])) {
    $studentID = $_POST['studentID'];
    if($warden->vacateRoom($studentID)) {
        header("Location: dashboard.php?vacated=1");
    } else {
        header("Location: dashboard.php?error=1");
    }
    exit();
}

$showApproved = isset($_GET['show_approved']);
$showAllocate = isset($_GET['show_allocate']);
$showAllocated = isset($_GET['show_allocated']);

$approvedStudents = $warden->viewApprovedStudents();
$availableRooms = $warden->viewAvailableRooms();
$allocatedStudents = $warden->viewAllocatedStudents();

$allocated = isset($_GET['allocated']);
$vacated = isset($_GET['vacated']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Portal - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>🔑 Hostel</h2>
                <p>Allocation System</p>
            </div>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Welcome back, Warden</h1>
                    <p>Warden Portal</p>
                </div>
                <div class="header-actions">
                    <div class="notification-bell">🔔</div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if($allocated): ?>
                <div class="success-message">✓ Room allocated successfully!</div>
            <?php endif; ?>
            <?php if($vacated): ?>
                <div class="success-message">✓ Room vacated successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">✗ Allocation failed. Gender mismatch or room not available.</div>
            <?php endif; ?>

            <!-- Main Action Cards -->
            <div class="actions-grid">
                <div class="action-card" onclick="window.location.href='?show_approved=1'">
                    <div class="action-icon">✅</div>
                    <h3>Approved Students</h3>
                    <p>View students ready for allocation</p>
                    <span class="action-btn">View →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='?show_allocate=1'">
                    <div class="action-icon">🏠</div>
                    <h3>Allocate Room</h3>
                    <p>Assign a room to a student</p>
                    <span class="action-btn">Allocate →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='?show_allocated=1'">
                    <div class="action-icon">📋</div>
                    <h3>Allocated Students</h3>
                    <p>View students with rooms</p>
                    <span class="action-btn">View →</span>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <?php if($showApproved): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>✅ Approved Students (Ready for Allocation)</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    
                    <?php if(count($approvedStudents) > 0): ?>
                        <div class="students-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Reg Number</th>
                                        <th>Student Name</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>Gender</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($approvedStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['regNumber']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year']; ?></td>
                                        <td><?php echo $student['gender']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>No Approved Students</h3>
                            <p>No students are ready for allocation yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($showAllocate): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>🏠 Allocate Room</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    
                    <?php if(count($approvedStudents) > 0): ?>
                        <div class="allocate-form">
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Select Student</label>
                                        <select name="studentID" required>
                                            <option value="">Select Student</option>
                                            <?php foreach($approvedStudents as $student): ?>
                                                <option value="<?php echo $student['studentID']; ?>">
                                                    <?php echo $student['regNumber']; ?> - <?php echo $student['name']; ?> (<?php echo $student['gender']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Select Room</label>
                                        <select name="roomID" required>
                                            <option value="">Select Room</option>
                                            <?php foreach($availableRooms as $room): ?>
                                                <option value="<?php echo $room['roomID']; ?>">
                                                    <?php echo $room['roomNumber']; ?> - <?php echo $room['hostelName']; ?> (<?php echo $room['gender']; ?>) - <?php echo $room['availableBeds']; ?> beds left
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="allocate" class="submit-btn" onclick="return confirm('Allocate this room to the selected student?')">Allocate Room</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>No Approved Students</h3>
                            <p>No students are ready for allocation yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($showAllocated): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>📋 Allocated Students</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    
                    <?php if(count($allocatedStudents) > 0): ?>
                        <div class="students-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Reg Number</th>
                                        <th>Student Name</th>
                                        <th>Room Number</th>
                                        <th>Hostel Name</th>
                                        <th>Allocation Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($allocatedStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['regNumber']; ?></td>
                                        <td><?php echo $student['studentName']; ?></td>
                                        <td><?php echo $student['roomNumber']; ?></td>
                                        <td><?php echo $student['hostelName']; ?></td>
                                        <td><?php echo $student['allocatedDate']; ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block;">
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
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>No Allocated Students</h3>
                            <p>No students have been allocated rooms yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>