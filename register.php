<?php
/**
 * Registration Page
 * Allows new users to create an account
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
$fullname = $email = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate full name
    if (empty($fullname)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($fullname) < 3) {
        $errors[] = 'Full name must be at least 3 characters';
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Create database connection
        $conn = mysqli_connect($serverName, $userName, 12345, $dbName);
        
        // Check connection
        if (!$conn) {
            $errors[] = 'Database connection failed: ' . mysqli_connect_error();
        } else {
            // Check if email already exists
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'Email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Registration successful
                    $_SESSION['success_message'] = 'Registration successful! Please login.';
                    header('Location: login.php');
                    exit();
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
            
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EmailScheduler</title>
    <link rel="stylesheet" href="assets/css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
                <h1>Create Your Account</h1>
                <p>Start scheduling your emails today</p>
            </div>

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

            <!-- Registration Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?php echo htmlspecialchars($fullname); ?>"
                        required 
                        placeholder="Enter your full name"
                    >
                </div>

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
                        placeholder="At least 6 characters"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        placeholder="Re-enter your password"
                    >
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
            </form>

            <!-- Footer Links -->
            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>

            <div class="back-home">
                <a href="index.php">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>