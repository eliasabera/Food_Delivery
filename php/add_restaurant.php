<?php

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $restaurantName = htmlspecialchars($_POST["restaurantName"]);
    $restaurantLocation = htmlspecialchars($_POST["restaurantLocation"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Hash the password

    // Handle image upload
    if (isset($_FILES["restaurantImage"]) && $_FILES["restaurantImage"]["error"] == 0) {
        $targetDir = "../images/"; // Directory to store uploaded images
        $targetFile = $targetDir . basename($_FILES["restaurantImage"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if the file is an actual image
        $check = getimagesize($_FILES["restaurantImage"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (e.g., 5MB max)
            if ($_FILES["restaurantImage"]["size"] <= 5000000) {
                // Allow certain file formats
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    // Move the file to the target directory
                    if (move_uploaded_file($_FILES["restaurantImage"]["tmp_name"], $targetFile)) {
                        $restaurantImage = $targetFile; // Save the file path to the database
                    } else {
                        echo "<div class='alert alert-danger text-center'>Error uploading image.</div>";
                        exit;
                    }
                } else {
                    echo "<div class='alert alert-danger text-center'>Only JPG, JPEG, PNG, and GIF files are allowed.</div>";
                    exit;
                }
            } else {
                echo "<div class='alert alert-danger text-center'>Image file is too large (max 5MB).</div>";
                exit;
            }
        } else {
            echo "<div class='alert alert-danger text-center'>File is not an image.</div>";
            exit;
        }
    } else {
        echo "<div class='alert alert-danger text-center'>No image file uploaded.</div>";
        exit;
    }

    // Insert data into the database
    $sql = "INSERT INTO Restaurant (name, location, image_url, password) VALUES (:name, :location, :image_url, :password)";
    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute([
            ":name" => $restaurantName,
            ":location" => $restaurantLocation,
            ":image_url" => $restaurantImage,
            ":password" => $password
        ]);

        // Success message
        echo "<div class='alert alert-success text-center'>Restaurant added successfully!</div>";
    } catch (PDOException $e) {
        // Error message
        echo "<div class='alert alert-danger text-center'>Error: " . $e->getMessage() . "</div>";
    }
}
?>