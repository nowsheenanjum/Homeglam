<?php
session_start();
include('db.php');

// Ensure user is logged in as parlour owner
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if required parameters exist
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header("Location: booking.php");
    exit;
}

$booking_id = mysqli_real_escape_string($conn, $_GET['id']);
$new_status = mysqli_real_escape_string($conn, $_GET['status']);

// Validate status
if (!in_array($new_status, ['confirmed', 'cancelled'])) {
    header("Location: booking.php");
    exit;
}

// Check if the booking belongs to the parlour owner's parlour and get booking details
$check_sql = "SELECT b.*, p.name as parlour_name, b.user_id as customer_id 
              FROM bookings b 
              JOIN parlours p ON b.parlour_id = p.id 
              WHERE b.id = '$booking_id' AND p.owner_id = '{$_SESSION['user_id']}'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    $booking = mysqli_fetch_assoc($check_result);
    
    // Update the booking status
    $update_sql = "UPDATE bookings SET booking_status = '$new_status' WHERE id = '$booking_id'";
    
    if (mysqli_query($conn, $update_sql)) {
        // Create notification for customer
        $customer_id = $booking['customer_id'];
        $parlour_name = mysqli_real_escape_string($conn, $booking['parlour_name']);
        $booking_date = $booking['booking_date'];
        $booking_time = $booking['booking_time'];
        
        // Create different messages based on status
        if ($new_status == 'confirmed') {
            $notification_message = "Great news! Your booking at " . $parlour_name . " for " . $booking_date . " at " . $booking_time . " has been CONFIRMED. We look forward to serving you!";
        } else if ($new_status == 'cancelled') {
            $notification_message = "We are sorry to inform you that your booking at " . $parlour_name . " for " . $booking_date . " at " . $booking_time . " has been CANCELLED. Please contact the parlour for more details.";
        }
        
        // Properly escape the notification message
        $notification_message = mysqli_real_escape_string($conn, $notification_message);
        
        // Insert notification into database
        $notification_sql = "INSERT INTO notifications (user_id, message, status, created_at) 
                           VALUES ('$customer_id', '$notification_message', 'unread', NOW())";
        
        if (mysqli_query($conn, $notification_sql)) {
            // Success - notification created
            $success_message = "Booking status updated and customer notified successfully!";
        } else {
            // Error creating notification
            $error_message = "Booking updated but failed to notify customer: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Error updating booking status: " . mysqli_error($conn);
    }
} else {
    $error_message = "Booking not found or you don't have permission to modify it.";
}

// Store message in session to display on booking page
if (isset($success_message)) {
    $_SESSION['booking_message'] = $success_message;
    $_SESSION['message_type'] = 'success';
} else if (isset($error_message)) {
    $_SESSION['booking_message'] = $error_message;
    $_SESSION['message_type'] = 'error';
}

// Redirect back to manage bookings
header("Location: booking.php");
exit;
?>