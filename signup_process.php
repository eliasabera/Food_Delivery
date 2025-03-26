<?php
// Start the session
session_start();

// Include the database connection
require_once 'db.php'; // Make sure this file contains your database connection

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize form inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: signup.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: signup.php");
        exit();
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if the username already exists
    $stmt = $conn->prepare("SELECT * FROM Admin WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        $_SESSION['error'] = "Username already exists!";
        header("Location: signup.php");
        exit();
    }

    // Insert the new admin into the database
    $stmt = $conn->prepare("INSERT INTO Admin (username, password) VALUES (:username, :password)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Admin registered successfully!";
        header("Location: php/login.php");
        exit();
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        header("Location: signup.php");
        exit();
    }
}
?>
