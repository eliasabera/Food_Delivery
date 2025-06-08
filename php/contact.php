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
    <title>Contact Us - Food Delivery</title>
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
        
        /* Contact Page Specific Styles */
        .contact-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../images/contact-hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .contact-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 60px;
        }
        
        .contact-info {
            margin-bottom: 40px;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .contact-item i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-right: 15px;
            margin-top: 5px;
        }
        
        .contact-item h5 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
        }
        
        textarea.form-control {
            min-height: 150px;
        }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 60px;
        }
        
        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }
        
        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 2.2rem;
            }
            
            .contact-hero {
                padding: 70px 0;
            }
            
            .contact-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
                        <a href="about.php" class="nav-link active">About Us</a>
                    </li>
                    <li class="nav-item mx-2">
                        <a href="#" class="nav-link">Contact Us</a>
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
    <section class="contact-hero">
        <div class="container">
            <h1>We'd Love to Hear From You</h1>
            <p class="lead">Have questions, feedback, or suggestions? Reach out to our team.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="contact-card">
                    <h2 class="mb-4">Send Us a Message</h2>
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject">
                        </div>
                        <div class="form-group">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="5"></textarea>
                        </div>
                        <button type="submit" class="btn btn-orange btn-lg">Send Message</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="contact-info">
                    <h3 class="mb-4">Contact Information</h3>
                    
                    <div class="contact-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <h5>Address</h5>
                            <p>123 Food Street<br>Addis Ababa, Ethiopia</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="bi bi-telephone"></i>
                        <div>
                            <h5>Phone</h5>
                            <p>+251 123 456 789</p>
                            <p>+251 987 654 321</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="bi bi-envelope"></i>
                        <div>
                            <h5>Email</h5>
                            <p>contact@fooddelivery.com</p>
                            <p>support@fooddelivery.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="bi bi-clock"></i>
                        <div>
                            <h5>Working Hours</h5>
                            <p>Monday - Sunday</p>
                            <p>8:00 AM - 10:00 PM</p>
                        </div>
                    </div>
                </div>
                
                <div class="social-links">
                    <h5 class="mb-3">Follow Us</h5>
                    <a href="#" class="btn btn-outline-secondary me-2"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-outline-secondary me-2"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="btn btn-outline-secondary me-2"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-outline-secondary"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </section>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>