<?php
// Start the session
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    // Redirect to the login page if not logged in as a restaurant
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once '../db.php';

// Initialize variables for form data and error messages
$restaurant_id = $_SESSION['user_id']; // Auto-populate the restaurant ID
$name = $description = $price = '';
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = htmlspecialchars(trim($_POST['price']));

    // Validate form data
    if (empty($name)) {
        $errors['name'] = 'Food name is required.';
    }
    if (empty($description)) {
        $errors['description'] = 'Description is required.';
    }
    if (empty($price)) {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Price must be a valid positive number.';
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        // Validate file type
        if (!in_array($image['type'], $allowed_types)) {
            $errors['image'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }

        // Validate file size
        if ($image['size'] > $max_size) {
            $errors['image'] = 'File size must be less than 5 MB.';
        }

        // If no errors, process the file
        if (empty($errors)) {
            $upload_dir = '../images/'; // Folder to store uploaded images
            $file_name = uniqid() . '_' . basename($image['name']); // Unique file name
            $file_path = $upload_dir . $file_name;

            // Move the uploaded file to the images folder
            if (move_uploaded_file($image['tmp_name'], $file_path)) {
                // File uploaded successfully
                $image_url = 'images/' . $file_name; // Relative path to the image
            } else {
                $errors['image'] = 'Failed to upload image.';
            }
        }
    } else {
        $errors['image'] = 'Image is required.';
    }

    // If no errors, insert data into the database
    if (empty($errors)) {
        try {
            // Insert the food item into the database
            $stmt = $conn->prepare("
                INSERT INTO FoodItem (name, description, price, image_url, restaurant_id)
                VALUES (:name, :description, :price, :image_url, :restaurant_id)
            ");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':image_url' => $image_url,
                ':restaurant_id' => $restaurant_id,
            ]);

            // Set success message
            $_SESSION['status'] = 'success';
            header("Location: ../restaurant_admin.php");
            exit();
        } catch (PDOException $e) {
            // Handle database errors
            $_SESSION['status'] = 'error';
            header("Location: ../restaurant_admin.php");
            exit();
        }
    } else {
        // If there are validation errors, redirect back with error messages
        $_SESSION['errors'] = $errors;
        header("Location: ../restaurant_admin.php");
        exit();
    }
} else {
    // Redirect if the form is not submitted
    header("Location: ../restaurant_admin.php");
    exit();
}
?>