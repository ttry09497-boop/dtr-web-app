<?php
require_once '../includes/config.php';

if (isset($_GET['id'])) {
    $groupId = $_GET['id'];

    // 1. Bago idelete yung group, ibalik ang status ng users sa 'available'
    $stmtUsers = $pdo->prepare("SELECT user_id FROM group_users WHERE group_id = ?");
    $stmtUsers->execute([$groupId]);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    foreach($users as $user){
        $updateStatus = $pdo->prepare("UPDATE users SET status = 'available' WHERE id = ?");
        $updateStatus->execute([$user['user_id']]);
    }

    // 2. Delete users from group_users
    $stmt = $pdo->prepare("DELETE FROM group_users WHERE group_id = ?");
    $stmt->execute([$groupId]);

    // 3. Delete locations assigned to the group
    $stmtLoc = $pdo->prepare("DELETE FROM group_locations WHERE group_id = ?");
    $stmtLoc->execute([$groupId]);

    // 4. Delete the group itself
    $stmtGroup = $pdo->prepare("DELETE FROM groups WHERE id = ?");
    $stmtGroup->execute([$groupId]);

    header("Location: create_group.php?success=Group+deleted+successfully");
    exit;
}
?>
