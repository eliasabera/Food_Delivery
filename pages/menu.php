<?php
// Start the session
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php"); // Redirect to the login page
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password (empty)
$dbname = "food_delivery"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the restaurant ID from the URL
if (isset($_GET['id'])) {
    $restaurant_id = intval($_GET['id']); // Sanitize input
} else {
    die("Invalid restaurant ID.");
}

// Handle search query
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']); // Sanitize search input
}

// Pagination logic
$items_per_page = 15; // Number of items per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
$offset = ($page - 1) * $items_per_page; // Offset for SQL query

// Fetch restaurant details
$restaurant_sql = "SELECT name FROM Restaurant WHERE restaurant_id = ?";
$restaurant_stmt = $conn->prepare($restaurant_sql);
$restaurant_stmt->bind_param("i", $restaurant_id);
$restaurant_stmt->execute();
$restaurant_result = $restaurant_stmt->get_result();

if ($restaurant_result->num_rows === 0) {
    die("Restaurant not found.");
}
$restaurant = $restaurant_result->fetch_assoc();

// Fetch total number of menu items for pagination (with search filter)
$total_items_sql = "SELECT COUNT(*) as total FROM FoodItem WHERE restaurant_id = ? AND name LIKE ?";
$total_items_stmt = $conn->prepare($total_items_sql);
$search_param = "%" . $search_query . "%";
$total_items_stmt->bind_param("is", $restaurant_id, $search_param);
$total_items_stmt->execute();
$total_items_result = $total_items_stmt->get_result();
$total_items = $total_items_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page); // Total pages

// Fetch menu items for the restaurant with pagination and search filter
$menu_sql = "SELECT food_id, name, description, price, image_url FROM FoodItem WHERE restaurant_id = ? AND name LIKE ? LIMIT ? OFFSET ?";
$menu_stmt = $conn->prepare($menu_sql);
$menu_stmt->bind_param("isii", $restaurant_id, $search_param, $items_per_page, $offset);
$menu_stmt->execute();
$menu_result = $menu_stmt->get_result();

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $item = [
        'food_id' => intval($_POST['food_id']), // Sanitize input
        'name' => htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8'),
        'description' => htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8'),
        'price' => floatval($_POST['price']), // Sanitize input
        'image_url' => htmlspecialchars($_POST['image_url'], ENT_QUOTES, 'UTF-8'), // Store only the filename
        'restaurant_id' => $restaurant_id, // Include restaurant_id
        'quantity' => 1 // Default quantity
    ];

    // Check if the item is already in the cart
    $item_found = false;
    foreach ($_SESSION['cart'] as &$cart_item) {
        if ($cart_item['food_id'] === $item['food_id']) {
            $cart_item['quantity'] += 1; // Increment quantity
            $item_found = true;
            break;
        }
    }

    // If the item is not in the cart, add it
    if (!$item_found) {
        $_SESSION['cart'][] = $item;
    }

    // Redirect back to the same page to avoid form resubmission
    header("Location: menu.php?id=" . $restaurant_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4" id="restaurant-name">
            <?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8'); ?>
        </h1>

        <!-- Search Bar -->
        <form action="menu.php" method="GET" class="mb-4">
            <input type="hidden" name="id" value="<?php echo $restaurant_id; ?>">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search for food..." value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-orange">Search</button>
            </div>
        </form>
        
        <!-- Menu Items Section -->
        <div id="menu-items" class="row g-4">
            <?php
            if ($menu_result->num_rows > 0) {
                while ($row = $menu_result->fetch_assoc()) {
                    // Ensure the image URL is a valid path to the uploaded image
                    $imagePath = "../images/" . htmlspecialchars(basename($row['image_url']), ENT_QUOTES, 'UTF-8');
                    echo '
                    <div class="col-md-4 col-sm-6">
                        <div class="card menu-item shadow-sm">
                            <img src="' . $imagePath . '" class="card-img-top" alt="' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '">
                            <div class="card-body">
                                <h5 class="card-title">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</h5>
                                <p class="card-text">' . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . '</p>
                                <p class="price fw-bold">Price: ' . htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8') . ' Birr</p>
                                <div class="d-flex justify-content-between">
                                    <form action="menu.php?id=' . $restaurant_id . '" method="POST">
                                        <input type="hidden" name="food_id" value="' . htmlspecialchars($row['food_id'], ENT_QUOTES, 'UTF-8') . '">
                                        <input type="hidden" name="name" value="' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '">
                                        <input type="hidden" name="description" value="' . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . '">
                                        <input type="hidden" name="price" value="' . htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8') . '">
                                        <input type="hidden" name="image_url" value="' . htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') . '">
                                        <button type="submit" name="add_to_cart" class="btn btn-orange">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p class="text-center">No menu items found for this restaurant.</p>';
            }
            ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="my-4">
            <ul class="pagination justify-content-center">
                <?php
                for ($i = 1; $i <= $total_pages; $i++) {
                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                            <a class="page-link" href="menu.php?id=' . $restaurant_id . '&page=' . $i . '&search=' . urlencode($search_query) . '">' . $i . '</a>
                          </li>';
                }
                ?>
            </ul>
        </nav>
        
        <!-- Navigation Buttons -->
        <div class="d-flex justify-content-between mt-4">
            <a href="restaurants.php" class="btn btn-brown">Back to Restaurants</a>
            <a href="cart.php" class="btn btn-orange">Go to Cart</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>