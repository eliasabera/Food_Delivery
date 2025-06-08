<?php
session_start();

// Redirect to login if not customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../php/login.php");
    exit();
}

// Initialize cart if not exists
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    
    switch ($_POST['ajax_action']) {
        case 'add_to_cart':
            if (isset($_POST['food_id'], $_POST['name'], $_POST['price'], $_POST['image_url'], $_POST['restaurant_id'])) {
                $food_id = $_POST['food_id'];
                $item_exists = false;
                
                // Check if item already in cart
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['food_id'] == $food_id) {
                        $item['quantity']++;
                        $item_exists = true;
                        break;
                    }
                }
                
                if (!$item_exists) {
                    $_SESSION['cart'][] = [
                        'food_id' => $food_id,
                        'name' => $_POST['name'],
                        'price' => $_POST['price'],
                        'image_url' => $_POST['image_url'],
                        'description' => $_POST['description'] ?? '',
                        'restaurant_id' => $_POST['restaurant_id'],
                        'quantity' => 1
                    ];
                }
                $response['success'] = true;
            }
            break;
            
        case 'update_quantity':
            if (isset($_POST['food_id'], $_POST['quantity'])) {
                $food_id = $_POST['food_id'];
                $quantity = (int)$_POST['quantity'];
                
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['food_id'] == $food_id) {
                        if ($quantity > 0) {
                            $item['quantity'] = $quantity;
                        } else {
                            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($food_id) {
                                return $item['food_id'] != $food_id;
                            });
                        }
                        $response['success'] = true;
                        break;
                    }
                }
            }
            break;
            
        case 'remove_item':
            if (isset($_POST['food_id'])) {
                $food_id = $_POST['food_id'];
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($food_id) {
                    return $item['food_id'] != $food_id;
                });
                $response['success'] = true;
            }
            break;
    }
    
    // Reindex array and calculate totals
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    $response['cart_count'] = count($_SESSION['cart']);
    $response['total_amount'] = array_reduce($_SESSION['cart'], function($total, $item) {
        return $total + ($item['price'] * $item['quantity']);
    }, 0);
    
    echo json_encode($response);
    exit();
}

// Handle form submission to create order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_location'])) {
    require_once '../php/db.php';
    
    $user_id = $_SESSION['user_id'];
    $delivery_location = $_POST['delivery_location'];
    $total_amount = array_reduce($_SESSION['cart'], function($total, $item) {
        return $total + ($item['price'] * $item['quantity']);
    }, 0) + 30; // Adding delivery fee
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get restaurant ID from cart items (assuming all items are from same restaurant)
        $restaurant_id = $_SESSION['cart'][0]['restaurant_id'] ?? null;
        
        // Check if there's an available delivery person
        $delivery_person_id = null;
        $stmt = $conn->prepare("SELECT delivery_person_id FROM DeliveryPerson 
                               WHERE status = 'available' LIMIT 1 FOR UPDATE");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $delivery_person_id = $result['delivery_person_id'];
            
            // Mark delivery person as busy
            $stmt = $conn->prepare("UPDATE DeliveryPerson SET status = 'busy' 
                                   WHERE delivery_person_id = ?");
            $stmt->execute([$delivery_person_id]);
        }
        
        // Insert order with or without delivery person
        $stmt = $conn->prepare("INSERT INTO Orders 
                              (customer_id, restaurant_id, delivery_person_id, total_price, status, customer_location) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $order_status = $delivery_person_id ? 'Pending' : 'Waiting for Delivery';
        $stmt->execute([$user_id, $restaurant_id, $delivery_person_id, $total_amount, $order_status, $delivery_location]);
        $order_id = $conn->lastInsertId();
        
        // Insert order items
        $stmt = $conn->prepare("INSERT INTO OrderItem 
                              (order_id, food_id, quantity, price) 
                              VALUES (?, ?, ?, ?)");
        
        foreach ($_SESSION['cart'] as $item) {
            $stmt->execute([$order_id, $item['food_id'], $item['quantity'], $item['price']]);
        }
        
        // Create notification for restaurant
        $stmt = $conn->prepare("INSERT INTO notifications 
                              (user_id, user_type, sender_id, sender_type, message, notification_type, related_id) 
                              VALUES (?, 'restaurant', ?, 'customer', ?, 'order', ?)");
        $message = "New order #$order_id received";
        $stmt->execute([$restaurant_id, $user_id, $message, $order_id]);
        
        // Create notification for customer
        if ($delivery_person_id) {
            $message = "Your order #$order_id has been confirmed. Delivery person assigned.";
        } else {
            $message = "Your order #$order_id has been confirmed. Waiting for delivery person to become available.";
        }
        $stmt = $conn->prepare("INSERT INTO notifications 
                              (user_id, user_type, sender_id, sender_type, message, notification_type, related_id) 
                              VALUES (?, 'customer', ?, 'system', ?, 'order', ?)");
        $stmt->execute([$user_id, null, $message, $order_id]);
        
        // If delivery person assigned, create notification for them
        if ($delivery_person_id) {
            $stmt = $conn->prepare("INSERT INTO notifications 
                                  (user_id, user_type, sender_id, sender_type, message, notification_type, related_id) 
                                  VALUES (?, 'delivery', ?, 'system', ?, 'order', ?)");
            $message = "New delivery assignment: Order #$order_id to $delivery_location";
            $stmt->execute([$delivery_person_id, null, $message, $order_id]);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to order status page
        header("Location: payment.php?order_id=$order_id");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error processing your order: " . $e->getMessage();
    }
}

// Calculate totals for display
$total_amount = array_reduce($_SESSION['cart'], function($total, $item) {
    return $total + ($item['price'] * $item['quantity']);
}, 0);
$total_quantity = array_reduce($_SESSION['cart'], function($total, $item) {
    return $total + $item['quantity'];
}, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-image: url("https://www.transparenttextures.com/patterns/wood-pattern.png");
        }
        
        .cart-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        
        .cart-item {
            background-color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cart-item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
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
        
        .empty-cart {
            min-height: 300px;
        }
        
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .delivery-fee {
            color: var(--primary);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Food Delivery</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/restaurants.php">Restaurants</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../php/cart.php">Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../php/order_history.php">My Orders</a>
                    </li>
                </ul>
                <a href="cart.php" class="btn btn-orange position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $total_quantity ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="cart-container">
            <h2 class="mb-4">Your Cart</h2>
            
            <?php if (!empty($_SESSION['cart'])): ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="col-md-6" id="item-<?= $item['food_id'] ?>">
                            <div class="card cart-item">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img src="../images/<?= htmlspecialchars(basename($item['image_url'])) ?>" 
                                             class="img-fluid rounded-start cart-item-img" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             onerror="this.onerror=null;this.src='../images/default-food.jpg'">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                            <p class="card-text text-muted"><?= htmlspecialchars($item['description']) ?></p>
                                            <p class="price fw-bold"><?= number_format($item['price'], 2) ?> Birr</p>
                                            <div class="d-flex align-items-center">
                                                <button class="btn btn-danger btn-sm remove-btn" 
                                                        data-id="<?= $item['food_id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <div class="input-group mx-3" style="width: 130px;">
                                                    <button class="btn btn-outline-secondary minus-btn" 
                                                            data-id="<?= $item['food_id'] ?>">-</button>
                                                    <input type="number" class="form-control quantity-input" 
                                                           value="<?= $item['quantity'] ?>" 
                                                           min="1"
                                                           data-id="<?= $item['food_id'] ?>">
                                                    <button class="btn btn-outline-secondary plus-btn" 
                                                            data-id="<?= $item['food_id'] ?>">+</button>
                                                </div>
                                                <span class="ms-auto fw-bold text-primary item-total">
                                                    <?= number_format($item['price'] * $item['quantity'], 2) ?> Birr
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<span id="item-count"><?= $total_quantity ?></span> items):</span>
                            <span id="subtotal"><?= number_format($total_amount, 2) ?> Birr</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Delivery Fee:</span>
                            <span class="delivery-fee">30.00 Birr</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                            <span>Total:</span>
                            <span id="total"><?= number_format($total_amount + 30, 2) ?> Birr</span>
                        </div>
                        
<!-- Replace the existing form with this one -->
<!-- Replace the form with this version -->
<form action="payment.php" method="POST" id="order-form">
    <div class="mb-3">
        <label for="delivery-location" class="form-label">Delivery Location</label>
        <input type="text" class="form-control" id="delivery-location" 
               name="delivery_location" 
               placeholder="Enter your current address for delivery" 
               required>
        <small class="text-muted">Please provide detailed address including landmarks</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Payment Method</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="mobile-money" value="Mobile Money" checked>
            <label class="form-check-label" for="mobile-money">
                Mobile Money
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="payment_method" id="cash-on-delivery" value="Cash on Delivery">
            <label class="form-check-label" for="cash-on-delivery">
                Cash on Delivery
            </label>
        </div>
    </div>
    <button type="submit" class="btn btn-orange w-100 py-2">
        <i class="fas fa-credit-card me-2"></i> Proceed to Payment
    </button>
</form>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart d-flex flex-column justify-content-center align-items-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Your cart is empty</h4>
                    <a href="../pages/restaurants.php" class="btn btn-orange mt-3">
                        <i class="fas fa-utensils me-2"></i> Browse Restaurants
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Update quantity when changed
        $('.quantity-input').on('change', function() {
            const foodId = $(this).data('id');
            const quantity = $(this).val();
            updateCartItem(foodId, quantity);
        });

        // Plus button
        $('.plus-btn').on('click', function() {
            const foodId = $(this).data('id');
            const input = $(this).siblings('.quantity-input');
            const newVal = parseInt(input.val()) + 1;
            input.val(newVal).trigger('change');
        });

        // Minus button
        $('.minus-btn').on('click', function() {
            const foodId = $(this).data('id');
            const input = $(this).siblings('.quantity-input');
            const newVal = parseInt(input.val()) - 1;
            
            if (newVal >= 1) {
                input.val(newVal).trigger('change');
            } else {
                removeItem(foodId);
            }
        });

        // Remove item
        $('.remove-btn').on('click', function() {
            const foodId = $(this).data('id');
            removeItem(foodId);
        });

        $('#order-form').on('submit', function(e) {
    e.preventDefault(); // Prevent immediate submission
    
    // Store delivery location via AJAX
    $.ajax({
        url: 'store_delivery_location.php',
        method: 'POST',
        data: {
            delivery_location: $('#delivery-location').val()
        },
        success: function() {
            // After storing location, submit the form
            document.getElementById('order-form').submit();
        },
        error: function() {
            alert('Error saving delivery location. Please try again.');
        }
    });
});

        function updateCartItem(foodId, quantity) {
            $.ajax({
                url: 'cart.php',
                method: 'POST',
                data: {
                    ajax_action: 'update_quantity',
                    food_id: foodId,
                    quantity: quantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update item total display
                        const itemTotal = $(`#item-${foodId} .item-total`);
                        const price = parseFloat(itemTotal.text()) / (quantity - 1 || 1);
                        itemTotal.text((price * quantity).toFixed(2) + ' Birr');
                        
                        // Update cart totals
                        $('#item-count').text(response.total_quantity);
                        $('#subtotal').text(response.total_amount.toFixed(2) + ' Birr');
                        $('#total').text((response.total_amount + 30).toFixed(2) + ' Birr');
                    }
                }
            });
        }

        function removeItem(foodId) {
            $.ajax({
                url: 'cart.php',
                method: 'POST',
                data: {
                    ajax_action: 'remove_item',
                    food_id: foodId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`#item-${foodId}`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update cart totals
                            $('#item-count').text(response.total_quantity);
                            $('#subtotal').text(response.total_amount.toFixed(2) + ' Birr');
                            $('#total').text((response.total_amount + 30).toFixed(2) + ' Birr');
                            
                            if (response.cart_count === 0) {
                                location.reload();
                            }
                        });
                    }
                }
            });
        }
    });
    </script>
</body>
</html>