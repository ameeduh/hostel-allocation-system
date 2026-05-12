<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Allocation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Background Image */
        .landing-bg {
            background-image: url('images/nursing.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100%;
            width: 100%;
            position: relative;
        }

        /* Dark Overlay to make text readable */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        /* Content Container */
        .content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            z-index: 2;
        }

        /* Heading */
        .content h1 {
            font-size: 56px;
            font-weight: 700;
            color: #FFFFFF;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        /* Login Button */
        .login-btn {
            display: inline-block;
            background-color: #8B4513;
            color: #FFFFFF;
            font-size: 20px;
            font-weight: 600;
            padding: 14px 50px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #FFD700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .login-btn:hover {
            background-color: #A0522D;
            transform: scale(1.05);
            border-color: #FFD700;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            color: #FFFFFF;
            font-size: 14px;
            z-index: 2;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content h1 {
                font-size: 32px;
                padding: 0 20px;
            }
            .login-btn {
                font-size: 16px;
                padding: 10px 35px;
            }
        }
    </style>
</head>
<body>
    <div class="landing-bg">
        <div class="overlay"></div>
        <div class="content">
            <h1>HOSTEL ALLOCATION SYSTEM</h1>
            <a href="login.php" class="login-btn">LOGIN</a>
        </div>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Hostel Allocation System | All Rights Reserved
        </div>
    </div>
</body>
</html>