<?php
// Start the session
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once 'db.php';

// Fetch all restaurants from the database
try {
    $stmt = $conn->query("SELECT * FROM Restaurant");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors
    die("Database error: " . $e->getMessage());
}

// Display status messages
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $messages = [
        'update_success' => 'Restaurant updated successfully!',
        'update_error' => 'Failed to update restaurant. Please try again.',
        'delete_success' => 'Restaurant deleted successfully!',
        'delete_error' => 'Failed to delete restaurant. Please try again.',
    ];

    if (array_key_exists($status, $messages)) {
        $alert_class = strpos($status, 'success') !== false ? 'alert-success' : 'alert-danger';
        echo "<div class='alert $alert_class'>{$messages[$status]}</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/manage.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <!-- Manage Restaurants Table -->
        <section class="restaurant-management">
            <div class="card shadow">
                <div class="card-header">
                    <h2>Manage Restaurants</h2>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Restaurant Name</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="restaurant-list">
                            <?php if (empty($restaurants)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No restaurants found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($restaurant['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="update-restaurant.php?id=<?php echo $restaurant['restaurant_id']; ?>" class="btn btn-sm btn-warning">Update</a>
                                            <a href="delete-restaurant.php?id=<?php echo $restaurant['restaurant_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this restaurant?');">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    
</body>
</html>