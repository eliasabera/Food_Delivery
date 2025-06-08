<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch restaurants with ratings
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT 
            r.restaurant_id, 
            r.name, 
            r.location, 
            r.image_url,
            COALESCE(AVG(rt.food_rating), 0) AS avg_rating,
            COUNT(rt.rating_id) AS rating_count
        FROM Restaurant r
        LEFT JOIN Ratings rt ON r.restaurant_id = rt.restaurant_id
        ";

if (!empty($search)) {
    $sql .= " WHERE r.name LIKE '%$search%' OR r.location LIKE '%$search%'";
}

$sql .= " GROUP BY r.restaurant_id";
$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants | Food Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/resturants.css">
    <style>
        .restaurant-card .card-icon {
            font-size: 4rem;
            color: #ff6600;
            margin-bottom: 1rem;
        }
        .rating-stars {
            color: #ffc107;
            margin-right: 5px;
        }
        .card-image {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            overflow: hidden;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <header class="restaurant-header">
        <div class="container">
            <h1 class="text-center">Discover Restaurants</h1>
            <form class="search-form" method="GET" action="">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search restaurants..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-search" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </header>

    <main class="container restaurant-container">
        <?php if ($result->num_rows > 0): ?>
            <div class="restaurant-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    // Improved image path handling
                    $image_path = '';
                    $image_found = false;
                    
                    if (!empty($row['image_url'])) {
                        // Check if it's already a full path
                        if (filter_var($row['image_url'], FILTER_VALIDATE_URL)) {
                            $image_path = $row['image_url'];
                            $image_found = true;
                        } else {
                            // Handle relative paths
                            $possible_paths = [
                                '../images/restaurant/' . basename($row['image_url']),
                                'images/restaurant/' . basename($row['image_url']),
                                $row['image_url'] // Try the stored path as-is
                            ];
                            
                            foreach ($possible_paths as $path) {
                                if (file_exists($path)) {
                                    $image_path = $path;
                                    $image_found = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Calculate star ratings
                    $full_stars = floor($row['avg_rating']);
                    $has_half_star = ($row['avg_rating'] - $full_stars) >= 0.5;
                    ?>
                    <div class="restaurant-card">
                        <div class="card-image">
                            <?php if ($image_found): ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'card-icon\'><i class=\'fas fa-utensils\'></i></div>'">
                            <?php else: ?>
                                <div class="card-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-overlay">
                                <a href="menu.php?id=<?php echo $row['restaurant_id']; ?>" class="btn btn-view">
                                    View Menu <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($row['location']); ?>
                            </p>
                            <div class="rating">
                                <div class="rating-stars">
                                    <?php
                                    // Display full stars
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $full_stars) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($has_half_star && $i == $full_stars + 1) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span>(<?php echo $row['rating_count']; ?>)</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="card-icon">
                    <i class="fas fa-store-slash"></i>
                </div>
                <h3>No restaurants found</h3>
                <?php if (!empty($search)): ?>
                    <p>Try a different search term</p>
                <?php else: ?>
                    <p>Check back later for new restaurants</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="restaurant-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Food Delivery. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>