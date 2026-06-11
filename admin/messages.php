<?php
// admin/messages.php
require_once 'includes/admin_header.php';

try {
    // Check if is_read column exists. If it fails, fallback.
    $has_is_read = true;
    try {
        $pdo->query("SELECT is_read FROM contact_messages LIMIT 1");
        $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC");
    } catch (PDOException $e) {
        $has_is_read = false;
        $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    }
    
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; color: #111827;">Contact Form Messages</h2>
</div>

<div class="admin-table-container">
    <table class="admin-table" style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 20%;">Sender</th>
                <th style="width: 25%;">Email</th>
                <th style="width: 35%;">Subject</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($messages) > 0): ?>
                <?php foreach($messages as $msg): ?>
                <?php 
                    $is_unread = $has_is_read && isset($msg['is_read']) && $msg['is_read'] == 0; 
                    $row_bg = $is_unread ? 'background-color: #f8fafc;' : '';
                    $text_weight = $is_unread ? 'font-weight: 700;' : 'font-weight: 500;';
                ?>
                
                <!-- Summary Row (Clickable) -->
                <tr style="cursor: pointer; transition: background 0.2s; <?= $row_bg ?>" 
                    class="msg-row" 
                    data-id="<?= $msg['id'] ?>" 
                    data-unread="<?= $is_unread ? 'true' : 'false' ?>">
                    
                    <td style="<?= $text_weight ?> color: #111827;"><?= htmlspecialchars($msg['name']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="<?= $text_weight ?> color: #3b82f6; text-decoration: none;"><?= htmlspecialchars($msg['email']) ?></a></td>
                    <td style="<?= $text_weight ?> color: #4b5563;">
                        <?= htmlspecialchars($msg['subject']) ?>
                        <i class="fa-solid fa-chevron-down" style="float: right; margin-top: 3px; color: #9ca3af; transition: transform 0.3s;" id="icon-<?= $msg['id'] ?>"></i>
                    </td>
                    <td style="color: #6b7280; font-size: 0.85rem;"><?= date('M d', strtotime($msg['created_at'])) ?></td>
                    <td>
                        <?php if($is_unread): ?>
                            <span class="badge badge-pending" id="badge-<?= $msg['id'] ?>"><i class="fa-solid fa-circle" style="font-size:0.5rem; vertical-align:middle;"></i> New</span>
                        <?php else: ?>
                            <span class="badge" style="background: #e5e7eb; color: #6b7280;"><i class="fa-solid fa-check"></i> Read</span>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- Expanded Detail Row (Hidden by default) -->
                <tr id="body-<?= $msg['id'] ?>" style="display: none; background: #fafafa;">
                    <td colspan="5" style="padding: 25px; border-bottom: 2px solid var(--admin-border);">
                        <div style="background: white; border: 1px solid var(--admin-border); border-radius: 8px; padding: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                            <div style="margin-bottom: 15px; color: #6b7280; font-size: 0.9rem;">
                                <strong>From:</strong> <?= htmlspecialchars($msg['name']) ?> &lt;<?= htmlspecialchars($msg['email']) ?>&gt; <br>
                                <strong>Date:</strong> <?= date('F d, Y h:i A', strtotime($msg['created_at'])) ?>
                            </div>
                            <hr style="border: none; border-top: 1px solid var(--admin-border); margin-bottom: 15px;">
                            <div style="line-height: 1.7; color: #111827; font-size: 1rem; white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                            
                            <div style="margin-top: 20px; text-align: right;">
                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-reply"></i> Reply via Email
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted py-5">No messages found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelectorAll('.msg-row').forEach(row => {
    row.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const isUnread = this.getAttribute('data-unread') === 'true';
        const bodyRow = document.getElementById('body-' + id);
        const icon = document.getElementById('icon-' + id);
        
        // Toggle Accordion
        if (bodyRow.style.display === 'none') {
            bodyRow.style.display = 'table-row';
            icon.style.transform = 'rotate(180deg)';
            
            // Mark as read via AJAX if it's currently unread
            if (isUnread) {
                fetch('actions/message_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message_id=' + id
                }).then(() => {
                    // Visually update the row to 'read' state
                    this.setAttribute('data-unread', 'false');
                    this.style.backgroundColor = 'transparent';
                    
                    const cells = this.querySelectorAll('td');
                    cells[0].style.fontWeight = '500';
                    cells[1].querySelector('a').style.fontWeight = '500';
                    cells[2].style.fontWeight = '500';
                    
                    const badge = document.getElementById('badge-' + id);
                    if(badge) {
                        badge.className = 'badge';
                        badge.style.background = '#e5e7eb';
                        badge.style.color = '#6b7280';
                        badge.innerHTML = '<i class="fa-solid fa-check"></i> Read';
                    }
                });
            }
        } else {
            bodyRow.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
