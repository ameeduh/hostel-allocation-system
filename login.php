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
    } elseif($role == 'registrar') {
        header("Location: registrar/dashboard.php");
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
    
    // Try Registrar login
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
            background-color: #F5F5F5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #FFFFFF;
            padding: 50px 40px;
            border-radius: 20px;
            width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 5px solid #FFD700;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            color: #8B4513;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #8B4513;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
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
            border-radius: 10px;
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
            margin-top: 25px;
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
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
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
        <h1>HOSTEL ALLOCATION SYSTEM</h1>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Registration ID / Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">SIGN IN</button>
        </form>
        
        <div class="forgot-password">
            <a href="#">Forgot your password?</a>
        </div>
        
        <div class="footer">
            Hostel Allocation System &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>