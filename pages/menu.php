<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "food_delivery");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get and sanitize restaurant ID
$restaurant_id = isset($_GET['id']) ? intval($_GET['id']) : die("Invalid restaurant ID.");

// Handle AJAX request for adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_to_cart'])) {
    header('Content-Type: application/json');
    
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    
    $item = [
        'food_id' => intval($_POST['food_id']),
        'name' => htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8'),
        'description' => htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8'),
        'price' => floatval($_POST['price']),
        'image_url' => htmlspecialchars(basename($_POST['image_url'])),
        'restaurant_id' => $restaurant_id,
        'quantity' => 1
    ];

    $item_index = null;
    foreach ($_SESSION['cart'] as $index => $cart_item) {
        if ($cart_item['food_id'] === $item['food_id']) {
            $item_index = $index;
            break;
        }
    }

    if ($item_index !== null) {
        $_SESSION['cart'][$item_index]['quantity'] += 1;
    } else {
        $_SESSION['cart'][] = $item;
    }

    echo json_encode([
        'success' => true,
        'cart_count' => count($_SESSION['cart']),
        'item_name' => $item['name']
    ]);
    exit();
}

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : "";

// Pagination setup
$items_per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Fetch restaurant details
$restaurant_stmt = $conn->prepare("SELECT name FROM Restaurant WHERE restaurant_id = ?");
$restaurant_stmt->bind_param("i", $restaurant_id);
$restaurant_stmt->execute();
$restaurant = $restaurant_stmt->get_result()->fetch_assoc() or die("Restaurant not found.");

// Count total items
$total_items_stmt = $conn->prepare("SELECT COUNT(*) as total FROM FoodItem WHERE restaurant_id = ? AND name LIKE ?");
$search_param = "%" . $conn->real_escape_string($search_query) . "%";
$total_items_stmt->bind_param("is", $restaurant_id, $search_param);
$total_items_stmt->execute();
$total_items = $total_items_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch menu items
$menu_stmt = $conn->prepare("SELECT food_id, name, description, price, image_url FROM FoodItem 
                            WHERE restaurant_id = ? AND name LIKE ? 
                            LIMIT ? OFFSET ?");
$menu_stmt->bind_param("isii", $restaurant_id, $search_param, $items_per_page, $offset);
$menu_stmt->execute();
$menu_result = $menu_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name']) ?> Menu</title>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/menu.css">
    <style>
        /* Header styling */
        .restaurant-header {
            background-color: #ff6600;
            padding: 15px 0;
            position: relative;
            color: white;
        }
        
        /* Back button styling */
        .header-back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: white;
        }
        
        /* Cart button styling */
        .cart-btn {
            position: absolute;
            right: -80%;
            top: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Add to cart button styling */
        .add-to-cart {
            background-color: #ff6600;
            border-color: #ff6600;
            color: white;
        }
        .add-to-cart:hover {
            background-color: #e55c00;
            border-color: #e55c00;
        }
        
        /* Loading spinner */
        .add-to-cart.loading {
            position: relative;
            color: transparent;
        }
        .add-to-cart.loading::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Pagination styling */
        .pagination .page-item.active .page-link {
            background-color: #ff6600;
            border-color: #ff6600;
        }
        .pagination .page-link {
            color: #ff6600;
        }
    </style>
</head>
<body>
    <header class="restaurant-header">
        <!-- Back button -->
        <a href="restaurants.php" class="header-back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <!-- Restaurant title -->
        <h1 class="text-center m-0"><?= htmlspecialchars($restaurant['name']) ?> Menu</h1>
        
        <!-- Cart button -->
        <a href="cart.php" class="cart-btn position-relative w-full">
            <i class="fas fa-shopping-cart"></i>
            <span id="cart-count" class="position-absolute top-0 start-200 translate-middle badge rounded-pill bg-danger">
                <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
            </span>
        </a>
        
        <!-- Search form -->
        <form class="search-form mt-3 mx-auto" style="max-width: 500px;" method="GET">
            <input type="hidden" name="id" value="<?= $restaurant_id ?>">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search menu..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <button class="btn btn-light" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </header>
    
    <div class="container mt-4">
        <!-- Menu Items -->
        <div id="menu-items" class="row g-4">
            <?php if ($menu_result->num_rows > 0): ?>
                <?php while ($row = $menu_result->fetch_assoc()): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card menu-item shadow-sm h-100">
                            <img src="../images/<?= htmlspecialchars(basename($row['image_url'])) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($row['name']) ?>"
                                 loading="lazy">
                            <div class="card-body d-flex flex-column">
                                <h5><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="description flex-grow-1"><?= htmlspecialchars($row['description']) ?></p>
                                <p class="price fw-bold"><?= number_format($row['price'], 2) ?> Birr</p>
                                <button class="btn add-to-cart mt-auto"
                                    data-food-id="<?= $row['food_id'] ?>"
                                    data-name="<?= htmlspecialchars($row['name']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-price="<?= $row['price'] ?>"
                                    data-image-url="<?= htmlspecialchars($row['image_url']) ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center">No menu items found.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $restaurant_id ?>&page=<?= $page-1 ?>&search=<?= urlencode($search_query) ?>">
                            &laquo; Previous
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php 
                // Show page numbers
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?id='.$restaurant_id.'&page=1&search='.urlencode($search_query).'">1</a></li>';
                    if ($start > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?id=<?= $restaurant_id ?>&page=<?= $i ?>&search=<?= urlencode($search_query) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor;
                
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?id='.$restaurant_id.'&page='.$total_pages.'&search='.urlencode($search_query).'">'.$total_pages.'</a></li>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?= $restaurant_id ?>&page=<?= $page+1 ?>&search=<?= urlencode($search_query) ?>">
                            Next &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        document.getElementById('menu-items').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
                const button = e.target.classList.contains('add-to-cart') ? 
                    e.target : e.target.closest('.add-to-cart');
                
                button.classList.add('loading');
                
                const itemData = {
                    ajax_add_to_cart: true,
                    food_id: button.dataset.foodId,
                    name: button.dataset.name,
                    description: button.dataset.description,
                    price: button.dataset.price,
                    image_url: button.dataset.imageUrl
                };
                
                fetch('menu.php?id=<?= $restaurant_id ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(itemData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-count').textContent = data.cart_count;
                        
                        // Show subtle notification
                        const toast = document.createElement('div');
                        toast.className = 'cart-toast';
                        toast.innerHTML = `✓ ${data.item_name} added to cart`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 2000);
                    }
                })
                .finally(() => {
                    button.classList.remove('loading');
                });
            }
        });
    });
    </script>
    
    <style>
    .cart-toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 24px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s, fadeOut 0.3s 1.7s;
        z-index: 1000;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(20px); }
    }
    </style>
</body>
</html>

<?php
$conn->close();
?>