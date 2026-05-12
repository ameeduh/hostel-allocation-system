<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$db = new Database();

// Handle delete user
if(isset($_GET['delete'])) {
    $userID = (int)$_GET['delete'];
    
    // Don't allow admin to delete themselves
    if($userID != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE userID = $userID";
        $db->query($sql);
        header("Location: users.php?deleted=1");
    } else {
        header("Location: users.php?error=1");
    }
    exit();
}

// Get all users
$sql = "SELECT userID, username, name, email, phone, role FROM users ORDER BY role, name";
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
    <link rel="stylesheet" href="../css/style.css?v=16">
    <style>
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
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 12px;
        }
        .role-student { background-color: #e3f2fd; color: #1565c0; }
        .role-accounts { background-color: #e8f5e9; color: #2e7d32; }
        .role-warden { background-color: #fff3e0; color: #e65100; }
        .role-admin { background-color: #fce4ec; color: #c62828; }
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

            <?php if(count($users) > 0): ?>
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
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['name']; ?></td>
                            <td><?php echo $user['email'] ?: 'N/A'; ?></td>
                            <td><?php echo $user['phone'] ?: 'N/A'; ?></td>
                            <td class="role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <?php if($user['userID'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?php echo $user['userID']; ?>" class="delete-btn" onclick="return confirm('Delete this user?')">Delete</a>
                                <?php else: ?>
                                    <span style="color: #999;">Current</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
            
            <div class="back-link-bottom">
                <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>