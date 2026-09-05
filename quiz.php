<?php
require_once __DIR__ . '/includes/config.php';

$slug = (string) ($_GET['slug'] ?? '');
$quiz = get_quiz_by_slug($slug);

if (!$quiz) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$questions = get_quiz_questions_for_taking(db(), (int) $quiz['id']);

if (!$questions) {
    flash_set('info', 'This quiz is not ready yet. Please check back soon.');
    redirect(base_url('/quizzes'));
}

$quizData = [
    'quiz_id'      => (int) $quiz['id'],
    'csrf_token'   => csrf_token(),
    'is_logged_in' => is_learner_logged_in(),
    'submit_url'   => base_url('/quiz-submit.php'),
    'register_url' => base_url('/register'),
    'quizzes_url'  => base_url('/quizzes'),
    'questions'    => array_map(static function ($q) {
        return [
            'id'               => (int) $q['id'],
            'question_type'    => $q['question_type'],
            'question_text'    => $q['question_text'],
            'accepted_answers' => $q['accepted_answers'],
            'explanation'      => $q['explanation'],
            'options'          => array_map(static function ($o) {
                return [
                    'id'          => (int) $o['id'],
                    'option_text' => $o['option_text'],
                    'is_correct'  => (bool) $o['is_correct'],
                ];
            }, $q['options']),
        ];
    }, $questions),
];

$canonical = base_url('/quiz/' . $quiz['slug']);
$pageSeo = [
    'title'       => $quiz['title'],
    'description' => $quiz['description'] ?: ('Practice quiz: ' . $quiz['title']),
    'canonical'   => $canonical,
];
$activeNav = 'quizzes';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title"><?php echo e($quiz['title']); ?></h1>
  <?php if ($quiz['description']): ?><p class="page-subtitle"><?php echo e($quiz['description']); ?></p><?php endif; ?>

  <div class="quiz-shell" id="quiz-app">
    <div class="quiz-progress-track"><div class="quiz-progress-fill" id="quiz-progress-fill" style="width:0%"></div></div>
    <p class="quiz-progress-label" id="quiz-progress-label">Question 1 of <?php echo count($questions); ?></p>
    <div id="quiz-question-container"></div>
  </div>
</div>
<script type="application/json" id="quiz-data"><?php echo json_encode($quizData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>
<?php render_json_ld(quiz_json_ld($quiz, $questions, $canonical)); ?>

<?php $extraScripts = ['/assets/js/quiz.js']; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
