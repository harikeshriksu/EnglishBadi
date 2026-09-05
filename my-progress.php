<?php
require_once __DIR__ . '/includes/config.php';
require_learner_login('/my-progress');

$learner = current_learner();
$history = get_learner_quiz_history(db(), (int) $learner['id']);

$byQuiz = [];
foreach ($history as $a) {
    $qid = (int) $a['quiz_id'];
    if (!isset($byQuiz[$qid])) {
        $byQuiz[$qid] = ['title' => $a['quiz_title'], 'slug' => $a['quiz_slug'], 'attempts' => []];
    }
    $byQuiz[$qid]['attempts'][] = $a;
}

$totalAttempts = count($history);
$distinctQuizzes = count($byQuiz);
$avgPercentage = $totalAttempts > 0 ? round(array_sum(array_column($history, 'percentage')) / $totalAttempts, 1) : 0;
$bestPercentage = $totalAttempts > 0 ? max(array_column($history, 'percentage')) : 0;

/** A tiny inline SVG line chart of percentage scores over successive attempts. */
function render_score_chart(array $percentages): string
{
    $count = count($percentages);
    if ($count < 2) {
        return '';
    }

    $width = 300;
    $height = 70;
    $padX = 8;
    $padY = 8;
    $innerW = $width - 2 * $padX;
    $innerH = $height - 2 * $padY;

    $points = [];
    foreach (array_values($percentages) as $i => $pct) {
        $x = $padX + ($count === 1 ? 0 : ($i / ($count - 1)) * $innerW);
        $y = $padY + $innerH - ((float) $pct / 100) * $innerH;
        $points[] = round($x, 1) . ',' . round($y, 1);
    }

    $circles = '';
    foreach ($points as $i => $p) {
        [$x, $y] = explode(',', $p);
        $isLast = $i === count($points) - 1;
        $circles .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . ($isLast ? 4 : 3) . '" fill="' . ($isLast ? '#3FAE4B' : '#4A5FBF') . '"></circle>';
    }

    return '<svg class="dash-chart" viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="none" role="img" aria-label="Score trend over time">'
        . '<polyline points="' . e(implode(' ', $points)) . '" fill="none" stroke="#4A5FBF" stroke-width="2"></polyline>'
        . $circles
        . '</svg>';
}

$pageSeo = [
    'title'       => 'My Progress',
    'description' => 'Track your English Badi quiz scores over time.',
    'noindex'     => true,
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">My Progress</h1>
  <p class="page-subtitle">Welcome back, <?php echo e($learner['name']); ?>.</p>

  <div class="dash-stats">
    <div class="dash-stat"><p class="dash-stat__value"><?php echo (int) $totalAttempts; ?></p><p class="dash-stat__label">Quizzes Taken</p></div>
    <div class="dash-stat"><p class="dash-stat__value"><?php echo (int) $distinctQuizzes; ?></p><p class="dash-stat__label">Different Quizzes</p></div>
    <div class="dash-stat"><p class="dash-stat__value"><?php echo e(number_format((float) $avgPercentage, 0)); ?>%</p><p class="dash-stat__label">Average Score</p></div>
    <div class="dash-stat"><p class="dash-stat__value"><?php echo e(number_format((float) $bestPercentage, 0)); ?>%</p><p class="dash-stat__label">Best Score</p></div>
  </div>

  <?php if (!$byQuiz): ?>
    <p class="muted">You haven't taken any quizzes yet. <a href="<?php echo e(base_url('/quizzes')); ?>">Browse quizzes</a> to get started.</p>
  <?php else: ?>
    <?php foreach ($byQuiz as $data):
        $attempts = $data['attempts'];
        $n = count($attempts);
    ?>
    <div class="dash-quiz-block">
      <h3><a href="<?php echo e(base_url('/quiz/' . $data['slug'])); ?>"><?php echo e($data['title']); ?></a></h3>

      <?php if ($n >= 2):
          $last = $attempts[$n - 2];
          $latest = $attempts[$n - 1];
          $diff = (float) $latest['percentage'] <=> (float) $last['percentage'];
          $verb = $diff > 0 ? "You've improved!" : ($diff === 0 ? 'Same as last time.' : 'Keep practising!');
      ?>
      <p class="dash-encourage">Last time: <?php echo (int) $last['score']; ?>/<?php echo (int) $last['total']; ?>. This time: <?php echo (int) $latest['score']; ?>/<?php echo (int) $latest['total']; ?>. <?php echo e($verb); ?></p>
      <?php echo render_score_chart(array_column($attempts, 'percentage')); ?>
      <?php endif; ?>

      <table class="dash-attempts">
        <thead><tr><th>Date</th><th>Score</th><th>Percentage</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($attempts) as $a): ?>
          <tr>
            <td><?php echo e(format_date($a['created_at'], 'd M Y, g:i A')); ?></td>
            <td><?php echo (int) $a['score']; ?>/<?php echo (int) $a['total']; ?></td>
            <td><?php echo e(number_format((float) $a['percentage'], 0)); ?>%</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
