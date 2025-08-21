<?php
session_start();
include('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $parlour_id = intval($_POST['parlour_id'] ?? 0);
    
    if ($parlour_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parlour ID']);
        exit;
    }
    
    switch ($action) {
        case 'add':
            // Add to favorites
            $check_sql = "SELECT id FROM favorites WHERE customer_id = ? AND parlour_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $parlour_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                echo json_encode(['success' => false, 'message' => 'Already in favorites']);
                exit;
            }
            
            $insert_sql = "INSERT INTO favorites (customer_id, parlour_id) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $parlour_id);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Added to favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
            }
            break;
            
        case 'remove':
            // Remove from favorites
            $delete_sql = "DELETE FROM favorites WHERE customer_id = ? AND parlour_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $parlour_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
            }
            break;
            
        case 'check':
            // Check if parlour is in favorites
            $check_sql = "SELECT id FROM favorites WHERE customer_id = ? AND parlour_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $parlour_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            $is_favorite = mysqli_num_rows($check_result) > 0;
            echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Handle GET requests (for fetching favorites)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_favorites') {
        // Get user's favorite parlours
        $sql = "SELECT p.*, f.created_at as favorited_at 
                FROM parlours p 
                JOIN favorites f ON p.id = f.parlour_id 
                WHERE f.customer_id = ? 
                ORDER BY f.created_at DESC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $favorites = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $favorites[] = $row;
        }
        
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        exit;
    }
    
    if ($action === 'get_favorite_ids') {
        // Get just the IDs of favorite parlours (for checking status)
        $sql = "SELECT parlour_id FROM favorites WHERE customer_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $favorite_ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $favorite_ids[] = intval($row['parlour_id']);
        }
        
        echo json_encode(['success' => true, 'favorite_ids' => $favorite_ids]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>