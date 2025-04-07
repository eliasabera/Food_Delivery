<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

// Check if order data exists in session (from payment.php)
if (!isset($_SESSION['order_confirmation'])) {
    header("Location: cart.php");
    exit();
}

// Get order data from session
$order = $_SESSION['order_confirmation'];
unset($_SESSION['order_confirmation']); // Clear after use

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch complete order details from database
$order_id = $order['order_id'];
$order_sql = "SELECT o.order_id, o.total_price, o.status, o.customer_location, o.order_date, 
                     o.transaction_ref, o.phone_number,
                     r.name AS restaurant_name, r.restaurant_id,
                     dp.username AS delivery_person_name, dp.delivery_person_id
              FROM Orders o
              JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
              JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
              WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows > 0) {
    $order_details = $order_result->fetch_assoc();
    // Merge session data with database data
    $order = array_merge($order, $order_details);
} else {
    die("Order not found. Please contact customer support.");
}

// Fetch order items
$order_items_sql = "SELECT fi.name, oi.quantity, oi.price
                    FROM OrderItem oi
                    JOIN FoodItem fi ON oi.food_id = fi.food_id
                    WHERE oi.order_id = ?";
$order_items_stmt = $conn->prepare($order_items_sql);
$order_items_stmt->bind_param("i", $order_id);
$order_items_stmt->execute();
$order_items_result = $order_items_stmt->get_result();
$order_items = $order_items_result->fetch_all(MYSQLI_ASSOC);

// Check if order has already been rated
$rating_check_sql = "SELECT rating_id FROM Ratings WHERE order_id = ?";
$rating_check_stmt = $conn->prepare($rating_check_sql);
$rating_check_stmt->bind_param("i", $order_id);
$rating_check_stmt->execute();
$rating_check_result = $rating_check_stmt->get_result();
$already_rated = $rating_check_result->num_rows > 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --secondary-color: #858796;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .confirmation-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .order-header {
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .order-header h1 {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .order-timeline {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0;
            position: relative;
        }
        
        .order-timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e3e6f0;
            z-index: 1;
        }
        
        .timeline-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 0 1rem;
        }
        
        .timeline-step::before {
            content: '';
            display: block;
            width: 30px;
            height: 30px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #e3e6f0;
            border: 3px solid #e3e6f0;
        }
        
        .timeline-step h5 {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .timeline-step p {
            font-size: 0.8rem;
            color: #b7b9cc;
        }
        
        .timeline-step.completed::before {
            background: var(--success-color);
            border-color: var(--success-color);
        }
        
        .timeline-step.active::before {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 5px rgba(78, 115, 223, 0.2);
        }
        
        .timeline-step.completed h5,
        .timeline-step.active h5 {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .timeline-step.completed p,
        .timeline-step.active p {
            color: var(--secondary-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .status-pending {
            background-color: rgba(246, 194, 62, 0.2);
            color: var(--warning-color);
        }
        
        .status-preparing {
            background-color: rgba(78, 115, 223, 0.2);
            color: var(--primary-color);
        }
        
        .status-delivering {
            background-color: rgba(54, 185, 204, 0.2);
            color: #36b9cc;
        }
        
        .status-delivered {
            background-color: rgba(28, 200, 138, 0.2);
            color: var(--success-color);
        }
        
        .order-details-card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 2rem;
        }
        
        .order-details-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .order-items-table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
        }
        
        .order-items-table th {
            background-color: #f8f9fc;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
            color: var(--secondary-color);
            font-weight: 600;
        }
        
        .order-items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e3e6f0;
            vertical-align: top;
        }
        
        .order-items-table tr:last-child td {
            border-bottom: none;
        }
        
        .total-row {
            font-weight: 600;
            background-color: #f8f9fc;
        }
        
        .rating-section {
            padding: 2rem;
            margin: 2rem 0;
            background-color: #f8f9fc;
            border-radius: 0.35rem;
        }
        
        .btn-rate {
            padding: 0.5rem 2rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .action-buttons .btn {
            margin: 0.5rem;
            min-width: 150px;
        }
        
        @media (max-width: 768px) {
            .order-timeline {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-timeline::before {
                top: 0;
                left: 15px;
                bottom: 0;
                width: 3px;
                height: auto;
            }
            
            .timeline-step {
                display: flex;
                align-items: center;
                text-align: left;
                margin-bottom: 1.5rem;
                padding: 0;
            }
            
            .timeline-step::before {
                margin: 0 1rem 0 0;
                flex-shrink: 0;
            }
            
            .timeline-content {
                flex: 1;
            }
        }
    </style>
</head>
<body>
<div class="confirmation-container">
    <div class="order-header text-center">
        <h1><i class="fas fa-check-circle text-success me-2"></i>Order Confirmed!</h1>
        <p class="lead">Thank you for your order #<?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-muted">A confirmation has been sent to your phone (<?= htmlspecialchars($order['phone_number'], ENT_QUOTES, 'UTF-8') ?>)</p>
    </div>

    <!-- Order Status Timeline -->
    <div class="order-timeline">
        <div class="timeline-step <?= $order['status'] === 'Pending' ? 'active' : 'completed' ?>">
            <div class="timeline-content">
                <h5>Order Received</h5>
                <p>We've received your order</p>
            </div>
        </div>
        <div class="timeline-step <?= $order['status'] === 'Preparing' ? 'active' : ($order['status'] === 'Delivering' || $order['status'] === 'Delivered' ? 'completed' : '') ?>">
            <div class="timeline-content">
                <h5>Preparing Your Food</h5>
                <p>The restaurant is preparing your meal</p>
            </div>
        </div>
        <div class="timeline-step <?= $order['status'] === 'Delivering' ? 'active' : ($order['status'] === 'Delivered' ? 'completed' : '') ?>">
            <div class="timeline-content">
                <h5>On Its Way</h5>
                <p>Your food is being delivered</p>
            </div>
        </div>
        <div class="timeline-step <?= $order['status'] === 'Delivered' ? 'completed' : '' ?>">
            <div class="timeline-content">
                <h5>Delivered</h5>
                <p>Enjoy your meal!</p>
            </div>
        </div>
    </div>

    <!-- Current Status Badge -->
    <div class="text-center mb-4">
        <span class="status-badge status-<?= strtolower($order['status']) ?>">
            <i class="fas fa-<?= $order['status'] === 'Delivered' ? 'check' : 'sync-alt' ?> me-2"></i>
            Current Status: <?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>

    <!-- Order Details Card -->
    <div class="card order-details-card mb-4">
        <div class="card-header">
            <i class="fas fa-receipt me-2"></i>Order Details
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong><i class="fas fa-hashtag me-2"></i>Order ID:</strong> <?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><i class="fas fa-store me-2"></i>Restaurant:</strong> <?= htmlspecialchars($order['restaurant_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><i class="fas fa-motorcycle me-2"></i>Delivery Person:</strong> <?= htmlspecialchars($order['delivery_person_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><i class="fas fa-credit-card me-2"></i>Transaction Ref:</strong> <?= htmlspecialchars($order['transaction_ref'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><i class="fas fa-calendar-alt me-2"></i>Order Date:</strong> <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?></p>
                    <p><strong><i class="fas fa-map-marker-alt me-2"></i>Delivery Location:</strong> <?= htmlspecialchars($order['customer_location'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><i class="fas fa-phone me-2"></i>Contact Number:</strong> <?= htmlspecialchars($order['phone_number'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong><i class="fas fa-clock me-2"></i>Estimated Delivery:</strong> 30-45 minutes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="table-responsive mb-4">
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format($item['price'], 2) ?> Birr</td>
                        <td><?= number_format($item['price'] * $item['quantity'], 2) ?> Birr</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                    <td><?= number_format($order['total_amount'], 2) ?> Birr</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>Delivery Fee:</strong></td>
                    <td>25.00 Birr</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                    <td><?= number_format($order['total_amount'] + 25, 2) ?> Birr</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Rating Section -->
    <?php if ($order['status'] === 'Delivered' && !$already_rated): ?>
    <div class="rating-section">
        <div class="text-center">
            <h4><i class="fas fa-star me-2"></i>How was your experience?</h4>
            <p class="text-muted">Please rate your order to help us improve our service</p>
            <a href="rating_feedback.php?order_id=<?= $order['order_id'] ?>" class="btn btn-primary btn-rate">
                <i class="fas fa-star me-2"></i>Rate Your Order
            </a>
        </div>
    </div>
    <?php elseif ($already_rated): ?>
    <div class="alert alert-success text-center">
        <i class="fas fa-check-circle me-2"></i> Thank you for rating this order!
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons text-center mt-4">
        <a href="restaurants.php?id=<?= $order['restaurant_id'] ?>" class="btn btn-outline-primary">
            <i class="fas fa-utensils me-2"></i>Order Again
        </a>
        <a href="order_history.php" class="btn btn-outline-secondary">
            <i class="fas fa-history me-2"></i>View Order History
        </a>
        <a href="track_order.php?order_id=<?= $order['order_id'] ?>" class="btn btn-outline-info">
            <i class="fas fa-map-marked-alt me-2"></i>Track Order
        </a>
        <a href="../php/logout.php" class="btn btn-outline-danger">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-refresh the page every 60 seconds to check for status updates
setTimeout(function(){
    window.location.reload();
}, 60000);
</script>
</body>
</html>