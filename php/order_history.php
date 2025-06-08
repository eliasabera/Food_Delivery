<?php
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verify required tables exist
    $tables = ['Orders', 'Restaurant', 'DeliveryPerson'];
    foreach ($tables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) {
            throw new PDOException("Required table '$table' doesn't exist");
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch customer's orders with improved error handling
try {
$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.total_price, o.status, 
           IFNULL(r.name, 'Restaurant Not Available') AS restaurant_name, 
           IFNULL(r.image_url, 'default-restaurant.jpg') AS restaurant_image,
           IFNULL(dp.username, 'Not Assigned') AS delivery_person_name,
           IFNULL(dp.phone_number, 'N/A') AS delivery_person_phone,
           IFNULL(dp.avg_rating, 0.00) AS delivery_person_rating,
           IFNULL(dp.status, 'N/A') AS delivery_person_status
    FROM Orders o
    LEFT JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
    LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching orders: " . $e->getMessage());
}

// Handle cancel order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    
    try {
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Cancelled' 
                               WHERE order_id = ? AND customer_id = ? AND status = 'Processing'");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order #$order_id has been cancelled successfully";
            header("Location: order_history.php");
            exit();
        } else {
            $_SESSION['error'] = "Unable to cancel order. It may have already been processed.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error cancelling order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/order_history.css">
    <style>
        /* Critical CSS that needs to work immediately */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .order-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px 20px;
            background-color: #fff;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="order-history-header">
            <h1><i class="fas fa-history"></i> My Order History</h1>
            <a href="../index.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </header>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="order-filters mb-4">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-filter active" data-filter="all">All Orders</button>
                <button type="button" class="btn btn-filter" data-filter="delivered">Delivered</button>
                <button type="button" class="btn btn-filter" data-filter="processing">Processing</button>
                <button type="button" class="btn btn-filter" data-filter="cancelled">Cancelled</button>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-box-open"></i>
                <h3>No orders yet</h3>
                <p>Your order history will appear here once you place an order</p>
                <a href="../restaurants.php" class="btn btn-order-now">Order Now</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card" data-status="<?= strtolower($order['status']) ?>">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-id">Order #<?= $order['order_id'] ?></span>
                                <span class="order-date">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?= date('M j, Y', strtotime($order['order_date'])) ?>
                                </span>
                            </div>
                            <span class="order-status status-<?= strtolower($order['status']) ?>">
                                <?= $order['status'] ?>
                            </span>
                        </div>

                        <div class="order-body">
                            <div class="restaurant-info">
                                <img src="<?= !empty($order['restaurant_image']) ? '../images/restaurants/'.$order['restaurant_image'] : '../images/default-restaurant.jpg' ?>" 
                                     alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                     class="restaurant-image">
                                <h4><?= htmlspecialchars($order['restaurant_name']) ?></h4>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">Total Amount:</span>
                                    <span class="detail-value"><?= number_format($order['total_price'], 2) ?> Birr</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Delivery Person:</span>
                                    <span class="detail-value"><?= htmlspecialchars($order['delivery_person_name']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <a href="order_status.php?order_id=<?= $order['order_id'] ?>" class="btn btn-view-details">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($order['status'] === 'Delivered'): ?>
                                <a href="rate_order.php?order_id=<?= $order['order_id'] ?>" class="btn btn-rate">
                                    <i class="fas fa-star"></i> Rate Order
                                </a>
                            <?php elseif ($order['status'] === 'Processing'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <button type="submit" name="cancel_order" class="btn btn-cancel" 
                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="fas fa-times"></i> Cancel Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/order_history.js"></script>
</body>
</html>