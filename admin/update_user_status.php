<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($userId && in_array($action, ['assign','unassign'])) {
        $status = $action === 'assign' ? 'assigned' : 'available';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $userId])) {
            echo json_encode(['status'=>'success', 'newStatus'=>$status]);
        } else {
            echo json_encode(['status'=>'error']);
        }
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Invalid data']);
    }
}
?>
