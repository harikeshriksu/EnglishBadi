/**
 * Switches between the Login and Register panels on /login without a
 * page reload. Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  var tabs = document.querySelectorAll('[data-auth-tab]');
  if (!tabs.length) return;

  function activate(name) {
    Array.prototype.forEach.call(tabs, function (tab) {
      var isActive = tab.getAttribute('data-auth-tab') === name;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    var loginPanel = document.getElementById('auth-panel-login');
    var registerPanel = document.getElementById('auth-panel-register');
    if (loginPanel) loginPanel.hidden = name !== 'login';
    if (registerPanel) registerPanel.hidden = name !== 'register';

    if (window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', name);
      window.history.replaceState(null, '', url);
    }
  }

  Array.prototype.forEach.call(tabs, function (tab) {
    tab.addEventListener('click', function () {
      activate(tab.getAttribute('data-auth-tab'));
    });
  });
})();
