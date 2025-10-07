<?php
/**
 * Edit Scheduled Email Page
 * Allows users to edit existing scheduled emails
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

// Check if email ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$email_id = $_GET['id'];

// Initialize variables
$errors = [];
$success_message = '';

// Fetch the scheduled email
$query = "SELECT * FROM scheduled_emails WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $email_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$email_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If email not found or doesn't belong to user
if (!$email_data) {
    header('Location: dashboard.php');
    exit();
}

// Set form values
$recipient_email = $email_data['recipient_email'];
$subject = $email_data['subject'];
$message = $email_data['message'];
$scheduled_datetime = $email_data['scheduled_time'];

// Split datetime into date and time
$scheduled_date = date('Y-m-d', strtotime($scheduled_datetime));
$scheduled_time = date('H:i', strtotime($scheduled_datetime));

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $recipient_email = trim($_POST['recipient_email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $scheduled_date = $_POST['scheduled_date'];
    $scheduled_time = $_POST['scheduled_time'];
    
    // Validate recipient email
    if (empty($recipient_email)) {
        $errors[] = 'Recipient email is required';
    } elseif (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid recipient email format';
    }
    
    // Validate subject
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    // Validate message
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    // Validate scheduled date and time
    if (empty($scheduled_date) || empty($scheduled_time)) {
        $errors[] = 'Scheduled date and time are required';
    } else {
        // Combine date and time
        $new_scheduled_datetime = $scheduled_date . ' ' . $scheduled_time;
        
        // Check if scheduled time is in the future
        if (strtotime($new_scheduled_datetime) <= time()) {
            $errors[] = 'Scheduled time must be in the future';
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $update_query = "UPDATE scheduled_emails SET recipient_email = ?, subject = ?, message = ?, scheduled_time = ? WHERE id = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssssii", $recipient_email, $subject, $message, $new_scheduled_datetime, $email_id, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success_message'] = 'Email updated successfully!';
            header('Location: dashboard.php');
            exit();
        } else {
            $errors[] = 'Failed to update email. Please try again.';
        }
        
        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Scheduled Email - EmailScheduler</title>
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
            max-width: 800px;
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

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
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
        .form-group textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
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

        .btn-secondary {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #cbd5e1;
            color: #334155;
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

            .form-card {
                padding: 24px;
            }

            .page-title {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
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
            <h1 class="page-title">Edit Scheduled Email</h1>
            <p class="page-description">Update your scheduled email details</p>
        </div>

        <div class="form-card">
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

            <!-- Edit Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="recipient_email">Recipient Email <span style="color: #ef4444;">*</span></label>
                    <input 
                        type="email" 
                        id="recipient_email" 
                        name="recipient_email" 
                        value="<?php echo htmlspecialchars($recipient_email); ?>"
                        required 
                        placeholder="recipient@example.com"
                    >
                    <p class="hint-text">Enter the email address of the recipient</p>
                </div>

                <div class="form-group">
                    <label for="subject">Email Subject <span style="color: #ef4444;">*</span></label>
                    <input 
                        type="text" 
                        id="subject" 
                        name="subject" 
                        value="<?php echo htmlspecialchars($subject); ?>"
                        required 
                        placeholder="Enter email subject"
                    >
                </div>

                <div class="form-group">
                    <label for="message">Email Message <span style="color: #ef4444;">*</span></label>
                    <textarea 
                        id="message" 
                        name="message" 
                        required 
                        placeholder="Write your email message here..."
                    ><?php echo htmlspecialchars($message); ?></textarea>
                    <p class="hint-text">Compose the content of your email</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="scheduled_date">Scheduled Date <span style="color: #ef4444;">*</span></label>
                        <input 
                            type="date" 
                            id="scheduled_date" 
                            name="scheduled_date" 
                            value="<?php echo htmlspecialchars($scheduled_date); ?>"
                            min="<?php echo date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="scheduled_time">Scheduled Time <span style="color: #ef4444;">*</span></label>
                        <input 
                            type="time" 
                            id="scheduled_time" 
                            name="scheduled_time" 
                            value="<?php echo htmlspecialchars($scheduled_time); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Email</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>