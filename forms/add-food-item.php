<?php
// Start the session
session_start();

// Check if the user is logged in as an admin or restaurant
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'restaurant')) {
    // Redirect to the login page if not logged in as an admin or restaurant
    header("Location: ../php/login.php");
    exit();
}

// Include the database connection
require_once '../php/db.php';

// Initialize variables for form data and error messages
$restaurant_id = $name = $description = $price = '';
$errors = [];
$success_message = '';

// Fetch all restaurants for the dropdown
try {
    $stmt = $conn->query("SELECT restaurant_id, name FROM Restaurant");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $restaurant_id = htmlspecialchars(trim($_POST['restaurant_id']));
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = htmlspecialchars(trim($_POST['price']));

    // Validate form data
    if (empty($restaurant_id)) {
        $errors['restaurant_id'] = 'Restaurant is required.';
    }
    if (empty($name)) {
        $errors['name'] = 'Food name is required.';
    }
    if (empty($description)) {
        $errors['description'] = 'Description is required.';
    }
    if (empty($price)) {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Price must be a valid positive number.';
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        // Validate file type
        if (!in_array($image['type'], $allowed_types)) {
            $errors['image'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }

        // Validate file size
        if ($image['size'] > $max_size) {
            $errors['image'] = 'File size must be less than 5 MB.';
        }

        // If no errors, process the file
        if (empty($errors)) {
            $upload_dir = '../images/'; // Folder to store uploaded images
            $file_name = uniqid() . '_' . basename($image['name']); // Unique file name
            $file_path = $upload_dir . $file_name;

            // Move the uploaded file to the images folder
            if (move_uploaded_file($image['tmp_name'], $file_path)) {
                // File uploaded successfully
                $image_url = '../images/' . $file_name; // Full URL to the image
            } else {
                $errors['image'] = 'Failed to upload image.';
            }
        }
    } else {
        $errors['image'] = 'Image is required.';
    }

    // If no errors, insert data into the database
    if (empty($errors)) {
        try {
            // Insert the food item into the database
            $stmt = $conn->prepare("
                INSERT INTO FoodItem (name, description, price, image_url, restaurant_id)
                VALUES (:name, :description, :price, :image_url, :restaurant_id)
            ");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':image_url' => $image_url,
                ':restaurant_id' => $restaurant_id,
            ]);

            // Set success message
            $success_message = 'Food item added successfully!';
        } catch (PDOException $e) {
            // Handle database errors
            $errors['database'] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Food Item</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/form.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Add Food Item</h2>

        <!-- Display success message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="add-food-form" method="POST" action="add-food-item.php" enctype="multipart/form-data">
            <!-- Restaurant dropdown (only shown for admins) -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="mb-3">
                    <label for="restaurant_id" class="form-label">Select Restaurant:</label>
                    <select id="restaurant_id" name="restaurant_id" class="form-select" required>
                        <option value="">Choose a restaurant</option>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <option value="<?php echo $restaurant['restaurant_id']; ?>" <?php echo ($restaurant_id == $restaurant['restaurant_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- For restaurants, auto-select their own ID -->
                <input type="hidden" name="restaurant_id" value="<?php echo $_SESSION['user_id']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Food Name:</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo $name; ?>" required placeholder="Enter food name">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea id="description" name="description" class="form-control" required placeholder="Enter food description"><?php echo $description; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price (in Birr):</label>
                <input type="number" id="price" name="price" class="form-control" value="<?php echo $price; ?>" required placeholder="Enter price">
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Food Image:</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                <small class="form-text text-muted">
                    Upload an image file (jpg, jpeg, png, gif).
                </small>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">Add Food Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>