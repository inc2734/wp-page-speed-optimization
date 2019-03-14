'use strict';

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

export const replacePrefetchedImg = (img) => {
  const prefetchImg = new Image();

  prefetchImg.onload = () => {
    replaceImg(img);
  };

  prefetchImg.src = img.getAttribute('data-src');
};
