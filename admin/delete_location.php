<?php
require_once '../includes/config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("DELETE FROM tagged_locations WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: dashboard.php?success=deleted");
    exit;
}
?>
