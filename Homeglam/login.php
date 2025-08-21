<?php
session_start();
include('db.php'); // Include database connection

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = $_POST['role'];  // Get the role selected by the user

    // Query to check if the user exists
    $sql = "SELECT * FROM users WHERE email = '$email' AND role = '$role'";  // Add role condition
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // User found, now verify password
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            // Password is correct, store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];  // Store the user's role in session

            // Redirect based on role
            if ($_SESSION['role'] == 'parlour_owner') {
                header("Location: parlour_dashboard.php"); // Redirect to parlour owner's dashboard
                exit;
            } else {
                header("Location: customer_dashboard.php"); // Redirect to customer dashboard
                exit;
            }
        } else {
            // Incorrect password
            $error_message = "Invalid password.";
        }
    } else {
        // User not found
        $error_message = "No user found with that email or incorrect role.";
    }
}
?>

<!-- HTML for the login form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Home Glam</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      margin: 20px;
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #333;
      font-size: 28px;
      font-weight: 600;
    }

    .input-group {
      margin-bottom: 20px;
    }

    .input-group label {
      display: block;
      margin-bottom: 8px;
      color: #555;
      font-weight: 500;
    }

    .input-group input,
    .input-group select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s;
      box-sizing: border-box;
    }

    .input-group input:focus,
    .input-group select:focus {
      outline: none;
      border-color: #0078d4;
    }

    .login-btn {
      width: 100%;
      background: #0078d4;
      color: white;
      padding: 14px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
      margin-bottom: 20px;
    }

    .login-btn:hover {
      background: #106ebe;
    }

    .error-message {
      background: #f8d7da;
      color: #721c24;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      border-left: 4px solid #dc3545;
    }

    .register-link {
      text-align: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #e1e5e9;
    }

    .register-link p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }

    .register-link a {
      color: #0078d4;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .register-link a:hover {
      color: #106ebe;
      text-decoration: underline;
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
        margin: 10px;
      }
      
      .login-container h2 {
        font-size: 24px;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2>Login</h2>

    <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="input-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="input-group">
        <input type="password" name="password" placeholder="Password" required>
      </div>

      <!-- Role Selection -->
      <div class="input-group">
        <label for="role">I am a:</label>
        <select name="role" required>
          <option value="customer">Customer</option>
          <option value="parlour_owner">Parlour Owner</option>
        </select>
      </div>

      <button type="submit" class="login-btn">Login</button>
    </form>

    <!-- Register Link Section -->
    <div class="register-link">
      <p>Don't have an account? <a href="register.php">Create Account</a></p>
    </div>
  </div>

</body>
</html>



