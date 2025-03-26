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

// Delete food item
if (isset($_GET['id'])) {
    $food_id = $_GET['id'];
    $sql = "DELETE FROM FoodItem WHERE food_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $food_id);

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: ../restaurant_admin.php?status=delete_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: ../restaurant_admin.php?status=delete_error");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: ../restaurant_admin.php");
    exit();
}

// Close the connection
$conn->close();
?>