<?php
// admin/users.php
require_once 'includes/admin_header.php';

// Fetch users, but avoid selecting passwords. Include orders count.
// Assuming 'role' and 'status' columns exist based on previous prompts context.
$query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at, 
          (SELECT COUNT(id) FROM orders WHERE user_id = u.id) as orders_count
          FROM users u
          ORDER BY u.created_at DESC";

try {
    // Check if status column exists to fetch it. If not, fallback to assuming 'active'
    $status_exists = true;
    try {
        $pdo->query("SELECT status FROM users LIMIT 1");
        $query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at, 
                  (SELECT COUNT(id) FROM orders WHERE user_id = u.id) as orders_count
                  FROM users u ORDER BY u.created_at DESC";
    } catch (PDOException $e) {
        $status_exists = false;
    }
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Manage Users</h2>
</div>

<div class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Registered Date</th>
                <th>Total Orders</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($users) > 0): ?>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><strong>#<?= $u['id'] ?></strong></td>
                    <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($u['name']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <span class="badge" style="background: #f3f4f6; color: #374151;"><?= $u['orders_count'] ?></span>
                    </td>
                    <td>
                        <?php if($u['role'] === 'admin'): ?>
                            <span class="badge" style="background: #e0e7ff; color: #4338ca;">Admin</span>
                        <?php else: ?>
                            <span class="badge" style="background: #f3f4f6; color: #4b5563;">Customer</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                            // Default to active if column missing from schema
                            $status = $status_exists ? ($u['status'] ?? 'active') : 'active';
                        ?>
                        <span class="badge <?= $status === 'active' ? 'badge-delivered' : 'badge-cancelled' ?>" id="status-badge-<?= $u['id'] ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['id'] != $_SESSION['user_id'] && $status_exists): ?>
                            <!-- AJAX Toggle Button -->
                            <?php if($status === 'active'): ?>
                                <button onclick="toggleUserStatus(<?= $u['id'] ?>, 'blocked')" class="btn btn-sm btn-outline" style="color: #ef4444; border-color: #ef4444; min-width: 80px;" id="btn-toggle-<?= $u['id'] ?>">
                                    <i class="fa-solid fa-ban"></i> Block
                                </button>
                            <?php else: ?>
                                <button onclick="toggleUserStatus(<?= $u['id'] ?>, 'active')" class="btn btn-sm btn-outline" style="color: #10b981; border-color: #10b981; min-width: 80px;" id="btn-toggle-<?= $u['id'] ?>">
                                    <i class="fa-solid fa-check"></i> Unblock
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted" style="font-size: 0.85rem;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// AJAX to Toggle Block/Unblock
function toggleUserStatus(userId, newStatus) {
    if (!confirm('Are you sure you want to ' + (newStatus === 'blocked' ? 'block' : 'unblock') + ' this user?')) {
        return;
    }
    
    // Fallback logic using vanilla JS fetch API
    fetch('actions/user_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Simply reload to reflect changes safely
            window.location.reload();
        } else {
            alert(data.error || 'Failed to update user status.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during the request.');
    });
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
