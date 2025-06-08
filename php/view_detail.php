<?php
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch order header information
    $stmt = $conn->prepare("
        SELECT o.*, 
               r.name AS restaurant_name, 
               r.image_url AS restaurant_image,
               dp.username AS delivery_person_name,
               dp.phone_number AS delivery_person_phone,
               dp.avg_rating AS delivery_person_rating
        FROM Orders o
        LEFT JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
        LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
        WHERE o.order_id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Order not found or you don't have permission to view it";
        header("Location: order_history.php");
        exit();
    }
    
    // Fetch order items
    $stmt = $conn->prepare("
        SELECT oi.*, fi.name AS food_name, fi.price AS unit_price, fi.image_url AS food_image
        FROM OrderItem oi
        JOIN FoodItem fi ON oi.food_id = fi.food_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .order-detail-container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .order-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
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
        .restaurant-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="order_history.php" class="btn btn-outline-secondary back-btn">
            <i class="fas fa-arrow-left"></i> Back to Order History
        </a>
        
        <div class="order-detail-container">
            <div class="order-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Order #<?= $order_id ?></h2>
                    <span class="order-status status-<?= strtolower($order['status']) ?>">
                        <?= $order['status'] ?>
                    </span>
                </div>
                <p class="text-muted">
                    <i class="far fa-calendar-alt"></i> 
                    <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?>
                </p>
            </div>
            
            <div class="restaurant-info d-flex align-items-center mb-4">
                <img src="<?= !empty($order['restaurant_image']) ? '../images/restaurants/'.$order['restaurant_image'] : '../images/default-restaurant.jpg' ?>" 
                     alt="<?= htmlspecialchars($order['restaurant_name']) ?>">
                <div>
                    <h4><?= htmlspecialchars($order['restaurant_name']) ?></h4>
                    <p class="text-muted mb-0">Delivery to: <?= htmlspecialchars($order['customer_location']) ?></p>
                </div>
            </div>
            
            <div class="delivery-info mb-4 p-3 bg-light rounded">
                <h5><i class="fas fa-truck"></i> Delivery Information</h5>
                <?php if (!empty($order['delivery_person_name'])): ?>
                    <p class="mb-1"><strong>Delivery Person:</strong> <?= htmlspecialchars($order['delivery_person_name']) ?></p>
                    <p class="mb-1"><strong>Contact:</strong> <?= htmlspecialchars($order['delivery_person_phone']) ?></p>
                    <p class="mb-0"><strong>Rating:</strong> 
                        <?= number_format($order['delivery_person_rating'], 1) ?> 
                        <i class="fas fa-star text-warning"></i>
                    </p>
                <?php else: ?>
                    <p class="mb-0">Delivery person not assigned yet</p>
                <?php endif; ?>
            </div>
            
            <h4 class="mb-3">Order Items</h4>
            <div class="order-items mb-4">
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item row">
                        <div class="col-md-2">
                            <img src="<?= !empty($item['food_image']) ? '../images/'.$item['food_image'] : '../images/default-food.jpg' ?>" 
                                 alt="<?= htmlspecialchars($item['food_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <h5><?= htmlspecialchars($item['food_name']) ?></h5>
                            <p class="text-muted mb-0"><?= number_format($item['unit_price'], 2) ?> Birr each</p>
                        </div>
                        <div class="col-md-2 text-center">
                            <p>Qty: <?= $item['quantity'] ?></p>
                        </div>
                        <div class="col-md-2 text-end">
                            <p><strong><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> Birr</strong></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-summary">
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span><?= number_format($order['total_price'] - 30, 2) ?> Birr</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee:</span>
                                <span>30.00 Birr</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span><?= number_format($order['total_price'], 2) ?> Birr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($order['status'] === 'Processing'): ?>
                <div class="mt-4 text-end">
                    <form method="POST" action="order_history.php" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <button type="submit" name="cancel_order" class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to cancel this order?')">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>