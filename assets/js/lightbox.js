/**
 * Poster gallery lightbox: full-size view with prev/next, swipe,
 * keyboard navigation, download and share (Web Share API with a
 * clipboard fallback).
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('poster-data');
  var lightbox = document.getElementById('poster-lightbox');
  if (!dataEl || !lightbox) return;

  var posters = JSON.parse(dataEl.textContent);
  var current = 0;
  var lastFocused = null;

  var imageEl = document.getElementById('lightbox-image');
  var sourceWebp = document.getElementById('lightbox-source-webp');
  var captionEl = document.getElementById('lightbox-caption');
  var counterEl = document.getElementById('lightbox-counter');
  var downloadEl = document.getElementById('lightbox-download');
  var shareBtn = document.getElementById('lightbox-share');
  var closeBtn = document.getElementById('lightbox-close');
  var prevBtn = document.getElementById('lightbox-prev');
  var nextBtn = document.getElementById('lightbox-next');

  function show(index) {
    current = (index + posters.length) % posters.length;
    var p = posters[current];

    if (p.webp) {
      sourceWebp.setAttribute('srcset', p.webp);
      sourceWebp.hidden = false;
    } else {
      sourceWebp.removeAttribute('srcset');
      sourceWebp.hidden = true;
    }
    imageEl.setAttribute('src', p.image);
    imageEl.setAttribute('alt', p.alt || p.caption || '');
    captionEl.textContent = p.caption || '';
    captionEl.hidden = !p.caption;
    counterEl.textContent = (current + 1) + ' of ' + posters.length;
    downloadEl.setAttribute('href', p.image);
    downloadEl.setAttribute('download', '');
  }

  function open(index, triggerEl) {
    lastFocused = triggerEl || document.activeElement;
    show(index);
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }

  function close() {
    lightbox.hidden = true;
    document.body.style.overflow = '';
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  Array.prototype.forEach.call(document.querySelectorAll('.poster-grid__item'), function (btn, i) {
    btn.addEventListener('click', function () { open(i, btn); });
  });

  closeBtn.addEventListener('click', close);
  prevBtn.addEventListener('click', function () { show(current - 1); });
  nextBtn.addEventListener('click', function () { show(current + 1); });

  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) close();
  });

  document.addEventListener('keydown', function (e) {
    if (lightbox.hidden) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') show(current - 1);
    else if (e.key === 'ArrowRight') show(current + 1);
  });

  // ---- Touch swipe ----
  var touchStartX = 0, touchStartY = 0;
  lightbox.addEventListener('touchstart', function (e) {
    touchStartX = e.changedTouches[0].clientX;
    touchStartY = e.changedTouches[0].clientY;
  }, { passive: true });

  lightbox.addEventListener('touchend', function (e) {
    var dx = e.changedTouches[0].clientX - touchStartX;
    var dy = e.changedTouches[0].clientY - touchStartY;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      show(current + (dx < 0 ? 1 : -1));
    }
  }, { passive: true });

  // ---- Share ----
  shareBtn.addEventListener('click', function () {
    var p = posters[current];
    // p.image is already an absolute URL (built server-side via base_url()).
    var url = p.image;
    var shareData = { title: p.caption || 'English Badi poster', url: url };

    if (navigator.share) {
      navigator.share(shareData).catch(function () { /* user cancelled - ignore */ });
      return;
    }

    var originalText = shareBtn.textContent;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () {
        shareBtn.textContent = 'Link copied!';
        window.setTimeout(function () { shareBtn.textContent = originalText; }, 2000);
      }).catch(function () {
        window.prompt('Copy this link:', url);
      });
    } else {
      window.prompt('Copy this link:', url);
    }
  });
})();
