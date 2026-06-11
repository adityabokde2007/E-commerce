<?php
// profile.php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to view your profile.');
    redirect(SITE_URL . '/login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user addresses
$stmt_addr = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
$stmt_addr->execute([$user_id]);
$addresses = $stmt_addr->fetchAll();

// Active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'personal';

// First letter for avatar
$initial = strtoupper(substr($user['name'], 0, 1));
?>

<style>
/* Profile Page Layout */
.profile-layout { display: flex; gap: 30px; margin-top: 30px; }
.profile-sidebar { width: 280px; flex-shrink: 0; }
.profile-content { flex: 1; }

/* Sidebar Card */
.sidebar-card { background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 30px; text-align: center; position: sticky; top: 100px; }
.avatar-circle { width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #ff8c61); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; margin: 0 auto 15px; }
.sidebar-card h4 { margin-bottom: 5px; }
.sidebar-card .text-muted { font-size: 0.9rem; }
.sidebar-nav { list-style: none; padding: 0; margin-top: 20px; text-align: left; }
.sidebar-nav li { margin-bottom: 0; }
.sidebar-nav li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: var(--text-main); border-radius: var(--border-radius-sm); transition: all var(--transition-fast); font-weight: 500; }
.sidebar-nav li a:hover { background: var(--bg-light); color: var(--primary); }
.sidebar-nav li a.active { background: var(--primary-light); color: var(--primary); font-weight: 600; }
.sidebar-nav li a i { width: 20px; text-align: center; }

/* Content Card */
.profile-box { background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 30px; margin-bottom: 25px; }
.profile-box h3 { margin-bottom: 20px; font-size: 1.3rem; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }

/* Address Cards */
.addr-card { border: 1px solid var(--border-color); padding: 20px; border-radius: var(--border-radius-sm); margin-bottom: 15px; position: relative; transition: border-color var(--transition-fast); }
.addr-card:hover { border-color: var(--primary); }
.addr-actions { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; }
.addr-actions button, .addr-actions a { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 0.9rem; transition: color var(--transition-fast); }
.addr-actions button:hover, .addr-actions a:hover { color: var(--primary); }
.addr-actions .delete-btn:hover { color: var(--danger); }

/* Inline Edit Form */
.addr-edit-form { display: none; margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border-color); }

@media (max-width: 991px) {
    .profile-layout { flex-direction: column; }
    .profile-sidebar { width: 100%; }
    .sidebar-card { position: static; }
}
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="#" class="text-primary">My Profile</a></li>
        </ul>
    </div>
</div>

<div class="container mb-5">
    <div class="profile-layout">
        
        <!-- Left Sidebar -->
        <aside class="profile-sidebar">
            <div class="sidebar-card">
                <div class="avatar-circle"><?= $initial ?></div>
                <h4><?= htmlspecialchars($user['name']) ?></h4>
                <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                <p class="text-muted" style="font-size: 0.8rem; margin-top: 5px;">
                    <i class="fa-regular fa-calendar"></i> Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                </p>
                
                <ul class="sidebar-nav">
                    <li><a href="?tab=personal" class="<?= $active_tab === 'personal' ? 'active' : '' ?>"><i class="fa-regular fa-user"></i> Personal Info</a></li>
                    <li><a href="?tab=password" class="<?= $active_tab === 'password' ? 'active' : '' ?>"><i class="fa-solid fa-lock"></i> Change Password</a></li>
                    <li><a href="?tab=addresses" class="<?= $active_tab === 'addresses' ? 'active' : '' ?>"><i class="fa-solid fa-location-dot"></i> My Addresses</a></li>
                    <li><a href="<?= SITE_URL ?>/orders.php"><i class="fa-solid fa-box"></i> My Orders</a></li>
                </ul>
            </div>
        </aside>

        <!-- Right Content -->
        <div class="profile-content">
            
            <?php
            if (function_exists('displayFlashMessage')) {
                echo displayFlashMessage();
            }
            ?>

            <!-- Tab 1: Personal Info -->
            <?php if ($active_tab === 'personal'): ?>
            <div class="profile-box">
                <h3><i class="fa-regular fa-user text-primary"></i> Personal Information</h3>
                <form action="actions/profile/update_profile.php" method="POST" data-profile-form="info" novalidate>
                    <input type="hidden" name="action" value="update_info">
                    <div class="grid grid-2" style="gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Tab 2: Change Password -->
            <?php if ($active_tab === 'password'): ?>
            <div class="profile-box">
                <h3><i class="fa-solid fa-lock text-primary"></i> Change Password</h3>
                <form action="actions/profile/update_profile.php" method="POST" data-profile-form="password" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="password-field">
                            <input type="password" name="current_password" class="form-control password-input" required>
                            <button type="button" class="toggle-password" data-toggle-password aria-label="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-2" style="gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div class="password-field">
                                <input type="password" name="new_password" class="form-control password-input" required minlength="6">
                                <button type="button" class="toggle-password" data-toggle-password aria-label="Show password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <div class="password-field">
                                <input type="password" name="confirm_password" class="form-control password-input" required minlength="6">
                                <button type="button" class="toggle-password" data-toggle-password aria-label="Show password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="fa-solid fa-shield-halved"></i> Update Password</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Tab 3: My Addresses -->
            <?php if ($active_tab === 'addresses'): ?>
            <div class="profile-box">
                <h3><i class="fa-solid fa-location-dot text-primary"></i> My Addresses</h3>
                
                <?php if (count($addresses) > 0): ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="addr-card" id="addr-<?= $addr['id'] ?>">
                            <div class="addr-actions">
                                <button type="button" onclick="toggleEditAddress(<?= $addr['id'] ?>)" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <form action="actions/profile/update_address.php" method="POST" style="display:inline;" data-profile-form="address" data-confirm="Delete this address?" novalidate>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                    <button type="submit" class="delete-btn" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            </div>
                            <p style="margin-bottom: 5px;"><strong><?= htmlspecialchars($addr['full_name']) ?></strong> &middot; <?= htmlspecialchars($addr['phone']) ?></p>
                            <p class="text-muted" style="margin-bottom: 0;"><?= htmlspecialchars($addr['address_line']) ?>, <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> - <?= htmlspecialchars($addr['pincode']) ?></p>
                            
                            <!-- Inline Edit Form (hidden by default) -->
                            <div class="addr-edit-form" id="edit-form-<?= $addr['id'] ?>">
                                <form action="actions/profile/update_address.php" method="POST" data-profile-form="address" novalidate>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                    <div class="grid grid-2" style="gap: 10px;">
                                        <div class="form-group"><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($addr['full_name']) ?>" placeholder="Name" required></div>
                                        <div class="form-group"><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($addr['phone']) ?>" placeholder="Phone" required></div>
                                    </div>
                                    <div class="form-group mb-2"><input type="text" name="address_line" class="form-control" value="<?= htmlspecialchars($addr['address_line']) ?>" placeholder="Address" required></div>
                                    <div class="grid grid-3" style="gap: 10px;">
                                        <div class="form-group"><input type="text" name="city" class="form-control" value="<?= htmlspecialchars($addr['city']) ?>" placeholder="City" required></div>
                                        <div class="form-group"><input type="text" name="state" class="form-control" value="<?= htmlspecialchars($addr['state']) ?>" placeholder="State" required></div>
                                        <div class="form-group"><input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($addr['pincode']) ?>" placeholder="Pincode" required></div>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Save</button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditAddress(<?= $addr['id'] ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center" style="padding: 20px;">You haven't saved any addresses yet.</p>
                <?php endif; ?>

                <!-- Add New Address -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color);">
                    <button type="button" class="btn btn-outline btn-sm mb-3" onclick="document.getElementById('newAddrForm').style.display = document.getElementById('newAddrForm').style.display === 'none' ? 'block' : 'none'">
                        <i class="fa-solid fa-plus"></i> Add New Address
                    </button>
                    <div id="newAddrForm" style="display: none;">
                        <form action="actions/profile/update_address.php" method="POST" data-profile-form="address" novalidate>
                            <input type="hidden" name="action" value="add">
                            <div class="grid grid-2" style="gap: 10px;">
                                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
                            </div>
                            <div class="form-group mb-2"><label class="form-label">Street Address</label><input type="text" name="address_line" class="form-control" required></div>
                            <div class="grid grid-3" style="gap: 10px;">
                                <div class="form-group"><label class="form-label">City</label><input type="text" name="city" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">State</label><input type="text" name="state" class="form-control" required></div>
                                <div class="form-group"><label class="form-label">Pincode</label><input type="text" name="pincode" class="form-control" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-2"><i class="fa-solid fa-plus"></i> Save Address</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleEditAddress(id) {
    const form = document.getElementById('edit-form-' + id);
    form.style.display = form.style.display === 'block' ? 'none' : 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>
