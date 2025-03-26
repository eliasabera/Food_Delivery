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

// Fetch all users/customers from the database
$stmt = $conn->query("SELECT * FROM Customer");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display status messages
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'update_success') {
        echo '<div class="alert alert-success">Customer updated successfully!</div>';
    } elseif ($_GET['status'] === 'update_error') {
        echo '<div class="alert alert-danger">Failed to update customer. Please try again.</div>';
    } elseif ($_GET['status'] === 'delete_success') {
        echo '<div class="alert alert-success">Customer deleted successfully!</div>';
    } elseif ($_GET['status'] === 'delete_error') {
        echo '<div class="alert alert-danger">Failed to delete customer. Please try again.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users/Customers</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/manage.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <!-- Manage Users/Customers Table -->
        <section class="user-management">
            <div class="card shadow">
                <div class="card-header">
                    <h2>Manage Users/Customers</h2>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone Number</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="user-list">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($user['address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a href="update-customer.php?id=<?php echo $user['customer_id']; ?>" class="btn btn-sm btn-warning">Update</a>
                                            <a href="delete-customer.php?id=<?php echo $user['customer_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Remove</a>
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
    <script src="../js/admin.js"></script>
</body>
</html>