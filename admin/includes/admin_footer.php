<?php
// admin/includes/admin_footer.php
?>
        </div> <!-- End .admin-content -->
    </main> <!-- End .admin-main -->

    <div class="logout-modal" id="logoutModal" aria-hidden="true">
        <div class="logout-modal-backdrop" data-logout-cancel></div>
        <div class="logout-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
            <h3 id="logoutModalTitle">Are you sure you want to logout?</h3>
            <p>You will be signed out from your account.</p>
            <div class="logout-modal-actions">
                <button type="button" class="btn btn-outline" data-logout-cancel>Cancel</button>
                <button type="button" class="btn btn-primary" data-logout-confirm>Yes</button>
            </div>
        </div>
    </div>

    <script>
        // Basic Sidebar Toggle Logic
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');
            
            if (sidebar.style.transform === 'translateX(-100%)') {
                sidebar.style.transform = 'translateX(0)';
                main.style.marginLeft = '260px';
            } else {
                sidebar.style.transform = 'translateX(-100%)';
                main.style.marginLeft = '0';
            }
        });
    </script>
    <script src="<?= ASSETS_URL ?>js/main.js"></script>
</body>
</html>
