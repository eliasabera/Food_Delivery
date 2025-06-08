<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/delivery_panel.php");
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'deliveryperson') {
    header("Location: login.php");
    exit();
}

$order_id = (int)$_POST['order_id'];

try {
    // Verify the delivery person is assigned to this order
    $stmt = $conn->prepare("
        UPDATE Orders 
        SET status = 'Delivered', 
            delivered_at = NOW() 
        WHERE order_id = ? 
        AND delivery_person_id = ?
        AND status IN ('Out for Delivery', 'Ready')
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        // Update delivery person status to available
        $stmt = $conn->prepare("UPDATE DeliveryPerson SET status = 'available' WHERE delivery_person_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $_SESSION['status_message'] = [
            'type' => 'success',
            'text' => 'Order marked as delivered successfully!'
        ];
    } else {
        $_SESSION['status_message'] = [
            'type' => 'danger',
            'text' => 'Failed to update order status. Order may have already been delivered.'
        ];
    }
} catch (PDOException $e) {
    $_SESSION['status_message'] = [
        'type' => 'danger',
        'text' => 'Database error: ' . $e->getMessage()
    ];
}

header("Location: ../pages/delivery_panel.php");
exit();
?>