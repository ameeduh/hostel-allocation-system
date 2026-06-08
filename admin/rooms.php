<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get filter from URL
$hostelFilter = isset($_GET['hostel_filter']) ? $_GET['hostel_filter'] : '';

// Handle add room
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $roomNumber = $db->escape(trim($_POST['roomNumber']));
    $hostelName = $db->escape(trim($_POST['hostelName']));
    $gender = $db->escape(trim($_POST['gender']));
    $capacity = (int)$_POST['capacity'];
    $availableBeds = max(0, $capacity);

    $sql = "INSERT INTO rooms (roomNumber, hostelName, gender, capacity, availableBeds, status) 
            VALUES ('$roomNumber', '$hostelName', '$gender', $capacity, $availableBeds, 'available')";
    $db->query($sql);
    header("Location: rooms.php?added=1&hostel_filter=$hostelFilter");
    exit();
}

// Handle delete room
if(isset($_GET['delete'])) {
    $roomID = (int)$_GET['delete'];
    
    $db->query("DELETE FROM room_preferences WHERE preferredRoomID = $roomID");
    
    $checkSql = "SELECT COUNT(*) as count FROM students WHERE allocatedRoomID = $roomID AND allocationStatus = 'active'";
    $checkResult = $db->query($checkSql);
    $allocated = $checkResult->fetch_assoc()['count'];
    
    if($allocated > 0) {
        header("Location: rooms.php?error=allocated&hostel_filter=$hostelFilter");
        exit();
    }
    
    $sql = "DELETE FROM rooms WHERE roomID = $roomID";
    $db->query($sql);
    header("Location: rooms.php?deleted=1&hostel_filter=$hostelFilter");
    exit();
}

// Build WHERE clause for hostel filter
$whereClause = "";
if($hostelFilter) {
    $whereClause = " WHERE hostelName = '$hostelFilter'";
}

// Get all rooms with filter
$sql = "SELECT * FROM rooms $whereClause ORDER BY hostelName, roomNumber";
$result = $db->query($sql);
$rooms = array();
if($result) {
    while($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
}

$hostels = ['Eswanthini', 'Seychells', 'Namibia', 'Botswana', 'Lesotho'];

$added = isset($_GET['added']);
$deleted = isset($_GET['deleted']);
$error = isset($_GET['error']) && $_GET['error'] == 'allocated';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; }
        
        .full-page-container { padding: 20px 40px; }
        .full-content-card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .full-content-card h1 { color: #8B4513; font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #8B4513; padding-bottom: 10px; }
        .full-content-card h2 { color: #8B4513; font-size: 18px; margin: 20px 0 15px 0; }
        .full-content-card h3 { color: #8B4513; font-size: 16px; margin-bottom: 15px; }
        
        .form-card { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e0e0e0; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 150px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #8B4513; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
        .form-group input:focus, .form-group select:focus { border-color: #8B4513; outline: none; }
        
        .btn-add { background-color: #8B4513; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-add:hover { background-color: #6d3710; }
        .delete-btn { background-color: #8B4513; color: white; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; display: inline-block; }
        .delete-btn:hover { background-color: #6d3710; }
        .back-btn { background-color: #8B4513; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; font-size: 13px; }
        .back-btn:hover { background-color: #6d3710; }
        
        .filter-dropdown { margin-bottom: 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid #eee; }
        .filter-dropdown label { font-weight: 600; color: #8B4513; font-size: 13px; }
        .filter-dropdown select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white; cursor: pointer; }
        .filter-dropdown select:focus { border-color: #8B4513; outline: none; }
        .filter-dropdown button { background-color: #8B4513; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .filter-dropdown button:hover { background-color: #6d3710; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { background-color: #8B4513; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
        .data-table tr:hover { background-color: #f9f9f9; }
        
        .success-message { background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        .error-message { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-available { background-color: #e8f5e9; color: #2e7d32; }
        .badge-full { background-color: #ffebee; color: #c62828; }
        
        .back-link-bottom { margin-top: 25px; }
        
        @media (max-width: 768px) {
            .full-page-container { padding: 15px 20px; }
            .form-row { flex-direction: column; }
            .data-table { overflow-x: auto; display: block; }
            .filter-dropdown { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Manage Rooms</h1>

            <?php if($added): ?>
                <div class="success-message">Room added successfully!</div>
            <?php endif; ?>
            <?php if($deleted): ?>
                <div class="success-message">Room deleted successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">Cannot delete room because it has active student allocations!</div>
            <?php endif; ?>

            <!-- Hostel Filter Dropdown -->
            <div class="filter-dropdown">
                <label>Filter by Hostel:</label>
                <select id="hostelFilter" onchange="this.form.submit()">
                    <option value="">All Hostels</option>
                    <?php foreach($hostels as $hostel): ?>
                        <option value="<?php echo $hostel; ?>" <?php echo ($hostelFilter == $hostel) ? 'selected' : ''; ?>><?php echo $hostel; ?></option>
                    <?php endforeach; ?>
                </select>
                <form method="get" style="display:inline;">
                    <button type="submit" name="hostel_filter" value="" class="btn-filter" style="background-color: #8B4513; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer;">Clear Filter</button>
                </form>
            </div>

            <!-- Add Room Form -->
            <div class="form-card">
                <h3>Add New Room</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Room Number</label>
                            <input type="text" name="roomNumber" required>
                        </div>
                        <div class="form-group">
                            <label>Hostel Name</label>
                            <select name="hostelName" required>
                                <?php foreach($hostels as $hostel): ?>
                                    <option value="<?php echo $hostel; ?>"><?php echo $hostel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity</label>
                            <input type="number" name="capacity" required>
                        </div>
                    </div>
                    <button type="submit" name="add_room" class="btn-add">Add Room</button>
                </form>
            </div>

            <!-- Rooms List -->
            <h2>All Rooms</h2>
            <?php if(count($rooms) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Hostel Name</th>
                                <th>Gender</th>
                                <th>Capacity</th>
                                <th>Available Beds</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['roomNumber']); ?></td>
                                <td><?php echo htmlspecialchars($room['hostelName']); ?></td>
                                <td><?php echo htmlspecialchars($room['gender']); ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td><?php echo $room['availableBeds']; ?></td>
                                <td><?php echo ($room['availableBeds'] > 0) ? '<span class="badge badge-available">Available</span>' : '<span class="badge badge-full">Full</span>'; ?></td>
                                <td>
                                    <a href="rooms.php?delete=<?php echo $room['roomID']; ?>&hostel_filter=<?php echo $hostelFilter; ?>" class="delete-btn" onclick="return confirm('Delete this room?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No rooms found.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <script>
        // Simple function to handle filter change
        document.getElementById('hostelFilter').addEventListener('change', function() {
            var selected = this.value;
            window.location.href = 'rooms.php?hostel_filter=' + selected;
        });
    </script>
</body>
</html>