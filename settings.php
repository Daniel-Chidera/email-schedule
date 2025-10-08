<?php
/**
 * Settings Page
 * Allows users to manage their account settings
 */

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database configuration
require_once 'config.php';

// Get user information
$user_id = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$fullname = $user_data['username'];
$email = $user_data['email'];

// Initialize messages
$success_message = '';
$errors = [];

// Handle account info update
if (isset($_POST['update_account'])) {
    $new_fullname = trim($_POST['fullname']);
    $new_email = trim($_POST['email']);
    
    // Validate fullname
    if (empty($new_fullname)) {
        $errors[] = 'Full name is required';
    }
    
    // Validate email
    if (empty($new_email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email already exists (for other users)
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $new_email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = 'Email already in use by another account';
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Update if no errors
    if (empty($errors)) {
        $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssi", $new_fullname, $new_email, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['username'] = $new_fullname;
            $_SESSION['email'] = $new_email;
            $fullname = $new_fullname;
            $email = $new_email;
            $success_message = 'Account information updated successfully!';
        } else {
            $errors[] = 'Failed to update account information';
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate current password
    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    } elseif (!password_verify($current_password, $user_data['password'])) {
        $errors[] = 'Current password is incorrect';
    }
    
    // Validate new password
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }
    
    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Update if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_message = 'Password changed successfully!';
        } else {
            $errors[] = 'Failed to change password';
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    $confirm_password = $_POST['delete_password'];
    
    if (password_verify($confirm_password, $user_data['password'])) {
        // Delete user account (will cascade delete scheduled emails)
        $delete_query = "DELETE FROM users WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        
        // Destroy session
        session_destroy();
        header('Location: index.php');
        exit();
    } else {
        $errors[] = 'Incorrect password. Account not deleted.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EmailScheduler</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #334155;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .header {
            margin-bottom: 32px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 16px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #8b5cf6;
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .page-description {
            color: #64748b;
            font-size: 16px;
        }

        .settings-section {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
            color: #334155;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background: #8b5cf6;
            color: white;
        }

        .btn-primary:hover {
            background: #7c3aed;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .danger-zone {
            border: 2px solid #fecaca;
            background: #fef2f2;
        }

        .danger-zone .section-title {
            color: #dc2626;
        }

        .danger-text {
            color: #dc2626;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .hint-text {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 6px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 24px 16px;
            }

            .settings-section {
                padding: 24px;
            }

            .page-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Dashboard
            </a>
            <h1 class="page-title">Settings</h1>
            <p class="page-description">Manage your account settings and preferences</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Account Information -->
        <div class="settings-section">
            <h2 class="section-title">Account Information</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?php echo htmlspecialchars($fullname); ?>"
                        required
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
                    >
                </div>

                <button type="submit" name="update_account" class="btn btn-primary">Update Account</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="settings-section">
            <h2 class="section-title">Change Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        required
                        placeholder="Enter your current password"
                    >
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required
                        placeholder="At least 6 characters"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        placeholder="Re-enter new password"
                    >
                </div>

                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>

        <!-- Preferences -->
        <div class="settings-section">
            <h2 class="section-title">Preferences</h2>
            <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone" disabled>
                    <option>Africa/Lagos</option>
                </select>
                <p class="hint-text">Currently set to Africa/Lagos (configured in system)</p>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="settings-section danger-zone">
            <h2 class="section-title">Danger Zone</h2>
            <p class="danger-text">⚠️ Once you delete your account, there is no going back. This will permanently delete your account and all scheduled emails.</p>
            <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                <div class="form-group">
                    <label for="delete_password">Confirm Password to Delete Account</label>
                    <input 
                        type="password" 
                        id="delete_password" 
                        name="delete_password" 
                        required
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" name="delete_account" class="btn btn-danger">Delete My Account</button>
            </form>
        </div>
    </div>
</body>
</html>