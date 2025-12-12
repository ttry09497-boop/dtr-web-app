<?php
require_once '../includes/config.php';

// Get location details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM tagged_locations WHERE id = ?");
    $stmt->execute([$id]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$location) {
        die("Location not found!");
    }
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];

    $stmt = $pdo->prepare("UPDATE tagged_locations SET name=?, latitude=?, longitude=?, radius=? WHERE id=?");
    $stmt->execute([$name, $latitude, $longitude, $radius, $id]);

    header("Location: dashboard.php?success=updated");
    exit;
}
?>