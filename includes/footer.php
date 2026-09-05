</main>
<footer class="site-footer">
  <div class="site-footer__inner">
    <nav class="site-footer__links" aria-label="Footer">
      <a href="<?php echo e(base_url('/about')); ?>">About</a>
      <a href="<?php echo e(base_url('/contact')); ?>">Contact</a>
      <a href="<?php echo e(base_url('/privacy')); ?>">Privacy Policy</a>
      <a href="<?php echo e(base_url('/terms')); ?>">Terms</a>
    </nav>
    <?php
    $igUrl = setting('social_instagram');
    $ytUrl = setting('social_youtube');
    $fbUrl = setting('social_facebook');
    if ($igUrl || $ytUrl || $fbUrl):
    ?>
    <div class="site-footer__social">
      <?php if ($igUrl): ?><a href="<?php echo e($igUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?php echo icon('instagram'); ?></a><?php endif; ?>
      <?php if ($ytUrl): ?><a href="<?php echo e($ytUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><?php echo icon('youtube'); ?></a><?php endif; ?>
      <?php if ($fbUrl): ?><a href="<?php echo e($fbUrl); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?php echo icon('facebook'); ?></a><?php endif; ?>
    </div>
    <?php endif; ?>
    <p class="site-footer__copyright">&copy; <?php echo date('Y'); ?> <?php echo e(setting('site_title', 'English Badi')); ?>. All rights reserved.</p>
  </div>
</footer>
<script src="<?php echo e(base_url('/assets/js/main.js')); ?>" defer></script>
<?php foreach ($extraScripts ?? [] as $script): ?>
<script src="<?php echo e(base_url($script)); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
