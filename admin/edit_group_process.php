<?php
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $members = $_POST['members'] ?? [];
    $locations = $_POST['locations'] ?? [];

    if ($id && $name) {
        // Update group name
        $stmt = $pdo->prepare("UPDATE groups SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);

        // Reset members
        $pdo->prepare("DELETE FROM group_users WHERE group_id=?")->execute([$id]);
        $stmtUser = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (?, ?)");
        foreach ($members as $userId) {
            $stmtUser->execute([$id, $userId]);
        }

        // Reset locations
        $pdo->prepare("DELETE FROM group_locations WHERE group_id=?")->execute([$id]);
        $stmtLoc = $pdo->prepare("INSERT INTO group_locations (group_id, location_id) VALUES (?, ?)");
        foreach ($locations as $locId) {
            $stmtLoc->execute([$id, $locId]);
        }

        header("Location: create_group.php?edited=success");
        exit;
    } else {
        header("Location: create_group.php?edited=error");
        exit;
    }
} else {
    header("Location: create_group.php");
    exit;
}

?>