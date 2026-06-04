<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$type = isset($_GET['type']) ? $_GET['type'] : 'students';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.pdf"');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Report - Daeyang University</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #8B4513; text-align: center; }
        h2 { color: #8B4513; margin-top: 20px; border-bottom: 2px solid #FFD700; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #8B4513; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <h1>Daeyang University</h1>
    <h2>Hostel Allocation System Report</h2>
    <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php if($type == 'students'): ?>
        <h3>All Students Report</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Reg Number</th>
                    <th>Program</th>
                    <th>Year</th>
                    <th>Gender</th>
                    <th>Status</th>
                </tr>
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
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    <?php elseif($type == 'rooms'): ?>
        <h3>Room Occupancy Report</h3>
        <table>
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Hostel Name</th>
                    <th>Gender</th>
                    <th>Capacity</th>
                    <th>Occupied</th>
                    <th>Available</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT roomNumber, hostelName, gender, capacity, availableBeds, 
                               (capacity - availableBeds) as occupiedBeds, status 
                        FROM rooms ORDER BY hostelName, roomNumber";
                $result = $db->query($sql);
                if($result) {
                    while($row = $result->fetch_assoc()) {
                        echo "<td>";
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
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Reg Number</th>
                    <th>Program</th>
                    <th>Year</th>
                    <th>Gender</th>
                    <th>Action Needed</th>
                </tr>
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
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>