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

// Get the order ID and new status from the URL
if (isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = intval($_GET['id']); // Sanitize input
    $new_status = htmlspecialchars($_GET['status'], ENT_QUOTES, 'UTF-8'); // Sanitize input

    // Update the order status in the database
    try {
        $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);

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
    header("Location: ../pages/delivery_panel.php");
    exit();
}
?>