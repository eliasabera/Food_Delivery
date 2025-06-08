<?php
session_start();

// Check if the user is logged in as a delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
    header("Location: ../php/login.php");
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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch delivery person details
try {
    $stmt = $conn->prepare("SELECT dp.*, COUNT(o.order_id) AS total_deliveries 
                          FROM DeliveryPerson dp
                          LEFT JOIN Orders o ON dp.delivery_person_id = o.delivery_person_id AND o.status = 'Delivered'
                          WHERE dp.delivery_person_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $delivery_person = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_status = $delivery_person['status'];
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

try {
    $notification_stmt = $conn->prepare("SELECT * FROM notifications 
                                       WHERE user_id = ? AND user_type = 'delivery' AND is_read = FALSE
                                       ORDER BY created_at DESC LIMIT 5");
    $notification_stmt->execute([$_SESSION['user_id']]);
    $unread_notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Don't die if notifications fail, just log it
    error_log("Notification error: " . $e->getMessage());
    $unread_notifications = [];
}
// Handle status update - Modified version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("UPDATE DeliveryPerson SET status = ? WHERE delivery_person_id = ?");
        $stmt->execute([$new_status, $_SESSION['user_id']]);
        
        // If changing to 'available', check if they have any pending deliveries
        if ($new_status === 'available') {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Orders 
                                        WHERE delivery_person_id = ? 
                                        AND status IN ('Out for Delivery', 'Ready')");
            $check_stmt->execute([$_SESSION['user_id']]);
            $pending_orders = $check_stmt->fetchColumn();
            
            if ($pending_orders > 0) {
                $conn->rollBack();
                $_SESSION['status_message'] = [
                    'type' => 'danger', 
                    'text' => 'Cannot set to available - you have pending deliveries!'
                ];
                header("Location: delivery_panel.php");
                exit();
            }
        }
        
        $conn->commit();
        $_SESSION['status_message'] = ['type' => 'success', 'text' => 'Status updated successfully!'];
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['status_message'] = [
            'type' => 'danger', 
            'text' => 'Failed to update status: ' . $e->getMessage()
        ];
    }
    header("Location: delivery_panel.php");
    exit();
}

// Handle order status update
// Handle order status update - Modified version
if (isset($_GET['mark_delivered'])) {
    $order_id = intval($_GET['mark_delivered']);
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Verify and update the order
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Delivered', delivered_at = NOW() 
                              WHERE order_id = ? AND delivery_person_id = ? 
                              AND status = 'Out for Delivery'");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Update delivery person's completed deliveries count
            $update_stmt = $conn->prepare("UPDATE DeliveryPerson 
                                          SET total_deliveries = total_deliveries + 1 
                                          WHERE delivery_person_id = ?");
            $update_stmt->execute([$_SESSION['user_id']]);
            
            // Check if there are more pending deliveries
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Orders 
                                        WHERE delivery_person_id = ? 
                                        AND status IN ('Out for Delivery', 'Ready')");
            $check_stmt->execute([$_SESSION['user_id']]);
            $pending_orders = $check_stmt->fetchColumn();
            
            // Update delivery person status based on pending orders
            $status = $pending_orders > 0 ? 'busy' : 'available';
            $status_stmt = $conn->prepare("UPDATE DeliveryPerson SET status = ? 
                                         WHERE delivery_person_id = ?");
            $status_stmt->execute([$status, $_SESSION['user_id']]);
            
            $conn->commit();
            $_SESSION['status_message'] = [
                'type' => 'success', 
                'text' => 'Order marked as delivered!'
            ];
        } else {
            $conn->rollBack();
            $_SESSION['status_message'] = [
                'type' => 'danger', 
                'text' => 'Failed to update order status. It may not be assigned to you or already delivered.'
            ];
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['status_message'] = [
            'type' => 'danger', 
            'text' => 'Database error: ' . $e->getMessage()
        ];
    }
    header("Location: delivery_panel.php");
    exit();
}

// Fetch all orders assigned to the delivery person
try {
    $stmt = $conn->prepare("
        SELECT o.order_id, c.username AS customer_name, c.phone_number, 
               o.customer_location, r.name AS restaurant_name, o.status,
               o.order_date, o.total_price,
               (SELECT COUNT(*) FROM OrderItem oi WHERE oi.order_id = o.order_id) AS item_count
        FROM Orders o
        JOIN Customer c ON o.customer_id = c.customer_id
        JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
        WHERE o.delivery_person_id = ?
        ORDER BY 
            CASE o.status 
                WHEN 'Out for Delivery' THEN 1
                WHEN 'Ready' THEN 2
                ELSE 3
            END,
            o.order_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Display status messages
if (isset($_SESSION['status_message'])) {
    $message = $_SESSION['status_message'];
    echo "<div class='alert alert-{$message['type']}'>{$message['text']}</div>";
    unset($_SESSION['status_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/delivery.css">
    <style>
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
<body class="bg-light">
<header class="delivery-header text-white p-3">
        <div class="container bg-[#ff6600]">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0"><i class="fas fa-motorcycle me-2"></i>Delivery Dashboard</h1>
                <div class="d-flex align-items-center">
                    <!-- ========== NEW: Notification Bell ========== -->
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
                                               href="../php/mark_notification_read.php?id=<?= $notification['id'] ?>&redirect=delivery_panel.php">
                                                <div><?= htmlspecialchars($notification['message']) ?></div>
                                                <small class="notification-time">
                                                    <?= date('M j, g:i a', strtotime($notification['created_at'])) ?>
                                                </small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="..">No new notifications</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="../php/delivery_notifications.php">View All</a></li>
                            </ul>
                        </div>
                    </div>
                    <!-- ========== END NEW ========== -->
                    <a href="../php/logout.php" class="btn btn-light">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <!-- Delivery Person Profile and Status -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card delivery-person-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-orange text-white rounded-circle p-3 me-3">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="mb-0"><?= htmlspecialchars($delivery_person['username']) ?></h4>
                                <p class="text-muted mb-0">Delivery Person</p>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($delivery_person['phone_number']) ?></p>
                            <p class="mb-1"><strong>Completed Deliveries:</strong> <?= $delivery_person['total_deliveries'] ?></p>
                            <p class="mb-0">
                                <strong>Rating:</strong> 
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?= $i <= $delivery_person['avg_rating'] ? '' : '-empty' ?> text-warning"></i>
                                <?php endfor; ?>
                                (<?= number_format($delivery_person['avg_rating'], 1) ?>)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Delivery Status</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="delivery_panel.php">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label"><strong>Current Status:</strong></label>
                                    <div>
                                        <span class="status-badge status-<?= strtolower($current_status) ?>">
                                            <?= ucfirst($current_status) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label"><strong>Update Status:</strong></label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="available" <?= $current_status === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="busy" <?= $current_status === 'busy' ? 'selected' : '' ?>>Busy</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Your Orders</h4>
                    <span class="badge bg-primary"><?= count($orders) ?> active</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>No orders assigned to you</h5>
                        <p class="text-muted">When you receive new orders, they'll appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Restaurant</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id'] ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($order['phone_number']) ?></small>
                                                <small><?= htmlspecialchars($order['customer_location']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                                        <td><?= $order['item_count'] ?> items</td>
                                        <td>
                                            <span class="order-status order-status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                                <?= $order['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="../php/order_items.php?order_id=<?= $order['order_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary me-2"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($order['status'] === 'Out for Delivery'): ?>
                                                    <a href="delivery_panel.php?mark_delivered=<?= $order['order_id'] ?>" 
                                                       class="btn btn-sm btn-success"
                                                       title="Mark Delivered"
                                                       onclick="return confirm('Confirm this order has been delivered?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Map Section (Placeholder) -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Delivery Map</h4>
            </div>
            <div class="card-body">
                <div class="map-container d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <i class="fas fa-map fa-3x text-muted mb-3"></i>
                        <h5>Map View</h5>
                        <p class="text-muted">Interactive map would display here with delivery locations</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to show a confirmation when marking orders as delivered
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            });
            
            // You would add map integration here in a real implementation
            // For example: initMap();
        });
    </script>
</body>
</html>