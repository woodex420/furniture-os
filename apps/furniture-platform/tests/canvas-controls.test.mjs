import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../resources/js/workspace.js', import.meta.url), 'utf8');

function setup() {
    class Element {
        constructor() { this.handlers = {}; this.attributes = {}; this.dataset = {}; this.style = {}; this.value = ''; this.hidden = true; }
        addEventListener(type, handler) { (this.handlers[type] ??= []).push(handler); }
        dispatchEvent(event) { for (const fn of this.handlers[event.type] ?? []) fn(event); }
        setAttribute(name, value) { this.attributes[name] = value; }
        focus() { this.focused = true; }
        click() { this.dispatchEvent({ type: 'click' }); }
    }
    const title = new Element(), subtitle = new Element(), order = new Element(), ctaLabel = new Element(), ctaPath = new Element(), frame = new Element(), status = new Element(), controls = new Element();
    title.value = 'Draft'; subtitle.value = 'Sub'; order.value = 'title_first'; ctaLabel.value = 'Shop'; ctaPath.value = '/shop';
    const messages = [];
    frame.contentWindow = { postMessage: (...args) => messages.push(args) };
    const buttons = ['fit', '375', '768', '1280', 'evil'].map(width => { const b = new Element(); b.dataset.canvasWidth = width; return b; });
    controls.querySelectorAll = () => buttons;
    const home = new Element(); home.dataset.storefrontOrigin = 'https://store.example';
    home.querySelector = selector => ({ '[data-canvas-frame]': frame, '[data-canvas-status]': status, '[data-canvas-controls]': controls })[selector];
    const doc = { documentElement: { classList: { remove() {} } }, querySelector: selector => selector === '[data-home-canvas]' ? home : null, getElementById: id => ({ 'hero-title': title, 'hero-subtitle': subtitle, 'hero-order': order, 'hero-cta-label': ctaLabel, 'hero-cta-path': ctaPath })[id], addEventListener() {} };
    const win = new Element(); win.location = { origin: 'https://admin.example' }; win.setTimeout = () => 1;
    vm.runInNewContext(script, { document: doc, window: win, URL, Event: class { constructor(type) { this.type = type; } } });
    const message = data => win.dispatchEvent({ type: 'message', source: frame.contentWindow, origin: 'https://store.example', data });
    return { buttons, controls, frame, title, subtitle, order, ctaLabel, ctaPath, status, messages, win, message };
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
