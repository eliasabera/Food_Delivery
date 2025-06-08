<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
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

// Fetch all report data
$report_data = [];

// 1. Admin Statistics
$admin_sql = "SELECT COUNT(*) AS total FROM Admin";
$admin_result = $conn->query($admin_sql);
$report_data['admins'] = $admin_result->fetch_assoc();

// 2. User Statistics
$user_sql = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS new_users,
    (SELECT COUNT(DISTINCT customer_id) FROM Orders) AS active_users
    FROM Customer";
$user_result = $conn->query($user_sql);
$report_data['users'] = $user_result->fetch_assoc();

// 3. Restaurant Statistics
$restaurant_sql = "SELECT 
    COUNT(*) AS total,
    AVG(avg_rating) AS avg_rating,
    (SELECT COUNT(*) FROM FoodItem) AS total_food_items
    FROM Restaurant";
$restaurant_result = $conn->query($restaurant_sql);
$report_data['restaurants'] = $restaurant_result->fetch_assoc();

// Top rated restaurants
$top_restaurants_sql = "SELECT name, avg_rating FROM Restaurant ORDER BY avg_rating DESC LIMIT 5";
$top_restaurants_result = $conn->query($top_restaurants_sql);
$report_data['restaurants']['top_rated'] = [];
while ($row = $top_restaurants_result->fetch_assoc()) {
    $report_data['restaurants']['top_rated'][] = $row;
}

// 4. Delivery Person Statistics
$delivery_sql = "SELECT 
    COUNT(*) AS total,
    AVG(avg_rating) AS avg_rating,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status = 'busy' THEN 1 ELSE 0 END) AS busy
    FROM DeliveryPerson";
$delivery_result = $conn->query($delivery_sql);
$report_data['delivery_persons'] = $delivery_result->fetch_assoc();

// Top delivery persons
$top_delivery_sql = "SELECT username, avg_rating FROM DeliveryPerson ORDER BY avg_rating DESC LIMIT 5";
$top_delivery_result = $conn->query($top_delivery_sql);
$report_data['delivery_persons']['top_rated'] = [];
while ($row = $top_delivery_result->fetch_assoc()) {
    $report_data['delivery_persons']['top_rated'][] = $row;
}

// 5. Order Statistics
$order_sql = "SELECT 
    COUNT(*) AS total,
    SUM(total_price) AS total_revenue,
    AVG(total_price) AS avg_order_value,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) AS preparing,
    SUM(CASE WHEN status = 'Out for Delivery' THEN 1 ELSE 0 END) AS out_for_delivery,
    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) AS delivered,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM Orders";
$order_result = $conn->query($order_sql);
$report_data['orders'] = $order_result->fetch_assoc();

// Recent orders
$recent_orders_sql = "SELECT 
    o.order_id, 
    c.username AS customer,
    r.name AS restaurant,
    dp.username AS delivery_person,
    o.total_price,
    o.status,
    o.order_date
    FROM Orders o
    LEFT JOIN Customer c ON o.customer_id = c.customer_id
    LEFT JOIN Restaurant r ON o.restaurant_id = r.restaurant_id
    LEFT JOIN DeliveryPerson dp ON o.delivery_person_id = dp.delivery_person_id
    ORDER BY o.order_date DESC
    LIMIT 10";
$recent_orders_result = $conn->query($recent_orders_sql);
$report_data['orders']['recent'] = [];
while ($row = $recent_orders_result->fetch_assoc()) {
    $report_data['orders']['recent'][] = $row;
}

// 6. Payment Statistics
$payment_sql = "SELECT 
    COUNT(*) AS total,
    SUM(amount) AS total_amount,
    SUM(CASE WHEN payment_status = 'Completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN payment_status = 'Failed' THEN 1 ELSE 0 END) AS failed,
    SUM(CASE WHEN payment_method = 'Credit Card' THEN 1 ELSE 0 END) AS credit_card,
    SUM(CASE WHEN payment_method = 'Mobile Money' THEN 1 ELSE 0 END) AS mobile_money,
    SUM(CASE WHEN payment_method = 'Cash on Delivery' THEN 1 ELSE 0 END) AS cash_on_delivery
    FROM Payment";
$payment_result = $conn->query($payment_sql);
$report_data['payments'] = $payment_result->fetch_assoc();

// 7. Rating Statistics
$rating_sql = "SELECT 
    COUNT(*) AS total,
    AVG(food_rating) AS avg_food_rating,
    AVG(delivery_rating) AS avg_delivery_rating
    FROM Ratings";
$rating_result = $conn->query($rating_sql);
$report_data['ratings'] = $rating_result->fetch_assoc();

// Recent ratings
$recent_ratings_sql = "SELECT 
    r.rating_id,
    c.username AS customer,
    res.name AS restaurant,
    dp.username AS delivery_person,
    r.food_rating,
    r.delivery_rating,
    r.rating_date
    FROM Ratings r
    JOIN Customer c ON r.customer_id = c.customer_id
    JOIN Restaurant res ON r.restaurant_id = res.restaurant_id
    JOIN DeliveryPerson dp ON r.delivery_person_id = dp.delivery_person_id
    ORDER BY r.rating_date DESC
    LIMIT 5";
$recent_ratings_result = $conn->query($recent_ratings_sql);
$report_data['ratings']['recent'] = [];
while ($row = $recent_ratings_result->fetch_assoc()) {
    $report_data['ratings']['recent'][] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-header {
            background-color: #FF6B00;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .stat-card {
            border-left: 4px solid #FF6B00;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #FF6B00;
            border-bottom: 2px solid #FF6B00;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .pending { background-color: #FFC107; color: #000; }
        .preparing { background-color: #17A2B8; color: #FFF; }
        .out_for_delivery { background-color: #007BFF; color: #FFF; }
        .delivered { background-color: #28A745; color: #FFF; }
        .cancelled { background-color: #DC3545; color: #FFF; }
        .rating-star { color: #FFC107; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="report-header text-center">
            <h1>Food Delivery System Report</h1>
            <p class="lead">Generated on <?= date('F j, Y \a\t H:i:s') ?></p>
        </div>

        <!-- Summary Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Admins</h5>
                        <h2><?= $report_data['admins']['total'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <h2><?= $report_data['users']['total'] ?></h2>
                        <small><?= $report_data['users']['new_users'] ?> new this month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Restaurants</h5>
                        <h2><?= $report_data['restaurants']['total'] ?></h2>
                        <small>Avg rating: <?= number_format($report_data['restaurants']['avg_rating'], 1) ?>/5</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Delivery Persons</h5>
                        <h2><?= $report_data['delivery_persons']['total'] ?></h2>
                        <small><?= $report_data['delivery_persons']['available'] ?> available</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Sections -->
        <div class="row mt-4">
            <!-- Orders Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title">Orders Summary</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Total Orders: <strong><?= $report_data['orders']['total'] ?></strong></p>
                                <p>Total Revenue: <strong><?= number_format($report_data['orders']['total_revenue'], 2) ?> Birr</strong></p>
                                <p>Avg Order Value: <strong><?= number_format($report_data['orders']['avg_order_value'], 2) ?> Birr</strong></p>
                            </div>
                            <div class="col-md-6">
                                <p>Pending: <span class="badge-status pending"><?= $report_data['orders']['pending'] ?></span></p>
                                <p>Preparing: <span class="badge-status preparing"><?= $report_data['orders']['preparing'] ?></span></p>
                                <p>Out for Delivery: <span class="badge-status out_for_delivery"><?= $report_data['orders']['out_for_delivery'] ?></span></p>
                                <p>Delivered: <span class="badge-status delivered"><?= $report_data['orders']['delivered'] ?></span></p>
                                <p>Cancelled: <span class="badge-status cancelled"><?= $report_data['orders']['cancelled'] ?></span></p>
                            </div>
                        </div>

                        <h4 class="mt-4">Recent Orders</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Restaurant</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['orders']['recent'] as $order): ?>
                                    <tr>
                                        <td><?= $order['order_id'] ?></td>
                                        <td><?= $order['customer'] ?></td>
                                        <td><?= $order['restaurant'] ?></td>
                                        <td><?= number_format($order['total_price'], 2) ?> Birr</td>
                                        <td>
                                            <?php 
                                            $status_class = strtolower(str_replace(' ', '_', $order['status']));
                                            echo '<span class="badge-status '.$status_class.'">'.$order['status'].'</span>';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ratings Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title">Ratings & Feedback</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Total Ratings: <strong><?= $report_data['ratings']['total'] ?></strong></p>
                                <p>Avg Food Rating: 
                                    <strong>
                                        <?= number_format($report_data['ratings']['avg_food_rating'], 1) ?>
                                        <span class="rating-star">★</span>
                                    </strong>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p>Avg Delivery Rating: 
                                    <strong>
                                        <?= number_format($report_data['ratings']['avg_delivery_rating'], 1) ?>
                                        <span class="rating-star">★</span>
                                    </strong>
                                </p>
                            </div>
                        </div>

                        <h4 class="mt-4">Recent Ratings</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Restaurant</th>
                                        <th>Delivery</th>
                                        <th>Food</th>
                                        <th>Delivery</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['ratings']['recent'] as $rating): ?>
                                    <tr>
                                        <td><?= $rating['customer'] ?></td>
                                        <td><?= $rating['restaurant'] ?></td>
                                        <td><?= $rating['delivery_person'] ?></td>
                                        <td>
                                            <?= str_repeat('<span class="rating-star">★</span>', $rating['food_rating']) ?>
                                        </td>
                                        <td>
                                            <?= str_repeat('<span class="rating-star">★</span>', $rating['delivery_rating']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Sections -->
        <div class="row mt-4">
            <!-- Top Restaurants -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title">Top Rated Restaurants</h3>
                        <ul class="list-group">
                            <?php foreach ($report_data['restaurants']['top_rated'] as $restaurant): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= $restaurant['name'] ?>
                                <span class="badge bg-warning rounded-pill">
                                    <?= number_format($restaurant['avg_rating'], 1) ?> ★
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Top Delivery Persons -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title">Top Rated Delivery Persons</h3>
                        <ul class="list-group">
                            <?php foreach ($report_data['delivery_persons']['top_rated'] as $delivery): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= $delivery['username'] ?>
                                <span class="badge bg-warning rounded-pill">
                                    <?= number_format($delivery['avg_rating'], 1) ?> ★
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="section-title">Payment Methods</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <h5>Credit Card</h5>
                                        <h3><?= $report_data['payments']['credit_card'] ?></h3>
                                        <p><?= number_format(($report_data['payments']['credit_card']/$report_data['payments']['total'])*100, 1) ?>%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <h5>Mobile Money</h5>
                                        <h3><?= $report_data['payments']['mobile_money'] ?></h3>
                                        <p><?= number_format(($report_data['payments']['mobile_money']/$report_data['payments']['total'])*100, 1) ?>%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <h5>Cash on Delivery</h5>
                                        <h3><?= $report_data['payments']['cash_on_delivery'] ?></h3>
                                        <p><?= number_format(($report_data['payments']['cash_on_delivery']/$report_data['payments']['total'])*100, 1) ?>%</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>