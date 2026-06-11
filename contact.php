<?php
// contact.php
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<style>
/* Contact Page Layout & Styling */
.contact-layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; margin-top: 40px; }
.contact-form-box { background: var(--bg-white); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 40px; }
.contact-form-box h2 { margin-bottom: 10px; font-size: 1.8rem; }
.contact-form-box p { color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; line-height: 1.6; }

.contact-info-cards { display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px; }
.info-card { display: flex; align-items: flex-start; gap: 20px; background: var(--bg-white); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: transform var(--transition-fast), border-color var(--transition-fast); }
.info-card:hover { transform: translateY(-5px); border-color: var(--primary); }
.info-card-icon { width: 55px; height: 55px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.info-card-content h4 { margin-bottom: 5px; font-size: 1.1rem; color: var(--secondary); }
.info-card-content p { color: var(--text-muted); margin-bottom: 0; line-height: 1.6; font-size: 0.95rem; }

.map-container { border-radius: var(--border-radius); overflow: hidden; border: 1px solid var(--border-color); height: 320px; box-shadow: var(--shadow-sm); }
.map-container iframe { width: 100%; height: 100%; border: none; }

@media (max-width: 991px) {
    .contact-layout { grid-template-columns: 1fr; }
}
</style>

<!-- Breadcrumb -->
<div class="bg-light" style="background: var(--bg-light); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <ul class="breadcrumb">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="#" class="text-primary">Contact Us</a></li>
        </ul>
    </div>
</div>

<div class="container mb-5">
    
    <div class="contact-layout">
        
        <!-- Left: Contact Form -->
        <div class="contact-form-box">
            <?php
            // Show flash messages (success / error) set by actions
            if (function_exists('displayFlashMessage')) {
                echo displayFlashMessage();
            }
            ?>
            <h2>Get in Touch</h2>
            <p>Have a question or feedback? We'd love to hear from you. Fill out the form below and our customer support team will get back to you as soon as possible.</p>
            
            <form id="contactForm" action="<?= SITE_URL ?>/actions/contact/send_message.php" method="POST">
                <div class="grid grid-2" style="gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="contactName" class="form-control" placeholder="Enter Your name" value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_name']) : '' ?>" required>
                        <small class="text-danger error-text" id="nameError" style="display:none; margin-top: 5px;"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="contactEmail" class="form-control" placeholder="Enter your email" value="<?= isLoggedIn() ? htmlspecialchars($_SESSION['user_email']) : '' ?>" required>
                        <small class="text-danger error-text" id="emailError" style="display:none; margin-top: 5px;"></small>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" id="contactSubject" class="form-control" placeholder="How can we help you?" required>
                    <small class="text-danger error-text" id="subjectError" style="display:none; margin-top: 5px;"></small>
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea name="message" id="contactMessage" class="form-control" rows="6" placeholder="Write your message here..." required></textarea>
                    <small class="text-danger error-text" id="messageError" style="display:none; margin-top: 5px;"></small>
                </div>
                <button type="submit" class="btn btn-primary btn-block p-3" style="font-size: 1.1rem; font-weight: 600;">
                    <i class="fa-regular fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

        <!-- Right: Info Cards & Map -->
        <div>
            <div class="contact-info-cards">
                <!-- Address Card -->
                <div class="info-card">
                    <div class="info-card-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-card-content">
                        <h4>Our Headquarters</h4>
                        <p>123 Commerce Street<br>
Tech Valley, San Francisco, CA 94107<br>
United States</p>
                    </div>
                </div>
                
                <!-- Email Card -->
                <div class="info-card">
                    <div class="info-card-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="info-card-content">
                        <h4>Email Support</h4>
                        <p>support@shopease.com</p>
                    </div>
                </div>

                <!-- Phone Card -->
                <div class="info-card">
                    <div class="info-card-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="info-card-content">
                        <h4>Call Us</h4>
                        <p>+1 (555) 123-4567<br>Mon-Sat, 9am to 9pm</p>
                    </div>
                </div>
            </div>

            <!-- Google Maps Embed -->
            <!-- Static Map Placeholder -->
<div class="map-container" style="position:relative; background: #e8f0f7; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:12px;">
    <div style="text-align:center;">
        <i class="fa-solid fa-location-dot" style="font-size:2.5rem; color:var(--primary);"></i>
        <h4 style="margin:10px 0 5px; color:var(--secondary);">San Francisco, CA</h4>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">United States</p>
    </div>
    <a href="https://maps.google.com/?q=San+Francisco+CA" target="_blank" class="btn btn-primary" style="font-size:0.9rem; padding:8px 20px;">
        <i class="fa-solid fa-map"></i> View on Google Maps
    </a>
</div>
        </div>

    </div>
</div>

<script>
// Client-side Form Validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Reset visual errors
    document.querySelectorAll('.error-text').forEach(el => el.style.display = 'none');
    
    // Validate Name
    const name = document.getElementById('contactName').value.trim();
    if (name.length < 2) {
        document.getElementById('nameError').innerText = 'Please enter a valid name (at least 2 characters).';
        document.getElementById('nameError').style.display = 'block';
        isValid = false;
    }
    
    // Validate Email using regex
    const email = document.getElementById('contactEmail').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        document.getElementById('emailError').innerText = 'Please enter a valid email address.';
        document.getElementById('emailError').style.display = 'block';
        isValid = false;
    }
    
    // Validate Subject
    const subject = document.getElementById('contactSubject').value.trim();
    if (subject.length < 3) {
        document.getElementById('subjectError').innerText = 'Subject must be at least 3 characters.';
        document.getElementById('subjectError').style.display = 'block';
        isValid = false;
    }
    
    // Validate Message
    const message = document.getElementById('contactMessage').value.trim();
    if (message.length < 10) {
        document.getElementById('messageError').innerText = 'Message must be at least 10 characters long.';
        document.getElementById('messageError').style.display = 'block';
        isValid = false;
    }
    
    // Prevent submission if anything is invalid
    if (!isValid) {
        e.preventDefault();
    }
});
</script>

<script>
// AJAX submit to show toast like add-to-cart
document.getElementById('contactForm').addEventListener('submit', function(e) {
    // If client-side validation blocked earlier, form won't submit
    // We'll intercept the submit to send via AJAX and show toast
    e.preventDefault();

    const form = this;

    // Re-run basic validation to be safe
    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const subject = document.getElementById('contactSubject').value.trim();
    const message = document.getElementById('contactMessage').value.trim();

    if (name.length < 2 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || subject.length < 3 || message.length < 10) {
        // Let the existing validation UI handle errors
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.innerHTML = originalHtml;
        submitBtn.disabled = false;

        if (data.status === 'success') {
            form.reset();
            showToast('Success', data.message, 'success');
        } else {
            showToast('Error', data.message || 'Could not send message.', 'error');
        }
    })
    .catch(err => {
        submitBtn.innerHTML = originalHtml;
        submitBtn.disabled = false;
        showToast('Error', 'Network error. Please try again later.', 'error');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
