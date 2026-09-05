<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$types = ['lesson' => 'Lesson', 'link' => 'Link', 'poster' => 'Poster'];
$usageTable = ['lesson' => 'lessons', 'link' => 'links', 'poster' => 'posters'];

function unique_category_slug(string $name, string $type, ?int $excludeId = null): string
{
    $slug = slugify($name);
    if ($slug === '') {
        $slug = 'category-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
    $original = $slug;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM categories WHERE type = ? AND slug = ?';
        $params = [$type, $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $i;
        $i++;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(base_url('/admin/categories.php'));
    }

    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';

    if (!isset($types[$type])) {
        flash_set('error', 'Invalid category type.');
        redirect(base_url('/admin/categories.php'));
    }

    if ($action === 'add') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            flash_set('error', 'Please enter a category name (up to 100 characters).');
        } else {
            $stmt = db()->prepare('SELECT COALESCE(MAX(display_order), 0) FROM categories WHERE type = ?');
            $stmt->execute([$type]);
            $maxOrder = (int) $stmt->fetchColumn();

            $slug = unique_category_slug($name, $type);
            $ins = db()->prepare('INSERT INTO categories (type, name, slug, display_order) VALUES (?, ?, ?, ?)');
            $ins->execute([$type, $name, $slug, $maxOrder + 1]);
            flash_set('success', ucfirst($type) . ' category added.');
        }
    } elseif ($action === 'rename') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Please enter a category name.');
        } else {
            $slug = unique_category_slug($name, $type, $id);
            $stmt = db()->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ? AND type = ?');
            $stmt->execute([$name, $slug, $id, $type]);
            flash_set('success', 'Category renamed.');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $table = $usageTable[$type];
        $countStmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE category_id = ?");
        $countStmt->execute([$id]);
        $usageCount = (int) $countStmt->fetchColumn();

        db()->prepare('DELETE FROM categories WHERE id = ? AND type = ?')->execute([$id, $type]);
        flash_set('success', 'Category deleted.' . ($usageCount > 0 ? " {$usageCount} item(s) no longer have a category (they were not deleted)." : ''));
    }

    redirect(base_url('/admin/categories.php'));
}

$allCategories = [];
foreach ($types as $type => $label) {
    $categories = get_categories($type);
    $table = $usageTable[$type];
    foreach ($categories as &$c) {
        $countStmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE category_id = ?");
        $countStmt->execute([$c['id']]);
        $c['usage_count'] = (int) $countStmt->fetchColumn();
    }
    unset($c);
    $allCategories[$type] = $categories;
}

$adminPageTitle = 'Categories';
$activeAdminNav = 'categories';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Categories</h1></div>
<p style="color:var(--color-ink-light);margin-bottom:20px;">Manage the categories used to organise Lessons, Links and Posters.</p>

<?php foreach ($types as $type => $label): ?>
<div class="admin-card">
  <h2><?php echo e($label); ?> categories</h2>

  <?php if (!$allCategories[$type]): ?>
    <p class="admin-empty">No <?php echo e(strtolower($label)); ?> categories yet.</p>
  <?php else: ?>
  <div class="table-scroll">
  <table class="admin-table">
    <thead><tr><th>Name</th><th>In use</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($allCategories[$type] as $c): ?>
      <tr>
        <td class="wrap">
          <form method="post" style="display:flex;gap:6px;align-items:center;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="type" value="<?php echo e($type); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
            <input type="text" name="name" value="<?php echo e($c['name']); ?>" style="min-height:36px;">
            <button type="submit" class="btn btn--outline btn--sm">Rename</button>
          </form>
        </td>
        <td><?php echo (int) $c['usage_count']; ?> item(s)</td>
        <td>
          <?php
          $deleteMessage = $c['usage_count'] > 0
              ? 'This category is used by ' . (int) $c['usage_count'] . ' item(s). They will lose this category, but will NOT be deleted. Continue?'
              : 'Delete this category?';
          ?>
          <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="type" value="<?php echo e($type); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
            <button type="submit" class="icon-btn-sm" title="Delete" data-confirm="<?php echo e($deleteMessage); ?>"><?php echo icon('trash'); ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>

  <form method="post" style="display:flex;gap:8px;margin-top:14px;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="type" value="<?php echo e($type); ?>">
    <input type="text" name="name" placeholder="New <?php echo e(strtolower($label)); ?> category name" style="flex:1;min-height:40px;">
    <button type="submit" class="btn btn--primary btn--sm"><?php echo icon('plus'); ?> Add</button>
  </form>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
