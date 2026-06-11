<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'accounts') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$message = '';
$messageType = '';

// Handle add single student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $regNumber = $db->escape($_POST['regNumber']);
    $studentName = $db->escape($_POST['studentName']);
    $semester = $db->escape($_POST['semester']);
    $academic_year = $db->escape($_POST['academic_year']);
    
    // Check if already exists
    $checkSql = "SELECT id FROM paid_students WHERE regNumber = '$regNumber' AND status = 'active'";
    $checkResult = $db->query($checkSql);
    
    if($checkResult && $checkResult->num_rows > 0) {
        $message = "Student already exists in paid list!";
        $messageType = "error";
    } else {
        $insertSql = "INSERT INTO paid_students (regNumber, studentName, semester, academic_year, uploaded_by, uploaded_date, status) 
                      VALUES ('$regNumber', '$studentName', '$semester', '$academic_year', {$_SESSION['user_id']}, CURDATE(), 'active')";
        
        if($db->query($insertSql)) {
            $message = "Student added to paid list successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add student.";
            $messageType = "error";
        }
    }
}

$currentSemester = date('n') <= 6 ? 'Semester 1' : 'Semester 2';
$currentYear = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Accountant Portal</title>
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
        .welcome-section{background-color:white;margin:15px 40px;padding:15px 25px;border-radius:8px;border:1px solid #e0e0e0;}
        .welcome-section h1{font-size:20px;color:#333;margin-bottom:5px;}
        .content-area{margin:15px 40px;min-height:250px;}
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .form-card{background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:20px;border:1px solid #e0e0e0;}
        .form-row{display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group select{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        
        .btn-add{background-color:#2e7d32;color:white;border:none;padding:10px 25px;border-radius:5px;cursor:pointer;font-size:14px;font-weight:600;}
        .btn-add:hover{background-color:#1b5e20;}
        
        .success-message{background-color:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        .error-message{background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;}
            .nav-bar{flex-direction:column;}
            .form-row{flex-direction:column;}
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
        <a href="dashboard.php?page=pending">Pending Students</a>
        <a href="upload_students.php">Upload Paid Students</a>
        <a href="add_student.php" class="active">Add Student</a>
        <a href="paid_list.php">Paid Students List</a>
        <a href="dashboard.php?page=verified">Verified Students</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
    <p>Manually add a student to the paid list</p>
</div>
<div class="content-area">
    <div class="content-card">
        <h2>Add Single Student</h2>
        
        <?php if($message): ?>
            <div class="<?php echo $messageType; ?>-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST">
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="Semester 1" <?php echo ($currentSemester == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                            <option value="Semester 2" <?php echo ($currentSemester == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="academic_year" value="<?php echo $currentYear . '/' . ($currentYear + 1); ?>" required>
                    </div>
                </div>
                <button type="submit" name="add_student" class="btn-add">Add to Paid List</button>
            </form>
        </div>
    </div>
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