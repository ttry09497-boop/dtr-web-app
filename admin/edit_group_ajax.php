<?php
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $members = $_POST['members'] ?? [];
    $locations = $_POST['locations'] ?? [];

    if($id && $name){
        // Update group name
        $stmt = $pdo->prepare("UPDATE groups SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);

        // ---------- UPDATE MEMBERS ----------
        // Fetch current members
        $stmt = $pdo->prepare("SELECT user_id FROM group_users WHERE group_id=?");
        $stmt->execute([$id]);
        $currentMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove unchecked members
        $removeMembers = array_diff($currentMembers, $members);
        if($removeMembers){
            $in = str_repeat('?,', count($removeMembers)-1) . '?';
            $stmt = $pdo->prepare("DELETE FROM group_users WHERE group_id=? AND user_id IN ($in)");
            $stmt->execute(array_merge([$id], $removeMembers));
        }

        // Add new members
        $addMembers = array_diff($members, $currentMembers);
        $stmtUser = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (?, ?)");
        foreach($addMembers as $m){
            $stmtUser->execute([$id, $m]);
        }

        // ---------- UPDATE LOCATIONS ----------
        // Fetch current locations
        $stmt = $pdo->prepare("SELECT location_id FROM group_locations WHERE group_id=?");
        $stmt->execute([$id]);
        $currentLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove unchecked locations
        $removeLoc = array_diff($currentLocations, $locations);
        if($removeLoc){
            $in = str_repeat('?,', count($removeLoc)-1) . '?';
            $stmt = $pdo->prepare("DELETE FROM group_locations WHERE group_id=? AND location_id IN ($in)");
            $stmt->execute(array_merge([$id], $removeLoc));
        }

        // Add new locations
        $addLoc = array_diff($locations, $currentLocations);
        $stmtLoc = $pdo->prepare("INSERT INTO group_locations (group_id, location_id) VALUES (?, ?)");
        foreach($addLoc as $l){
            $stmtLoc->execute([$id, $l]);
        }

        echo 'success';
    } else {
        echo 'Group name is required.';
    }

} else {
    echo 'Invalid request.';
}
?>
