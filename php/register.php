<?php
// register.php
session_start();

// Include the database connection file
require_once 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $username = trim($_POST['username']);
    $email = trim($_POST['emailid']);
    $phone_number = trim($_POST['phonenumber']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($email) || empty($phone_number) || empty($address) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: signup.php"); // Make sure this points to your form page
        exit();
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT username FROM Customer WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Username already exists. Please choose a different username.";
        header("Location: signup.php");
        exit();
    }

    // Check if phone number already exists with a different username
    $stmt = $conn->prepare("SELECT username FROM Customer WHERE phone_number = :phone_number");
    $stmt->bindParam(':phone_number', $phone_number);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Phone number is already registered with another account.";
        header("Location: signup.php");
        exit();
    }

    // Check if user is trying to register with admin credentials
    $stmt = $conn->prepare("SELECT username FROM Admin WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "This username is reserved for administrators. Please choose a different username.";
        header("Location: signup.php");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT email FROM Customer WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email is already registered. Please use a different email address.";
        header("Location: signup.php");
        exit();
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

        // Set success message and redirect to login page
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: signup.php");
        exit();
    }
}