<?php
// Start the session
session_start();

// Check if the user is logged in and has the appropriate role (e.g., admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not logged in as an admin
    header("Location: login.php");
    exit();
}

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

// Fetch customer details
if (isset($_GET['id'])) {
    $customer_id = $_GET['id'];
    $sql = "SELECT * FROM Customer WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $customer = $result->fetch_assoc();
    } else {
        // Redirect if no customer is found
        header("Location: manage-customers.php");
        exit();
    }
} else {
    // Redirect if no ID is provided
    header("Location: manage-customers.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // Update the customer
    $update_sql = "UPDATE Customer SET username = ?, email = ?, phone_number = ?, address = ? WHERE customer_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssi", $username, $email, $phone_number, $address, $customer_id);

    if ($update_stmt->execute()) {
        // Redirect with success message
        header("Location: manage-customers.php?status=update_success");
        exit();
    } else {
        // Redirect with error message
        header("Location: manage-customers.php?status=update_error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Customer</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="text-center mb-4">Update Customer</h2>
            <form method="POST" action="update-customer.php?id=<?php echo $customer_id; ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($customer['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($customer['phone_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address:</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($customer['address'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary w-100">Update Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>