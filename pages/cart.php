<?php
session_start();

// Redirect to login page if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

// Handle AJAX requests first (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // Initialize cart if not exists
    $_SESSION['cart'] = $_SESSION['cart'] ?? [];
    
    $response = ['success' => false];
    $item_name = $_POST['item_name'] ?? '';

    // Find item in cart efficiently
    $item_index = null;
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['name'] === $item_name) {
            $item_index = $index;
            break; // Exit loop early when found
        }
    }

    switch ($_POST['ajax_action']) {
        case 'remove_item':
            if ($item_index !== null) {
                $_SESSION['cart'][$item_index]['quantity'] -= 1;
                
                if ($_SESSION['cart'][$item_index]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$item_index]);
                }
                
                $response['success'] = true;
            }
            break;
            
        case 'update_quantity':
            $new_quantity = intval($_POST['quantity'] ?? 0);
            
            if ($item_index !== null) {
                if ($new_quantity > 0) {
                    $_SESSION['cart'][$item_index]['quantity'] = $new_quantity;
                    $response['success'] = true;
                } else {
                    unset($_SESSION['cart'][$item_index]);
                    $response['success'] = true;
                    $response['removed'] = true;
                }
            }
            break;
    }
    
    // Reindex array after modifications
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    // Calculate totals efficiently
    $totals = calculateCartTotals($_SESSION['cart']);
    
    $response = array_merge($response, $totals);
    echo json_encode($response);
    exit();
}

// Function to calculate cart totals
function calculateCartTotals($cart_items) {
    $total_amount = 0;
    $total_quantity = 0;
    
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
        $total_quantity += $item['quantity'];
    }
    
    return [
        'total_amount' => $total_amount,
        'total_quantity' => $total_quantity,
        'cart_count' => count($cart_items)
    ];
}

// Get cart items and calculate totals for initial page load
$cart_items = $_SESSION['cart'] ?? [];
$totals = calculateCartTotals($cart_items);
$total_amount = $totals['total_amount'];
$total_quantity = $totals['total_quantity'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/cart.css">
</head>
<body>
<div class="container">
    <h1 class="text-center my-4">Your Cart</h1>

    <!-- Cart Summary -->
    <div class="cart-summary mb-4 p-3 bg-light rounded">
        <div class="d-flex justify-content-between">
            <div>
                <span class="fw-bold">Total Items:</span>
                <span id="total-quantity"><?= $total_quantity ?></span>
            </div>
            <div>
                <span class="fw-bold">Total Amount:</span>
                <span id="total-amount"><?= number_format($total_amount, 2) ?></span> Birr
            </div>
        </div>
    </div>

    <!-- Display Cart Items -->
    <?php if (!empty($cart_items)): ?>
        <div id="cart-items" class="row g-4">
            <?php foreach ($cart_items as $item): ?>
                <div class="col-md-4" id="item-<?= md5($item['name']) ?>">
                    <div class="card cart-item">
                        <img src="../images/<?= htmlspecialchars(basename($item['image_url']), ENT_QUOTES, 'UTF-8') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                             loading="lazy"> <!-- Lazy loading for images -->
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <p class="card-text"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="price">Price: <?= htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') ?> Birr</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-danger remove-item" 
                                    data-item-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <input type="number" class="form-control item-quantity" 
                                    value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?>" 
                                    min="1"
                                    data-item-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-primary update-quantity"
                                    data-item-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center">Your cart is empty.</p>
    <?php endif; ?>

    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-between mt-4">
        <a href="restaurants.php" class="btn btn-outline-primary btn-lg">
            <i class="bi bi-arrow-left"></i> Back to Restaurants
        </a>
        <?php if (!empty($cart_items)): ?>
            <form action="payment.php" method="POST" id="payment-form">
                <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
                <input type="hidden" name="total_quantity" value="<?= $total_quantity ?>">
                <?php foreach ($cart_items as $item): ?>
                    <input type="hidden" name="food_names[]" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="food_quantities[]" value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="food_prices[]" value="<?= htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endforeach; ?>
                <div class="form-group mb-3">
                    <label for="customer_location">Current Location</label>
                    <input type="text" name="customer_location" id="customer_location" 
                           class="form-control" placeholder="Enter your current location" required>
                </div>
                <button type="submit" name="proceed_to_payment" class="btn btn-success btn-lg">
                    <i class="fas fa-credit-card"></i> Proceed to Payment
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Debounce function to limit rapid requests
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }

    // Update cart display after changes
    function updateCartDisplay(data, itemId = null) {
        // Update totals
        document.getElementById('total-quantity').textContent = data.total_quantity;
        document.getElementById('total-amount').textContent = data.total_amount.toFixed(2);
        
        // Remove item if needed
        if (data.removed && itemId) {
            const itemElement = document.getElementById(itemId);
            if (itemElement) {
                itemElement.style.opacity = '0';
                setTimeout(() => {
                    itemElement.remove();
                    checkEmptyCart(data.cart_count);
                }, 300);
            }
        }
        
        // Show success notification
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            showConfirmButton: false,
            timer: 1500
        });
    }

    // Check if cart is empty
    function checkEmptyCart(count) {
        if (count === 0) {
            const cartItems = document.getElementById('cart-items');
            if (cartItems) {
                cartItems.innerHTML = '<p class="text-center">Your cart is empty.</p>';
            }
            const paymentForm = document.getElementById('payment-form');
            if (paymentForm) {
                paymentForm.remove();
            }
        }
    }

    // Show error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message
        });
    }

    // Handle remove item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const button = e.target.closest('.remove-item');
            const itemName = button.dataset.itemName;
            const itemId = 'item-' + md5(itemName);
            
            // Visual feedback
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax_action: 'remove_item',
                    item_name: itemName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay(data, itemId);
                } else {
                    showError('Failed to remove item');
                }
            })
            .catch(() => {
                showError('Network error occurred');
            })
            .finally(() => {
                button.innerHTML = '<i class="fas fa-trash"></i> Remove';
                button.disabled = false;
            });
        }
    });

    // Handle update quantity with debouncing
    const debouncedUpdate = debounce(function(button) {
        const itemName = button.dataset.itemName;
        const quantityInput = button.previousElementSibling;
        const newQuantity = parseInt(quantityInput.value);
        
        if (isNaN(newQuantity) || newQuantity < 1) {
            showError('Please enter a valid quantity');
            return;
        }
        
        // Visual feedback
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                ajax_action: 'update_quantity',
                item_name: itemName,
                quantity: newQuantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartDisplay(data, 'item-' + md5(itemName));
            } else {
                showError('Failed to update quantity');
            }
        })
        .catch(() => {
            showError('Network error occurred');
        })
        .finally(() => {
            button.innerHTML = '<i class="fas fa-sync-alt"></i>';
            button.disabled = false;
        });
    }, 300);

    // Attach event listener for quantity updates
    document.addEventListener('click', function(e) {
        if (e.target.closest('.update-quantity')) {
            debouncedUpdate(e.target.closest('.update-quantity'));
        }
    });

    // Simple MD5 function (replace with a real implementation if needed)
    function md5(string) {
        return string.split('').reduce((acc, char) => {
            return acc + char.charCodeAt(0).toString(16);
        }, '');
    }
});
</script>
</body>
</html>