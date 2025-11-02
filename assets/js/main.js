// Navigation
document.addEventListener('DOMContentLoaded', function() {
    // Log for debugging
    console.log('JavaScript loaded');
    console.log('Current pages:', document.querySelectorAll('.page').length);
    
    // Handle menu item clicks
    const menuItems = document.querySelectorAll('.menu-item');
    console.log('Menu items found:', menuItems.length);
    
    // For server-side routing, menu items with href will navigate naturally
    // Only handle if there's no href or for special cases
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        const pageId = item.getAttribute('data-page');
        
        // If it has a proper href with index.php, navigation will work server-side
        // Just update active state on page load
        if (pageId) {
            // Highlight active menu item based on current page
            const currentUrl = window.location.href;
            if (href && currentUrl.includes('page=' + pageId)) {
                item.classList.add('active');
            }
        }
    });

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });
    
    // Debug: Show current active page
    const activePage = document.querySelector('.page.active');
    if (activePage) {
        console.log('Active page found:', activePage.id);
    } else {
        console.warn('No active page found!');
    }
});

function showPage(pageId) {
    console.log('Showing page:', pageId);
    
    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    
    // Show selected page
    const page = document.getElementById(pageId);
    if (page) {
        page.classList.add('active');
        console.log('Page shown:', pageId);
    } else {
        console.error('Page not found:', pageId);
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
}

// Form handling
function handleFormSubmit(formId, apiEndpoint, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (successCallback) successCallback(result);
                closeModal();
                form.reset();
                // Reload page data
                loadPageData();
            } else {
                alert('Error: ' + (result.message || 'Có lỗi xảy ra'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xử lý yêu cầu');
        }
    });
}

// Delete confirmation
function confirmDelete(message, deleteCallback) {
    if (confirm(message || 'Bạn có chắc chắn muốn xóa?')) {
        if (deleteCallback) deleteCallback();
    }
}

// Load page data
function loadPageData() {
    const activePage = document.querySelector('.page.active');
    if (!activePage) return;
    
    const pageId = activePage.id;
    // This will be implemented per page
    console.log('Loading data for page:', pageId);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Format currency
function formatCurrency(amount, currency = 'USDT') {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' ' + currency;
}

