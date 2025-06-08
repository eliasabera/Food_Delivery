<?php
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: ../login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$restaurant_id = $_SESSION['user_id'];

// Fetch restaurant details with average ratings
$restaurant_sql = "SELECT r.name, r.location, r.image_url, 
                   COALESCE(AVG(rt.food_rating), 0) AS avg_food_rating,
                   COALESCE(AVG(rt.delivery_rating), 0) AS avg_delivery_rating,
                   COUNT(rt.rating_id) AS total_ratings
                   FROM Restaurant r
                   LEFT JOIN Ratings rt ON r.restaurant_id = rt.restaurant_id
                   WHERE r.restaurant_id = ?
                   GROUP BY r.restaurant_id";
$restaurant_stmt = $conn->prepare($restaurant_sql);
$restaurant_stmt->bind_param("i", $restaurant_id);
$restaurant_stmt->execute();
$restaurant_result = $restaurant_stmt->get_result();

if ($restaurant_result->num_rows === 0) {
    header("Location: ../login.php");
    exit();
}

$restaurant = $restaurant_result->fetch_assoc();

// Get all reviews for this restaurant with customer and delivery person info
$reviews_sql = "SELECT r.rating_id, r.order_id, r.food_rating, r.delivery_rating, 
               r.feedback_text, r.rating_date,
               c.username AS customer_name, c.email AS customer_email,
               dp.username AS delivery_person_name
               FROM Ratings r
               JOIN Customer c ON r.customer_id = c.customer_id
               JOIN DeliveryPerson dp ON r.delivery_person_id = dp.delivery_person_id
               WHERE r.restaurant_id = ?
               ORDER BY r.rating_date DESC";
$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $restaurant_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> - Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background-color: #f8f9fa;
            color: var(--text-dark);
        }
        
        .header {
            background-color: var(--primary);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .restaurant-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid white;
        }
        
        .rating-summary {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .rating-stars {
            color: #FFD700;
            font-size: 1.2rem;
        }
        
        .review-card {
            border-radius: 10px;
            border: 1px solid #eee;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .customer-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-weight: bold;
        }
        
        .rating-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .food-rating {
            color: var(--primary);
        }
        
        .delivery-rating {
            color: #28a745;
        }
        
        .back-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background-color: var(--primary-dark);
            color: white;
        }
        
        .empty-reviews {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .delivery-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .initials-avatar {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <?php if (!empty($restaurant['image_url'])): ?>
                        <img src="../images/restaurant/<?= htmlspecialchars(basename($restaurant['image_url'])) ?>" 
                             alt="<?= htmlspecialchars($restaurant['name']) ?>" 
                             class="restaurant-image me-4">
                    <?php else: ?>
                        <div class="restaurant-image me-4 d-flex align-items-center justify-content-center bg-light">
                            <span class="initials-avatar">
                                <?= substr(htmlspecialchars($restaurant['name']), 0, 1) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
                        <p class="mb-0">Customer Reviews</p>
                    </div>
                </div>
                <a href="restaurant_admin.php" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Rating Summary -->
        <div class="rating-summary">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="display-4 mb-2"><?= number_format($restaurant['avg_food_rating'], 1) ?></div>
                    <div class="rating-stars mb-2">
                        <?php
                        $full_stars = floor($restaurant['avg_food_rating']);
                        $half_star = ($restaurant['avg_food_rating'] - $full_stars) >= 0.5;
                        
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $full_stars) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($half_star && $i == $full_stars + 1) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p>Average Food Rating</p>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="display-4 mb-2"><?= number_format($restaurant['avg_delivery_rating'], 1) ?></div>
                    <div class="rating-stars mb-2">
                        <?php
                        $full_stars = floor($restaurant['avg_delivery_rating']);
                        $half_star = ($restaurant['avg_delivery_rating'] - $full_stars) >= 0.5;
                        
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $full_stars) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($half_star && $i == $full_stars + 1) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p>Average Delivery Rating</p>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="display-4 mb-2"><?= $restaurant['total_ratings'] ?></div>
                    <p><i class="fas fa-comment-alt me-2"></i>Total Reviews</p>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <h2 class="mb-4">All Customer Reviews</h2>
        
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="customer-image me-3">
                                    <span class="initials-avatar">
                                        <?= substr(htmlspecialchars($review['customer_name']), 0, 1) ?>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($review['customer_name']) ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars($review['customer_email']) ?></small><br>
                                    <small class="text-muted">Order #<?= $review['order_id'] ?></small>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('M j, Y g:i a', strtotime($review['rating_date'])) ?></small>
                        </div>
                        
                        <div class="d-flex mb-3">
                            <div class="me-4">
                                <span class="rating-badge food-rating">
                                    <i class="fas fa-utensils me-1"></i>
                                    <?= $review['food_rating'] ?> stars
                                </span>
                            </div>
                            <div>
                                <span class="rating-badge delivery-rating">
                                    <i class="fas fa-motorcycle me-1"></i>
                                    <?= $review['delivery_rating'] ?> stars
                                </span>
                            </div>
                        </div>
                        
                        <div class="delivery-info">
                            <small class="text-muted">Delivery by:</small>
                            <p class="mb-0"><?= htmlspecialchars($review['delivery_person_name']) ?></p>
                        </div>
                        
                        <?php if (!empty($review['feedback_text'])): ?>
                            <div class="review-content mt-3">
                                <p class="mb-0"><?= htmlspecialchars($review['feedback_text']) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="review-content text-muted mt-3">
                                <p class="mb-0"><i>Customer didn't leave any additional feedback</i></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-reviews">
                <i class="fas fa-star fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No Reviews Yet</h4>
                <p class="text-muted">Your restaurant hasn't received any reviews yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>