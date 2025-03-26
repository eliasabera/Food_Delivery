<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password (empty)
$dbname = "food_delivery"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php"); // Redirect to the login page
    exit();
}

// Check if the cart data is passed via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed_to_payment'])) {
    // Sanitize and validate input
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0.00;
    $total_quantity = isset($_POST['total_quantity']) ? intval($_POST['total_quantity']) : 0;
    $customer_location = isset($_POST['customer_location']) ? trim($_POST['customer_location']) : '';

    // Retrieve food names and quantities from POST data
    $food_names = $_POST['food_names'] ?? [];
    $food_quantities = $_POST['food_quantities'] ?? [];
    $food_prices = $_POST['food_prices'] ?? [];

    // Save the order to the database
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Get customer_id from the session
        $customer_id = $_SESSION['user_id']; // Use 'user_id' instead of 'customer_id'

        // Get restaurant_id from the first item in the cart
        $restaurant_id = $_SESSION['cart'][0]['restaurant_id'];

        // Assign an available delivery person
        $delivery_person_sql = "SELECT delivery_person_id FROM DeliveryPerson WHERE status = 'available' LIMIT 1";
        $delivery_person_result = $conn->query($delivery_person_sql);

        if ($delivery_person_result->num_rows > 0) {
            $delivery_person = $delivery_person_result->fetch_assoc();
            $delivery_person_id = $delivery_person['delivery_person_id'];

            // Insert order into the Orders table
            $order_sql = "INSERT INTO Orders (customer_id, restaurant_id, delivery_person_id, total_price, status, customer_location) 
                          VALUES (?, ?, ?, ?, 'Pending', ?)";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("iiids", $customer_id, $restaurant_id, $delivery_person_id, $total_amount, $customer_location);

            if (!$order_stmt->execute()) {
                die("Error saving order: " . $order_stmt->error);
            }

            // Get the last inserted order ID
            $order_id = $conn->insert_id;

            // Insert order items into the OrderItem table
            foreach ($_SESSION['cart'] as $item) {
                $food_id = $item['food_id']; // Ensure 'food_id' is included in the cart items
                $quantity = $item['quantity'];
                $price = $item['price'];

                $order_item_sql = "INSERT INTO OrderItem (order_id, food_id, quantity, price) 
                                   VALUES (?, ?, ?, ?)";
                $order_item_stmt = $conn->prepare($order_item_sql);
                $order_item_stmt->bind_param("iiid", $order_id, $food_id, $quantity, $price);

                if (!$order_item_stmt->execute()) {
                    die("Error saving order item: " . $order_item_stmt->error);
                }
            }

            // Update delivery person's status to 'busy'
            $update_delivery_person_sql = "UPDATE DeliveryPerson SET status = 'busy' WHERE delivery_person_id = ?";
            $update_delivery_person_stmt = $conn->prepare($update_delivery_person_sql);
            $update_delivery_person_stmt->bind_param("i", $delivery_person_id);
            $update_delivery_person_stmt->execute();

            // Clear the cart after payment
            unset($_SESSION['cart']);

            // Redirect to the order confirmation page
            header("Location: order_confirmation.php");
            exit();
        } else {
            // No delivery person available, set a session variable to trigger the alert
            $_SESSION['no_delivery_person'] = true;
        }
    } else {
        die("Cart is empty. Please add items to your cart before proceeding.");
    }
}

// Fetch cart items from the session
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Calculate total amount and total quantity
$total_amount = 0;
$total_quantity = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
    $total_quantity += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/payment.css">
    <!-- JavaScript for handling alerts -->
    <script>
        window.onload = function() {
            <?php if (isset($_SESSION['no_delivery_person'])) { ?>
                alert("No available delivery person at the moment. Please try again later.");
                <?php unset($_SESSION['no_delivery_person']); ?>
            <?php } ?>
        };
    </script>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">MH</div>
            <nav>
                <a href="#">Home</a>
                <a href="#" class="logout-btn">Logout</a>
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

                <label for="phone">Phone Number</label>
                <input type="text" id="phone" placeholder="Enter your phone number">

                <div class="save-account">
                    <input type="checkbox" id="save">
                    <label for="save">Save this account for later use</label>
                </div>
            </div>

            <div class="amount-box">
                <h3>Amount to Pay</h3>
                <p>Total items: <span><?php echo $total_quantity; ?></span></p>
                <p>Total amount: <span><?php echo number_format($total_amount, 2); ?> Birr</span></p>
                <div class="food-items">
                    <h4>Items in Cart:</h4>
                    <ul>
                        <?php foreach ($cart_items as $item): ?>
                            <li><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?> (Qty: <?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>

        <form action="payment.php" method="POST">
            <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
            <input type="hidden" name="total_quantity" value="<?php echo $total_quantity; ?>">
            <input type="hidden" name="customer_location" value="Sample Location"> <!-- Replace with actual location input -->
            <?php foreach ($cart_items as $item): ?>
                <input type="hidden" name="food_names[]" value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="food_quantities[]" value="<?php echo htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="food_prices[]" value="<?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <button type="submit" name="proceed_to_payment" class="pay-btn">Pay now</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>