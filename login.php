<?php
session_start();
include 'config/conn.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: dashboard/index.php");
        exit();
    } elseif ($_SESSION['role'] == 'employee') {
        header("Location: employee/index.php");
        exit();
    }
}

$login_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role IMMEDIATELY after setting session
            if ($user['role'] == 'admin') {
                header("Location: dashboard/index.php");
                exit();
            } elseif ($user['role'] == 'employee') {
                header("Location: employee/index.php");
                exit();
            }
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Rice Inventory - Login</title>
<link rel="stylesheet" href="css/login.css" />
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <div class="logo"></div>
        <h1 class="title">Rice Inventory</h1>
        <p class="subtitle">Management System</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="" id="loginForm">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required autocomplete="username" />
            <span class="input-icon">👤</span>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password" />
            <span class="input-icon">🔒</span>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">Sign In</button>
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