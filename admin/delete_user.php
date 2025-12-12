<?php
require_once '../includes/config.php';
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $success = $stmt->execute([$_GET['id']]);
  echo json_encode(['success' => $success]);
} else {
  echo json_encode(['success' => false]);
}
?>