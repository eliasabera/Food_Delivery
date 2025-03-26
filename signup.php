<?php
// Start the session
session_start();

// Check if there's an error message in the session
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']); // Clear the error message after retrieving it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Signup</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link to your custom CSS file -->
    <link rel="stylesheet" href="css/signup.css">
</head>
<body>
    <div class="signup">
        <h3>Admin Signup</h3>
        <form action="signup_process.php" method="POST" class="form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" placeholder="Enter your username" required />
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required />
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required />
            </div>

            <button type="submit">Sign Up</button>
        </form>

        <p>Already have an account? <a href="php/login.php">Login here</a></p>

        <!-- Display error if available -->
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>