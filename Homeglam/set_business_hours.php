<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle business hours form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_hours'])) {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // Delete any existing business hours for this parlour
    $delete_sql = "DELETE FROM business_hours WHERE parlour_id = '$user_id'";
    mysqli_query($conn, $delete_sql); // Optional: Remove previous hours

    // Insert new business hours
    foreach ($days as $day) {
        $open_time = mysqli_real_escape_string($conn, $_POST["{$day}_open"]);
        $close_time = mysqli_real_escape_string($conn, $_POST["{$day}_close"]);
        
        // Insert each day's business hours into the database
        $sql = "INSERT INTO business_hours (parlour_id, day_of_week, open_time, close_time) 
                VALUES ('$user_id', '$day', '$open_time', '$close_time')";
        
        if (!mysqli_query($conn, $sql)) {
            $error_message = "Error saving business hours: " . mysqli_error($conn);
            break;
        }
    }

    // If no error, success message
    if (!isset($error_message)) {
        $success_message = "Business hours saved successfully!";
    }
}

// Fetch current business hours
$sql = "SELECT * FROM business_hours WHERE parlour_id = '$user_id' ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Business Hours Management - Home Glam</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4F46E5;
      --primary-dark: #4338CA;
      --primary-light: #6366F1;
      --secondary: #EC4899;
      --success: #10B981;
      --warning: #F59E0B;
      --danger: #EF4444;
      --light-bg: #F9FAFB;
      --light-border: #E5E7EB;
      --dark-text: #1F2937;
      --gray-text: #6B7280;
      --white: #FFFFFF;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --radius: 8px;
      --radius-lg: 12px;
      --radius-xl: 16px;
      --transition: all 0.2s ease-in-out;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background-color: var(--light-bg);
      color: var(--dark-text);
      line-height: 1.6;
    }

    /* Header Styles */
    .header {
      background: var(--white);
      border-bottom: 1px solid var(--light-border);
      padding: 1rem 0;
      box-shadow: var(--shadow-sm);
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
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
      padding: 0.5rem 1rem;
      border-radius: var(--radius);
      transition: var(--transition);
    }

    .back-button:hover {
      background: rgba(79, 70, 229, 0.1);
    }

    .header h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .nav-link {
      color: var(--gray-text);
      text-decoration: none;
      font-weight: 500;
      padding: 0.5rem 1rem;
      border-radius: var(--radius);
      transition: var(--transition);
    }

    .nav-link:hover {
      color: var(--primary);
      background: rgba(79, 70, 229, 0.1);
    }

    /* Main Content */
    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 2rem;
    }

    /* Card Styles */
    .card {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--light-border);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .card-header {
      padding: 1.5rem 2rem;
      border-bottom: 1px solid var(--light-border);
      background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
      color: var(--white);
    }

    .card-header h2 {
      font-size: 1.5rem;
      font-weight: 600;
    }

    .card-body {
      padding: 2rem;
    }

    /* Messages */
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.1);
      color: #065F46;
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.1);
      color: #991B1B;
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Form Styles */
    .days-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2.5rem;
    }

    .day-card {
      background: var(--light-bg);
      padding: 1.5rem;
      border-radius: var(--radius);
      border: 1px solid var(--light-border);
      transition: var(--transition);
    }

    .day-card:hover {
      border-color: var(--primary-light);
      box-shadow: var(--shadow-sm);
    }

    .day-header {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--dark-text);
      margin-bottom: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid var(--light-border);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .time-controls {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-group {
      margin-bottom: 0;
    }

    .form-label {
      display: block;
      font-weight: 500;
      color: var(--gray-text);
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
    }

    .form-control {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--light-border);
      border-radius: var(--radius);
      font-size: 0.95rem;
      transition: var(--transition);
      background: var(--white);
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    /* Button Styles */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: var(--radius);
      font-weight: 500;
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
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .btn-center {
      display: block;
      margin: 0 auto;
    }

    /* Current Hours Display */
    .current-hours {
      margin-top: 3rem;
      padding-top: 2rem;
      border-top: 1px solid var(--light-border);
    }

    .current-hours h3 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--dark-text);
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .hours-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }

    .hour-card {
      background: linear-gradient(135deg, var(--light-bg) 0%, #f8fafc 100%);
      padding: 1.25rem;
      border-radius: var(--radius);
      border: 1px solid var(--light-border);
      text-align: center;
      transition: var(--transition);
    }

    .hour-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow);
      border-color: var(--primary-light);
    }

    .hour-day {
      color: var(--dark-text);
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 0.75rem;
      display: block;
    }

    .hour-time {
      color: var(--primary);
      font-weight: 500;
      font-size: 0.95rem;
    }

    .no-hours {
      grid-column: 1 / -1;
      text-align: center;
      padding: 2rem;
      background: var(--light-bg);
      border-radius: var(--radius);
      border: 2px dashed var(--light-border);
      color: var(--gray-text);
    }

    /* Footer */
    .footer {
      background: var(--white);
      border-top: 1px solid var(--light-border);
      padding: 1.5rem 0;
      margin-top: 3rem;
      text-align: center;
    }

    .footer p {
      color: var(--gray-text);
      font-weight: 500;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      .header-title {
        flex-direction: column;
      }

      .container {
        padding: 0 1rem;
      }

      .days-grid {
        grid-template-columns: 1fr;
      }

      .time-controls {
        grid-template-columns: 1fr;
      }

      .hours-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .day-card {
      animation: fadeIn 0.3s ease-out;
    }

    /* Icons */
    .icon {
      width: 20px;
      height: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>
<body>

<!-- Header Section -->
<header class="header">
  <div class="header-content">
    <div class="header-title">
      <a href="parlour_dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
      </a>
      <h1>Business Hours Management</h1>
    </div>
    <div class="header-actions">
      <a href="index.html" class="nav-link">
        <i class="fas fa-home"></i>
        Home
      </a>
      <a href="profile.php" class="nav-link">
        <i class="fas fa-user"></i>
        Profile
      </a>
      <a href="logout.php" class="nav-link">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </div>
</header>

<!-- Main Content Section -->
<div class="container">
  <div class="card">
    <div class="card-header">
      <h2><i class="fas fa-clock"></i> Configure Business Hours</h2>
    </div>
    <div class="card-body">
      <!-- Display success/error messages -->
      <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <?php echo $success_message; ?>
        </div>
      <?php elseif (isset($error_message)): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <!-- Form to set business hours -->
      <form action="set_business_hours.php" method="POST">
        <div class="days-grid">
          <?php
          $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
          $dayIcons = [
            'Monday' => 'fas fa-calendar-day',
            'Tuesday' => 'fas fa-calendar-alt',
            'Wednesday' => 'fas fa-calendar-week',
            'Thursday' => 'fas fa-calendar-check',
            'Friday' => 'fas fa-calendar',
            'Saturday' => 'fas fa-umbrella-beach',
            'Sunday' => 'fas fa-church'
          ];
          
          foreach ($days as $day) {
              $open_time = '';
              $close_time = '';
              
              // If hours already exist, get them
              if (mysqli_num_rows($result) > 0) {
                  mysqli_data_seek($result, 0); // Reset result pointer
                  while ($hours = mysqli_fetch_assoc($result)) {
                      if ($hours['day_of_week'] == $day) {
                          $open_time = $hours['open_time'];
                          $close_time = $hours['close_time'];
                          break;
                      }
                  }
              }
              
              echo "<div class='day-card'>
                      <div class='day-header'>
                        <i class='{$dayIcons[$day]}'></i>
                        $day
                      </div>
                      <div class='time-controls'>
                        <div class='form-group'>
                          <label class='form-label'>Opening Time</label>
                          <input type='time' class='form-control' name='{$day}_open' value='$open_time' required>
                        </div>
                        <div class='form-group'>
                          <label class='form-label'>Closing Time</label>
                          <input type='time' class='form-control' name='{$day}_close' value='$close_time' required>
                        </div>
                      </div>
                    </div>";
          }
          ?>
        </div>

        <button type="submit" class="btn btn-primary btn-center" name="set_hours">
          <i class="fas fa-save"></i>
          Save Business Hours
        </button>
      </form>

      <!-- Display current business hours -->
      <div class="current-hours">
        <h3><i class="fas fa-list-alt"></i> Current Operating Hours</h3>
        <div class="hours-grid">
          <?php
          // Reset result pointer and display current hours
          if (mysqli_num_rows($result) > 0) {
              mysqli_data_seek($result, 0);
              
              while ($hours = mysqli_fetch_assoc($result)) {
                  echo "<div class='hour-card'>
                          <span class='hour-day'>{$hours['day_of_week']}</span>
                          <span class='hour-time'>{$hours['open_time']} - {$hours['close_time']}</span>
                        </div>";
              }
          } else {
              echo "<div class='no-hours'>
                      <i class='fas fa-clock' style='font-size: 2rem; margin-bottom: 1rem; color: #9CA3AF;'></i>
                      <p>No business hours configured yet.</p>
                      <p>Please set your operating hours above.</p>
                    </div>";
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Footer Section -->
<footer class="footer">
  <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

</body>
</html>