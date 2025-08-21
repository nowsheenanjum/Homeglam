<?php
// ===============================
// Professional Customer Dashboard
// ===============================
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID

// Fetch notifications for the customer
$sql = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC"; 
$result = mysqli_query($conn, $sql);

// Check for any query errors
if (!$result) {
    die("Error fetching notifications: " . mysqli_error($conn));
}

// Check if customer has completed their profile (updated to include profile picture)
$profile_check_sql = "SELECT c.*, u.name, u.email 
                      FROM users u 
                      LEFT JOIN customers c ON u.id = c.user_id 
                      WHERE u.id = '$user_id'";
$profile_result = mysqli_query($conn, $profile_check_sql);
$user_profile = mysqli_fetch_assoc($profile_result);

$profile_incomplete = !$user_profile['first_name'] || !$user_profile['last_name'];

// Get favorite parlours count
$favorites_count_sql = "SELECT COUNT(*) as count FROM favorites WHERE customer_id = '$user_id'";
$favorites_count_result = mysqli_query($conn, $favorites_count_sql);
$favorites_count = mysqli_fetch_assoc($favorites_count_result)['count'];

// Get unread messages count
$unread_messages_sql = "SELECT COUNT(*) as count FROM messages WHERE customer_id = '$user_id' AND sender_type = 'parlour_owner' AND is_read = FALSE";
$unread_messages_result = mysqli_query($conn, $unread_messages_sql);
$unread_messages_count = mysqli_fetch_assoc($unread_messages_result)['count'];

// Calculate profile completion percentage
$completion = 0;
if ($user_profile['first_name']) $completion += 20;
if ($user_profile['last_name']) $completion += 20;
if ($user_profile['phone']) $completion += 20;
if ($user_profile['address']) $completion += 20;
if ($user_profile['profile_picture']) $completion += 20;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Home Glam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(rgba(248, 250, 252, 0.95), rgba(248, 250, 252, 0.95)), 
                        url('images/customer_dashboard_background.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .user-welcome {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.125rem;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        .nav-links a:hover {
            background: rgba(241, 245, 249, 0.8);
            color: #334155;
        }

        /* Message notification badge */
        .message-badge {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            background: #dc2626;
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.125rem 0.375rem;
            border-radius: 50px;
            min-width: 1.25rem;
            text-align: center;
            line-height: 1;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Profile Alert */
        .profile-alert {
            background: rgba(254, 243, 199, 0.95);
            backdrop-filter: blur(5px);
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 1rem 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            font-weight: 500;
        }

        .profile-alert a {
            background: #1f2937;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .profile-alert a:hover {
            background: #111827;
        }

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
        }

        /* Sidebar Styles */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .sidebar-card-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.5);
        }

        .sidebar-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-card-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            background: rgba(248, 250, 252, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ec4899;
        }

        .sidebar-card-content {
            padding: 1.5rem;
        }

        /* Profile Summary */
        .profile-summary {
            text-align: center;
        }

        .user-avatar-large {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
        }

        .user-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-avatar-large-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .profile-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .profile-email {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .profile-stat {
            text-align: center;
        }

        .profile-stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
        }

        .profile-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .profile-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            flex: 1;
        }

        .btn-primary {
            background: #ec4899;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #db2777;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: rgba(249, 250, 251, 0.9);
        }

        /* Quick Actions Buttons */
        .quick-action-btn {
            background: #dc2626;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            width: 100%;
            position: relative;
        }

        .quick-action-btn:hover {
            background: #b91c1c;
        }

        .quick-action-btn.messages-btn {
            background: #ec4899;
        }

        .quick-action-btn.messages-btn:hover {
            background: #db2777;
        }

        .quick-action-btn .badge {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            background: #fbbf24;
            color: #92400e;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.125rem 0.375rem;
            border-radius: 50px;
            min-width: 1.25rem;
            text-align: center;
            line-height: 1;
        }

        /* Notifications */
        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.5);
            transition: all 0.2s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: rgba(248, 250, 252, 0.5);
        }

        .notification-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .notification-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .notification-status.unread {
            background: rgba(219, 234, 254, 0.8);
            color: #1e40af;
        }

        .notification-status.read {
            background: rgba(243, 244, 246, 0.8);
            color: #6b7280;
        }

        .notification-message {
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }

        .notification-action {
            color: #ec4899;
            text-decoration: none;
            font-weight: 500;
        }

        .notification-action:hover {
            color: #db2777;
        }

        .no-notifications {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 2rem;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .content-card-header {
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.5);
        }

        .content-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .content-card-description {
            font-size: 0.875rem;
            color: #64748b;
        }

        .content-card-body {
            padding: 2rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }

        .search-section {
            position: relative;
        }

        .search-toggle {
            background: #ec4899;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .search-toggle:hover {
            background: #db2777;
        }

        .search-form {
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(5px);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
            margin-bottom: 1.5rem;
        }

        .search-form.hidden {
            display: none;
        }

        /* Tab Styles */
        .tab-container {
            margin-bottom: 2rem;
        }

        .tab-buttons {
            display: flex;
            border-bottom: 2px solid rgba(226, 232, 240, 0.5);
            margin-bottom: 2rem;
        }

        .tab-button {
            background: none;
            border: none;
            padding: 1rem 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab-button.active {
            color: #ec4899;
            border-bottom-color: #ec4899;
        }

        .tab-button:hover {
            color: #ec4899;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Parlour Cards */
        .parlour-grid {
            display: grid;
            gap: 1.5rem;
        }

        .parlour-card {
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(5px);
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            transition: all 0.2s;
            position: relative;
        }

        .parlour-card:hover {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .favorite-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #d1d5db;
            transition: all 0.2s;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .favorite-btn:hover {
            background: rgba(243, 244, 246, 0.8);
        }

        .favorite-btn.favorited {
            color: #dc2626;
        }

        .parlour-info {
            flex: 1;
            padding-right: 3rem;
        }

        .parlour-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .parlour-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .parlour-detail {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .parlour-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-width: 140px;
        }

        .already-reviewed {
            background: rgba(243, 244, 246, 0.8);
            color: #6b7280;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Profile completion indicator */
        .profile-completion {
            position: relative;
            margin-bottom: 1rem;
        }

        .completion-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .completion-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .completion-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
            text-align: center;
        }

        /* Loading and empty states */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }

        /* Footer */
        .footer {
            background: rgba(31, 41, 55, 0.9);
            backdrop-filter: blur(10px);
            color: #d1d5db;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .sidebar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .header-nav {
                width: 100%;
                justify-content: space-between;
            }

            .nav-links {
                gap: 1rem;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .sidebar {
                grid-template-columns: 1fr;
            }

            .parlour-card {
                flex-direction: column;
                align-items: stretch;
            }

            .parlour-info {
                padding-right: 0;
            }

            .parlour-actions {
                min-width: auto;
            }

            .content-card-header,
            .content-card-body {
                padding: 1.5rem;
            }

            .user-menu {
                display: none;
            }

            .tab-buttons {
                overflow-x: auto;
            }

            .tab-button {
                white-space: nowrap;
                min-width: 120px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header class="header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-logo">HG</div>
                <div>
                    <h1>Home Glam</h1>
                    <div class="user-welcome">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                </div>
            </div>
            <div class="header-nav">
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="booking.php"><i class="fas fa-calendar"></i> Bookings</a></li>
                        <li>
                            <a href="customer_messages.php">
                                <i class="fas fa-comments"></i> Messages
                                <?php if ($unread_messages_count > 0): ?>
                                    <span class="message-badge"><?php echo $unread_messages_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><a href="index.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php if ($user_profile['profile_picture'] && file_exists($user_profile['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>" 
                                 alt="Profile Picture">
                        <?php else: ?>
                            <div class="user-avatar-placeholder">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Profile Completion Alert -->
    <?php if ($profile_incomplete): ?>
    <div class="profile-alert">
        <i class="fas fa-exclamation-triangle"></i>
        <span><strong>Action Required:</strong> Complete your profile to unlock personalized recommendations and enhanced booking features.</span>
        <a href="profile.php">Complete Profile</a>
    </div>
    <?php endif; ?>

    <!-- Dashboard Content Section -->
    <div class="dashboard-container">
        <aside class="sidebar">
            <!-- Profile Summary -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <h3 class="sidebar-card-title">
                        <div class="sidebar-card-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        Profile Overview
                    </h3>
                </div>
                <div class="sidebar-card-content">
                    <div class="profile-summary">
                        <div class="user-avatar-large">
                            <?php if ($user_profile['profile_picture'] && file_exists($user_profile['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>" 
                                     alt="Profile Picture">
                            <?php else: ?>
                                <div class="user-avatar-large-placeholder">
                                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Profile Completion Bar -->
                        <div class="profile-completion">
                            <div class="completion-bar">
                                <div class="completion-fill" style="width: <?php echo $completion; ?>%"></div>
                            </div>
                            <div class="completion-text">
                                Profile <?php echo $completion; ?>% Complete
                            </div>
                        </div>
                        
                        <?php if ($user_profile['first_name']): ?>
                            <div class="profile-name"><?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?></div>
                            <div class="profile-email"><?php echo htmlspecialchars($user_profile['email']); ?></div>
                            <div class="profile-stats">
                                <div class="profile-stat">
                                    <div class="profile-stat-value"><?php echo $completion; ?>%</div>
                                    <div class="profile-stat-label">Complete</div>
                                </div>
                                <div class="profile-stat">
                                    <div class="profile-stat-value"><?php echo $favorites_count; ?></div>
                                    <div class="profile-stat-label">Favorites</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="profile-name">Profile Incomplete</div>
                            <div class="profile-email"><?php echo htmlspecialchars($user_profile['email']); ?></div>
                        <?php endif; ?>
                        <div class="profile-actions">
                            <a href="profile.php" class="btn btn-primary">
                                <i class="fas fa-edit"></i>
                                <?php echo $profile_incomplete ? 'Complete' : 'Edit'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <h3 class="sidebar-card-title">
                        <div class="sidebar-card-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        Quick Actions
                    </h3>
                </div>
                <div class="sidebar-card-content">
                    <button class="quick-action-btn" onclick="showFavorites()">
                        <i class="fas fa-heart"></i>
                        My Favorites (<?php echo $favorites_count; ?>)
                    </button>
                    <button class="quick-action-btn messages-btn" onclick="window.location.href='customer_messages.php'">
                        <i class="fas fa-comments"></i>
                        Messages
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="badge"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

            <!-- Notifications -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <h3 class="sidebar-card-title">
                        <div class="sidebar-card-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        Notifications
                    </h3>
                </div>
                <div class="notifications-list">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($notification = mysqli_fetch_assoc($result)): ?>
                            <div class="notification-item">
                                <div class="notification-header">
                                    <span class="notification-status <?php echo $notification['status']; ?>">
                                        <i class="fas fa-<?php echo $notification['status'] == 'unread' ? 'circle' : 'check-circle'; ?>"></i>
                                        <?php echo ucfirst($notification['status']); ?>
                                    </span>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-meta">
                                    <span><?php echo date('M j, Y', strtotime($notification['created_at'])); ?></span>
                                    <div class="notification-actions">
                                        <?php if ($notification['status'] == 'unread'): ?>
                                            <a href="mark_as_read.php?id=<?php echo $notification['id']; ?>" class="notification-action">
                                                Mark as Read
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="fas fa-inbox"></i><br>
                            No notifications available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Main Content Section -->
        <div class="main-content">
            <!-- Tab Container -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="showTab('reviews')">
                        <i class="fas fa-star"></i> Reviews
                    </button>
                    <button class="tab-button" onclick="showTab('search')">
                        <i class="fas fa-search"></i> Find Parlours
                    </button>
                    <button class="tab-button" onclick="showTab('favorites')">
                        <i class="fas fa-heart"></i> My Favorites
                    </button>
                </div>

                <!-- Review Tab -->
                <div id="reviews-tab" class="tab-content active">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h3 class="content-card-title">
                                <i class="fas fa-star"></i>
                                Leave a Review
                            </h3>
                            <p class="content-card-description">Share your experience and help other customers discover great parlours</p>
                        </div>
                        <div class="content-card-body">
                            <button class="search-toggle" onclick="toggleReviewSearch()">
                                <i class="fas fa-search"></i>
                                Find Parlour to Review
                            </button>
                            
                            <div id="review-search" class="search-form hidden">
                                <form action="customer_dashboard.php" method="POST">
                                    <div class="form-group">
                                        <label class="form-label">Search Parlours</label>
                                        <input type="text" name="search_parlour" class="form-input" 
                                               placeholder="Enter parlour name or area" required>
                                    </div>
                                    <button type="submit" name="search_for_review" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                        Search Parlours
                                    </button>
                                </form>
                            </div>

                            <?php
                            if (isset($_POST['search_for_review']) && !empty($_POST['search_parlour'])) {
                                $search_term = mysqli_real_escape_string($conn, $_POST['search_parlour']);
                                $parlour_sql = "SELECT p.*, 
                                               (SELECT COUNT(*) FROM parlour_reviews WHERE parlour_id = p.id AND customer_id = '$user_id') as already_reviewed,
                                               (SELECT COUNT(*) FROM favorites WHERE parlour_id = p.id AND customer_id = '$user_id') as is_favorite
                                               FROM parlours p 
                                               WHERE p.name LIKE '%$search_term%' OR p.area LIKE '%$search_term%' OR p.address LIKE '%$search_term%'";
                                $parlour_result = mysqli_query($conn, $parlour_sql);

                                if (mysqli_num_rows($parlour_result) > 0) {
                                    echo "<div class='parlour-grid'>";
                                    while ($parlour = mysqli_fetch_assoc($parlour_result)) {
                                        echo "<div class='parlour-card'>
                                                <button class='favorite-btn " . ($parlour['is_favorite'] ? 'favorited' : '') . "' 
                                                        onclick='toggleFavorite(" . $parlour['id'] . ", this)'>
                                                    <i class='fas fa-heart'></i>
                                                </button>
                                                <div class='parlour-info'>
                                                    <h4 class='parlour-name'>" . htmlspecialchars($parlour['name']) . "</h4>
                                                    <div class='parlour-details'>
                                                        <div class='parlour-detail'>
                                                            <i class='fas fa-map-marker-alt'></i>
                                                            " . htmlspecialchars($parlour['area']) . "
                                                        </div>
                                                        <div class='parlour-detail'>
                                                            <i class='fas fa-home'></i>
                                                            " . htmlspecialchars($parlour['address']) . "
                                                        </div>
                                                        <div class='parlour-detail'>
                                                            <i class='fas fa-cut'></i>
                                                            " . htmlspecialchars($parlour['services']) . "
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='parlour-actions'>";
                                        
                                        if ($parlour['already_reviewed'] > 0) {
                                            echo "<span class='already-reviewed'>
                                                    <i class='fas fa-check'></i>
                                                    Already Reviewed
                                                  </span>";
                                        } else {
                                            echo "<a href='leave_review.php?parlour_id=" . $parlour['id'] . "' class='btn btn-primary'>
                                                    <i class='fas fa-star'></i>
                                                    Write Review
                                                  </a>";
                                        }
                                        
                                        echo "  <a href='customer_messages.php?parlour_id=" . $parlour['owner_id'] . "' class='btn btn-secondary'>
                                                    <i class='fas fa-comment'></i>
                                                    Message
                                                </a>
                                              </div>
                                            </div>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<p>No parlours found matching '" . htmlspecialchars($search_term) . "'.</p>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Search Tab -->
                <div id="search-tab" class="tab-content">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h3 class="content-card-title">
                                <i class="fas fa-search"></i>
                                Find Parlours
                            </h3>
                            <p class="content-card-description">Discover beauty parlours in your area and book services</p>
                        </div>
                        <div class="content-card-body">
                            <form action="customer_dashboard.php" method="POST">
                                <div class="form-group">
                                    <label class="form-label">Enter Location</label>
                                    <input type="text" name="area" class="form-input" 
                                           placeholder="e.g., Dhaka, Gulshan, Dhanmondi" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Search Parlours
                                </button>
                            </form>

                            <?php
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['area']) && !isset($_POST['search_for_review'])) {
                                $area = mysqli_real_escape_string($conn, $_POST['area']);
                                $sql = "SELECT p.*, 
                                       (SELECT COUNT(*) FROM favorites WHERE parlour_id = p.id AND customer_id = '$user_id') as is_favorite
                                       FROM parlours p 
                                       WHERE p.area LIKE '%$area%'";
                                $search_result = mysqli_query($conn, $sql);

                                if (mysqli_num_rows($search_result) > 0) {
                                    echo "<div class='parlour-grid' style='margin-top: 2rem;'>";
                                    while ($parlour = mysqli_fetch_assoc($search_result)) {
                                        echo "<div class='parlour-card'>
                                                <button class='favorite-btn " . ($parlour['is_favorite'] ? 'favorited' : '') . "' 
                                                        onclick='toggleFavorite(" . $parlour['id'] . ", this)'>
                                                    <i class='fas fa-heart'></i>
                                                </button>
                                                <div class='parlour-info'>
                                                    <h4 class='parlour-name'>" . htmlspecialchars($parlour['name']) . "</h4>
                                                    <div class='parlour-details'>
                                                        <div class='parlour-detail'>
                                                            <i class='fas fa-map-marker-alt'></i>
                                                            " . htmlspecialchars($parlour['area']) . "
                                                        </div>
                                                        <div class='parlour-detail'>
                                                            <i class='fas fa-home'></i>
                                                            " . htmlspecialchars($parlour['address']) . "
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='parlour-actions'>
                                                    <a href='book_service.php?parlour_id=" . $parlour['id'] . "' class='btn btn-primary'>
                                                        <i class='fas fa-calendar-plus'></i>
                                                        Book Service
                                                    </a>
                                                    <a href='customer_messages.php?parlour_id=" . $parlour['owner_id'] . "' class='btn btn-secondary'>
                                                        <i class='fas fa-comment'></i>
                                                        Message
                                                    </a>
                                                </div>
                                              </div>";
                                    }
                                    echo "</div>";
                                } else {
                                    echo "<p style='margin-top: 2rem; text-align: center; color: #6b7280;'>No parlours found in " . htmlspecialchars($area) . ".</p>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Favorites Tab -->
                <div id="favorites-tab" class="tab-content">
                    <div class="content-card">
                        <div class="content-card-header">
                            <h3 class="content-card-title">
                                <i class="fas fa-heart"></i>
                                My Favorite Parlours
                            </h3>
                            <p class="content-card-description">Quick access to your saved parlours for easy booking</p>
                        </div>
                        <div class="content-card-body">
                            <div id="favorites-content">
                                <div class="loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    Loading your favorites...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <p>&copy; 2025 Home Glam. All rights reserved.</p>
    </footer>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Load favorites when favorites tab is clicked
            if (tabName === 'favorites') {
                loadFavorites();
            }
        }

        // Toggle review search form
        function toggleReviewSearch() {
            const searchForm = document.getElementById('review-search');
            searchForm.classList.toggle('hidden');
        }

        // Show favorites (for sidebar button)
        function showFavorites() {
            showTab('favorites');
            // Update tab button active state
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelector('.tab-button:nth-child(3)').classList.add('active');
        }

        // Toggle favorite status
        async function toggleFavorite(parlourId, button) {
            try {
                const isFavorited = button.classList.contains('favorited');
                const action = isFavorited ? 'remove' : 'add';
                
                const formData = new FormData();
                formData.append('action', action);
                formData.append('parlour_id', parlourId);
                
                const response = await fetch('favorites.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    button.classList.toggle('favorited');
                    
                    // Update favorites count in sidebar
                    const currentCount = parseInt(document.querySelector('.quick-action-btn').textContent.match(/\d+/)[0]);
                    const newCount = isFavorited ? currentCount - 1 : currentCount + 1;
                    document.querySelector('.quick-action-btn').innerHTML = 
                        `<i class="fas fa-heart"></i> My Favorites (${newCount})`;
                    
                    // Update profile stat
                    const profileStat = document.querySelector('.profile-stat-value');
                    if (profileStat && profileStat.parentElement.querySelector('.profile-stat-label').textContent === 'Favorites') {
                        profileStat.textContent = newCount;
                    }
                    
                    // If we're on the favorites tab, reload it
                    if (document.getElementById('favorites-tab').classList.contains('active')) {
                        loadFavorites();
                    }
                } else {
                    alert(result.message || 'Failed to update favorite');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update favorite. Please try again.');
            }
        }

        // Load favorites
        async function loadFavorites() {
            const favoritesContent = document.getElementById('favorites-content');
            
            try {
                favoritesContent.innerHTML = `
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Loading your favorites...
                    </div>
                `;
                
                const response = await fetch('favorites.php?action=get_favorites');
                const result = await response.json();
                
                if (result.success) {
                    if (result.favorites.length === 0) {
                        favoritesContent.innerHTML = `
                            <div class="empty-state">
                                <i class="fas fa-heart"></i>
                                <h3>No Favorites Yet</h3>
                                <p>Start adding parlours to your favorites for quick access!</p>
                            </div>
                        `;
                    } else {
                        let favoritesHtml = '<div class="parlour-grid">';
                        
                        result.favorites.forEach(parlour => {
                            favoritesHtml += `
                                <div class="parlour-card">
                                    <button class="favorite-btn favorited" 
                                            onclick="toggleFavorite(${parlour.id}, this)">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <div class="parlour-info">
                                        <h4 class="parlour-name">${escapeHtml(parlour.name)}</h4>
                                        <div class="parlour-details">
                                            <div class="parlour-detail">
                                                <i class="fas fa-map-marker-alt"></i>
                                                ${escapeHtml(parlour.area)}
                                            </div>
                                            <div class="parlour-detail">
                                                <i class="fas fa-home"></i>
                                                ${escapeHtml(parlour.address)}
                                            </div>
                                            <div class="parlour-detail">
                                                <i class="fas fa-calendar-plus"></i>
                                                Added ${new Date(parlour.favorited_at).toLocaleDateString()}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="parlour-actions">
                                        <a href="book_service.php?parlour_id=${parlour.id}" class="btn btn-primary">
                                            <i class="fas fa-calendar-plus"></i>
                                            Book Service
                                        </a>
                                        <a href="customer_messages.php?parlour_id=${parlour.owner_id}" class="btn btn-secondary">
                                            <i class="fas fa-comment"></i>
                                            Message
                                        </a>
                                    </div>
                                </div>
                            `;
                        });
                        
                        favoritesHtml += '</div>';
                        favoritesContent.innerHTML = favoritesHtml;
                    }
                } else {
                    throw new Error(result.message || 'Failed to load favorites');
                }
            } catch (error) {
                console.error('Error:', error);
                favoritesContent.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Error Loading Favorites</h3>
                        <p>Please try refreshing the page.</p>
                    </div>
                `;
            }
        }

        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load favorite IDs on page load to set initial heart states
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                const response = await fetch('favorites.php?action=get_favorite_ids');
                const result = await response.json();
                
                if (result.success) {
                    // Set initial favorite states for any parlour cards on the page
                    result.favorite_ids.forEach(parlourId => {
                        const favoriteButtons = document.querySelectorAll(`[onclick*="toggleFavorite(${parlourId},"]`);
                        favoriteButtons.forEach(button => {
                            button.classList.add('favorited');
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading favorite states:', error);
            }
        });
    </script>

</body>
</html>

