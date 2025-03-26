<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php"); // Redirect to the login page
    exit();
}

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

// Handle Add to Cart Logic
if (isset($_POST['add_to_cart'])) {
    $item = [
        'food_id' => $_POST['food_id'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'image_url' => $_POST['image_url'], // Ensure this is the correct path
        'quantity' => 1 // Default quantity
    ];

    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if the item already exists in the cart
    $itemExists = false;
    foreach ($_SESSION['cart'] as $key => $cartItem) {
        if ($cartItem['food_id'] === $item['food_id']) {
            // Increase the quantity if the item already exists
            $_SESSION['cart'][$key]['quantity'] += 1;
            $itemExists = true;
            break;
        }
    }

    // If the item does not exist, add it to the cart
    if (!$itemExists) {
        $_SESSION['cart'][] = $item;
    }

    // Redirect back to the previous page (e.g., restaurant menu)
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Handle Remove Item Logic
if (isset($_POST['remove_item'])) {
    $item_name = $_POST['item_name'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['name'] === $item_name) {
            // Decrease quantity by 1
            $_SESSION['cart'][$key]['quantity'] -= 1;

            // If quantity reaches 0, remove the item from the cart
            if ($_SESSION['cart'][$key]['quantity'] <= 0) {
                unset($_SESSION['cart'][$key]);
            }

            // Reset array keys to avoid issues
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            break;
        }
    }
    // Redirect back to the cart page to refresh the display
    header("Location: cart.php");
    exit();
}

// Handle Update Quantity Logic
if (isset($_POST['update_quantity'])) {
    $item_name = $_POST['item_name'];
    $new_quantity = intval($_POST['quantity']);

    if ($new_quantity > 0) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['name'] === $item_name) {
                $_SESSION['cart'][$key]['quantity'] = $new_quantity;
                break;
            }
        }
    } else {
        // If quantity is 0 or less, remove the item
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['name'] === $item_name) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }
    // Redirect back to the cart page to refresh the display
    header("Location: cart.php");
    exit();
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
    <title>Shopping Cart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/cart.css">
</head>
<body>
<div class="container">
    <h1 class="text-center my-4">Your Cart</h1>

    <!-- Display Cart Items -->
    <?php if (!empty($cart_items)): ?>
        <div id="cart-items" class="row g-4">
            <?php foreach ($cart_items as $item): ?>
                <div class="col-md-4">
                    <div class="card cart-item">
                        <!-- Update the image source to point to the correct path -->
                        <img src="../images/<?= htmlspecialchars(basename($item['image_url']), ENT_QUOTES, 'UTF-8') ?>" class="card-img-top" alt="Product Image">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <p class="card-text"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="price">Price: <?= htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') ?> Birr</p>
                            <form action="cart.php" method="POST" class="d-flex justify-content-between">
                                <input type="hidden" name="item_name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="remove_item" class="btn btn-orange">Remove</button>
                                <input type="number" name="quantity" class="form-control w-25" value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?>" min="1">
                                <button type="submit" name="update_quantity" class="btn btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">Your cart is empty.</p>
    <?php endif; ?>

    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-between mt-4">
        <a href="restaurants.php" class="btn btn-outline-primary btn-lg">
            <i class="bi bi-arrow-left"></i> Back to Restaurants
        </a>
        <form action="payment.php" method="POST">
            <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
            <input type="hidden" name="total_quantity" value="<?= $total_quantity ?>">
            <?php foreach ($cart_items as $item): ?>
                <input type="hidden" name="food_names[]" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="food_quantities[]" value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="food_prices[]" value="<?= htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <div class="form-group mb-3">
                <label for="customer_location">Current Location</label>
                <input type="text" name="customer_location" id="customer_location" class="form-control" placeholder="Enter your current location" required>
            </div>
            <button type="submit" name="proceed_to_payment" class="btn btn-success">Proceed to Payment</button>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>