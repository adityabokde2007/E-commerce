/* assets/js/main.js */
document.addEventListener('DOMContentLoaded', () => {
    
    /**
     * 1. Mobile Navbar Toggle (Hamburger Menu)
     */
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', () => {
            mainNav.classList.toggle('active');
            mobileToggle.classList.toggle('is-active');
        });
    }

    /**
     * 2. Hero Banner Auto-Slider with Dots and Arrows
     */
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length > 0) {
        let currentSlide = 0;
        const nextBtn = document.querySelector('.hero-next');
        const prevBtn = document.querySelector('.hero-prev');
        const dots = document.querySelectorAll('.hero-dot');
        
        const goToSlide = (n) => {
            slides[currentSlide].classList.remove('active');
            if(dots.length) dots[currentSlide].classList.remove('active');
            
            currentSlide = (n + slides.length) % slides.length;
            
            slides[currentSlide].classList.add('active');
            if(dots.length) dots[currentSlide].classList.add('active');
        };

        const nextSlide = () => goToSlide(currentSlide + 1);
        const prevSlide = () => goToSlide(currentSlide - 1);

        let slideInterval = setInterval(nextSlide, 6000); // 6s auto-slide

        if (nextBtn) {
            nextBtn.addEventListener('click', () => { 
                nextSlide(); 
                clearInterval(slideInterval); 
                slideInterval = setInterval(nextSlide, 6000); 
            });
        }
        if (prevBtn) {
            prevBtn.addEventListener('click', () => { 
                prevSlide(); 
                clearInterval(slideInterval); 
                slideInterval = setInterval(nextSlide, 6000); 
            });
        }
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                clearInterval(slideInterval); 
                slideInterval = setInterval(nextSlide, 6000);
            });
        });
    }

    /**
     * 3. Scroll Animations (Fade-in elements using IntersectionObserver)
     */
    const fadeElements = document.querySelectorAll('.fade-in-on-scroll');
    if (fadeElements.length > 0) {
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        fadeElements.forEach(el => fadeObserver.observe(el));
    }

    /**
     * 4. Back-to-Top Button
     */
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /**
     * 5. Smooth Scroll for Anchor Links
     */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId === '#!') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    /**
     * 6b. Logout Confirmation Modal
     */
    const logoutModal = document.getElementById('logoutModal');
    const logoutLinks = document.querySelectorAll('[data-logout-link]');
    const logoutConfirmBtn = logoutModal ? logoutModal.querySelector('[data-logout-confirm]') : null;
    const logoutCancelBtns = logoutModal ? logoutModal.querySelectorAll('[data-logout-cancel]') : [];
    let pendingLogoutUrl = null;

    if (logoutModal && logoutLinks.length > 0) {
        const openLogoutModal = (url) => {
            pendingLogoutUrl = url;
            logoutModal.classList.add('show');
            logoutModal.setAttribute('aria-hidden', 'false');
        };

        const closeLogoutModal = () => {
            pendingLogoutUrl = null;
            logoutModal.classList.remove('show');
            logoutModal.setAttribute('aria-hidden', 'true');
        };

        logoutLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                openLogoutModal(link.getAttribute('href'));
            });
        });

        logoutCancelBtns.forEach(btn => btn.addEventListener('click', closeLogoutModal));

        if (logoutConfirmBtn) {
            logoutConfirmBtn.addEventListener('click', () => {
                if (pendingLogoutUrl) {
                    window.location.href = pendingLogoutUrl;
                }
            });
        }

        logoutModal.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                closeLogoutModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && logoutModal.classList.contains('show')) {
                closeLogoutModal();
            }
        });
    }

    /**
     * 6c. Profile Forms AJAX + Password Toggle
     */
    const profileForms = document.querySelectorAll('form[data-profile-form]');
    profileForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const confirmMsg = form.getAttribute('data-confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            // Custom client-side validation for required fields
            let isValid = true;
            const requiredInputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            for (const input of requiredInputs) {
                if ((input.type === 'radio' || input.type === 'checkbox') && !form.querySelector(`input[name="${input.name}"]:checked`)) {
                    isValid = false;
                    break;
                } else if (input.type !== 'radio' && input.type !== 'checkbox' && !input.value.trim()) {
                    isValid = false;
                    break;
                }
            }

            const showProfileToast = (type, title, message) => {
                if (typeof window.showToast === 'function') {
                    window.showToast(title, message, type);
                }
            };

            if (!isValid) {
                showProfileToast('error', 'Error', 'Please fill in all required fields.');
                return;
            }

            const formData = new FormData(form);

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            }



            const actionUrl = form.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async (response) => {
                const rawText = await response.text();
                let data = null;

                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    console.error("Failed to parse JSON response:", rawText);
                    data = {
                        success: false,
                        message: 'Error: ' + (rawText ? rawText.substring(0, 50) + '...' : 'Invalid response from server'),
                        code: 'parse_error'
                    };
                }

                if (data.field) {
                    const fieldInput = form.querySelector(`[name="${data.field}"]`);
                    if (fieldInput) {
                        fieldInput.focus();
                        fieldInput.style.borderColor = '#ef4444';
                        fieldInput.style.backgroundColor = '#fef2f2';
                    }
                }

                const type = data.success ? 'success' : 'error';
                showProfileToast(
                    type,
                    data.success ? 'Success' : 'Error',
                    data.message || (data.success ? 'Saved successfully.' : 'Something went wrong.')
                );

                if (data.success && data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 900);
                }
            })
            .catch(() => {
                showProfileToast('error', 'Error', 'Please try again later.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        });
    });

    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const field = button.closest('.password-field');
            const input = field ? field.querySelector('.password-input') : null;
            const icon = button.querySelector('i');

            if (!input) return;

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            if (icon) {
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            }

            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });

    /**
     * 6. Dropdown Menus (User Profile, Notifications)
     */
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const parent = toggle.parentElement;
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown').forEach(d => {
                if(d !== parent) d.classList.remove('show');
            });
            
            parent.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
    });

    /**
     * 7. Toast Notification System Initialization
     */
    const serverToasts = document.querySelectorAll('.toast-message');
    serverToasts.forEach(toast => {
        // Slide in automatically
        setTimeout(() => {
            toast.classList.add('show');
            // Auto hide after 4.5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300); // Wait for CSS transition
            }, 4500);
        }, 100);

        // Manual close button
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            });
        }
    });

    /**
     * 8. Newsletter Form AJAX Submission
     */
    const newsletterForm = document.getElementById('newsletterForm') || document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = newsletterForm.querySelector('input[type="email"]');
            const submitBtn = newsletterForm.querySelector('button[type="submit"]');
            
            // UI Loading state
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            const formData = new FormData(newsletterForm);

            fetch(newsletterForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Newsletter', data.message || 'Subscribed successfully!', 'success');
                    }
                    emailInput.value = '';
                } else {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Newsletter', data.error || 'Subscription failed.', 'error');
                    }
                }
            })
            .catch(err => {
                if (typeof window.showToast === 'function') {
                    window.showToast('Newsletter', 'Please try again later.', 'error');
                }
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    /**
     * 9. Image Lazy Loading
     */
    const lazyImages = document.querySelectorAll('img[data-src]');
    if (lazyImages.length > 0) {
        const lazyObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.add('loaded'); // Can hook CSS fade-in here
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '0px 0px 50px 0px' }); // Load slightly before coming into view
        
        lazyImages.forEach(img => lazyObserver.observe(img));
    }

    /**
     * 10. Quantity Selector (+/- Buttons) on Product & Cart Pages
     */
    const qtyWrappers = document.querySelectorAll('.quantity-selector');
    qtyWrappers.forEach(wrapper => {
        const input = wrapper.querySelector('input[type="number"]');
        const btnMinus = wrapper.querySelector('.qty-minus');
        const btnPlus = wrapper.querySelector('.qty-plus');

        if (input && btnMinus && btnPlus) {
            btnMinus.addEventListener('click', (e) => {
                e.preventDefault();
                let val = parseInt(input.value) || 1;
                let min = parseInt(input.min) || 1;
                if (val > min) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change')); // Important: triggers cart recalculation logic
                }
            });

            btnPlus.addEventListener('click', (e) => {
                e.preventDefault();
                let val = parseInt(input.value) || 1;
                let max = parseInt(input.max) || 99;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });

});

/**
 * Global Function: Programmatic Toasts
 * Allows frontend AJAX responses (like add-to-cart) to trigger nice animated flash messages.
 */
window.showToast = function(type, message) {
    let container = document.getElementById('toastContainer');
    
    // Create container if missing
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-message toast-${type}`;
    // Basic inline styling for dynamic toasts (relies on main CSS for full formatting)
    toast.style.cssText = `
        min-width: 250px; padding: 15px 20px; border-radius: 6px; color: white; display: flex; align-items: center; gap: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateX(120%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: ${type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6')};
    `;
    
    let icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-circle-exclamation' : 'fa-info-circle');
    
    toast.innerHTML = `
        <div class="toast-icon" style="font-size: 1.2rem;"><i class="fa-solid ${icon}"></i></div>
        <div class="toast-content" style="flex:1; font-weight:500;">${message}</div>
        <button class="toast-close" style="background:transparent; border:none; color:white; cursor:pointer; padding:0; opacity:0.8;"><i class="fa-solid fa-xmark"></i></button>
    `;

    container.appendChild(toast);

    // Slide in
    setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 10);
    
    // Auto slide out
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);

    // Close button click
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 300);
    });
};
