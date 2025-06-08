<?php
session_start();

// Check if restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../../php/login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$restaurant_id = (int)$_SESSION['user_id'];

try {
    // Mark all notifications as read when viewing all
    $conn->beginTransaction();
    $stmt = $conn->prepare("UPDATE notifications 
                          SET is_read = TRUE 
                          WHERE user_id = :user_id 
                          AND user_type = 'restaurant'");
    $stmt->bindParam(':user_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Get all notifications
    $stmt = $conn->prepare("SELECT * FROM notifications 
                          WHERE user_id = :user_id 
                          AND user_type = 'restaurant'
                          ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $conn->commit();

} catch (PDOException $e) {
    if (isset($conn)) $conn->rollBack();
    die("Database error: " . $e->getMessage());
}

// Display status message if exists
if (isset($_SESSION['notification_message'])) {
    $message = $_SESSION['notification_message'];
    unset($_SESSION['notification_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #fdf1db;
            background-image: url("https://www.transparenttextures.com/patterns/wood-pattern.png");
            font-family: "Poppins", sans-serif;
        }
        
        .bg-orange {
            background-color: #FF6B00;
        }
        
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .notification-item {
            border-left: 4px solid #FF6B00;
            padding-left: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #FF6B00;
            margin-bottom: 15px;
        }
        
        .btn-outline-light:hover {
            color: #FF6B00;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-orange">
        <div class="container">
            <a class="navbar-brand" href="restaurant_admin.php">
                <i class="fas fa-utensils me-2"></i>Restaurant Dashboard
            </a>
            <div class="d-flex align-items-center">
                <a href="restaurant_admin.php" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <a href="../../php/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="m-0"><i class="fas fa-bell me-2"></i>Your Notifications</h2>
                    <span class="badge bg-orange">
                        <?= count($notifications) ?> total
                    </span>
                </div>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show">
                <?= $message['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>No notifications yet</h4>
                        <p class="text-muted">You'll see order updates and important messages here</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item notification-item">
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    <small class="notification-time">
                                        <?= date('M j, Y g:i a', strtotime($notification['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>