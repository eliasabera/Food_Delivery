<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fixed table name from 'Notification' to 'notifications'
    $conn->exec("UPDATE notifications SET is_read = TRUE WHERE user_id = {$_SESSION['user_id']} AND user_type = 'delivery'");
    
    $stmt = $conn->prepare("SELECT * FROM notifications
                          WHERE user_id = ? AND user_type = 'delivery'
                          ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #fdf1db;
            background-image: url("https://www.transparenttextures.com/patterns/wood-pattern.png");
            font-family: "Poppins", sans-serif;
        }
        
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #333;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: #ff6600;
            border-color: #ff6600;
        }
        
        .btn-primary:hover {
            background-color: #e65c00;
            border-color: #e65c00;
        }
        
        .list-group-item {
            border-left: 4px solid #ff6600;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .text-muted {
            color: #666 !important;
        }
        
        .fa-bell-slash {
            color: #ff6600;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-bell me-2"></i>Your Notifications</h1>
            <a href="delivery_panel.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="card shadow">
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                        <h4>No notifications found</h4>
                        <p class="text-muted">You'll see order updates here when available</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    <small class="text-muted">
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