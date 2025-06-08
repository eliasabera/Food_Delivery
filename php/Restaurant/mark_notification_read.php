<?php
session_start();

// Check if restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../../php/login.php");
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
if (!isset($_GET['id']) ){
    header("Location: restaurant_admin.php");
    exit();
}

$notification_id = (int)$_GET['id'];
$restaurant_id = (int)$_SESSION['user_id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark notification as read
    $stmt = $conn->prepare("UPDATE notifications 
                          SET is_read = TRUE 
                          WHERE id = :id 
                          AND user_id = :user_id 
                          AND user_type = 'restaurant'");
    $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();

    // Redirect back with success message
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'restaurant_admin.php';
    $_SESSION['notification_message'] = [
        'type' => 'success',
        'text' => 'Notification marked as read'
    ];
    header("Location: $redirect");
    exit();

} catch (PDOException $e) {
    // Log error and redirect back
    error_log("Error marking notification read: " . $e->getMessage());
    $_SESSION['notification_message'] = [
        'type' => 'danger',
        'text' => 'Failed to mark notification as read'
    ];
    header("Location: restaurant_admin.php");
    exit();
}
?>