<?php
session_start();

// Delivery fee constant
const DELIVERY_FEE = 30.00;

// Database connection
$conn = new mysqli("localhost", "root", "", "food_delivery");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if not logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

// Validate cart has items from a single restaurant
$cart_items = $_SESSION['cart'] ?? [];
if (empty($cart_items)) {
    die("Your cart is empty");
}

// Verify all items belong to the same restaurant
$restaurant_id = $cart_items[0]['restaurant_id'] ?? null;
foreach ($cart_items as $item) {
    if ($item['restaurant_id'] != $restaurant_id) {
        die("All items must be from the same restaurant");
    }
}

// Verify restaurant exists
$check_restaurant = $conn->prepare("SELECT restaurant_id FROM restaurant WHERE restaurant_id = ?");
$check_restaurant->bind_param("i", $restaurant_id);
$check_restaurant->execute();
if ($check_restaurant->get_result()->num_rows == 0) {
    die("Invalid restaurant selected");
}
$check_restaurant->close();

// Calculate cart total with delivery fee
$subtotal_amount = 0;
$total_quantity = 0;
foreach ($cart_items as $item) {
    $subtotal_amount += $item['price'] * $item['quantity'];
    $total_quantity += $item['quantity'];
}
$total_amount = $subtotal_amount + DELIVERY_FEE;

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    
    // Find an available delivery person
    $delivery_person_id = null;
    $find_delivery_sql = "SELECT delivery_person_id FROM DeliveryPerson 
                         WHERE status = 'available' 
                         ORDER BY RAND() LIMIT 1";
    $delivery_result = $conn->query($find_delivery_sql);
    
    if ($delivery_result && $delivery_result->num_rows > 0) {
        $row = $delivery_result->fetch_assoc();
        $delivery_person_id = $row['delivery_person_id'];
        
        // Mark delivery person as busy
        $update_delivery_sql = "UPDATE DeliveryPerson 
                               SET status = 'busy' 
                               WHERE delivery_person_id = ?";
        $update_stmt = $conn->prepare($update_delivery_sql);
        $update_stmt->bind_param("i", $delivery_person_id);
        $update_stmt->execute();
    }

    // Create the order with restaurant association
    $order_sql = "INSERT INTO Orders (
                    customer_id, 
                    restaurant_id, 
                    delivery_person_id,
                    total_price, 
                    status,
                    customer_location
                ) VALUES (?, ?, ?, ?, 'Pending', ?)";

    $stmt = $conn->prepare($order_sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param(
        "iiids", 
        $_SESSION['user_id'], 
        $restaurant_id,
        $delivery_person_id,
        $total_amount,
        $_SESSION['delivery_location']
    );

    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }

    $order_id = $conn->insert_id;
    
    // Save order items
    foreach ($cart_items as $item) {
        $item_sql = "INSERT INTO OrderItem (
                        order_id, 
                        food_id, 
                        quantity, 
                        price
                    ) VALUES (?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        $item_stmt->bind_param(
            "iiid", 
            $order_id, 
            $item['food_id'], 
            $item['quantity'], 
            $item['price']
        );
        $item_stmt->execute();
    }

    // Create notification for restaurant
    $notification_time = date('Y-m-d H:i:s');
    $restaurant_notification = "INSERT INTO notifications (
                                user_id, 
                                user_type, 
                                message, 
                                is_read,
                                related_id,
                                created_at
                              ) VALUES (
                                ?, 
                                'restaurant', 
                                CONCAT('New order #', ?, ' received'), 
                                0,
                                ?,
                                ?
                              )";
    $restaurant_stmt = $conn->prepare($restaurant_notification);
    $restaurant_stmt->bind_param(
        "iiis", 
        $restaurant_id, 
        $order_id,
        $order_id,
        $notification_time
    );
    $restaurant_stmt->execute();
    // Notification for delivery person (if assigned)
    if ($delivery_person_id) {
        $delivery_notification = "INSERT INTO notifications (
                                    user_id, 
                                    user_type, 
                                    message, 
                                    is_read,
                                    related_id,
                                    created_at
                                  ) VALUES (
                                    ?, 
                                    'delivery', 
                                    CONCAT('New delivery assignment: Order #', ?), 
                                    0,
                                    ?,
                                    ?
                                  )";
        $delivery_stmt = $conn->prepare($delivery_notification);
        $delivery_stmt->bind_param(
            "iiis", 
            $delivery_person_id, 
            $order_id,
            $order_id,
            $notification_time
        );
        $delivery_stmt->execute();
    }

    // 2. Initialize Chapa payment
    $chapa_secret_key = 'CHASECK_TEST-JJUndeBmPmz3oeBzaHlfciwH4UWaVsd1';
    $tx_ref = "FD-" . time() . "-" . $order_id;
    
    $data = [
        'amount' => $total_amount,
        'currency' => 'ETB',
        'email' => 'test@gmail.com',
        'first_name' => $_SESSION['first_name'] ?? 'Customer',
        'last_name' => $_SESSION['last_name'] ?? '',
        'tx_ref' => $tx_ref,
        'callback_url' => 'http://localhost/Food_del/pages/payment_callback.php',
        'return_url' => 'http://localhost/Food_del/pages/order_confirmation.php?order_id='.$order_id,
        'customization' => [
            'title' => 'Food',
            'description' => 'Pa'.$order_id
        ],
        'meta' => [
            'order_id' => $order_id,
            'customer_id' => $_SESSION['user_id'],
            'delivery_person_id' => $delivery_person_id
        ]
    ];

    $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $chapa_secret_key,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200) {
        $responseData = json_decode($response, true);
        
        // Record payment in Payment table
        $payment_sql = "INSERT INTO Payment (
                          order_id,
                          payment_method,
                          amount,
                          payment_status
                        ) VALUES (?, 'Mobile Money', ?, 'Pending')";
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("id", $order_id, $total_amount);
        $payment_stmt->execute();
        
        header('Location: ' . $responseData['data']['checkout_url']);
        exit();
    } else {
        // If payment fails, mark delivery person as available again
        if ($delivery_person_id) {
            $reset_delivery_sql = "UPDATE DeliveryPerson 
                                  SET status = 'available' 
                                  WHERE delivery_person_id = ?";
            $reset_stmt = $conn->prepare($reset_delivery_sql);
            $reset_stmt->bind_param("i", $delivery_person_id);
            $reset_stmt->execute();
        }
        
        error_log("Chapa API Error: $response");
        echo $response;
    }

    // Rest of your existing payment processing code...
    // [Keep all Chapa payment integration code]
}
?>

<!-- Rest of your HTML remains exactly the same -->