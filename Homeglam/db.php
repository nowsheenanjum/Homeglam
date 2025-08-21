<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = ""; // Use your database password
$dbname = "homeglam"; // Name of your database

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Video upload configuration
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/mov', 'video/avi', 'video/wmv', 'video/webm']);
define('VIDEO_UPLOAD_PATH', 'uploads/videos/');

// Create upload directory if it doesn't exist
if (!file_exists(VIDEO_UPLOAD_PATH)) {
    mkdir(VIDEO_UPLOAD_PATH, 0777, true);
}
?>
