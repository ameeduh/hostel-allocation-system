<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get filter from URL
$genderFilter = isset($_GET['gender_filter']) ? $_GET['gender_filter'] : 'all';

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
    header("Location: rooms.php?added=1&gender_filter=$genderFilter");
    exit();
}

// Handle delete room
if(isset($_GET['delete'])) {
    $roomID = (int)$_GET['delete'];
    
    // First, delete any room preferences for this room
    $db->query("DELETE FROM room_preferences WHERE preferredRoomID = $roomID");
    
    // Check if room is allocated to any active student
    $checkSql = "SELECT COUNT(*) as count FROM students WHERE allocatedRoomID = $roomID AND allocationStatus = 'active'";
    $checkResult = $db->query($checkSql);
    $allocated = $checkResult->fetch_assoc()['count'];
    
    if($allocated > 0) {
        header("Location: rooms.php?error=allocated&gender_filter=$genderFilter");
        exit();
    }
    
    $sql = "DELETE FROM rooms WHERE roomID = $roomID";
    $db->query($sql);
    header("Location: rooms.php?deleted=1&gender_filter=$genderFilter");
    exit();
}

// Build WHERE clause for gender filter
$whereClause = "";
if($genderFilter == 'male') {
    $whereClause = " WHERE gender = 'Male'";
} elseif($genderFilter == 'female') {
    $whereClause = " WHERE gender = 'Female'";
}

// Get all rooms with filter
$sql = "SELECT * FROM rooms $whereClause ORDER BY hostelName, roomNumber";
$result = $db->query($sql);
if ($result && is_object($result) && method_exists($result, 'fetch_all')) {
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $rooms = array();
}

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

            <!-- Gender Filter Dropdown -->
            <div class="filter-dropdown">
                <label>Filter by Gender:</label>
                <select id="genderFilter" onchange="window.location.href='rooms.php?gender_filter='+this.value">
                    <option value="all" <?php echo ($genderFilter == 'all') ? 'selected' : ''; ?>>All Rooms</option>
                    <option value="male" <?php echo ($genderFilter == 'male') ? 'selected' : ''; ?>>Male Rooms</option>
                    <option value="female" <?php echo ($genderFilter == 'female') ? 'selected' : ''; ?>>Female Rooms</option>
                </select>
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
                                <option value="Eswanthini">Eswanthini</option>
                                <option value="Seychells">Seychells</option>
                                <option value="Namibia">Namibia</option>
                                <option value="Botswana">Botswana</option>
                                <option value="Lesotho">Lesotho</option>
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
                                    <a href="rooms.php?delete=<?php echo $room['roomID']; ?>&gender_filter=<?php echo $genderFilter; ?>" class="delete-btn" onclick="return confirm('Delete this room?')">Delete</a>
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
</body>
</html>