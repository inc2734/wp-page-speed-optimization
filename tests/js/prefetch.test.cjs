const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

function loadScript(filename, { paths = ['/next/'], observe = true, config = {} } = {}) {
  const links = paths.map((path) => ({
    href: new URL(path, 'https://example.org').href,
    listeners: {},
    addEventListener(name, callback) { this.listeners[name] = callback; },
    removeEventListener(name, callback) {
      assert.equal(this.listeners[name], callback);
      delete this.listeners[name];
    },
    hover() { this.listeners.mouseenter?.({ currentTarget: this }); },
  }));
  const children = [];
  const timers = [];
  const observers = [];
  let selectors;
  let ready;
  const document = {
    addEventListener(name, callback) {
      assert.equal(name, 'DOMContentLoaded');
      ready = callback;
    },
    createElement(tag) {
      assert.equal(tag, 'link');
      return { setAttribute(name, value) { this[name] = value; } };
    },
    querySelector(selector) {
      assert.equal(selector, 'head');
      return { append: (element) => children.push(element) };
    },
    querySelectorAll(value) { selectors = Array.from(value); return links; },
  };
  const location = new URL('https://example.org/current/');
  const context = {
    URL,
    document,
    location,
    window: { location },
    WPPSO: { prefetch: { selector: '.header, .main', interval: 2000, connections: 1, ...config } },
    setInterval(callback, delay) { timers.push({ callback, delay }); return timers.length; },
  };
  if (config === null) {
    context.WPPSO = {};
  }
  if (observe) {
    context.IntersectionObserver = class {
      constructor(callback) { this.callback = callback; this.links = []; observers.push(this); }
      observe(link) { this.links.push(link); }
    };
  }
  runInNewContext(readFileSync(filename, 'utf8'), context, { filename });
  assert.equal(children.length, 0);
  assert.equal(timers.length, 0);
  ready();
  return { links, children, timers, observers, selectors };
}

const scripts = {
  source: resolve(__dirname, '../../src/src/js/prefetch.js'),
  bundle: process.env.WPPSO_TEST_BUNDLE || resolve(__dirname, '../../src/assets/js/prefetch.js'),
};

for (const [name, filename] of Object.entries(scripts)) {
  test(`${name}: hover queues a same-origin link until the configured interval`, () => {
    const { links, children, timers, selectors } = loadScript(filename, { config: { interval: 1234 } });
    assert.deepEqual(selectors, [
      '.header a[href^="https://example.org"]',
      ' .main a[href^="https://example.org"]',
    ]);
    assert.equal(timers[0].delay, 1234);
    links[0].hover();
    assert.equal(children.length, 0);
    assert.equal(links[0].listeners.mouseenter, undefined);
    timers[0].callback();
    assert.deepEqual(children.map(({ rel, href }) => ({ rel, href })), [
      { rel: 'prefetch', href: 'https://example.org/next/' },
    ]);
    timers[0].callback();
    assert.equal(children.length, 1);
  });

  test(`${name}: intersection queues only visible links and avoids duplicate requests`, () => {
    const { links, children, timers, observers } = loadScript(filename);
    assert.deepEqual(observers[0].links, links);
    observers[0].callback([{ target: links[0], isIntersecting: false }]);
    timers[0].callback();
    assert.equal(children.length, 0);
    observers[0].callback([{ target: links[0], isIntersecting: true }]);
    links[0].hover();
    timers[0].callback();
    assert.equal(children.length, 1);
    observers[0].callback([{ target: links[0], isIntersecting: true }]);
    timers[0].callback();
    assert.equal(children.length, 1);
  });

  test(`${name}: admin, login, REST, fragment and current-page links are skipped`, () => {
    const { links, children, timers, observers } = loadScript(filename, {
      paths: ['/wp-admin/', '/wp-login.php', '/wp-json/posts', '/next/#section', '/current/?query=1'],
    });
    links.forEach((link) => link.hover());
    observers[0].callback(observers[0].links.map((target) => ({ target, isIntersecting: true })));
    timers[0].callback();
    assert.equal(children.length, 0);
  });

  test(`${name}: hover works without IntersectionObserver`, () => {
    const { links, children, timers, observers } = loadScript(filename, { observe: false });
    assert.equal(observers.length, 0);
    links[0].hover();
    timers[0].callback();
    assert.equal(children[0].href, 'https://example.org/next/');
  });

  test(`${name}: distinct links respect the configured connection count`, () => {
    const { links, children, timers } = loadScript(filename, {
      paths: ['/first/', '/second/'],
      config: { connections: 2 },
    });
    links.forEach((link) => link.hover());
    timers[0].callback();
    assert.deepEqual(children.map((link) => link.href), [
      'https://example.org/second/',
      'https://example.org/first/',
    ]);
  });

  test(`${name}: missing prefetch settings leave the document untouched`, () => {
    const { children, timers, observers, selectors } = loadScript(filename, { config: null });
    assert.equal(children.length, 0);
    assert.equal(timers.length, 0);
    assert.equal(observers.length, 0);
    assert.equal(selectors, undefined);
  });
}
