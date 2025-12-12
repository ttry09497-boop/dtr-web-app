<?php
require_once '../includes/config.php';

$groupId = $_GET['id'] ?? 0;

// Get group info
$stmtGroup = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
$stmtGroup->execute([$groupId]);
$group = $stmtGroup->fetch(PDO::FETCH_ASSOC);
$groupName = $group['name'] ?? '';

// Get members
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username
    FROM group_users gu
    JOIN users u ON gu.user_id = u.id
    WHERE gu.group_id = ?
");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get locations
$stmt2 = $pdo->prepare("
    SELECT DISTINCT l.id, l.name, l.radius
    FROM group_locations gl
    JOIN tagged_locations l ON gl.location_id = l.id
    WHERE gl.group_id = ?
");
$stmt2->execute([$groupId]);
$locations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Return JSON
echo json_encode([
    'name' => $groupName,    // <-- dito naidagdag
    'members' => $members,
    'locations' => $locations,
]);
