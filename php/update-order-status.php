<?php
session_start();
require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
    header("Location: login.php");
    exit();
}

// Validate inputs
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$new_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

if (!$order_id || !$new_status) {
    $_SESSION['error'] = "Invalid parameters for status update";
    header("Location: ../pages/delivery_panel.php");
    exit();
}

// Allowed status transitions
$allowed_statuses = [
    'Ready' => ['Out for Delivery'],
    'Out for Delivery' => ['Delivered']
];

try {
    // Verify order exists and is assigned to current delivery person
    $stmt = $conn->prepare("
        SELECT status FROM Orders 
        WHERE order_id = ? AND delivery_person_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $current_status = $stmt->fetchColumn();

    if (!$current_status) {
        $_SESSION['error'] = "Order not found or not assigned to you";
        header("Location: delivery_panel.php");
        exit();
    }

    // Validate status transition
    if (!isset($allowed_statuses[$current_status]) || 
        !in_array($new_status, $allowed_statuses[$current_status])) {
        $_SESSION['error'] = "Invalid status transition";
        header("Location: ../pages/delivery_panel.php");
        exit();
    }

    // Update status
    $update_stmt = $conn->prepare("
        UPDATE Orders SET status = ? 
        WHERE order_id = ? AND delivery_person_id = ?
    ");
    $update_stmt->execute([$new_status, $order_id, $_SESSION['user_id']]);

    // If marking as delivered, update delivery person status
    if ($new_status === 'Delivered') {
        $delivery_stmt = $conn->prepare("
            UPDATE DeliveryPerson 
            SET status = 'available', total_deliveries = total_deliveries + 1 
            WHERE delivery_person_id = ?
        ");
        $delivery_stmt->execute([$_SESSION['user_id']]);
    }

    $_SESSION['success'] = "Order status updated successfully";
    header("Location: order_items.php?order_id=$order_id");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../pages/delivery_panel.php");
    exit();
}
?>