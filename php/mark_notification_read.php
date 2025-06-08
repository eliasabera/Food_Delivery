<?php
session_start();

// Check if user is logged in (either customer or delivery)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

// Only allow customers and delivery personnel
$allowed_roles = ['customer', 'deliveryperson'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../unauthorized.php");
    exit();
}

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

// Validate notification ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid notification ID";
    header("Location: " . ($_SESSION['role'] === 'customer' ? '../index.php' : '../delivery_panel.php'));
    exit();
}

$notification_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['role'] === 'customer' ? 'customer' : 'delivery';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark notification as read (only if it belongs to this user)
    $stmt = $conn->prepare("UPDATE notifications 
                          SET is_read = TRUE 
                          WHERE id = :id 
                          AND user_id = :user_id 
                          AND user_type = :user_type");
    $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Notification not found or you don't have permission";
    } else {
        $_SESSION['success'] = "Notification marked as read";
    }

    // Redirect back to appropriate dashboard
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 
               ($_SESSION['role'] === 'customer' ? '../index.php' : '../delivery_panel.php');
    header("Location: $redirect");
    exit();

} catch (PDOException $e) {
    error_log("Error marking notification read: " . $e->getMessage());
    $_SESSION['error'] = "Database error occurred";
    header("Location: " . ($_SESSION['role'] === 'customer' ? '../index.php' : '../delivery_panel.php'));
    exit();
}
?>