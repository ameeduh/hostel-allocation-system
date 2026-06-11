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

// For Institutional Report
$reportProgram = isset($_GET['report_program']) ? $_GET['report_program'] : 'all';
$reportHostel = isset($_GET['report_hostel']) ? $_GET['report_hostel'] : 'all';
$report_from_date = isset($_GET['report_from_date']) ? $_GET['report_from_date'] : '';
$report_to_date = isset($_GET['report_to_date']) ? $_GET['report_to_date'] : '';

// Determine current semester
$currentMonth = date('n');
$currentYear = date('Y');
if($currentMonth <= 6) {
    $currentSemester = 'Semester 1';
    $academicYear = ($currentYear - 1) . '/' . $currentYear;
} else {
    $currentSemester = 'Semester 2';
    $academicYear = $currentYear . '/' . ($currentYear + 1);
}

// Function to build department filter for fee_commitment_requests
function getDeptWhere($departmentFilter) {
    if($departmentFilter == 'ict') {
        return " AND s.regNumber LIKE '%BscICT%'";
    } elseif($departmentFilter == 'nursing') {
        return " AND s.regNumber LIKE '%BscNM%'";
    } elseif($departmentFilter == 'business') {
        return " AND s.regNumber LIKE '%BscBA%'";
    }
    return "";
}

// Function to build department filter for blacklist
function getBlacklistDeptWhere($departmentFilter) {
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
$blacklistDeptWhere = getBlacklistDeptWhere($departmentFilter);

// Build date filter for fee commitments
$dateWhere = "";
if($from_date && $to_date) {
    $dateWhere = " AND fc.approvedDate BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $dateWhere = " AND fc.approvedDate >= '$from_date'";
} elseif($to_date) {
    $dateWhere = " AND fc.approvedDate <= '$to_date'";
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
$blacklistSql = "SELECT * FROM blacklist WHERE status = 'active' $blacklistDeptWhere $blacklistDateWhere ORDER BY dateAdded DESC";
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

// ========== INSTITUTIONAL REPORT DATA ==========

// Build program filter for institutional report
$programWhere = "";
if($reportProgram == 'ict') {
    $programWhere = " AND regNumber LIKE '%BscICT%'";
} elseif($reportProgram == 'nursing') {
    $programWhere = " AND regNumber LIKE '%BscNM%'";
} elseif($reportProgram == 'business') {
    $programWhere = " AND regNumber LIKE '%BscBA%'";
}

// Build hostel filter for rooms
$hostelWhere = "";
if($reportHostel != 'all') {
    $hostelWhere = " WHERE hostelName = '$reportHostel'";
}

// Build date filter for institutional report
$reportDateWhere = "";
if($report_from_date && $report_to_date) {
    $reportDateWhere = " AND allocatedDate BETWEEN '$report_from_date' AND '$report_to_date'";
} elseif($report_from_date) {
    $reportDateWhere = " AND allocatedDate >= '$report_from_date'";
} elseif($report_to_date) {
    $reportDateWhere = " AND allocatedDate <= '$report_to_date'";
}

// Student Statistics
$totalStudents = $db->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
$totalICT = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscICT%'")->fetch_assoc()['total'];
$totalNursing = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscNM%'")->fetch_assoc()['total'];
$totalBusiness = $db->query("SELECT COUNT(*) as total FROM students WHERE regNumber LIKE '%BscBA%'")->fetch_assoc()['total'];
$totalMale = $db->query("SELECT COUNT(*) as total FROM students WHERE gender = 'Male'")->fetch_assoc()['total'];
$totalFemale = $db->query("SELECT COUNT(*) as total FROM students WHERE gender = 'Female'")->fetch_assoc()['total'];

// Room Statistics
$totalRooms = $db->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$totalBeds = $db->query("SELECT SUM(capacity) as total FROM rooms")->fetch_assoc()['total'];
$totalAvailableBeds = $db->query("SELECT SUM(availableBeds) as total FROM rooms")->fetch_assoc()['total'];
$totalOccupiedBeds = $totalBeds - $totalAvailableBeds;
$occupancyRate = ($totalBeds > 0) ? round(($totalOccupiedBeds / $totalBeds) * 100) : 0;

// Rooms by hostel
$hostels = ['Eswanthini', 'Seychells', 'Namibia', 'Botswana', 'Lesotho'];
$roomsByHostel = array();
foreach($hostels as $hostel) {
    $hostelData = array();
    $hostelData['name'] = $hostel;
    $hostelData['total_rooms'] = $db->query("SELECT COUNT(*) as total FROM rooms WHERE hostelName = '$hostel'")->fetch_assoc()['total'];
    $hostelData['total_beds'] = $db->query("SELECT SUM(capacity) as total FROM rooms WHERE hostelName = '$hostel'")->fetch_assoc()['total'];
    $hostelData['available_beds'] = $db->query("SELECT SUM(availableBeds) as total FROM rooms WHERE hostelName = '$hostel'")->fetch_assoc()['total'];
    $hostelData['occupied_beds'] = $hostelData['total_beds'] - $hostelData['available_beds'];
    $roomsByHostel[] = $hostelData;
}

// Payment Statistics (paid_students for current semester)
$paidStudents = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND semester = '$currentSemester'")->fetch_assoc()['total'];
$paidICT = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND semester = '$currentSemester' AND regNumber LIKE '%BscICT%'")->fetch_assoc()['total'];
$paidNursing = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND semester = '$currentSemester' AND regNumber LIKE '%BscNM%'")->fetch_assoc()['total'];
$paidBusiness = $db->query("SELECT COUNT(*) as total FROM paid_students WHERE status = 'active' AND semester = '$currentSemester' AND regNumber LIKE '%BscBA%'")->fetch_assoc()['total'];

// Blacklist Statistics
$totalBlacklisted = $db->query("SELECT COUNT(*) as total FROM blacklist WHERE status = 'active'")->fetch_assoc()['total'];
$blacklistedICT = $db->query("SELECT COUNT(*) as total FROM blacklist WHERE status = 'active' AND regNumber LIKE '%BscICT%'")->fetch_assoc()['total'];
$blacklistedNursing = $db->query("SELECT COUNT(*) as total FROM blacklist WHERE status = 'active' AND regNumber LIKE '%BscNM%'")->fetch_assoc()['total'];
$blacklistedBusiness = $db->query("SELECT COUNT(*) as total FROM blacklist WHERE status = 'active' AND regNumber LIKE '%BscBA%'")->fetch_assoc()['total'];

// Fee Commitment Statistics
$fcApproved = $db->query("SELECT COUNT(*) as total FROM fee_commitment_requests WHERE status = 'approved'")->fetch_assoc()['total'];
$fcPending = $db->query("SELECT COUNT(*) as total FROM fee_commitment_requests WHERE status = 'pending'")->fetch_assoc()['total'];
$fcRejected = $db->query("SELECT COUNT(*) as total FROM fee_commitment_requests WHERE status = 'rejected'")->fetch_assoc()['total'];

// Allocation Statistics
$allocatedStudents = $db->query("SELECT COUNT(*) as total FROM students WHERE applicationStatus = 'allocated'")->fetch_assoc()['total'];
$allocatedByHostel = array();
foreach($hostels as $hostel) {
    $count = $db->query("SELECT COUNT(*) as total FROM students s 
                         JOIN rooms r ON s.allocatedRoomID = r.roomID 
                         WHERE r.hostelName = '$hostel' AND s.applicationStatus = 'allocated'")->fetch_assoc()['total'];
    $allocatedByHostel[] = ['hostel' => $hostel, 'count' => $count];
}
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
        .report-tab{padding:10px 20px;background:none;border:none;cursor:pointer;font-size:14px;color:#666;transition:all 0.3s;text-decoration:none;display:inline-block;}
        .report-tab:hover{color:#8B4513;}
        .report-tab.active{color:#8B4513;border-bottom:2px solid #FFD700;font-weight:600;}
        
        .filter-bar{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;align-items:center;padding:10px 0;border-bottom:1px solid #eee;}
        .filter-group{display:flex;align-items:center;gap:8px;}
        .filter-group label{font-weight:600;color:#8B4513;font-size:13px;}
        .filter-group select,.filter-group input{padding:6px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:white;}
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
        
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:25px;}
        .stat-box{background:#f8f9fa;border-radius:8px;padding:15px;border:1px solid #e0e0e0;}
        .stat-box h3{color:#8B4513;font-size:14px;margin-bottom:10px;border-bottom:1px solid #ddd;padding-bottom:5px;}
        .stat-box .number{font-size:24px;font-weight:bold;color:#333;}
        .stat-box .label{font-size:11px;color:#666;}
        .stat-row{display:flex;justify-content:space-between;margin:5px 0;}
        
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
            .stats-grid{grid-template-columns:1fr;}
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
        <a href="reports.php" class="active">Reports</a>
        <a href="dashboard.php?page=profile">Profile</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>
<div class="welcome-section">
    <h1>Welcome, <?php echo $_SESSION['name']; ?>!</h1>
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
            <a href="?tab=institutional" class="report-tab <?php echo ($activeTab == 'institutional') ? 'active' : ''; ?>">
                Institutional Report
            </a>
        </div>
        
        <!-- TAB 1: Approved Fee Commitment -->
        <?php if($activeTab == 'approved'): ?>
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Department:</label>
                    <select id="deptFilterApproved" onchange="applyApprovedFilter()">
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
                <button class="btn-filter" onclick="applyApprovedFilter()">Apply Filter</button>
                <button class="btn-filter" onclick="clearApprovedFilter()">Clear Filter</button>
            </div>
            
            <div class="export-buttons">
                <button onclick="window.print()" class="btn-export">Print / Save as PDF</button>
                <a href="export_csv.php?type=approved&dept=<?php echo $departmentFilter; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn-export">Export Excel</a>
            </div>
            
            <h3 style="margin-bottom:15px; color:#8B4513;">Approved Fee Commitment Students</h3>
            <?php if(count($approvedStudents) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reg Number</th>
                                <th>Student Name</th>
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
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td>
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
                                 </td
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No approved fee commitment students found for the selected criteria.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- TAB 2: Blacklisted Students -->
        <?php if($activeTab == 'blacklist'): ?>
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Department:</label>
                    <select id="deptFilterBlacklist" onchange="applyBlacklistFilter()">
                        <option value="all" <?php echo ($departmentFilter == 'all') ? 'selected' : ''; ?>>All Departments</option>
                        <option value="ict" <?php echo ($departmentFilter == 'ict') ? 'selected' : ''; ?>>ICT</option>
                        <option value="nursing" <?php echo ($departmentFilter == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
                        <option value="business" <?php echo ($departmentFilter == 'business') ? 'selected' : ''; ?>>Business Administration</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" id="blacklistFromDate" value="<?php echo $from_date; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" id="blacklistToDate" value="<?php echo $to_date; ?>">
                </div>
                <button class="btn-filter" onclick="applyBlacklistFilter()">Apply Filter</button>
                <button class="btn-filter" onclick="clearBlacklistFilter()">Clear Filter</button>
            </div>
            
            <div class="export-buttons">
                <button onclick="window.print()" class="btn-export">Print / Save as PDF</button>
                <a href="export_csv.php?type=blacklist&dept=<?php echo $departmentFilter; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" class="btn-export">Export Excel</a>
            </div>
            
            <h3 style="margin-bottom:15px; color:#8B4513;">Blacklisted Students</h3>
            <?php if(count($blacklistedStudents) > 0): ?>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($blacklistedStudents as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                                <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                                <td style="max-width:300px;"><?php echo htmlspecialchars($student['reason']); ?></td>
                                <td>
                                    <?php if(isset($student['evidence_image']) && $student['evidence_image']): ?>
                                        <a href="../uploads/blacklist/<?php echo $student['evidence_image']; ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        No evidence
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo htmlspecialchars($student['dateAdded']); ?></td>
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
        
        <!-- TAB 3: Institutional Report -->
        <?php if($activeTab == 'institutional'): ?>
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Program:</label>
                    <select id="reportProgram">
                        <option value="all" <?php echo ($reportProgram == 'all') ? 'selected' : ''; ?>>All Programs</option>
                        <option value="ict" <?php echo ($reportProgram == 'ict') ? 'selected' : ''; ?>>ICT</option>
                        <option value="nursing" <?php echo ($reportProgram == 'nursing') ? 'selected' : ''; ?>>Nursing</option>
                        <option value="business" <?php echo ($reportProgram == 'business') ? 'selected' : ''; ?>>Business Administration</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Hostel:</label>
                    <select id="reportHostel">
                        <option value="all" <?php echo ($reportHostel == 'all') ? 'selected' : ''; ?>>All Hostels</option>
                        <option value="Eswanthini">Eswanthini</option>
                        <option value="Seychells">Seychells</option>
                        <option value="Namibia">Namibia</option>
                        <option value="Botswana">Botswana</option>
                        <option value="Lesotho">Lesotho</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date:</label>
                    <input type="date" id="reportFromDate" value="<?php echo $report_from_date; ?>">
                </div>
                <div class="filter-group">
                    <label>To Date:</label>
                    <input type="date" id="reportToDate" value="<?php echo $report_to_date; ?>">
                </div>
                <button class="btn-filter" onclick="applyInstitutionalFilter()">Apply Filter</button>
                <button class="btn-filter" onclick="clearInstitutionalFilter()">Clear Filter</button>
            </div>
            
            <div class="export-buttons">
                <button onclick="window.print()" class="btn-export">Print / Save as PDF</button>
                <a href="export_institutional_csv.php?program=<?php echo $reportProgram; ?>&hostel=<?php echo $reportHostel; ?>&from_date=<?php echo $report_from_date; ?>&to_date=<?php echo $report_to_date; ?>" class="btn-export">Export Excel</a>
            </div>
            
            <!-- Current Semester Info -->
            <div style="background:#e3f2fd; padding:10px; border-radius:5px; margin-bottom:20px; text-align:center;">
                <strong>Current Semester:</strong> <?php echo $currentSemester; ?> | 
                <strong>Academic Year:</strong> <?php echo $academicYear; ?>
            </div>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                
                <!-- Student Statistics -->
                <div class="stat-box">
                    <h3>Student Statistics</h3>
                    <div class="stat-row"><span>Total Students:</span><strong><?php echo $totalStudents; ?></strong></div>
                    <div class="stat-row"><span>ICT:</span><strong><?php echo $totalICT; ?></strong></div>
                    <div class="stat-row"><span>Nursing:</span><strong><?php echo $totalNursing; ?></strong></div>
                    <div class="stat-row"><span>Business Administration:</span><strong><?php echo $totalBusiness; ?></strong></div>
                    <div class="stat-row"><span>Male:</span><strong><?php echo $totalMale; ?></strong></div>
                    <div class="stat-row"><span>Female:</span><strong><?php echo $totalFemale; ?></strong></div>
                </div>
                
                <!-- Room Statistics -->
                <div class="stat-box">
                    <h3>Room Statistics</h3>
                    <div class="stat-row"><span>Total Rooms:</span><strong><?php echo $totalRooms; ?></strong></div>
                    <div class="stat-row"><span>Total Beds:</span><strong><?php echo $totalBeds; ?></strong></div>
                    <div class="stat-row"><span>Occupied Beds:</span><strong><?php echo $totalOccupiedBeds; ?></strong></div>
                    <div class="stat-row"><span>Available Beds:</span><strong><?php echo $totalAvailableBeds; ?></strong></div>
                    <div class="stat-row"><span>Occupancy Rate:</span><strong><?php echo $occupancyRate; ?>%</strong></div>
                </div>
                
                <!-- Rooms by Hostel -->
                <div class="stat-box">
                    <h3>Rooms by Hostel</h3>
                    <?php foreach($roomsByHostel as $hostel): ?>
                    <div class="stat-row">
                        <span><?php echo $hostel['name']; ?>:</span>
                        <strong><?php echo $hostel['occupied_beds']; ?>/<?php echo $hostel['total_beds']; ?> beds</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Payment Statistics -->
                <div class="stat-box">
                    <h3>Payment Statistics (<?php echo $currentSemester; ?>)</h3>
                    <div class="stat-row"><span>Paid Students:</span><strong><?php echo $paidStudents; ?></strong></div>
                    <div class="stat-row"><span>ICT Paid:</span><strong><?php echo $paidICT; ?></strong></div>
                    <div class="stat-row"><span>Nursing Paid:</span><strong><?php echo $paidNursing; ?></strong></div>
                    <div class="stat-row"><span>Business Paid:</span><strong><?php echo $paidBusiness; ?></strong></div>
                </div>
                
                <!-- Blacklist Statistics -->
                <div class="stat-box">
                    <h3>Blacklist Statistics</h3>
                    <div class="stat-row"><span>Total Blacklisted:</span><strong><?php echo $totalBlacklisted; ?></strong></div>
                    <div class="stat-row"><span>ICT:</span><strong><?php echo $blacklistedICT; ?></strong></div>
                    <div class="stat-row"><span>Nursing:</span><strong><?php echo $blacklistedNursing; ?></strong></div>
                    <div class="stat-row"><span>Business:</span><strong><?php echo $blacklistedBusiness; ?></strong></div>
                </div>
                
                <!-- Fee Commitment Statistics -->
                <div class="stat-box">
                    <h3>Fee Commitment Statistics</h3>
                    <div class="stat-row"><span>Approved:</span><strong class="badge-good" style="padding:2px 8px;"><?php echo $fcApproved; ?></strong></div>
                    <div class="stat-row"><span>Pending:</span><strong class="badge-warning" style="padding:2px 8px;"><?php echo $fcPending; ?></strong></div>
                    <div class="stat-row"><span>Rejected:</span><strong class="badge-danger" style="padding:2px 8px;"><?php echo $fcRejected; ?></strong></div>
                </div>
                
                <!-- Allocation Statistics -->
                <div class="stat-box">
                    <h3>Allocation Statistics</h3>
                    <div class="stat-row"><span>Allocated Students:</span><strong><?php echo $allocatedStudents; ?></strong></div>
                    <?php foreach($allocatedByHostel as $hostel): ?>
                    <div class="stat-row">
                        <span><?php echo $hostel['hostel']; ?>:</span>
                        <strong><?php echo $hostel['count']; ?> students</strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
    // Approved Fee Commitment Filters
    function applyApprovedFilter() {
        var dept = document.getElementById('deptFilterApproved').value;
        var fromDate = document.getElementById('fromDate').value;
        var toDate = document.getElementById('toDate').value;
        window.location.href = 'reports.php?tab=approved&dept=' + dept + '&from_date=' + fromDate + '&to_date=' + toDate;
    }
    
    function clearApprovedFilter() {
        window.location.href = 'reports.php?tab=approved';
    }
    
    // Blacklist Filters
    function applyBlacklistFilter() {
        var dept = document.getElementById('deptFilterBlacklist').value;
        var fromDate = document.getElementById('blacklistFromDate').value;
        var toDate = document.getElementById('blacklistToDate').value;
        window.location.href = 'reports.php?tab=blacklist&dept=' + dept + '&from_date=' + fromDate + '&to_date=' + toDate;
    }
    
    function clearBlacklistFilter() {
        window.location.href = 'reports.php?tab=blacklist';
    }
    
    // Institutional Report Filters
    function applyInstitutionalFilter() {
        var program = document.getElementById('reportProgram').value;
        var hostel = document.getElementById('reportHostel').value;
        var fromDate = document.getElementById('reportFromDate').value;
        var toDate = document.getElementById('reportToDate').value;
        window.location.href = 'reports.php?tab=institutional&report_program=' + program + '&report_hostel=' + hostel + '&report_from_date=' + fromDate + '&report_to_date=' + toDate;
    }
    
    function clearInstitutionalFilter() {
        window.location.href = 'reports.php?tab=institutional';
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