<?php
require_once '../includes/config.php';

$groupId = $_GET['id'] ?? 0;

// Get current group info
$stmtGroup = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
$stmtGroup->execute([$groupId]);
$group = $stmtGroup->fetch(PDO::FETCH_ASSOC);
$groupName = $group['name'] ?? '';

// Get all users
$allUsersStmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get users already assigned to this group
$stmtCurrent = $pdo->prepare("SELECT user_id FROM group_users WHERE group_id = ?");
$stmtCurrent->execute([$groupId]);
$currentUsers = $stmtCurrent->fetchAll(PDO::FETCH_COLUMN);

// Get users assigned in other groups (to disable)
$stmtOther = $pdo->prepare("
    SELECT user_id 
    FROM group_users 
    WHERE group_id != ?
");
$stmtOther->execute([$groupId]);
$otherUsers = $stmtOther->fetchAll(PDO::FETCH_COLUMN);

// Get locations for this group
$stmtLoc = $pdo->prepare("
    SELECT location_id FROM group_locations WHERE group_id = ?
");
$stmtLoc->execute([$groupId]);
$selectedLocations = $stmtLoc->fetchAll(PDO::FETCH_COLUMN);

// Get all locations
$stmtAllLoc = $pdo->query("SELECT id, name, radius FROM tagged_locations ORDER BY name ASC");
$allLocations = $stmtAllLoc->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for AJAX
echo json_encode([
    'id' => $groupId,
    'name' => $groupName,
    'allUsers' => $allUsers,
    'selectedMembers' => $currentUsers,
    'assignedMembers' => $otherUsers, // used to disable checkboxes
    'allLocations' => $allLocations,
    'selectedLocations' => $selectedLocations
]);
