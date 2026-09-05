/**
 * Vanilla-JS square crop tool. Shows a modal with the picked image and a
 * draggable/resizable square box; resolves with either a crop rectangle
 * (in the ORIGINAL image's pixel coordinates, ready to send to the
 * server) or a "keep full image" choice. Uses Pointer Events so the same
 * code handles mouse and touch.
 *
 * Usage: window.EBCrop.open(file).then(function (decision) { ... })
 * decision is either {mode:'crop', x, y, size} or {mode:'keep'}.
 */
(function () {
  'use strict';

  var modalEl = null;

  function getModal() {
    if (modalEl) return modalEl;

    var modal = document.createElement('div');
    modal.className = 'crop-modal';
    modal.hidden = true;
    modal.innerHTML =
      '<div class="crop-modal__box">' +
      '<h3>Crop this poster to a square</h3>' +
      '<p class="form-hint">Drag the box to choose which part to keep. Drag the corner handle to resize it.</p>' +
      '<div class="crop-stage" data-crop-stage>' +
      '<img data-crop-img alt="">' +
      '<div class="crop-box" data-crop-box><div class="crop-box__handle" data-crop-handle></div></div>' +
      '</div>' +
      '<div class="form-actions">' +
      '<button type="button" class="btn btn--outline" data-crop-keep>Keep full image</button>' +
      '<button type="button" class="btn btn--primary" data-crop-use>Use this crop</button>' +
      '</div></div>';
    document.body.appendChild(modal);
    modalEl = modal;
    return modal;
  }

  function setBoxRect(box, x, y, size) {
    box.style.left = x + 'px';
    box.style.top = y + 'px';
    box.style.width = size + 'px';
    box.style.height = size + 'px';
  }

  function getBoxRect(box) {
    return {
      x: parseFloat(box.style.left) || 0,
      y: parseFloat(box.style.top) || 0,
      size: parseFloat(box.style.width) || 0,
    };
  }

  function clamp(v, min, max) {
    return Math.max(min, Math.min(max, v));
  }

  function attachDrag(box, stageW, stageH) {
    var handle = box.querySelector('[data-crop-handle]');
    var dragging = null;
    var startX = 0, startY = 0, startRect = null;

    function onPointerMove(e) {
      if (!dragging) return;
      var dx = e.clientX - startX;
      var dy = e.clientY - startY;

      if (dragging === 'move') {
        var x = clamp(startRect.x + dx, 0, stageW - startRect.size);
        var y = clamp(startRect.y + dy, 0, stageH - startRect.size);
        setBoxRect(box, x, y, startRect.size);
      } else {
        var delta = Math.max(dx, dy);
        var maxSize = Math.min(stageW - startRect.x, stageH - startRect.y);
        var size = clamp(startRect.size + delta, 40, maxSize);
        setBoxRect(box, startRect.x, startRect.y, size);
      }
    }

    function onPointerUp() {
      dragging = null;
      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', onPointerUp);
    }

    function onPointerDown(mode) {
      return function (e) {
        e.preventDefault();
        dragging = mode;
        startX = e.clientX;
        startY = e.clientY;
        startRect = getBoxRect(box);
        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);
      };
    }

    handle.addEventListener('pointerdown', function (e) {
      e.stopPropagation();
      onPointerDown('resize')(e);
    });
    box.addEventListener('pointerdown', onPointerDown('move'));

    return function detach() {
      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', onPointerUp);
    };
  }

  function open(file) {
    return new Promise(function (resolve) {
      var modal = getModal();
      var img = modal.querySelector('[data-crop-img]');
      var stage = modal.querySelector('[data-crop-stage]');
      var box = modal.querySelector('[data-crop-box]');
      var useBtn = modal.querySelector('[data-crop-use]');
      var keepBtn = modal.querySelector('[data-crop-keep]');
      var url = URL.createObjectURL(file);
      var detach = null;

      img.onload = function () {
        var maxW = Math.min(460, window.innerWidth - 60);
        var maxH = 360;
        var natW = img.naturalWidth;
        var natH = img.naturalHeight;
        var scale = Math.min(maxW / natW, maxH / natH, 1);
        var dispW = Math.round(natW * scale);
        var dispH = Math.round(natH * scale);

        img.style.width = dispW + 'px';
        img.style.height = dispH + 'px';
        stage.style.width = dispW + 'px';
        stage.style.height = dispH + 'px';

        var size = Math.min(dispW, dispH);
        setBoxRect(box, (dispW - size) / 2, (dispH - size) / 2, size);

        detach = attachDrag(box, dispW, dispH);
        modal.hidden = false;

        function cleanup() {
          URL.revokeObjectURL(url);
          modal.hidden = true;
          if (detach) detach();
          useBtn.removeEventListener('click', onUse);
          keepBtn.removeEventListener('click', onKeep);
        }

        function onUse() {
          var rect = getBoxRect(box);
          cleanup();
          resolve({
            mode: 'crop',
            x: Math.round(rect.x / scale),
            y: Math.round(rect.y / scale),
            size: Math.round(rect.size / scale),
          });
        }

        function onKeep() {
          cleanup();
          resolve({ mode: 'keep' });
        }

        useBtn.addEventListener('click', onUse);
        keepBtn.addEventListener('click', onKeep);
      };

      img.src = url;
    });
  }

  window.EBCrop = { open: open };
})();
