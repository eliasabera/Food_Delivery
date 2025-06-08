<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $food_id = $_POST['food_id'] ?? null;
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_quantity':
            $quantity = intval($_POST['quantity'] ?? 1);
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['food_id'] == $food_id) {
                    $item['quantity'] = $quantity;
                    $response['success'] = true;
                    break;
                }
            }
            break;
            
        case 'remove_item':
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($food_id) {
                return $item['food_id'] != $food_id;
            }));
            $response['success'] = true;
            break;
    }

    // Calculate new totals
    $total_amount = 0;
    $total_quantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
        $total_quantity += $item['quantity'];
    }

    $response['total_amount'] = $total_amount;
    $response['total_quantity'] = $total_quantity;
    $response['cart_count'] = count($_SESSION['cart']);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>