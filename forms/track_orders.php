<?php
// Start the session
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
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

// Fetch all orders
$order_sql = "SELECT o.order_id, o.total_price, o.status, o.customer_location, o.order_date, 
                     c.username AS customer_name, r.name AS restaurant_name, dp.username AS delivery_person_name
              FROM Orders o
              JOIN Customer c ON o.customer_id = c.customer_id
              JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
              JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
              ORDER BY o.order_date DESC";
$order_result = $conn->query($order_sql);
$orders = $order_result->fetch_all(MYSQLI_ASSOC);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="bg-light">

    <header class="bg-orange text-white p-3 d-flex justify-content-between align-items-center">
        <h1 class="m-0">Track Orders</h1>
        <a href="../php/logout.php" class="btn btn-light logout-btn">Logout</a>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 bg-light sidebar p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="../admin.php" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="../php/manage-customers.php" class="nav-link">Manage Customers</a>
                    </li>
                    <li class="nav-item">
                        <a href="../php/manage-restaurants.php" class="nav-link">Manage Restaurants</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage-delivery-person.php" class="nav-link">Manage Delivery Persons</a>
                    </li>
                    <li class="nav-item">
                        <a href="add-food-item.php" class="nav-link">Add Food Item</a>
                    </li>
                    <li class="nav-item">
                        <a href="add-delivery-person.php" class="nav-link">Add Delivery Person</a>
                    </li>
                    <li class="nav-item">
                        <a href="add-restaurant.php" class="nav-link">Add Restaurant</a>
                    </li>
                    <li class="nav-item">
                        <a href="track_orders.php" class="nav-link active">Track Orders</a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <h2>All Orders</h2>
                <p>View and manage all orders placed by customers.</p>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-orange text-white">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Restaurant</th>
                                <th>Delivery Person</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_id'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['restaurant_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['delivery_person_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format($order['total_price'], 2) ?> Birr</td>
                                    <td>
                                        <span class="badge 
                                            <?= $order['status'] === 'Delivered' ? 'bg-success' : 
                                                  ($order['status'] === 'Pending' ? 'bg-warning' : 
                                                  ($order['status'] === 'Cancelled' ? 'bg-danger' : 'bg-primary')) ?>">
                                            <?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_location'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['order_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>