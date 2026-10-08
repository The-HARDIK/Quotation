/**
 * Quotation Studio - Global Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle on Mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            appSidebar.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!appSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                appSidebar.classList.remove('open');
            }
        });
    }

    // 2. User Menu Dropdown Toggle
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenuDropdown = document.getElementById('userMenuDropdown');

    if (userMenuBtn && userMenuDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenuDropdown.style.display = userMenuDropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                userMenuDropdown.style.display = 'none';
            }
        });
    }

    // 3. Global Search Input
    const globalSearch = document.getElementById('globalSearchInput');
    if (globalSearch) {
        globalSearch.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && globalSearch.value.trim()) {
                const query = encodeURIComponent(globalSearch.value.trim());
                window.location.href = `${window.BASE_URL || ''}/quotations/index.php?search=${query}`;
            }
        });
    }
});

// Toast Notifications
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    toast.style.cssText = `
        min-width: 280px;
        max-width: 400px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        margin: 0;
        animation: slideIn 0.3s ease;
    `;

    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-triangle' : 'fa-info-circle');
    toast.innerHTML = `
        <div class="alert-icon"><i class="fas ${icon}"></i></div>
        <div class="alert-content">${message}</div>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Copy to Clipboard Utility
function copyToClipboard(text, successMsg = 'Copied to clipboard!') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => showToast(successMsg, 'success'));
    } else {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast(successMsg, 'success');
    }
}
