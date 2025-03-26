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

// Fetch the delivery person's current status
$delivery_person_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT status FROM DeliveryPerson WHERE delivery_person_id = ?");
    $stmt->execute([$delivery_person_id]);
    $current_status = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    try {
        $stmt = $conn->prepare("UPDATE DeliveryPerson SET status = ? WHERE delivery_person_id = ?");
        $stmt->execute([$new_status, $delivery_person_id]);
        header("Location: delivery_panel.php?status=update_success");
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch all orders assigned to the delivery person
try {
$stmt = $conn->prepare("
    SELECT o.order_id, c.username AS customer_name, c.phone_number, o.customer_location, r.name AS restaurant_name, o.status
    FROM Orders o
    JOIN Customer c ON o.customer_id = c.customer_id
    JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
    WHERE o.delivery_person_id = ?
");
$stmt->execute([$delivery_person_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Display status messages
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $messages = [
        'update_success' => 'Status updated successfully!',
        'update_error' => 'Failed to update status. Please try again.',
        'order_delivered' => 'Order marked as delivered successfully!',
    ];

    if (array_key_exists($status, $messages)) {
        $alert_class = strpos($status, 'success') !== false ? 'alert-success' : 'alert-danger';
        echo "<div class='alert $alert_class'>{$messages[$status]}</div>";
    }
}
$messages = [
    'update_success' => 'Status updated successfully!',
    'update_error' => 'Failed to update status. Please try again.',
    'order_delivered' => 'Order marked as delivered successfully!',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/delivery.css">
</head>
<body class="bg-light">
    <header class="bg-orange text-white p-3 d-flex justify-content-between align-items-center">
        <h1 class="m-0">Delivery Dashboard</h1>
        <a href="../php/logout.php" class="btn btn-light logout-btn">Logout</a>
    </header>

    <div class="container mt-4">
        <!-- Delivery Person Status Section -->
        <section class="delivery-status mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0">Update Your Status</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="delivery_panel.php">
                        <div class="form-group">
                            <label for="status">Current Status:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="available" <?php echo $current_status === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="busy" <?php echo $current_status === 'busy' ? 'selected' : ''; ?>>Busy</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Status</button>
                    </form>
                </div>
            </div>
        </section>

<!-- Orders Section -->
    <section class="delivery-orders">
        <div class="card shadow">
            <div class="card-header">
                <h2 class="m-0">Orders to Deliver</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone Number</th>
                                <th>Location</th>
                                <th>Restaurant</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="delivery-list">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($order['restaurant_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <?php
                                            $status_class = $order['status'] === 'Delivered' ? 'bg-success' : ($order['status'] === 'Out for Delivery' ? 'bg-info' : 'bg-warning');
                                            echo "<span class='badge $status_class'>{$order['status']}</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] === 'Out for Delivery'): ?>
                                                <a href="../php/mark_delivered.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-success">Mark as Delivered</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>