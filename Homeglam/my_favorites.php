<?php
session_start();
include('db.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's favorite parlours
$sql = "SELECT p.*, f.created_at as favorited_at,
        (SELECT AVG(rating) FROM parlour_reviews WHERE parlour_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM parlour_reviews WHERE parlour_id = p.id) as review_count
        FROM parlours p 
        JOIN favorites f ON p.id = f.parlour_id 
        WHERE f.customer_id = ? 
        ORDER BY f.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Home Glam</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }

        .back-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #1d4ed8;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-description {
            color: #64748b;
            font-size: 1.125rem;
        }

        /* Favorites Grid */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .favorite-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .favorite-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px 0 rgba(0, 0, 0, 0.15);
        }

        .favorite-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }

        .favorite-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.2s;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .favorite-btn:hover {
            background: #fef2f2;
            transform: scale(1.1);
        }

        .parlour-name {
            font-size: 1.375rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
            padding-right: 3rem;
        }

        .parlour-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #fbbf24;
        }

        .favorite-body {
            padding: 1.5rem;
        }

        .parlour-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .parlour-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .detail-icon {
            width: 1.25rem;
            text-align: center;
            color: #2563eb;
        }

        .favorite-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            flex: 1;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .favorited-date {
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 500;
            background: #ecfdf5;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #d1d5db;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #374151;
        }

        .empty-state p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }

        .empty-state .btn {
            display: inline-flex;
            width: auto;
        }

        /* Stats Bar */
        .stats-bar {
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .loading i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header-content {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .favorites-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stats-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .favorite-actions {
                flex-direction: column;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="header-content">
            <div class="header-brand">
                <div class="brand-logo">HG</div>
                <h1>My Favorites</h1>
            </div>
            <a href="customer_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">
                <i class="fas fa-heart" style="color: #dc2626;"></i>
                My Favorite Parlours
            </h2>
            <p class="page-description">Quick access to your saved parlours for easy booking</p>
        </div>

        <?php
        $favorites = [];
        $total_favorites = 0;
        $avg_rating = 0;
        $total_reviews = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            $favorites[] = $row;
            $total_favorites++;
            if ($row['avg_rating']) {
                $avg_rating += $row['avg_rating'];
            }
            $total_reviews += $row['review_count'];
        }

        if ($total_favorites > 0) {
            $avg_rating = $avg_rating / $total_favorites;
        }
        ?>

        <?php if ($total_favorites > 0): ?>
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $total_favorites; ?></div>
                    <div class="stat-label">Favorite Parlours</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>

            <!-- Favorites Grid -->
            <div class="favorites-grid">
                <?php foreach ($favorites as $parlour): ?>
                    <div class="favorite-card" id="favorite-<?php echo $parlour['id']; ?>">
                        <div class="favorite-header">
                            <button class="favorite-btn" onclick="removeFavorite(<?php echo $parlour['id']; ?>)" title="Remove from favorites">
                                <i class="fas fa-heart"></i>
                            </button>
                            
                            <h3 class="parlour-name"><?php echo htmlspecialchars($parlour['name']); ?></h3>
                            
                            <div class="parlour-meta">
                                <?php if ($parlour['avg_rating']): ?>
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <span><?php echo number_format($parlour['avg_rating'], 1); ?></span>
                                        <span>(<?php echo $parlour['review_count']; ?> reviews)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="favorited-date">
                                    <i class="fas fa-heart"></i>
                                    Added <?php echo date('M j, Y', strtotime($parlour['favorited_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="favorite-body">
                            <div class="parlour-details">
                                <div class="parlour-detail">
                                    <i class="fas fa-map-marker-alt detail-icon"></i>
                                    <span><?php echo htmlspecialchars($parlour['area']); ?></span>
                                </div>
                                
                                <div class="parlour-detail">
                                    <i class="fas fa-home detail-icon"></i>
                                    <span><?php echo htmlspecialchars($parlour['address']); ?></span>
                                </div>
                                
                                <?php if ($parlour['services']): ?>
                                    <div class="parlour-detail">
                                        <i class="fas fa-cut detail-icon"></i>
                                        <span><?php echo htmlspecialchars($parlour['services']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($parlour['phone']): ?>
                                    <div class="parlour-detail">
                                        <i class="fas fa-phone detail-icon"></i>
                                        <span><?php echo htmlspecialchars($parlour['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="favorite-actions">
                                <a href="book_service.php?parlour_id=<?php echo $parlour['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus"></i>
                                    Book Service
                                </a>
                                
                                <a href="parlour_details.php?id=<?php echo $parlour['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-info-circle"></i>
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-heart"></i>
                <h3>No Favorites Yet</h3>
                <p>Start adding parlours to your favorites for quick access and easy booking!</p>
                <a href="customer_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Find Parlours
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Remove favorite function
        async function removeFavorite(parlourId) {
            if (!confirm('Are you sure you want to remove this parlour from your favorites?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('parlour_id', parlourId);
                
                const response = await fetch('favorites.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Remove the card with animation
                    const card = document.getElementById(`favorite-${parlourId}`);
                    card.style.transform = 'translateX(-100%)';
                    card.style.opacity = '0';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if this was the last favorite
                        const remainingCards = document.querySelectorAll('.favorite-card');
                        if (remainingCards.length === 0) {
                            location.reload(); // Reload to show empty state
                        } else {
                            // Update stats if needed
                            updateStats();
                        }
                    }, 300);
                    
                    // Show success message
                    showAlert('Parlour removed from favorites successfully!', 'success');
                } else {
                    showAlert(result.message || 'Failed to remove from favorites', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Failed to remove from favorites. Please try again.', 'error');
            }
        }

        // Show alert message
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Update stats (simplified version)
        function updateStats() {
            const remainingCards = document.querySelectorAll('.favorite-card');
            const totalFavorites = remainingCards.length;
            
            const statValue = document.querySelector('.stat-value');
            if (statValue) {
                statValue.textContent = totalFavorites;
            }
        }

        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.favorite-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>