<?php
require_once __DIR__ . '/includes/config.php';

$learner = current_learner();

$stmt = db()->query(
    "SELECT q.*, COUNT(qq.id) AS question_count
     FROM quizzes q
     LEFT JOIN quiz_questions qq ON qq.quiz_id = q.id
     WHERE q.status = 'published'
     GROUP BY q.id
     ORDER BY q.created_at DESC"
);
$quizzes = $stmt->fetchAll();

if ($learner) {
    foreach ($quizzes as &$q) {
        $q['best'] = get_best_attempt(db(), (int) $learner['id'], (int) $q['id']);
    }
    unset($q);
}

$pageSeo = [
    'title'       => 'Quizzes',
    'description' => 'Practice quizzes to test your English - multiple choice, fill in the blank and one-word questions with instant scoring.',
];
$activeNav = 'quizzes';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">Quizzes</h1>
  <p class="page-subtitle">Test what you've learned. Instant results, every time.</p>

  <?php if (!$quizzes): ?>
    <p class="muted">No quizzes here yet. Please check back soon.</p>
  <?php else: ?>
  <div class="quiz-card-grid">
    <?php foreach ($quizzes as $q): ?>
    <a href="<?php echo e(base_url('/quiz/' . $q['slug'])); ?>" class="quiz-card">
      <p class="quiz-card__title"><?php echo e($q['title']); ?></p>
      <p class="quiz-card__meta"><?php echo e($q['topic']); ?> &middot; <?php echo (int) $q['question_count']; ?> questions</p>
      <?php if (!empty($q['best'])): ?>
        <span class="quiz-card__best">Best score: <?php echo (int) $q['best']['score']; ?>/<?php echo (int) $q['best']['total']; ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
