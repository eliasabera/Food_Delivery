<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "food_delivery";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$delivery_person = null;
$username = isset($_GET['username']) ? $conn->real_escape_string($_GET['username']) : '';

// Fetch delivery person data
if ($username) {
    $sql = "SELECT username, email, phone_number, address FROM DeliveryPerson WHERE username = '$username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $delivery_person = $result->fetch_assoc();
    } else {
        header("Location: ../php/manage_delivery.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $address = $conn->real_escape_string($_POST['address']);
    
    $sql = "UPDATE DeliveryPerson SET 
            email = '$email', 
            phone_number = '$phone_number', 
            address = '$address' 
            WHERE username = '$username'";
    
    if ($conn->query($sql) === TRUE) {
        $message = "Delivery person updated successfully!";
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Delivery Person</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/update_delivery.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Update Delivery Person</h2>
            </div>
            <div class="card-body">
                <?php if ($delivery_person): ?>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($delivery_person['username']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($delivery_person['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" 
                               value="<?php echo htmlspecialchars($delivery_person['phone_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                               value="<?php echo htmlspecialchars($delivery_person['address']); ?>" required>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="manage_delivery.php" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
                <?php else: ?>
                    <div class="alert alert-warning">Delivery person not found.</div>
                    <a href="../php/manage_delivery.php" class="btn btn-primary">Back to List</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>