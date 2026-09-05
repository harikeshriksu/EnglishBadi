<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$pages = db()->query('SELECT * FROM pages ORDER BY FIELD(slug, "start-here", "about", "contact", "privacy", "terms")')->fetchAll();

$adminPageTitle = 'Pages';
$activeAdminNav = 'pages';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Pages</h1></div>
<p style="color:var(--color-ink-light);margin-bottom:20px;">These are the site's fixed content pages. Edit their text below - the page addresses themselves do not change.</p>

<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th>Page</th><th>Address</th><th>Last updated</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($pages as $p): ?>
    <tr>
      <td class="wrap"><?php echo e($p['title']); ?></td>
      <td>/<?php echo e($p['slug']); ?></td>
      <td><?php echo e(format_date($p['updated_at'], 'd M Y, g:i A')); ?></td>
      <td><a href="<?php echo e(base_url('/admin/page-form.php?id=' . $p['id'])); ?>" class="icon-btn-sm" title="Edit"><?php echo icon('edit'); ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
