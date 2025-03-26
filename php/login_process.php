<?php
// Start the session
session_start();

// Include the database connection
require_once 'db.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required.";
        header("Location: login.php");
        exit();
    }

    // Function to verify user credentials
    function verifyUser($conn, $table, $username, $password, $usernameColumn, $idColumn) {
        // Prepare query with dynamic column names
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $usernameColumn = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password if the user exists
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    // Define login tables and their respective username & ID columns
    $roles = [
        "Admin" => [
            "username" => "username", 
            "id" => "admin_id", 
            "redirect" => "../admin.php"
        ],
        "Customer" => [
            "username" => "username", 
            "id" => "customer_id", 
            "redirect" => "../index.php"
        ],
        "Restaurant" => [
            "username" => "name", 
            "id" => "restaurant_id", 
            "redirect" => "../restaurant_admin.php"
        ],
        "DeliveryPerson" => [
            "username" => "username", 
            "id" => "delivery_person_id", 
            "redirect" => "../pages/delivery_panel.php"
        ]
    ];

    // Loop through roles and attempt to verify the user
    $loginSuccessful = false;
    foreach ($roles as $table => $data) {
        $user = verifyUser($conn, $table, $username, $password, $data["username"], $data["id"]);
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user[$data["id"]];
            $_SESSION['role'] = strtolower($table);
            $_SESSION['username'] = htmlspecialchars($username);
            $_SESSION['success'] = "Login successful! Welcome, " . htmlspecialchars($username);

            // Redirect to the appropriate page
            header("Location: " . $data["redirect"]);
            $loginSuccessful = true;
            exit();
        }
    }

    // If no match is found, set an error message and redirect to login page
    if (!$loginSuccessful) {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }
} else {
    // Redirect back to login page if the form is not submitted
    $_SESSION['error'] = "Invalid request.";
    header("Location: login.php");
    exit();
}
?>