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

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}


// Get order details if order_id is provided
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = null;
$restaurant_details = null;
$delivery_person_details = null;

if ($order_id > 0) {
    // Get order details - modified query to ensure order belongs to logged-in customer
    $order_sql = "SELECT o.*, r.name AS restaurant_name, name AS delivery_person_name 
                  FROM Orders o
                  JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
                  LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
                  WHERE o.order_id = ? AND o.customer_id = ? AND o.status = 'Delivered'";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        $restaurant_id = $order_details['restaurant_id'];
        $delivery_person_id = $order_details['delivery_person_id'];
        
        // Check if rating already exists for this order
        $rating_check_sql = "SELECT * FROM Ratings WHERE order_id = ?";
        $stmt = $conn->prepare($rating_check_sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "You've already rated this order.";
            $order_details = null; // Prevent showing the rating form
        }
    } else {
        $error = "Order not found, already rated, or you don't have permission to view this order.";
    }
} else {
    $error = "No order ID specified. Please access this page from your order history.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $order_id = intval($_POST['order_id']);
    $food_rating = intval($_POST['food_rating']);
    $delivery_rating = intval($_POST['delivery_rating']);
    $feedback_text = $conn->real_escape_string(trim($_POST['feedback_text']));

    // Validate ratings
    if ($food_rating < 1 || $food_rating > 5 || $delivery_rating < 1 || $delivery_rating > 5) {
        $error = "Please provide valid ratings between 1 and 5 stars.";
    } else {
        // Insert rating into database
        $insert_sql = "INSERT INTO Ratings (order_id, customer_id, restaurant_id, delivery_person_id, 
                       food_rating, delivery_rating, feedback_text)
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiiiiss", $order_id, $_SESSION['user_id'], $restaurant_id, 
                         $delivery_person_id, $food_rating, $delivery_rating, $feedback_text);
        
        if ($stmt->execute()) {
            // Update restaurant average rating
            $update_restaurant_sql = "UPDATE Restaurant r
                                     SET avg_rating = (
                                         SELECT AVG(food_rating) 
                                         FROM Ratings 
                                         WHERE restaurant_id = r.restaurant_id
                                     )
                                     WHERE restaurant_id = ?";
            $stmt = $conn->prepare($update_restaurant_sql);
            $stmt->bind_param("i", $restaurant_id);
            $stmt->execute();
            
            // Update delivery person average rating
            $update_delivery_sql = "UPDATE DeliveryPerson dp
                                   SET avg_rating = (
                                       SELECT AVG(delivery_rating) 
                                       FROM Ratings 
                                       WHERE delivery_person_id = dp.delivery_person_id
                                   )
                                   WHERE delivery_person_id = ?";
            $stmt = $conn->prepare($update_delivery_sql);
            $stmt->bind_param("i", $delivery_person_id);
            $stmt->execute();
            
            $success = "Thank you for your feedback!";
        } else {
            $error = "Error submitting your rating. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/rating.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <h2 class="text-center mb-4">Rate Your Order</h2>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center">
                            <a href="customer_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($order_details): ?>
                            <div class="order-details mb-4">
                                <h4>Order #<?php echo $order_details['order_id']; ?></h4>
                                <p>Restaurant: <?php echo htmlspecialchars($order_details['restaurant_name']); ?></p>
                                <p>Delivery Person: <?php echo htmlspecialchars($order_details['delivery_person_name']); ?></p>
                                <p>Order Date: <?php echo date('M j, Y', strtotime($order_details['order_date'])); ?></p>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                
                                <div class="mb-4">
                                    <h5>Food Quality</h5>
                                    <div class="rating">
                                        <input type="radio" id="food5" name="food_rating" value="5" required>
                                        <label for="food5"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="food4" name="food_rating" value="4">
                                        <label for="food4"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="food3" name="food_rating" value="3">
                                        <label for="food3"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="food2" name="food_rating" value="2">
                                        <label for="food2"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="food1" name="food_rating" value="1">
                                        <label for="food1"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5>Delivery Service</h5>
                                    <div class="rating">
                                        <input type="radio" id="delivery5" name="delivery_rating" value="5" required>
                                        <label for="delivery5"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="delivery4" name="delivery_rating" value="4">
                                        <label for="delivery4"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="delivery3" name="delivery_rating" value="3">
                                        <label for="delivery3"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="delivery2" name="delivery_rating" value="2">
                                        <label for="delivery2"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="delivery1" name="delivery_rating" value="1">
                                        <label for="delivery1"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="feedback_text" class="form-label">Additional Feedback (Optional)</label>
                                    <textarea class="form-control" id="feedback_text" name="feedback_text" rows="4"></textarea>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" name="submit_rating" class="btn btn-submit btn-primary">Submit Rating</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">No order specified for rating.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>