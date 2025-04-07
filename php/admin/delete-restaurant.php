<?php
// Start the session
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once '../db.php';

// Delete restaurant
if (isset($_GET['id'])) {
    $restaurant_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM Restaurant WHERE restaurant_id = ?");
    if ($stmt->execute([$restaurant_id])) {
        // Redirect with success message
        header("Location: manage-restaurants.php?status=delete_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: manage-restaurants.php?status=delete_error");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: manage-restaurants.php");
    exit();
}
?>