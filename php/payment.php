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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            // Redirect to a confirmation page
            header("Location: order_confirmation.php");
            exit();
        } else {
            die("No available delivery person at the moment. Please try again later.");
        }
    } else {
        die("Cart is empty. Please add items to your cart before proceeding.");
    }
} else {
    // Redirect to cart if data is not passed
    header("Location: cart.php");
    exit();
}
?>