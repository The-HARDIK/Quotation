/**
 * Quotation Studio - Global Application JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. User Menu Dropdown Toggle
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

    // 2. Global Search Input Navigation on Enter
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

// 3. Reliable Copy to Clipboard Utility (Uses modern UI toast)
function copyToClipboard(text, successMsg = 'Copied to clipboard!') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            if (window.showToast) window.showToast(successMsg, 'success');
        }).catch(() => fallbackCopy(text, successMsg));
    } else {
        fallbackCopy(text, successMsg);
    }
}

function fallbackCopy(text, successMsg) {
    const input = document.createElement('input');
    input.value = text;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();
    try {
        document.execCommand('copy');
        if (window.showToast) window.showToast(successMsg, 'success');
    } catch (e) {
        if (window.showToast) window.showToast('Failed to copy', 'error');
    }
    document.body.removeChild(input);
}
