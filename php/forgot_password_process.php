<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['error'] = "Email is required.";
        header("Location: forgot_password.php");
        exit();
    }

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expires in 1 hour

        // Store the token in the database
        $stmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE email = :email");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':expires', $expires, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // Send the password reset email
        $resetLink = "http://yourwebsite.com/reset_password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n\n$resetLink";
        $headers = "From: no-reply@yourwebsite.com";

        if (mail($email, $subject, $message, $headers)) {
            $_SESSION['success'] = "A password reset link has been sent to your email.";
        } else {
            $_SESSION['error'] = "Failed to send the password reset email.";
        }
    } else {
        $_SESSION['error'] = "No account found with that email.";
    }

    header("Location: forgot_password.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: forgot_password.php");
    exit();
}