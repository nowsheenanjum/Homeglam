<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

// Get user_id (owner's ID)
$user_id = $_SESSION['user_id'];

// Check if the form is submitted to create the parlour profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get parlour details from the form
    $parlour_name = mysqli_real_escape_string($conn, $_POST['parlour_name']);
    $parlour_address = mysqli_real_escape_string($conn, $_POST['parlour_address']);
    $parlour_services = mysqli_real_escape_string($conn, $_POST['parlour_services']);

    // Insert parlour details into the parlours table
    $sql = "INSERT INTO parlours (owner_id, name, address, services) 
            VALUES ('$user_id', '$parlour_name', '$parlour_address', '$parlour_services')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to parlour dashboard or show success message
        header("Location: parlour_dashboard.php");  // Redirect to the parlour dashboard after successful profile creation
        exit;
    } else {
        $error_message = "Error creating parlour profile: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Parlour Profile - Home Glam</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #7E57C2;
      --primary-light: #B39DDB;
      --primary-dark: #5E35B1;
      --secondary: #FF80AB;
      --accent: #18FFFF;
      --success: #64DD17;
      --warning: #FFD600;
      --danger: #FF5252;
      --dark: #263238;
      --light: #ECEFF1;
      --gray-100: #F5F7FA;
      --gray-200: #E4E7EB;
      --gray-300: #CBD2D9;
      --gray-400: #9AA5B1;
      --gray-500: #7B8794;
      --gray-600: #616E7C;
      --gray-700: #52606D;
      --gray-800: #3E4C59;
      --gray-900: #323F4B;
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      --shadow-md: 0 6px 20px rgba(0, 0, 0, 0.12);
      --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.15);
      --radius: 10px;
      --radius-lg: 16px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
      color: var(--gray-800);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .create-profile-container {
      background: white;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 500px;
      padding: 40px;
      position: relative;
      overflow: hidden;
      border: 1px solid var(--gray-200);
    }

    .create-profile-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    h2 {
      text-align: center;
      color: var(--primary-dark);
      margin-bottom: 30px;
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .error-message {
      background: #FFEBEE;
      color: #C62828;
      padding: 14px;
      border-radius: var(--radius);
      border-left: 4px solid var(--danger);
      margin-bottom: 24px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow-sm);
    }

    .error-message i {
      font-size: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .input-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .input-group label {
      font-weight: 500;
      color: var(--gray-700);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .input-group label i {
      color: var(--primary);
      width: 20px;
    }

    input, textarea {
      width: 100%;
      padding: 16px 18px;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius);
      font-size: 16px;
      transition: var(--transition);
      font-family: inherit;
      background: var(--gray-100);
    }

    input:focus, textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
      background: white;
    }

    textarea {
      resize: vertical;
      min-height: 120px;
    }

    .submit-btn {
      padding: 16px;
      background: linear-gradient(120deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      border-radius: var(--radius);
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: var(--shadow);
      margin-top: 10px;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(126, 87, 194, 0.3);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .form-footer {
      text-align: center;
      margin-top: 24px;
      color: var(--gray-600);
      font-size: 14px;
    }

    .form-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }

    .form-footer a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 640px) {
      .create-profile-container {
        padding: 30px 24px;
      }
      
      h2 {
        font-size: 24px;
      }
      
      input, textarea {
        padding: 14px 16px;
      }
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .create-profile-container {
      animation: fadeIn 0.6s ease-out;
    }
  </style>
</head>
<body>

  <div class="create-profile-container">
    <h2><i class="fas fa-store"></i> Create Your Parlour Profile</h2>

    <!-- Display any error message if exists -->
    <?php if (isset($error_message)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Parlour Profile Form -->
    <form action="create_parlour_profile.php" method="POST">
      <div class="input-group">
        <label for="parlour_name"><i class="fas fa-signature"></i> Parlour Name</label>
        <input type="text" id="parlour_name" name="parlour_name" placeholder="Enter your parlour name" required>
      </div>
      
      <div class="input-group">
        <label for="parlour_address"><i class="fas fa-map-marker-alt"></i> Parlour Address</label>
        <input type="text" id="parlour_address" name="parlour_address" placeholder="Enter your parlour address" required>
      </div>
      
      <div class="input-group">
        <label for="parlour_services"><i class="fas fa-concierge-bell"></i> Services Offered</label>
        <textarea id="parlour_services" name="parlour_services" placeholder="List the services you offer (separated by commas)" required></textarea>
      </div>
      
      <button type="submit" class="submit-btn">
        <i class="fas fa-plus-circle"></i> Create Profile
      </button>
    </form>

    <div class="form-footer">
      <p>Return to <a href="parlour_dashboard.php">Dashboard</a></p>
    </div>
  </div>

</body>
</html>