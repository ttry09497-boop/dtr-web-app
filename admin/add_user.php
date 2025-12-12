<?php
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username  = $_POST['username'];
    $phone     = $_POST['phone'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = $_POST['role'];
    $position  = $_POST['position'];
    $salary    = $_POST['salary'];

    $stmt = $pdo->prepare("
        INSERT INTO users (username, phone, password, role, position, salary)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$username, $phone, $password, $role, $position, $salary]);

    header("Location: user_management.php?success=1");
    exit;
}
