'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
let document;

// Exercise the actual menu handlers without requiring a browser or an extra package.
function element() {
    return {
        dataset: {}, attributes: {}, listeners: {}, inert: false, hidden: false,
        addEventListener(type, listener) { (this.listeners[type] ??= []).push(listener); },
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { document.activeElement = this; },
    };
}
const sidebar = element();
const content = element();
const toggle = element();
const close = element();
const backdrop = element();
const firstLink = element();
const lastLink = element();
const desktop = Object.assign(element(), { matches: false });
const classes = new Set();
sidebar.querySelectorAll = () => [firstLink, close, lastLink];
document = Object.assign(element(), {
    body: { classList: { toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); } } },
    getElementById: id => ({ 'admin-sidebar': sidebar, 'admin-content': content })[id],
    querySelector: selector => ({ '[data-admin-menu-toggle]': toggle, '[data-admin-menu-close]': close, '[data-admin-backdrop]': backdrop })[selector],
    querySelectorAll: () => [],
});
const window = Object.assign(element(), { matchMedia: () => desktop });
vm.runInNewContext(fs.readFileSync(path.join(root, 'js/admin.js'), 'utf8'), { document, window });
function fire(target, type, details = {}) {
    const event = { ...details, prevented: false, preventDefault() { this.prevented = true; } };
    for (const listener of target.listeners[type] ?? []) listener(event);
    return event;
}
function expectClosed() {
    assert.equal(sidebar.inert, true);
    assert.equal(content.inert, false);
    assert.equal(backdrop.hidden, true);
    assert.equal(toggle.attributes['aria-expanded'], 'false');
    assert.equal(classes.has('overflow-hidden'), false);
}
expectClosed();
fire(toggle, 'click');
assert.equal(sidebar.inert, false);
assert.equal(content.inert, true);
assert.equal(backdrop.hidden, false);
assert.equal(toggle.attributes['aria-expanded'], 'true');
assert.equal(classes.has('overflow-hidden'), true);
assert.equal(document.activeElement, close);
lastLink.focus();
assert.equal(fire(document, 'keydown', { key: 'Tab' }).prevented, true);
assert.equal(document.activeElement, firstLink);
assert.equal(fire(document, 'keydown', { key: 'Tab', shiftKey: true }).prevented, true);
assert.equal(document.activeElement, lastLink);
fire(document, 'keydown', { key: 'Escape' });
expectClosed();
assert.equal(document.activeElement, toggle);
for (const dismiss of [close, backdrop]) {
    fire(toggle, 'click');
    fire(dismiss, 'click');
    expectClosed();
}
fire(toggle, 'click');
desktop.matches = true;
fire(desktop, 'change');
assert.equal(sidebar.inert, false);
assert.equal(content.inert, false);
assert.equal(backdrop.hidden, true);
desktop.matches = false;
fire(desktop, 'change');
expectClosed();

const css = fs.readFileSync(path.join(root, 'css/admin.css'), 'utf8');
for (const selector of ['.admin-card', '.admin-input', '.status-active', '.status-blocked', '[data-open=true]', '.lg\\:ml-\\[270px\\]']) {
    assert.ok(css.includes(selector), 'Compiled admin CSS is missing ' + selector);
}
const sprite = fs.readFileSync(path.join(root, 'images/admin/icons.svg'), 'utf8');
for (const file of ['admin.php', 'admin-dashboard.php', 'admin-posts.php', 'includes/admin-sidebar.php', 'includes/admin-topbar.php']) {
    const markup = fs.readFileSync(path.join(root, file), 'utf8');
    for (const match of markup.matchAll(/(?:admin_icon\(|'icon' => )'([a-z-]+)'/g)) {
        assert.ok(sprite.includes('id="' + match[1] + '"'), 'Missing admin icon: ' + match[1]);
    }
    for (const match of markup.matchAll(/'images\/admin\/([^']+)'/g)) {
        assert.ok(fs.existsSync(path.join(root, 'images/admin', match[1])), 'Missing template asset: ' + match[1]);
    }
}
console.log('PASS: admin menu open/close, keyboard focus, responsive state, compiled styles and template assets. Browser layout not covered.');
