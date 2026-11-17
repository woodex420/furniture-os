import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../resources/js/workspace.js', import.meta.url), 'utf8');

function setup(fetchOverride) {
    class Element {
        constructor() { this.handlers = {}; this.attributes = {}; this.dataset = {}; this.style = {}; this.value = ''; this.hidden = true; }
        addEventListener(type, handler) { (this.handlers[type] ??= []).push(handler); }
        dispatchEvent(event) { for (const fn of this.handlers[event.type] ?? []) fn(event); }
        setAttribute(name, value) { this.attributes[name] = value; }
        focus() { this.focused = true; }
        click() { this.dispatchEvent({ type: 'click' }); }
    }
    const title = new Element(), subtitle = new Element(), order = new Element(), ctaLabel = new Element(), ctaPath = new Element(), image = new Element(), media = new Element(), tagline = new Element(), frame = new Element(), status = new Element(), controls = new Element();
    title.value = 'Draft'; subtitle.value = 'Sub'; order.value = 'title_first'; ctaLabel.value = 'Shop'; ctaPath.value = '/shop'; image.value = 'workspace'; tagline.value = 'Original tagline';
    const messages = [];
    frame.contentWindow = { postMessage: (...args) => messages.push(args) };
    const buttons = ['fit', '375', '768', '1280', 'evil'].map(width => { const b = new Element(); b.dataset.canvasWidth = width; return b; });
    controls.querySelectorAll = () => buttons;
    const home = new Element(); home.dataset.storefrontOrigin = 'https://store.example';
    home.querySelector = selector => ({ '[data-canvas-frame]': frame, '[data-canvas-status]': status, '[data-canvas-controls]': controls })[selector];
    const doc = { documentElement: { classList: { remove() {} } }, querySelector: selector => selector === '[data-home-canvas]' ? home : null, getElementById: id => ({ 'hero-title': title, 'hero-subtitle': subtitle, 'hero-order': order, 'hero-cta-label': ctaLabel, 'hero-cta-path': ctaPath, 'hero-image': image, 'hero-media': media, 'home-tagline': tagline })[id], addEventListener() {} };
    const win = new Element(); win.location = { origin: 'https://admin.example' }; win.timers = []; win.setTimeout = fn => { win.timers.push(fn); return win.timers.length; };
    const requests = [];
    const fetch = async (url, options) => {
        requests.push({ url, options });
        return fetchOverride ? fetchOverride(url, options) : { ok: true, headers: { get: () => 'image/png' }, arrayBuffer: async () => new ArrayBuffer(4) };
    };
    vm.runInNewContext(script, { document: doc, window: win, URL, AbortController, fetch, Event: class { constructor(type) { this.type = type; } } });
    const message = data => win.dispatchEvent({ type: 'message', source: frame.contentWindow, origin: 'https://store.example', data });
    return { buttons, controls, frame, title, subtitle, order, ctaLabel, ctaPath, image, media, tagline, status, messages, requests, win, message };
}

test('viewport presets change the real frame width without reloading or changing draft', () => {
    const { buttons, controls, frame, title } = setup();
    assert.equal(controls.hidden, false);
    const src = frame.src;
    for (const [index, width] of [[1, '375px'], [2, '768px'], [3, '1280px'], [0, '100%']]) {
        buttons[index].click();
        assert.equal(frame.style.width, width);
        assert.equal(buttons[index].attributes['aria-pressed'], 'true');
        assert.equal(buttons.filter(b => b.attributes['aria-pressed'] === 'true').length, 1);
        assert.equal(frame.src, src);
        assert.equal(title.value, 'Draft');
    }
    buttons[4].click();
    assert.equal(frame.style.width, '100%');
});

test('selection messages retain toolbar focus when requested and reject invalid origins or values', () => {
    const { title, subtitle, frame, win, message } = setup();
    message({ type: 'furniture-home-canvas-select', field: 'hero_title', focus_input: false });
    assert.equal(title.focused, undefined);
    message({ type: 'furniture-home-canvas-select', field: 'hero_subtitle', focus_input: true });
    assert.equal(subtitle.focused, true);
    message({ type: 'furniture-home-canvas-select', field: 'hero_title', focus_input: 'yes' });
    assert.equal(title.focused, undefined);
    win.dispatchEvent({ type: 'message', source: frame.contentWindow, origin: 'https://attacker.example', data: { type: 'furniture-home-canvas-select', field: 'hero_title' } });
    win.dispatchEvent({ type: 'message', source: {}, origin: 'https://store.example', data: { type: 'furniture-home-canvas-select', field: 'hero_title' } });
    assert.equal(title.focused, undefined);
    message({ type: 'furniture-home-canvas-select', field: 'hero_title' });
    assert.equal(title.focused, true); // old storefront clients still work
});

test('button draft is sent only to the configured frame and click selection focuses its field', () => {
    const { ctaLabel, ctaPath, messages, message } = setup();
    ctaLabel.value = 'Explore'; ctaPath.value = '/services';
    ctaLabel.dispatchEvent({ type: 'input' });
    const [payload, origin] = messages.at(-1);
    assert.equal(origin, 'https://store.example');
    assert.equal(payload.hero_cta_label, 'Explore');
    assert.equal(payload.hero_cta_path, '/services');
    message({ type: 'furniture-home-canvas-select', field: 'hero_cta_label', focus_input: true });
    assert.equal(ctaLabel.focused, true);
});

test('background selection sends the draft and focuses the image selector', () => {
    const { image, messages, message } = setup();
    image.value = 'meeting';
    image.dispatchEvent({ type: 'change' });
    assert.equal(messages.at(-1)[0].hero_image, 'meeting');
    assert.equal(messages.at(-1)[1], 'https://store.example');
    message({ type: 'furniture-home-canvas-select', field: 'hero_image', focus_input: true });
    assert.equal(image.focused, true);
});

test('private image bytes go only to the approved frame after same-origin fetch', async () => {
    const { media, messages, requests, message } = setup();
    message({ type: 'furniture-home-canvas-ready' });
    const id = '8b8f3a62-54cc-4c39-8642-f35fd1c2671a';
    media.value = id;
    media.dispatchEvent({ type: 'change' });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url, `/admin/website/media/${id}/file`);
    assert.equal(requests[0].options.credentials, 'same-origin');
    const [payload, origin, transfer] = messages.at(-1);
    assert.equal(origin, 'https://store.example');
    assert.equal(payload.type, 'furniture-home-canvas-image');
    assert.equal(payload.id, id);
    assert.equal(payload.mime, 'image/png');
    assert.equal(payload.bytes.byteLength, 4);
    assert.equal(transfer[0], payload.bytes);
    media.value = '../unsafe';
    media.dispatchEvent({ type: 'change' });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(messages.at(-1)[0].hero_media_id, null);
});

test('a late private image cannot replace a newer selection', async () => {
    const pending = [];
    const { media, messages, requests, message } = setup(() => new Promise(resolve => pending.push(resolve)));
    const a = '8b8f3a62-54cc-4c39-8642-f35fd1c2671a', b = '8b8f3a62-54cc-4c39-8642-f35fd1c2671b';
    const result = { ok: true, headers: { get: () => 'image/png' }, arrayBuffer: async () => new ArrayBuffer(4) };
    message({ type: 'furniture-home-canvas-ready' });
    media.value = a; media.dispatchEvent({ type: 'change' });
    media.value = b; media.dispatchEvent({ type: 'change' });
    assert.equal(requests[0].options.signal.aborted, true);
    pending[1](result);
    await new Promise(resolve => setImmediate(resolve));
    pending[0](result);
    await new Promise(resolve => setImmediate(resolve));
    const imageMessages = messages.filter(([payload]) => payload.type === 'furniture-home-canvas-image');
    assert.equal(imageMessages.length, 1);
    assert.equal(imageMessages[0][0].id, b);
});

test('private preview failures are reported and cleared when using a bundled image', async () => {
    const { media, status, message } = setup(async () => ({ ok: false, headers: { get: () => 'text/html' } }));
    message({ type: 'furniture-home-canvas-ready' });
    media.value = '8b8f3a62-54cc-4c39-8642-f35fd1c2671a';
    media.dispatchEvent({ type: 'change' });
    await new Promise(resolve => setImmediate(resolve));
    assert.match(status.textContent, /could not be loaded/);
    media.value = '';
    media.dispatchEvent({ type: 'change' });
    assert.match(status.textContent, /Canvas connected/);
    assert.doesNotMatch(status.textContent, /could not be loaded/);
});

test('tagline updates and separate component readiness use only the approved frame', () => {
    const { tagline, messages, message, win, frame, status } = setup();
    message({ type: 'furniture-home-canvas-ready' });
    win.timers[0]();
    assert.match(status.textContent, /tagline preview is unavailable/);
    message({ type: 'furniture-home-canvas-ready' });
    assert.match(status.textContent, /tagline preview is unavailable/);
    tagline.value = 'Fresh draft';
    tagline.dispatchEvent({ type: 'input' });
    assert.equal(messages.at(-1)[0].home_tagline, 'Fresh draft');
    const count = messages.length;
    win.dispatchEvent({ type: 'message', source: frame.contentWindow, origin: 'https://evil.example', data: { type: 'furniture-home-tagline-ready' } });
    assert.equal(messages.length, count);
    message({ type: 'furniture-home-tagline-ready' });
    assert.match(status.textContent, /Canvas connected/);
    assert.doesNotMatch(status.textContent, /unavailable/);
    assert.equal(messages.at(-1)[0].home_tagline, 'Fresh draft');
    assert.equal(messages.at(-1)[1], 'https://store.example');
    message({ type: 'furniture-home-canvas-select', field: 'home_tagline', focus_input: true });
    assert.equal(tagline.focused, true);
});
