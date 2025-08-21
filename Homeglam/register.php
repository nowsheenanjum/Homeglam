<?php
include('db.php'); // Include database connection

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input from the form
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $role = $_POST['role'];  // Get the role selected by the user

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Check if email is already registered
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $error_message = "Email is already registered!";
        } else {
            // Hash the password before saving it
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user data into the database
            $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')";
            if (mysqli_query($conn, $sql)) {
                // Redirect to login page after successful registration
                header("Location: login.php");
                exit;
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Home Glam</title>
  <link rel="stylesheet" href="styles.css"> <!-- Link to your existing styles.css -->
</head>
<body>

  <div class="register-container">
    <h2>Create Your Account</h2>

    <!-- Display error message if any -->
    <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <!-- Registration Form -->
    <form action="register.php" method="POST">
      <div class="input-group">
        <input type="text" name="name" placeholder="Full Name" required>
      </div>

      <div class="input-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="input-group">
        <input type="password" name="password" placeholder="Password" required>
      </div>

      <div class="input-group">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      </div>

      <!-- Role Selection -->
      <div class="input-group">
        <label for="role">I am a:</label>
        <select name="role" required>
          <option value="customer">Customer</option>
          <option value="parlour_owner">Parlour Owner</option>
        </select>
      </div>

      <button type="submit" class="register-btn">Register</button>
    </form>

    <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
  </div>

</body>
</html>

