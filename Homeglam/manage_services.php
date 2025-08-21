<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/services/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Function to handle image upload
function uploadServiceImage($file, $service_id) {
    global $upload_dir;
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Only JPG, PNG and GIF images are allowed.");
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Image size must be less than 5MB.");
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'service_' . $service_id . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    } else {
        throw new Exception("Failed to upload image.");
    }
}

// Handle Add/Edit/Delete Services
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_action'])) {
    try {
        if ($_POST['service_action'] == 'add') {
            $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
            $service_price = mysqli_real_escape_string($conn, $_POST['service_price']);
            
            // Insert service first
            $sql = "INSERT INTO services (parlour_id, service_name, price) VALUES ('$user_id', '$service_name', '$service_price')";
            if (mysqli_query($conn, $sql)) {
                $service_id = mysqli_insert_id($conn);
                
                // Handle image upload if provided
                $image_path = null;
                if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == UPLOAD_ERR_OK) {
                    $image_path = uploadServiceImage($_FILES['service_image'], $service_id);
                    
                    // Update service with image path
                    $update_sql = "UPDATE services SET service_image = '$image_path' WHERE id = '$service_id'";
                    mysqli_query($conn, $update_sql);
                }
                
                $success_message = "Service added successfully!" . ($image_path ? " Image uploaded." : "");
            } else {
                $error_message = "Error adding service: " . mysqli_error($conn);
            }
        } 
        elseif ($_POST['service_action'] == 'delete') {
            $service_id = $_POST['service_id'];
            
            // Check if the service has any bookings
            $check_bookings_sql = "SELECT COUNT(*) as booking_count FROM booking_services WHERE service_id = '$service_id'";
            $check_result = mysqli_query($conn, $check_bookings_sql);
            $booking_data = mysqli_fetch_assoc($check_result);
            
            if ($booking_data['booking_count'] > 0) {
                $error_message = "Cannot delete service. This service has existing bookings.";
            } else {
                // Get image path before deletion
                $get_image_sql = "SELECT service_image FROM services WHERE id = '$service_id' AND parlour_id = '$user_id'";
                $image_result = mysqli_query($conn, $get_image_sql);
                $image_data = mysqli_fetch_assoc($image_result);
                
                $sql = "DELETE FROM services WHERE id = '$service_id' AND parlour_id = '$user_id'";
                if (mysqli_query($conn, $sql)) {
                    // Delete image file if it exists
                    if ($image_data['service_image'] && file_exists($image_data['service_image'])) {
                        unlink($image_data['service_image']);
                    }
                    $success_message = "Service deleted successfully!";
                } else {
                    $error_message = "Error deleting service: " . mysqli_error($conn);
                }
            }
        } 
        elseif ($_POST['service_action'] == 'edit') {
            $service_id = $_POST['service_id'];
            $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
            $service_price = mysqli_real_escape_string($conn, $_POST['service_price']);
            
            // Check if new image is uploaded
            $image_update = "";
            if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == UPLOAD_ERR_OK) {
                // Get old image path
                $get_old_image_sql = "SELECT service_image FROM services WHERE id = '$service_id' AND parlour_id = '$user_id'";
                $old_image_result = mysqli_query($conn, $get_old_image_sql);
                $old_image_data = mysqli_fetch_assoc($old_image_result);
                
                // Upload new image
                $new_image_path = uploadServiceImage($_FILES['service_image'], $service_id);
                $image_update = ", service_image = '$new_image_path'";
                
                // Delete old image file if it exists
                if ($old_image_data['service_image'] && file_exists($old_image_data['service_image'])) {
                    unlink($old_image_data['service_image']);
                }
            }
            
            $sql = "UPDATE services SET service_name = '$service_name', price = '$service_price' $image_update WHERE id = '$service_id' AND parlour_id = '$user_id'";
            if (mysqli_query($conn, $sql)) {
                $success_message = "Service updated successfully!" . ($image_update ? " Image updated." : "");
            } else {
                $error_message = "Error updating service: " . mysqli_error($conn);
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch existing services for the parlour owner
$sql = "SELECT * FROM services WHERE parlour_id = '$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Parlour Dashboard</title>
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

        /* Main Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .main-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Messages */
        .success-message, .error-message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
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

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #e9ecef;
        }

        .section-header h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
        }

        /* Add Service Form */
        .add-service-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-row-full {
            grid-column: span 2;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-group input {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* File Upload Styles */
        .file-upload-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem;
            border: 2px dashed #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
            justify-content: center;
        }

        .file-upload-label:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-label.has-file {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
            color: #155724;
        }

        /* Service Image Preview */
        .image-preview {
            margin-top: 0.5rem;
            display: none;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-self: end;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        /* Services Grid */
        .services-container {
            margin-top: 2rem;
        }

        .services-grid {
            display: grid;
            gap: 1.5rem;
        }

        .service-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .service-content {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            align-items: center;
        }

        .service-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e9ecef;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-image .no-image {
            color: #6c757d;
            text-align: center;
            font-size: 0.9rem;
        }

        .service-info {
            flex: 1;
        }

        .service-info h4 {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .service-price {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .booking-warning {
            background: #fff3cd;
            color: #856404;
            padding: 0.8rem;
            border-radius: 6px;
            margin: 1rem 0;
            border-left: 4px solid #ffc107;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        /* Edit Form */
        .edit-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }

        .edit-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.8rem;
            justify-content: flex-end;
        }

        .edit-btn {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .delete-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .delete-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .delete-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .empty-state h4 {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
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
            }

            .form-row-full {
                grid-column: span 1;
            }

            .service-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .service-image {
                margin: 0 auto;
            }

            .edit-form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .dashboard-container {
                padding: 0 1rem;
            }

            nav ul {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Header Section -->
<header>
    <div class="header-content">
        <h1>
            <i class="fas fa-cogs"></i>
            Manage Your Services
        </h1>
        <nav>
            <ul>
                <li><a href="parlour_dashboard.php"><i class="fas fa-tachometer-alt"></i> Back to Dashboard</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Main Content Section -->
<div class="dashboard-container">
    <div class="main-content">
        <!-- Display success/error messages -->
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Service Section -->
        <div class="section-header">
            <i class="fas fa-plus-circle"></i>
            <h3>Add New Service</h3>
        </div>
        
        <div class="add-service-container">
            <form action="manage_services.php" method="POST" enctype="multipart/form-data" id="addServiceForm">
                <div class="form-grid">
                    <div class="input-group">
                        <label for="service_name">
                            <i class="fas fa-spa"></i>
                            Service Name:
                        </label>
                        <input type="text" name="service_name" id="service_name" required placeholder="e.g., Haircut, Facial, Manicure">
                    </div>
                    <div class="input-group">
                        <label for="service_price">
                            <i class="fas fa-tags"></i>
                            Service Price (BDT):
                        </label>
                        <input type="number" name="service_price" id="service_price" min="0" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="input-group form-row-full">
                        <label for="service_image">
                            <i class="fas fa-image"></i>
                            Service Image (Optional):
                        </label>
                        <div class="file-upload-container">
                            <input type="file" name="service_image" id="service_image" class="file-upload-input" accept="image/*" onchange="previewImage(this, 'add-preview')">
                            <label for="service_image" class="file-upload-label" id="add-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Choose Image (JPG, PNG, GIF - Max 5MB)
                            </label>
                        </div>
                        <div class="image-preview" id="add-preview">
                            <img src="" alt="Preview">
                        </div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="submit" name="service_action" value="add" class="submit-btn">
                        <i class="fas fa-plus"></i>
                        Add Service
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Services Section -->
        <div class="services-container">
            <div class="section-header">
                <i class="fas fa-list"></i>
                <h3>Your Existing Services</h3>
            </div>
            
            <div class="services-grid">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($service = mysqli_fetch_assoc($result)) {
                        // Check if service has bookings
                        $service_id = $service['id'];
                        $booking_check_sql = "SELECT COUNT(*) as booking_count FROM booking_services WHERE service_id = '$service_id'";
                        $booking_check_result = mysqli_query($conn, $booking_check_sql);
                        $booking_check_data = mysqli_fetch_assoc($booking_check_result);
                        $has_bookings = $booking_check_data['booking_count'] > 0;
                        
                        echo "<div class='service-card'>
                                <div class='service-content'>
                                    <div class='service-image'>";
                        
                        if ($service['service_image'] && file_exists($service['service_image'])) {
                            echo "<img src='" . htmlspecialchars($service['service_image']) . "' alt='Service Image'>";
                        } else {
                            echo "<div class='no-image'><i class='fas fa-image'></i><br>No Image</div>";
                        }
                        
                        echo "</div>
                                    <div class='service-info'>
                                        <h4><i class='fas fa-spa'></i> " . htmlspecialchars($service['service_name']) . "</h4>";
                        
                        if ($has_bookings) {
                            echo "<div class='booking-warning'>
                                    <i class='fas fa-exclamation-triangle'></i>
                                    This service has " . $booking_check_data['booking_count'] . " active booking(s). Cannot be deleted until all bookings are completed.
                                  </div>";
                        }
                        
                        echo "</div>
                                    <div class='service-price'>
                                        ৳ " . number_format($service['price'], 2) . "
                                    </div>
                                </div>";
                        
                        echo "<div class='edit-form'>
                                <form action='manage_services.php' method='POST' enctype='multipart/form-data'>
                                    <input type='hidden' name='service_id' value='" . $service['id'] . "'>
                                    <div class='edit-form-grid'>
                                        <div class='input-group'>
                                            <label><i class='fas fa-edit'></i> Service Name:</label>
                                            <input type='text' name='service_name' value='" . htmlspecialchars($service['service_name']) . "' required>
                                        </div>
                                        <div class='input-group'>
                                            <label><i class='fas fa-dollar-sign'></i> Price (BDT):</label>
                                            <input type='number' name='service_price' value='" . $service['price'] . "' min='0' step='0.01' required>
                                        </div>
                                        <div class='input-group form-row-full'>
                                            <label><i class='fas fa-image'></i> Update Image:</label>
                                            <div class='file-upload-container'>
                                                <input type='file' name='service_image' id='edit_image_" . $service['id'] . "' class='file-upload-input' accept='image/*' onchange=\"previewImage(this, 'edit-preview-" . $service['id'] . "')\">
                                                <label for='edit_image_" . $service['id'] . "' class='file-upload-label'>
                                                    <i class='fas fa-cloud-upload-alt'></i>
                                                    " . ($service['service_image'] ? 'Change Image' : 'Add Image') . "
                                                </label>
                                            </div>
                                            <div class='image-preview' id='edit-preview-" . $service['id'] . "'>
                                                <img src='' alt='Preview'>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='action-buttons'>
                                        <button type='submit' name='service_action' value='edit' class='edit-btn'>
                                            <i class='fas fa-save'></i> Update Service
                                        </button>";
                        
                        if (!$has_bookings) {
                            echo "<button type='submit' name='service_action' value='delete' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this service?\")'>
                                    <i class='fas fa-trash'></i> Delete Service
                                  </button>";
                        } else {
                            echo "<button type='button' class='delete-btn' disabled>
                                    <i class='fas fa-lock'></i> Cannot Delete
                                  </button>";
                        }
                        
                        echo "</div>
                                </form>
                              </div>
                            </div>";
                    }
                } else {
                    echo "<div class='empty-state'>
                            <i class='fas fa-spa'></i>
                            <h4>No Services Yet</h4>
                            <p>Start by adding your first service using the form above.</p>
                          </div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Footer Section -->
<footer>
    <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

<script>
// Image preview functionality
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const label = input.nextElementSibling;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
            label.classList.add('has-file');
            label.innerHTML = '<i class="fas fa-check"></i> Image Selected';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
        label.classList.remove('has-file');
        if (previewId.includes('add')) {
            label.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Choose Image (JPG, PNG, GIF - Max 5MB)';
        } else {
            label.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Change Image';
        }
    }
}

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

// Form validation
document.getElementById('service_price').addEventListener('input', function() {
    const value = parseFloat(this.value);
    if (value < 0) {
        this.value = 0;
    }
});

// Add service form submission handling
document.getElementById('addServiceForm').addEventListener('submit', function() {
    const serviceName = document.getElementById('service_name').value.trim();
    const servicePrice = document.getElementById('service_price').value;
    
    if (!serviceName) {
        alert('Please enter a service name');
        return false;
    }
    
    if (!servicePrice || servicePrice <= 0) {
        alert('Please enter a valid service price');
        return false;
    }
    
    return true;
});
</script>

</body>
</html>