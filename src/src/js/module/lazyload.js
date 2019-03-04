'use strict';

import forEachHtmlNodes from '@inc2734/for-each-html-nodes';

export const replaceImg = (img) => {
  if (!! img.getAttribute('data-src')) {
    img.setAttribute('src', img.getAttribute('data-src'));
    img.removeAttribute('data-src');
  }

  if (!! img.getAttribute('data-srcset')) {
    img.setAttribute('srcset', img.getAttribute('data-srcset'));
    img.removeAttribute('data-srcset');
  }
};

export const lazyload = () => {
  const images = document.querySelectorAll('img[data-src][decoding="async"]');

  const replacePrefetchedImg = (img) => {
    const prefetchImg = new Image();

    prefetchImg.onload = () => {
      replaceImg(img);
    };

    prefetchImg.src = img.getAttribute('data-src');
  };

  if (typeof IntersectionObserver !== 'undefined') {
    const lazyLoadObserver = new IntersectionObserver((entries, object) => {
      entries.forEach((entry, i) => {
        if (! entry.isIntersecting) {
          return;
        }

        replacePrefetchedImg(entry.target);
        object.unobserve(entry.target);
      });
    },
    {
      rootMaring: "100px 20px",
      threshold: [0, 0.5, 1.0]
    });

    forEachHtmlNodes(images, (img) => lazyLoadObserver.observe(img));
  } else {
    forEachHtmlNodes(images, (img) => replaceImg(img));
  }
};
