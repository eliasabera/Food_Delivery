<?php
// register.php

// Include the database connection file
require_once 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $username = $_POST['username'];
    $email = $_POST['emailid'];
    $phone_number = $_POST['phonenumber'];
    $address = $_POST['address'];
    $password = $_POST['password'];

    // Validate input (you can add more validation as needed)
    if (empty($username) || empty($email) || empty($phone_number) || empty($address) || empty($password)) {
        die("All fields are required.");
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert data into the Customer table
    try {
        $stmt = $conn->prepare("INSERT INTO Customer (username, email, phone_number, address, password) 
                                VALUES (:username, :email, :phone_number, :address, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->execute();

        // Redirect to the login page after successful registration
        header("Location: ../php/login.php");
        exit(); // Ensure no further code is executed after the redirect
    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
}
?>