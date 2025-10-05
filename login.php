<?php
/**
 * Login Page
 * Allows users to log into their account
 */

// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Include database configuration
require_once 'config.php';

// Initialize variables
$email = '';
$errors = [];
$success_message = '';

// Check for success message from registration
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Check if connection exists
        if (!$conn) {
            $errors[] = 'Database connection failed';
        } else {
            // Query user by email
            $stmt = mysqli_prepare($conn, "SELECT id, username, email, password FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Set remember me cookie if checked
                    if ($remember_me) {
                        setcookie('user_email', $email, time() + (86400 * 30), '/'); // 30 days
                    }
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $errors[] = 'Invalid email or password';
                }
            } else {
                $errors[] = 'Invalid email or password';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Pre-fill email if remember me cookie exists
if (isset($_COOKIE['user_email']) && empty($email)) {
    $email = $_COOKIE['user_email'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EmailScheduler</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <!-- Header -->
            <div class="auth-header">
                <div class="auth-logo">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="8" fill="#8B5CF6"/>
                        <path d="M8 12L16 18L24 12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>EmailScheduler</span>
                </div>
                <h1>Welcome Back</h1>
                <p>Login to continue scheduling your emails</p>
            </div>

            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>"
                        required 
                        placeholder="your@email.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">Login</button>
            </form>

            <!-- Footer Links -->
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Sign up here</a>
            </div>

            <div class="back-home">
                <a href="index.php">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>