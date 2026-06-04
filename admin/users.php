<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Get filter from URL
$userFilter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all';

// Handle delete user
if(isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    
    // Don't allow admin to delete themselves
    if($userID != $_SESSION['user_id']) {
        // Delete from role-specific tables first
        $roleSql = "SELECT role FROM users WHERE userID = $userID";
        $roleResult = $db->query($roleSql);
        if($roleResult) {
            $role = $roleResult->fetch_assoc()['role'];
            if($role == 'accounts') $db->query("DELETE FROM accountants WHERE userID = $userID");
            elseif($role == 'warden') $db->query("DELETE FROM wardens WHERE userID = $userID");
            elseif($role == 'registrar') $db->query("DELETE FROM registrars WHERE userID = $userID");
            elseif($role == 'student') $db->query("DELETE FROM students WHERE userID = $userID");
            elseif($role == 'admin') $db->query("DELETE FROM admins WHERE userID = $userID");
        }
        
        $sql = "DELETE FROM users WHERE userID = $userID";
        $db->query($sql);
        header("Location: users.php?deleted=1&user_filter=$userFilter");
    } else {
        header("Location: users.php?error=1&user_filter=$userFilter");
    }
    exit();
}

// Build WHERE clause for user filter
$whereClause = "";
if($userFilter != 'all') {
    $whereClause = " WHERE role = '$userFilter'";
}

// Get all users with filter
$sql = "SELECT userID, username, name, email, phone, role FROM users $whereClause ORDER BY role, name";
$result = $db->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

$deleted = isset($_GET['deleted']);
$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; }
        
        .full-page-container { padding: 20px 40px; }
        .full-content-card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .full-content-card h1 { color: #8B4513; font-size: 24px; margin-bottom: 20px; border-bottom: 2px solid #8B4513; padding-bottom: 10px; }
        
        .filter-dropdown { margin-bottom: 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; padding: 10px 0; border-bottom: 1px solid #eee; }
        .filter-dropdown label { font-weight: 600; color: #8B4513; font-size: 13px; }
        .filter-dropdown select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; background: white; cursor: pointer; }
        .filter-dropdown select:focus { border-color: #8B4513; outline: none; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .data-table th { background-color: #8B4513; color: white; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
        .data-table tr:hover { background-color: #f9f9f9; }
        
        .delete-btn { background-color: #8B4513; color: white; padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; display: inline-block; }
        .delete-btn:hover { background-color: #6d3710; }
        .back-btn { background-color: #8B4513; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; font-size: 13px; }
        .back-btn:hover { background-color: #6d3710; }
        
        .success-message { background-color: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        .error-message { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-size: 13px; }
        
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background-color: #f0f0f0; color: #333; }
        
        .back-link-bottom { margin-top: 25px; }
        
        @media (max-width: 768px) {
            .full-page-container { padding: 15px 20px; }
            .data-table { overflow-x: auto; display: block; }
            .filter-dropdown { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="full-page-container">
        <div class="full-content-card">
            <h1>Manage Users</h1>

            <?php if($deleted): ?>
                <div class="success-message">User deleted successfully!</div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="error-message">You cannot delete your own account.</div>
            <?php endif; ?>

            <!-- User Type Filter Dropdown -->
            <div class="filter-dropdown">
                <label>Filter by User Type:</label>
                <select id="userFilter" onchange="window.location.href='users.php?user_filter='+this.value">
                    <option value="all" <?php echo ($userFilter == 'all') ? 'selected' : ''; ?>>All Users</option>
                    <option value="student" <?php echo ($userFilter == 'student') ? 'selected' : ''; ?>>Students</option>
                    <option value="accounts" <?php echo ($userFilter == 'accounts') ? 'selected' : ''; ?>>Accountant</option>
                    <option value="registrar" <?php echo ($userFilter == 'registrar') ? 'selected' : ''; ?>>Registrar</option>
                    <option value="warden" <?php echo ($userFilter == 'warden') ? 'selected' : ''; ?>>Warden</option>
                    <option value="admin" <?php echo ($userFilter == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <?php if(count($users) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo $user['email'] ?: 'N/A'; ?></td>
                                <td><?php echo $user['phone'] ?: 'N/A'; ?></td>
                                <td><span class="role-badge"><?php echo ucfirst($user['role']); ?></span></td>
                                <td>
                                    <?php if($user['userID'] != $_SESSION['user_id']): ?>
                                        <a href="users.php?delete=<?php echo $user['userID']; ?>&user_filter=<?php echo $userFilter; ?>" class="delete-btn" onclick="return confirm('Delete this user?')">Delete</a>
                                    <?php else: ?>
                                        <span style="color: #999;">Current</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>