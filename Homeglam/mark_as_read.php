<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get the notification ID from the URL
$notification_id = isset($_GET['id']) ? $_GET['id'] : '';

// Mark the notification as read
if ($notification_id) {
    // Use user_id from the session to update the notification
    $update_sql = "UPDATE notifications SET status = 'read' WHERE id = '$notification_id' AND user_id = '" . $_SESSION['user_id'] . "'";

    if (mysqli_query($conn, $update_sql)) {
        // Redirect to the customer dashboard after successfully updating the status
        header("Location: customer_dashboard.php");
    } else {
        echo "Error updating notification status: " . mysqli_error($conn);
    }
} else {
    echo "Invalid notification ID.";
}
?>



