<?php
// Start the session
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
    header("Location: ../php/login.php");
    exit();
}

// Include the database connection
require_once '../db.php';

// Initialize variables for form data and error messages
$username = $email = $phone_number = $address = $password = '';
$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));
    $address = htmlspecialchars(trim($_POST['address']));
    $password = htmlspecialchars(trim($_POST['password']));

    // Validate form data
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    }
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }
    if (empty($phone_number)) {
        $errors['phone_number'] = 'Phone number is required.';
    }
    if (empty($address)) {
        $errors['address'] = 'Address is required.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    // If no errors, insert data into the database
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the delivery person into the database
            $stmt = $conn->prepare("
                INSERT INTO DeliveryPerson (username, email, phone_number, address, password)
                VALUES (:username, :email, :phone_number, :address, :password)
            ");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':phone_number' => $phone_number,
                ':address' => $address,
                ':password' => $hashed_password,
            ]);

            // Set success message
            $success_message = 'Delivery person added successfully!';
        } catch (PDOException $e) {
            // Handle database errors
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Delivery Persons</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        body {
            background-color: #fdf1db;
            background-image: url("https://www.transparenttextures.com/patterns/wood-pattern.png");
            font-family: "Poppins", sans-serif;
        }
        
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            border: none;
        }
        
        h2 {
            color: #333;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: #ff6600;
            border-color: #ff6600;
        }
        
        .btn-primary:hover {
            background-color: #e65c00;
            border-color: #e65c00;
        }
        
        .form-label {
            color: #333;
            font-weight: 500;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .form-control:focus {
            border-color: #ff6600;
            box-shadow: 0 0 0 0.25rem rgba(255, 102, 0, 0.25);
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Add Delivery Person</h2>

        <!-- Display success message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="deliveryForm" method="POST" action="add-delivery-person.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo $username; ?>" required placeholder="Enter username">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>" required placeholder="Enter email">
            </div>

            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?php echo $phone_number; ?>" required placeholder="Enter phone number">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address:</label>
                <textarea id="address" name="address" class="form-control" required placeholder="Enter address"><?php echo $address; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>