<?php
session_start();
include 'config/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['userid'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Add a small delay for animation, then redirect
            echo "<script>
                setTimeout(function() {
                    if ('{$user['role']}' == 'admin') {
                        window.location.href = 'dashboard/index.php';
                    } else if ('{$user['role']}' == 'employee') {
                        window.location.href = 'employee/index.php';
                    }
                }, 3500); // 3.5 seconds to show animation
            </script>";
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Inventory - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            position: relative;
        }

        .logo::after {
            content: '🌾';
            font-size: 32px;
            color: white;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .form-group input:hover {
            border-color: #667eea;
            background: white;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .error-message {
            background: #fee;
            color: #d63384;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d63384;
            font-size: 14px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        .features {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
            color: #666;
        }

        .feature-icon {
            width: 16px;
            height: 16px;
            margin-right: 10px;
            color: #667eea;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .title {
                font-size: 20px;
            }
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .login-btn {
            background: #ccc;
        }

        /* Input icons */
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .form-group {
            position: relative;
        }

        .form-group input {
            padding-right: 50px;
        }

        /* Success Animation Styles */
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .success-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .success-content {
            text-align: center;
            transform: scale(0.8);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .success-overlay.show .success-content {
            transform: scale(1);
        }

        .success-checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-block;
            stroke-width: 2;
            stroke: #4CAF50;
            stroke-miterlimit: 10;
            margin: 10px auto;
            box-shadow: inset 0px 0px 0px #4CAF50;
            animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
            position: relative;
        }

        .success-checkmark .check-icon {
            width: 56px;
            height: 56px;
            position: absolute;
            left: 12px;
            top: 12px;
            z-index: 1;
            transform: scale(0);
            animation: scale 0.3s ease-in-out 0.9s both;
        }

        .check-icon .icon-line {
            height: 2px;
            background: #4CAF50;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 1;
        }

        .check-icon .line-tip {
            top: 27px;
            left: 14px;
            width: 15px;
            transform: scaleX(0);
            transform-origin: 0% 50%;
            animation: icon-line-tip 0.75s cubic-bezier(0.650, 0.000, 0.450, 1.000) 1.2s forwards;
        }

        .check-icon .line-long {
            top: 24px;
            right: 8px;
            width: 25px;
            transform: scaleX(0);
            transform-origin: 100% 50%;
            animation: icon-line-long 0.75s cubic-bezier(0.650, 0.000, 0.450, 1.000) 1.5s forwards;
        }

        .check-icon .icon-circle {
            top: -2px;
            left: -2px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid rgba(76, 175, 80, 0.2);
            position: absolute;
        }

        .check-icon .icon-fix {
            top: 6px;
            width: 5px;
            left: 26px;
            z-index: 1;
            height: 85px;
            position: absolute;
            transform: rotate(-45deg);
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: #4CAF50;
            margin: 20px 0 10px;
            animation: fadeInUp 0.6s ease 1.3s both;
        }

        .success-message {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            animation: fadeInUp 0.6s ease 1.5s both;
        }

        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            animation: fadeInUp 0.6s ease 1.7s both;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4CAF50;
            animation: bounce 1.4s ease-in-out infinite both;
        }

        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #4CAF50;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes icon-line-tip {
            0% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            54% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            70% {
                width: 50px;
                left: -8px;
                top: 37px;
            }
            84% {
                width: 17px;
                left: 21px;
                top: 48px;
            }
            100% {
                width: 25px;
                left: 14px;
                top: 45px;
            }
        }

        @keyframes icon-line-long {
            0% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            65% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            84% {
                width: 55px;
                right: 0px;
                top: 35px;
            }
            100% {
                width: 47px;
                right: 8px;
                top: 38px;
            }
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo"></div>
            <h1 class="title">Rice Inventory</h1>
            <p class="subtitle">Management System</p>
        </div>

        <!-- Error message -->
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Success overlay -->
        <div class="success-overlay" id="successOverlay">
            <div class="success-content">
                <div class="success-checkmark">
                    <div class="check-icon">
                        <span class="icon-line line-tip"></span>
                        <span class="icon-line line-long"></span>
                        <div class="icon-circle"></div>
                        <div class="icon-fix"></div>
                    </div>
                </div>
                <h2 class="success-title">Welcome!</h2>
                <p class="success-message">Login successful. Redirecting to dashboard...</p>
                <div class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

        <form method="post" action="" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username">
                <span class="input-icon">👤</span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
                <span class="input-icon">🔒</span>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Sign In
            </button>
        </form>

        <div class="forgot-password">
            <a href="#" onclick="alert('Please contact your administrator for password recovery.')">Forgot Password?</a>
        </div>

        
    </div>

    <script>
        // Enhanced form interactions
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const form = this;
            const btn = document.getElementById('loginBtn');
            
            // Add loading state
            form.classList.add('loading');
            btn.innerHTML = 'Signing In...';
            
            // Remove loading state after a delay (if form validation fails)
            setTimeout(() => {
                form.classList.remove('loading');
                btn.innerHTML = 'Sign In';
            }, 3000);
        });

        // Input focus animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Success animation function
        function showSuccessAnimation() {
            const overlay = document.getElementById('successOverlay');
            overlay.classList.add('show');
            
            // Optional: Auto redirect after animation
            setTimeout(() => {
                // This will be handled by PHP redirect, but you can add additional logic here
                console.log('Redirecting to dashboard...');
            }, 3000);
        }

        // Check if login was successful (you'll need to modify your PHP to trigger this)
        <?php if (isset($_SESSION['userid']) && !isset($error)): ?>
            // Delay to show the success animation before redirect
            setTimeout(showSuccessAnimation, 500);
        <?php endif; ?>

        // Keyboard accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement.tagName !== 'BUTTON') {
                document.getElementById('loginBtn').focus();
            }
        });
    </script>
</body>
</html>