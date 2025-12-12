<?php
session_start();
require_once 'config.php';

// DEBUG: show environment variables
echo "<pre>";
echo "HOST: "; var_dump($host);
echo "DB: "; var_dump($db);
echo "USER: "; var_dump($user);
echo "PASS: "; var_dump($pass);

// Validate inputs
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

echo "POST DATA: "; var_dump($_POST);

// Check inputs
if (!$username || !$password) {
    echo "Missing username or password";
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// DEBUG: show fetched user
echo "USER FETCHED: "; var_dump($user);
exit;

// Original login logic (disabled for debug)
// if ($user && password_verify($password, $user['password'])) {
//     $_SESSION['user_id'] = $user['id'];
//     $_SESSION['username'] = $user['username'];
//     $_SESSION['role'] = $user['role'];
    
//     if ($user['role'] === 'admin') {
//         header('Location: ../admin/dashboard.php');
//     } else {
//         header('Location: ../user/user_home.php');
//     }
// } else {
//     header('Location: ../index.php?error=invalid');
// }
// exit;
