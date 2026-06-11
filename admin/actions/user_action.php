<?php
// admin/actions/user_action.php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    
    // Prevent an admin from blocking themselves
    if ($user_id === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot block your own account.']);
        exit;
    }
    
    if (in_array($status, ['active', 'blocked'])) {
        try {
            // Attempt to update the status column.
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            // If `status` column does not exist in schema, silently ignore or return false
            echo json_encode(['success' => false, 'error' => 'Database error. Schema may lack status column.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid status.']);
    }
}
