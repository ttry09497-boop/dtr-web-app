<?php
session_start();
require_once '../includes/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'] ?? '';
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$radius = $data['radius'] ?? 50; // default meters

if ($name && $lat && $lng) {
    $stmt = $pdo->prepare("INSERT INTO tagged_locations (name, latitude, longitude, radius) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $lat, $lng, $radius]);


    echo json_encode([
        "status" => "success",
        "id" => $pdo->lastInsertId(),
        "name" => $name,
        "latitude" => $lat,
        "longitude" => $lng,
        "radius" => $radius
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid data"
    ]);
}
