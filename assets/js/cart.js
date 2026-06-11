/**
 * js/cart.js
 * Handles AJAX interactions for the Cart system (Adding, Updating, Removing)
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Global Event Listener for "Add to Cart" buttons dynamically
    document.body.addEventListener('click', function(e) {
        
        const btn = e.target.closest('.add-to-cart-btn');
        if (!btn) return;
        
        e.preventDefault();
        
        const productId = btn.getAttribute('data-id');
        if (!productId) return;
        
        // Grab quantity from input if on product.php
        let quantity = 1;
        const qtyInput = document.getElementById('qtyInput');
        if (qtyInput && qtyInput.closest('#addToCartForm')) {
            quantity = parseInt(qtyInput.value) || 1;
        }

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('actions/cart/add_to_cart.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            
            if (data.status === 'success') {
                updateCartBadge(data.cart_count);
                showToast('Success', data.message, 'success');
            } else {
                const loginMessage = 'Please login first to add items to cart.';
                showToast('Notice', data.message || loginMessage, data.status === 'error' ? 'warning' : 'error');
            }
        })
        .catch(error => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            showToast('Notice', 'Please login first to add items to cart.', 'warning');
        });
    });

    // Global Event Listener for wishlist/like buttons
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.wishlist-btn');
        if (!btn) return;

        e.preventDefault();

        const productId = btn.getAttribute('data-id');
        if (!productId) return;

        const icon = btn.querySelector('i');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('actions/wishlist/add_wishlist.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;

            if (data.status === 'success') {
                if (data.action === 'added') {
                    btn.classList.add('wishlist-active');
                    if (icon) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                    }
                } else {
                    btn.classList.remove('wishlist-active');
                    if (icon) {
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                    }
                }

                btn.innerHTML = icon ? icon.outerHTML : originalHtml;

                if (data.wishlist_count !== undefined) {
                    updateWishlistBadge(data.wishlist_count);
                }

                showToast('Success', data.message, 'success');
            } else {
                btn.innerHTML = originalHtml;
                showToast('Notice', data.message, 'warning');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            showToast('Error', 'Please login first to add items to cart.', 'warning');
        });
    });
});

// Increment/Decrement Cart Quantity
function updateCart(cartId, change, maxStock) {
    const input = document.getElementById(`qty-${cartId}`);
    if (!input) return;
    
    let currentQty = parseInt(input.value);
    let newQty = currentQty + change;
    
    if (newQty < 1) return;
    if (newQty > maxStock) {
        showToast('Info', `Only ${maxStock} items available in stock.`, 'warning');
        return;
    }
    
    // Optimistic Update
    input.value = newQty;
    
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', newQty);

    fetch('actions/cart/update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById(`subtotal-${cartId}`).innerText = data.item_subtotal;
            document.getElementById('cartTotal').innerText = data.cart_total;
            updateCartBadge(data.cart_count);
        } else {
            input.value = currentQty;
            showToast('Error', data.message, 'error');
        }
    })
    .catch(error => {
        input.value = currentQty;
        showToast('Error', 'Network error.', 'error');
    });
}

// Remove Item from Cart Table
function removeFromCart(cartId) {
    if (!confirm('Remove this item from your cart?')) return;
    
    const formData = new FormData();
    formData.append('cart_id', cartId);

    fetch('actions/cart/remove_from_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const row = document.getElementById(`cart-row-${cartId}`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    document.getElementById('cartTotal').innerText = data.cart_total;
                    updateCartBadge(data.cart_count);
                    
                    if (data.cart_count == 0) {
                        window.location.reload();
                    }
                }, 300);
            }
            showToast('Success', data.message, 'success');
        } else {
            showToast('Error', data.message, 'error');
        }
    })
    .catch(error => showToast('Error', 'Network error.', 'error'));
}

function updateCartBadge(count) {
    const badges = document.querySelectorAll('.cart-badge, .header-icons .icon-btn[href*="cart.php"] .badge');
    badges.forEach(badge => {
        badge.innerText = count;
        badge.classList.toggle('badge--hidden', count === 0);
    });
}

function updateWishlistBadge(count) {
    document.querySelectorAll('.header-icons a[href*="wishlist.php"] .badge').forEach(badge => {
        badge.innerText = count;
        badge.classList.toggle('badge--hidden', count === 0);
    });
}

function showToast(arg1, arg2, arg3) {
    const knownTypes = ['success', 'error', 'warning', 'info'];
    let title;
    let message;
    let type;

    if (typeof arg3 !== 'undefined') {
        title = arg1 || 'Notice';
        message = arg2 || '';
        type = arg3 || 'success';
    } else if (knownTypes.includes(arg1)) {
        type = arg1;
        title = type.charAt(0).toUpperCase() + type.slice(1);
        message = arg2 || '';
    } else {
        title = arg1 || 'Notice';
        message = arg2 || '';
        type = 'success';
    }

    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
        document.body.appendChild(container);
    }
    
    const bgColors = { success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
    
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.style.cssText = `background: white; border-left: 4px solid ${bgColors[type] || bgColors.info}; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 15px 20px; border-radius: 4px; min-width: 250px; display: flex; align-items: center; justify-content: space-between;`;
    toast.innerHTML = `<div><strong style="display:block; margin-bottom: 5px;">${title}</strong><span style="color: #666; font-size: 0.9rem;">${message}</span></div><button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; color: #999; font-size: 1.2rem;">&times;</button>`;
    
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

window.showToast = showToast;
