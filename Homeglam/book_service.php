<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a customer
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

// Get the parlour_id from URL and validate it
$parlour_id = isset($_GET['parlour_id']) ? mysqli_real_escape_string($conn, $_GET['parlour_id']) : '';

if (empty($parlour_id)) {
    die("Error: No parlour ID provided");
}

// Debug: Output parlour ID for troubleshooting
echo "<!-- DEBUG: Parlour ID = " . $parlour_id . " -->";

// Force clear any potential query cache
mysqli_query($conn, "FLUSH QUERY CACHE");

// Fetch the services provided by the parlour with improved query
$sql = "SELECT s.id, s.parlour_id, s.service_name, s.price, s.service_image, s.created_at 
        FROM services s 
        INNER JOIN parlours p ON s.parlour_id = p.owner_id 
        WHERE p.id = '$parlour_id' 
        ORDER BY s.created_at DESC";
echo "<!-- DEBUG: Services SQL Query = " . $sql . " -->";

$result = mysqli_query($conn, $sql);

// Add error checking
if (!$result) {
    echo "<!-- DEBUG: Services Query Error = " . mysqli_error($conn) . " -->";
    die("Database error occurred while fetching services.");
} else {
    $num_services = mysqli_num_rows($result);
    echo "<!-- DEBUG: Found " . $num_services . " services for parlour " . $parlour_id . " -->";
}

// Fetch combo offers from the offers table
$combo_sql = "SELECT * FROM offers WHERE parlour_id = '$parlour_id'";
$combo_result = mysqli_query($conn, $combo_sql);

if (!$combo_result) {
    echo "<!-- DEBUG: Combo Query Error = " . mysqli_error($conn) . " -->";
}

// Fetch parlour details
$parlour_sql = "SELECT * FROM parlours WHERE id = '$parlour_id'";
$parlour_result = mysqli_query($conn, $parlour_sql);

if (!$parlour_result) {
    die("Error fetching parlour details: " . mysqli_error($conn));
}

$parlour = mysqli_fetch_assoc($parlour_result);

if (!$parlour) {
    die("Parlour not found with ID: " . $parlour_id);
}

// Fetch reviews for this parlour with rating statistics
$reviews_stats_sql = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star_count,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star_count,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star_count,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_count
    FROM parlour_reviews WHERE parlour_id = '$parlour_id'";
$reviews_stats_result = mysqli_query($conn, $reviews_stats_sql);
$reviews_stats = mysqli_fetch_assoc($reviews_stats_result);

// Fetch recent reviews (limit to 5 most recent)
$recent_reviews_sql = "SELECT * FROM parlour_reviews WHERE parlour_id = '$parlour_id' ORDER BY created_at DESC LIMIT 5";
$recent_reviews_result = mysqli_query($conn, $recent_reviews_sql);

// Calculate rating percentages
$total_reviews = $reviews_stats['total_reviews'] ?? 0;
if ($total_reviews > 0) {
    $average_rating = round($reviews_stats['average_rating'], 1);
    $five_star_percent = round(($reviews_stats['five_star_count'] / $total_reviews) * 100);
    $four_star_percent = round(($reviews_stats['four_star_count'] / $total_reviews) * 100);
    $three_star_percent = round(($reviews_stats['three_star_count'] / $total_reviews) * 100);
    $two_star_percent = round(($reviews_stats['two_star_count'] / $total_reviews) * 100);
    $one_star_percent = round(($reviews_stats['one_star_count'] / $total_reviews) * 100);
} else {
    $average_rating = 0;
    $five_star_percent = $four_star_percent = $three_star_percent = $two_star_percent = $one_star_percent = 0;
}

// Fetch service videos for this parlour
$videos_sql = "SELECT sv.*, s.service_name 
               FROM service_videos sv 
               JOIN services s ON sv.service_id = s.id 
               WHERE s.parlour_id = '$parlour_id' 
               ORDER BY sv.uploaded_at DESC";
$videos_result = mysqli_query($conn, $videos_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service - <?php echo htmlspecialchars($parlour['name']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #8a63d2;
            --primary-light: #9c7ddb;
            --primary-dark: #7849b9;
            --secondary: #ff7d9c;
            --accent: #6cdfcc;
            --dark: #2d2b55;
            --light: #f8f5ff;
            --gray: #9e9ea7;
            --success: #4cc9a4;
            --warning: #ffd166;
            --text-dark: #33334d;
            --text-light: #6a6a8d;
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-md: 0 5px 20px rgba(0,0,0,0.12);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f5ff 0%, #f0ebff 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 15px 0;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 50px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        nav a:hover {
            color: var(--primary);
            background-color: rgba(138, 99, 210, 0.1);
        }

        /* Main Layout */
        .booking-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 30px 0;
        }

        .main-booking-section {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-md);
        }

        .reviews-sidebar {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        /* Parlour Header */
        .parlour-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .parlour-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(25deg);
            pointer-events: none;
        }

        .parlour-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
        }

        .parlour-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
        }

        .parlour-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Section Titles */
        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Service Items */
        .services-section {
            margin-bottom: 30px;
        }

        .service-item, .combo-offer {
            background: white;
            border: 2px solid var(--light);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 15px;
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            overflow: hidden;
        }

        .service-item::before, .combo-offer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
            opacity: 0;
            transition: var(--transition);
        }

        .service-item:hover, .combo-offer:hover {
            border-color: var(--primary-light);
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .service-item:hover::before, .combo-offer:hover::before {
            opacity: 1;
        }

        .service-item input, .combo-offer input {
            margin-right: 0;
            transform: scale(1.3);
            accent-color: var(--primary);
        }

        /* Service Image */
        .service-image {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--light);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--transition);
        }

        .service-item:hover .service-image {
            border-color: var(--primary-light);
            transform: scale(1.05);
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .service-item:hover .service-image img {
            transform: scale(1.1);
        }

        .service-image .no-image {
            color: var(--gray);
            text-align: center;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .service-image .no-image i {
            font-size: 1.8rem;
            color: var(--primary-light);
        }

        /* Service Content */
        .service-content {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .service-details {
            flex: 1;
        }

        .service-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-price {
            color: var(--success);
            font-weight: bold;
            font-size: 1.2rem;
            text-align: right;
        }

        .service-item label, .combo-offer label {
            cursor: pointer;
            display: flex;
            align-items: center;
            width: 100%;
            gap: 15px;
            font-weight: 500;
            margin: 0;
        }

        /* Combo Offers */
        .combo-offer {
            background: linear-gradient(to right, #fdf7ff, #f8f5ff);
            border: 2px solid #e8daff;
        }

        .combo-offer:hover {
            background: linear-gradient(to right, #faf0ff, #f3ebff);
            border-color: var(--primary-light);
        }

        /* No Services Message */
        .no-services {
            text-align: center;
            padding: 50px 30px;
            background: var(--light);
            border-radius: var(--radius);
            color: var(--text-light);
            border: 2px dashed #d9d1ff;
        }

        .no-services i {
            font-size: 3.5rem;
            color: #d9d1ff;
            margin-bottom: 20px;
        }

        .no-services h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        /* Price Summary */
        .price-summary {
            background: linear-gradient(135deg, #f0fff8 0%, #e6f7ff 100%);
            border: 2px solid #b8f0d5;
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .total-price {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--success);
        }

        /* Customer Form */
        .customer-form {
            background: var(--light);
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input {
            padding: 14px 16px;
            border: 2px solid #e2dcff;
            border-radius: 12px;
            transition: var(--transition);
            font-family: inherit;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(138, 99, 210, 0.2);
        }

        /* Submit Button */
        .submit-button {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-md);
        }

        .submit-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .submit-button:disabled {
            background: var(--gray);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .submit-button:disabled:hover {
            transform: none;
            box-shadow: var(--shadow-md);
        }

        /* Reviews Sidebar */
        .rating-summary {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
        }

        .overall-rating {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .rating-number {
            font-size: 2.8rem;
            font-weight: bold;
            color: var(--warning);
        }

        .rating-details {
            flex: 1;
        }

        .stars-display {
            font-size: 1.4rem;
            color: var(--warning);
            margin-bottom: 5px;
        }

        .rating-text {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .rating-breakdown {
            margin-top: 15px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .rating-star {
            width: 20px;
            color: var(--warning);
        }

        .rating-bar {
            flex: 1;
            height: 8px;
            background: #f0eaff;
            border-radius: 10px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: var(--warning);
            transition: width 0.5s ease;
        }

        .rating-count {
            width: 35px;
            text-align: right;
            color: var(--text-light);
        }

        /* Recent Reviews */
        .recent-reviews {
            margin-top: 25px;
        }

        .reviews-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .review-item {
            background: var(--light);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--warning);
            transition: var(--transition);
        }

        .review-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .review-date {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .review-stars {
            color: var(--warning);
            margin-bottom: 10px;
        }

        .review-text {
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            color: var(--text-light);
            padding: 30px 20px;
            background: var(--light);
            border-radius: 14px;
        }

        /* Video Section Styles */
        .videos-section {
            margin-bottom: 30px;
        }

        .video-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .video-card {
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid var(--light);
        }

        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .video-thumbnail {
            position: relative;
            width: 100%;
            height: 160px;
            overflow: hidden;
            background: #f0f0f0;
        }

        .video-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-info {
            padding: 15px;
        }

        .video-info h4 {
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .video-info p {
            color: var(--text-light);
            margin-bottom: 5px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-videos {
            text-align: center;
            padding: 40px 20px;
            background: var(--light);
            border-radius: var(--radius);
            color: var(--text-light);
            border: 2px dashed #d9d1ff;
        }

        .no-videos i {
            font-size: 2.5rem;
            color: #d9d1ff;
            margin-bottom: 15px;
        }

        /* Footer */
        footer {
            background: white;
            color: var(--text-dark);
            text-align: center;
            padding: 30px;
            margin-top: 50px;
            border-top: 1px solid var(--light);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .booking-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .reviews-sidebar {
                position: static;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }

            .service-item, .combo-offer {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .service-content {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .service-image {
                margin: 0 auto;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                gap: 10px;
            }

            .video-gallery {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .parlour-details {
                flex-direction: column;
                gap: 10px;
            }
            
            .parlour-header {
                padding: 20px;
            }
            
            .main-booking-section, .reviews-sidebar {
                padding: 20px;
            }
            
            .service-image {
                width: 70px;
                height: 70px;
            }

            .video-gallery {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .service-item, .combo-offer, .review-item, .video-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>

<header>
    <div class="container header-content">
        <a href="customer_dashboard.php" class="logo">
            <i class="fas fa-spa"></i>
            Home Glam
        </a>
        <nav>
            <ul>
                <li><a href="customer_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container">
    <div class="booking-container">
        <!-- Main Booking Section -->
        <div class="main-booking-section">
            <!-- Parlour Header -->
            <div class="parlour-header">
                <h1 class="parlour-name"><?php echo htmlspecialchars($parlour['name']); ?></h1>
                <div class="parlour-details">
                    <div class="parlour-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($parlour['area']); ?></span>
                    </div>
                    <div class="parlour-detail">
                        <i class="fas fa-home"></i>
                        <span><?php echo htmlspecialchars($parlour['address']); ?></span>
                    </div>
                    <div class="parlour-detail">
                        <i class="fas fa-cut"></i>
                        <span><?php echo htmlspecialchars($parlour['services']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Service Videos Section -->
            <?php if ($videos_result && mysqli_num_rows($videos_result) > 0): ?>
                <div class="videos-section">
                    <h2 class="section-title"><i class="fas fa-video"></i> Service Videos</h2>
                    <div class="video-gallery">
                        <?php while ($video = mysqli_fetch_assoc($videos_result)): ?>
                            <div class="video-card">
                                <div class="video-thumbnail">
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                                <div class="video-info">
                                    <h4><?php echo htmlspecialchars($video['video_title']); ?></h4>
                                    <p><i class="fas fa-cut"></i> Service: <?php echo htmlspecialchars($video['service_name']); ?></p>
                                    <p><i class="fas fa-calendar"></i> Uploaded: <?php echo date('M j, Y', strtotime($video['uploaded_at'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="videos-section">
                    <h2 class="section-title"><i class="fas fa-video"></i> Service Videos</h2>
                    <div class="no-videos">
                        <i class="fas fa-video-slash"></i>
                        <h3>No Videos Available</h3>
                        <p>This parlour hasn't uploaded any service videos yet.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form action="confirm_booking.php" method="POST" id="bookingForm">
                <!-- Combo Offers Section -->
                <?php if ($combo_result && mysqli_num_rows($combo_result) > 0): ?>
                    <div class="services-section">
                        <h2 class="section-title"><i class="fas fa-gift"></i> Special Combo Offers</h2>
                        <?php while ($combo_offer = mysqli_fetch_assoc($combo_result)): ?>
                            <div class="combo-offer">
                                <label>
                                    <input type="checkbox" name="combo_offers[]" value="<?php echo $combo_offer['id']; ?>" data-price="<?php echo $combo_offer['discount_percentage']; ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($combo_offer['offer_name']); ?></strong>
                                        <br>
                                        <span style="color: var(--secondary); font-weight: bold;"><?php echo $combo_offer['discount_percentage']; ?>% OFF</span>
                                        <br>
                                        <small>Includes: <?php echo htmlspecialchars($combo_offer['services_included']); ?></small>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <!-- Individual Services Section -->
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="services-section">
                        <h2 class="section-title"><i class="fas fa-concierge-bell"></i> Available Services</h2>
                        <?php 
                        $service_counter = 0;
                        while ($service = mysqli_fetch_assoc($result)): 
                            $service_counter++;
                        ?>
                            <div class="service-item">
                                <label>
                                    <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                    
                                    <!-- Service Image -->
                                    <div class="service-image">
                                        <?php if (!empty($service['service_image']) && file_exists($service['service_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($service['service_image']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-spa"></i>
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Service Content -->
                                    <div class="service-content">
                                        <div class="service-details">
                                            <div class="service-name">
                                                <i class="fas fa-spa"></i>
                                                <?php echo htmlspecialchars($service['service_name']); ?>
                                            </div>
                                        </div>
                                        <div class="service-price">
                                            ৳ <?php echo number_format($service['price'], 2); ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-services">
                        <i class="fas fa-spa"></i>
                        <h3>No Services Available</h3>
                        <p>This parlour hasn't added any services yet.</p>
                        <div class="debug-info">
                            <strong>Debug Info:</strong><br>
                            Parlour ID: <?php echo htmlspecialchars($parlour_id); ?><br>
                            Services Query Result: <?php echo $result ? 'Query executed' : 'Query failed'; ?><br>
                            <?php if ($result): ?>
                                Number of services found: <?php echo mysqli_num_rows($result); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Price Summary -->
                <div class="price-summary" id="priceSummary">
                    <div class="total-price">Total: <span id="totalAmount">0.00</span> BDT</div>
                </div>

                <!-- Customer Details Form -->
                <div class="customer-form">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Your Details</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" name="name" id="name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact">Contact Number *</label>
                            <input type="tel" name="contact" id="contact" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="address">Address *</label>
                            <input type="text" name="address" id="address" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="booking_date">Preferred Date *</label>
                            <input type="date" name="booking_date" id="booking_date" required>
                        </div>
                        <div class="form-group">
                            <label for="booking_time">Preferred Time *</label>
                            <input type="time" name="booking_time" id="booking_time" required>
                        </div>
                    </div>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" name="parlour_id" value="<?php echo $parlour_id; ?>">
                <input type="hidden" name="customer_id" value="<?php echo $_SESSION['user_id']; ?>">
                <input type="hidden" name="total_price" id="total_price" value="0">

                <button type="submit" class="submit-button" id="submitBtn" disabled>
                    <i class="fas fa-calendar-check"></i> Confirm Booking
                </button>
            </form>
        </div>

        <!-- Reviews Sidebar -->
        <div class="reviews-sidebar">
            <?php if ($total_reviews > 0): ?>
                <!-- Rating Summary -->
                <div class="rating-summary">
                    <h3 style="margin-bottom: 15px; color: var(--text-dark);">Customer Reviews</h3>
                    
                    <div class="overall-rating">
                        <div class="rating-number"><?php echo $average_rating; ?></div>
                        <div class="rating-details">
                            <div class="stars-display">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= floor($average_rating)) ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="rating-text">Based on <?php echo $total_reviews; ?> review<?php echo $total_reviews != 1 ? 's' : ''; ?></div>
                        </div>
                    </div>

                    <div class="rating-breakdown">
                        <div class="rating-row">
                            <span class="rating-star">5★</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $five_star_percent; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $five_star_percent; ?>%</span>
                        </div>
                        <div class="rating-row">
                            <span class="rating-star">4★</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $four_star_percent; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $four_star_percent; ?>%</span>
                        </div>
                        <div class="rating-row">
                            <span class="rating-star">3★</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $three_star_percent; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $three_star_percent; ?>%</span>
                        </div>
                        <div class="rating-row">
                            <span class="rating-star">2★</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $two_star_percent; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $two_star_percent; ?>%</span>
                        </div>
                        <div class="rating-row">
                            <span class="rating-star">1★</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $one_star_percent; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $one_star_percent; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Reviews -->
                <div class="recent-reviews">
                    <h4 class="reviews-title"><i class="fas fa-comments"></i> Recent Reviews</h4>
                    <?php while ($review = mysqli_fetch_assoc($recent_reviews_result)): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-name"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                                <div class="review-date"><?php echo date('M j', strtotime($review['created_at'])); ?></div>
                            </div>
                            <div class="review-stars">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo ($i <= $review['rating']) ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <div class="review-text">
                                "<?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?><?php echo strlen($review['review_text']) > 100 ? '...' : ''; ?>"
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <h3><i class="fas fa-pencil-alt"></i> No Reviews Yet</h3>
                    <p>Be the first to review this parlour after your service!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const totalPriceInput = document.getElementById('total_price');
    const totalAmountDisplay = document.getElementById('totalAmount');
    const priceSummary = document.getElementById('priceSummary');
    const submitBtn = document.getElementById('submitBtn');
    const bookingDateInput = document.getElementById('booking_date');
    
    let totalPrice = 0;

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    bookingDateInput.setAttribute('min', today);

    // Add event listeners to update total price
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const price = parseFloat(this.getAttribute('data-price'));
            
            if (this.checked) {
                totalPrice += price;
            } else {
                totalPrice -= price;
            }
            
            // Update displays
            totalPriceInput.value = totalPrice;
            totalAmountDisplay.textContent = totalPrice.toFixed(2);
            
            // Show/hide price summary and enable/disable submit button
            if (totalPrice > 0) {
                priceSummary.style.display = 'block';
                submitBtn.disabled = false;
            } else {
                priceSummary.style.display = 'none';
                submitBtn.disabled = true;
            }
        });
    });

    // Form validation before submit
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        const checkedServices = document.querySelectorAll('input[type="checkbox"]:checked');
        
        if (checkedServices.length === 0) {
            e.preventDefault();
            alert('Please select at least one service or combo offer.');
            return false;
        }
        
        if (totalPrice <= 0) {
            e.preventDefault();
            alert('Please select services before booking.');
            return false;
        }
        
        return true;
    });

    // Debug: Log services found on page load
    console.log('Services checkboxes found:', checkboxes.length);
    console.log('Parlour ID from URL:', new URLSearchParams(window.location.search).get('parlour_id'));
});
</script>

</body>
</html>