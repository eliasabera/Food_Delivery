<?php
// Start the session
session_start();

// Check if the user is logged in and has the appropriate role (e.g., admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
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

// Delete customer
if (isset($_GET['id'])) {
    $customer_id = $_GET['id'];
    $sql = "DELETE FROM Customer WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: ../forms/manage-customers.php?status=delete_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: ../forms/manage-customers.php?status=delete_error");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: ../forms/manage-customers.php");
    exit();
}

// Close the connection
$conn->close();
?>