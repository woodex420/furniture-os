import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../resources/js/workspace.js', import.meta.url), 'utf8');

function setup({ media = true } = {}) {
    class Element {
        constructor(value = '') { this.value = value; this.handlers = {}; this.hidden = true; this.disabled = false; }
        addEventListener(type, fn) { (this.handlers[type] ??= []).push(fn); }
        dispatchEvent(event) { for (const fn of this.handlers[event.type] ?? []) fn(event); }
        click() { if (!this.disabled) this.dispatchEvent({ type: 'click' }); }
    }
    const fields = Object.fromEntries(['hero-title', 'hero-subtitle', 'hero-order', 'hero-cta-label', 'hero-cta-path', 'hero-image', 'home-tagline', ...(media ? ['hero-media'] : [])].map(id => [id, new Element(id)]));
    const undo = new Element(), redo = new Element(), history = new Element(), form = new Element();
    history.querySelector = selector => ({ '[data-home-undo]': undo, '[data-home-redo]': redo })[selector];
    form.querySelector = selector => selector === '[data-home-history]' ? history : null;
    form.contains = el => Object.values(fields).includes(el);
    const doc = { documentElement: { classList: { remove() {} } }, querySelector: selector => selector === '[data-home-editor-form]' ? form : null, getElementById: id => fields[id] ?? null, addEventListener() {} };
    vm.runInNewContext(script, { document: doc, window: {}, Event: class { constructor(type) { this.type = type; } } });
    const set = (id, value, event) => { fields[id].value = value; fields[id].dispatchEvent({ type: event }); };
    return { fields, undo, redo, history, set };
}

test('undo and redo cover text and selects, clear future history after a new edit, and dispatch changes', () => {
    const { fields, undo, redo, history, set } = setup();
    assert.equal(history.hidden, false);
    assert.equal(undo.disabled, true);
    assert.equal(redo.disabled, true);
    let orderEvents = 0;
    fields['hero-order'].addEventListener('change', () => { orderEvents++; });
    set('hero-title', 'New title', 'input');
    set('hero-order', 'subtitle_first', 'change');
    set('hero-media', 'image-id', 'change');
    assert.equal(undo.disabled, false);
    undo.click();
    assert.equal(fields['hero-media'].value, 'hero-media');
    undo.click();
    assert.equal(fields['hero-order'].value, 'hero-order');
    assert.equal(orderEvents, 2, 'undo notifies the canvas bridge');
    redo.click();
    assert.equal(fields['hero-order'].value, 'subtitle_first');
    set('home-tagline', 'New tagline', 'input');
    assert.equal(redo.disabled, true);
    undo.click(); undo.click(); undo.click();
    assert.equal(fields['hero-title'].value, 'hero-title');
    assert.equal(fields['home-tagline'].value, 'home-tagline');
    assert.equal(undo.disabled, true);
    redo.click();
    assert.equal(fields['hero-title'].value, 'New title');
});

test('duplicate events do not create steps and history retains at most 100 undo steps', () => {
    const { fields, undo, redo, set } = setup({ media: false });
    set('hero-title', 'hero-title', 'input');
    assert.equal(undo.disabled, true);
    for (let i = 1; i <= 125; i++) set('hero-title', String(i), 'input');
    for (let i = 0; i < 100; i++) undo.click();
    assert.equal(fields['hero-title'].value, '25');
    assert.equal(undo.disabled, true);
    for (let i = 0; i < 100; i++) redo.click();
    assert.equal(fields['hero-title'].value, '125');
    assert.equal(redo.disabled, true);
});
