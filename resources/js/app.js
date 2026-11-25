import './bootstrap';

// Register Service Worker for PWA experience
if ('serviceWorker' in navigator && (window.location.protocol === 'https:' || window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((reg) => {
            console.log('TG Partner PWA Service Worker registered successfully:', reg.scope);
        }).catch((err) => {
            console.warn('TG Partner PWA Service Worker registration skipped or failed:', err);
        });
    });
}

// Global PWA Install Prompt Listener
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const installButtons = document.querySelectorAll('.pwa-install-trigger');
    installButtons.forEach(btn => {
        btn.classList.remove('hidden');
        btn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to install prompt: ${outcome}`);
                deferredPrompt = null;
                btn.classList.add('hidden');
            }
        });
    });
});

window.addEventListener('appinstalled', () => {
    console.log('TG Partner PWA installed successfully.');
    deferredPrompt = null;
    document.querySelectorAll('.pwa-install-trigger').forEach(btn => btn.classList.add('hidden'));
});

// Universal Password Show/Hide Toggle Function & Delegated Event Listener
window.togglePasswordVisibility = function (target, btn) {
    let input = null;
    if (typeof target === 'string') {
        input = document.getElementById(target) || document.querySelector(`input[name="${target}"]`);
    } else if (target instanceof HTMLElement) {
        input = target;
    }

    if (!input && btn) {
        input = btn.closest('.relative')?.querySelector('input');
    }

    if (!input) return;

    const toggleBtn = btn || (typeof event !== 'undefined' && event ? event.currentTarget : null);
    const eyeIcon = toggleBtn ? toggleBtn.querySelector('.eye-icon') : null;
    const eyeOffIcon = toggleBtn ? toggleBtn.querySelector('.eye-off-icon') : null;

    if (input.type === 'password') {
        input.type = 'text';
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', 'Hide password');
            toggleBtn.setAttribute('aria-pressed', 'true');
        }
        if (eyeIcon) eyeIcon.classList.add('hidden');
        if (eyeOffIcon) eyeOffIcon.classList.remove('hidden');
    } else {
        input.type = 'password';
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-label', 'Show password');
            toggleBtn.setAttribute('aria-pressed', 'false');
        }
        if (eyeIcon) eyeIcon.classList.remove('hidden');
        if (eyeOffIcon) eyeOffIcon.classList.add('hidden');
    }

    // Keep input focus and place caret at end of current value
    try {
        const len = input.value.length;
        input.focus();
        input.setSelectionRange(len, len);
    } catch (e) {
        // Fallback if setSelectionRange is unsupported
    }
};

// Global click delegation for [data-password-toggle] buttons
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;
    // Avoid double toggle if button has an inline onclick
    if (btn.hasAttribute('onclick')) return;
    e.preventDefault();
    const targetId = btn.getAttribute('data-password-toggle');
    window.togglePasswordVisibility(targetId, btn);
});

