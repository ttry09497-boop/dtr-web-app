<?php
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];

    // ✅ Update only existing fields (remove updated_at if not in table)
    $stmt = $pdo->prepare("UPDATE tagged_locations 
                           SET name = ?, latitude = ?, longitude = ?, radius = ? 
                           WHERE id = ?");
    $stmt->execute([$name, $latitude, $longitude, $radius, $id]);

    // ✅ Redirect back to dashboard
    header("Location: dashboard.php?success=updated");
    exit;
}
?>
