  </main>
</div>
<script src="<?php echo e(asset_url('/admin/assets/admin.js')); ?>" defer></script>
<?php foreach ($extraScripts ?? [] as $script): ?>
<script src="<?php echo e(asset_url($script)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
