<?php
session_start();
require_once 'db.php';

// Enhanced Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
    header("Location: ../php/login.php");
    exit();
}

// Secure Order ID Retrieval and Validation
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, [
    'options' => [
        'min_range' => 1
    ]
]);

if ($order_id === false || $order_id === null) {
    die("Invalid order ID - Please provide a valid numeric order ID");
}

// Function to get available status actions
function getStatusActions($order_id, $current_status) {
    $actions = [];
    
    switch ($current_status) {
        case 'Ready':
            $actions[] = [
                'url' => "update-order-status.php?order_id=$order_id&status=Out for Delivery",
                'class' => 'btn-primary',
                'icon' => 'fa-truck',
                'text' => 'Start Delivery'
            ];
            break;
            
        case 'Out for Delivery':
            $actions[] = [
                'url' => "update-order-status.php?order_id=$order_id&status=Delivered",
                'class' => 'btn-success',
                'icon' => 'fa-check-circle',
                'text' => 'Mark as Delivered'
            ];
            break;
            
        case 'Delivered':
            $actions[] = [
                'url' => '#',
                'class' => 'btn-secondary disabled',
                'icon' => 'fa-check',
                'text' => 'Delivery Completed'
            ];
            break;
            
        default:
            $actions[] = [
                'url' => '#',
                'class' => 'btn-secondary disabled',
                'icon' => 'fa-info-circle',
                'text' => 'No actions available'
            ];
    }
    
    return $actions;
}

try {
    // Verify Order Assignment
    $stmt = $conn->prepare("
        SELECT o.order_id, o.delivery_person_id, o.status 
        FROM Orders o 
        WHERE o.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order_assignment) {
        die("Order #$order_id not found in the system");
    }

    if ($order_assignment['delivery_person_id'] != $_SESSION['user_id']) {
        die("Order #$order_id is not assigned to your account");
    }

    // Get Order Details
    $stmt = $conn->prepare("
        SELECT 
            o.order_id,
            o.status AS order_status,
            o.customer_location,
            o.order_date,
            c.username AS customer_name,
            c.phone_number AS customer_phone,
            r.name AS restaurant_name,
            r.location AS restaurant_address,
            COUNT(oi.order_item_id) AS item_count
        FROM Orders o
        JOIN Customer c ON o.customer_id = c.customer_id
        JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
        LEFT JOIN OrderItem oi ON o.order_id = oi.order_id
        WHERE o.order_id = :order_id
        GROUP BY o.order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get Order Items with proper image handling
    $stmt = $conn->prepare("
        SELECT 
            oi.order_item_id,
            fi.name, 
            fi.description, 
            oi.quantity,
            fi.image_url
        FROM OrderItem oi
        INNER JOIN FoodItem fi ON oi.food_id = fi.food_id
        WHERE oi.order_id = :order_id
        ORDER BY fi.name
    ");
    $stmt->execute([':order_id' => $order_id]);
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
    <title>Order #<?= htmlspecialchars($order_id) ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-header {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .item-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .bg-delivering { background-color: #0dcaf0; }
        .bg-delivered { background-color: #198754; }
        .bg-pending { background-color: #ffc107; color: #000; }
        .order-status-out-for-delivery {
            background-color: #fff3cd;
            color: #856404;
        }
        .order-status-ready {
            background-color: #cce5ff;
            color: #004085;
        }
        .order-status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="order-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Order #<?= htmlspecialchars($order_id) ?></h2>
                <span class="status-badge bg-<?= strtolower(str_replace(' ', '-', $order['order_status'])) ?>">
                    <?= htmlspecialchars($order['order_status']) ?>
                </span>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                    <p><strong>Delivery Location:</strong> <?= htmlspecialchars($order['customer_location']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Restaurant:</strong> <?= htmlspecialchars($order['restaurant_name']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($order['restaurant_address']) ?></p>
                    <p><strong>Order Date:</strong> <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></p>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Order Summary</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span><strong>Total Items:</strong> <?= htmlspecialchars($order['item_count']) ?></span>
                </div>
            </div>
        </div>

        <h4 class="mb-3">Order Items</h4>
        
        <?php if (empty($order_items)): ?>
            <div class="alert alert-info">No items found in this order.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($order_items as $item): ?>
                    <div class="col-md-6">
                        <div class="card item-card mb-3">
                            <div class="card-body">
                                <div class="d-flex">
                                    <img src="../images/<?= htmlspecialchars(basename($item['image_url'])) ?>" 
                                         class="img-fluid rounded-start cart-item-img me-3" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         onerror="this.onerror=null;this.src='../images/default-food.jpg'">
                                    
                                    <div>
                                        <h5><?= htmlspecialchars($item['name']) ?></h5>
                                        <p class="text-muted"><?= htmlspecialchars($item['description']) ?></p>
                                        <div class="d-flex justify-content-between">
                                            <span>Qty: <?= htmlspecialchars($item['quantity']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Status Update Section -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Update Order Status</h5>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <?php 
                    $actions = getStatusActions($order_id, $order['order_status']);
                    foreach ($actions as $action): 
                    ?>
                        <a href="<?= htmlspecialchars($action['url']) ?>" 
                           class="btn <?= htmlspecialchars($action['class']) ?> me-2"
                           onclick="<?= strpos($action['url'], '#') === 0 ? 'return false;' : 'return confirm(\'Are you sure you want to update this order status?\')' ?>">
                            <i class="fas <?= htmlspecialchars($action['icon']) ?> me-1"></i>
                            <?= htmlspecialchars($action['text']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="../pages/delivery_panel.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>