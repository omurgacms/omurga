<?php
$title=trim((string)($settings['title'] ?? '')) ?: om_t('blocks.auth_box','Üye Paneli');
$user=function_exists('current_user') ? current_user() : null;
$login=trim((string)($settings['login_url'] ?? 'admin/login.php?tab=login')) ?: 'admin/login.php?tab=login';
$register=trim((string)($settings['register_url'] ?? 'admin/login.php?tab=register')) ?: 'admin/login.php?tab=register';
$forgot='admin/login.php?tab=forgot';
$loginUrl=str_starts_with($login,'http') ? $login : omurga_url($login);
$registerUrl=str_starts_with($register,'http') ? $register : omurga_url($register);
$forgotUrl=omurga_url($forgot);
$profileUrl=function_exists('omurga_user_center_url') ? omurga_user_center_url('profile') : omurga_url('hesabim');
$registrationEnabled=function_exists('setting') ? setting('membership_registration_enabled','0')==='1' : false;
?>
<section class="omg-core-block omg-auth-box">
  <h2><?=e($title)?></h2>
  <?php if($user): ?>
    <p><strong><?=e($user['name'] ?? $user['username'] ?? $user['email'] ?? om_t('blocks.profile','Profilim'))?></strong></p>
    <small><?=e($user['role'] ?? '')?></small>
    <div class="omg-auth-actions">
      <a href="<?=e($profileUrl)?>"><?=e(om_t('blocks.profile','Profilim'))?></a>
      <?php if(can('posts.view') || can('layout.manage') || can('themes.manage')): ?><a href="<?=e(omurga_url('admin/'))?>"><?=e(om_t('blocks.admin_panel','Yönetim Paneli'))?></a><?php endif; ?>
      <a href="<?=e(omurga_url('admin/logout.php'))?>"><?=e(om_t('blocks.logout','Çıkış'))?></a>
    </div>
  <?php else: ?>
    <div class="omg-auth-actions">
      <a href="<?=e($loginUrl)?>"><?=e(om_t('blocks.login','Giriş'))?></a>
      <?php if(!empty($settings['show_register']) && $registrationEnabled): ?><a href="<?=e($registerUrl)?>"><?=e(om_t('blocks.register','Kayıt'))?></a><?php endif; ?>
      <a href="<?=e($forgotUrl)?>">Şifremi Unuttum</a>
    </div>
  <?php endif; ?>
</section>
