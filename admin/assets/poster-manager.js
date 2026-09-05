/**
 * Poster manager: drag-and-drop / click-to-pick multi-file upload zone.
 * Each picked file is checked - square images are queued as-is, non-square
 * ones get a "Crop" button (using crop.js) before they can be uploaded.
 */
(function () {
  'use strict';

  var dropzone = document.getElementById('poster-dropzone');
  var fileInput = document.getElementById('poster-file-input');
  var queueEl = document.getElementById('poster-queue');
  var uploadBtn = document.getElementById('poster-upload-btn');
  var form = document.getElementById('poster-upload-form');
  if (!dropzone || !fileInput || !form) return;

  var queue = [];

  function updateUploadButton() {
    var ready = queue.length > 0 && queue.every(function (q) { return q.decision !== null; });
    uploadBtn.disabled = !ready;
    uploadBtn.textContent = queue.length ? ('Upload ' + queue.length + ' poster' + (queue.length === 1 ? '' : 's')) : 'Upload';
  }

  function updateItemStatus(item, text) {
    if (item.statusEl) item.statusEl.textContent = text;
  }

  function renderQueueItem(item) {
    var row = document.createElement('div');
    row.className = 'poster-queue__item';

    var thumb = document.createElement('img');
    thumb.className = 'poster-queue__thumb';
    thumb.alt = '';
    thumb.src = URL.createObjectURL(item.file);

    var body = document.createElement('div');
    body.className = 'poster-queue__body';
    var name = document.createElement('p');
    name.textContent = item.file.name;
    name.style.fontWeight = '700';
    name.style.margin = '0';
    var status = document.createElement('p');
    status.className = 'poster-queue__status';
    status.textContent = 'Checking image...';
    body.appendChild(name);
    body.appendChild(status);
    item.statusEl = status;

    var actions = document.createElement('div');
    var cropBtn = document.createElement('button');
    cropBtn.type = 'button';
    cropBtn.className = 'btn btn--outline btn--sm';
    cropBtn.textContent = 'Crop';
    cropBtn.hidden = true;
    cropBtn.addEventListener('click', function () {
      window.EBCrop.open(item.file).then(function (decision) {
        item.decision = decision;
        updateItemStatus(item, decision.mode === 'crop' ? 'Crop set - ready to upload.' : 'Will use the full image.');
        updateUploadButton();
      });
    });
    item.cropBtn = cropBtn;

    var removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn--outline btn--sm';
    removeBtn.style.marginLeft = '6px';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', function () {
      queue = queue.filter(function (q) { return q !== item; });
      row.remove();
      updateUploadButton();
    });

    actions.appendChild(cropBtn);
    actions.appendChild(removeBtn);

    row.appendChild(thumb);
    row.appendChild(body);
    row.appendChild(actions);
    queueEl.appendChild(row);
  }

  function checkSquareness(item) {
    var img = new Image();
    var url = URL.createObjectURL(item.file);
    img.onload = function () {
      URL.revokeObjectURL(url);
      if (img.naturalWidth === img.naturalHeight) {
        item.decision = { mode: 'keep' };
        updateItemStatus(item, 'Ready to upload (already square).');
      } else {
        item.cropBtn.hidden = false;
        updateItemStatus(item, 'This image is not square - click "Crop" to choose how to fit it, or Remove it.');
      }
      updateUploadButton();
    };
    img.onerror = function () {
      updateItemStatus(item, "This doesn't look like a readable image - it will still be sent to the server to try.");
      item.decision = { mode: 'keep' };
      updateUploadButton();
    };
    img.src = url;
  }

  function addFiles(fileList) {
    Array.prototype.forEach.call(fileList, function (file) {
      var item = { file: file, decision: null, statusEl: null, cropBtn: null };
      queue.push(item);
      renderQueueItem(item);
      checkSquareness(item);
    });
    updateUploadButton();
  }

  dropzone.addEventListener('click', function () { fileInput.click(); });
  dropzone.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
  });
  fileInput.addEventListener('change', function () {
    addFiles(fileInput.files);
    fileInput.value = '';
  });

  ['dragover', 'dragenter'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) { e.preventDefault(); dropzone.classList.add('is-dragover'); });
  });
  ['dragleave', 'drop'].forEach(function (evt) {
    dropzone.addEventListener(evt, function (e) { e.preventDefault(); dropzone.classList.remove('is-dragover'); });
  });
  dropzone.addEventListener('drop', function (e) {
    if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (uploadBtn.disabled) return;

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    var formData = new FormData();
    formData.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
    queue.forEach(function (item) {
      formData.append('images[]', item.file, item.file.name);
      formData.append('modes[]', item.decision.mode);
      formData.append('x[]', item.decision.x || 0);
      formData.append('y[]', item.decision.y || 0);
      formData.append('size[]', item.decision.size || 0);
    });

    fetch(form.getAttribute('action'), { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.ok) {
          if (data.failures && data.failures.length) {
            window.alert('Uploaded ' + data.inserted + ' poster(s). Some files could not be processed:\n' + data.failures.join('\n'));
          }
          window.location.reload();
        } else {
          window.alert(data.error || 'Some images could not be uploaded.');
          uploadBtn.disabled = false;
          updateUploadButton();
        }
      })
      .catch(function () {
        window.alert('Upload failed. Please check your connection and try again.');
        uploadBtn.disabled = false;
        updateUploadButton();
      });
  });
})();
