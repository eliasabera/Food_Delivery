<?php
session_start();

// Redirect to login if not customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

require_once '../php/db.php';

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: order_history.php");
    exit();
}

// Fetch order details
$order = [];
$delivery_person = null;
$restaurant = null;
$order_items = [];

try {
    // Get order information
    $stmt = $conn->prepare("SELECT o.*, 
                           c.username AS customer_name, 
                           c.phone_number AS customer_phone,
                           r.name AS restaurant_name,
                           r.location AS restaurant_location,
                           r.image_url AS restaurant_image,
                           dp.username AS delivery_person_name,
                           dp.phone_number AS delivery_person_phone
                           FROM Orders o
                           JOIN Customer c ON o.customer_id = c.customer_id
                           JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
                           LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
                           WHERE o.order_id = ? AND o.customer_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: order_history.php");
        exit();
    }

    // Get order items
    $stmt = $conn->prepare("SELECT oi.*, fi.name, fi.image_url 
                           FROM OrderItem oi
                           JOIN FoodItem fi ON oi.food_id = fi.food_id
                           WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check for new delivery person assignment (if previously waiting)
    if ($order['status'] === 'Waiting for Delivery') {
        $stmt = $conn->prepare("SELECT delivery_person_id FROM DeliveryPerson 
                               WHERE status = 'available' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $delivery_person_id = $result['delivery_person_id'];
            
            // Update order with delivery person
            $stmt = $conn->prepare("UPDATE Orders 
                                  SET delivery_person_id = ?, status = 'Pending'
                                  WHERE order_id = ?");
            $stmt->execute([$delivery_person_id, $order_id]);
            
            // Mark delivery person as busy
            $stmt = $conn->prepare("UPDATE DeliveryPerson SET status = 'busy' 
                                  WHERE delivery_person_id = ?");
            $stmt->execute([$delivery_person_id]);
            
            // Create notification for delivery person
            $stmt = $conn->prepare("INSERT INTO notifications 
                                  (user_id, user_type, sender_id, sender_type, message, notification_type, related_id) 
                                  VALUES (?, 'delivery', ?, 'system', ?, 'order', ?)");
            $message = "New delivery assignment: Order #$order_id to {$order['customer_location']}";
            $stmt->execute([$delivery_person_id, null, $message, $order_id]);
            
            // Create notification for customer
            $stmt = $conn->prepare("INSERT INTO notifications 
                                  (user_id, user_type, sender_id, sender_type, message, notification_type, related_id) 
                                  VALUES (?, 'customer', ?, 'system', ?, 'order', ?)");
            $message = "Delivery person assigned for order #$order_id";
            $stmt->execute([$_SESSION['user_id'], null, $message, $order_id]);
            
            // Refresh order data
            $stmt = $conn->prepare("SELECT o.*, 
                                   c.username AS customer_name, 
                                   c.phone_number AS customer_phone,
                                   r.name AS restaurant_name,
                                   r.location AS restaurant_location,
                                   r.image_url AS restaurant_image,
                                   dp.username AS delivery_person_name,
                                   dp.phone_number AS delivery_person_phone
                                   FROM Orders o
                                   JOIN Customer c ON o.customer_id = c.customer_id
                                   JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
                                   LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
                                   WHERE o.order_id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $error = "Error fetching order details: " . $e->getMessage();
}

// Status tracking with icons and descriptions
$status_steps = [
    'Pending' => ['icon' => 'fa-hourglass-half', 'description' => 'Order received by restaurant'],
    'Preparing' => ['icon' => 'fa-utensils', 'description' => 'Restaurant is preparing your food'],
    'Out for Delivery' => ['icon' => 'fa-motorcycle', 'description' => 'Delivery person is on the way'],
    'Delivered' => ['icon' => 'fa-check-circle', 'description' => 'Order delivered successfully'],
    'Cancelled' => ['icon' => 'fa-times-circle', 'description' => 'Order was cancelled'],
    'Waiting for Delivery' => ['icon' => 'fa-clock', 'description' => 'Waiting for delivery person to become available']
];

// Determine current status index for progress bar
$status_keys = array_keys($status_steps);
$current_status_index = array_search($order['status'], $status_keys);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status - #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B00;
            --primary-dark: #E05D00;
            --light-bg: #FFF8F0;
            --text-dark: #333333;
            --text-light: #777777;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: var(--light-bg);
        }
        
        .order-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 20px;
        }
        
        .status-timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }
        
        .status-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .status-step {
            position: relative;
            padding-bottom: 20px;
        }
        
        .status-step.completed .status-icon {
            background-color: var(--primary);
            color: white;
        }
        
        .status-step.current .status-icon {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .status-step.pending .status-icon {
            background-color: #e9ecef;
            color: var(--text-light);
        }
        
        .status-icon {
            position: absolute;
            left: -30px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            background-color: white;
        }
        
        .order-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .contact-card {
            border-radius: 10px;
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .progress {
            height: 6px;
            margin: 20px 0;
        }
        
        .progress-bar {
            background-color: var(--primary);
        }
        
        .map-container {
            height: 200px;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .map-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            color: var(--text-light);
        }
        
        .btn-orange {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-orange:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Food Delivery</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/restaurants.php">Restaurants</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../php/order_history.php">My Orders</a>
                    </li>
                </ul>
                <a href="../php/logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="order-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order #<?= $order_id ?></h2>
                <span class="badge bg-<?= $order['status'] === 'Cancelled' ? 'danger' : ($order['status'] === 'Delivered' ? 'success' : 'warning') ?>">
                    <?= $order['status'] ?>
                </span>
            </div>
            
            <!-- Status Timeline -->
            <div class="status-timeline">
                <?php foreach ($status_steps as $status => $step): ?>
                    <?php 
                    $status_index = array_search($status, $status_keys);
                    $is_completed = $status_index < $current_status_index;
                    $is_current = $status_index === $current_status_index;
                    ?>
                    <div class="status-step <?= $is_completed ? 'completed' : ($is_current ? 'current' : 'pending') ?>">
                        <div class="status-icon">
                            <i class="fas <?= $step['icon'] ?> fa-xs"></i>
                        </div>
                        <div class="status-content ps-4">
                            <h5><?= $status ?></h5>
                            <p class="text-muted"><?= $step['description'] ?></p>
                            <?php if ($is_current && $status === 'Out for Delivery' && $order['delivery_person_name']): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-motorcycle"></i> <?= htmlspecialchars($order['delivery_person_name']) ?> is delivering your order
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Progress Bar (Alternative view) -->
            <div class="progress">
                <div class="progress-bar" role="progressbar" 
                     style="width: <?= ($current_status_index + 1) / count($status_steps) * 100 ?>%" 
                     aria-valuenow="<?= ($current_status_index + 1) ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="<?= count($status_steps) ?>">
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <!-- Order Items -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($order_items as $item): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="../images/<?= htmlspecialchars(basename($item['image_url'])) ?>" 
                                         class="order-item-img me-3" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         onerror="this.onerror=null;this.src='../images/default-food.jpg'">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                        <small class="text-muted"><?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?> Birr</small>
                                    </div>
                                    <div class="fw-bold">
                                        <?= number_format($item['quantity'] * $item['price'], 2) ?> Birr
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?= number_format($order['total_price'] - 30, 2) ?> Birr</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Delivery Fee:</span>
                                <span>30.00 Birr</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold mt-2">
                                <span>Total:</span>
                                <span><?= number_format($order['total_price'], 2) ?> Birr</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Information -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Delivery Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Delivery Address</h6>
                                <p><?= htmlspecialchars($order['customer_location']) ?></p>
                            </div>
                            
                            <div class="map-container mb-3">
                                <div class="map-placeholder">
                                    <i class="fas fa-map-marker-alt fa-2x me-2"></i>
                                    <span>Delivery location map</span>
                                </div>
                            </div>
                            
                            <?php if ($order['status'] === 'Out for Delivery'): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Your order is on the way! Track your delivery in real-time.
                                </div>
                            <?php elseif ($order['status'] === 'Waiting for Delivery'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock me-2"></i>
                                    We're waiting for a delivery person to become available. Your order will be processed soon.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Restaurant Contact -->
                    <div class="contact-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="../images/<?= htmlspecialchars(basename($order['restaurant_image'])) ?>" 
                                 class="rounded-circle me-3" width="50" height="50" 
                                 alt="<?= htmlspecialchars($order['restaurant_name']) ?>"
                                 onerror="this.onerror=null;this.src='../images/default-restaurant.jpg'">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($order['restaurant_name']) ?></h5>
                                <small class="text-muted">Restaurant</small>
                            </div>
                        </div>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= htmlspecialchars($order['restaurant_location']) ?>
                        </p>
                        <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>" class="btn btn-outline-primary w-100 mt-2">
                            <i class="fas fa-phone me-2"></i> Call Restaurant
                        </a>
                    </div>
                    
                    <!-- Delivery Person Contact (if assigned) -->
                    <?php if ($order['delivery_person_name']): ?>
                        <div class="contact-card">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-motorcycle fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= htmlspecialchars($order['delivery_person_name']) ?></h5>
                                    <small class="text-muted">Delivery Person</small>
                                </div>
                            </div>
                            <a href="tel:<?= htmlspecialchars($order['delivery_person_phone']) ?>" class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-phone me-2"></i> Call Delivery
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Order Actions -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <?php if ($order['status'] === 'Pending' || $order['status'] === 'Preparing'): ?>
                                <button class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="fas fa-times me-2"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                            
                            <a href="../pages/restaurants.php" class="btn btn-orange w-100">
                                <i class="fas fa-utensils me-2"></i> Order Again
                            </a>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Order ID:</strong> #<?= $order_id ?></p>
                            <p class="mb-1"><strong>Order Date:</strong> <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> Cash on Delivery</p>
                            <p class="mb-0"><strong>Status:</strong> <?= $order['status'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order?</p>
                    <p class="text-danger">Note: You may be charged a cancellation fee if the restaurant has already started preparing your food.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <form action="cancel_order.php" method="POST">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-refresh the page every 30 seconds if order is not completed
    $(document).ready(function() {
        const completedStatuses = ['Delivered', 'Cancelled'];
        const currentStatus = "<?= $order['status'] ?>";
        
        if (!completedStatuses.includes(currentStatus)) {
            setTimeout(function() {
                location.reload();
            }, 30000); // 30 seconds
        }
        
        // You could add real-time tracking here if you integrate with a maps API
    });
    </script>
</body>
</html>