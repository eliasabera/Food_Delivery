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

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['username'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $sql = "DELETE FROM DeliveryPerson WHERE username = '$username'";
    if ($conn->query($sql) === TRUE) {
        $message = "Delivery person deleted successfully";
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}

// Fetch delivery persons from the database
$sql = "SELECT username, email, phone_number, address FROM DeliveryPerson";
$result = $conn->query($sql);

$delivery_persons = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $delivery_persons[] = $row;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Delivery Persons</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/manage.css">
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
            <div class="card-header bg-[#ff6600] text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Manage Delivery Persons</h2>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($delivery_persons)): ?>
                                <?php foreach ($delivery_persons as $person): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($person['username']); ?></td>
                                        <td><?php echo htmlspecialchars($person['email']); ?></td>
                                        <td><?php echo htmlspecialchars($person['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($person['address']); ?></td>
                                        <td>
                                            <a href="../php/update_delivery.php?username=<?php echo urlencode($person['username']); ?>" 
                                               class="btn btn-sm btn-warning btn-action">Update</a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($person['username']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this delivery person?')">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No delivery persons found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>