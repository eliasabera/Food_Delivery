<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_location'])) {
    $_SESSION['delivery_location'] = $_POST['delivery_location'];
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false]);
?>