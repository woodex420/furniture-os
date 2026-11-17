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
    const ctaLabel = document.getElementById('hero-cta-label');
    const ctaPath = document.getElementById('hero-cta-path');
    const image = document.getElementById('hero-image');
    const media = document.getElementById('hero-media');
    const tagline = document.getElementById('home-tagline');
    try {
        const storefront = new URL(homeCanvas.dataset.storefrontOrigin);
        if (!frame || !status || !title || !subtitle || !order || !ctaLabel || !ctaPath || !image || !['http:', 'https:'].includes(storefront.protocol) || storefront.origin === window.location.origin) throw new Error('Canvas must use a separate origin');
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
        let taglineReady = false;
        let taglineTimedOut = false;
        let attempt = 0;
        const connectedMessage = () => tagline && taglineTimedOut && !taglineReady
            ? 'Canvas connected, but the tagline preview is unavailable. You can still save using the fields below.'
            : 'Canvas connected · Changes here are not saved until you update the storefront.';
        const waitForCanvas = () => {
            connected = false;
            taglineReady = false;
            taglineTimedOut = false;
            const thisAttempt = ++attempt;
            status.textContent = 'Loading storefront canvas…';
            window.setTimeout(() => {
                if (thisAttempt !== attempt) return;
                taglineTimedOut = true;
                if (!connected) status.textContent = 'Canvas unavailable or blocked. You can still save using the fields below.';
                else if (tagline && !taglineReady) status.textContent = connectedMessage();
            }, 12000);
        };
        const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
        let imageRequest = 0;
        let imageAbort = null;
        const sendValues = () => {
            const values = { type: 'furniture-home-canvas-values', hero_title: title.value, hero_subtitle: subtitle.value, hero_order: order.value, hero_cta_label: ctaLabel.value, hero_cta_path: ctaPath.value, hero_image: image.value };
            if (media) values.hero_media_id = uuid.test(media.value) ? media.value : null;
            if (tagline) values.home_tagline = tagline.value;
            frame.contentWindow?.postMessage(values, targetOrigin);
        };
        const sendPrivateImage = async () => {
            const sequence = ++imageRequest;
            imageAbort?.abort();
            if (!media || !uuid.test(media.value)) {
                if (connected) status.textContent = connectedMessage();
                return;
            }
            const id = media.value;
            imageAbort = new AbortController();
            try {
                // The admin fetches with its own session. Only the approved frame receives
                // the bytes; no private URL, cookie or token is sent to the storefront.
                const response = await fetch(`/admin/website/media/${id}/file`, { credentials: 'same-origin', cache: 'no-store', signal: imageAbort.signal });
                const mime = response.headers.get('content-type')?.split(';')[0];
                if (!response.ok || !['image/png', 'image/jpeg'].includes(mime)) throw new Error('Image unavailable');
                const bytes = await response.arrayBuffer();
                if (bytes.byteLength === 0 || bytes.byteLength > 12 * 1024 * 1024) throw new Error('Image too large');
                if (sequence !== imageRequest || media.value !== id || !connected) return;
                frame.contentWindow?.postMessage({ type: 'furniture-home-canvas-image', id, mime, bytes }, targetOrigin, [bytes]);
                status.textContent = connectedMessage();
            } catch (error) {
                if (sequence === imageRequest && error?.name !== 'AbortError') status.textContent = 'Canvas connected, but the selected image could not be loaded. Check your session or choose another image.';
            }
        };
        window.addEventListener('message', (event) => {
            if (event.source !== frame.contentWindow || event.origin !== targetOrigin || !event.data || typeof event.data !== 'object') return;
            if (event.data.type === 'furniture-home-canvas-ready') {
                connected = true;
                status.textContent = connectedMessage();
                sendValues();
                void sendPrivateImage();
            } else if (event.data.type === 'furniture-home-tagline-ready') {
                // Tagline is a separate real component and may hydrate after the hero.
                taglineReady = true;
                if (connected && status.textContent.includes('tagline preview is unavailable')) status.textContent = connectedMessage();
                sendValues();
            } else if (event.data.type === 'furniture-home-canvas-select') {
                // Toolbar selection stays in the frame so keyboard users can reach move controls.
                if (event.data.focus_input !== undefined && typeof event.data.focus_input !== 'boolean') return;
                if (event.data.focus_input !== false && event.data.field === 'hero_title') title.focus();
                if (event.data.focus_input !== false && event.data.field === 'hero_subtitle') subtitle.focus();
                if (event.data.focus_input !== false && event.data.field === 'hero_cta_label') ctaLabel.focus();
                if (event.data.focus_input !== false && event.data.field === 'hero_image') image.focus();
                if (event.data.focus_input !== false && event.data.field === 'home_tagline') tagline?.focus();
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
        for (const input of [title, subtitle, ctaLabel, ctaPath, tagline]) input?.addEventListener('input', sendValues);
        order.addEventListener('change', sendValues);
        image.addEventListener('change', sendValues);
        if (media) media.addEventListener('change', () => { sendValues(); void sendPrivateImage(); });
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
