<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $preferences = mysqli_real_escape_string($conn, $_POST['preferences']);
    
    $profile_picture_path = null;
    $upload_success = true;
    $remove_picture = isset($_POST['remove_picture']) && $_POST['remove_picture'] == '1';
    
    // Handle picture removal
    if ($remove_picture) {
        $old_pic_query = "SELECT profile_picture FROM customers WHERE user_id = '$user_id'";
        $old_pic_result = mysqli_query($conn, $old_pic_query);
        if ($old_pic_result && mysqli_num_rows($old_pic_result) > 0) {
            $old_pic_data = mysqli_fetch_assoc($old_pic_result);
            if ($old_pic_data['profile_picture'] && file_exists($old_pic_data['profile_picture'])) {
                unlink($old_pic_data['profile_picture']);
            }
        }
        $profile_picture_path = ''; // Set to empty string to update database
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pictures/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_error = $_FILES['profile_picture']['error'];
        $file_type = $_FILES['profile_picture']['type'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        // Validate file
        if (in_array($file_ext, $allowed_extensions)) {
            if ($file_size <= 5000000) { // 5MB limit
                // Generate unique filename
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $profile_picture_path = $upload_path;
                    
                    // Delete old profile picture if exists
                    $old_pic_query = "SELECT profile_picture FROM customers WHERE user_id = '$user_id'";
                    $old_pic_result = mysqli_query($conn, $old_pic_query);
                    if ($old_pic_result && mysqli_num_rows($old_pic_result) > 0) {
                        $old_pic_data = mysqli_fetch_assoc($old_pic_result);
                        if ($old_pic_data['profile_picture'] && file_exists($old_pic_data['profile_picture'])) {
                            unlink($old_pic_data['profile_picture']);
                        }
                    }
                } else {
                    $error_message = "Failed to upload profile picture.";
                    $upload_success = false;
                }
            } else {
                $error_message = "Profile picture size should be less than 5MB.";
                $upload_success = false;
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed for profile picture.";
            $upload_success = false;
        }
    }
    
    // Only proceed with database update if upload was successful (or no file was uploaded)
    if ($upload_success) {
        // Check if customer record exists
        $check_sql = "SELECT id FROM customers WHERE user_id = '$user_id'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update existing customer record
            $update_sql = "UPDATE customers SET 
                           first_name = '$first_name',
                           last_name = '$last_name',
                           phone = '$phone',
                           address = '$address',
                           date_of_birth = " . ($date_of_birth ? "'$date_of_birth'" : "NULL") . ",
                           gender = " . ($gender ? "'$gender'" : "NULL") . ",
                           preferences = '$preferences'";
            
            // Add profile picture to update if uploaded or removed
            if ($profile_picture_path !== null) {
                if ($remove_picture) {
                    $update_sql .= ", profile_picture = NULL";
                } else {
                    $update_sql .= ", profile_picture = '$profile_picture_path'";
                }
            }
            
            $update_sql .= " WHERE user_id = '$user_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $success_message = "Profile updated successfully";
            } else {
                $error_message = "Error updating profile: " . mysqli_error($conn);
            }
        } else {
            // Insert new customer record
            $insert_sql = "INSERT INTO customers (user_id, first_name, last_name, phone, address, date_of_birth, gender, preferences, profile_picture) 
                           VALUES ('$user_id', '$first_name', '$last_name', '$phone', '$address', 
                           " . ($date_of_birth ? "'$date_of_birth'" : "NULL") . ", 
                           " . ($gender ? "'$gender'" : "NULL") . ", '$preferences', 
                           " . ($profile_picture_path ? "'$profile_picture_path'" : "NULL") . ")";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = "Profile created successfully";
            } else {
                $error_message = "Error creating profile: " . mysqli_error($conn);
            }
        }
    }
}

// Fetch current customer data
$sql = "SELECT u.name, u.email, c.* 
        FROM users u 
        LEFT JOIN customers c ON u.id = c.user_id 
        WHERE u.id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user_data = mysqli_fetch_assoc($result);

// If no customer record exists, create default values
if (!$user_data['first_name']) {
    $name_parts = explode(' ', $user_data['name']);
    $user_data['first_name'] = $name_parts[0];
    $user_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - Home Glam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e91e63;
            --primary-dark: #c2185b;
            --primary-light: #f8bbd0;
            --secondary: #f48fb1;
            --success: #a5d6a7;
            --success-text: #2e7d32;
            --warning: #ffe0b2;
            --warning-text: #f57c00;
            --danger: #ffcdd2;
            --danger-text: #c62828;
            --light-pink: #fce4ec;
            --lighter-pink: #faf2f5;
            --dark-pink: #ad1457;
            --gray: #cccccc;
            --light-gray: #eeeeee;
            --dark-gray: #666666;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.1);
            --shadow-lg: 0 15px 25px rgba(0,0,0,0.1);
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--light-pink) 0%, var(--lighter-pink) 100%);
            color: #333333;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(233, 30, 99, 0.2);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius);
            transition: var(--transition);
            background: rgba(233, 30, 99, 0.1);
            border: 1px solid rgba(233, 30, 99, 0.2);
        }

        .back-button:hover {
            background: rgba(233, 30, 99, 0.2);
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--dark-gray);
            font-size: 0.875rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: 2px solid rgba(233, 30, 99, 0.2);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Status Messages */
        .alert {
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: var(--radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid;
        }

        .alert.success {
            background: var(--success);
            color: var(--success-text);
            border-color: rgba(46, 125, 50, 0.3);
        }

        .alert.error {
            background: var(--danger);
            color: var(--danger-text);
            border-color: rgba(198, 40, 40, 0.3);
        }

        /* Profile Stats */
        .profile-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.75rem;
            border-radius: var(--radius);
            border: 1px solid rgba(233, 30, 99, 0.1);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }

        .stat-card-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark-gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card-icon.primary {
            background: rgba(233, 30, 99, 0.2);
            color: var(--primary);
        }

        .stat-card-icon.success {
            background: rgba(165, 214, 167, 0.5);
            color: var(--success-text);
        }

        .stat-card-icon.warning {
            background: rgba(255, 224, 178, 0.5);
            color: var(--warning-text);
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-card-description {
            font-size: 0.875rem;
            color: var(--dark-gray);
        }

        /* Form Sections */
        .form-container {
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid rgba(233, 30, 99, 0.1);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-section {
            padding: 2.5rem;
            border-bottom: 1px solid rgba(233, 30, 99, 0.1);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(233, 30, 99, 0.1);
        }

        .section-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: var(--radius);
            background: rgba(233, 30, 99, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.25rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .section-description {
            font-size: 0.95rem;
            color: var(--dark-gray);
            margin-top: 0.5rem;
        }

        /* Profile Picture Upload */
        .profile-picture-section {
            display: flex;
            align-items: flex-start;
            gap: 2.5rem;
            margin-bottom: 2rem;
        }

        .profile-picture-preview {
            flex-shrink: 0;
        }

        .profile-picture-container {
            position: relative;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(233, 30, 99, 0.2);
            background: rgba(233, 30, 99, 0.1);
            box-shadow: var(--shadow);
        }

        .profile-picture-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
        }

        .profile-picture-upload {
            flex: 1;
        }

        .upload-info {
            margin-bottom: 1.5rem;
        }

        .upload-info h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .upload-info p {
            font-size: 0.95rem;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .file-input-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-input-button {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .file-input-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }

        .remove-picture-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 205, 210, 0.5);
            color: var(--danger-text);
            border: 1px solid rgba(198, 40, 40, 0.3);
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .remove-picture-btn:hover {
            background: rgba(255, 205, 210, 0.8);
            transform: translateY(-1px);
        }

        /* Form Elements */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .form-label.required::after {
            content: " *";
            color: var(--danger-text);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid rgba(233, 30, 99, 0.2);
            border-radius: var(--radius);
            font-size: 1rem;
            background: var(--white);
            color: #333333;
            transition: var(--transition);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.2);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input.readonly {
            background: var(--light-gray);
            color: var(--dark-gray);
            cursor: not-allowed;
            border-color: rgba(233, 30, 99, 0.1);
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--dark-gray);
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: rgba(233, 30, 99, 0.1);
            color: var(--primary);
            border: 1px solid rgba(233, 30, 99, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(233, 30, 99, 0.2);
            transform: translateY(-1px);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1.25rem;
            padding: 2.5rem;
            background: rgba(252, 228, 236, 0.3);
            border-top: 1px solid rgba(233, 30, 99, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .container {
                padding: 1.5rem;
            }

            .header-content {
                padding: 1rem 1.5rem;
            }

            .profile-overview {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .form-section {
                padding: 2rem;
            }

            .form-actions {
                flex-direction: column;
                padding: 2rem;
            }

            .profile-picture-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 2rem;
            }

            .profile-picture-container {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-title {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .user-info {
                display: none;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card,
        .form-container {
            animation: fadeIn 0.6s ease-out;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(233, 30, 99, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="header-title">
                <a href="customer_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <h1>Profile Management</h1>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <!-- Status Messages -->
        <?php if ($success_message): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Overview -->
        <div class="profile-overview">
            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Account Status</span>
                    <div class="stat-card-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-card-value">Active</div>
                <div class="stat-card-description">Account in good standing</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Profile Completion</span>
                    <div class="stat-card-icon <?php echo ($user_data['first_name'] && $user_data['last_name'] && $user_data['phone']) ? 'success' : 'warning'; ?>">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
                <div class="stat-card-value">
                    <?php 
                    $completion = 0;
                    if ($user_data['first_name']) $completion += 20;
                    if ($user_data['last_name']) $completion += 20;
                    if ($user_data['phone']) $completion += 20;
                    if ($user_data['address']) $completion += 20;
                    if ($user_data['profile_picture']) $completion += 20;
                    echo $completion . '%';
                    ?>
                </div>
                <div class="stat-card-description">
                    <?php echo $completion < 100 ? 'Complete your profile' : 'Profile completed'; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <span class="stat-card-title">Member Since</span>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-card-value">
                    <?php echo date('M Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                </div>
                <div class="stat-card-description">Loyalty member</div>
            </div>
        </div>

        <!-- Profile Form -->
        <form method="POST" action="profile.php" enctype="multipart/form-data">
            <div class="form-container">
                <!-- Profile Picture Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div>
                            <h3 class="section-title">Profile Picture</h3>
                            <p class="section-description">Upload a profile picture to personalize your account</p>
                        </div>
                    </div>

                    <div class="profile-picture-section">
                        <div class="profile-picture-preview">
                            <div class="profile-picture-container">
                                <?php if ($user_data['profile_picture'] && file_exists($user_data['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                                         alt="Profile Picture" class="profile-picture-img" id="profileImg">
                                <?php else: ?>
                                    <div class="profile-picture-placeholder" id="profilePlaceholder">
                                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="profile-picture-upload">
                            <div class="upload-info">
                                <h4>Upload Profile Picture</h4>
                                <p>Choose a clear photo of yourself for your profile.</p>
                                <p>• Accepted formats: JPG, JPEG, PNG, GIF</p>
                                <p>• Maximum size: 5MB</p>
                                <p>• Recommended: Square image, at least 200x200 pixels</p>
                            </div>

                            <div class="file-input-container">
                                <input type="file" name="profile_picture" id="profilePictureInput" 
                                       class="file-input" accept="image/*" onchange="previewImage(this)">
                                <label for="profilePictureInput" class="file-input-button">
                                    <i class="fas fa-upload"></i>
                                    Choose Picture
                                </label>
                            </div>

                            <?php if ($user_data['profile_picture']): ?>
                                <button type="button" class="remove-picture-btn" onclick="removePicture()">
                                    <i class="fas fa-trash"></i>
                                    Remove Picture
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h3 class="section-title">Account Information</h3>
                            <p class="section-description">Your login credentials and account details</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-input readonly" 
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                            <div class="form-help">Contact support to change your email address</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input readonly" 
                                   value="<?php echo htmlspecialchars($user_data['name']); ?>" readonly>
                            <div class="form-help">Username cannot be modified</div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h3 class="section-title">Personal Information</h3>
                            <p class="section-description">Your personal details and contact information</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">First Name</label>
                            <input type="text" name="first_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Last Name</label>
                            <input type="text" name="last_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" 
                                   placeholder="+880 1234-567890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-input" 
                                   value="<?php echo $user_data['date_of_birth'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user_data['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user_data['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user_data['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" 
                                  placeholder="Enter your complete address including area, city, and postal code"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Service Preferences -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h3 class="section-title">Service Preferences</h3>
                            <p class="section-description">Help us provide better personalized service recommendations</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preferences & Special Requirements</label>
                        <textarea name="preferences" class="form-textarea" 
                                  placeholder="Please share any service preferences, skin/hair concerns, allergies, or special requirements that will help our partners serve you better..."><?php echo htmlspecialchars($user_data['preferences'] ?? ''); ?></textarea>
                        <div class="form-help">This information helps parlours provide personalized service recommendations</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="customer_dashboard.php" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var profileImg = document.getElementById('profileImg');
                    var profilePlaceholder = document.getElementById('profilePlaceholder');
                    var container = document.querySelector('.profile-picture-container');
                    
                    if (profileImg) {
                        profileImg.src = e.target.result;
                    } else {
                        // Replace placeholder with image
                        container.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture" class="profile-picture-img" id="profileImg">';
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removePicture() {
            if (confirm('Are you sure you want to remove your profile picture?')) {
                // Create a hidden input to mark picture for removal
                var form = document.querySelector('form');
                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_picture';
                hiddenInput.value = '1';
                form.appendChild(hiddenInput);
                
                // Submit the form
                form.submit();
            }
        }

        // File input validation
        document.getElementById('profilePictureInput').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                // Check file size (5MB = 5 * 1024 * 1024 bytes)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB. Please choose a smaller image.');
                    e.target.value = '';
                    return;
                }
                
                // Check file type
                var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, JPEG, PNG, or GIF).');
                    e.target.value = '';
                    return;
                }
            }
        });
    </script>

</body>
</html>