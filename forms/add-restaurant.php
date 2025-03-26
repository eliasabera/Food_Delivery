<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Restaurant</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link to external CSS file (for custom styles) -->
    <link rel="stylesheet" href="../css/form.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Add Restaurant</h2>
        <form id="restaurantForm" method="POST" action="../php/add_restaurant.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="restaurantName" class="form-label">Restaurant Name:</label>
                <input type="text" id="restaurantName" name="restaurantName" class="form-control" required placeholder="Enter restaurant name">
            </div>

            <div class="mb-3">
                <label for="restaurantLocation" class="form-label">Location:</label>
                <input type="text" id="restaurantLocation" name="restaurantLocation" class="form-control" required placeholder="Enter location">
            </div>

            <div class="mb-3">
                <label for="restaurantImage" class="form-label">Restaurant Image:</label>
                <input type="file" id="restaurantImage" name="restaurantImage" class="form-control" required accept="image/*">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>