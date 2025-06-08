<?php
session_start();

// Check for messages
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

// Clear messages after retrieving them
unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SignUp</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/signup.css" />
</head>
<body>
    <div class="signup">
        <h3>Register</h3>
        <form class="form" action="register.php" method="POST">
            <!-- Your existing form fields here -->
            <div class="form-group">
                <label for="username">Username:</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    pattern="^[a-zA-Z0-9]{3,15}$"
                    title="Username must be 3 to 15 characters long and contain only letters and numbers."
                    placeholder="Enter your username"
                    required
                />
            </div>

            <div class="form-group">
          <label for="emailid">Email ID:</label>
          <input
            type="email"
            name="emailid"
            id="emailid"
            pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
            title="Please enter a valid email address."
            placeholder="Enter your email address"
            required
          />
        </div>

        <div class="form-group">
          <label for="phonenumber">Phone number:</label>
          <input
            type="text"
            name="phonenumber"
            id="phonenumber"
            pattern="^(09\d{8}|\+251\d{9})$"
            title="Phone number must start with 09 or +251 and be followed by 8 digits."
            placeholder="Enter your phone number"
            required
          />
        </div>

        <div class="form-group">
          <label for="address">Address:</label>
          <input
            type="text"
            name="address"
            id="address"
            pattern="^[a-zA-Z0-9\s,.'-]{3,}$"
            title="Please enter a valid street address or location."
            placeholder="Enter your street address or specific location"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input
            type="password"
            name="password"
            id="password"
            pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$"
            title="Password must be at least 6 characters long, including both letters and numbers."
            placeholder="Enter your password"
            required
          />
        </div>


            <button type="submit">Register Now</button>
            <p>Have an account? <a href="login.php">Login</a></p>
        </form>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <!-- Error Toast -->
        <div id="errorToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        
        <!-- Success Toast -->
        <div id="successToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show toasts if there are messages
        document.addEventListener('DOMContentLoaded', function() {
            const errorToastEl = document.getElementById('errorToast');
            const successToastEl = document.getElementById('successToast');
            
            if ("<?php echo $error; ?>") {
                const errorToast = new bootstrap.Toast(errorToastEl);
                errorToast.show();
            }
            
            if ("<?php echo $success; ?>") {
                const successToast = new bootstrap.Toast(successToastEl);
                successToast.show();
            }
        });
    </script>
</body>
</html>