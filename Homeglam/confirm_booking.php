<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a customer
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

// Get the form data
$customer_name = mysqli_real_escape_string($conn, $_POST['name']);
$customer_contact = mysqli_real_escape_string($conn, $_POST['contact']);
$customer_address = mysqli_real_escape_string($conn, $_POST['address']);
$booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
$booking_time = mysqli_real_escape_string($conn, $_POST['booking_time']);
$parlour_id = mysqli_real_escape_string($conn, $_POST['parlour_id']);
$total_price = mysqli_real_escape_string($conn, $_POST['total_price']);
$services = isset($_POST['services']) ? $_POST['services'] : []; // Array of selected service IDs
$combo_offers = isset($_POST['combo_offers']) ? $_POST['combo_offers'] : []; // Array of selected combo offer IDs
$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Initialize total price
$final_price = 0;

// Initialize flag for valid services and combo offers
$valid_services = true;
$valid_combo_offers = true;
$error_message = "";

// First, get the parlour's owner_id
$parlour_owner_sql = "SELECT owner_id FROM parlours WHERE id = '$parlour_id'";
$parlour_owner_result = mysqli_query($conn, $parlour_owner_sql);

if (mysqli_num_rows($parlour_owner_result) == 0) {
    $error_message = "Invalid parlour ID.";
    $valid_services = false;
    $valid_combo_offers = false;
} else {
    $parlour_owner = mysqli_fetch_assoc($parlour_owner_result);
    $owner_id = $parlour_owner['owner_id'];

    // Validate services - check if services belong to the parlour owner
    foreach ($services as $service_id) {
        // Updated query to check against the parlour owner's ID
        $service_check_sql = "SELECT s.id, s.price FROM services s 
                             INNER JOIN parlours p ON s.parlour_id = p.owner_id 
                             WHERE s.id = '$service_id' AND p.id = '$parlour_id'";
        $service_check_result = mysqli_query($conn, $service_check_sql);

        // If service doesn't exist, stop the process and display an error
        if (mysqli_num_rows($service_check_result) == 0) {
            $valid_services = false;
            $error_message = "Invalid service ID $service_id. This service does not exist for parlour ID $parlour_id.<br>";
            break;
        }
    }

    // Validate combo offers
    foreach ($combo_offers as $combo_offer_id) {
        // Check if the combo_offer_id exists in the offers table for the given parlour_id
        $combo_check_sql = "SELECT id, discount_percentage FROM offers WHERE id = '$combo_offer_id' AND parlour_id = '$parlour_id'";
        $combo_check_result = mysqli_query($conn, $combo_check_sql);

        // If the combo offer doesn't exist, stop the process and display an error
        if (mysqli_num_rows($combo_check_result) == 0) {
            $valid_combo_offers = false;
            $error_message = "Invalid combo offer ID $combo_offer_id. This combo offer does not exist for parlour ID $parlour_id.<br>";
            break;
        }
    }
}

// Proceed only if all services and combo offers are valid
if ($valid_services && $valid_combo_offers) {
    // Calculate total price by adding service prices
    foreach ($services as $service_id) {
        $sql = "SELECT s.price FROM services s 
                INNER JOIN parlours p ON s.parlour_id = p.owner_id 
                WHERE s.id = '$service_id' AND p.id = '$parlour_id'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $service = mysqli_fetch_assoc($result);
            $final_price += $service['price']; // Add service price to final price
        }
    }

    // Calculate original price before discount for combo offers
    $original_price = $final_price;

    // Apply combo offer discounts
    foreach ($combo_offers as $combo_offer_id) {
        $sql = "SELECT discount_percentage FROM offers WHERE id = '$combo_offer_id' AND parlour_id = '$parlour_id'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $combo_offer = mysqli_fetch_assoc($result);
            $combo_discount = $combo_offer['discount_percentage'];
            // Apply discount to the original price (not cumulative)
            $discount_amount = ($original_price * ($combo_discount / 100));
            $final_price -= $discount_amount;
        }
    }

    // Ensure final price is not negative
    $final_price = max(0, $final_price);

    // Insert the booking data into the bookings table
    $sql = "INSERT INTO bookings (parlour_id, customer_name, customer_contact, customer_address, booking_date, booking_time, booking_status, total_price, user_id) 
            VALUES ('$parlour_id', '$customer_name', '$customer_contact', '$customer_address', '$booking_date', '$booking_time', 'pending', '$final_price', '$user_id')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn); // Get the ID of the newly created booking

        // Insert selected services into the booking_services table
        foreach ($services as $service_id) {
            $sql = "INSERT INTO booking_services (booking_id, service_id) VALUES ('$booking_id', '$service_id')";
            mysqli_query($conn, $sql);
        }

        // Insert selected combo offers into the booking_combo_offers table
        foreach ($combo_offers as $combo_offer_id) {
            $sql = "INSERT INTO booking_combo_offers (booking_id, combo_offer_id) VALUES ('$booking_id', '$combo_offer_id')";
            mysqli_query($conn, $sql);
        }

        $success_message = "Booking confirmed successfully!";
    } else {
        $error_message = "Error booking service: " . mysqli_error($conn);
    }
} else {
    // Display error if services or combo offers are invalid
    if (empty($error_message)) {
        $error_message = "Please select valid services or combo offers.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | Home Glam</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3a86ff;
            --primary-dark: #2563eb;
            --secondary: #ff006e;
            --accent: #8338ec;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            color: var(--secondary);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: var(--transition);
        }

        nav a:hover {
            color: var(--primary);
            background-color: #f1f5f9;
        }

        /* Main Content */
        .main-content {
            padding: 40px 0;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 30px;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body {
            padding: 30px;
        }

        /* Status Messages */
        .status-card {
            text-align: center;
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
        }

        .status-card.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 5px solid var(--success);
        }

        .status-card.error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 5px solid var(--danger);
        }

        .status-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            display: block;
        }

        .success .status-icon {
            color: var(--success);
        }

        .error .status-icon {
            color: var(--danger);
        }

        .status-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .success .status-title {
            color: var(--success);
        }

        .error .status-title {
            color: var(--danger);
        }

        .status-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: var(--gray);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Booking Details */
        .booking-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .details-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 500;
            color: var(--dark);
            font-size: 1.1rem;
        }

        /* Price Display */
        .price-display {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
        }

        .price-label {
            font-size: 1.1rem;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .total-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: #f0f7ff;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
        }

        .btn-error {
            background: var(--danger);
            color: white;
        }

        .btn-error:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
            color: white;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 50px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 15px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .status-title {
                font-size: 1.7rem;
            }
            
            .status-message {
                font-size: 1.1rem;
            }
            
            .total-price {
                font-size: 2rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <div class="header-content">
            <a href="customer_dashboard.php" class="logo">
                <i class="fas fa-spa"></i>
                <span>Home Glam</span>
            </a>
            <nav>
                <ul>
                    <li><a href="customer_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<main class="container">
    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="card status-card success">
                <i class="fas fa-check-circle status-icon"></i>
                <h2 class="status-title">Booking Confirmed Successfully!</h2>
                <p class="status-message">Your appointment has been scheduled. We've sent a confirmation to your contact details.</p>
                
                <div class="booking-details">
                    <h3 class="details-title"><i class="fas fa-receipt"></i> Booking Details</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Customer Name</span>
                            <span class="detail-value"><?php echo htmlspecialchars($customer_name); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact Number</span>
                            <span class="detail-value"><?php echo htmlspecialchars($customer_contact); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Booking Date</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime($booking_date)); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Booking Time</span>
                            <span class="detail-value"><?php echo date('g:i A', strtotime($booking_time)); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value" style="color: var(--warning);">Pending Confirmation</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Booking Reference</span>
                            <span class="detail-value">#<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                </div>

                <div class="price-display">
                    <div class="price-label">Total Amount</div>
                    <div class="total-price">
                        <i class="fas fa-tag"></i>
                        ৳ <?php echo number_format($final_price, 2); ?>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="customer_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-th-large"></i> Go to Dashboard
                    </a>
                    <a href="book_service.php?parlour_id=<?php echo $parlour_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Book Another Service
                    </a>
                </div>
            </div>
            
        <?php elseif (isset($error_message)): ?>
            <div class="card status-card error">
                <i class="fas fa-exclamation-circle status-icon"></i>
                <h2 class="status-title">Booking Failed</h2>
                <p class="status-message"><?php echo $error_message; ?></p>
                
                <div class="action-buttons">
                    <a href="book_service.php?parlour_id=<?php echo $parlour_id; ?>" class="btn btn-error">
                        <i class="fas fa-arrow-left"></i> Go Back to Booking
                    </a>
                    <a href="customer_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

</body>
</html>