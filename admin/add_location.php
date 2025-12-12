<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];

    $stmt = $pdo->prepare("INSERT INTO tagged_locations (name, latitude, longitude) VALUES (?, ?, ?)");
    $stmt->execute([$name, $lat, $lng]);

    // Return JSON so JS can render it
    echo json_encode([
        "id" => $pdo->lastInsertId(),
        "name" => $name,
        "latitude" => $lat,
        "longitude" => $lng
    ]);
}