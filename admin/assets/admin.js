/**
 * Admin-wide behaviour: mobile nav drawer, and a confirmation prompt on
 * every destructive action marked with data-confirm="...".
 */
(function () {
  'use strict';

  var hamburger = document.getElementById('admin-hamburger');
  var nav = document.getElementById('admin-nav');

  if (hamburger && nav) {
    hamburger.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', function (e) {
      if (!nav.classList.contains('is-open')) return;
      if (nav.contains(e.target) || hamburger.contains(e.target)) return;
      nav.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', 'false');
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-confirm]'), function (el) {
    el.addEventListener('click', function (e) {
      var message = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Auto-submit a <select> the moment it changes (e.g. list filters),
  // without an inline onchange= attribute.
  Array.prototype.forEach.call(document.querySelectorAll('[data-autosubmit]'), function (el) {
    el.addEventListener('change', function () {
      if (el.form) el.form.submit();
    });
  });
})();
