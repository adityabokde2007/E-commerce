<?php
// 404.php
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<div class="container text-center" style="padding: 100px 20px; min-height: 60vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <div style="font-size: 8rem; font-weight: 800; color: var(--primary); line-height: 1;">404</div>
    <h1 style="font-size: 2.5rem; margin-bottom: 20px; color: var(--secondary);">Page Not Found</h1>
    <p class="text-muted mb-4" style="font-size: 1.1rem; max-width: 500px;">
        Oops! The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
    </p>
    <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem; border-radius: var(--border-radius-pill);">
        <i class="fa-solid fa-house"></i> Back to Homepage
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
