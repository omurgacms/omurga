<?php $u=current_user(); ?>
<section class="omg-core-block omg-profile-summary">
  <?php if($u): ?><strong><?=e($u['name'] ?? $u['username'] ?? '')?></strong><small><?=e($u['role'] ?? '')?></small><?php else: ?><a href="<?=e(omurga_url('admin/login.php'))?>"><?=e($settings['login_text'] ?? 'Giris yap')?></a><?php endif; ?>
</section>
