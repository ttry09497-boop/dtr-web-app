<?php
session_start();
require_once 'config.php';

// Validate inputs
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
  header('Location: ../index.php?error=missing');
  exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['user_id'] = $user['id'];
  $_SESSION['username'] = $user['username'];
  $_SESSION['role'] = $user['role'];

  
  if ($user['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
  } else {
    header('Location: ../user/user_home.php');
  }
} else {
  header('Location: ../index.php?error=invalid');
}
exit;
