<?php
// about.php
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<style>
/* Hero Section: use local 16:9 banner and hide text overlay */
.about-hero { position: relative; text-align: center; color: white; background: url('<?= ASSETS_URL ?>images/banners/about_banner.png') center/cover no-repeat; aspect-ratio: 16/9; height: auto; min-height: 220px; }
.about-hero::before { display: none; }
.about-hero-content { display: none; }

/* Section Spacing */
.about-section { padding: 80px 0; }

/* Our Story & Mission/Vision */
.story-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center; }
.story-image { width: 100%; border-radius: var(--border-radius); box-shadow: var(--shadow-md); object-fit: cover; }
.mission-vision-cards { display: grid; gap: 20px; }
.mv-card { background: var(--bg-white); border: 1px solid var(--border-color); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border-left: 4px solid var(--primary); transition: transform var(--transition-fast); }
.mv-card:hover { transform: translateY(-5px); }
.mv-card i { font-size: 2rem; color: var(--primary); margin-bottom: 15px; }

/* Team Section */
.team-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; margin-top: 28px; }
.team-card { background: var(--bg-white); border: 1px solid var(--border-color); border-radius: var(--border-radius); overflow: hidden; text-align: center; box-shadow: var(--shadow-sm); transition: transform var(--transition-fast); }
.team-card:hover { transform: translateY(-10px); box-shadow: var(--shadow-md); }
.team-img { width: 100%; height: 250px; object-fit: cover; display: block; }
.team-info { padding: 16px 18px 18px; }
.team-info h4 { margin-bottom: 5px; color: var(--secondary); }
.team-info p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0; }

/* Stats Counter Section */
.stats-section { background: var(--secondary); color: white; padding: 60px 0; text-align: center; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; }
.stat-box { padding: 20px; }
.stat-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 15px; }
.stat-number { font-size: 3rem; font-weight: 700; margin-bottom: 5px; display: inline-block; }
.stat-text { font-size: 1.1rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

@media (max-width: 991px) {
    .story-layout { grid-template-columns: 1fr; }
    .team-grid { grid-template-columns: repeat(2, 1fr); }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 576px) {
    .team-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
    .about-hero h1 { font-size: 2.2rem; }
}
</style>

<!-- Hero Section -->
<section class="about-hero" aria-label="About banner"></section>

<!-- Our Story Section -->
<section class="about-section">
    <div class="container">
        <div class="story-layout">
            <div>
                <h2 style="font-size: 2.2rem; margin-bottom: 20px; color: var(--secondary);">Our Story</h2>
                <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 20px;">
                    Founded in 2023, ShopEase began with a simple mission: to make high-quality products accessible to everyone, anywhere. What started as a small local startup has quickly grown into a leading e-commerce platform.
                </p>
                <p style="color: var(--text-muted); line-height: 1.8; margin-bottom: 30px;">
                    We believe that shopping should be an experience, not a chore. That's why we carefully curate our inventory, partnering with the world's best brands and local artisans to bring you a diverse catalog of exceptional items.
                </p>
                
                <div class="mission-vision-cards">
                    <div class="mv-card">
                        <i class="fa-solid fa-bullseye"></i>
                        <h4>Our Mission</h4>
                        <p class="text-muted mb-0">To empower consumers by providing a seamless, secure, and highly personalized shopping experience that exceeds expectations at every turn.</p>
                    </div>
                    <div class="mv-card">
                        <i class="fa-regular fa-eye"></i>
                        <h4>Our Vision</h4>
                        <p class="text-muted mb-0">To become the world's most customer-centric marketplace, where anyone can find and discover anything they might want to buy online.</p>
                    </div>
                </div>
            </div>
            <div>
                <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&q=80&w=800" alt="Our Team Working" class="story-image">
            </div>
        </div>
    </div>
</section>

<!-- Stats Counter Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
                <div class="stat-number" data-target="1000">0</div><span style="font-size: 3rem; font-weight:700;">+</span>
                <div class="stat-text">Premium Products</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-number" data-target="500">0</div><span style="font-size: 3rem; font-weight:700;">+</span>
                <div class="stat-text">Happy Customers</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                <div class="stat-number" data-target="50">0</div><span style="font-size: 3rem; font-weight:700;">+</span>
                <div class="stat-text">Categories</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon"><i class="fa-solid fa-headset"></i></div>
                <div class="stat-number" data-target="24">0</div><span style="font-size: 3rem; font-weight:700;">/7</span>
                <div class="stat-text">Customer Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Our Team Section -->
<section class="about-section bg-light" style="background: var(--bg-light);">
    <div class="container">
        <div class="text-center" style="max-width: 600px; margin: 0 auto;">
            <h2 style="font-size: 2.2rem; margin-bottom: 15px; color: var(--secondary);">Meet Our Leadership</h2>
            <p class="text-muted">The passionate individuals behind ShopEase who work tirelessly to bring you the best experience possible.</p>
        </div>
        
        <div class="team-grid">
            <!-- Team Member 1 -->
            <div class="team-card">
                <img src="<?= ASSETS_URL ?>images/team/ceo.jpg" alt="CEO" class="team-img">
                <div class="team-info">
                    <h4>James Carter</h4>
                    <p>Chief Executive Officer</p>
                </div>
            </div>
            <!-- Team Member 2 -->
            <div class="team-card">
                <img src="<?= ASSETS_URL ?>images/team/cto.jpg" alt="CTO" class="team-img">
                <div class="team-info">
                    <h4>Emily Parker</h4>
                    <p>Chief Technology Officer</p>
                </div>
            </div>
            <!-- Team Member 3 -->
            <div class="team-card">
                <img src="<?= ASSETS_URL ?>images/team/cmo.jpg" alt="CMO" class="team-img">
                <div class="team-info">
                    <h4>Sophia Reed</h4>
                    <p>Head of Marketing</p>
                </div>
            </div>
            <!-- Team Member 4 -->
            <div class="team-card">
                <img src="<?= ASSETS_URL ?>images/team/coo.jpg" alt="COO" class="team-img">
                <div class="team-info">
                    <h4>Daniel Brooks</h4>
                    <p>Head of Operations</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Animated Counter on Scroll using IntersectionObserver
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.stat-number');
    let hasAnimated = false;

    const animateCounters = () => {
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            // Determine increment step based on target size
            const increment = target / 50; 
            
            const updateCount = () => {
                const count = +counter.innerText;
                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 40);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });
    };

    // Use Intersection Observer to detect when stats section is in view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !hasAnimated) {
                animateCounters();
                hasAnimated = true; // Ensure it only animates once
            }
        });
    }, { threshold: 0.5 }); // Trigger when 50% of the element is visible

    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        observer.observe(statsSection);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
