<?php
// Start the session
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    // Redirect to the login page if not logged in as a restaurant
    header("Location: login.php");
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

// Fetch food item details
if (isset($_GET['id'])) {
    $food_id = $_GET['id'];
    $sql = "SELECT * FROM FoodItem WHERE food_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $food_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $food_item = $result->fetch_assoc();
    } else {
        // Redirect if no food item is found
        header("Location: ../restaurant_admin.php");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: ../restaurant_admin.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];

    // Update the food item
    $update_sql = "UPDATE FoodItem SET name = ?, description = ?, price = ?, image_url = ? WHERE food_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssdsi", $name, $description, $price, $image_url, $food_id);

    if ($update_stmt->execute()) {
        // Redirect with success message
        header("Location: ../restaurant_admin.php?status=edit_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: ../restaurant_admin.php?status=edit_error");
        exit();
    }
}

// Include the HTML form
include '../forms/edit_food_form.php';

// Close the connection
$conn->close();
?>