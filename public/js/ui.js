/**
 * Quotation Studio - UI & Motion Interaction Engine
 * Handles Theme, Command Palette, Count-Up Animations, Modals, Toasts & Tooltips
 */

class UIEngine {
    static init() {
        this.initTheme();
        this.initSidebar();
        this.initCommandPalette();
        this.initCountUp();
        this.initModals();
        this.initButtonEffects();
        this.initTableEffects();
    }

    // ==========================================
    // 1. Theme Management (Light / Dark)
    // ==========================================
    static initTheme() {
        const savedTheme = localStorage.getItem('qs_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);

        document.getElementById('themeToggleBtn')?.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('qs_theme', next);
            this.updateThemeIcon(next);
            if (window.showToast) {
                window.showToast(`Switched to ${next} mode`, 'info');
            }
        });
    }

    static updateThemeIcon(theme) {
        const btn = document.getElementById('themeToggleBtn');
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // ==========================================
    // 2. Sidebar Collapsible & Mobile Drawer
    // ==========================================
    static initSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const layout = document.querySelector('.app-layout');
        const collapseBtn = document.getElementById('sidebarCollapseToggle');
        const mobileToggle = document.getElementById('sidebarToggle');

        // Restore collapsed state
        const isCollapsed = localStorage.getItem('qs_sidebar_collapsed') === 'true';
        if (isCollapsed && sidebar && layout) {
            sidebar.classList.add('collapsed');
            layout.classList.add('sidebar-collapsed');
        }

        collapseBtn?.addEventListener('click', () => {
            if (!sidebar || !layout) return;
            const collapsed = sidebar.classList.toggle('collapsed');
            layout.classList.toggle('sidebar-collapsed', collapsed);
            localStorage.setItem('qs_sidebar_collapsed', collapsed ? 'true' : 'false');
        });

        mobileToggle?.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar?.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (sidebar?.classList.contains('open') && !sidebar.contains(e.target) && !mobileToggle?.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ==========================================
    // 3. Command Palette (Cmd / Ctrl + K)
    // ==========================================
    static initCommandPalette() {
        const backdrop = document.getElementById('commandPaletteModal');
        const input = document.getElementById('commandPaletteInput');
        const results = document.getElementById('commandPaletteResults');
        if (!backdrop || !input || !results) return;

        // Open shortcut
        window.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                UIEngine.openCommandPalette();
            }
            if (e.key === 'Escape' && backdrop.classList.contains('active')) {
                UIEngine.closeCommandPalette();
            }
        });

        document.getElementById('openCommandPaletteBtn')?.addEventListener('click', () => {
            UIEngine.openCommandPalette();
        });

        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) UIEngine.closeCommandPalette();
        });

        // Search filtering inside Command Palette
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const items = results.querySelectorAll('.command-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? 'flex' : 'none';
            });
        });
    }

    static openCommandPalette() {
        const backdrop = document.getElementById('commandPaletteModal');
        const input = document.getElementById('commandPaletteInput');
        if (!backdrop) return;
        backdrop.classList.add('active');
        input?.focus();
        if (input) input.value = '';
        const items = backdrop.querySelectorAll('.command-item');
        items.forEach(i => i.style.display = 'flex');
    }

    static closeCommandPalette() {
        const backdrop = document.getElementById('commandPaletteModal');
        if (backdrop) backdrop.classList.remove('active');
    }

    // ==========================================
    // 4. Number Count-Up Animation
    // ==========================================
    static initCountUp() {
        const metricValues = document.querySelectorAll('.count-up-val');
        if (!metricValues.length) return;

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseFloat(el.getAttribute('data-target') || '0');
                    const prefix = el.getAttribute('data-prefix') || '';
                    const suffix = el.getAttribute('data-suffix') || '';
                    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
                    UIEngine.animateValue(el, 0, target, 1200, prefix, suffix, decimals);
                    obs.unobserve(el);
                }
            });
        }, { threshold: 0.1 });

        metricValues.forEach(el => observer.observe(el));
    }

    static animateValue(element, start, end, duration, prefix = '', suffix = '', decimals = 0) {
        if (start === end) {
            element.textContent = prefix + end.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
            return;
        }
        const range = end - start;
        let current = start;
        const increment = range / (duration / 16);
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = prefix + current.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
        }, 16);
    }

    // ==========================================
    // 5. Button Micro-Interactions & Modals
    // ==========================================
    static initButtonEffects() {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Ripple effect
                const ripple = document.createElement('span');
                ripple.className = 'btn-ripple';
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = `${size}px`;
                ripple.style.left = `${e.clientX - rect.left - size/2}px`;
                ripple.style.top = `${e.clientY - rect.top - size/2}px`;
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    }

    static initTableEffects() {
        document.querySelectorAll('.table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.classList.add('row-hover');
            });
            row.addEventListener('mouseleave', () => {
                row.classList.remove('row-hover');
            });
        });
    }

    static initModals() {
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-backdrop.active').forEach(m => m.classList.remove('active'));
            }
        });
    }

    // Custom Sleek Confirm Modal (replaces browser confirm)
    static confirm(title, message, confirmBtnText = 'Confirm', isDanger = false) {
        return new Promise((resolve) => {
            let modal = document.getElementById('customConfirmModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'customConfirmModal';
                modal.className = 'modal-backdrop';
                modal.innerHTML = `
                    <div class="modal-card">
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
                            <div id="confirmIconBox" style="width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 id="confirmTitle" style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;"></h3>
                        </div>
                        <p id="confirmMessage" style="font-size:13.5px; color:var(--text-muted); margin-bottom:20px; line-height:1.5;"></p>
                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button type="button" class="btn btn-secondary btn-sm" id="confirmCancelBtn">Cancel</button>
                            <button type="button" class="btn btn-sm" id="confirmActionBtn"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            const titleEl = modal.querySelector('#confirmTitle');
            const msgEl = modal.querySelector('#confirmMessage');
            const actBtn = modal.querySelector('#confirmActionBtn');
            const cancelBtn = modal.querySelector('#confirmCancelBtn');
            const iconBox = modal.querySelector('#confirmIconBox');

            titleEl.textContent = title;
            msgEl.textContent = message;
            actBtn.textContent = confirmBtnText;

            if (isDanger) {
                actBtn.className = 'btn btn-danger btn-sm';
                iconBox.style.background = 'var(--status-rejected-bg)';
                iconBox.style.color = 'var(--danger)';
            } else {
                actBtn.className = 'btn btn-primary btn-sm';
                iconBox.style.background = 'var(--primary-light)';
                iconBox.style.color = 'var(--primary)';
            }

            modal.classList.add('active');

            const onConfirm = () => {
                cleanup();
                resolve(true);
            };

            const onCancel = () => {
                cleanup();
                resolve(false);
            };

            const cleanup = () => {
                modal.classList.remove('active');
                actBtn.removeEventListener('click', onConfirm);
                cancelBtn.removeEventListener('click', onCancel);
            };

            actBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
        });
    }
}

// Global Toast System with Animated Progress Bar
window.showToast = function(message, type = 'success', duration = 3500) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icon}"></i></div>
        <div class="toast-message">${message}</div>
        <button type="button" class="btn-ghost btn-sm" style="padding:2px 6px; color:var(--text-muted); cursor:pointer;" onclick="this.closest('.toast').remove()">&times;</button>
        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);

    const progressBar = toast.querySelector('.toast-progress');
    progressBar.style.transition = `width ${duration}ms linear`;
    setTimeout(() => { progressBar.style.width = '0%'; }, 20);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(12px) scale(0.96)';
        toast.style.transition = 'all 0.25s cubic-bezier(0.16, 1, 0.3, 1)';
        setTimeout(() => toast.remove(), 250);
    }, duration);
};

// Auto initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    UIEngine.init();
});
