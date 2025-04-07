<?php
session_start();

// Check if the restaurant is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'restaurant') {
    header("Location: php/login.php");
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

// Fetch restaurant details with average rating
$restaurant_id = $_SESSION['user_id'];
$restaurant_sql = "SELECT r.name, r.image_url, 
                   COALESCE(AVG(rt.food_rating), 0) AS avg_rating,
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
    header("Location: php/login.php");
    exit();
}

$restaurant = $restaurant_result->fetch_assoc();

// Fetch menu items
$menu_sql = "SELECT food_id, name, price, description, image_url FROM FoodItem WHERE restaurant_id = ?";
$menu_stmt = $conn->prepare($menu_sql);
$menu_stmt->bind_param("i", $restaurant_id);
$menu_stmt->execute();
$menu_result = $menu_stmt->get_result();

// Handle status messages
$status_messages = [
    'success' => ['type' => 'success', 'text' => 'Food item added successfully!'],
    'error' => ['type' => 'danger', 'text' => 'Failed to add food item. Please try again.'],
    'edit_success' => ['type' => 'success', 'text' => 'Food item updated successfully!'],
    'edit_error' => ['type' => 'danger', 'text' => 'Failed to update food item. Please try again.'],
    'delete_success' => ['type' => 'success', 'text' => 'Food item deleted successfully!'],
    'delete_error' => ['type' => 'danger', 'text' => 'Failed to delete food item. Please try again.'],
    'status_updated' => ['type' => 'success', 'text' => 'Order status updated successfully!']
];

if (isset($_GET['status']) ){
    $status = $_GET['status'];
    if (array_key_exists($status, $status_messages)) {
        $message = $status_messages[$status];
    }
}
// Display the updated status
if (isset($_GET['new_status'])) {
    echo "<div class='alert alert-success'>Status updated to: " . htmlspecialchars($_GET['new_status']) . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/restaurant.css">
</head>
<body class="bg-light">

    <!-- Header -->
    <header class="bg-orange text-white p-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
        <div class="restaurant-image-box me-3">
            <img src="images/restaurant/<?= htmlspecialchars(basename($restaurant['image_url']), ENT_QUOTES, 'UTF-8') ?>" 
                alt="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>" 
                class="img-fluid rounded" 
                style="width: 100px; height: 100px; object-fit: cover;">
        </div>
            <h1 class="m-0"><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?> Dashboard</h1>
        </div>
        <a href="php/logout.php" class="btn btn-light logout-btn">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
    </header>

    <div class="container mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show">
                <?= $message['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Restaurant Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <?php
                        $order_count_sql = "SELECT COUNT(*) AS total_orders FROM Orders WHERE restaurant_id = ?";
                        $stmt = $conn->prepare($order_count_sql);
                        $stmt->bind_param("i", $restaurant_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $total_orders = $result->fetch_assoc()['total_orders'];
                        ?>
                        <p class="display-4"><?= $total_orders ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Average Rating</h5>
                        <div class="d-flex align-items-center">
                            <span class="display-4 me-2"><?= number_format($restaurant['avg_rating'], 1) ?></span>
                            <div class="rating-stars">
                                <?php
                                $full_stars = floor($restaurant['avg_rating']);
                                $half_star = ($restaurant['avg_rating'] - $full_stars) >= 0.5;
                                
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
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Menu Items</h5>
                        <?php
                        $menu_count_sql = "SELECT COUNT(*) AS total_items FROM FoodItem WHERE restaurant_id = ?";
                        $stmt = $conn->prepare($menu_count_sql);
                        $stmt->bind_param("i", $restaurant_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $total_items = $result->fetch_assoc()['total_items'];
                        ?>
                        <p class="display-4"><?= $total_items ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Menu Section -->
        <section class="menu-management">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="m-0"><i class="fas fa-utensils me-2"></i>Manage Menu</h2>
                    <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#addFoodForm">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <!-- Add Food Item Form (Collapsible) -->
                    <div class="collapse mb-4" id="addFoodForm">
                        <form id="restaurant-add-food-form" method="POST" action="php/Restaurant/restaurant_add_food.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="restaurant_id" value="<?= $restaurant_id ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Food Name</label>
                                    <input type="text" id="name" name="name" class="form-control" required>
                                    <div class="invalid-feedback">Please provide a food name.</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price (in Birr)</label>
                                    <input type="number" id="price" name="price" class="form-control" min="1" step="0.01" required>
                                    <div class="invalid-feedback">Please provide a valid price.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="2" required></textarea>
                                <div class="invalid-feedback">Please provide a description.</div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Food Image</label>
                                <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                                <small class="form-text text-muted">Upload an image file (jpg, jpeg, png, gif)</small>
                                <div class="invalid-feedback">Please provide an image.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Add Food Item
                            </button>
                        </form>
                        <hr class="my-4">
                    </div>

                    <!-- Menu Items Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="menu-list">
                                <?php if ($menu_result->num_rows > 0): ?>
                                    <?php while ($row = $menu_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="images/<?= htmlspecialchars(basename($row['image_url']), ENT_QUOTES, 'UTF-8' )?>" 
                                                         alt="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                         class="rounded me-3" width="50" height="50">
                                                    <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </td>
                                            <td><?= number_format($row['price'], 2) ?> Birr</td>
                                            <td><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="php/Restaurant/edit_food.php?id=<?php echo $row['food_id']; ?>" 
                                                    class="btn btn-sm btn-warning" 
                                                    title="Edit"
                                                    data-toggle="tooltip">
                                                    <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="php/Restaurant/delete_food.php?id=<?php echo $row['food_id']; ?>" 
                                                    class="btn btn-sm btn-danger" 
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this item?')">
                                                    <i class="fas fa-trash"></i>
                                                    </a>    
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-utensils fa-2x mb-3 text-muted"></i>
                                            <p class="mb-0">No menu items found. Add your first item!</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Orders Section -->
        <section class="restaurant-orders">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0"><i class="fas fa-clipboard-list me-2"></i>Customer Orders</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Delivery</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $orders_sql = "SELECT o.order_id, c.username AS customer_name, o.total_price, o.status, 
                                              d.username AS delivery_person_name, o.order_date
                                              FROM Orders o
                                              JOIN Customer c ON o.customer_id = c.customer_id
                                              LEFT JOIN DeliveryPerson d ON o.delivery_person_id = d.delivery_person_id
                                              WHERE o.restaurant_id = ?
                                              ORDER BY o.order_date DESC";
                                $orders_stmt = $conn->prepare($orders_sql);
                                $orders_stmt->bind_param("i", $restaurant_id);
                                $orders_stmt->execute();
                                $orders_result = $orders_stmt->get_result();

                                if ($orders_result->num_rows > 0): 
                                    while ($order = $orders_result->fetch_assoc()):
                                        $order_items_sql = "SELECT f.name, oi.quantity 
                                                           FROM OrderItem oi
                                                           JOIN FoodItem f ON oi.food_id = f.food_id
                                                           WHERE oi.order_id = ?";
                                        $order_items_stmt = $conn->prepare($order_items_sql);
                                        $order_items_stmt->bind_param("i", $order['order_id']);
                                        $order_items_stmt->execute();
                                        $order_items_result = $order_items_stmt->get_result();

                                        $food_items = [];
                                        while ($item = $order_items_result->fetch_assoc()) {
                                            $food_items[] = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . 
                                                           ' (x' . htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') . ')';
                                        }
                                ?>
                                <tr id="order-<?= $order['order_id'] ?>">
                                    <td>#<?= $order['order_id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="popover" 
                                                title="Order Items" 
                                                data-bs-content="<?= htmlspecialchars(implode('<br>', $food_items), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= count($food_items) ?> items
                                        </button>
                                    </td>
                                    <td><?= number_format($order['total_price'], 2) ?> Birr</td>
                                    <td><?= $order['delivery_person_name'] ? htmlspecialchars($order['delivery_person_name'], ENT_QUOTES, 'UTF-8') : 'Not assigned' ?></td>
                                    <td>
                                        <span class="badge bg-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                            <?= htmlspecialchars($order['status'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php 
                                            // Define status actions based on current order status
                                            $actions = [];
                                            
                                            if ($order['status'] === 'Pending') {
                                                $actions[] = [
                                                    'url' => "php/Restaurant/R_update_order_status?id=".$order['order_id']."&status=Preparing",
                                                    'class' => 'btn-info',
                                                    'icon' => 'fa-play',
                                                    'text' => 'Start Preparing'
                                                ];
                                            } elseif ($order['status'] === 'Preparing') {
                                                $actions[] = [
                                                    'url' => "php/Restaurant/R_update_order_status?id=".$order['order_id']."&status=Ready",
                                                    'class' => 'btn-success',
                                                    'icon' => 'fa-check',
                                                    'text' => 'Mark as Ready'
                                                ];
                                            }
                                            
                                            // Add view details button
                                            $actions[] = [
                                                'url' => "php/Restaurant/order_details.php?id=".$order['order_id'],
                                                'class' => 'btn-primary',
                                                'icon' => 'fa-eye',
                                                'text' => 'View Details'
                                            ];
                                            
                                            // Render all action buttons
                                            foreach ($actions as $action): 
                                            ?>
                                                <a href="<?= $action['url'] ?>" 
                                                class="btn btn-sm <?= $action['class'] ?>"
                                                title="<?= $action['text'] ?>"
                                                <?= strpos($action['url'], '#') === 0 ? 'onclick="return false;"' : '' ?>>
                                                    <i class="fas <?= $action['icon'] ?>"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-2x mb-3 text-muted"></i>
                                        <p class="mb-0">No orders found yet.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Ratings Section -->
        <section class="ratings-section">
            <div class="card shadow">
                <div class="card-header">
                    <h2 class="m-0"><i class="fas fa-star me-2"></i>Customer Ratings</h2>
                </div>
                <div class="card-body">
                    <?php if ($restaurant['avg_rating'] > 0): ?>
                        <div class="d-flex align-items-center mb-4">
                            <div class="display-4 me-4"><?= number_format($restaurant['avg_rating'], 1) ?></div>
                            <div>
                                <div class="rating-stars mb-2">
                                    <?php
                                    $full_stars = floor($restaurant['avg_rating']);
                                    $half_star = ($restaurant['avg_rating'] - $full_stars) >= 0.5;
                                    
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
                                <p class="mb-0">Based on <?= $restaurant['total_ratings'] ?> reviews</p>
                            </div>
                        </div>
                        
                        <div class="reviews">
                            <h5 class="mb-3">Recent Feedback</h5>
                            <?php 
                            $reviews_sql = "SELECT r.food_rating, r.delivery_rating, r.feedback_text, r.rating_date, 
                                          u.username AS customer_name
                                          FROM Ratings r
                                          JOIN Customer u ON r.customer_id = u.customer_id
                                          WHERE r.restaurant_id = ?
                                          ORDER BY r.rating_date DESC LIMIT 3";
                            $stmt = $conn->prepare($reviews_sql);
                            $stmt->bind_param("i", $restaurant_id);
                            $stmt->execute();
                            $reviews = $stmt->get_result();
                            
                            if ($reviews->num_rows > 0): 
                                while ($review = $reviews->fetch_assoc()): ?>
                                    <div class="card review-card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <h6 class="card-title mb-0"><?= htmlspecialchars($review['customer_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                                <small class="text-muted"><?= date('M j, Y', strtotime($review['rating_date'])) ?></small>
                                            </div>
                                            <div class="d-flex mb-2">
                                                <div class="me-3">
                                                    <small class="text-muted">Food</small>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= $review['food_rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Delivery</small>
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= $review['delivery_rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="card-text mb-0"><?= htmlspecialchars($review['feedback_text'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <div class="text-end">
                                    <a href="php/restaurant_reviews.php" class="btn btn-sm btn-outline-primary">
                                        View All Reviews <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-star fa-2x mb-3 text-muted"></i>
                                    <p class="mb-0">No reviews yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-star fa-2x mb-3 text-muted"></i>
                            <p class="mb-0">This restaurant hasn't received any ratings yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable popovers
        document.addEventListener('DOMContentLoaded', function() {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    html: true,
                    trigger: 'focus'
                });
            });
            
            // Form validation
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Highlight updated orders
            if (window.location.search.includes('status_updated')) {
                const orderId = new URLSearchParams(window.location.search).get('order_id');
                if (orderId) {
                    const orderRow = document.getElementById(`order-${orderId}`);
                    if (orderRow) {
                        orderRow.classList.add('status-updated');
                        setTimeout(() => {
                            orderRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 500);
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>