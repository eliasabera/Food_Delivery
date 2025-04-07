<?php
session_start();
require 'db_connection.php'; // Your database connection file

$callback_data = json_decode(file_get_contents('php://input'), true);

if ($callback_data && $callback_data['status'] == 'success') {
    $tx_ref = $callback_data['tx_ref'];
    $order_id = explode('-', $tx_ref)[2]; // Extract from FD-timestamp-orderid
    
    // Verify with Chapa API (recommended)
    $ch = curl_init("https://api.chapa.co/v1/transaction/verify/$tx_ref");
    // ... (add verification code from your third script)
    
    // Update order status
    $update_sql = "UPDATE Orders SET status = 'Paid' WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Clear cart
    unset($_SESSION['cart']);
    
    header("HTTP/1.1 200 OK");
}
?>