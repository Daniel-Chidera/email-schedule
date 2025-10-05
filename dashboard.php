<?php
/**
 * Dashboard Page
 * Main user interface for managing scheduled emails
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
$fullname = $_SESSION['username'];
$user_email = $_SESSION['email'];

// Get user initials
$names = explode(' ', $fullname);
$initials = '';
if (count($names) >= 2) {
    $initials = strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
} else {
    $initials = strtoupper(substr($fullname, 0, 2));
}

// Fetch statistics
$total_scheduled = 0;
$emails_sent = 0;
$pending_drafts = 0;

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM scheduled_emails WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$total_scheduled = $stats['total'];
$emails_sent = $stats['sent'];
$pending_drafts = $stats['pending'];
$success_rate = $total_scheduled > 0 ? round(($emails_sent / $total_scheduled) * 100) : 0;

// Fetch recent scheduled emails
$query = "SELECT * FROM scheduled_emails WHERE user_id = ? ORDER BY scheduled_time DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$scheduled_emails = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EmailScheduler</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="8" fill="#8B5CF6"/>
                    <path d="M8 12L16 18L24 12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>EmailScheduler</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link active">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="scheduled.php" class="nav-link">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Scheduled Emails
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="drafts.php" class="nav-link">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Drafts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sent.php" class="nav-link">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Sent Emails
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <h1 class="greeting">👋 Welcome, <?php echo htmlspecialchars($fullname); ?></h1>
                <div class="header-actions">
                    <a href="#" class="icon-btn" title="Notifications">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </a>
                    <a href="settings.php" class="icon-btn" title="Settings">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </a>
                    <div class="profile-btn" title="<?php echo htmlspecialchars($user_email); ?>">
                        <?php echo $initials; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $total_scheduled; ?></div>
                            <div class="stat-label">Total Scheduled</div>
                        </div>
                        <div class="stat-icon purple">📧</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $emails_sent; ?></div>
                            <div class="stat-label">Emails Sent</div>
                        </div>
                        <div class="stat-icon green">✅</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $pending_drafts; ?></div>
                            <div class="stat-label">Pending Drafts</div>
                        </div>
                        <div class="stat-icon yellow">⏳</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $success_rate; ?>%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                        <div class="stat-icon blue">📊</div>
                    </div>
                </div>
            </div>

            <!-- Recent Emails Table -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Scheduled Emails</h2>
                    <a href="schedule.php" class="schedule-btn">
                        <span>+</span> Schedule New Email
                    </a>
                </div>
                <div class="table-container">
                    <?php if (count($scheduled_emails) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Subject</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduled_emails as $email): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($email['recipient_email']); ?></td>
                                        <td><?php echo htmlspecialchars($email['subject']); ?></td>
                                        <td><?php echo date('M d, Y - h:i A', strtotime($email['scheduled_time'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $email['status']; ?>">
                                                <?php echo htmlspecialchars($email['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3>No Scheduled Emails Yet</h3>
                            <p>Start scheduling your first email to see it here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="dashboard-footer">
                <p>© 2025 EmailScheduler. All rights reserved.</p>
            </footer>
        </main>
    </div>
</body>
</html>