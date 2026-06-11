/* assets/js/admin.js */
document.addEventListener('DOMContentLoaded', () => {

    /**
     * 1. Sidebar Toggle (Collapse/Expand)
     */
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const main = document.getElementById('adminMain');
    
    if (sidebarToggleBtn && sidebar && main) {
        sidebarToggleBtn.addEventListener('click', () => {
            // If the sidebar is currently open (default)
            if (sidebar.style.transform === 'translateX(0px)' || sidebar.style.transform === '') {
                sidebar.style.transform = 'translateX(-100%)';
                main.style.marginLeft = '0';
            } else {
                sidebar.style.transform = 'translateX(0px)';
                main.style.marginLeft = '260px';
            }
        });
    }

    /**
     * 2. Confirm Dialog Before Delete Actions
     */
    const deleteForms = document.querySelectorAll('form input[value="delete"]');
    deleteForms.forEach(input => {
        const form = input.closest('form');
        if (form && !form.hasAttribute('onsubmit')) { // Don't override if already has inline onsubmit
            form.addEventListener('submit', (e) => {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone and may affect related data.')) {
                    e.preventDefault();
                }
            });
        }
    });

    /**
     * 3. Global Image Preview on File Input Change
     * Used as a fallback if inline scripts are not present on specific pages.
     */
    const imgInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imgInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Find an image preview container dynamically
            let previewTarget = document.getElementById(this.id.replace('Inp', 'Preview'));
            if (!previewTarget) {
                // Try finding an img sibling
                const parent = this.parentElement;
                previewTarget = parent.querySelector('img');
            }
            
            if (previewTarget && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewTarget.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    /**
     * 4. DataTable-style Search/Filter for Tables
     * Filters table rows dynamically based on cell text content.
     */
    const tableSearchInputs = document.querySelectorAll('.admin-table-search');
    tableSearchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const table = this.closest('.admin-table-container').querySelector('.admin-table');
            if (!table) return;

            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    /**
     * 5. Status Toggle Switches (AJAX Update)
     * Detects checkboxes with class 'ajax-status-toggle' and fires POST requests.
     */
    const statusToggles = document.querySelectorAll('.ajax-status-toggle');
    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type'); // e.g., 'product', 'user', 'category'
            const newStatus = this.checked ? 'active' : 'inactive';
            
            fetch(`actions/${type}_action.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=ajax_toggle_status&id=${id}&status=${newStatus}`
            })
            .then(res => res.json())
            .then(data => {
                if(!data.success) {
                    alert('Failed to update status dynamically.');
                    this.checked = !this.checked; // revert UI
                }
            })
            .catch(err => {
                console.error(err);
                alert('Request failed. Check console.');
                this.checked = !this.checked; // revert UI
            });
        });
    });

    /**
     * 6. Dashboard Chart (Simple Bar Chart using Canvas API)
     * Draws a revenue chart dynamically without external chart libraries.
     */
    const chartCanvas = document.getElementById('adminRevenueChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        // Dummy data representing last 6 months revenue
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const data = [1200, 1900, 1500, 2200, 1800, 3100];
        
        const width = chartCanvas.width;
        const height = chartCanvas.height;
        const padding = 40;
        const maxVal = Math.max(...data) * 1.2; // Add 20% headroom
        const barWidth = (width - padding * 2) / data.length - 20;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);

        // Draw Axes
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, height - padding);
        ctx.lineTo(width - padding, height - padding);
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Draw Bars
        data.forEach((val, i) => {
            const barHeight = (val / maxVal) * (height - padding * 2);
            const x = padding + 20 + i * (barWidth + 20);
            const y = height - padding - barHeight;
            
            // Bar fill
            ctx.fillStyle = '#FF6B35';
            ctx.fillRect(x, y, barWidth, barHeight);
            
            // X-Axis Labels
            ctx.fillStyle = '#6b7280';
            ctx.font = '12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(months[i], x + barWidth / 2, height - padding + 20);
            
            // Tooltip / Value on top of bar
            ctx.fillStyle = '#111827';
            ctx.font = 'bold 11px Inter';
            ctx.fillText('$' + val, x + barWidth / 2, y - 8);
        });
    }

    /**
     * 7. Bulk Select Checkboxes for Products/Orders
     */
    const selectAllCheckbox = document.getElementById('selectAllItems');
    const rowCheckboxes = document.querySelectorAll('.row-select-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = this.checked);
        });
        
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = document.querySelectorAll('.row-select-checkbox:checked').length === rowCheckboxes.length;
                    if (allChecked) selectAllCheckbox.checked = true;
                }
            });
        });
    }

    /**
     * 8. Strict Form Validation for Add/Edit Forms
     * Highlights required fields that are empty.
     */
    const adminForms = document.querySelectorAll('form[enctype="multipart/form-data"], form[action*="action"]');
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#ef4444';
                    input.style.backgroundColor = '#fef2f2';
                    
                    // Reset styling on input
                    input.addEventListener('input', function() {
                        this.style.borderColor = '#e5e7eb';
                        this.style.backgroundColor = '#ffffff';
                    }, { once: true });
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Validation Error: Please fill out all required fields before saving.');
            }
        });
    });

});
