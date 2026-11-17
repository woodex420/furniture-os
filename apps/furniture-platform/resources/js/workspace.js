// Progressive enhancement for the approved sidebar on narrow screens.
const toggle = document.querySelector('[data-workspace-menu]');
const close = document.querySelector('[data-workspace-close]');
const sidebar = document.getElementById('workspace-navigation');
const backdrop = document.querySelector('[data-workspace-backdrop]');
document.documentElement.classList.remove('no-js');

if (toggle && close && sidebar && backdrop) {
    toggle.hidden = false;
    close.hidden = false;
    const setOpen = (open) => {
        sidebar.classList.toggle('mobile-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        if (open) close.focus();
        else if (window.matchMedia('(max-width: 900px)').matches) toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    close.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false);
    });
    window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
        if (event.matches && toggle.getAttribute('aria-expanded') === 'true') setOpen(false);
    });
}
