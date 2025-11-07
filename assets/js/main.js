// Navigation
document.addEventListener('DOMContentLoaded', function() {
    // Handle menu item active state
    const menuItems = document.querySelectorAll('.menu-item');
    const currentUrl = window.location.href;
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        const pageId = item.getAttribute('data-page');
        
        if (pageId && href && currentUrl.includes('page=' + pageId)) {
            item.classList.add('active');
        }
    });

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal();
        }
    });
});

function showPage(pageId) {
    // Hide all pages
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    
    // Show selected page
    const page = document.getElementById(pageId);
    if (page) {
        page.classList.add('active');
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

// Form handling - Generic API call function
async function apiCall(endpoint, method = 'POST', data = null, options = {}) {
    try {
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };
        
        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }
        
        const response = await fetch(endpoint, config);
        const result = await response.json();
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        return {
            success: false,
            message: 'Có lỗi xảy ra khi xử lý yêu cầu'
        };
    }
}

// Form handling - Generic form submit handler
function handleFormSubmit(formId, apiEndpoint, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        const result = await apiCall(apiEndpoint, 'POST', data);
        
        if (result.success) {
            if (successCallback) {
                successCallback(result);
            } else {
                closeModal();
                form.reset();
                location.reload();
            }
        } else {
            const message = result.message || 'Có lỗi xảy ra';
            if (errorCallback) {
                errorCallback(result);
            } else {
                alert('Error: ' + message);
            }
        }
    });
}

// Delete confirmation with API call
async function confirmDelete(message, apiEndpoint, data, successCallback) {
    if (!confirm(message || 'Bạn có chắc chắn muốn xóa?')) {
        return;
    }
    
    const result = await apiCall(apiEndpoint, 'POST', data);
    
    if (result.success) {
        if (successCallback) {
            successCallback(result);
        } else {
            location.reload();
        }
    } else {
        alert('Error: ' + (result.message || 'Có lỗi xảy ra khi xóa'));
    }
}

// Show notification (can be enhanced with toast library)
function showNotification(message, type = 'success') {
    // Simple alert for now, can be replaced with toast notification
    if (type === 'error') {
        alert('Error: ' + message);
    } else {
        // Could show success message
        console.log(message);
    }
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

