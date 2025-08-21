<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

if (!isset($_GET['parlour_id'])) {
    header("Location: customer_dashboard.php");
    exit;
}

$parlour_id = $_GET['parlour_id'];

// Fetch parlour details
$parlour_sql = "SELECT * FROM parlours WHERE id = '$parlour_id'";
$parlour_result = mysqli_query($conn, $parlour_sql);

if (mysqli_num_rows($parlour_result) == 0) {
    die("Parlour not found.");
}

$parlour = mysqli_fetch_assoc($parlour_result);

// Check if customer already reviewed this parlour
$existing_review_sql = "SELECT * FROM parlour_reviews WHERE customer_id = '$customer_id' AND parlour_id = '$parlour_id'";
$existing_review_result = mysqli_query($conn, $existing_review_sql);

if (mysqli_num_rows($existing_review_result) > 0) {
    $already_reviewed = true;
    $existing_review = mysqli_fetch_assoc($existing_review_result);
} else {
    $already_reviewed = false;
}

// Handle review submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$already_reviewed) {
    $rating = (int)$_POST['rating'];
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
    
    $insert_sql = "INSERT INTO parlour_reviews (customer_id, customer_name, parlour_id, rating, review_text, created_at) 
                   VALUES ('$customer_id', '$customer_name', '$parlour_id', '$rating', '$review_text', NOW())";
    
    if (mysqli_query($conn, $insert_sql)) {
        $success_message = "Thank you for your review! It has been submitted successfully.";
        $already_reviewed = true;
    } else {
        $error_message = "Error submitting review: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review <?php echo htmlspecialchars($parlour['name']); ?> - Home Glam</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .review-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .parlour-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #e74c3c;
        }
        
        .rating-group {
            margin: 20px 0;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .star {
            font-size: 2em;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star:hover,
        .star.active {
            color: #f39c12;
        }
        
        .review-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .submit-btn {
            background: #e74c3c;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background 0.3s;
        }
        
        .submit-btn:hover {
            background: #c0392b;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .already-reviewed {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <h1>Review Parlour</h1>
        <nav>
            <ul>
                <li><a href="customer_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="review-container">
    <div class="parlour-info">
        <h3>⭐ <?php echo htmlspecialchars($parlour['name']); ?></h3>
        <p><strong>📍 Area:</strong> <?php echo htmlspecialchars($parlour['area']); ?></p>
        <p><strong>📧 Address:</strong> <?php echo htmlspecialchars($parlour['address']); ?></p>
        <p><strong>🎯 Services:</strong> <?php echo htmlspecialchars($parlour['services']); ?></p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($already_reviewed): ?>
        <div class="already-reviewed">
            <h4>✓ You have already reviewed this parlour</h4>
            <?php if (isset($existing_review)): ?>
                <p><strong>Your Rating:</strong> <?php echo $existing_review['rating']; ?>/5 ⭐</p>
                <p><strong>Your Review:</strong> "<?php echo htmlspecialchars($existing_review['review_text']); ?>"</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form action="" method="POST" id="reviewForm">
            <div class="rating-group">
                <label><strong>Rate this parlour:</strong></label>
                <div class="star-rating" id="starRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
                <small id="ratingError" style="color: red; display: none;">Please select a rating</small>
            </div>

            <div class="input-group">
                <label for="review_text"><strong>Write your review:</strong></label>
                <textarea name="review_text" id="review_text" class="review-textarea" 
                         placeholder="Share your experience with this parlour..." required></textarea>
            </div>

            <button type="submit" class="submit-btn">Submit Review</button>
        </form>
    <?php endif; ?>
    
    <a href="customer_dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>

<footer>
    <p>&copy; 2025 Home Glam | All rights reserved.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');
    const reviewForm = document.getElementById('reviewForm');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            document.getElementById('ratingError').style.display = 'none';
        });
        
        star.addEventListener('mouseover', function() {
            const rating = this.getAttribute('data-rating');
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#f39c12';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
    
    document.getElementById('starRating').addEventListener('mouseleave', function() {
        const currentRating = ratingInput.value;
        stars.forEach((s, index) => {
            if (index < currentRating) {
                s.style.color = '#f39c12';
            } else {
                s.style.color = '#ddd';
            }
        });
    });
    
    reviewForm.addEventListener('submit', function(e) {
        if (!ratingInput.value) {
            e.preventDefault();
            document.getElementById('ratingError').style.display = 'block';
        }
    });
});
</script>

</body>
</html>

