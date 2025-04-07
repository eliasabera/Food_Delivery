<?php
session_start();

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

// Calculate cart total
$cart_items = $_SESSION['cart'] ?? [];
$total_amount = 0;
$total_quantity = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
    $total_quantity += $item['quantity'];
}

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    
    // Get restaurant_id from the first cart item
    $restaurant_id = $cart_items[0]['restaurant_id'] ?? null;
    if (!$restaurant_id) {
        die("Invalid restaurant");
    }

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

    // 1. Save the order to database
    $order_sql = "INSERT INTO Orders (
                    customer_id, 
                    restaurant_id, 
                    delivery_person_id,
                    total_price, 
                    status
                ) VALUES (?, ?, ?, ?, 'Pending')";
    
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param(
        "iiid", 
        $_SESSION['user_id'], 
        $restaurant_id, 
        $delivery_person_id,
        $total_amount
    );
    $stmt->execute();
    $order_id = $stmt->insert_id;
    
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

<!-- Rest of your HTML remains the same -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/payment.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">FD</div>
            <nav>
                <a href="../index.php">Home</a>
                <a href="../php/logout.php" class="logout-btn">Logout</a>
            </nav>
        </header>

        <h1 class="title">PAYMENT</h1>

        <section class="payment-section">
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
                    
                    <button type="submit" id="pay-now-btn" class="pay-btn">Pay now</button>
                </form>
            </div>

            <div class="amount-box">
                <h3>Amount to Pay</h3>
                <p>Total items: <span><?php echo $total_quantity; ?></span></p>
                <p>Total amount: <span><?php echo number_format($total_amount, 2); ?> Birr</span></p>
                <div class="food-items">
                    <h4>Items in Cart:</h4>
                    <ul>
                        <?php foreach ($cart_items as $item): ?>
                            <li><?php echo htmlspecialchars($item['name']); ?> (Qty: <?php echo htmlspecialchars($item['quantity']); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
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