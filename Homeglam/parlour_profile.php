<?php
session_start();
include('db.php');

// Check if the user is logged in and is a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php"); // Redirect to login page if not a parlour owner
    exit;
}

// Get parlour owner details from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Fetch parlour details from the database
$sql = "SELECT * FROM parlours WHERE owner_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$parlour = mysqli_fetch_assoc($result);

// If the form is submitted, update the parlour profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $services = mysqli_real_escape_string($conn, $_POST['services']);
    $area = mysqli_real_escape_string($conn, $_POST['area']);

    // Update parlour details in the database (only the fields that exist in the form)
    $update_sql = "UPDATE parlours SET name='$name', address='$address', services='$services', area='$area' 
                   WHERE owner_id = '$user_id'";

    if (mysqli_query($conn, $update_sql)) {
        $success_message = "Your parlour profile has been updated successfully!";
        // Refresh parlour data after update
        $sql = "SELECT * FROM parlours WHERE owner_id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        $parlour = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }
}

// Function to safely get array value
function getValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parlour Profile - Home Glam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        nav a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }

        /* Profile Container */
        .profile-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h2 {
            color: #2c3e50;
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .profile-header p {
            color: #6c757d;
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* Messages */
        .message-container {
            margin-bottom: 2rem;
        }

        .success-message, .error-message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Form Styles */
        .profile-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .form-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .profile-form {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group.full-width {
            grid-column: 1 / -1;
        }

        .input-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-group input,
        .input-group textarea {
            padding: 0.9rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: inherit;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .input-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        /* Button Styles */
        .button-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f3f4;
        }

        .cta-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .cta-button:active {
            transform: translateY(0);
        }

        .back-button {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
        }

        .info-card h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }

        footer p {
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .profile-form {
                padding: 1.5rem;
            }

            .button-container {
                flex-direction: column;
                align-items: center;
            }

            .profile-container {
                padding: 0 1rem;
            }

            .profile-header h2 {
                font-size: 1.8rem;
            }

            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                margin: 1rem auto;
            }

            .form-header {
                padding: 1rem 1.5rem;
            }

            .profile-form {
                padding: 1rem;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .cta-button {
            background: #6c757d;
        }

        /* Form Validation Styles */
        .input-group input:invalid,
        .input-group textarea:invalid {
            border-color: #dc3545;
        }

        .input-group input:valid,
        .input-group textarea:valid {
            border-color: #28a745;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<header>
    <div class="header-content">
        <h1>
            <i class="fas fa-spa"></i>
            Welcome, <?php echo htmlspecialchars($user_name); ?>!
        </h1>
        <nav>
            <ul>
                <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="parlour_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Profile Container Section -->
<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <h2>
            <i class="fas fa-store"></i>
            Your Parlour Profile
        </h2>
        <p>Manage your business information and keep your profile up to date</p>
    </div>

    <!-- Info Cards -->
    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fas fa-user-circle"></i> Owner</h4>
            <p><?php echo htmlspecialchars($user_name); ?></p>
        </div>
        <div class="info-card">
            <h4><i class="fas fa-envelope"></i> Email</h4>
            <p><?php echo htmlspecialchars($user_email); ?></p>
        </div>
        <div class="info-card">
            <h4><i class="fas fa-calendar-alt"></i> Last Updated</h4>
            <p><?php echo date('M d, Y'); ?></p>
        </div>
    </div>

    <!-- Messages -->
    <div class="message-container">
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Form Container -->
    <div class="profile-form-container">
        <div class="form-header">
            <i class="fas fa-edit"></i>
            <h3>Edit Profile Information</h3>
        </div>

        <!-- Profile Form -->
        <form class="profile-form" action="parlour_profile.php" method="POST" id="profileForm">
            <div class="form-grid">
                <div class="input-group">
                    <label for="name">
                        <i class="fas fa-store"></i>
                        Parlour Name:
                    </label>
                    <input type="text" name="name" id="name" 
                           value="<?php echo htmlspecialchars(getValue($parlour, 'name')); ?>" 
                           required placeholder="Enter your parlour name">
                </div>

                <div class="input-group">
                    <label for="area">
                        <i class="fas fa-map-marker-alt"></i>
                        Area:
                    </label>
                    <input type="text" name="area" id="area" 
                           value="<?php echo htmlspecialchars(getValue($parlour, 'area')); ?>" 
                           required placeholder="Enter your area">
                </div>

                <div class="input-group full-width">
                    <label for="address">
                        <i class="fas fa-map-marked-alt"></i>
                        Full Address:
                    </label>
                    <input type="text" name="address" id="address" 
                           value="<?php echo htmlspecialchars(getValue($parlour, 'address')); ?>" 
                           required placeholder="Enter your complete address">
                </div>

                <div class="input-group full-width">
                    <label for="services">
                        <i class="fas fa-list-ul"></i>
                        Services Offered:
                    </label>
                    <textarea name="services" id="services" 
                              required placeholder="Describe all the services you offer (e.g., Haircut, Facial, Manicure, etc.)"><?php echo htmlspecialchars(getValue($parlour, 'services')); ?></textarea>
                </div>
            </div>

            <div class="button-container">
                <a href="parlour_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button type="submit" class="cta-button" id="submitBtn">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Footer Section -->
<footer>
    <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

<script>
// Form submission handling with loading state
document.getElementById('profileForm').addEventListener('submit', function() {
    const submitBtn = document.getElementById('submitBtn');
    const form = this;
    
    // Add loading state
    form.classList.add('loading');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.disabled = true;
});

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Form validation feedback
const inputs = document.querySelectorAll('input, textarea');
inputs.forEach(input => {
    input.addEventListener('blur', function() {
        if (this.checkValidity()) {
            this.style.borderColor = '#28a745';
        } else {
            this.style.borderColor = '#dc3545';
        }
    });
});
</script>

</body>
</html>
