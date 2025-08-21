<?php
session_start();
include('db.php');

// Check if the user is logged in and is a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parlour_owner') {
    header("Location: login.html"); // Redirect if not logged in or not a parlour owner
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch the parlour details for the logged-in owner
if (isset($_GET['parlour_id'])) {
    $parlour_id = $_GET['parlour_id'];

    // Fetch the parlour details from the database
    $sql = "SELECT * FROM parlours WHERE id = '$parlour_id' AND user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    $parlour = mysqli_fetch_assoc($result);

    if (!$parlour) {
        header("Location: dashboard.php"); // Redirect if parlour not found or not owned by the user
        exit;
    }
}

// Handle the form submission for updating parlour data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parlour_name = mysqli_real_escape_string($conn, $_POST['parlour_name']);
    $area = mysqli_real_escape_string($conn, $_POST['area']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $services = mysqli_real_escape_string($conn, $_POST['services']);

    $sql = "UPDATE parlours SET name='$parlour_name', area='$area', address='$address', services='$services' WHERE id='$parlour_id' AND user_id='$user_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "Parlour profile updated successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!-- Similar form as before to allow editing the parlour details -->
