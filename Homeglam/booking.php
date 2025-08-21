<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if this is a POST request (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is form processing code (for booking confirmation)
    
    // Initialize variables
    $customer_name = '';
    $customer_contact = '';
    $customer_address = '';
    $booking_date = '';
    $booking_time = '';
    $parlour_id = '';
    $services = [];
    $combo_offers = [];
    $error_message = '';

    // Check if all required POST data exists
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $customer_name = mysqli_real_escape_string($conn, trim($_POST['name']));
    } else {
        $error_message .= "Customer name is required.<br>";
    }

    if (isset($_POST['contact']) && !empty(trim($_POST['contact']))) {
        $customer_contact = mysqli_real_escape_string($conn, trim($_POST['contact']));
    } else {
        $error_message .= "Customer contact is required.<br>";
    }

    if (isset($_POST['address']) && !empty(trim($_POST['address']))) {
        $customer_address = mysqli_real_escape_string($conn, trim($_POST['address']));
    } else {
        $error_message .= "Customer address is required.<br>";
    }

    if (isset($_POST['booking_date']) && !empty(trim($_POST['booking_date']))) {
        $booking_date = mysqli_real_escape_string($conn, trim($_POST['booking_date']));
    } else {
        $error_message .= "Booking date is required.<br>";
    }

    if (isset($_POST['booking_time']) && !empty(trim($_POST['booking_time']))) {
        $booking_time = mysqli_real_escape_string($conn, trim($_POST['booking_time']));
    } else {
        $error_message .= "Booking time is required.<br>";
    }

    if (isset($_POST['parlour_id']) && !empty(trim($_POST['parlour_id']))) {
        $parlour_id = mysqli_real_escape_string($conn, trim($_POST['parlour_id']));
        
        // Validate that parlour_id exists
        $parlour_check_sql = "SELECT id FROM parlours WHERE id = '$parlour_id'";
        $parlour_check_result = mysqli_query($conn, $parlour_check_sql);
        
        if (mysqli_num_rows($parlour_check_result) == 0) {
            $error_message .= "Invalid parlour ID.<br>";
        }
    } else {
        $error_message .= "Parlour ID is required.<br>";
    }

    $services = isset($_POST['services']) ? $_POST['services'] : [];
    $combo_offers = isset($_POST['combo_offers']) ? $_POST['combo_offers'] : [];
    $user_id = $_SESSION['user_id'];

    // Only proceed if no validation errors
    if (empty($error_message)) {
        $final_price = 0;
        $valid_services = true;
        $valid_combo_offers = true;

        // Validate and calculate services
        foreach ($services as $service_id) {
            $service_check_sql = "SELECT price FROM services WHERE id = '$service_id' AND parlour_id = '$parlour_id'";
            $service_check_result = mysqli_query($conn, $service_check_sql);

            if (mysqli_num_rows($service_check_result) == 0) {
                $valid_services = false;
                $error_message = "Invalid service ID $service_id.";
                break;
            } else {
                $service = mysqli_fetch_assoc($service_check_result);
                $final_price += $service['price'];
            }
        }

        // Validate and apply combo offers
        if ($valid_services) {
            foreach ($combo_offers as $combo_offer_id) {
                $combo_check_sql = "SELECT discount_percentage FROM offers WHERE id = '$combo_offer_id' AND parlour_id = '$parlour_id'";
                $combo_check_result = mysqli_query($conn, $combo_check_sql);

                if (mysqli_num_rows($combo_check_result) == 0) {
                    $valid_combo_offers = false;
                    $error_message = "Invalid combo offer ID $combo_offer_id.";
                    break;
                } else {
                    $combo_offer = mysqli_fetch_assoc($combo_check_result);
                    $combo_discount = $combo_offer['discount_percentage'];
                    $final_price -= ($final_price * ($combo_discount / 100));
                }
            }
        }

        // Insert booking if everything is valid
        if ($valid_services && $valid_combo_offers) {
            $sql = "INSERT INTO bookings (parlour_id, customer_name, customer_contact, customer_address, booking_date, booking_time, booking_status, total_price, user_id) 
                    VALUES ('$parlour_id', '$customer_name', '$customer_contact', '$customer_address', '$booking_date', '$booking_time', 'pending', '$final_price', '$user_id')";

            if (mysqli_query($conn, $sql)) {
                $booking_id = mysqli_insert_id($conn);

                // Insert services
                foreach ($services as $service_id) {
                    $sql = "INSERT INTO booking_services (booking_id, service_id) VALUES ('$booking_id', '$service_id')";
                    mysqli_query($conn, $sql);
                }

                // Insert combo offers
                foreach ($combo_offers as $combo_offer_id) {
                    $sql = "INSERT INTO booking_combo_offers (booking_id, combo_offer_id) VALUES ('$booking_id', '$combo_offer_id')";
                    mysqli_query($conn, $sql);
                }

                $success_message = "Booking confirmed successfully!";
            } else {
                $error_message = "Error booking service: " . mysqli_error($conn);
            }
        }
    }
    
    // Display booking confirmation result
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Confirmation - Home Glam</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #7E57C2;
                --primary-light: #B39DDB;
                --primary-dark: #5E35B1;
                --secondary: #FF80AB;
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
                padding: 20px;
            }

            header {
                background: linear-gradient(120deg, var(--primary), var(--primary-dark));
                color: white;
                padding: 24px 32px;
                border-radius: var(--radius-lg);
                margin-bottom: 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: var(--shadow);
            }

            header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            header a {
                color: white;
                text-decoration: none;
                background: rgba(255,255,255,0.15);
                padding: 10px 20px;
                border-radius: var(--radius);
                transition: var(--transition);
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            header a:hover {
                background: rgba(255,255,255,0.25);
                transform: translateY(-2px);
            }

            .main-content {
                max-width: 700px;
                margin: 0 auto;
            }

            .message-card {
                background: white;
                border-radius: var(--radius-lg);
                padding: 32px;
                margin-bottom: 24px;
                box-shadow: var(--shadow);
                border-left: 5px solid;
                text-align: center;
                transition: var(--transition);
            }

            .message-card:hover {
                transform: translateY(-5px);
                box-shadow: var(--shadow-lg);
            }

            .success-message {
                border-left-color: var(--success);
                background: linear-gradient(to right, #E8F5E9, #F1F8E9);
            }

            .error-message {
                border-left-color: var(--danger);
                background: linear-gradient(to right, #FFEBEE, #FCE4EC);
            }

            .message-icon {
                font-size: 48px;
                margin-bottom: 16px;
            }

            .success-message .message-icon {
                color: var(--success);
            }

            .error-message .message-icon {
                color: var(--danger);
            }

            .message-card h2 {
                color: var(--gray-800);
                margin-bottom: 16px;
                font-size: 24px;
            }

            .message-card p {
                margin-bottom: 12px;
                font-size: 16px;
                color: var(--gray-700);
            }

            .price-display {
                font-size: 24px;
                font-weight: 700;
                color: var(--primary-dark);
                margin: 20px 0;
                padding: 16px;
                background: rgba(126, 87, 194, 0.1);
                border-radius: var(--radius);
                display: inline-block;
            }

            .action-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                background: linear-gradient(120deg, var(--primary), var(--primary-dark));
                color: white;
                text-decoration: none;
                border-radius: var(--radius);
                font-weight: 500;
                transition: var(--transition);
                margin-top: 16px;
                box-shadow: var(--shadow);
            }

            .action-button:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }

            @media (max-width: 768px) {
                body {
                    padding: 16px;
                }
                
                header {
                    flex-direction: column;
                    gap: 16px;
                    text-align: center;
                    padding: 20px;
                }
                
                header h1 {
                    font-size: 24px;
                }
                
                .message-card {
                    padding: 24px;
                }
                
                .message-icon {
                    font-size: 40px;
                }
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .message-card {
                animation: fadeIn 0.6s ease-out;
            }
        </style>
    </head>
    <body>
        <header>
            <h1><i class="fas fa-calendar-check"></i> Booking Confirmation</h1>
            <a href="customer_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </header>
        
        <div class="main-content">
            <?php if (isset($success_message)): ?>
                <div class="message-card success-message">
                    <div class="message-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Booking Successful!</h2>
                    <p><?php echo $success_message; ?></p>
                    <div class="price-display">
                        Total: ৳ <?php echo number_format($final_price, 2); ?>
                    </div>
                    <br>
                    <a href="customer_dashboard.php" class="action-button">
                        <i class="fas fa-th-large"></i> Go to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <div class="message-card error-message">
                    <div class="message-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h2>Booking Failed</h2>
                    <p><?php echo $error_message; ?></p>
                    <br>
                    <?php if (!empty($parlour_id)): ?>
                        <a href="book_service.php?parlour_id=<?php echo $parlour_id; ?>" class="action-button">
                            <i class="fas fa-arrow-left"></i> Back to Booking
                        </a>
                    <?php else: ?>
                        <a href="customer_dashboard.php" class="action-button">
                            <i class="fas fa-th-large"></i> Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    
} else {
    // This is a GET request (direct access) - show manage bookings page
    
    // Check user role to determine what bookings to show
    $user_role_sql = "SELECT role FROM users WHERE id = '{$_SESSION['user_id']}'";
    $user_role_result = mysqli_query($conn, $user_role_sql);
    $user_role = mysqli_fetch_assoc($user_role_result)['role'];
    
    if ($user_role === 'parlour_owner') {
        // Show bookings for parlour owner's parlours
        $parlour_sql = "SELECT id FROM parlours WHERE owner_id = '{$_SESSION['user_id']}'";
        $parlour_result = mysqli_query($conn, $parlour_sql);
        
        $parlour_ids = [];
        while ($parlour = mysqli_fetch_assoc($parlour_result)) {
            $parlour_ids[] = $parlour['id'];
        }
        
        if (!empty($parlour_ids)) {
            $parlour_ids_str = implode(',', $parlour_ids);
            $bookings_sql = "SELECT b.*, p.name as parlour_name 
                           FROM bookings b 
                           JOIN parlours p ON b.parlour_id = p.id 
                           WHERE b.parlour_id IN ($parlour_ids_str) 
                           ORDER BY b.booking_date DESC, b.booking_time DESC";
        } else {
            $bookings_sql = "SELECT b.*, p.name as parlour_name FROM bookings b JOIN parlours p ON b.parlour_id = p.id WHERE 1=0"; // No results
        }
    } else {
        // Show bookings for regular customer
        $bookings_sql = "SELECT b.*, p.name as parlour_name 
                        FROM bookings b 
                        JOIN parlours p ON b.parlour_id = p.id 
                        WHERE b.user_id = '{$_SESSION['user_id']}' 
                        ORDER BY b.booking_date DESC, b.booking_time DESC";
    }
    
    $bookings_result = mysqli_query($conn, $bookings_sql);
    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Bookings - Home Glam</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #7E57C2;
                --primary-light: #B39DDB;
                --primary-dark: #5E35B1;
                --secondary: #FF80AB;
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
                padding: 20px;
            }

            header {
                background: linear-gradient(120deg, var(--primary), var(--primary-dark));
                color: white;
                padding: 24px 32px;
                border-radius: var(--radius-lg);
                margin-bottom: 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: var(--shadow);
            }

            header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            header a {
                color: white;
                text-decoration: none;
                background: rgba(255,255,255,0.15);
                padding: 10px 20px;
                border-radius: var(--radius);
                transition: var(--transition);
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            header a:hover {
                background: rgba(255,255,255,0.25);
                transform: translateY(-2px);
            }

            .main-content {
                background: white;
                padding: 32px;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow);
                margin-bottom: 30px;
            }

            .message-card {
                padding: 20px;
                border-radius: var(--radius);
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .success-message {
                background: #E8F5E9;
                border-left: 4px solid var(--success);
                color: #2E7D32;
            }

            .error-message {
                background: #FFEBEE;
                border-left: 4px solid var(--danger);
                color: #C62828;
            }

            .message-card i {
                font-size: 24px;
            }

            .bookings-table-container {
                overflow-x: auto;
                border-radius: var(--radius);
                box-shadow: var(--shadow-sm);
                margin-top: 24px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: var(--radius);
                overflow: hidden;
            }

            th {
                background: linear-gradient(to right, var(--primary-light), var(--primary));
                color: white;
                padding: 16px 20px;
                text-align: left;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            td {
                padding: 16px 20px;
                border-bottom: 1px solid var(--gray-200);
                color: var(--gray-700);
            }

            tr:hover {
                background: var(--gray-100);
                transition: var(--transition);
            }

            tr:last-child td {
                border-bottom: none;
            }

            .status-badge {
                padding: 6px 12px;
                border-radius: 50px;
                font-size: 12px;
                font-weight: 600;
                display: inline-block;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .status-confirmed {
                background: rgba(100, 221, 23, 0.15);
                color: var(--success);
            }

            .status-pending {
                background: rgba(255, 214, 0, 0.15);
                color: var(--warning);
            }

            .status-cancelled {
                background: rgba(255, 82, 82, 0.15);
                color: var(--danger);
            }

            .action-button {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: var(--radius);
                font-size: 13px;
                font-weight: 500;
                text-decoration: none;
                transition: var(--transition);
                margin-right: 8px;
            }

            .confirm-button {
                background: var(--success);
                color: white;
            }

            .confirm-button:hover {
                background: #4CAF50;
                transform: translateY(-2px);
            }

            .cancel-button {
                background: var(--danger);
                color: white;
            }

            .cancel-button:hover {
                background: #D32F2F;
                transform: translateY(-2px);
            }

            .no-bookings {
                text-align: center;
                padding: 60px 20px;
                color: var(--gray-500);
            }

            .no-bookings i {
                font-size: 64px;
                margin-bottom: 16px;
                color: var(--gray-300);
            }

            .no-bookings h3 {
                font-size: 20px;
                margin-bottom: 8px;
                color: var(--gray-600);
            }

            .no-bookings p {
                font-size: 16px;
                color: var(--gray-500);
            }

            @media (max-width: 1024px) {
                table {
                    min-width: 900px;
                }
            }

            @media (max-width: 768px) {
                body {
                    padding: 16px;
                }
                
                header {
                    flex-direction: column;
                    gap: 16px;
                    text-align: center;
                    padding: 20px;
                }
                
                header h1 {
                    font-size: 24px;
                }
                
                .main-content {
                    padding: 24px;
                }
                
                th, td {
                    padding: 12px 16px;
                }
                
                .action-button {
                    padding: 6px 12px;
                    font-size: 12px;
                }
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .main-content {
                animation: fadeIn 0.6s ease-out;
            }

            tr {
                animation: fadeIn 0.4s ease-out;
            }
        </style>
    </head>
    <body>
    <header>
        <h1><i class="fas fa-calendar-alt"></i> Manage Bookings</h1>
        <a href="<?php echo ($user_role === 'parlour_owner') ? 'parlour_dashboard.php' : 'customer_dashboard.php'; ?>">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </header>
    
    <div class="main-content">
        <?php
        // Display success/error messages from session
        if (isset($_SESSION['booking_message'])) {
            $message_class = ($_SESSION['message_type'] === 'success') ? 'success-message' : 'error-message';
            echo "<div class='message-card $message_class'>";
            echo "<i class='fas " . ($_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') . "'></i>";
            echo "<div>" . $_SESSION['booking_message'] . "</div>";
            echo "</div>";
            
            // Clear the session message after displaying
            unset($_SESSION['booking_message']);
            unset($_SESSION['message_type']);
        }
        ?>
        
        <?php if (mysqli_num_rows($bookings_result) > 0): ?>
            <div class="bookings-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Parlour</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Price</th>
                            <?php if ($user_role === 'parlour_owner'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                            <tr>
                                <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                <td><?php echo $booking['parlour_name']; ?></td>
                                <td>
                                    <div><strong><?php echo $booking['customer_name']; ?></strong></div>
                                    <div style="font-size: 12px; color: var(--gray-500);"><?php echo substr($booking['customer_address'], 0, 30); ?>...</div>
                                </td>
                                <td><?php echo $booking['customer_contact']; ?></td>
                                <td>
                                    <div><strong><?php echo $booking['booking_date']; ?></strong></div>
                                    <div style="font-size: 12px; color: var(--gray-500);"><?php echo $booking['booking_time']; ?></div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td><strong>৳ <?php echo number_format($booking['total_price'], 2); ?></strong></td>
                                <?php if ($user_role === 'parlour_owner'): ?>
                                    <td>
                                        <?php if ($booking['booking_status'] === 'pending'): ?>
                                            <a href="update_booking_status.php?id=<?php echo $booking['id']; ?>&status=confirmed" 
                                               class="action-button confirm-button"
                                               onclick="return confirm('Are you sure you want to confirm this booking?')">
                                               <i class="fas fa-check"></i> Confirm
                                            </a>
                                            <a href="update_booking_status.php?id=<?php echo $booking['id']; ?>&status=cancelled" 
                                               class="action-button cancel-button"
                                               onclick="return confirm('Are you sure you want to cancel this booking?')">
                                               <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-bookings">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You don't have any bookings yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    </body>
    </html>
    <?php
}
?>