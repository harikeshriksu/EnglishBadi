/**
 * Live YouTube-detection preview on the Add/Edit Link form.
 */
(function () {
  'use strict';

  var input = document.querySelector('[data-youtube-check]');
  var hint = document.getElementById('youtube-preview-hint');
  var preview = document.getElementById('youtube-preview');
  if (!input || !hint || !preview) return;

  function check() {
    var m = input.value.match(/(?:youtube\.com\/(?:watch\?[^ ]*v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
    if (m) {
      hint.textContent = 'YouTube video detected - its thumbnail will be used automatically.';
      preview.innerHTML = '<img src="https://img.youtube.com/vi/' + m[1] + '/hqdefault.jpg" alt="" style="width:160px;border-radius:8px;margin-top:6px;">';
    } else {
      hint.textContent = '';
      preview.innerHTML = '';
    }
  }

  input.addEventListener('input', check);
  check();
})();
