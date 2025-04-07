<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
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

// Get order ID
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get order details
$order_sql = "SELECT o.*, c.username AS customer_name, c.phone_number AS customer_phone,
              r.name AS restaurant_name, dp.username AS delivery_person_name,
              dp.phone_number AS delivery_phone
              FROM Orders o
              JOIN Customer c ON o.customer_id = c.customer_id
              JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
              LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
              WHERE o.order_id = ? AND o.restaurant_id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    die("Order not found or you don't have permission to view this order.");
}

$order = $order_result->fetch_assoc();

// Get order items
$items_sql = "SELECT fi.name, oi.quantity, oi.price
              FROM OrderItem oi
              JOIN FoodItem fi ON oi.food_id = fi.food_id
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);

// Define getStatusActions function
function getStatusActions($order_id, $current_status) {
    $actions = [];
    
    switch ($current_status) {
        case 'Pending':
            $actions[] = [
                'url' => "R_update_order_status.php?id=$order_id&status=Preparing",
                'class' => 'btn-info',
                'icon' => 'fa-play',
                'text' => 'Start Preparing'
            ];
            $actions[] = [
                'url' => "R_update_order_status.php?id=$order_id&status=Cancelled",
                'class' => 'btn-danger',
                'icon' => 'fa-times',
                'text' => 'Cancel Order'
            ];
            break;
            
        case 'Preparing':
            $actions[] = [
                'url' => "R_update_order_status.php?id=$order_id&status=Ready",
                'class' => 'btn-success',
                'icon' => 'fa-check',
                'text' => 'Mark as Ready'
            ];
            break;
            
        case 'Ready':
            $actions[] = [
                'url' => "#",
                'class' => 'btn-secondary',
                'icon' => 'fa-clock',
                'text' => 'Waiting for Delivery'
            ];
            break;
            
        case 'Out for Delivery':
            $actions[] = [
                'url' => "#",
                'class' => 'btn-secondary',
                'icon' => 'fa-truck',
                'text' => 'In Transit'
            ];
            break;
    }
    
    return $actions;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/restaurant.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Order #<?= $order_id ?> Details</h1>
            <a href="../../restaurant_admin.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                <?= $order['status'] ?>
                            </span>
                        </p>
                        <p><strong>Order Date:</strong> <?= date('F j, Y \a\t g:i A', strtotime($order['order_date'])) ?></p>
                        <p><strong>Total Amount:</strong> <?= number_format($order['total_price'], 2) ?> Birr</p>
                        <p><strong>Delivery Location:</strong> <?= htmlspecialchars($order['customer_location']) ?></p>
                        <p><strong>Customer Notes:</strong> <?= isset($order['special_instructions']) ? htmlspecialchars($order['special_instructions']) : 'None' ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Delivery Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($order['delivery_person_name']): ?>
                            <p><strong>Delivery Person:</strong> <?= htmlspecialchars($order['delivery_person_name']) ?></p>
                            <p><strong>Contact:</strong> <?= htmlspecialchars($order['delivery_phone']) ?></p>
                        <?php else: ?>
                            <p class="text-muted">Delivery person not assigned yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td><?= number_format($item['price'], 2) ?> Birr</td>
                                            <td><?= number_format($item['price'] * $item['quantity'], 2) ?> Birr</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong><?= number_format($order['total_price'], 2) ?> Birr</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($order['status'] !== 'Delivered' && $order['status'] !== 'Cancelled'): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Update Order Status</h5>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <?php 
                    $actions = getStatusActions($order_id, $order['status']);
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>