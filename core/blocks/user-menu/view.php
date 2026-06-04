<?php $u=current_user(); ?>
<nav class="omg-core-block omg-user-menu">
  <?php if($u): ?><a href="<?=e(omurga_url('admin/'))?>"><?=e(om_t('blocks.admin_panel','Yonetim Paneli'))?></a><a href="<?=e(omurga_url('admin/logout.php'))?>"><?=e(om_t('blocks.logout','Cikis'))?></a><?php else: ?><a href="<?=e(omurga_url('admin/login.php'))?>"><?=e(om_t('blocks.login','Giris'))?></a><?php endif; ?>
</nav>
