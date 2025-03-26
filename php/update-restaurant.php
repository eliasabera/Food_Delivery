<?php
// Start the session
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
    header("Location: ../php/login.php");
    exit();
}

// Include the database connection
require_once 'db.php';

// Fetch restaurant details
if (isset($_GET['id'])) {
    $restaurant_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Restaurant WHERE restaurant_id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        // Redirect if no restaurant is found
        header("Location: ../forms/manage-restaurants.php");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: ../forms/manage-restaurants.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $location = $_POST['location'];

    // Update the restaurant
    $update_stmt = $conn->prepare("UPDATE Restaurant SET name = ?, location = ? WHERE restaurant_id = ?");
    if ($update_stmt->execute([$name, $location, $restaurant_id])) {
        // Redirect with success message
        header("Location: ../forms/manage-restaurants.php?status=update_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: ../forms/manage-restaurants.php?status=update_error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Restaurant</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="text-center mb-4">Update Restaurant</h2>
            <form method="POST" action="update-restaurant.php?id=<?php echo $restaurant_id; ?>">
                <div class="mb-3">
                    <label for="name" class="form-label">Restaurant Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location:</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($restaurant['location'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary w-100">Update Restaurant</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>