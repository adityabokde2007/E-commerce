/**
 * js/product.js
 * Handles interactions specific to the single product detail page
 */

// Image Gallery Logic
function changeMainImage(element, srcUrl) {
    // 1. Change main image source
    const mainImg = document.getElementById('mainProductImage');
    mainImg.src = srcUrl;
    
    // 2. Remove active class from all thumbnails
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    
    // 3. Add active class to clicked thumbnail
    element.classList.add('active');
}

// Simple Zoom Effect on Main Image Hover
const mainImageContainer = document.getElementById('mainImageContainer');
const mainImage = document.getElementById('mainProductImage');

if (mainImageContainer && mainImage) {
    mainImageContainer.addEventListener('mousemove', function(e) {
        const { left, top, width, height } = this.getBoundingClientRect();
        
        // Calculate X and Y coordinates of cursor relative to container
        const x = (e.clientX - left) / width * 100;
        const y = (e.clientY - top) / height * 100;
        
        // Scale and set transform origin
        mainImage.style.transformOrigin = `${x}% ${y}%`;
        mainImage.style.transform = 'scale(1.5)';
    });

    mainImageContainer.addEventListener('mouseleave', function() {
        mainImage.style.transformOrigin = 'center center';
        mainImage.style.transform = 'scale(1)';
    });
}

// Quantity Selector Logic
function updateQty(change) {
    const input = document.getElementById('qtyInput');
    if(!input) return;
    
    let currentVal = parseInt(input.value);
    let max = parseInt(input.getAttribute('max'));
    
    if (isNaN(currentVal)) currentVal = 1;
    
    let newVal = currentVal + change;
    
    // Enforce limits
    if (newVal < 1) newVal = 1;
    if (newVal > max) {
        newVal = max;
        // Optionally show toast warning here
    }
    
    input.value = newVal;
}

// Ensure manual input obeys limits
const qtyInput = document.getElementById('qtyInput');
if(qtyInput) {
    qtyInput.addEventListener('change', function() {
        let val = parseInt(this.value);
        let max = parseInt(this.getAttribute('max'));
        
        if (isNaN(val) || val < 1) this.value = 1;
        if (val > max) this.value = max;
    });
}

// Tabs Logic
function openTab(tabId) {
    // Remove active class from all panes and buttons
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Add active class to target pane and button
    document.getElementById(tabId).classList.add('active');
    document.getElementById('btn-' + tabId).classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const wishlistButton = document.getElementById('wishlistToggleBtn');

    if (wishlistButton) {
        wishlistButton.addEventListener('click', function() {
            const productId = wishlistButton.getAttribute('data-product-id');
            if (!productId) return;

            const originalHtml = wishlistButton.innerHTML;
            wishlistButton.disabled = true;
            wishlistButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('actions/wishlist/add_wishlist.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                wishlistButton.disabled = false;

                if (data.status === 'success') {
                    if (data.action === 'added') {
                        wishlistButton.innerHTML = '<i class="fa-solid fa-heart"></i>';
                    } else {
                        wishlistButton.innerHTML = '<i class="fa-regular fa-heart"></i>';
                    }

                    updateHeaderWishlistCount(data.wishlist_count);
                    if (typeof window.showToast === 'function') {
                        window.showToast('success', data.message || 'Wishlist updated.');
                    }
                } else {
                    wishlistButton.innerHTML = originalHtml;
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', data.message || 'Unable to update wishlist.');
                    }
                }
            })
            .catch(function() {
                wishlistButton.disabled = false;
                wishlistButton.innerHTML = originalHtml;

                if (typeof window.showToast === 'function') {
                    window.showToast('error', 'Failed to communicate with the server.');
                }
            });
        });
    }
});

function updateHeaderCartCount(count) {
    document.querySelectorAll('.header-icons a[href*="cart.php"] .badge').forEach(function(badge) {
        badge.textContent = count;
    });
}

function updateHeaderWishlistCount(count) {
    document.querySelectorAll('.header-icons a[href*="wishlist.php"] .badge').forEach(function(badge) {
        badge.textContent = count;
    });
}
