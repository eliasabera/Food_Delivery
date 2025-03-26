<?php
// Start the session
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    // Redirect to the login page if not logged in as a restaurant
    header("Location: php/login.php");
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

// Fetch restaurant details
$restaurant_id = $_SESSION['user_id'];
$restaurant_sql = "SELECT name, image_url FROM Restaurant WHERE restaurant_id = ?";
$restaurant_stmt = $conn->prepare($restaurant_sql);
$restaurant_stmt->bind_param("i", $restaurant_id);
$restaurant_stmt->execute();
$restaurant_result = $restaurant_stmt->get_result();

if ($restaurant_result->num_rows === 0) {
    // If no restaurant is found, redirect to login
    header("Location: php/login.php");
    exit();
}

$restaurant = $restaurant_result->fetch_assoc();

// Fetch menu items for the restaurant
$menu_sql = "SELECT food_id, name, price, description, image_url FROM FoodItem WHERE restaurant_id = ?";
$menu_stmt = $conn->prepare($menu_sql);
$menu_stmt->bind_param("i", $restaurant_id);
$menu_stmt->execute();
$menu_result = $menu_stmt->get_result();

// Fetch orders for the restaurant
$orders_sql = "SELECT o.order_id, c.username AS customer_name, o.total_price, o.status, d.username AS delivery_person_name
               FROM Orders o
               JOIN Customer c ON o.customer_id = c.customer_id
               LEFT JOIN DeliveryPerson d ON o.delivery_person_id = d.delivery_person_id
               WHERE o.restaurant_id = ?";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $restaurant_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        echo '<div class="alert alert-success">Food item added successfully!</div>';
    } elseif ($_GET['status'] === 'error') {
        echo '<div class="alert alert-danger">Failed to add food item. Please try again.</div>';
    }
}
// Display status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'edit_success') {
        echo '<div class="alert alert-success">Food item updated successfully!</div>';
    } elseif ($_GET['status'] === 'edit_error') {
        echo '<div class="alert alert-danger">Failed to update food item. Please try again.</div>';
    } elseif ($_GET['status'] === 'delete_success') {
        echo '<div class="alert alert-success">Food item deleted successfully!</div>';
    } elseif ($_GET['status'] === 'delete_error') {
        echo '<div class="alert alert-danger">Failed to delete food item. Please try again.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/restaurant.css">
</head>
<body class="bg-light">

    <!-- Header -->
    <header class="bg-orange text-white p-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <!-- Restaurant Image Box -->
            <div class="restaurant-image-box me-3">
                <!-- Update the image source to point to the correct path -->
                <img src="../images/<?php echo htmlspecialchars(basename($restaurant['image_url']), ENT_QUOTES, 'UTF-8'); ?>" alt="Restaurant Image" class="img-fluid rounded-circle" style="width: 60px; height: 60px;">
            </div>
            <h1 class="m-0"><?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8'); ?> Dashboard</h1>
        </div>
        <a href="php/logout.php" class="btn btn-light logout-btn">Logout</a>
    </header>

    <div class="container mt-4">
        <!-- Manage Menu Section -->
        <section class="menu-management">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0">Manage Menu</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="menu-list">
                                <?php
                                if ($menu_result->num_rows > 0) {
                                    while ($row = $menu_result->fetch_assoc()) {
                                        echo '
                                        <tr>
                                            <td>' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</td>
                                            <td>' . htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8') . ' Birr</td>
                                            <td>' . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . '</td>
                                            <td>
                                                <a href="php/edit_food.php?id=' . $row['food_id'] . '" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="php/delete_food.php?id=' . $row['food_id'] . '" class="btn btn-sm btn-danger">Delete</a>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No menu items found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Add Food Item Section -->
        <section class="add-food-item mt-4">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0">Add Food Item</h2>
                </div>
                <div class="card-body">
                    <form id="restaurant-add-food-form" method="POST" action="php/restaurant_add_food.php" enctype="multipart/form-data">
                        <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>" />

                        <div class="mb-3">
                            <label for="name" class="form-label">Food Name:</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description:</label>
                            <textarea id="description" name="description" class="form-control" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (in Birr):</label>
                            <input type="number" id="price" name="price" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Food Image:</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                            <small class="form-text text-muted">
                                Upload an image file (jpg, jpeg, png, gif).
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Add Food Item</button>
                    </form>
                </div>
            </div>
        </section>

<!-- Customer Orders Section -->
        <section class="restaurant-orders mt-4">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0">Customer Orders</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Food Items</th>
                                    <th>Total Price</th>
                                    <th>Delivery Person</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="restaurant-orders-list">
                                <?php
                                // Fetch orders and their items from the database
                                $orders_sql = "SELECT o.order_id, c.username AS customer_name, o.total_price, o.status, d.username AS delivery_person_name
                                            FROM Orders o
                                            JOIN Customer c ON o.customer_id = c.customer_id
                                            LEFT JOIN DeliveryPerson d ON o.delivery_person_id = d.delivery_person_id
                                            WHERE o.restaurant_id = ?";
                                $orders_stmt = $conn->prepare($orders_sql);
                                $orders_stmt->bind_param("i", $restaurant_id);
                                $orders_stmt->execute();
                                $orders_result = $orders_stmt->get_result();

                                if ($orders_result->num_rows > 0) {
                                    while ($order = $orders_result->fetch_assoc()) {
                                        // Fetch order items for each order
                                        $order_items_sql = "SELECT f.name, oi.quantity 
                                                            FROM OrderItem oi
                                                            JOIN FoodItem f ON oi.food_id = f.food_id
                                                            WHERE oi.order_id = ?";
                                        $order_items_stmt = $conn->prepare($order_items_sql);
                                        $order_items_stmt->bind_param("i", $order['order_id']);
                                        $order_items_stmt->execute();
                                        $order_items_result = $order_items_stmt->get_result();

                                        $food_items = [];
                                        while ($item = $order_items_result->fetch_assoc()) {
                                            $food_items[] = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . ' (x' . htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') . ')';
                                        }

                                        echo '
                                        <tr>
                                            <td>' . htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') . '</td>
                                            <td>' . htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') . '</td>
                                            <td>' . implode(', ', $food_items) . '</td>
                                            <td>' . htmlspecialchars($order['total_price'], ENT_QUOTES, 'UTF-8') . ' Birr</td>
                                            <td>' . htmlspecialchars($order['delivery_person_name'] ?? 'Not Assigned', ENT_QUOTES, 'UTF-8') . '</td>
                                            <td>
                                                <span class="badge bg-' . ($order['status'] === 'Pending' ? 'warning' : ($order['status'] === 'Out for Delivery' ? 'info' : 'success')) . '">' . htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') . '</span>
                                            </td>
                                            <td>
                                                <a href="php/mark_ready.php?id=' . $order['order_id'] . '" class="btn btn-sm btn-info">Mark as Ready</a>
                                            </td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">No orders found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>