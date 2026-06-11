<?php
// includes/footer.php
?>
    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                
                <!-- Column 1: About -->
                <div class="footer-col about-col">
                    <a href="<?= SITE_URL ?>/index.php" class="logo footer-logo">
                        <img src="<?= ASSETS_URL ?>images/logo.png" alt="<?= SITE_NAME ?>">
                    </a>
                    <p class="footer-text">
                        Your one-stop destination for the best products at unbeatable prices. We deliver quality, speed, and exceptional customer service directly to your doorstep.
                    </p>
                    <div class="social-links">
                        <a href="#" target="_blank"><i class="fa-brands fa-github"></i></a>
                        <a href="#" target="_blank"><i class="fa-brands fa-linkedin"></i></a>
                        <a href="#" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col links-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/shop.php">Shop Products</a></li>
                        <li><a href="<?= SITE_URL ?>/about.php">About Us</a></li>
                        <li><a href="<?= SITE_URL ?>/contact.php">Contact Us</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Column 3: Customer Service -->
                <div class="footer-col links-col">
                    <h3>Customer Service</h3>
                    <ul>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?= SITE_URL ?>/profile.php">My Account</a></li>
                            <li><a href="<?= SITE_URL ?>/orders.php">Order History</a></li>
                            <li><a href="<?= SITE_URL ?>/wishlist.php">Wishlist</a></li>
                        <?php else: ?>
                            <li><a href="<?= SITE_URL ?>/login.php">Login / Register</a></li>
                        <?php endif; ?>
                        <li><a href="<?= SITE_URL ?>/cart.php">Shopping Cart</a></li>
                        <li><a href="#">Track Order</a></li>
                        <li><a href="#">Returns & Refunds</a></li>
                        <li><a href="#">FAQs</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter -->
                <div class="footer-col newsletter-col">
                    <h3>Newsletter Signup</h3>
                    <p>Subscribe to our newsletter and get 10% off your first purchase.</p>
                    <form action="<?= SITE_URL ?>/actions/newsletter.php" method="POST" class="newsletter-form" id="newsletterForm">
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Your Email Address" required>
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </form>
                    <div class="payment-methods mt-3">
                        <img src="<?= ASSETS_URL ?>images/payments.png" alt="Accepted Payment Methods" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Copyright Bar -->
        <div class="copyright-bar">
            <div class="container">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All Rights Reserved. Designed by Kingshuk.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top">
        <i class="fa-solid fa-arrow-up"></i>
    </button>

    <!-- Logout Confirmation Modal -->
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

    <!-- Main JavaScript -->
    <script src="<?= ASSETS_URL ?>js/main.js?v=2.0"></script>
    <script src="<?= ASSETS_URL ?>js/search.js?v=2.0"></script>
    <script src="<?= ASSETS_URL ?>js/cart.js?v=2.0"></script>
</body>
</html>
