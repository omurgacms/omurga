<?php
$title=trim((string)($settings['title'] ?? '')) ?: om_t('blocks.auth_box','Uye Paneli');
$user=function_exists('current_user') ? current_user() : null;
$login=trim((string)($settings['login_url'] ?? 'admin/login.php')) ?: 'admin/login.php';
$register=trim((string)($settings['register_url'] ?? ''));
$loginUrl=str_starts_with($login,'http') ? $login : omurga_url($login);
$registerUrl=$register!=='' ? (str_starts_with($register,'http') ? $register : omurga_url($register)) : '';
?>
<section class="omg-core-block omg-auth-box">
  <h2><?=e($title)?></h2>
  <?php if($user): ?>
    <p><strong><?=e($user['username'] ?? $user['email'] ?? om_t('blocks.profile','Profilim'))?></strong></p>
    <div class="omg-auth-actions">
      <a href="<?=e(omurga_url('admin/profile.php'))?>"><?=e(om_t('blocks.profile','Profilim'))?></a>
      <?php if(can('posts.view') || can('layout.manage') || can('themes.manage')): ?><a href="<?=e(omurga_url('admin/'))?>"><?=e(om_t('blocks.admin_panel','Yonetim Paneli'))?></a><?php endif; ?>
      <a href="<?=e(omurga_url('admin/logout.php'))?>"><?=e(om_t('blocks.logout','Cikis'))?></a>
    </div>
  <?php else: ?>
    <div class="omg-auth-actions">
      <a href="<?=e($loginUrl)?>"><?=e(om_t('blocks.login','Giris'))?></a>
      <?php if(!empty($settings['show_register']) && $registerUrl!==''): ?><a href="<?=e($registerUrl)?>"><?=e(om_t('blocks.register','Kayit'))?></a><?php endif; ?>
    </div>
  <?php endif; ?>
</section>
