<?php
session_start();

$host = "localhost"; 
$dbname = "food_delivery"; 
$username = "root";
$password = ""; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $restaurantName = htmlspecialchars($_POST["restaurantName"]);
    $restaurantLocation = htmlspecialchars($_POST["restaurantLocation"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Handle image upload
    if (isset($_FILES["restaurantImage"]) && $_FILES["restaurantImage"]["error"] == 0) {
        $targetDir = "../../images/restaurant/"; // Changed to correct relative path
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate unique filename
        $imageExtension = strtolower(pathinfo($_FILES["restaurantImage"]["name"], PATHINFO_EXTENSION));
        $imageName = uniqid() . '.' . $imageExtension;
        $targetFile = $targetDir . $imageName;
        
        // Validate image
        $validExtensions = ["jpg", "jpeg", "png", "gif"];
        $check = getimagesize($_FILES["restaurantImage"]["tmp_name"]);
        
        if ($check === false) {
            $error_message = "File is not an image.";
        } elseif (!in_array($imageExtension, $validExtensions)) {
            $error_message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES["restaurantImage"]["size"] > 5000000) {
            $error_message = "Image file is too large (max 5MB).";
        } else {
            // Move uploaded file
            if (move_uploaded_file($_FILES["restaurantImage"]["tmp_name"], $targetFile)) {
                // Store relative path in database
                $restaurantImage = "../../images/restaurant/" . $imageName;
            } else {
                $error_message = "Error uploading image file.";
            }
        }
    } else {
        $error_message = "Please select an image file to upload.";
    }

    // Only proceed with database insertion if no errors
    if (empty($error_message)) {
        try {
            $sql = "INSERT INTO Restaurant (name, location, image_url, password) VALUES (:name, :location, :image_url, :password)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ":name" => $restaurantName,
                ":location" => $restaurantLocation,
                ":image_url" => $restaurantImage,
                ":password" => $password
            ]);

            $success_message = "Restaurant added successfully!";
            // Clear form fields after successful submission
            $_POST = array();
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            // Delete the uploaded file if database insertion failed
            if (isset($targetFile) && file_exists($targetFile)) {
                unlink($targetFile);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #fdf1db;
            background-image: url("https://www.transparenttextures.com/patterns/wood-pattern.png");
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .btn-orange {
            background-color: #ff6600;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn-orange:hover {
            background-color: #e65c00;
            color: white;
        }
        .form-title {
            color: #6d4b34;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center form-title">Add New Restaurant</h2>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success text-center"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="restaurantName" class="form-label">Restaurant Name</label>
                    <input type="text" class="form-control" id="restaurantName" name="restaurantName" 
                           value="<?php echo isset($_POST['restaurantName']) ? htmlspecialchars($_POST['restaurantName']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="restaurantLocation" class="form-label">Location</label>
                    <input type="text" class="form-control" id="restaurantLocation" name="restaurantLocation" 
                           value="<?php echo isset($_POST['restaurantLocation']) ? htmlspecialchars($_POST['restaurantLocation']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="restaurantImage" class="form-label">Restaurant Image</label>
                    <input type="file" class="form-control" id="restaurantImage" name="restaurantImage" accept="image/*" required>
                    <div class="form-text">Upload a high-quality image (JPG, PNG, or GIF, max 5MB)</div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-orange px-4 py-2">Add Restaurant</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>