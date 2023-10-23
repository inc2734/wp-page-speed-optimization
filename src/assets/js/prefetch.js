/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!********************************!*\
  !*** ./src/src/js/prefetch.js ***!
  \********************************/
const queue = [];
const prefetched = [];
const enqueue = _url => {
  const url = new URL(_url);
  if (-1 !== queue.indexOf(url.href)) {
    return;
  }
  if (-1 !== prefetched.indexOf(url.href)) {
    return;
  }
  if (url.href.match('wp-admin')) {
    return;
  }
  if (url.href.match('wp-json')) {
    return;
  }
  if (!!url.hash) {
    return;
  }
  if (url.pathname === location.pathname) {
    return;
  }
  queue.push(url.href);
};
const createLinkElement = url => {
  const element = document.createElement('link');
  element.setAttribute('rel', 'prefetch');
  element.setAttribute('href', url);
  return element;
};
const prefetch = url => {
  const link = createLinkElement(url);
  document.querySelector('head').append(link);
  prefetched.push(url);
};
const handleInterval = () => {
  if (1 > queue.length) {
    return;
  }
  let count = 0;
  while (WPPSO.prefetch.connections > count) {
    prefetch(queue.pop());
    count++;
  }
};
const initObserve = links => {
  if ('undefined' === typeof IntersectionObserver) {
    return;
  }
  const options = {
    root: null,
    rootMargin: "0px",
    threshold: 0
  };
  const callback = entries => entries.forEach(entry => {
    if (true === entry.isIntersecting) {
      enqueue(entry.target.href);
    }
  });
  const observer = new IntersectionObserver(callback, options);
  links.forEach(link => observer.observe(link));
};
const handleHover = event => {
  const link = event.currentTarget;
  enqueue(link.href);
  link.removeEventListener('mouseenter', handleHover);
};
document.addEventListener('DOMContentLoaded', () => {
  if ('undefined' === typeof WPPSO.prefetch) {
    return;
  }
  const blacklist = ['\/wp-admin\/', '\/wp-login.php'];
  const selectors = WPPSO.prefetch.selector.split(',');
  const selectorWithLink = selectors.map(selector => `${selector} a[href^="${window.location.origin}"]`);
  const links = [].slice.call(document.querySelectorAll(selectorWithLink)).filter(link => {
    return blacklist.every(regex => !link.href.match(regex));
  });
  initObserve(links);
  links.forEach(link => link.addEventListener('mouseenter', handleHover));
  const timerId = setInterval(handleInterval, WPPSO.prefetch.interval);
});
/******/ })()
;
//# sourceMappingURL=prefetch.js.map