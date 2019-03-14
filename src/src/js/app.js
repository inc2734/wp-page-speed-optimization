'use strict';

import forEachHtmlNodes from '@inc2734/for-each-html-nodes';
import {replaceImg, replacePrefetchedImg} from './module/lazyload.js';

document.addEventListener(
  'DOMContentLoaded',
  () => {
    const images = document.querySelectorAll('img[data-src][decoding="async"]');

    if (typeof IntersectionObserver !== 'undefined') {
      const lazyLoadObserver = new IntersectionObserver(
        (entries, object) => {
          entries.forEach(
            (entry, i) => {
              if (! entry.isIntersecting) {
                return;
              }

              replacePrefetchedImg(entry.target);
              object.unobserve(entry.target);
            }
          );
        },
        {
          rootMargin: "100px",
        }
      );

      forEachHtmlNodes(images, (img) => lazyLoadObserver.observe(img));
    } else {
      forEachHtmlNodes(images, (img) => replaceImg(img));
    }
  }
);
