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

// The frame renders the actual public Reimagin homepage. Messages only affect its
// temporary visual state; the existing authenticated form is the only writer.
const homeCanvas = document.querySelector('[data-home-canvas]');
if (homeCanvas) {
    const frame = homeCanvas.querySelector('[data-canvas-frame]');
    const status = homeCanvas.querySelector('[data-canvas-status]');
    const title = document.getElementById('hero-title');
    const subtitle = document.getElementById('hero-subtitle');
    const order = document.getElementById('hero-order');
    try {
        const storefront = new URL(homeCanvas.dataset.storefrontOrigin);
        if (!frame || !status || !title || !subtitle || !order || !['http:', 'https:'].includes(storefront.protocol) || storefront.origin === window.location.origin) throw new Error('Canvas must use a separate origin');
        const targetOrigin = storefront.origin;
        const controls = homeCanvas.querySelector('[data-canvas-controls]');
        if (controls) {
            const buttons = [...controls.querySelectorAll('button[data-canvas-width]')];
            for (const button of buttons) {
                button.addEventListener('click', () => {
                    const width = button.dataset.canvasWidth;
                    if (!['fit', '375', '768', '1280'].includes(width)) return;
                    frame.style.width = width === 'fit' ? '100%' : `${width}px`;
                    for (const item of buttons) item.setAttribute('aria-pressed', String(item === button));
                });
            }
            controls.hidden = false;
        }
        storefront.searchParams.set('furniture-canvas', 'home');
        storefront.searchParams.set('parent-origin', window.location.origin);
        let connected = false;
        let attempt = 0;
        const waitForCanvas = () => {
            connected = false;
            const thisAttempt = ++attempt;
            status.textContent = 'Loading storefront canvas…';
            window.setTimeout(() => {
                if (!connected && thisAttempt === attempt) status.textContent = 'Canvas unavailable or blocked. You can still save using the fields below.';
            }, 12000);
        };
        const sendValues = () => {
            frame.contentWindow?.postMessage({ type: 'furniture-home-canvas-values', hero_title: title.value, hero_subtitle: subtitle.value, hero_order: order.value }, targetOrigin);
        };
        window.addEventListener('message', (event) => {
            if (event.source !== frame.contentWindow || event.origin !== targetOrigin || !event.data || typeof event.data !== 'object') return;
            if (event.data.type === 'furniture-home-canvas-ready') {
                connected = true;
                status.textContent = 'Canvas connected · Changes here are not saved until you update the storefront.';
                sendValues();
            } else if (event.data.type === 'furniture-home-canvas-select') {
                // Toolbar selection stays in the frame so keyboard users can reach move controls.
                if (event.data.focus_input !== undefined && typeof event.data.focus_input !== 'boolean') return;
                if (event.data.focus_input !== false && event.data.field === 'hero_title') title.focus();
                if (event.data.focus_input !== false && event.data.field === 'hero_subtitle') subtitle.focus();
            } else if (event.data.type === 'furniture-home-canvas-move') {
                const fields = order.value === 'title_first' ? ['hero_title', 'hero_subtitle'] : order.value === 'subtitle_first' ? ['hero_subtitle', 'hero_title'] : [];
                const index = fields.indexOf(event.data.field);
                const next = index + (event.data.direction === 'up' ? -1 : event.data.direction === 'down' ? 1 : 0);
                if (index < 0 || next < 0 || next >= fields.length || next === index) return;
                [fields[index], fields[next]] = [fields[next], fields[index]];
                order.value = fields[0] === 'hero_title' ? 'title_first' : 'subtitle_first';
                order.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        for (const input of [title, subtitle]) input.addEventListener('input', sendValues);
        order.addEventListener('change', sendValues);
        frame.addEventListener('load', () => {
            waitForCanvas();
            frame.contentWindow?.postMessage({ type: 'furniture-home-canvas-ping' }, targetOrigin);
        });
        waitForCanvas();
        frame.src = storefront.href;
    } catch {
        if (status) status.textContent = 'Canvas configuration is invalid. You can still use the fields below.';
    }
}
