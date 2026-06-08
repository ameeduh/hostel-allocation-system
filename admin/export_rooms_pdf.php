<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();
$selectedHostel = isset($_GET['hostel']) ? $_GET['hostel'] : '';

$roomWhere = "";
if($selectedHostel) {
    $roomWhere = " WHERE hostelName = '$selectedHostel'";
}

$rooms = [];
$result = $db->query("SELECT *, (capacity - availableBeds) as occupiedBeds FROM rooms $roomWhere ORDER BY hostelName, roomNumber");
if($result) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rooms Report - Daeyang University</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #8B4513; text-align: center; }
        .report-header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #8B4513; color: white; padding: 10px; text-align: left; border: 1px solid #ddd; }
        td { padding: 8px; border: 1px solid #ddd; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <h1>Daeyang University</h1>
    <div class="report-header">
        <h2>Rooms Report</h2>
        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        <?php if($selectedHostel): ?>
            <p>Hostel: <?php echo $selectedHostel; ?></p>
        <?php endif; ?>
    </div>
    
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
            <?php foreach($rooms as $room): ?>
            <tr>
                <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                <td><?php echo htmlspecialchars($room['gender']); ?></td>
                <td><?php echo $room['capacity']; ?></td>
                <td><?php echo $room['occupiedBeds']; ?></td>
                <td><?php echo $room['availableBeds']; ?></td>
                <td><?php echo ($room['availableBeds'] > 0) ? 'Available' : 'Full'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
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