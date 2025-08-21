<?php
session_start();
include('db.php'); // Include database connection

// Ensure the user is logged in as a parlour owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parlour_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the parlour_id for this owner
$parlour_sql = "SELECT id, name FROM parlours WHERE owner_id = '$user_id'";
$parlour_result = mysqli_query($conn, $parlour_sql);

if (mysqli_num_rows($parlour_result) == 0) {
    die("No parlour found for this owner. Please create your parlour profile first.");
}

$parlour_data = mysqli_fetch_assoc($parlour_result);
$parlour_id = $parlour_data['id'];
$parlour_name = $parlour_data['name'];

// Fetch reviews for this parlour with customer profile pictures
$reviews_sql = "SELECT pr.*, c.profile_picture, c.first_name, c.last_name 
                FROM parlour_reviews pr 
                LEFT JOIN customers c ON pr.customer_id = c.user_id 
                WHERE pr.parlour_id = '$parlour_id' 
                ORDER BY pr.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);

// Check for query errors
if (!$reviews_result) {
    die("Error fetching reviews: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Reviews - <?php echo htmlspecialchars($parlour_name); ?></title>
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

    /* Main Content */
    .main-content {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 2rem;
    }

    .reviews-section {
      background: linear-gradient(to bottom, white, var(--gray-100));
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: var(--shadow);
      border: 1px solid var(--gray-200);
    }
    
    .review-card {
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

    .review-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      opacity: 0.8;
    }

    .review-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
    }
    
    .review-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
      border-bottom: 1px solid var(--gray-200);
      padding-bottom: 1rem;
    }
    
    .customer-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .customer-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid var(--primary-light);
      flex-shrink: 0;
    }
    
    .customer-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .customer-avatar-placeholder {
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      font-weight: 600;
    }
    
    .customer-details {
      display: flex;
      flex-direction: column;
    }
    
    .customer-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--primary-dark);
    }
    
    .review-date {
      color: var(--gray-500);
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .rating {
      display: flex;
      align-items: center;
      margin: 1rem 0;
    }
    
    .stars {
      color: #FFD700;
      margin-right: 0.75rem;
      font-size: 1.3rem;
      letter-spacing: 2px;
    }
    
    .rating-text {
      color: var(--gray-600);
      font-weight: 500;
      background: var(--gray-100);
      padding: 0.25rem 0.75rem;
      border-radius: var(--radius);
    }
    
    .review-text {
      background: var(--gray-100);
      padding: 1.25rem;
      border-radius: var(--radius);
      border-left: 4px solid var(--primary);
      font-style: italic;
      line-height: 1.6;
      margin-top: 1rem;
      color: var(--gray-700);
      position: relative;
    }

    .review-text::before {
      content: '"';
      font-size: 3rem;
      color: var(--primary-light);
      position: absolute;
      top: -0.5rem;
      left: 0.5rem;
      opacity: 0.3;
      font-family: serif;
    }
    
    .no-reviews {
      text-align: center;
      padding: 3rem;
      color: var(--gray-500);
      background: var(--gray-100);
      border-radius: var(--radius);
      border: 2px dashed var(--gray-300);
    }

    .no-reviews h3 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: var(--gray-600);
    }

    .no-reviews p {
      font-size: 1.1rem;
      max-width: 500px;
      margin: 0 auto;
    }
    
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: linear-gradient(120deg, var(--primary), var(--primary-dark));
      color: white;
      padding: 0.75rem 1.5rem;
      text-decoration: none;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      transition: var(--transition);
      font-weight: 500;
      box-shadow: var(--shadow);
    }
    
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
      background: linear-gradient(120deg, var(--primary-dark), var(--primary));
    }
    
    .stats-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      background: linear-gradient(135deg, var(--primary-light), var(--primary));
      padding: 2rem;
      border-radius: var(--radius);
      margin-bottom: 2rem;
      color: white;
      box-shadow: var(--shadow);
    }
    
    .stat-item {
      text-align: center;
      padding: 1.5rem;
      background: rgba(255, 255, 255, 0.15);
      border-radius: var(--radius);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: white;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-label {
      color: rgba(255, 255, 255, 0.9);
      font-size: 1rem;
      font-weight: 500;
    }
    
    .parlour-title {
      text-align: center;
      color: var(--primary-dark);
      margin-bottom: 2rem;
      padding: 2rem;
      background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
      border-radius: var(--radius);
      border: 1px solid var(--gray-200);
      box-shadow: var(--shadow-sm);
    }

    .parlour-title h2 {
      font-size: 1.8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
    }

    /* Footer */
    footer {
      background: var(--dark);
      color: white;
      text-align: center;
      padding: 1.5rem;
      margin-top: 3rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
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

      .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .customer-info {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
      }
      
      .customer-avatar {
        width: 50px;
        height: 50px;
      }

      .stats-summary {
        grid-template-columns: 1fr;
      }

      .main-content {
        padding: 0 1rem;
      }
      
      .reviews-section {
        padding: 1.5rem;
      }
    }

    /* Animation for elements */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .review-card {
      animation: fadeIn 0.5s ease-out;
    }
  </style>
</head>
<body>

<!-- Header Section -->
<header>
  <div class="header-content">
    <h1>Customer Reviews</h1>
    <div class="header-nav">
      <nav>
        <ul>
          <li><a href="parlour_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
          <li><a href="parlour_profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
    </div>
  </div>
</header>

<!-- Main Content Section -->
<div class="main-content">
  <section class="reviews-section">
    
    <div class="parlour-title">
      <h2><i class="fas fa-store"></i> Reviews for <?php echo htmlspecialchars($parlour_name); ?></h2>
    </div>

    <!-- Back to Dashboard Button -->
    <a href="parlour_dashboard.php" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <?php
    if (mysqli_num_rows($reviews_result) > 0) {
        // Calculate statistics
        $total_reviews = mysqli_num_rows($reviews_result);
        $total_rating = 0;
        $reviews_data = [];
        
        // Store all reviews and calculate total rating
        while ($review = mysqli_fetch_assoc($reviews_result)) {
            $reviews_data[] = $review;
            $total_rating += $review['rating'];
        }
        
        $average_rating = round($total_rating / $total_reviews, 1);
        
        // Display statistics
        echo "<div class='stats-summary'>
                <div class='stat-item'>
                  <div class='stat-number'>$total_reviews</div>
                  <div class='stat-label'><i class='fas fa-comments'></i> Total Reviews</div>
                </div>
                <div class='stat-item'>
                  <div class='stat-number'>$average_rating</div>
                  <div class='stat-label'><i class='fas fa-star'></i> Average Rating</div>
                </div>
                <div class='stat-item'>
                  <div class='stat-number'>5</div>
                  <div class='stat-label'><i class='fas fa-medal'></i> Max Rating</div>
                </div>
              </div>";
        
        // Display individual reviews
        foreach ($reviews_data as $review) {
            // Generate star display
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $review['rating']) {
                    $stars .= '★';
                } else {
                    $stars .= '☆';
                }
            }
            
            // Get customer name (use first_name + last_name if available, otherwise fallback to customer_name)
            $customer_name = '';
            if (!empty($review['first_name']) || !empty($review['last_name'])) {
                $customer_name = trim($review['first_name'] . ' ' . $review['last_name']);
            } else {
                $customer_name = $review['customer_name'];
            }
            
            // Get customer initials for placeholder
            $initials = '';
            $name_parts = explode(' ', $customer_name);
            if (count($name_parts) > 0) {
                $initials = strtoupper(substr($name_parts[0], 0, 1));
                if (count($name_parts) > 1) {
                    $initials .= strtoupper(substr($name_parts[1], 0, 1));
                }
            }
            
            echo "<div class='review-card'>
                    <div class='review-header'>
                      <div class='customer-info'>
                        <div class='customer-avatar'>";
            
            if (!empty($review['profile_picture']) && file_exists($review['profile_picture'])) {
                echo "<img src='" . htmlspecialchars($review['profile_picture']) . "' alt='" . htmlspecialchars($customer_name) . "'>";
            } else {
                echo "<div class='customer-avatar-placeholder'>$initials</div>";
            }
            
            echo "        </div>
                        <div class='customer-details'>
                          <div class='customer-name'><i class='fas fa-user-circle'></i> " . htmlspecialchars($customer_name) . "</div>
                          <div class='review-date'><i class='fas fa-calendar-alt'></i> " . date('M j, Y', strtotime($review['created_at'])) . "</div>
                        </div>
                      </div>
                    </div>
                    
                    <div class='rating'>
                      <div class='stars'>$stars</div>
                      <div class='rating-text'>" . $review['rating'] . " out of 5</div>
                    </div>
                    
                    <div class='review-text'>
                      " . htmlspecialchars($review['review_text']) . "
                    </div>
                  </div>";
        }
    } else {
        echo "<div class='no-reviews'>
                <h3><i class='far fa-frown'></i> No Reviews Yet</h3>
                <p>Your parlour hasn't received any reviews yet. Keep providing excellent service and reviews will come!</p>
              </div>";
    }
    ?>

  </section>
</div>

<!-- Footer Section -->
<footer>
  <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

</body>
</html>