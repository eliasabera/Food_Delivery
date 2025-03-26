<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php"); // Redirect to the login page
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password (empty)
$dbname = "food_delivery"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the latest order details for the logged-in customer
$customer_id = $_SESSION['user_id'];
$order_sql = "SELECT o.order_id, o.total_price, o.status, o.customer_location, o.order_date, 
                     r.name AS restaurant_name, dp.username AS delivery_person_name
              FROM Orders o
              JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
              JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
              WHERE o.customer_id = ?
              ORDER BY o.order_date DESC
              LIMIT 1";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $customer_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows > 0) {
    $order = $order_result->fetch_assoc();
} else {
    die("No order found. Please place an order first.");
}

// Fetch order items for the latest order
$order_id = $order['order_id'];
$order_items_sql = "SELECT fi.name, oi.quantity, oi.price
                    FROM OrderItem oi
                    JOIN FoodItem fi ON oi.food_id = fi.food_id
                    WHERE oi.order_id = ?";
$order_items_stmt = $conn->prepare($order_items_sql);
$order_items_stmt->bind_param("i", $order_id);
$order_items_stmt->execute();
$order_items_result = $order_items_stmt->get_result();
$order_items = $order_items_result->fetch_all(MYSQLI_ASSOC);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/order_confirmation.css">
</head>
<body>
<div class="container">
    <h1 class="text-center my-4">Order Confirmation</h1>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Thank you for your order!</h3>
            <p class="card-text">Your order has been successfully placed. Here are the details:</p>

            <!-- Order Details -->
            <div class="order-details">
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Restaurant:</strong> <?= htmlspecialchars($order['restaurant_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Delivery Person:</strong> <?= htmlspecialchars($order['delivery_person_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Total Amount:</strong> <?= number_format($order['total_price'], 2) ?> Birr</p>
                <p><strong>Delivery Location:</strong> <?= htmlspecialchars($order['customer_location'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Order Status:</strong> <?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Order Date:</strong> <?= htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Order Items -->
            <h4 class="mt-4">Order Items:</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format($item['price'], 2) ?> Birr</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Estimated Delivery Time -->
            <div class="estimated-delivery">
                <p><strong>Estimated Delivery Time:</strong> 30-45 minutes</p>
            </div>

            <!-- Navigation Buttons -->
            <div class="text-center mt-4">
                <a href="restaurants.php" class="btn btn-primary">Back to Restaurants</a>
                <a href="../php/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>