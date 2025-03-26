<?php
// Start the session (if needed)
session_start();

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

// Fetch restaurants from the database
$sql = "SELECT restaurant_id, name, location, image_url FROM Restaurant";
$result = $conn->query($sql);

// Check for errors in the query
if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/resturants.css"> <!-- Separate CSS file -->
</head>
<body>
    <header class="text-center p-3">
        <h1>Restaurants</h1>
    </header>

    <div class="container">
        <div class="row">
            <?php
            // Check if there are any restaurants
            if ($result->num_rows > 0) {
                // Loop through each restaurant
                while ($row = $result->fetch_assoc()) {
                    echo '
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <img src="' . htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') . '" class="card-img-top" alt="Restaurant Image">
                            <div class="card-body">
                                <h5 class="card-title">' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</h5>
                                <p class="card-text">Location: ' . htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8') . '</p>
                                <a href="menu.php?id=' . $row['restaurant_id'] . '" class="btn btn-orange">View Menu</a>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p class="text-center">No restaurants found.</p>';
            }
            ?>
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