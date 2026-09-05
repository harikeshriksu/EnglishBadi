/**
 * Site-wide behaviour: mobile nav drawer, and lazy YouTube embeds on the
 * Links page. Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  // ---- Mobile nav drawer ----
  var hamburger = document.getElementById('hamburger-btn');
  var nav = document.getElementById('site-nav');

  if (hamburger && nav) {
    var closeNav = function () {
      nav.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', 'false');
    };
    var openNav = function () {
      nav.classList.add('is-open');
      hamburger.setAttribute('aria-expanded', 'true');
    };

    hamburger.addEventListener('click', function () {
      if (nav.classList.contains('is-open')) {
        closeNav();
      } else {
        openNav();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });

    document.addEventListener('click', function (e) {
      if (!nav.classList.contains('is-open')) return;
      if (nav.contains(e.target) || hamburger.contains(e.target)) return;
      closeNav();
    });

    Array.prototype.forEach.call(nav.querySelectorAll('a'), function (link) {
      link.addEventListener('click', closeNav);
    });
  }

  // ---- Lazy YouTube embeds on the Links page ----
  var thumbs = document.querySelectorAll('[data-youtube-id]');
  Array.prototype.forEach.call(thumbs, function (thumb) {
    thumb.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        thumb.click();
      }
    });

    thumb.addEventListener('click', function () {
      var videoId = thumb.getAttribute('data-youtube-id');
      var wrap = document.getElementById(thumb.getAttribute('data-embed-target'));
      if (!wrap) return;

      if (!wrap.dataset.loaded) {
        var iframe = document.createElement('iframe');
        iframe.setAttribute('src', 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0');
        iframe.setAttribute('title', 'YouTube video player');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'lazy');
        wrap.appendChild(iframe);
        wrap.dataset.loaded = '1';
      }

      wrap.hidden = false;
      thumb.closest('.link-card').classList.add('is-playing');
      wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });
})();
