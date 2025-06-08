<?php
session_start();

// Check if the user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: php/login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch unread notifications
$customer_id = $_SESSION['user_id'];
$notifications_sql = "SELECT * FROM notifications 
                     WHERE user_id = ? AND user_type = 'customer' AND is_read = FALSE
                     ORDER BY created_at DESC LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $customer_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$unread_notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* Existing styles... */
        
        /* Notification Styles */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
            width: 300px;
        }
        .notification-item {
            border-left: 3px solid #0d6efd;
            padding-left: 10px;
            margin-bottom: 5px;
            white-space: normal;
        }
        .notification-item.unread {
            background-color: #f8f9fa;
            border-left: 3px solid #ffc107;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .notification-container {
            position: relative;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="logo bg-orange text-white p-2 rounded-circle">MH</div>
            
            <!-- Notification Bell -->
            <div class="d-flex align-items-center gap-3">
                <div class="notification-container">
                    <div class="dropdown">
                        <a class="btn btn-light position-relative" href="#" role="button" 
                           id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if (count($unread_notifications) > 0): ?>
                                <span class="notification-badge"><?= count($unread_notifications) ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" 
                            aria-labelledby="notificationDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (count($unread_notifications) > 0): ?>
                                <?php foreach ($unread_notifications as $notification): ?>
                                    <li>
                                        <a class="dropdown-item notification-item unread" 
                                           href="php/mark_notification_read.php?id=<?= $notification['id'] ?>&redirect=index.php">
                                            <div><?= htmlspecialchars($notification['message']) ?></div>
                                            <small class="notification-time">
                                                <?= date('M j, g:i a', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a class="dropdown-item text-muted" href="#">No new notifications</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="php/customer_notifications.php">View All</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Navbar Toggler for Small Screens -->
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <!-- Navbar Links -->
            <nav class="navbar-collapse d-lg-flex justify-content-end" id="navbarNav">
                <ul class="navbar-nav d-flex gap-3">
                    <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="pages/restaurants.php" class="nav-link">Restaurants</a></li>
                    <li class="nav-item"><a href="php/order_history.php" class="nav-link">History</a></li>
                    <li class="nav-item"><a href="php/about.php" class="nav-link">About Us</a></li>
                    <li class="nav-item"><a href="php/contact.php" class="nav-link">Contact Us</a></li>
                    <li class="nav-item">
                        <a href="php/logout.php" class="btn btn-orange">Logout</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <section class="hero bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">ENJOY YOUR FOOD <br> AT YOUR PLACE</h1>
                   <a href="pages/restaurants.php"> <button class="btn btn-orange btn-lg mt-3">Order Now</button>     </a> 
                </div>
                <div class="col-md-6">
                    <img src="./images/food.jpg" alt="Food Illustration" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>
    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>