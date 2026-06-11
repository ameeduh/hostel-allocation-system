<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'warden') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/mail_config.php';

$db = new Database();
$wardenName = $_SESSION['name'];

$message = '';
$messageType = '';
$showList = isset($_GET['view']) && $_GET['view'] == 'list';
$departmentFilter = isset($_GET['dept']) ? $_GET['dept'] : 'all';

// Function to build department filter
function getDepartmentFilter($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND regNumber LIKE '%BscBA%'";
    }
    return "";
}

$departmentWhere = getDepartmentFilter($departmentFilter);

// Handle add to blacklist
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_blacklist'])) {
    $regNumber = $db->escape($_POST['regNumber']);
    $studentName = $db->escape($_POST['studentName']);
    $reason = $db->escape($_POST['reason']);
    
    // Handle image upload
    $evidenceImage = '';
    if(isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['evidence_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $newFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $regNumber) . '.' . $ext;
            $uploadPath = '../uploads/blacklist/' . $newFilename;
            
            if(!is_dir('../uploads/blacklist')) {
                mkdir('../uploads/blacklist', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['evidence_image']['tmp_name'], $uploadPath)) {
                $evidenceImage = $newFilename;
            } else {
                $message = "Failed to upload image.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
            $messageType = "error";
        }
    }
    
    // Check if student already exists
    $checkSql = "SELECT * FROM blacklist WHERE regNumber = '$regNumber'";
    $checkResult = $db->query($checkSql);
    
    if($checkResult && $checkResult->num_rows > 0) {
        // Update existing
        $updateSql = "UPDATE blacklist SET 
                      studentName = '$studentName',
                      reason = '$reason', 
                      evidence_image = '$evidenceImage',
                      dateAdded = CURDATE(), 
                      addedBy = '$wardenName', 
                      status = 'active' 
                      WHERE regNumber = '$regNumber'";
        if($db->query($updateSql)) {
            $message = "Student blacklist entry updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to update blacklist entry.";
            $messageType = "error";
        }
    } else {
        // Insert new
        $insertSql = "INSERT INTO blacklist (regNumber, studentName, reason, evidence_image, dateAdded, addedBy, status) 
                      VALUES ('$regNumber', '$studentName', '$reason', '$evidenceImage', CURDATE(), '$wardenName', 'active')";
        if($db->query($insertSql)) {
            $message = "Student added to blacklist successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add student to blacklist.";
            $messageType = "error";
        }
    }
    
    // Update student application status
    $updateSql = "UPDATE students SET applicationStatus = 'rejected', registrar_status = 'rejected', registrar_reason = '$reason' WHERE regNumber = '$regNumber'";
    $db->query($updateSql);
    
    // Get student email and send notification
    $studentSql = "SELECT u.email, u.name FROM students s JOIN users u ON s.userID = u.userID WHERE s.regNumber = '$regNumber'";
    $studentResult = $db->query($studentSql);
    if($studentResult && $studentResult->num_rows > 0) {
        $student = $studentResult->fetch_assoc();
        if($student && $student['email']) {
            $subject = "Hostel Application Status - Important Notification";
            $body = "<html><body>
                     <h2>Hostel Allocation System</h2>
                     <p>Dear " . $student['name'] . ",</p>
                     <p>Your hostel application has been <strong style='color:#c62828;'>REJECTED</strong> by the Warden's office.</p>
                     <p><strong>Reason:</strong> " . $reason . "</p>
                     <p>Please contact the Warden's office for further clarification.</p>
                     </body></html>";
            sendEmail($student['email'], $subject, $body);
        }
    }
    
    // Notify Registrar
    $registrarSql = "SELECT email FROM users WHERE role = 'registrar' LIMIT 1";
    $registrarResult = $db->query($registrarSql);
    if($registrarResult) {
        $registrar = $registrarResult->fetch_assoc();
        if($registrar && $registrar['email']) {
            $subject = "New Blacklist Entry - Notification";
            $body = "<html><body>
                     <h2>Hostel Allocation System - Warden Notification</h2>
                     <p>The Warden has added/updated a student on the blacklist.</p>
                     <p><strong>Student Name:</strong> $studentName</p>
                     <p><strong>Registration Number:</strong> $regNumber</p>
                     <p><strong>Reason:</strong> $reason</p>
                     <p>Please log in to view details.</p>
                     </body></html>";
            sendEmail($registrar['email'], $subject, $body);
        }
    }
}

// Handle remove from blacklist
if(isset($_GET['remove'])) {
    $blacklistID = (int)$_GET['remove'];
    $deleteSql = "DELETE FROM blacklist WHERE blacklistID = $blacklistID";
    if($db->query($deleteSql)) {
        $message = "Student removed from blacklist.";
        $messageType = "success";
    }
    header("Location: blacklist.php?view=list&dept=" . $departmentFilter);
    exit();
}

// Get blacklisted students
$blacklistSql = "SELECT * FROM blacklist WHERE status = 'active' $departmentWhere ORDER BY dateAdded DESC";
$blacklistResult = $db->query($blacklistSql);
$blacklistStudents = array();
if($blacklistResult) {
    while($row = $blacklistResult->fetch_assoc()) {
        $blacklistStudents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blacklist Management - Warden Portal</title>
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
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;margin-bottom:20px;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .sub-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;flex-wrap:wrap;}
        .sub-tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;}
        .sub-tab.active{background:#8B4513;color:white;}
        
        .filter-dropdown{margin-bottom:20px;display:flex;align-items:center;gap:15px;flex-wrap:wrap;}
        .filter-dropdown label{font-weight:600;color:#8B4513;font-size:14px;}
        .filter-dropdown select{padding:8px 15px;border:1px solid #ddd;border-radius:5px;font-size:13px;background:white;}
        
        .form-card{background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:30px;border:1px solid #e0e0e0;}
        .form-row{display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        .form-group input[type="file"]{padding:5px;}
        
        .btn-add{background-color:#2e7d32;color:white;border:none;padding:10px 25px;border-radius:5px;cursor:pointer;font-size:14px;font-weight:600;}
        .btn-add:hover{background-color:#1b5e20;}
        .btn-remove{background-color:#dc3545;color:white;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block;}
        .btn-view{background-color:#17a2b8;color:white;border:none;padding:3px 8px;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block;font-size:11px;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;}
        
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;margin-left:20px;margin-right:20px;}
            .nav-bar{flex-direction:column;gap:10px;}
            .form-row{flex-direction:column;}
            .data-table{overflow-x:auto;display:block;}
            .sub-tabs{flex-direction:column;}
            .filter-dropdown{flex-direction:column;align-items:flex-start;}
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
        <a href="dashboard.php?page=allocated">Allocated Students</a>
        <a href="dashboard.php?page=reports">Reports</a>
        <a href="dashboard.php?page=clearance">Clearance Requests</a>
        <a href="blacklist.php" class="active">Blacklist</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $wardenName; ?>!</h1>
    <p>Blacklist Management - Add students with evidence</p>
</div>
<div class="content-area">
    <?php if($message): ?>
        <div class="<?php echo $messageType; ?>-message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div class="sub-tabs">
        <a href="blacklist.php" class="sub-tab <?php echo (!$showList) ? 'active' : ''; ?>">Add to Blacklist</a>
        <a href="blacklist.php?view=list" class="sub-tab <?php echo ($showList) ? 'active' : ''; ?>">Blacklisted Students</a>
    </div>
    
    <?php if(!$showList): ?>
        <div class="content-card">
            <h2>Add Student to Blacklist</h2>
            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="regNumber" required placeholder="e.g., BscICT/24/001">
                        </div>
                        <div class="form-group">
                            <label>Student Name</label>
                            <input type="text" name="studentName" required placeholder="Full name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason for Blacklist</label>
                        <textarea name="reason" rows="3" required placeholder="Describe the offense/reason for blacklisting"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Evidence Image (Photo evidence)</label>
                        <input type="file" name="evidence_image" accept="image/*">
                        <small style="color:#666; display:block; margin-top:5px;">Upload photo evidence (JPG, PNG, GIF, WEBP). Max 5MB.</small>
                    </div>
                    <button type="submit" name="add_blacklist" class="btn-add">Add to Blacklist</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if($showList): ?>
        <div class="content-card">
            <h2>Blacklisted Students</h2>
            
            <div class="filter-dropdown">
                <label>Filter by Department:</label>
                <select id="deptFilter" onchange="window.location.href='blacklist.php?view=list&dept='+this.value">
                    <option value="all" <?php echo ($departmentFilter == 'all') ? 'selected' : ''; ?>>All Departments</option>
                    <option value="ict" <?php echo ($departmentFilter == 'ict') ? 'selected' : ''; ?>>ICT</option>
                    <option value="nursing" <?php echo ($departmentFilter == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
                    <option value="business" <?php echo ($departmentFilter == 'business') ? 'selected' : ''; ?>>Business Administration</option>
                </select>
            </div>
            
            <?php if(count($blacklistStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reg Number</th>
                                <th>Student Name</th>
                                <th>Reason</th>
                                <th>Evidence</th>
                                <th>Date Added</th>
                                <th>Added By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($blacklistStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                    <td style="max-width:300px;"><?php echo htmlspecialchars($student['reason']); ?></td>
                                    <td>
                                        <?php if(isset($student['evidence_image']) && $student['evidence_image']): ?>
                                            <a href="../uploads/blacklist/<?php echo $student['evidence_image']; ?>" target="_blank" class="btn-view">View Evidence</a>
                                        <?php else: ?>
                                            <span style="color:#999;">No evidence</span>
                                        <?php endif; ?>
                                     </td>
                                    <td><?php echo htmlspecialchars($student['dateAdded']); ?></td>
                                    <td><?php echo htmlspecialchars($student['addedBy']); ?></td>
                                    <td><a href="blacklist.php?remove=<?php echo $student['blacklistID']; ?>&view=list&dept=<?php echo $departmentFilter; ?>" class="btn-remove" onclick="return confirm('Remove this student from blacklist?')">Remove</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No students in blacklist.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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