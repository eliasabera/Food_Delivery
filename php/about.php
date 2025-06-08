<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: php/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B00;
            --primary-dark: #E05D00;
            --light-bg: #FFF8F0;
            --text-dark: #333333;
            --text-light: #777777;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background-color: var(--light-bg);
        }
        
        .logo {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            border-radius: 50%;
            font-weight: bold;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }
        
        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary);
        }
        
        .btn-orange {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-orange:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3);
        }
        
        /* About Page Specific Styles */
        .about-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../images/about-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .about-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .about-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
            height: 100%;
            border: none;
        }
        
        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .about-card .card-body {
            padding: 30px;
        }
        
        .about-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .about-card h3 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .story-section {
            background: white;
            border-radius: 15px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 60px 0;
        }
        
        .team-section {
            margin: 60px 0;
        }
        
        .team-member {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        
        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2.2rem;
            }
            
            .about-hero {
                padding: 70px 0;
            }
            
            .story-section {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
<!-- For About.php and Contact.php - Updated Header Section -->
<!-- Updated Header Section -->
<header class="bg-white shadow-sm sticky-top">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light py-2">
            <!-- Logo -->
            <a class="navbar-brand" href="#">
                <div class="logo bg-orange text-white p-2 rounded-circle">MH</div>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Nav Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item mx-2">
                        <a href="../index.php" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a href="../pages/restaurants.php" class="nav-link">Restaurants</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a href="order_history.php" class="nav-link">History</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a href="#" class="nav-link active">About Us</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a href="contact.php" class="nav-link">Contact Us</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="logout.php" class="btn btn-orange px-3 py-2">Logout</a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1>Our Story, Your Satisfaction</h1>
            <p class="lead">Delivering happiness one meal at a time since 2020</p>
        </div>
    </section>

    <!-- Mission/Vision Section -->
    <section class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body text-center">
                        <i class="bi bi-lightning-charge"></i>
                        <h3>Our Mission</h3>
                        <p>To revolutionize food delivery by connecting customers with the best local restaurants through innovative technology and exceptional service.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="about-card">
                    <div class="card-body text-center">
                        <i class="bi bi-eye"></i>
                        <h3>Our Vision</h3>
                        <p>To become the most trusted food delivery platform in Ethiopia, known for reliability, speed, and customer satisfaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section class="container story-section">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="mb-4">From Humble Beginnings</h2>
                <p>Founded in 2020 during the pandemic, we started as a small team with a big dream - to help local restaurants survive while bringing delicious meals to people staying at home.</p>
                <p>What began with just five partner restaurants in Addis Ababa has grown into a network of over 200 establishments serving thousands of happy customers daily across multiple cities.</p>
                <p>Our success comes from our commitment to quality, our amazing restaurant partners, and most importantly - our loyal customers.</p>
            </div>
            <div class="col-lg-6">
                <img src="../images/about-story.jpg" alt="Our Story" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="container team-section">
        <h2 class="text-center mb-5">Meet Our Team</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="team-member">
                    <img src="../images/team1.jpg" alt="Team Member" class="img-fluid">
                    <h4>Michael H.</h4>
                    <p class="text-muted">Founder & CEO</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-member">
                    <img src="../images/team2.jpg" alt="Team Member" class="img-fluid">
                    <h4>Sarah K.</h4>
                    <p class="text-muted">Operations Director</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-member">
                    <img src="../images/team3.jpg" alt="Team Member" class="img-fluid">
                    <h4>David M.</h4>
                    <p class="text-muted">Technology Lead</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5" style="background-color: var(--primary); color: white;">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="display-4">200+</h3>
                    <p>Restaurant Partners</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4">50K+</h3>
                    <p>Happy Customers</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4">98%</h3>
                    <p>Positive Reviews</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4">24/7</h3>
                    <p>Support Available</p>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>