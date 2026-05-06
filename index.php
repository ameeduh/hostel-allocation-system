<?php
session_start();

// Autoload classes
spl_autoload_register(function($class_name) {
    include 'classes/' . $class_name . '.php';
});

// If already logged in, redirect
if(isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if($role == 'student') {
        header("Location: student/dashboard.php");
    } elseif($role == 'accounts') {
        header("Location: accountant/dashboard.php");
    } elseif($role == 'warden') {
        header("Location: warden/dashboard.php");
    } elseif($role == 'admin') {
        header("Location: admin/dashboard.php");
    }
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Try Student login
    $student = new Student();
    if($student->login($username, $password)) {
        header("Location: student/dashboard.php");
        exit();
    }
    
    // Try Accountant login
    $accountant = new Accountant();
    if($accountant->login($username, $password)) {
        header("Location: accountant/dashboard.php");
        exit();
    }
    
    // Try Warden login
    $warden = new Warden();
    if($warden->login($username, $password)) {
        header("Location: warden/dashboard.php");
        exit();
    }
    
    // Try Admin login
    $admin = new Admin();
    if($admin->login($username, $password)) {
        header("Location: admin/dashboard.php");
        exit();
    }
    
    $error = "Invalid ID or Password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Allocation - Login</title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>Hostel Allocation</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Registration ID</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">SIGN IN</button>
        </form>
        
        <div class="footer" style="margin-top: 20px; text-align: center;">
            Don't have an account? <a href="student/register.php" style="color: #8B4513;">Register here</a>
        </div>
    </div>
</body>
</html>