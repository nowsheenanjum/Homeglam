<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Check if the parlour profile exists
$sql = "SELECT * FROM parlours WHERE owner_id = '$user_id'";
$result = mysqli_query($conn, $sql);

// If no profile exists, prompt the user to create one
if (mysqli_num_rows($result) == 0) {
    header("Location: create_parlour_profile.php");
    exit;
} else {
    $parlour = mysqli_fetch_assoc($result);
}

// Get unread messages count
$unread_messages_sql = "SELECT COUNT(*) as count FROM messages WHERE parlour_id = '$user_id' AND sender_type = 'customer' AND is_read = FALSE";
$unread_messages_result = mysqli_query($conn, $unread_messages_sql);
$unread_messages_count = mysqli_fetch_assoc($unread_messages_result)['count'];

// Handle video upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['service_video'])) {
    $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
    $video_title = mysqli_real_escape_string($conn, $_POST['video_title']);
    
    // Check if video file was uploaded without errors
    if ($_FILES['service_video']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['service_video']['tmp_name'];
        $file_name = $_FILES['service_video']['name'];
        $file_size = $_FILES['service_video']['size'];
        $file_type = $_FILES['service_video']['type'];
        
        // Validate file size
        if ($file_size > MAX_VIDEO_SIZE) {
            $error_message = "Video file is too large. Maximum size allowed is " . (MAX_VIDEO_SIZE / (1024 * 1024)) . "MB.";
        } 
        // Validate file type
        else if (!in_array($file_type, ALLOWED_VIDEO_TYPES)) {
            $error_message = "Only MP4, MOV, AVI, WMV, and WEBM files are allowed.";
        } 
        else {
            // Generate unique file name
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('video_', true) . '.' . $file_extension;
            $dest_path = VIDEO_UPLOAD_PATH . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                // Insert video info into database
                $sql = "INSERT INTO service_videos (service_id, video_title, video_path, uploaded_at) 
                        VALUES ('$service_id', '$video_title', '$dest_path', NOW())";
                
                if (mysqli_query($conn, $sql)) {
                    $success_message = "Video uploaded successfully!";
                } else {
                    $error_message = "Error saving video information: " . mysqli_error($conn);
                    // Delete the uploaded file if DB insert failed
                    unlink($dest_path);
                }
            } else {
                $error_message = "There was an error uploading your video.";
            }
        }
    } else {
        $error_message = "Error in file upload: " . $_FILES['service_video']['error'];
    }
}

// Handle video deletion
if (isset($_POST['delete_video'])) {
    $video_id = mysqli_real_escape_string($conn, $_POST['video_id']);
    
    // Get video path first
    $sql = "SELECT video_path FROM service_videos WHERE id = '$video_id' AND service_id IN (SELECT id FROM services WHERE parlour_id = '$user_id')";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $video = mysqli_fetch_assoc($result);
        $video_path = $video['video_path'];
        
        // Delete from database
        $delete_sql = "DELETE FROM service_videos WHERE id = '$video_id'";
        if (mysqli_query($conn, $delete_sql)) {
            // Delete physical file
            if (file_exists($video_path)) {
                unlink($video_path);
            }
            $success_message = "Video deleted successfully!";
        } else {
            $error_message = "Error deleting video: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Video not found or you don't have permission to delete it.";
    }
}

// Fetch service videos
$videos_sql = "SELECT sv.*, s.service_name 
               FROM service_videos sv 
               JOIN services s ON sv.service_id = s.id 
               WHERE s.parlour_id = '$user_id' 
               ORDER BY sv.uploaded_at DESC";
$videos_result = mysqli_query($conn, $videos_sql);

// Check if the form is submitted to create combo offers
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['offer_name'])) {
    // Get combo offer details from the form
    $offer_name = mysqli_real_escape_string($conn, $_POST['offer_name']);
    $offer_description = mysqli_real_escape_string($conn, $_POST['offer_description']);
    $discount_percentage = mysqli_real_escape_string($conn, $_POST['discount_percentage']);
    $services_included = mysqli_real_escape_string($conn, $_POST['services_included']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    
    // Insert combo offer into the database
    $sql = "INSERT INTO offers (parlour_id, offer_name, offer_description, discount_percentage, services_included, start_date, end_date) 
            VALUES ('$user_id', '$offer_name', '$offer_description', '$discount_percentage', '$services_included', '$start_date', '$end_date')";

    if (mysqli_query($conn, $sql)) {
        $success_message = "Combo offer created successfully!";
    } else {
        $error_message = "Error creating combo offer: " . mysqli_error($conn);
    }
}

// Fetch existing combo offers for the parlour owner
$offer_sql = "SELECT * FROM offers WHERE parlour_id = '$user_id'";
$offer_result = mysqli_query($conn, $offer_sql);

// Handle Combo Offer Deletion
if (isset($_POST['delete_offer']) && isset($_POST['offer_id'])) {
    $offer_id = mysqli_real_escape_string($conn, $_POST['offer_id']);

    // Start transaction for safe deletion
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete all related records in booking_combo_offers table
        $delete_booking_offers_sql = "DELETE FROM booking_combo_offers WHERE combo_offer_id = '$offer_id'";
        mysqli_query($conn, $delete_booking_offers_sql);
        
        // Then delete the offer from offers table
        $delete_offer_sql = "DELETE FROM offers WHERE id = '$offer_id' AND parlour_id = '$user_id'";
        mysqli_query($conn, $delete_offer_sql);
        
        // If both queries succeed, commit the transaction
        mysqli_commit($conn);
        $success_message = "Offer and all related bookings deleted successfully!";
        
        // Refresh the page to show updated data
        header("Location: parlour_dashboard.php");
        exit;
        
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        mysqli_rollback($conn);
        $error_message = "Error deleting offer: " . $e->getMessage();
    }
}

// Fetch existing services
$service_sql = "SELECT * FROM services WHERE parlour_id = '$user_id'";
$service_result = mysqli_query($conn, $service_sql);

// Handle Add/Edit/Delete Services
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_action'])) {
    if ($_POST['service_action'] == 'add') {
        $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
        $service_price = mysqli_real_escape_string($conn, $_POST['service_price']);
        $sql = "INSERT INTO services (parlour_id, service_name, price) VALUES ('$user_id', '$service_name', '$service_price')";
        if (mysqli_query($conn, $sql)) {
            $success_message = "Service added successfully!";
        } else {
            $error_message = "Error adding service: " . mysqli_error($conn);
        }
    }
    elseif ($_POST['service_action'] == 'delete') {
        $service_id = $_POST['service_id'];
        $sql = "DELETE FROM services WHERE id = '$service_id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = "Service deleted successfully!";
        } else {
            $error_message = "Error deleting service: " . mysqli_error($conn);
        }
    }
    elseif ($_POST['service_action'] == 'edit') {
        $service_id = $_POST['service_id'];
        $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
        $service_price = mysqli_real_escape_string($conn, $_POST['service_price']);
        $sql = "UPDATE services SET service_name = '$service_name', price = '$service_price' WHERE id = '$service_id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = "Service updated successfully!";
        } else {
            $error_message = "Error updating service: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parlour Owner Dashboard - Home Glam</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #7E57C2;
      --primary-light: #B39DDB;
      --primary-dark: #5E35B1;
      --secondary: #FF80AB;
      --secondary-dark: #F50057;
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
    }

    /* Header Styles */
    header {
      background: linear-gradient(120deg, var(--primary), var(--primary-dark));
      color: white;
      padding: 1rem 0;
      box-shadow: var(--shadow);
      position: sticky;
      top: 0;
      z-index: 100;
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
      background: linear-gradient(to right, #fff, var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .header-nav {
      display: flex;
      align-items: center;
      gap: 2rem;
    }

    nav ul {
      display: flex;
      list-style: none;
      gap: 1.5rem;
    }

    nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius);
    }

    nav a:hover {
      background-color: rgba(255, 255, 255, 0.15);
      transform: translateY(-2px);
    }

    /* Message notification badge */
    .message-badge {
      position: absolute;
      top: -0.25rem;
      right: -0.25rem;
      background: var(--secondary-dark);
      color: white;
      font-size: 0.75rem;
      font-weight: 500;
      padding: 0.125rem 0.375rem;
      border-radius: 50px;
      min-width: 1.25rem;
      text-align: center;
      line-height: 1;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Dashboard Container */
    .dashboard-container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 2rem;
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 1.5rem;
    }

    /* Sidebar Styles */
    .sidebar {
      background: linear-gradient(to bottom, white, var(--gray-100));
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      height: fit-content;
      position: sticky;
      top: 6rem;
      border: 1px solid var(--gray-200);
    }

    .user-info h3 {
      color: var(--primary-dark);
      margin-bottom: 1.5rem;
      font-size: 1.25rem;
      border-bottom: 2px solid var(--primary-light);
      padding-bottom: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Button Styles */
    .sidebar button {
      width: 100%;
      padding: 0.875rem 1rem;
      margin-bottom: 0.75rem;
      border: none;
      border-radius: var(--radius);
      background: linear-gradient(to right, white, var(--gray-100));
      color: var(--gray-700);
      font-size: 0.95rem;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      position: relative;
      text-align: left;
      box-shadow: var(--shadow-sm);
      border-left: 4px solid var(--primary);
    }

    .sidebar button:hover {
      background: linear-gradient(to right, var(--primary-light), var(--primary));
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .sidebar button:active {
      transform: translateY(0);
    }

    /* Messages button with badge */
    .messages-btn {
      border-left-color: var(--secondary);
    }

    .sidebar .message-badge {
      position: absolute;
      top: 0.75rem;
      right: 0.75rem;
      background: var(--secondary-dark);
      color: white;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.125rem 0.375rem;
      border-radius: 50px;
      min-width: 1.25rem;
      text-align: center;
      line-height: 1;
    }

    /* Message Styles */
    .success-message {
      background: #E8F5E9;
      color: #2E7D32;
      padding: 0.875rem;
      border-radius: var(--radius);
      border-left: 4px solid var(--success);
      margin-bottom: 1rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: var(--shadow-sm);
    }

    .error-message {
      background: #FFEBEE;
      color: #C62828;
      padding: 0.875rem;
      border-radius: var(--radius);
      border-left: 4px solid var(--danger);
      margin-bottom: 1rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: var(--shadow-sm);
    }

    /* Main Content */
    .main-content {
      background: linear-gradient(to bottom, white, var(--gray-100));
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: var(--shadow);
      border: 1px solid var(--gray-200);
    }

    .combo-offers-section h3 {
      color: var(--primary-dark);
      margin-bottom: 1.5rem;
      font-size: 1.5rem;
      border-bottom: 2px solid var(--primary-light);
      padding-bottom: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Combo Offer Cards */
    .combo-offer-card {
      background: white;
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1.25rem;
      box-shadow: var(--shadow);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      border: 1px solid var(--gray-200);
    }

    .combo-offer-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      opacity: 0.8;
    }

    .combo-offer-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
    }

    .combo-offer-card h4 {
      color: var(--primary-dark);
      margin-bottom: 0.875rem;
      font-size: 1.25rem;
      font-weight: 600;
    }

    .combo-offer-card p {
      margin-bottom: 0.5rem;
      color: var(--gray-600);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .warning-text {
      color: var(--danger);
      font-weight: 500;
      font-size: 0.9em;
      margin: 0.75rem 0;
      padding: 0.75rem;
      background: #FFEBEE;
      border-radius: var(--radius);
      border-left: 3px solid var(--danger);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .delete-btn {
      background: linear-gradient(120deg, var(--danger), #D32F2F);
      color: white;
      border: none;
      padding: 0.625rem 1.25rem;
      border-radius: var(--radius);
      cursor: pointer;
      font-weight: 500;
      transition: var(--transition);
      margin-top: 0.75rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: var(--shadow-sm);
    }

    .delete-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Form Styles */
    .offer-form-container, .video-form-container {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      backdrop-filter: blur(4px);
    }

    .offer-form-container > div, .video-form-container > div {
      background: white;
      padding: 2rem;
      border-radius: var(--radius-lg);
      width: 90%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
      position: relative;
      border: 1px solid var(--gray-200);
    }

    .offer-form-container h3, .video-form-container h3 {
      color: var(--primary-dark);
      margin-bottom: 1.5rem;
      text-align: center;
      font-size: 1.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .input-group {
      margin-bottom: 1.25rem;
    }

    .input-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--gray-700);
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .input-group input,
    .input-group textarea,
    .input-group select {
      width: 100%;
      padding: 0.875rem;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius);
      font-size: 0.95rem;
      transition: var(--transition);
      font-family: inherit;
    }

    .input-group input:focus,
    .input-group textarea:focus,
    .input-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.1);
    }

    .input-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .submit-btn {
      width: 100%;
      padding: 0.875rem;
      background: linear-gradient(120deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      border-radius: var(--radius);
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      box-shadow: var(--shadow);
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(126, 87, 194, 0.3);
    }

    /* Footer */
    footer {
      background: var(--dark);
      color: white;
      text-align: center;
      padding: 1.5rem;
      margin-top: 3rem;
    }

    /* Close button for form */
    .close-btn {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: var(--gray-200);
      border: none;
      width: 2rem;
      height: 2rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.125rem;
      cursor: pointer;
      color: var(--gray-600);
      transition: var(--transition);
    }

    .close-btn:hover {
      background: var(--gray-300);
      color: var(--gray-800);
    }

    /* Quick stats section */
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: linear-gradient(135deg, #fff, var(--gray-100));
      padding: 1.5rem;
      border-radius: var(--radius);
      text-align: center;
      box-shadow: var(--shadow);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      border: 1px solid var(--gray-200);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      opacity: 0.8;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }

    .stat-card h4 {
      color: var(--primary-dark);
      font-size: 2.25rem;
      margin-bottom: 0.5rem;
      font-weight: 700;
    }

    .stat-card p {
      color: var(--gray-600);
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    /* Video Gallery Styles */
    .video-management-section {
      margin-top: 2rem;
    }

    .video-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }

    .video-card {
      background: white;
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: var(--transition);
      border: 1px solid var(--gray-200);
    }

    .video-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }

    .video-thumbnail {
      position: relative;
      width: 100%;
      height: 180px;
      overflow: hidden;
      background: var(--gray-100);
    }

    .video-thumbnail video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .video-info {
      padding: 1.25rem;
    }

    .video-info h4 {
      color: var(--primary-dark);
      margin-bottom: 0.75rem;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .video-info p {
      color: var(--gray-600);
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
    }

    /* Responsive Design */
    @media (max-width: 968px) {
      .dashboard-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 0 1.5rem;
      }

      .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      .header-nav {
        width: 100%;
        justify-content: center;
      }

      nav ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
      }

      .sidebar {
        position: static;
      }

      .quick-stats {
        grid-template-columns: repeat(2, 1fr);
      }

      .video-gallery {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
    }

    @media (max-width: 640px) {
      .dashboard-container {
        padding: 0 1rem;
      }
      
      .header-content {
        padding: 0 1rem;
      }
      
      nav ul {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
      }
      
      .quick-stats {
        grid-template-columns: 1fr;
      }
      
      .main-content, .sidebar {
        padding: 1.5rem;
      }

      .video-gallery {
        grid-template-columns: 1fr;
      }
    }

    /* Animation for elements */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .combo-offer-card, .stat-card, .video-card {
      animation: fadeIn 0.5s ease-out;
    }

    /* Loading animation for buttons */
    .btn-loading {
      position: relative;
      color: transparent !important;
    }

    .btn-loading::after {
      content: '';
      position: absolute;
      width: 1rem;
      height: 1rem;
      top: 50%;
      left: 50%;
      margin-left: -0.5rem;
      margin-top: -0.5rem;
      border: 2px solid transparent;
      border-radius: 50%;
      border-top-color: currentColor;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<!-- Header Section -->
<header>
  <div class="header-content">
    <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
    <div class="header-nav">
      <nav>
        <ul>
          <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
          <li><a href="parlour_profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li>
            <a href="parlour_messages.php">
              <i class="fas fa-comments"></i> Messages
              <?php if ($unread_messages_count > 0): ?>
                <span class="message-badge"><?php echo $unread_messages_count; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li><a href="logout.php"><i class="fas fa-home"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
  </div>
</header>

<!-- Dashboard Content Section -->
<div class="dashboard-container">
  <aside class="sidebar">
    <div class="user-info">
      <h3><i class="fas fa-store"></i> Your Parlour</h3>
      
      <!-- Display success/error messages -->
      <?php if (isset($success_message)): ?>
        <p class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></p>
      <?php elseif (isset($error_message)): ?>
        <p class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></p>
      <?php endif; ?>

      <!-- Button to view messages -->
      <button class="messages-btn" onclick="window.location.href='parlour_messages.php'">
        <i class="fas fa-comments"></i> Customer Messages
        <?php if ($unread_messages_count > 0): ?>
          <span class="message-badge"><?php echo $unread_messages_count; ?></span>
        <?php endif; ?>
      </button>

      <!-- Button to create combo offers -->
      <button class="create-offer-btn" onclick="document.getElementById('offer-form').style.display='flex'">
        <i class="fas fa-plus-circle"></i> Create Combo Offer
      </button>

      <!-- Button to manage services -->
      <button class="manage-service-btn" onclick="window.location.href='manage_services.php'">
        <i class="fas fa-cog"></i> Manage Services
      </button>

      <!-- Button to upload service videos -->
      <button class="upload-video-btn" onclick="document.getElementById('video-form').style.display='flex'">
        <i class="fas fa-video"></i> Upload Service Video
      </button>

      <!-- Button to manage bookings -->
      <button class="manage-booking-btn" onclick="window.location.href='booking.php'">
        <i class="fas fa-calendar-check"></i> Manage Bookings
      </button>

      <!-- Button to manage reviews -->
      <button class="manage-reviews-btn" onclick="window.location.href='view_review.php'">
        <i class="fas fa-star"></i> View Reviews
      </button>

      <!-- Button to go to Set Business Hours page -->
      <button class="business-hours-btn" onclick="window.location.href='set_business_hours.php'">
        <i class="fas fa-clock"></i> Set Business Hours
      </button>

    </div>
  </aside>

  <!-- Main Content Section -->
  <div class="main-content">
    <!-- Quick Stats Section -->
    <section class="quick-stats">
      <div class="stat-card">
        <h4><?php echo mysqli_num_rows($offer_result); ?></h4>
        <p><i class="fas fa-tags"></i> Active Offers</p>
      </div>
      <div class="stat-card">
        <h4><?php echo $unread_messages_count; ?></h4>
        <p><i class="fas fa-envelope"></i> Unread Messages</p>
      </div>
      <div class="stat-card">
        <h4>
          <?php 
          $service_count_sql = "SELECT COUNT(*) as count FROM services WHERE parlour_id = '$user_id'";
          $service_count_result = mysqli_query($conn, $service_count_sql);
          echo mysqli_fetch_assoc($service_count_result)['count']; 
          ?>
        </h4>
        <p><i class="fas fa-cut"></i> Services</p>
      </div>
      <div class="stat-card">
        <h4>
          <?php 
          $video_count_sql = "SELECT COUNT(*) as count FROM service_videos WHERE service_id IN (SELECT id FROM services WHERE parlour_id = '$user_id')";
          $video_count_result = mysqli_query($conn, $video_count_sql);
          echo mysqli_fetch_assoc($video_count_result)['count']; 
          ?>
        </h4>
        <p><i class="fas fa-video"></i> Service Videos</p>
      </div>
    </section>

    <!-- Combo Offers Section -->
    <section class="combo-offers-section">
      <h3><i class="fas fa-tags"></i> Your Combo Offers</h3>
      <?php
      if (mysqli_num_rows($offer_result) > 0) {
          while ($offer = mysqli_fetch_assoc($offer_result)) {
              // Check if this offer has any bookings
              $booking_check_sql = "SELECT COUNT(*) as booking_count FROM booking_combo_offers WHERE combo_offer_id = '" . $offer['id'] . "'";
              $booking_check_result = mysqli_query($conn, $booking_check_sql);
              $booking_count = mysqli_fetch_assoc($booking_check_result)['booking_count'];
              
              echo "<div class='combo-offer-card'>
                      <h4>" . htmlspecialchars($offer['offer_name']) . "</h4>
                      <p><i class='fas fa-info-circle'></i> " . htmlspecialchars($offer['offer_description']) . "</p>
                      <p><i class='fas fa-percentage'></i> Discount: " . htmlspecialchars($offer['discount_percentage']) . "%</p>
                      <p><i class='fas fa-list'></i> Services: " . htmlspecialchars($offer['services_included']) . "</p>
                      <p><i class='fas fa-calendar'></i> Valid From: " . htmlspecialchars($offer['start_date']) . " To " . htmlspecialchars($offer['end_date']) . "</p>";
              
              if ($booking_count > 0) {
                  echo "<p class='warning-text'><i class='fas fa-exclamation-triangle'></i> This offer has " . $booking_count . " active booking(s). Deleting will remove all related bookings.</p>";
              }
              
              echo "<form action='parlour_dashboard.php' method='POST' onsubmit='return confirm(\"Are you sure you want to delete this offer" . ($booking_count > 0 ? " and all its related bookings" : "") . "?\")'>
                        <input type='hidden' name='offer_id' value='" . $offer['id'] . "'>
                        <button type='submit' name='delete_offer' class='delete-btn'><i class='fas fa-trash'></i> Delete Offer</button>
                      </form>
                  </div>";
          }
      } else {
          echo "<p style='text-align: center; color: var(--gray-500); padding: 2.5rem; background: var(--gray-100); border-radius: var(--radius);'><i class='fas fa-info-circle' style='font-size: 2rem; margin-bottom: 1rem; display: block;'></i> No combo offers created yet.</p>";
      }
      ?>
    </section>

    <!-- Video Management Section -->
    <section class="video-management-section">
      <h3><i class="fas fa-video"></i> Your Service Videos</h3>
      <?php if (mysqli_num_rows($videos_result) > 0): ?>
        <div class="video-gallery">
          <?php while ($video = mysqli_fetch_assoc($videos_result)): ?>
            <div class="video-card">
              <div class="video-thumbnail">
                <video controls>
                  <source src="<?php echo $video['video_path']; ?>" type="video/mp4">
                  Your browser does not support the video tag.
                </video>
              </div>
              <div class="video-info">
                <h4><?php echo htmlspecialchars($video['video_title']); ?></h4>
                <p><i class="fas fa-cut"></i> Service: <?php echo htmlspecialchars($video['service_name']); ?></p>
                <p><i class="fas fa-calendar"></i> Uploaded: <?php echo date('M j, Y', strtotime($video['uploaded_at'])); ?></p>
              </div>
              <form action="parlour_dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this video?')" style="padding: 0 1.25rem 1.25rem;">
                <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                <button type="submit" name="delete_video" class="delete-btn">
                  <i class="fas fa-trash"></i> Delete Video
                </button>
              </form>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p style="text-align: center; color: var(--gray-500); padding: 2.5rem; background: var(--gray-100); border-radius: var(--radius);">
          <i class="fas fa-video-slash" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i> 
          No videos uploaded yet. Click the "Upload Service Video" button to add videos to your services.
        </p>
      <?php endif; ?>
    </section>
  </div>
</div>

<!-- Create Combo Offer Form -->
<div id="offer-form" class="offer-form-container" style="display:none;">
  <div style="position: relative;">
    <button class="close-btn" onclick="document.getElementById('offer-form').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
    <h3><i class="fas fa-plus"></i> Create New Combo Offer</h3>
    <form action="parlour_dashboard.php" method="POST">
      <div class="input-group">
        <label for="offer_name"><i class="fas fa-tag"></i> Offer Name:</label>
        <input type="text" name="offer_name" required>
      </div>
      <div class="input-group">
        <label for="offer_description"><i class="fas fa-align-left"></i> Offer Description:</label>
        <textarea name="offer_description" required></textarea>
      </div>
      <div class="input-group">
        <label for="discount_percentage"><i class="fas fa-percentage"></i> Discount Percentage:</label>
        <input type="number" name="discount_percentage" step="0.01" required>
      </div>
      <div class="input-group">
        <label for="services_included"><i class="fas fa-list-ul"></i> Services Included (comma-separated):</label>
        <input type="text" name="services_included" required>
      </div>
      <div class="input-group">
        <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date:</label>
        <input type="date" name="start_date" required>
      </div>
      <div class="input-group">
        <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date:</label>
        <input type="date" name="end_date" required>
      </div>
      <button type="submit" class="submit-btn"><i class="fas fa-plus-circle"></i> Create Combo Offer</button>
    </form>
  </div>
</div>

<!-- Video Upload Form -->
<div id="video-form" class="video-form-container" style="display:none;">
  <div style="position: relative;">
    <button class="close-btn" onclick="document.getElementById('video-form').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
    <h3><i class="fas fa-upload"></i> Upload Service Video</h3>
    <form action="parlour_dashboard.php" method="POST" enctype="multipart/form-data">
      <div class="input-group">
        <label for="service_id"><i class="fas fa-cut"></i> Select Service:</label>
        <select name="service_id" required>
          <option value="">-- Select a Service --</option>
          <?php
          $services_sql = "SELECT * FROM services WHERE parlour_id = '$user_id'";
          $services_result = mysqli_query($conn, $services_sql);
          while ($service = mysqli_fetch_assoc($services_result)) {
              echo "<option value='" . $service['id'] . "'>" . htmlspecialchars($service['service_name']) . "</option>";
          }
          ?>
        </select>
      </div>
      <div class="input-group">
        <label for="video_title"><i class="fas fa-heading"></i> Video Title:</label>
        <input type="text" name="video_title" required>
      </div>
      <div class="input-group">
        <label for="service_video"><i class="fas fa-video"></i> Select Video File (Max 50MB):</label>
        <input type="file" name="service_video" accept="video/*" required>
      </div>
      <button type="submit" class="submit-btn"><i class="fas fa-upload"></i> Upload Video</button>
    </form>
  </div>
</div>

<!-- Footer Section -->
<footer>
  <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

<script>
  // Add loading animation to buttons on click
  document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
      button.addEventListener('click', function() {
        this.classList.add('btn-loading');
      });
    });
  });
</script>

</body>
</html>