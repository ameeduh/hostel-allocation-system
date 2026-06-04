<?php
// If user is already logged in, redirect to appropriate dashboard
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'student') {
        header("Location: student/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'accounts') {
        header("Location: accountant/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'registrar') {
        header("Location: registrar/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'warden') {
        header("Location: warden/dashboard.php");
        exit();
    } elseif($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daeyang University - Hostel Allocation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        /* Top Bar */
        .top-bar {
            background-color: #8B4513;
            color: white;
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-bar .left {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .top-bar .left span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .top-bar .right {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .top-bar a {
            color: white;
            text-decoration: none;
        }
        .top-bar a:hover {
            text-decoration: underline;
        }

        /* Main Navigation */
        .main-nav {
            background-color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background-color: #8B4513;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: #8B4513;
        }
        .logo-text small {
            font-size: 10px;
            font-weight: normal;
            color: #666;
            display: block;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-links a:hover {
            color: #8B4513;
        }
        .login-btn {
            background-color: #8B4513;
            color: white !important;
            padding: 8px 25px;
            border-radius: 25px;
        }
        .login-btn:hover {
            background-color: #6d3710;
            color: white !important;
        }

        /* Hero Section with your bed image */
        .hero {
            background-image: url('images/hans-beds-182964_1920.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
            padding: 100px 40px;
            text-align: center;
            color: white;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.55);
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .hero p {
            font-size: 16px;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            background-color: #FFD700;
            color: #8B4513;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #e6c200;
            transform: scale(1.02);
        }
        .btn-secondary {
            background-color: transparent;
            color: white;
            padding: 12px 30px;
            border: 2px solid #FFD700;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background-color: rgba(255,255,255,0.1);
            transform: scale(1.02);
        }

        /* Foundation Section */
        .foundation {
            background-color: #8B4513;
            color: white;
            padding: 40px;
            text-align: center;
        }
        .foundation h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .foundation p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Colleges Section - No Icons */
        .colleges {
            padding: 60px 40px;
            background-color: white;
        }
        .colleges h2 {
            text-align: center;
            color: #8B4513;
            margin-bottom: 40px;
            font-size: 28px;
        }
        .college-grid {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }
        .college-card {
            flex: 1;
            min-width: 250px;
            background: #f8f9fa;
            padding: 30px 20px;
            text-align: center;
            border-radius: 10px;
            transition: transform 0.3s;
            border: 1px solid #e0e0e0;
        }
        .college-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .college-card h3 {
            color: #8B4513;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .college-card p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }

        /* Footer - Brown */
        .footer {
            background-color: #8B4513;
            color: white;
            padding: 40px 40px 20px;
        }
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #FFD700;
        }
        .footer-section p, .footer-section a {
            font-size: 12px;
            color: #f0f0f0;
            text-decoration: none;
            line-height: 1.8;
            display: block;
        }
        .footer-section a:hover {
            color: #FFD700;
        }
        .copyright {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 11px;
            color: #e0e0e0;
        }

        @media (max-width: 768px) {
            .top-bar, .main-nav {
                padding-left: 20px;
                padding-right: 20px;
            }
            .top-bar {
                flex-direction: column;
                text-align: center;
            }
            .main-nav {
                flex-direction: column;
                text-align: center;
            }
            .hero h1 {
                font-size: 28px;
            }
            .hero {
                padding: 60px 20px;
            }
            .colleges {
                padding: 40px 20px;
            }
            .footer {
                padding: 30px 20px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="left">
            <span>📞 +265994000389</span>
            <span>✉️ registrar@dyuni.ac.mw</span>
        </div>
        <div class="right">
            <a href="#">Chat</a>
            <a href="#">About</a>
            <a href="#">eLearning</a>
            <a href="#">Portal</a>
            <a href="#">Contact Us</a>
            <a href="#">Download Application</a>
        </div>
    </div>

    <!-- Main Navigation -->
    <div class="main-nav">
        <div class="logo">
            <div class="logo-icon">DU</div>
            <div class="logo-text">
                Daeyang University
                <small>Hostel Allocation System</small>
            </div>
        </div>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Programmes</a>
            <a href="#">Student</a>
            <a href="#">More</a>
            <a href="#">Contact Us</a>
            <a href="login.php" class="login-btn">Login</a>
        </div>
    </div>

    <!-- Hero Section with your bed image -->
    <div class="hero">
        <div class="hero-content">
            <h1>Advance Your Career with Daeyang University!</h1>
            <p>Daeyang University is a Christian University registered and accredited by the National Council for Higher Education (NCHE) and founded by the Miracle for Africa Foundation.</p>
            <div class="hero-buttons">
                <a href="#" class="btn-primary">Apply Now</a>
                <a href="#" class="btn-secondary">Learn More</a>
            </div>
        </div>
    </div>

    <!-- Foundation Section -->
    <div class="foundation">
        <h3>Miracle for Africa Foundation</h3>
        <p>Building futures through quality Christian education</p>
    </div>

    <!-- Colleges Section - No Icons -->
    <div class="colleges">
        <h2>Our Colleges</h2>
        <div class="college-grid">
            <div class="college-card">
                <h3>College of Nursing & Midwifery</h3>
                <p>Excellence in healthcare education and professional nursing training</p>
            </div>
            <div class="college-card">
                <h3>College of Information & Communication</h3>
                <p>Leading the way in ICT, computer science and digital innovation</p>
            </div>
            <div class="college-card">
                <h3>School of Business & Management Sciences</h3>
                <p>Developing future business leaders and entrepreneurs</p>
            </div>
        </div>
    </div>

    <!-- Footer - Brown -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Daeyang University is a Christian University founded by the Miracle for Africa Foundation. We are committed to providing quality education with strong moral values.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="#">Home</a>
                <a href="#">About Us</a>
                <a href="#">Admissions</a>
                <a href="#">Contact Us</a>
                <a href="login.php">Student Portal</a>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>📍 Lilongwe, Malawi</p>
                <p>📞 +265994000389</p>
                <p>✉️ registrar@dyuni.ac.mw</p>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Daeyang University. All rights reserved.
        </div>
    </div>
</body>
</html>