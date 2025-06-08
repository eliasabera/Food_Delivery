<?php
// Start the session
session_start();

// Check if the user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    // Redirect to the login page if not logged in
    header("Location: php/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* Maintain existing styles */
        .bg-orange {
            background-color: #FF6B00;
        }
        .btn-orange {
            background-color: #FF6B00;
            color: white;
        }
        .btn-orange:hover {
            background-color: #E05D00;
            color: white;
        }
        .hero {
            background-color: #FFF8F0;
        }
        /* Additional styles for new pages */
        .about-section, .contact-section {
            padding: 60px 0;
            background-color: #FFF8F0;
        }
        .about-content, .contact-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white p-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="logo bg-orange text-white p-2 rounded-circle">MH</div>
            <!-- Navbar Toggler for Small Screens -->
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list"></i>
            </button>
            <!-- Navbar Links -->
            <nav class="navbar-collapse d-lg-flex justify-content-end" id="navbarNav">
                <ul class="navbar-nav d-flex gap-3">
                    <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="pages/restaurants.php" class="nav-link">Restaurants</a></li>
                    <li class="nav-item"><a href="php/order_history.php" class="nav-link">History</a></li>
                    <li class="nav-item"><a href="php/about.php" class="nav-link">About Us</a></li>
                    <li class="nav-item"><a href="php/contact.php" class="nav-link">Contact Us</a></li>
                    <li class="nav-item">
                        <a href="php/logout.php" class="btn btn-orange">Logout</a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">ENJOY YOUR FOOD <br> AT YOUR PLACE</h1>
                    <button class="btn btn-orange btn-lg mt-3">Order Now</button>
                </div>
                <div class="col-md-6">
                    <img src="./images/food.jpg" alt="Food Illustration" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>