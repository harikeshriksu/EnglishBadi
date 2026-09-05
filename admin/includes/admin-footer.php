  </main>
</div>
<script src="<?php echo e(base_url('/admin/assets/admin.js')); ?>" defer></script>
<?php foreach ($extraScripts ?? [] as $script): ?>
<script src="<?php echo e(base_url($script)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
