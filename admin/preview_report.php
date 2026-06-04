<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$type = isset($_GET['type']) ? $_GET['type'] : 'students';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Report Preview - Daeyang University</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #8B4513; text-align: center; }
        .header { text-align: center; margin-bottom: 20px; }
        .filter-info { text-align: center; color: #666; margin-bottom: 20px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #8B4513; color: white; padding: 10px; text-align: left; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .download-buttons { text-align: center; margin: 20px 0; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; cursor: pointer; border: none; font-size: 14px; }
        .btn-excel { background-color: #28a745; color: white; }
        .btn-print { background-color: #17a2b8; color: white; }
        .btn-back { background-color: #6c757d; color: white; }
        .filter-form { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; justify-content: center; }
        .filter-form input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { background-color: #8B4513; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #666; }
        @media print {
            .download-buttons, .no-print, .filter-form { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Daeyang University</h1>
        <h2>Hostel Allocation System Report</h2>
        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
    
    <div class="filter-form no-print">
        <label>From Date:</label>
        <input type="date" id="from_date" value="<?php echo $from_date; ?>">
        <label>To Date:</label>
        <input type="date" id="to_date" value="<?php echo $to_date; ?>">
        <button onclick="applyFilter()">Apply Filter</button>
        <button onclick="clearFilter()">Clear Filter</button>
    </div>
    
    <div class="download-buttons no-print">
        <button onclick="exportToExcel()" class="btn btn-excel">Save as Excel (CSV)</button>
        <button onclick="window.print()" class="btn btn-print">Print / Save as PDF</button>
        <a href="dashboard.php?page=reports&report=<?php echo $type; ?>" class="btn btn-back">Back to Reports</a>
    </div>
    
    <?php if($from_date || $to_date): ?>
        <div class="filter-info">
            📅 Filtered from <?php echo $from_date ?: 'start'; ?> to <?php echo $to_date ?: 'today'; ?>
        </div>
    <?php endif; ?>
    
    <?php if($type == 'students'): ?>
        <h3>All Students Report</h3>
        <table id="reportTable">
            <thead>
                <tr><th>Name</th><th>Reg Number</th><th>Program</th><th>Year</th><th>Gender</th><th>Status</th><th>Allocation Date</th></tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT s.*, u.name FROM students s JOIN users u ON s.userID = u.userID ORDER BY u.name";
                $result = $db->query($sql);
                if($result) {
                    while($row = $result->fetch_assoc()) {
                        $yearText = '';
                        if($row['year'] == 1) $yearText = '1st Year';
                        elseif($row['year'] == 2) $yearText = '2nd Year';
                        elseif($row['year'] == 3) $yearText = '3rd Year';
                        elseif($row['year'] == 4) $yearText = '4th Year';
                        else $yearText = 'N/A';
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['regNumber']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                        echo "<td>" . $yearText . "</td>";
                        echo "<td>" . $row['gender'] . "</td>";
                        echo "<td>" . ucfirst($row['applicationStatus']) . "</td>";
                        echo "<td>" . ($row['allocatedDate'] ?? 'N/A') . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    <?php elseif($type == 'rooms'): ?>
        <h3>Room Occupancy Report</h3>
        <table id="reportTable">
            <thead>
                <tr><th>Room Number</th><th>Hostel Name</th><th>Gender</th><th>Capacity</th><th>Occupied</th><th>Available</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT roomNumber, hostelName, gender, capacity, availableBeds, 
                               (capacity - availableBeds) as occupiedBeds, status 
                        FROM rooms ORDER BY hostelName, roomNumber";
                $result = $db->query($sql);
                if($result) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['roomNumber']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['hostelName']) . "</td>";
                        echo "<td>" . $row['gender'] . "</td>";
                        echo "<td>" . $row['capacity'] . "</td>";
                        echo "<td>" . $row['occupiedBeds'] . "</td>";
                        echo "<td>" . $row['availableBeds'] . "</td>";
                        echo "<td>" . ucfirst($row['status']) . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    <?php elseif($type == 'pending'): ?>
        <h3>Pending Approvals Report</h3>
        <table id="reportTable">
            <thead>
                <tr><th>Name</th><th>Reg Number</th><th>Program</th><th>Year</th><th>Gender</th><th>Action Needed</th><th>Submission Date</th></tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT s.*, u.name FROM students s 
                        JOIN users u ON s.userID = u.userID 
                        WHERE s.applicationStatus = 'pending'";
                $result = $db->query($sql);
                if($result) {
                    while($row = $result->fetch_assoc()) {
                        $yearText = '';
                        if($row['year'] == 1) $yearText = '1st Year';
                        elseif($row['year'] == 2) $yearText = '2nd Year';
                        elseif($row['year'] == 3) $yearText = '3rd Year';
                        elseif($row['year'] == 4) $yearText = '4th Year';
                        else $yearText = 'N/A';
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['regNumber']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                        echo "<td>" . $yearText . "</td>";
                        echo "<td>" . $row['gender'] . "</td>";
                        echo "<td>Accountant Verification</td>";
                        echo "<td>" . ($row['allocatedDate'] ?? 'N/A') . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        &copy; <?php echo date('Y'); ?> Daeyang University - Hostel Allocation System
    </div>
    
    <script>
        function applyFilter() {
            var from_date = document.getElementById('from_date').value;
            var to_date = document.getElementById('to_date').value;
            window.location.href = 'preview_report.php?type=<?php echo $type; ?>&from_date=' + from_date + '&to_date=' + to_date;
        }
        
        function clearFilter() {
            window.location.href = 'preview_report.php?type=<?php echo $type; ?>';
        }
        
        function exportToExcel() {
            // Get the current filter values
            var from_date = document.getElementById('from_date').value;
            var to_date = document.getElementById('to_date').value;
            
            // Redirect to export_csv.php with the same parameters
            window.location.href = 'export_csv.php?type=<?php echo $type; ?>&from_date=' + from_date + '&to_date=' + to_date;
        }
    </script>
</body>
</html>