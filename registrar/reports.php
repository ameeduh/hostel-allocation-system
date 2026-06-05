<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get active tab and filters
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'approved';
$departmentFilter = isset($_GET['dept']) ? $_GET['dept'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Function to build department filter
function getDeptWhere($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND regNumber LIKE '%BscBA%'";
    }
    return "";
}

$deptWhere = getDeptWhere($departmentFilter);

// Build date filter
$dateWhere = "";
if($from_date && $to_date) {
    $dateWhere = " AND approvedDate BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $dateWhere = " AND approvedDate >= '$from_date'";
} elseif($to_date) {
    $dateWhere = " AND approvedDate <= '$to_date'";
}

// Build date filter for blacklist
$blacklistDateWhere = "";
if($from_date && $to_date) {
    $blacklistDateWhere = " AND dateAdded BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $blacklistDateWhere = " AND dateAdded >= '$from_date'";
} elseif($to_date) {
    $blacklistDateWhere = " AND dateAdded <= '$to_date'";
}

// Get approved fee commitment students
$approvedSql = "SELECT fc.*, s.program, s.year, s.gender, s.regNumber 
                FROM fee_commitment_requests fc
                JOIN students s ON fc.studentID = s.studentID
                WHERE fc.status = 'approved' $deptWhere $dateWhere
                ORDER BY fc.approvedDate DESC";
$approvedResult = $db->query($approvedSql);
$approvedStudents = array();
if($approvedResult) {
    while($row = $approvedResult->fetch_assoc()) {
        $expiryDate = $row['approvedDate'];
        if($expiryDate) {
            $expiry = strtotime($expiryDate . ' +4 weeks');
            $today = time();
            $daysRemaining = round(($expiry - $today) / (60 * 60 * 24));
            $row['expiry_date'] = date('Y-m-d', $expiry);
            $row['days_remaining'] = $daysRemaining;
        } else {
            $row['expiry_date'] = 'N/A';
            $row['days_remaining'] = 'N/A';
        }
        $approvedStudents[] = $row;
    }
}

// Get blacklisted students
$blacklistSql = "SELECT * FROM blacklist WHERE status = 'active' $deptWhere $blacklistDateWhere ORDER BY dateAdded DESC";
$blacklistResult = $db->query($blacklistSql);
$blacklistedStudents = array();
if($blacklistResult) {
    while($row = $blacklistResult->fetch_assoc()) {
        $blacklistedStudents[] = $row;
    }
}

// Count for badges
$approvedCount = count($approvedStudents);
$blacklistCount = count($blacklistedStudents);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Registrar Portal</title>
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
        .content-card{background:white;border-radius:8px;padding:20px;border:1px solid #e0e0e0;}
        .content-card h2{color:#8B4513;font-size:18px;margin-bottom:15px;border-bottom:2px solid #FFD700;display:inline-block;padding-bottom:5px;}
        
        .report-tabs{display:flex;gap:5px;margin-bottom:20px;border-bottom:2px solid #8B4513;flex-wrap:wrap;}
        .report-tab{padding:10px 20px;background:none;border:none;cursor:pointer;font-size:14px;color:#666;transition:all 0.3s;}
        .report-tab:hover{color:#8B4513;}
        .report-tab.active{color:#8B4513;border-bottom:2px solid #FFD700;font-weight:600;}
        
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;align-items:center;padding:10px 0;border-bottom:1px solid #eee;}
        .filter-group{display:flex;align-items:center;gap:8px;}
        .filter-group label{font-weight:600;color:#8B4513;font-size:13px;}
        .filter-group select,.filter-group input{padding:6px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;}
        .filter-group select:focus,.filter-group input:focus{border-color:#8B4513;outline:none;}
        .btn-filter{background-color:#8B4513;color:white;border:none;padding:6px 15px;border-radius:4px;cursor:pointer;}
        .btn-filter:hover{background-color:#6d3710;}
        
        .export-buttons{display:flex;gap:10px;justify-content:flex-end;margin-bottom:20px;}
        .btn-export{background-color:#8B4513;color:white;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block;}
        .btn-export:hover{background-color:#6d3710;}
        
        .data-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .data-table th{background-color:#8B4513;color:white;padding:12px;text-align:left;font-size:13px;}
        .data-table td{padding:10px;border-bottom:1px solid #eee;font-size:13px;}
        .data-table tr:hover{background-color:#f9f9f9;}
        
        .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:500;}
        .badge-good{background-color:#d4edda;color:#155724;}
        .badge-warning{background-color:#fff3cd;color:#856404;}
        .badge-danger{background-color:#f8d7da;color:#721c24;}
        
        .footer{background-color:#8B4513;color:white;padding:25px 40px;margin-top:20px;}
        .footer-content{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;}
        .footer-section h3{font-size:15px;margin-bottom:10px;color:#FFD700;}
        .footer-section p,.footer-section a{font-size:12px;color:#f0f0f0;text-decoration:none;line-height:1.6;}
        .footer-section a:hover{color:#FFD700;}
        .copyright{text-align:center;padding-top:20px;margin-top:20px;border-top:1px solid rgba(255,255,255,0.2);font-size:11px;}
        
        @media (max-width:768px){
            .top-bar,.nav-bar,.welcome-section,.content-area{padding-left:20px;padding-right:20px;margin-left:20px;margin-right:20px;}
            .nav-bar{flex-direction:column;gap:10px;}
            .filter-bar{flex-direction:column;align-items:flex-start;}
            .data-table{overflow-x:auto;display:block;}
            .export-buttons{justify-content:flex-start;}
            .report-tabs{flex-direction:column;}
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
        <a href="blacklist.php">Blacklist</a>
        <a href="fee_requests.php">Fee Requests</a>
        <a href="dashboard.php?page=approved">Approved Students</a>
        <a href="reports.php" class="active">Reports</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
    <p>Reports - Fee Commitment and Blacklisted Students</p>
</div>
<div class="content-area">
    <div class="content-card">
        <h2>Reports</h2>
        
        <!-- Report Tabs -->
        <div class="report-tabs">
            <a href="?tab=approved" class="report-tab <?php echo ($activeTab == 'approved') ? 'active' : ''; ?>">
                Approved Fee Commitment <span style="background:#8B4513; color:white; padding:2px 8px; border-radius:12px; margin-left:5px;"><?php echo $approvedCount; ?></span>
            </a>
            <a href="?tab=blacklist" class="report-tab <?php echo ($activeTab == 'blacklist') ? 'active' : ''; ?>">
                Blacklisted Students <span style="background:#8B4513; color:white; padding:2px 8px; border-radius:12px; margin-left:5px;"><?php echo $blacklistCount; ?></span>
            </a>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Department:</label>
                <select id="deptFilter">
                    <option value="all" <?php echo ($departmentFilter == 'all') ? 'selected' : ''; ?>>All Departments</option>
                    <option value="ict" <?php echo ($departmentFilter == 'ict') ? 'selected' : ''; ?>>ICT</option>
                    <option value="nursing" <?php echo ($departmentFilter == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
                    <option value="business" <?php echo ($departmentFilter == 'business') ? 'selected' : ''; ?>>Business Administration</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date:</label>
                <input type="date" id="fromDate" value="<?php echo $from_date; ?>">
            </div>
            <div class="filter-group">
                <label>To Date:</label>
                <input type="date" id="toDate" value="<?php echo $to_date; ?>">
            </div>
            <button class="btn-filter" onclick="applyFilter()">Apply Filter</button>
            <button class="btn-filter" onclick="clearFilter()">Clear Filter</button>
        </div>
        
        <!-- Export Buttons - Only Excel now, PDF via Print -->
        <div class="export-buttons">
            <button onclick="window.print()" class="btn-export">Print / Save as PDF</button>
            <a href="export_csv.php?type=<?php echo $activeTab; ?>&dept=<?php echo $departmentFilter; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn-export">Export Excel</a>
        </div>
        
        <!-- Approved Fee Commitment Report -->
        <?php if($activeTab == 'approved'): ?>
            <h3 style="margin-bottom:15px; color:#8B4513;">Approved Fee Commitment Students</h3>
            <?php if(count($approvedStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Reg Number</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Fee Note</th>
                                <th>Date Approved</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($approvedStudents as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['program']); ?></td>
                                <td><?php echo $student['year']; ?> Year</td>
                                <td><?php echo $student['gender']; ?></td>
                                <td style="max-width:200px;"><?php echo htmlspecialchars($student['reason']); ?></td>
                                <td><?php echo $student['approvedDate']; ?></td>
                                <td><?php echo $student['expiry_date']; ?></td>
                                <td>
                                    <?php 
                                    $days = $student['days_remaining'];
                                    if($days > 7) {
                                        echo '<span class="badge badge-good">' . $days . ' days left</span>';
                                    } elseif($days > 0 && $days <= 7) {
                                        echo '<span class="badge badge-warning">' . $days . ' days left</span>';
                                    } else {
                                        echo '<span class="badge badge-danger">Expired</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved fee commitment students found for the selected criteria.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Blacklisted Students Report -->
        <?php if($activeTab == 'blacklist'): ?>
            <h3 style="margin-bottom:15px; color:#8B4513;">Blacklisted Students</h3>
            <?php if(count($blacklistedStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Student Name</th>
                                <th>Reason</th>
                                <th>Date Added</th>
                                <th>Added By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($blacklistedStudents as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                <td style="max-width:300px;"><?php echo htmlspecialchars($student['reason']); ?></td>
                                <td><?php echo $student['dateAdded']; ?></td>
                                <td><?php echo $student['addedBy']; ?></td>
                                <td><span class="badge badge-danger">Active</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No blacklisted students found for the selected criteria.</p>
            <?php endif; ?>
        <?php endif; ?>
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

<script>
    function applyFilter() {
        var dept = document.getElementById('deptFilter').value;
        var fromDate = document.getElementById('fromDate').value;
        var toDate = document.getElementById('toDate').value;
        var tab = '<?php echo $activeTab; ?>';
        window.location.href = 'reports.php?tab=' + tab + '&dept=' + dept + '&from_date=' + fromDate + '&to_date=' + toDate;
    }
    
    function clearFilter() {
        var tab = '<?php echo $activeTab; ?>';
        window.location.href = 'reports.php?tab=' + tab;
    }
</script>
</body>
</html>