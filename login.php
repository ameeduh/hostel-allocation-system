<?php
session_start();

// Include all classes manually
require_once 'classes/User.php';
require_once 'classes/Student.php';
require_once 'classes/Accountant.php';
require_once 'classes/Registrar.php';
require_once 'classes/Warden.php';
require_once 'classes/Admin.php';

// If already logged in, redirect
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'student') {
        header("Location: student/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'accounts') {
        header("Location: accountant/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'warden') {
        header("Location: warden/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'registrar') {
        header("Location: registrar/dashboard.php");
        exit();
    }
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Try each role
    $student = new Student();
    if($student->login($username, $password)) {
        header("Location: student/dashboard.php");
        exit();
    }
    
    $accountant = new Accountant();
    if($accountant->login($username, $password)) {
        header("Location: accountant/dashboard.php");
        exit();
    }
    
    $warden = new Warden();
    if($warden->login($username, $password)) {
        header("Location: warden/dashboard.php");
        exit();
    }
    
    $admin = new Admin();
    if($admin->login($username, $password)) {
        header("Location: admin/dashboard.php");
        exit();
    }
    
    $registrar = new Registrar();
    if($registrar->login($username, $password)) {
        header("Location: registrar/dashboard.php");
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
    <title>Hostel Allocation System - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F5DC;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #FFFFFF;
            padding: 40px;
            border-radius: 20px;
            width: 420px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 5px solid #FFD700;
        }

        .logo {
            margin-bottom: 35px;
        }
        .logo img {
            max-width: 180px;
            height: auto;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #8B4513;
            font-weight: 500;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #FFFFFF;
        }

        input:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 0 2px rgba(139,69,19,0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #8B4513;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background-color: #A0522D;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #8B4513;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .footer {
            margin-top: 25px;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/daeyang_university_logo.jpg" alt="Daeyang University Logo">
        </div>
        
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
        
        <div class="forgot-password">
            <a href="#">Forgot Password?</a>
        </div>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Daeyang University - Hostel Allocation System
        </div>
    </div>
</body>
</html>