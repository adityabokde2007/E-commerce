<?php
// admin/actions/message_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_id = (int)($_POST['message_id'] ?? 0);
    
    try {
        // Assume is_read column exists on contact_messages
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$msg_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // If 'is_read' doesn't exist, we silently ignore to not break the UI
        echo json_encode(['success' => false]);
    }
}
