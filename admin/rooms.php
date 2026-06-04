<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

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
    header("Location: rooms.php?added=1");
    exit();
}

// Handle delete room
if(isset($_GET['delete'])) {
    $roomID = (int)$_GET['delete'];
    $sql = "DELETE FROM rooms WHERE roomID = $roomID";
    $db->query($sql);
    header("Location: rooms.php?deleted=1");
    exit();
}

// Get all rooms
$sql = "SELECT * FROM rooms ORDER BY hostelName, roomNumber";
$result = $db->query($sql);
if ($result && is_object($result) && method_exists($result, 'fetch_all')) {
    $rooms = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $rooms = array();
}

$added = isset($_GET['added']);
$deleted = isset($_GET['deleted']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Portal</title>
    <link rel="stylesheet" href="../css/style.css?v=16">
    <style>
        .form-card {
            background-color: #FFF8DC;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #FFD700;
        }
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #8B4513;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-add {
            background-color: #8B4513;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #8B4513;
            color: white;
        }
        .delete-btn {
            background-color: #000000;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
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
                            <td><?php echo $room['roomNumber']; ?></td>
                            <td><?php echo $room['hostelName']; ?></td>
                            <td><?php echo $room['gender']; ?></td>
                            <td><?php echo $room['capacity']; ?></td>
                            <td><?php echo $room['availableBeds']; ?></td>
                            <td><?php echo $room['status']; ?></td>
                            <td>
                                <a href="?delete=<?php echo $room['roomID']; ?>" class="delete-btn" onclick="return confirm('Delete this room?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No rooms found.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>