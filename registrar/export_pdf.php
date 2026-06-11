<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registrar') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$type = isset($_GET['type']) ? $_GET['type'] : 'approved';
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

$blacklistDateWhere = "";
if($from_date && $to_date) {
    $blacklistDateWhere = " AND dateAdded BETWEEN '$from_date' AND '$to_date'";
} elseif($from_date) {
    $blacklistDateWhere = " AND dateAdded >= '$from_date'";
} elseif($to_date) {
    $blacklistDateWhere = " AND dateAdded <= '$to_date'";
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.pdf"');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrar Report - Daeyang University</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #8B4513; text-align: center; margin-bottom: 10px; }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-date { text-align: center; color: #666; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #8B4513; color: white; padding: 10px; text-align: left; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
        .badge-good { color: #155724; }
        .badge-warning { color: #856404; }
        .badge-danger { color: #721c24; }
    </style>
</head>
<body>
    <h1>Daeyang University</h1>
    <div class="report-header">
        <h2>Registrar Report</h2>
        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        <?php if($from_date || $to_date): ?>
            <p>Date Range: <?php echo $from_date ?: 'Start'; ?> to <?php echo $to_date ?: 'Today'; ?></p>
        <?php endif; ?>
        <?php if($departmentFilter != 'all'): ?>
            <p>Department: <?php echo ucfirst($departmentFilter); ?></p>
        <?php endif; ?>
    </div>
    
    <?php if($type == 'approved'): ?>
        <h3>Approved Fee Commitment Students</h3>
        <?php
        $sql = "SELECT fc.*, s.program, s.year, s.gender, s.regNumber 
                FROM fee_commitment_requests fc
                JOIN students s ON fc.studentID = s.studentID
                WHERE fc.status = 'approved' $deptWhere $dateWhere
                ORDER BY fc.approvedDate DESC";
        $result = $db->query($sql);
        $students = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $expiryDate = $row['approvedDate'];
                if($expiryDate) {
                    $expiry = strtotime($expiryDate . ' +4 weeks');
                    $today = time();
                    $daysRemaining = round(($expiry - $today) / (60 * 60 * 24));
                    $row['expiry_date'] = date('Y-m-d', $expiry);
                    $row['days_remaining'] = $daysRemaining;
                }
                $students[] = $row;
            }
        }
        ?>
        <table>
            <thead>
                <tr><th>Student Name</th><th>Reg Number</th><th>Program</th><th>Year</th><th>Gender</th><th>Date Approved</th><th>Expiry Date</th><th>Days Left</th></tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                    <td><?php echo $student['year']; ?> Year</td>
                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    <td><?php echo htmlspecialchars($student['approvedDate']); ?></td>
                    <td><?php echo htmlspecialchars($student['expiry_date']); ?></td>
                    <td><?php echo htmlspecialchars($student['days_remaining']); ?> days</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif($type == 'blacklist'): ?>
        <h3>Blacklisted Students</h3>
        <?php
        $sql = "SELECT * FROM blacklist WHERE status = 'active' $deptWhere $blacklistDateWhere ORDER BY dateAdded DESC";
        $result = $db->query($sql);
        $students = array();
        if($result) {
            while($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
        }
        ?>
        <table>
            <thead>
                <tr><th>Reg Number</th><th>Student Name</th><th>Reason</th><th>Date Added</th><th>Added By</th></tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['regNumber']); ?></td>
                    <td><?php echo htmlspecialchars($student['studentName']); ?></td>
                    <td><?php echo htmlspecialchars($student['reason']); ?></td>
                    <td><?php echo htmlspecialchars($student['dateAdded']); ?></td>
                    <td><?php echo htmlspecialchars($student['addedBy']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        &copy; <?php echo date('Y'); ?> Daeyang University - Hostel Allocation System
    </div>
    
    <script>
        window.onload = function() {
            window.print();
            setTimeout(function() { window.close(); }, 1000);
        }
    </script>
</body>
</html>