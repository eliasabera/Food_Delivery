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

// Calculate cart total with delivery fee
$cart_items = $_SESSION['cart'] ?? [];
$subtotal_amount = 0;
$total_quantity = 0;

foreach ($cart_items as $item) {
    $subtotal_amount += $item['price'] * $item['quantity'];
    $total_quantity += $item['quantity'];
}

$total_amount = $subtotal_amount + DELIVERY_FEE;

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    
    // Get restaurant_id from the first cart item
    $restaurant_id = $cart_items[0]['restaurant_id'] ?? null;
    
    // Verify restaurant exists if ID is provided
    if ($restaurant_id) {
        $check_restaurant = $conn->prepare("SELECT restaurant_id FROM restaurant WHERE restaurant_id = ?");
        $check_restaurant->bind_param("i", $restaurant_id);
        $check_restaurant->execute();
        $restaurant_exists = $check_restaurant->get_result()->num_rows > 0;
        $check_restaurant->close();
        
        if (!$restaurant_exists) {
            $restaurant_id = null; // Set to null if restaurant doesn't exist
        }
    }

    // Find an available delivery person
  // Find and assign an available delivery person - FIXED VERSION
$delivery_person_id = null;
$find_delivery_sql = "SELECT delivery_person_id FROM DeliveryPerson 
                     WHERE status = 'available' 
                     ORDER BY RAND() LIMIT 1 FOR UPDATE"; // Added FOR UPDATE to lock the row

$conn->begin_transaction(); // Start transaction to prevent race conditions

try {
    $delivery_result = $conn->query($find_delivery_sql);
    
    if ($delivery_result && $delivery_result->num_rows > 0) {
        $row = $delivery_result->fetch_assoc();
        $delivery_person_id = $row['delivery_person_id'];
        
        // Mark delivery person as busy
        $update_delivery_sql = "UPDATE DeliveryPerson 
                               SET status = 'busy' 
                               WHERE delivery_person_id = ? AND status = 'available'"; // Added status check
        
        $update_stmt = $conn->prepare($update_delivery_sql);
        $update_stmt->bind_param("i", $delivery_person_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update delivery person status");
        }
        
        // Verify exactly 1 row was updated
        if ($conn->affected_rows !== 1) {
            $delivery_person_id = null; // Reset if update failed
            throw new Exception("Delivery person no longer available");
        }
    }
    
    // [REST OF YOUR ORDER PROCESSING CODE HERE]
    
    $conn->commit(); // Commit transaction if everything succeeded
    
} catch (Exception $e) {
    $conn->rollback(); // Rollback on any error
    error_log("Delivery assignment error: " . $e->getMessage());
    // Continue processing order without delivery person if needed
    $delivery_person_id = null;
}

    // 1. Save the order to database
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

    // Create notifications for restaurant and delivery
    $notification_time = date('Y-m-d H:i:s');
    
    // Fixed: Notification for restaurant with proper user_type and sender info
// Fixed: Notification for restaurant with proper user_type and sender info
if ($restaurant_id) {
    $restaurant_notification = "INSERT INTO notifications (
                                user_id, 
                                user_type, 
                                message, 
                                is_read,
                                related_id,
                                created_at,
                                sender_id,
                                sender_type,
                                notification_type
                              ) VALUES (
                                ?, 
                                'restaurant', 
                                CONCAT('New order #', ?, ' received. Total: ETB', ?), 
                                0,
                                ?,
                                ?,
                                ?,
                                'customer',
                                'order'
                              )";
    $restaurant_stmt = $conn->prepare($restaurant_notification);
    if (!$restaurant_stmt) {
        die("Error preparing restaurant notification: " . $conn->error);
    }
    
    // Corrected bind_param - now matches the 6 parameters needed
    $restaurant_stmt->bind_param(
        "iidiss",  // Notice the corrected type definition string
        $restaurant_id,     // i (integer)
        $order_id,          // i (integer)
        $total_amount,      // d (double/float)
        $order_id,          // i (integer)
        $notification_time, // s (string)
        $_SESSION['user_id'] // s (string)
    );
    
    if (!$restaurant_stmt->execute()) {
        die("Error executing restaurant notification: " . $restaurant_stmt->error);
    }
}

    // Notification for delivery person (if assigned)
    if ($delivery_person_id) {
        $delivery_notification = "INSERT INTO notifications (
                                    user_id, 
                                    user_type, 
                                    message, 
                                    is_read,
                                    related_id,
                                    created_at,
                                    sender_type,
                                    notification_type
                                  ) VALUES (
                                    ?, 
                                    'delivery', 
                                    CONCAT('New delivery assignment: Order #', ?), 
                                    0,
                                    ?,
                                    ?,
                                    'system',
                                    'order'
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B00;
            --primary-dark: #E05D00;
            --light-bg: #FFF8F0;
            --text-dark: #333333;
            --text-light: #777777;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }
        
        nav a {
            margin-left: 15px;
            text-decoration: none;
            color: var(--text-dark);
        }
        
        .logout-btn {
            color: #dc3545;
        }
        
        .title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary);
        }
        
        .payment-box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .payment-box h3 {
            margin-bottom: 20px;
            color: var(--primary);
            text-align: center;
        }
        
        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .payment-icons img {
            height: 30px;
            width: auto;
        }
        
        #payment-form {
            display: flex;
            flex-direction: column;
        }
        
        #payment-form label {
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        #payment-form input[type="text"] {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .save-account {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .save-account input {
            margin-right: 10px;
        }
        
        .pay-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pay-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .pay-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .food-items {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .food-items h4 {
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .food-items ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        .food-items li {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .total-display {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            text-align: center;
        }
        
        .total-display p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .total-display span {
            font-weight: bold;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">MH</div>
            <nav>
                <a href="../index.php">Home</a>
                <a href="../php/logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <h1 class="title">PAYMENT</h1>

        <div class="payment-box">
            <h3>Chapa checkout</h3>
            <div class="payment-icons">
                <img src="../images/tele_birr.png" alt="tele_birr">
                <img src="../images/mpesa.png" alt="M-Pesa">
                <img src="../images/cbe-birr.png" alt="CBE Birr">
                <img src="../images/ebirr.png" alt="E-Birr">
            </div>

            <form method="POST" id="payment-form">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone_number" placeholder="Enter your phone number" required>
                
                <div class="save-account">
                    <input type="checkbox" id="save" name="save_account">
                    <label for="save">Save this account for later use</label>
                </div>
                
                <div class="total-display">
    <p>Subtotal (<span><?php echo $total_quantity; ?></span> items): <span><?php echo number_format($subtotal_amount, 2); ?> Birr</span></p>
    <p>Delivery Fee: <span><?php echo number_format(DELIVERY_FEE, 2); ?> Birr</span></p>
    <p>Total amount: <span><?php echo number_format($total_amount, 2); ?> Birr</span></p>
</div>
                
                <button type="submit" id="pay-now-btn" class="pay-btn">Pay now</button>
            </form>

            <div class="food-items">
                <h4>Items in Cart:</h4>
                <ul>
                    <?php foreach ($cart_items as $item): ?>
                        <li><?php echo htmlspecialchars($item['name']); ?> (Qty: <?php echo htmlspecialchars($item['quantity']); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Simple form submission handler
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const btn = document.getElementById('pay-now-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>