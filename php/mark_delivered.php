<?php
session_start();

// Check if the delivery person is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get the order ID from the URL
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']); // Sanitize input

    // Update the order status to "Delivered"
    try {
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Delivered' WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // Notify the restaurant
        $stmt = $conn->prepare("INSERT INTO Notification (user_id, user_role, message) VALUES (?, 'restaurant', 'Order #' || ? || ' has been delivered.')");
        $stmt->execute([$order['restaurant_id'], $order_id]);

        // Notify the admin
        $stmt = $conn->prepare("INSERT INTO Notification (user_id, user_role, message) VALUES (?, 'admin', 'Order #' || ? || ' has been delivered.')");
        $stmt->execute([1, $order_id]); // Assuming admin ID is 1

        // Redirect back to the delivery panel with a success message
        header("Location: ../pages/delivery_panel.php?status=order_delivered");
        exit();
    } catch (PDOException $e) {
        // Redirect back to the delivery panel with an error message
        header("Location: ../pages/delivery_panel.php?status=update_error");
        exit();
    }
} else {
    // Redirect back to the delivery panel if the request is invalid
    header("Location: .../pages/delivery_panel.php");
    exit();
}
?>