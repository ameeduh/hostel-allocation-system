<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../index.php");
    exit();
}

// Include database directly
require_once '../config/database.php';

$db = new Database();
$studentID = $_SESSION['studentID'];

$sql = "SELECT s.*, u.name, u.phone 
        FROM students s 
        JOIN users u ON s.userID = u.userID 
        WHERE s.studentID = $studentID";
$result = $db->query($sql);
$studentData = $result->fetch_assoc();

$status = $studentData['applicationStatus'];
$roomID = $studentData['roomID'];
$hasDetails = ($studentData['program'] != '' && $studentData['year'] > 0 && $studentData['gender'] != '');

// Handle details form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_details'])) {
    $program = $_POST['program'];
    $year = $_POST['year'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    
    $program = $db->escape($program);
    $year = (int)$year;
    $phone = $db->escape($phone);
    $gender = $db->escape($gender);
    
    $sql1 = "UPDATE students SET program = '$program', year = $year, gender = '$gender' WHERE studentID = $studentID";
    $db->query($sql1);
    
    $sql2 = "UPDATE users SET phone = '$phone' WHERE userID = {$_SESSION['user_id']}";
    $db->query($sql2);
    
    header("Location: dashboard.php?details_saved=1");
    exit();
}

$showDetails = isset($_GET['show_details']);
$showApproval = isset($_GET['show_approval']);
$showRoom = isset($_GET['show_room']);
$detailsSaved = isset($_GET['details_saved']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>🏠 Hostel</h2>
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
            <div class="top-header">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo $studentData['name']; ?></h1>
                    <p>Registration Number: <?php echo $_SESSION['username']; ?></p>
                </div>
                <div class="header-actions">
                    <div class="notification-bell">🔔</div>
                </div>
            </div>

            <?php if($detailsSaved): ?>
                <div class="success-message">✓ Details saved successfully!</div>
            <?php endif; ?>

            <!-- Action Cards -->
            <div class="actions-grid">
                <?php if(!$hasDetails): ?>
                <div class="action-card" onclick="window.location.href='?show_details=1'">
                    <div class="action-icon">📝</div>
                    <h3>Complete Your Details</h3>
                    <p>Fill in your program, year, phone and gender</p>
                    <span class="action-btn">Fill Details →</span>
                </div>
                <?php endif; ?>
                
                <div class="action-card" onclick="window.location.href='?show_approval=1'">
                    <div class="action-icon">✅</div>
                    <h3>Approval Status</h3>
                    <p>Check your fee verification status</p>
                    <span class="action-btn">Check Status →</span>
                </div>
                
                <div class="action-card" onclick="window.location.href='?show_room=1'">
                    <div class="action-icon">🏠</div>
                    <h3>Room Details</h3>
                    <p>View your allocated room information</p>
                    <span class="action-btn">View Room →</span>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <?php if($showDetails): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>📝 Complete Your Registration Details</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Program *</label>
                                <select name="program" required>
                                    <option value="">Select Program</option>
                                    <option value="ICT">ICT (BscICT)</option>
                                    <option value="Nursing">Nursing (BscNM)</option>
                                    <option value="Business Administration">Business Administration (BscBA)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Year of Study *</label>
                                <select name="year" required>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" placeholder="Enter your phone number" required>
                            </div>
                            <div class="form-group">
                                <label>Gender *</label>
                                <select name="gender" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="save_details" class="submit-btn">Save Details</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($showApproval): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>✅ Approval Status</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    <?php if($status == 'pending'): ?>
                        <div class="status-box pending">
                            <div class="status-icon">⏳</div>
                            <h3>Pending Approval</h3>
                            <p>Your application has been submitted. Awaiting Accountant approval.</p>
                        </div>
                    <?php elseif($status == 'approved'): ?>
                        <div class="status-box approved">
                            <div class="status-icon">✅</div>
                            <h3>Approved!</h3>
                            <p>You have been verified! Waiting for Warden to allocate room.</p>
                        </div>
                    <?php elseif($status == 'rejected'): ?>
                        <div class="status-box rejected">
                            <div class="status-icon">❌</div>
                            <h3>Application Rejected</h3>
                            <p>Please contact Accounts office for more information.</p>
                        </div>
                    <?php elseif($status == 'allocated'): ?>
                        <div class="status-box allocated">
                            <div class="status-icon">🎉</div>
                            <h3>Room Allocated!</h3>
                            <p>Congratulations! A room has been allocated to you.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($showRoom): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>🏠 Room Allocation Details</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    <?php if($roomID && $roomID > 0): 
                        $roomSql = "SELECT * FROM rooms WHERE roomID = $roomID";
                        $roomResult = $db->query($roomSql);
                        $room = $roomResult->fetch_assoc();
                    ?>
                        <?php if($room): ?>
                            <div class="room-info-grid">
                                <div class="room-info-item">
                                    <label>Room Number</label>
                                    <p><?php echo $room['roomNumber']; ?></p>
                                </div>
                                <div class="room-info-item">
                                    <label>Hostel Name</label>
                                    <p><?php echo $room['hostelName']; ?></p>
                                </div>
                                <div class="room-info-item">
                                    <label>Gender</label>
                                    <p><?php echo $room['gender']; ?></p>
                                </div>
                                <div class="room-info-item">
                                    <label>Allocation Date</label>
                                    <p><?php echo date('d-m-Y'); ?></p>
                                </div>
                            </div>
                            <a href="request_clearance.php" class="clearance-btn" onclick="return confirm('Request clearance? This will vacate your room.')">Request Clearance</a>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">🏠</div>
                                <h3>No Room Allocated Yet</h3>
                                <p>Your room will appear here once the Warden allocates one.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">🏠</div>
                            <h3>No Room Allocated Yet</h3>
                            <p>Your room will appear here once the Warden allocates one.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>