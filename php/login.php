<?php
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
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/login.css" />
  </head>
  <body>
    <div class="login">
      <h3>Login</h3>
      <form class="form" action="login_process.php" method="POST">
        <div class="form-group">
          <label for="username">Username:</label>
          <input
            type="text"
            name="username"
            id="username"
            placeholder="Enter your username"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input
            type="password"
            name="password"
            id="password"
            placeholder="Enter your password"
            required
          />
        </div>

        <div class="form-group">
          <button type="submit" class="login-btn">Login Now</button>
        </div>
      </form>
     <p>No account? <a href="signup.php">Register</a></p>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
          <strong class="me-auto">Error</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
      // Check if there's an error message
      const errorMessage = "<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>";

      if (errorMessage) {
        // Show the toast
        const errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
        errorToast.show();
      }
    </script>
  </body>
</html>