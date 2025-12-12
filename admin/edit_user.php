<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST['id'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $position = $_POST['position'];  
    $salary = $_POST['salary']; // FIXED: name should match the input

    if ($id && $username && $phone && $role && $position && $salary) {

        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, phone = ?, role = ?, position = ?, salary = ?
            WHERE id = ?
        ");

        $stmt->execute([$username, $phone, $role, $position, $salary, $id]);

        header("Location: user_management.php?edited=success");
        exit;

    } else {
        header("Location: user_management.php?edited=error");
        exit;
    }

} else {
    header("Location: user_management.php");
    exit;
}
