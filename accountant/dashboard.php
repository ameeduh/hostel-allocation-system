<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'accounts') {
    header("Location: ../index.php");
    exit();
}

// Autoload classes
spl_autoload_register(function($class_name) {
    include '../classes/' . $class_name . '.php';
});

$accountant = new Accountant();
$accountant->login($_SESSION['username'], 'password123');

// Handle approve/reject actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['approve'])) {
        $studentID = $_POST['studentID'];
        $accountant->verifyFees($studentID);
        header("Location: dashboard.php?approved=1");
        exit();
    }
    if(isset($_POST['reject'])) {
        $studentID = $_POST['studentID'];
        $accountant->rejectStudent($studentID, 'Rejected by accountant');
        header("Location: dashboard.php?rejected=1");
        exit();
    }
}

$showPending = isset($_GET['show_pending']);
$pendingStudents = $accountant->viewPendingStudents();

$approved = isset($_GET['approved']);
$rejected = isset($_GET['rejected']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Portal - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2>💰 Hostel</h2>
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
                    <h1>Welcome back, Accountant</h1>
                    <p>Accountant Portal</p>
                </div>
                <div class="header-actions">
                    <div class="notification-bell">🔔</div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if($approved): ?>
                <div class="success-message">✓ Student approved successfully!</div>
            <?php endif; ?>
            <?php if($rejected): ?>
                <div class="success-message">✗ Student rejected successfully!</div>
            <?php endif; ?>

            <!-- Main Action Card -->
            <div class="actions-grid">
                <div class="action-card" onclick="window.location.href='?show_pending=1'">
                    <div class="action-icon">⏳</div>
                    <h3>View Pending Students</h3>
                    <p>View students who have submitted registration</p>
                    <span class="action-btn">View Pending →</span>
                </div>
            </div>

            <!-- Dynamic Content Area - Pending Students Table -->
            <?php if($showPending): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h2>⏳ Pending Students (Awaiting Fee Verification)</h2>
                        <button class="close-btn" onclick="window.location.href='dashboard.php'">✕</button>
                    </div>
                    
                    <?php if(count($pendingStudents) > 0): ?>
                        <div class="students-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Reg Number</th>
                                        <th>Student Name</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>Gender</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pendingStudents as $student): ?>
                                    <tr>
                                        <td><?php echo $student['regNumber']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td><?php echo $student['program']; ?></td>
                                        <td><?php echo $student['year']; ?></td>
                                        <td><?php echo $student['gender']; ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                                <button type="submit" name="approve" class="btn-approve" onclick="return confirm('Approve this student?')">Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="studentID" value="<?php echo $student['studentID']; ?>">
                                                <button type="submit" name="reject" class="btn-reject" onclick="return confirm('Reject this student?')">Reject</button>
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
                            <h3>No Pending Students</h3>
                            <p>All students have been processed.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>