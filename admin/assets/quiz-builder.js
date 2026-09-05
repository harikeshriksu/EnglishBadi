/**
 * Quiz question builder: add / remove / reorder question blocks of three
 * types (multiple choice, fill in the blank, one word answer) inside a
 * single form. Everything submits together in one POST - the server
 * replaces all of a quiz's questions with whatever was submitted, so
 * there is no separate save step per question.
 *
 * Existing questions (when editing) are rendered server-side in exactly
 * the same markup shape createBlock() below produces, so one set of
 * event-delegated handlers works for both.
 */
(function () {
  'use strict';

  var container = document.getElementById('questions-container');
  var addBtn = document.getElementById('add-question-btn');
  var addMenu = document.getElementById('add-question-menu-list');
  if (!container || !addBtn || !addMenu) return;

  var nextIndex = parseInt(container.getAttribute('data-next-index'), 10) || 0;

  var ICONS = {
    up: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="6 11 12 5 18 11"/></svg>',
    down: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="18 13 12 19 6 13"/></svg>',
    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>',
    close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/></svg>',
  };

  function el(tag, attrs) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') node.textContent = attrs[k];
      else if (k === 'html') node.innerHTML = attrs[k];
      else node.setAttribute(k, attrs[k]);
    });
    return node;
  }

  function typeLabel(type) {
    if (type === 'mcq') return 'Multiple Choice';
    if (type === 'fill_blank') return 'Fill in the Blank';
    return 'One Word Answer';
  }

  function renumberOrders() {
    Array.prototype.forEach.call(container.querySelectorAll('[data-question-block]'), function (block, i) {
      var orderInput = block.querySelector('[data-order-input]');
      if (orderInput) orderInput.value = i + 1;
    });
  }

  function createAnswerRow(prefix) {
    var row = el('div', { class: 'quiz-accepted-answer-row' });
    row.appendChild(el('input', { type: 'text', name: prefix + '[answers][]', required: '', placeholder: 'Accepted answer' }));
    row.appendChild(el('button', { type: 'button', class: 'icon-btn-sm', title: 'Remove this answer', 'data-remove-answer': '', html: ICONS.close }));
    return row;
  }

  function createBlock(type) {
    var index = nextIndex++;
    var prefix = 'questions[' + index + ']';

    var block = el('div', { class: 'quiz-question-block', 'data-question-block': '', 'data-index': String(index) });

    var head = el('div', { class: 'quiz-question-block__head' });
    head.appendChild(el('span', { class: 'quiz-question-block__type', text: typeLabel(type) }));
    var orderWrap = el('div', { class: 'quiz-question-block__order' });
    orderWrap.appendChild(el('button', { type: 'button', class: 'icon-btn-sm', title: 'Move up', 'data-move-up': '', html: ICONS.up }));
    orderWrap.appendChild(el('button', { type: 'button', class: 'icon-btn-sm', title: 'Move down', 'data-move-down': '', html: ICONS.down }));
    orderWrap.appendChild(el('button', { type: 'button', class: 'icon-btn-sm', title: 'Delete question', 'data-remove-question': '', html: ICONS.trash }));
    head.appendChild(orderWrap);
    block.appendChild(head);

    block.appendChild(el('input', { type: 'hidden', name: prefix + '[type]', value: type }));
    block.appendChild(el('input', { type: 'hidden', name: prefix + '[order]', value: '0', 'data-order-input': '' }));

    var textField = el('div', { class: 'form-field' });
    textField.appendChild(el('label', { text: 'Question text' }));
    textField.appendChild(el('textarea', {
      name: prefix + '[text]', rows: '2', required: '',
      placeholder: type === 'fill_blank' ? 'Use ___ where the blank goes, e.g. "I ___ to school."' : 'Type the question',
    }));
    if (type === 'fill_blank') {
      textField.appendChild(el('p', { class: 'form-hint', text: 'Use ___ where the blank goes.' }));
    }
    block.appendChild(textField);

    if (type === 'mcq') {
      var optField = el('div', { class: 'form-field' });
      optField.appendChild(el('label', { text: 'Options (select the correct one)' }));
      for (var i = 0; i < 4; i++) {
        var row = el('div', { class: 'quiz-option-row' });
        var radio = el('input', { type: 'radio', name: prefix + '[correct]', value: String(i) });
        if (i === 0) radio.checked = true;
        row.appendChild(radio);
        row.appendChild(el('input', { type: 'text', name: prefix + '[options][' + i + ']', required: '', placeholder: 'Option ' + (i + 1) }));
        optField.appendChild(row);
      }
      block.appendChild(optField);
    } else {
      var ansField = el('div', { class: 'form-field' });
      ansField.appendChild(el('label', { text: 'Accepted answer(s)' }));
      var list = el('div', { 'data-answers-list': '' });
      list.appendChild(createAnswerRow(prefix));
      ansField.appendChild(list);
      ansField.appendChild(el('button', { type: 'button', class: 'btn btn--outline btn--sm', 'data-add-answer': '', text: '+ Add another accepted answer' }));
      ansField.appendChild(el('p', { class: 'form-hint', text: 'Add spelling variants separately, e.g. "colour" and "color".' }));
      block.appendChild(ansField);
    }

    var explField = el('div', { class: 'form-field' });
    explField.appendChild(el('label', { text: 'Explanation (optional)' }));
    explField.appendChild(el('textarea', { name: prefix + '[explanation]', rows: '2', placeholder: 'Shown to the learner after they answer' }));
    block.appendChild(explField);

    return block;
  }

  // ---- Add Question menu ----
  addBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    addMenu.classList.toggle('is-open');
  });
  document.addEventListener('click', function () { addMenu.classList.remove('is-open'); });

  Array.prototype.forEach.call(addMenu.querySelectorAll('[data-add-type]'), function (btn) {
    btn.addEventListener('click', function () {
      var note = document.getElementById('no-questions-note');
      if (note) note.remove();

      var block = createBlock(btn.getAttribute('data-add-type'));
      container.appendChild(block);
      renumberOrders();
      block.scrollIntoView({ behavior: 'smooth', block: 'center' });
      addMenu.classList.remove('is-open');
    });
  });

  // ---- Event delegation for everything inside a question block ----
  container.addEventListener('click', function (e) {
    var target = e.target.closest('button');
    if (!target) return;
    var block = target.closest('[data-question-block]');

    if (target.hasAttribute('data-move-up') && block) {
      var prev = block.previousElementSibling;
      if (prev) { container.insertBefore(block, prev); renumberOrders(); }
    } else if (target.hasAttribute('data-move-down') && block) {
      var next = block.nextElementSibling;
      if (next) { container.insertBefore(next, block); renumberOrders(); }
    } else if (target.hasAttribute('data-remove-question') && block) {
      if (window.confirm('Remove this question?')) {
        block.remove();
        renumberOrders();
      }
    } else if (target.hasAttribute('data-add-answer') && block) {
      var list = block.querySelector('[data-answers-list]');
      var prefix = 'questions[' + block.getAttribute('data-index') + ']';
      list.appendChild(createAnswerRow(prefix));
    } else if (target.hasAttribute('data-remove-answer')) {
      var row = target.closest('.quiz-accepted-answer-row');
      var list2 = row.parentElement;
      if (list2.children.length > 1) row.remove();
      else window.alert('A question needs at least one accepted answer.');
    }
  });

  var form = container.closest('form');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (container.querySelectorAll('[data-question-block]').length === 0) {
        e.preventDefault();
        window.alert('Please add at least one question before saving.');
      }
    });
  }
})();
