<?php
session_start();

// Load database config
require_once 'config.php';

// ---------- FORCE CREATE ADMIN ACCOUNT ----------
$adminUsername = 'admin';
$adminPassword = 'admin123'; // password you want
$adminRole = 'admin';

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$adminUsername]);
$admin = $stmt->fetch();

if (!$admin) {
    // Generate hash for the password
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

    // Insert admin into database
    $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $insert->execute([$adminUsername, $hash, $adminRole]);
}

// ---------- LOGIN LOGIC ----------
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    die("Error: Missing username or password.");
}

// Get user from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    die("Error: Username '$username' not found in database.");
}

if (!password_verify($password, $user['password'])) {
    die("Error: Password for username '$username' is incorrect.");
}

// Login successful
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../user/user_home.php');
}
exit;
