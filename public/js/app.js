// ===== PROFITPULSE – Main JavaScript =====

document.addEventListener('DOMContentLoaded', () => {

    // ===== NAVBAR SCROLL =====
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    // ===== MOBILE NAVBAR TOGGLE =====
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.querySelector('.navbar-menu');
    const navbarActions = document.querySelector('.navbar-actions');
    if (navbarToggle) {
        navbarToggle.addEventListener('click', () => {
            navbarMenu?.classList.toggle('mobile-open');
            navbarActions?.classList.toggle('mobile-open');
        });
    }

    // ===== INTERSECTION OBSERVER – Animate on scroll =====
    const animatedEls = document.querySelectorAll('.animate-on-scroll');
    if (animatedEls.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        animatedEls.forEach(el => observer.observe(el));
    }

    // ===== SMOOTH ANCHOR SCROLL =====
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // ===== AUTO-DISMISS FLASH MESSAGES =====
    document.querySelectorAll('.flash').forEach(flash => {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateX(40px)';
            flash.style.transition = 'all 0.4s ease';
            setTimeout(() => flash.remove(), 400);
        }, 5000);
    });

    // ===== COUNTER ANIMATION =====
    const counters = document.querySelectorAll('[data-counter]');
    if (counters.length > 0) {
        const counterObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseFloat(el.dataset.counter);
                    const duration = 1500;
                    const start = performance.now();

                    const update = (time) => {
                        const elapsed = time - start;
                        const progress = Math.min(elapsed / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        el.textContent = (target * eased).toFixed(target % 1 !== 0 ? 1 : 0);
                        if (progress < 1) requestAnimationFrame(update);
                    };

                    requestAnimationFrame(update);
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => counterObserver.observe(c));
    }

    // ===== VINTED DASHBOARD – Tabs =====
    const tabBtns = document.querySelectorAll('[data-tab]');
    const tabPanels = document.querySelectorAll('[data-tab-panel]');
    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const panel = document.querySelector(`[data-tab-panel="${targetTab}"]`);
                if (panel) panel.classList.add('active');
            });
        });
    }

    // ===== CONFIRM ACTIONS =====
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Êtes-vous sûr ?')) {
                e.preventDefault();
            }
        });
    });

    // ===== COPY TO CLIPBOARD =====
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const text = btn.dataset.copy;
            try {
                await navigator.clipboard.writeText(text);
                const original = btn.textContent;
                btn.textContent = '✓ Copié !';
                btn.style.color = 'var(--color-success)';
                setTimeout(() => {
                    btn.textContent = original;
                    btn.style.color = '';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        });
    });

    // ===== PASSWORD STRENGTH INDICATOR =====
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('password-strength');
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[@$!%*?&]/.test(val)) score++;

            const colors = ['', '#ef4444', '#f59e0b', '#eab308', '#10b981', '#10b981'];
            const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];

            strengthBar.style.width = `${score * 20}%`;
            strengthBar.style.background = colors[score];

            const label = document.getElementById('password-strength-label');
            if (label) {
                label.textContent = labels[score];
                label.style.color = colors[score];
            }
        });
    }

    // ===== SIDEBAR ACTIVE STATE =====
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

});

// ===== MOBILE NAV CSS HELPER =====
const style = document.createElement('style');
style.textContent = `
.navbar-menu.mobile-open,
.navbar-actions.mobile-open {
    display: flex !important;
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    background: rgba(7, 8, 15, 0.98);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    flex-direction: column;
    padding: 1rem 1.5rem;
    gap: 0.75rem;
    z-index: 999;
}
`;
document.head.appendChild(style);
