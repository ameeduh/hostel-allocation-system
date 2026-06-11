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
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'list';

// Get current semester
$currentSemester = date('n') <= 6 ? 'Semester 1' : 'Semester 2';
$currentYear = date('Y');
$academic_year = $currentYear . '/' . ($currentYear + 1);

// ========== HANDLE ADD SINGLE STUDENT ==========
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_single'])) {
    $regNumber = $db->escape($_POST['regNumber']);
    $studentName = $db->escape($_POST['studentName']);
    
    if(empty($regNumber) || empty($studentName)) {
        $message = "Registration number and student name are required.";
        $messageType = "error";
    } else {
        $checkSql = "SELECT id FROM paid_students WHERE regNumber = '$regNumber'";
        $checkResult = $db->query($checkSql);
        
        if($checkResult && $checkResult->num_rows > 0) {
            $updateSql = "UPDATE paid_students SET 
                          studentName = '$studentName',
                          semester = '$currentSemester',
                          academic_year = '$academic_year',
                          uploaded_by = {$_SESSION['user_id']},
                          uploaded_date = CURDATE(),
                          status = 'active'
                          WHERE regNumber = '$regNumber'";
            if($db->query($updateSql)) {
                $message = "Student updated successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to update student.";
                $messageType = "error";
            }
        } else {
            $insertSql = "INSERT INTO paid_students (regNumber, studentName, semester, academic_year, uploaded_by, uploaded_date, status) 
                          VALUES ('$regNumber', '$studentName', '$currentSemester', '$academic_year', {$_SESSION['user_id']}, CURDATE(), 'active')";
            if($db->query($insertSql)) {
                $message = "Student added successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to add student.";
                $messageType = "error";
            }
        }
    }
}

// ========== HANDLE TEXTAREA PASTE (from PDF) ==========
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_paste'])) {
    $pastedText = $_POST['pasted_text'];
    $lines = explode("\n", $pastedText);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach($lines as $line) {
        $line = trim($line);
        if(empty($line)) continue;
        
        if(strpos($line, ',') !== false) {
            list($regNumber, $studentName) = explode(',', $line, 2);
            $regNumber = trim($regNumber);
            $studentName = trim($studentName);
        } elseif(strpos($line, ' ') !== false) {
            $parts = explode(' ', $line, 2);
            $regNumber = trim($parts[0]);
            $studentName = trim($parts[1]);
        } else {
            $regNumber = trim($line);
            $studentName = '';
        }
        
        if(empty($regNumber)) {
            $errorCount++;
            continue;
        }
        
        if(empty($studentName)) {
            $studentName = $regNumber;
        }
        
        $checkSql = "SELECT id FROM paid_students WHERE regNumber = '$regNumber'";
        $checkResult = $db->query($checkSql);
        
        if($checkResult && $checkResult->num_rows > 0) {
            $updateSql = "UPDATE paid_students SET 
                          studentName = '$studentName',
                          semester = '$currentSemester',
                          academic_year = '$academic_year',
                          uploaded_by = {$_SESSION['user_id']},
                          uploaded_date = CURDATE(),
                          status = 'active'
                          WHERE regNumber = '$regNumber'";
            $db->query($updateSql);
        } else {
            $insertSql = "INSERT INTO paid_students (regNumber, studentName, semester, academic_year, uploaded_by, uploaded_date, status) 
                          VALUES ('$regNumber', '$studentName', '$currentSemester', '$academic_year', {$_SESSION['user_id']}, CURDATE(), 'active')";
            $db->query($insertSql);
        }
        $successCount++;
    }
    
    $message = "Processed $successCount students. $errorCount errors.";
    $messageType = "success";
}

// ========== HANDLE DELETE ==========
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $deleteSql = "DELETE FROM paid_students WHERE id = $id";
    if($db->query($deleteSql)) {
        $message = "Student removed from paid list.";
        $messageType = "success";
    }
    header("Location: paid_list.php?tab=list");
    exit();
}

// ========== GET PROGRAM FILTER ==========
$programFilter = isset($_GET['program']) ? $_GET['program'] : 'all';

// Build WHERE clause for program filter
$where = "WHERE status = 'active'";
if($programFilter == 'ict') {
    $where .= " AND regNumber LIKE '%BscICT%'";
} elseif($programFilter == 'nursing') {
    $where .= " AND regNumber LIKE '%BscNM%'";
} elseif($programFilter == 'business') {
    $where .= " AND regNumber LIKE '%BscBA%'";
}

// ========== GET PAID STUDENTS LIST ==========
$sql = "SELECT * FROM paid_students $where ORDER BY uploaded_date DESC";
$result = $db->query($sql);
$students = array();
if($result) {
    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$totalCount = count($students);

// Get counts for each program
$totalICT = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND regNumber LIKE '%BscICT%'")->fetch_assoc()['total'];
$totalNursing = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND regNumber LIKE '%BscNM%'")->fetch_assoc()['total'];
$totalBusiness = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND regNumber LIKE '%BscBA%'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paid Students List - Accountant Portal</title>
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
        
        .tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;}
        .tab{padding:8px 20px;text-decoration:none;border-radius:5px;background:#f0f0f0;color:#333;}
        .tab.active{background:#8B4513;color:white;}
        
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;align-items:center;flex-wrap:wrap;}
        .filter-bar select{padding:8px 15px;border:1px solid #ddd;border-radius:5px;background:white;}
        .filter-bar button{padding:8px 15px;background:#8B4513;color:white;border:none;border-radius:5px;cursor:pointer;}
        
        .stats{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;}
        .stat-badge{padding:5px 15px;border-radius:20px;background:#f0f0f0;font-size:13px;}
        .stat-badge.active{background:#8B4513;color:white;}
        
        .form-card{background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:20px;border:1px solid #e0e0e0;}
        .form-row{display:flex;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
        .form-group{flex:1;min-width:180px;}
        .form-group label{display:block;font-size:12px;font-weight:600;color:#8B4513;margin-bottom:5px;}
        .form-group input,.form-group textarea{width:100%;padding:8px;border:1px solid #ddd;border-radius:5px;font-size:13px;}
        .form-group textarea{font-family:monospace;resize:vertical;}
        
        .btn-add{background-color:#2e7d32;color:white;border:none;padding:10px 25px;border-radius:5px;cursor:pointer;font-size:14px;font-weight:600;}
        .btn-add:hover{background-color:#1b5e20;}
        .btn-delete{background-color:#dc3545;color:white;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;font-size:11px;}
        .btn-delete:hover{background-color:#c82333;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
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
            .data-table{overflow-x:auto;display:block;}
            .tabs{flex-direction:column;}
            .form-row{flex-direction:column;}
            .filter-bar{flex-direction:column;align-items:flex-start;}
            .stats{flex-direction:column;}
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
        <a href="paid_list.php?tab=list" class="<?php echo ($activeTab == 'list') ? 'active' : ''; ?>">Paid List</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
</div>
<div class="content-area">
    <div class="content-card">
        <h2>Paid Students List</h2>
        
        <?php if($message): ?>
            <div class="<?php echo $messageType; ?>-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Tabs (No Icons) -->
        <div class="tabs">
            <a href="paid_list.php?tab=list" class="tab <?php echo ($activeTab == 'list') ? 'active' : ''; ?>">View List</a>
            <a href="paid_list.php?tab=add" class="tab <?php echo ($activeTab == 'add') ? 'active' : ''; ?>">Add Single</a>
            <a href="paid_list.php?tab=paste" class="tab <?php echo ($activeTab == 'paste') ? 'active' : ''; ?>">Paste from PDF</a>
        </div>
        
        <!-- TAB 1: VIEW LIST -->
        <?php if($activeTab == 'list'): ?>
            <!-- Program Filter Dropdown -->
            <div class="filter-bar">
                <select id="programFilter" onchange="applyProgramFilter()">
                    <option value="all" <?php echo ($programFilter == 'all') ? 'selected' : ''; ?>>All Programs (<?php echo $totalCount; ?>)</option>
                    <option value="ict" <?php echo ($programFilter == 'ict') ? 'selected' : ''; ?>>ICT (<?php echo $totalICT; ?>)</option>
                    <option value="nursing" <?php echo ($programFilter == 'nursing') ? 'selected' : ''; ?>>Nursing (<?php echo $totalNursing; ?>)</option>
                    <option value="business" <?php echo ($programFilter == 'business') ? 'selected' : ''; ?>>Business Administration (<?php echo $totalBusiness; ?>)</option>
                </select>
                <?php if($programFilter != 'all'): ?>
                    <button onclick="window.location.href='paid_list.php?tab=list'">Clear Filter</button>
                <?php endif; ?>
            </div>
            
            <?php if(count($students) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Student Name</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Date Added</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td
                                <td><?php echo $student['semester']; ?></td
                                <td><?php echo $student['academic_year']; ?></td
                                <td><?php echo $student['uploaded_date']; ?></td
                                <td>
                                    <a href="paid_list.php?delete=<?php echo $student['id']; ?>&tab=list&program=<?php echo $programFilter; ?>" class="btn-delete" onclick="return confirm('Remove this student from paid list?')">Remove</a>
                                 </td
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table
                </div>
            <?php else: ?>
                <p>No paid students found for the selected program.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- TAB 2: ADD SINGLE STUDENT -->
        <?php if($activeTab == 'add'): ?>
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
                    <button type="submit" name="add_single" class="btn-add">Add to Paid List</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- TAB 3: PASTE FROM PDF -->
        <?php if($activeTab == 'paste'): ?>
            <div class="form-card">
                <form method="POST">
                    <div class="form-group">
                        <label>Paste Student Data (from PDF)</label>
                        <textarea name="pasted_text" rows="10" required placeholder="Open your PDF, select all text (Ctrl+A), copy (Ctrl+C), then paste here (Ctrl+V).&#10;&#10;Supported formats:&#10;BscICT/24/001,John Doe&#10;BscICT/24/002 Jane Smith&#10;BscNM/22/001"></textarea>
                        <small style="color:#666; display:block; margin-top:5px;">
                            Each line should contain a registration number and student name (separated by comma or space).
                        </small>
                    </div>
                    <button type="submit" name="upload_paste" class="btn-add">Process Pasted Data</button>
                </form>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
    function applyProgramFilter() {
        var program = document.getElementById('programFilter').value;
        window.location.href = 'paid_list.php?tab=list&program=' + program;
    }
</script>

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