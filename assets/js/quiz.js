/**
 * Quiz-taking engine. Reads quiz data (including correct answers, so
 * each question can be graded instantly without a network round-trip)
 * from a JSON script tag, then renders one question at a time.
 *
 * The final score shown and stored always comes from the server
 * (quiz-submit.php re-grades from the database) - the running local
 * tally is only used as a same-page fallback if that request fails.
 */
(function () {
  'use strict';

  var dataEl = document.getElementById('quiz-data');
  var root = document.getElementById('quiz-app');
  if (!dataEl || !root) return;

  var quiz = JSON.parse(dataEl.textContent);
  var questions = quiz.questions;
  var total = questions.length;

  var progressFill = document.getElementById('quiz-progress-fill');
  var progressLabel = document.getElementById('quiz-progress-label');
  var questionContainer = document.getElementById('quiz-question-container');

  var state = {
    index: 0,
    answers: {},
    results: [],
    localScore: 0,
  };

  var TELUGU_RE = /[ఀ-౿]/;

  function textNode(text, tag) {
    var el = document.createElement(tag || 'span');
    el.textContent = text == null ? '' : String(text);
    if (TELUGU_RE.test(el.textContent)) {
      el.setAttribute('lang', 'te');
      el.classList.add('te');
    }
    return el;
  }

  function normalize(s) {
    return String(s == null ? '' : s).trim().replace(/\s+/g, ' ').toLowerCase();
  }

  function answerMatches(given, acceptedPipeList) {
    var g = normalize(given);
    if (!g) return false;
    return String(acceptedPipeList || '').split('|').some(function (a) {
      return a.trim() !== '' && normalize(a) === g;
    });
  }

  function clear(el) {
    while (el.firstChild) el.removeChild(el.firstChild);
  }

  function updateProgress() {
    var pct = Math.round((state.index / total) * 100);
    progressFill.style.width = pct + '%';
    progressLabel.textContent = 'Question ' + (state.index + 1) + ' of ' + total;
  }

  function renderQuestion() {
    updateProgress();
    clear(questionContainer);

    var q = questions[state.index];
    var card = document.createElement('div');
    card.className = 'quiz-question-card';

    card.appendChild(textNode(q.question_text, 'p'));
    card.firstChild.className = 'quiz-question-text';

    var answerHost = document.createElement('div');
    card.appendChild(answerHost);

    var actions = document.createElement('div');
    actions.className = 'quiz-actions';
    var primaryBtn = document.createElement('button');
    primaryBtn.type = 'button';
    primaryBtn.className = 'btn btn--primary';
    primaryBtn.textContent = 'Check';
    primaryBtn.disabled = true;
    actions.appendChild(primaryBtn);
    card.appendChild(actions);

    questionContainer.appendChild(card);

    var hasAnswer = function () {
      var v = state.answers[q.id];
      return v !== undefined && v !== null && String(v).trim() !== '';
    };

    if (q.question_type === 'mcq') {
      buildMcqOptions(q, answerHost, primaryBtn, hasAnswer);
    } else {
      buildTextAnswer(q, answerHost, primaryBtn, hasAnswer);
    }

    primaryBtn.addEventListener('click', function () {
      if (primaryBtn.dataset.state === 'next') {
        goNext();
      } else {
        checkAnswer(q, answerHost, primaryBtn);
      }
    });
  }

  function buildMcqOptions(q, host, primaryBtn, hasAnswer) {
    var group = document.createElement('div');
    group.className = 'quiz-options';

    q.options.forEach(function (opt) {
      var label = document.createElement('label');
      label.className = 'quiz-option';
      label.dataset.optionId = String(opt.id);

      var input = document.createElement('input');
      input.type = 'radio';
      input.name = 'quiz-option-' + q.id;
      input.value = String(opt.id);

      input.addEventListener('change', function () {
        state.answers[q.id] = opt.id;
        primaryBtn.disabled = !hasAnswer();
        Array.prototype.forEach.call(group.querySelectorAll('.quiz-option'), function (l) {
          l.classList.toggle('is-selected', l === label);
        });
      });

      label.appendChild(input);
      label.appendChild(textNode(opt.option_text));
      group.appendChild(label);
    });

    host.appendChild(group);
  }

  function buildTextAnswer(q, host, primaryBtn, hasAnswer) {
    var wrap = document.createElement('div');
    wrap.className = 'quiz-text-answer';

    var input = document.createElement('input');
    input.type = 'text';
    input.autocomplete = 'off';
    input.setAttribute('aria-label', 'Your answer');
    input.placeholder = q.question_type === 'fill_blank' ? 'Type the missing word or words' : 'Type one word';
    if (TELUGU_RE.test(q.question_text)) input.setAttribute('lang', 'te');

    input.addEventListener('input', function () {
      state.answers[q.id] = input.value;
      primaryBtn.disabled = !hasAnswer();
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (!primaryBtn.disabled) primaryBtn.click();
      }
    });

    wrap.appendChild(input);
    host.appendChild(wrap);
    window.setTimeout(function () { input.focus(); }, 0);
  }

  function checkAnswer(q, host, primaryBtn) {
    var given = state.answers[q.id];
    var isCorrect = false;
    var givenDisplay = '';
    var correctDisplay = '';

    if (q.question_type === 'mcq') {
      var correctOpt = q.options.filter(function (o) { return o.is_correct; })[0];
      var pickedOpt = q.options.filter(function (o) { return String(o.id) === String(given); })[0];
      correctDisplay = correctOpt ? correctOpt.option_text : '';
      givenDisplay = pickedOpt ? pickedOpt.option_text : '';
      isCorrect = !!pickedOpt && !!pickedOpt.is_correct;

      Array.prototype.forEach.call(host.querySelectorAll('.quiz-option'), function (label) {
        var input = label.querySelector('input');
        input.disabled = true;
        var isThisCorrect = q.options.some(function (o) { return String(o.id) === label.dataset.optionId && o.is_correct; });
        if (isThisCorrect) {
          label.classList.add('is-correct');
        } else if (label.dataset.optionId === String(given)) {
          label.classList.add('is-incorrect');
        }
      });
    } else {
      isCorrect = answerMatches(given, q.accepted_answers);
      correctDisplay = String(q.accepted_answers || '').split('|')[0].trim();
      givenDisplay = given || '';
      var input = host.querySelector('.quiz-text-answer input');
      input.disabled = true;
      input.classList.add(isCorrect ? 'is-correct' : 'is-incorrect');
    }

    if (isCorrect) state.localScore++;

    state.results.push({
      question_id: q.id,
      question_text: q.question_text,
      question_type: q.question_type,
      given_answer: givenDisplay,
      correct_answer: correctDisplay,
      is_correct: isCorrect,
      explanation: q.explanation,
    });

    renderFeedback(host, isCorrect, q.explanation);

    primaryBtn.dataset.state = 'next';
    primaryBtn.textContent = state.index === total - 1 ? 'See results' : 'Next';
    primaryBtn.disabled = false;
  }

  function renderFeedback(host, isCorrect, explanation) {
    var box = document.createElement('div');
    box.className = 'quiz-feedback ' + (isCorrect ? 'quiz-feedback--correct' : 'quiz-feedback--incorrect');
    box.appendChild(textNode(isCorrect ? 'Correct!' : 'Not quite.', 'strong'));
    if (explanation) {
      box.appendChild(textNode(explanation));
    }
    host.appendChild(box);
  }

  function goNext() {
    if (state.index < total - 1) {
      state.index++;
      renderQuestion();
    } else {
      submitAttempt();
    }
  }

  function submitAttempt() {
    clear(questionContainer);
    progressFill.style.width = '100%';
    progressLabel.textContent = 'Finishing up...';

    var payload = {
      quiz_id: quiz.quiz_id,
      csrf_token: quiz.csrf_token,
      answers: state.answers,
    };

    fetch(quiz.submit_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
    })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('bad response')); })
      .then(function (data) {
        renderEnd(data.score, data.total, data.percentage, data.results, data.previous, data.saved);
      })
      .catch(function () {
        // Network problem: fall back to the score tallied in the browser
        // so the learner still sees their result.
        var total2 = total;
        var pct = total2 > 0 ? Math.round((state.localScore / total2) * 100) : 0;
        renderEnd(state.localScore, total2, pct, state.results, null, false);
      });
  }

  function renderEnd(score, totalQ, percentage, results, previous, saved) {
    clear(questionContainer);
    progressFill.style.width = '100%';
    progressLabel.textContent = 'Complete';

    var wrap = document.createElement('div');
    wrap.className = 'quiz-end';

    var scoreEl = document.createElement('p');
    scoreEl.className = 'quiz-end__score';
    scoreEl.textContent = score + ' / ' + totalQ;
    wrap.appendChild(scoreEl);

    var pctEl = document.createElement('p');
    pctEl.className = 'quiz-end__percent';
    pctEl.textContent = percentage + '% correct';
    wrap.appendChild(pctEl);

    if (previous) {
      var compare = document.createElement('p');
      compare.className = 'quiz-end__compare';
      var prevScore = Number(previous.score);
      var line = 'Last time: ' + prevScore + '/' + previous.total + '. This time: ' + score + '/' + totalQ + '.';
      if (score > prevScore) line += " You've improved!";
      else if (score === prevScore) line += ' Same as last time.';
      else line += ' Keep practising!';
      compare.textContent = line;
      wrap.appendChild(compare);
    } else if (quiz.is_logged_in && saved) {
      var firstTime = document.createElement('p');
      firstTime.className = 'quiz-end__compare';
      firstTime.textContent = 'First attempt recorded. Take it again any time to beat your score.';
      wrap.appendChild(firstTime);
    }

    if (!quiz.is_logged_in) {
      wrap.appendChild(buildRegisterNudge());
    }

    var review = document.createElement('div');
    review.className = 'quiz-review';
    results.forEach(function (r, i) {
      var item = document.createElement('div');
      item.className = 'quiz-review__item ' + (r.is_correct ? 'is-right' : 'is-wrong');

      item.appendChild(textNode((i + 1) + '. ' + r.question_text, 'p'));
      item.lastChild.className = 'quiz-review__q';

      var yourAnswer = document.createElement('p');
      yourAnswer.className = 'quiz-review__a';
      yourAnswer.appendChild(document.createTextNode('Your answer: '));
      yourAnswer.appendChild(textNode(r.given_answer || '(no answer)'));
      item.appendChild(yourAnswer);

      if (!r.is_correct) {
        var correctAnswer = document.createElement('p');
        correctAnswer.className = 'quiz-review__a';
        correctAnswer.appendChild(document.createTextNode('Correct answer: '));
        correctAnswer.appendChild(textNode(r.correct_answer));
        item.appendChild(correctAnswer);
      }

      if (r.explanation) {
        var expl = textNode(r.explanation, 'p');
        expl.className = 'quiz-review__explain';
        item.appendChild(expl);
      }

      review.appendChild(item);
    });
    wrap.appendChild(review);

    var actions = document.createElement('div');
    actions.className = 'form-actions';
    var retryBtn = document.createElement('a');
    retryBtn.href = window.location.href;
    retryBtn.className = 'btn btn--primary';
    retryBtn.textContent = 'Retry this quiz';
    actions.appendChild(retryBtn);

    var backBtn = document.createElement('a');
    backBtn.href = quiz.quizzes_url;
    backBtn.className = 'btn btn--outline';
    backBtn.textContent = 'More quizzes';
    actions.appendChild(backBtn);
    wrap.appendChild(actions);

    questionContainer.appendChild(wrap);
  }

  function buildRegisterNudge() {
    var COOKIE_NAME = 'eb_hide_register_nudge';

    var card = document.createElement('div');
    card.className = 'quiz-register-nudge';

    var msg = document.createElement('p');
    msg.textContent = 'Register to keep track of your scores over time.';
    card.appendChild(msg);

    var link = document.createElement('a');
    link.href = quiz.register_url;
    link.className = 'btn btn--accent btn--sm';
    link.textContent = 'Register';
    card.appendChild(link);

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'quiz-register-nudge__close';
    closeBtn.setAttribute('aria-label', 'Dismiss');
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', function () {
      var expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
      document.cookie = COOKIE_NAME + '=1; expires=' + expires + '; path=/; SameSite=Lax';
      card.remove();
    });
    card.appendChild(closeBtn);

    if (document.cookie.indexOf(COOKIE_NAME + '=') !== -1) {
      card.hidden = true;
    }

    return card;
  }

  renderQuestion();
})();
