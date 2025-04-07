<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate and sanitize input
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$new_status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Allowed status transitions
$allowed_statuses = ['Pending', 'Preparing', 'Ready', 'Out for Delivery', 'Delivered'];

if ($order_id <= 0 || !in_array($new_status, $allowed_statuses)) {
    header("Location: ../../restaurant_admin.php?status=error");
    exit();
}

// Verify the restaurant owns this order and check current status
$verify_sql = "SELECT o.order_id, o.status, o.delivery_person_id 
               FROM Orders o 
               WHERE o.order_id = ? AND o.restaurant_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header("Location: ../../restaurant_admin.php?status=error");
    exit();
}

// Get current order data
$order_data = $verify_result->fetch_assoc();
$current_status = $order_data['status'];

// Validate status transition
$valid_transitions = [
    'Pending' => ['Preparing', 'Cancelled'],
    'Preparing' => ['Ready', 'Cancelled'],
    'Ready' => ['Out for Delivery'],
    'Out for Delivery' => ['Delivered'],
    'Delivered' => [],
    'Cancelled' => []
];

if (!in_array($new_status, $valid_transitions[$current_status])) {
    header("Location: ../../restaurant_admin.php?status=invalid_transition");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update order status
    $update_sql = "UPDATE Orders SET status = ? WHERE order_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();

    // Special case: When marking as Ready, verify delivery person exists
    if ($new_status === 'Ready') {
        if (empty($order_data['delivery_person_id'])) {
            // This should never happen if assignment happens at checkout
            $_SESSION['status_message'] = [
                'type' => 'danger',
                'text' => 'No delivery person assigned (system error)'
            ];
            $conn->rollback();
            header("Location: ../../restaurant_admin.php?status=error");
            exit();
        }
        
        // Just update status to Out for Delivery (delivery person already assigned)
        $update_sql = "UPDATE Orders SET status = 'Out for Delivery' WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $order_id);
        $update_stmt->execute();
        $new_status = 'Out for Delivery';
    }
    
    $conn->commit();
    
    // Redirect with success message
    header("Location: ../../restaurant_admin.php?status=status_updated&order_id=$order_id&new_status=" . urlencode($new_status));
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../../restaurant_admin.php?status=update_error&message=" . urlencode($e->getMessage()));
    exit();
}

$conn->close();
?>