<?php
// Start the session
session_start();

// Check if the user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    // Redirect to the login page if not logged in
    header("Location: php/login.php");
    exit();
}

// If logged in, display the home page
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
    <link rel="stylesheet" href="./css/index.css">
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

    <!-- Offer Section -->
    <section class="offer py-5 position-relative">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="bg-dark text-white p-4 rounded">
                        <p class="text-uppercase text-orange mb-1">Today’s Offer</p>
                        <h2 class="fw-bold">Get Flat 10% Off</h2>
                        <p>On all the starters you order today</p>
                        <small class="text-muted">*Terms and conditions apply</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vegan Section -->
    <section class="vegan bg-orange text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="./images/habshafood.jpg" alt="Vegan Food" class="img-fluid rounded">
                </div>
                <div class="col-md-6">
                    <h2 class="fw-bold">GO VEGAN!</h2>
                    <p class="mt-3">
                        Vegan food exclusively for you. Explore the wide variety of vegan foods on our menu.
                    </p>
                    <button class="btn btn-light btn-lg mt-3">Explore Now</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
 
</body>
</html>