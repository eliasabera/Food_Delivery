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

// Fetch total number of users
$user_sql = "SELECT COUNT(*) AS total_users FROM Customer";
$user_result = $conn->query($user_sql);
$total_users = $user_result->fetch_assoc()['total_users'];

// Fetch total number of restaurants
$restaurant_sql = "SELECT COUNT(*) AS total_restaurants FROM Restaurant";
$restaurant_result = $conn->query($restaurant_sql);
$total_restaurants = $restaurant_result->fetch_assoc()['total_restaurants'];

// Fetch total number of delivery persons
$delivery_person_sql = "SELECT COUNT(*) AS total_delivery_persons FROM DeliveryPerson";
$delivery_person_result = $conn->query($delivery_person_sql);
$total_delivery_persons = $delivery_person_result->fetch_assoc()['total_delivery_persons'];

// Fetch total number of food items
$food_item_sql = "SELECT COUNT(*) AS total_food_items FROM FoodItem";
$food_item_result = $conn->query($food_item_sql);
$total_food_items = $food_item_result->fetch_assoc()['total_food_items'];

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="bg-light">

    <header class="bg-orange text-white p-3 d-flex justify-content-between align-items-center">
        <h1 class="m-0">Admin Dashboard</h1>
        <a href="php/logout.php" class="btn btn-light logout-btn">Logout</a>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 bg-light sidebar p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="home.php" class="nav-link active">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/manage-customers.php" class="nav-link">Manage Customers</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/manage-restaurants.php" class="nav-link">Manage Restaurants</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/manage-delivery-person.php" class="nav-link">Manage Delivery Persons</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/add-food-item.php" class="nav-link">Add Food Item</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/add-delivery-person.php" class="nav-link">Add Delivery Person</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/add_restaurant.php" class="nav-link">Add Restaurant</a>
                    </li>
                    <li class="nav-item">
                        <a href="php/admin/track_orders.php" class="nav-link">Track Orders</a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <h2>Welcome to the Admin Dashboard</h2>
                <p>Select a section from the sidebar to manage your data.</p>

                <!-- Boxes for Statistics -->
                <div class="row">
                    <!-- Users Box -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow text-center p-4">
                            <i class="bi bi-people-fill text-orange fs-1"></i>
                            <h3 class="mt-3">Users</h3>
                            <p class="h2"><?= $total_users ?></p>
                        </div>
                    </div>

                    <!-- Restaurants Box -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow text-center p-4">
                            <i class="bi bi-shop text-orange fs-1"></i>
                            <h3 class="mt-3">Restaurants</h3>
                            <p class="h2"><?= $total_restaurants ?></p>
                        </div>
                    </div>

                    <!-- Delivery Persons Box -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow text-center p-4">
                            <i class="bi bi-person-badge text-orange fs-1"></i>
                            <h3 class="mt-3">Delivery Persons</h3>
                            <p class="h2"><?= $total_delivery_persons ?></p>
                        </div>
                    </div>

                    <!-- Food Items Box -->
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card shadow text-center p-4">
                            <i class="bi bi-egg-fried text-orange fs-1"></i>
                            <h3 class="mt-3">Food Items</h3>
                            <p class="h2"><?= $total_food_items ?></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>