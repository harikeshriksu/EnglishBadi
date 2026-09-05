/**
 * Lightweight contenteditable WYSIWYG editor. No external library - uses
 * the browser's own execCommand for formatting (still broadly supported
 * in every current browser for contenteditable regions) plus custom
 * logic for colour palettes, link/image insert, paste handling and a
 * live preview toggle.
 *
 * Multiple independent instances can exist on one page: every element
 * with [data-editor-root] is initialised separately.
 */
(function () {
  'use strict';

  var DEFAULT_COLORS = ['#4A5FBF', '#3FAE4B', '#1F2328', '#565D66', '#C0392B', '#FFFFFF'];
  var STATE_COMMANDS = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList', 'justifyLeft', 'justifyCenter'];

  // ---- Shared prompt modal (created once, reused for every "insert link") ----
  var promptModalEl = null;

  function getPromptModal() {
    if (promptModalEl) return promptModalEl;

    var modal = document.createElement('div');
    modal.className = 'crop-modal';
    modal.hidden = true;
    modal.innerHTML =
      '<div class="crop-modal__box" style="max-width:420px;">' +
      '<h3 data-modal-title>Insert Link</h3>' +
      '<div class="form-field"><input type="text" data-modal-input></div>' +
      '<div class="form-actions">' +
      '<button type="button" class="btn btn--outline" data-modal-cancel>Cancel</button>' +
      '<button type="button" class="btn btn--primary" data-modal-ok>Insert</button>' +
      '</div></div>';
    document.body.appendChild(modal);
    promptModalEl = modal;
    return modal;
  }

  function promptForText(title, placeholder, initialValue) {
    return new Promise(function (resolve) {
      var modal = getPromptModal();
      modal.querySelector('[data-modal-title]').textContent = title;
      var input = modal.querySelector('[data-modal-input]');
      input.placeholder = placeholder || '';
      input.value = initialValue || '';
      modal.hidden = false;
      window.setTimeout(function () { input.focus(); }, 0);

      var okBtn = modal.querySelector('[data-modal-ok]');
      var cancelBtn = modal.querySelector('[data-modal-cancel]');

      function cleanup(result) {
        modal.hidden = true;
        okBtn.removeEventListener('click', onOk);
        cancelBtn.removeEventListener('click', onCancel);
        input.removeEventListener('keydown', onKeydown);
        resolve(result);
      }
      function onOk() { cleanup(input.value.trim() || null); }
      function onCancel() { cleanup(null); }
      function onKeydown(e) {
        if (e.key === 'Enter') { e.preventDefault(); onOk(); }
        if (e.key === 'Escape') { e.preventDefault(); onCancel(); }
      }

      okBtn.addEventListener('click', onOk);
      cancelBtn.addEventListener('click', onCancel);
      input.addEventListener('keydown', onKeydown);
    });
  }

  // ---- Selection helpers (needed because focus moves away during an
  // async image upload, which would otherwise lose the cursor position) ----
  function saveSelection() {
    var sel = window.getSelection();
    return sel && sel.rangeCount > 0 ? sel.getRangeAt(0).cloneRange() : null;
  }
  function restoreSelection(range) {
    if (!range) return;
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }

  // ---- Lightweight client-side cleanup after a "paste with formatting" ----
  var PASTE_ALLOWED_TAGS = { P: 1, BR: 1, H2: 1, H3: 1, STRONG: 1, B: 1, EM: 1, I: 1, U: 1, S: 1, STRIKE: 1, UL: 1, OL: 1, LI: 1, BLOCKQUOTE: 1, A: 1, IMG: 1, SPAN: 1 };

  function cleanupPastedContent(root) {
    Array.prototype.slice.call(root.childNodes).forEach(function (child) {
      if (child.nodeType === 1) {
        if (!PASTE_ALLOWED_TAGS[child.tagName]) {
          while (child.firstChild) root.insertBefore(child.firstChild, child);
          root.removeChild(child);
          return;
        }
        Array.prototype.slice.call(child.attributes).forEach(function (attr) {
          if (attr.name === 'style') {
            var kept = [];
            attr.value.split(';').forEach(function (decl) {
              var prop = (decl.split(':')[0] || '').trim().toLowerCase();
              if (prop === 'color' || prop === 'background-color') kept.push(decl.trim());
            });
            if (kept.length) child.setAttribute('style', kept.join('; '));
            else child.removeAttribute('style');
          } else if (['href', 'src', 'alt', 'lang'].indexOf(attr.name) === -1) {
            child.removeAttribute(attr.name);
          }
        });
        cleanupPastedContent(child);
      } else if (child.nodeType !== 3) {
        root.removeChild(child);
      }
    });
  }

  function initEditor(root) {
    var toolbar = root.querySelector('[data-editor-toolbar]');
    var body = root.querySelector('[data-editor-body]');
    var preview = root.querySelector('[data-editor-preview]');
    var output = root.querySelector('[data-editor-output]');
    var pasteModeCheckbox = root.querySelector('[data-paste-mode]');
    var previewBtn = root.querySelector('[data-action="toggle-preview"]');

    function syncOutput() {
      output.value = body.innerHTML;
    }

    function updateActiveStates() {
      STATE_COMMANDS.forEach(function (cmd) {
        var btn = toolbar.querySelector('[data-cmd="' + cmd + '"]');
        if (!btn) return;
        var active = false;
        try { active = document.queryCommandState(cmd); } catch (e) { active = false; }
        btn.classList.toggle('is-active', active);
      });
    }

    function exec(cmd, value) {
      body.focus();
      try { document.execCommand('styleWithCSS', false, true); } catch (e) { /* ignore */ }
      document.execCommand(cmd, false, value || null);
      syncOutput();
      updateActiveStates();
    }

    // ---- Colour popovers ----
    function buildColorGrid(popover, mode) {
      var grid = popover.querySelector('[data-color-grid]');
      grid.innerHTML = '';
      DEFAULT_COLORS.forEach(function (hex) {
        var swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.style.background = hex;
        swatch.title = hex;
        swatch.addEventListener('click', function () {
          applyColor(mode, hex);
          closeAllPopovers();
        });
        grid.appendChild(swatch);
      });

      var hexInput = popover.querySelector('[data-hex-input]');
      var hexApply = popover.querySelector('[data-hex-apply]');
      hexApply.addEventListener('click', function () {
        var val = hexInput.value.trim();
        if (!/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(val)) {
          window.alert('Please enter a valid hex colour, like #4A5FBF.');
          return;
        }
        applyColor(mode, val);
        closeAllPopovers();
      });
    }

    function applyColor(mode, hex) {
      exec(mode === 'hiliteColor' ? 'hiliteColor' : 'foreColor', hex);
    }

    function closeAllPopovers() {
      Array.prototype.forEach.call(document.querySelectorAll('.editor-color-popover.is-open'), function (p) {
        p.classList.remove('is-open');
      });
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-color-popover]'), function (popover) {
      buildColorGrid(popover, popover.getAttribute('data-color-mode'));
    });

    // ---- Toolbar clicks ----
    toolbar.addEventListener('click', function (e) {
      var target = e.target;
      while (target && target !== toolbar && !target.hasAttribute('data-cmd') && !target.hasAttribute('data-action')) {
        target = target.parentElement;
      }
      if (!target || target === toolbar) return;

      if (target.hasAttribute('data-cmd')) {
        e.preventDefault();
        exec(target.getAttribute('data-cmd'), target.getAttribute('data-value'));
        return;
      }

      var action = target.getAttribute('data-action');
      if (action === 'undo' || action === 'redo') {
        e.preventDefault();
        exec(action);
      } else if (action === 'text-color' || action === 'highlight-color') {
        e.preventDefault();
        e.stopPropagation();
        var popover = target.parentElement.querySelector('[data-color-popover]');
        var wasOpen = popover.classList.contains('is-open');
        closeAllPopovers();
        if (!wasOpen) popover.classList.add('is-open');
      } else if (action === 'link') {
        e.preventDefault();
        var savedRange = saveSelection();
        promptForText('Insert Link', 'https://example.com').then(function (url) {
          if (!url) return;
          if (!/^(https?:|mailto:)/i.test(url) && url.charAt(0) !== '/') {
            url = 'https://' + url;
          }
          body.focus();
          restoreSelection(savedRange);
          document.execCommand('createLink', false, url);
          syncOutput();
        });
      } else if (action === 'image') {
        e.preventDefault();
        triggerImageUpload();
      } else if (action === 'toggle-telugu') {
        e.preventDefault();
        body.classList.toggle('font-telugu');
      } else if (action === 'toggle-preview') {
        e.preventDefault();
        togglePreview();
      }
    });

    document.addEventListener('click', function (e) {
      if (!toolbar.contains(e.target)) closeAllPopovers();
    });

    // ---- Image upload ----
    function triggerImageUpload() {
      var input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;

        var savedRange = saveSelection();
        var imageBtn = toolbar.querySelector('[data-action="image"]');
        imageBtn.disabled = true;

        var formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', root.getAttribute('data-csrf-token'));

        fetch(root.getAttribute('data-upload-url'), {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            imageBtn.disabled = false;
            if (!data.ok) {
              window.alert(data.error || "Sorry, that image couldn't be uploaded.");
              return;
            }
            body.focus();
            restoreSelection(savedRange);
            document.execCommand('insertHTML', false, '<img src="' + data.url + '" alt="">');
            syncOutput();
          })
          .catch(function () {
            imageBtn.disabled = false;
            window.alert("Sorry, that image couldn't be uploaded. Please check your connection and try again.");
          });
      });
      input.click();
    }

    // ---- Preview toggle ----
    function togglePreview() {
      var isPreviewing = preview.classList.contains('is-visible');
      if (isPreviewing) {
        preview.classList.remove('is-visible');
        body.classList.remove('is-hidden');
        previewBtn.textContent = 'Preview';
        toolbar.classList.remove('is-preview-mode');
      } else {
        preview.innerHTML = body.innerHTML;
        body.classList.add('is-hidden');
        preview.classList.add('is-visible');
        previewBtn.textContent = 'Edit';
        toolbar.classList.add('is-preview-mode');
      }
    }

    // ---- Paste handling: plain text by default, formatted if opted in ----
    body.addEventListener('paste', function (e) {
      if (pasteModeCheckbox && pasteModeCheckbox.checked) {
        window.setTimeout(function () {
          cleanupPastedContent(body);
          syncOutput();
        }, 0);
        return;
      }
      e.preventDefault();
      var clipboard = e.clipboardData || window.clipboardData;
      var text = clipboard ? clipboard.getData('text/plain') : '';
      document.execCommand('insertText', false, text);
      syncOutput();
    });

    body.addEventListener('input', syncOutput);
    body.addEventListener('keyup', updateActiveStates);
    body.addEventListener('mouseup', updateActiveStates);
    body.addEventListener('focus', updateActiveStates);

    syncOutput();
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-editor-root]'), initEditor);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
