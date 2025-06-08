<?php
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
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

    // Update the order status to "Out for Delivery"
    try {
        $stmt = $conn->prepare("UPDATE Orders SET status = 'Out for Delivery' WHERE order_id = ?");
        $stmt->execute([$order_id]);

        // Notify the delivery person
        $stmt = $conn->prepare("INSERT INTO Notification (user_id, user_role, message) VALUES (?, 'delivery_person', 'Order #' || ? || ' is ready for delivery.')");
        $stmt->execute([$order['delivery_person_id'], $order_id]);

        // Redirect back to the restaurant panel with a success message
        header("Location: ../../restaurant_admin.php?status=order_ready");
        exit();
    } catch (PDOException $e) {
        // Redirect back to the restaurant panel with an error message
        header("Location: ../../restaurant_admin.php?status=update_error");
        exit();
    }
} else {
    // Redirect back to the restaurant panel if the request is invalid
    header("Location: ../../restaurant_admin.php");
    exit();
}
?>