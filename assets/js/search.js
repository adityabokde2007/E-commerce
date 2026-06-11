/**
 * js/search.js
 * Handles live search suggestions dropdown for the main navigation search bar
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Find the search bar form and input in the header
    const searchForm = document.querySelector('.search-bar');
    if (!searchForm) return;
    
    const searchInput = searchForm.querySelector('input[name="q"]');
    if (!searchInput) return;

    // We need to ensure the search form has relative positioning to hold the absolute dropdown
    searchForm.style.position = 'relative';

    // Create the dropdown container and append it to the form
    const dropdown = document.createElement('div');
    dropdown.className = 'search-suggestions-dropdown';
    dropdown.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border: 1px solid #eee;
        z-index: 1000;
        margin-top: 5px;
        display: none;
        overflow: hidden;
    `;
    searchForm.appendChild(dropdown);

    let debounceTimer;

    // Listen for keystrokes
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(debounceTimer);
        
        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        // Add loading state
        dropdown.innerHTML = '<div style="padding: 15px; text-align: center; color: #888;"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
        dropdown.style.display = 'block';

        // Fetch results via AJAX after a 300ms delay (debounce)
        debounceTimer = setTimeout(() => {
            // Assumes SITE_URL is defined somewhere globally, or we use relative path
            fetch(`actions/search_action.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    dropdown.innerHTML = ''; // Clear loading
                    
                    if (data.length === 0) {
                        dropdown.innerHTML = '<div style="padding: 15px; text-align: center; color: #888;">No matches found</div>';
                        return;
                    }

                    // Build suggestions HTML
                    const ul = document.createElement('ul');
                    ul.style.cssText = 'list-style: none; margin: 0; padding: 0;';

                    data.forEach(item => {
                        const li = document.createElement('li');
                        li.style.cssText = 'border-bottom: 1px solid #eee;';
                        
                        const a = document.createElement('a');
                        a.href = `product.php?id=${item.id}`;
                        a.style.cssText = `
                            display: flex;
                            align-items: center;
                            padding: 10px 15px;
                            text-decoration: none;
                            color: #333;
                            transition: background 0.2s;
                        `;
                        
                        // Hover effect
                        a.onmouseover = () => a.style.background = '#f8f9fa';
                        a.onmouseout = () => a.style.background = 'transparent';

                        a.innerHTML = `
                            <img src="${item.image_url}" alt="${item.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 15px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 500; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">${item.name}</div>
                                <div style="color: #FF6B35; font-weight: 600; font-size: 0.85rem;">${item.price_html}</div>
                            </div>
                        `;
                        
                        li.appendChild(a);
                        ul.appendChild(li);
                    });

                    // Add a "See all results" link at the bottom
                    const seeAllLi = document.createElement('li');
                    seeAllLi.innerHTML = `
                        <a href="search.php?q=${encodeURIComponent(query)}" style="display: block; padding: 10px; text-align: center; background: #f8f9fa; color: #1A1A2E; font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                            See all results <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    `;
                    ul.appendChild(seeAllLi);

                    dropdown.appendChild(ul);
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                    dropdown.style.display = 'none';
                });
        }, 300);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Show dropdown again when input gets focused if it has value
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && dropdown.innerHTML !== '') {
            dropdown.style.display = 'block';
        }
    });
});
