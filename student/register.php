<?php
session_start();

// If already logged in, redirect to dashboard
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
    header("Location: dashboard.php");
    exit();
}

// Include database class directly (no autoloader)
require_once '../config/database.php';

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $regNumber = $_POST['regNumber'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Check if passwords match
    if($password != $confirmPassword) {
        $error = "Passwords do not match!";
    } else {
        $db = new Database();
        $regNumber = $db->escape($regNumber);
        $name = $db->escape($name);
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if student already exists
        $checkSql = "SELECT * FROM students WHERE regNumber = '$regNumber'";
        $checkResult = $db->query($checkSql);
        
        if($checkResult && $checkResult->num_rows > 0) {
            $error = "Registration number already exists!";
        } else {
            // Insert into users table with hashed password
            $sql1 = "INSERT INTO users (username, password, name, email, phone, role) 
                     VALUES ('$regNumber', '$hashedPassword', '$name', '', '', 'student')";
            
            if($db->query($sql1)) {
                $userID = $db->getInsertId();
                
                // Insert into students table (empty details, will be filled later)
                $sql2 = "INSERT INTO students (userID, regNumber, program, year, applicationStatus, gender) 
                         VALUES ($userID, '$regNumber', '', 0, 'pending', '')";
                
                if($db->query($sql2)) {
                    $message = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Hostel System</title>
    <link rel="stylesheet" href="../css/style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container" style="max-width: 450px;">
        <h2>📝 Student Registration</h2>
        
        <?php if($message): ?>
            <div class="success-message" style="background-color: #e8f5e9; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <?php echo $message; ?>
                <p style="margin-top: 10px;"><a href="../index.php" style="color: #8B4513;">Click here to login</a></p>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error" style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!$message): ?>
        <form method="POST">
            <div class="form-group">
                <label>Registration Number *</label>
                <input type="text" name="regNumber" placeholder="e.g., BscICT/24/001" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        
        <div class="footer" style="margin-top: 20px; text-align: center;">
            Already have an account? <a href="../index.php" style="color: #8B4513;">Login here</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>