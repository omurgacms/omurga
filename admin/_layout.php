<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_admin();
omurga_do_action('omurga_admin_loaded', basename($_SERVER['SCRIPT_NAME'] ?? ''));

$current = basename($_SERVER['SCRIPT_NAME']);
$st = site_type();
$user = current_user();
$adminName = (string)($user['name'] ?? 'Yönetici');
$adminRole = current_user_role();
$adminInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr(trim($adminName) !== '' ? trim($adminName) : 'Yönetici', 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr(trim($adminName) !== '' ? trim($adminName) : 'Yönetici', 0, 1));
$panelTitle = 'Omurga Panel';

function nav_active($files){
  global $current;
  return in_array($current, (array)$files, true) ? 'active' : '';
}

function omg_nav_item($href, $label, $icon, $activeFiles = null){
  $active = nav_active($activeFiles ?: basename(parse_url($href, PHP_URL_PATH)));
  echo '<a class="omg-acc-link '.$active.'" href="'.e($href).'"><span class="nav-ico">'.$icon.'</span><span>'.e($label).'</span><i>›</i></a>';
}

function omg_group_is_active(array $files){
  global $current;
  return in_array($current, $files, true);
}

function omg_nav_group($id, $label, $icon, array $files, callable $callback){
  $open = omg_group_is_active($files) ? ' open' : '';
  echo '<section class="omg-nav-group'.$open.'" data-group="'.e($id).'">';
  echo '<button type="button" class="omg-nav-head" onclick="omurgaToggleNavGroup(this)"><span><b>'.$icon.'</b>'.e($label).'</span><em>⌄</em></button>';
  echo '<div class="omg-nav-body">';
  $callback();
  echo '</div></section>';
}
?>
<!doctype html>
<html lang="<?=e(omurga_admin_language())?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=e(om_t('admin.dashboard','Başlangıç'))?> - Omurga</title>
  <link rel="stylesheet" href="../assets/css/omurga.css?v=1.0.0-beta">
</head>
<body class="omurga-admin-page dle-skin v33-compact-admin v334-accordion-admin">
<script>
(function(){
  try {
    if (window.matchMedia && !window.matchMedia('(max-width:900px)').matches && localStorage.getItem('omurga_admin_sidebar_collapsed') === '1') {
      document.body.classList.add('nav-collapsed');
    }
  } catch(e) {}
})();
function omurgaToggleSidebar(){
  if (window.matchMedia && window.matchMedia('(max-width:900px)').matches) {
    document.body.classList.toggle('nav-open');
    return;
  }
  document.body.classList.toggle('nav-collapsed');
  try {
    localStorage.setItem('omurga_admin_sidebar_collapsed', document.body.classList.contains('nav-collapsed') ? '1' : '0');
  } catch(e) {}
}
function omurgaToggleNavGroup(btn){
  var group = btn.closest('.omg-nav-group');
  if (!group) return;
  group.classList.toggle('open');
  try {
    var states = JSON.parse(localStorage.getItem('omurga_nav_groups') || '{}');
    states[group.getAttribute('data-group')] = group.classList.contains('open') ? 1 : 0;
    localStorage.setItem('omurga_nav_groups', JSON.stringify(states));
  } catch(e) {}
}

function omurgaCloseUserMenu(){
  var wrap = document.querySelector('.dle-user-wrap');
  var btn = document.getElementById('omurgaUserMenuBtn');
  if(wrap) wrap.classList.remove('open');
  if(btn) btn.setAttribute('aria-expanded','false');
}
function omurgaToggleUserMenu(){
  var wrap = document.querySelector('.dle-user-wrap');
  var btn = document.getElementById('omurgaUserMenuBtn');
  if(!wrap || !btn) return;
  var open = !wrap.classList.contains('open');
  wrap.classList.toggle('open', open);
  btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
document.addEventListener('DOMContentLoaded', function(){
  var userBtn = document.getElementById('omurgaUserMenuBtn');
  if(userBtn){
    userBtn.addEventListener('click', function(e){ e.stopPropagation(); omurgaToggleUserMenu(); });
  }
  document.addEventListener('click', function(e){
    if(!e.target.closest('.dle-user-wrap')) omurgaCloseUserMenu();
  });
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') omurgaCloseUserMenu();
  });
  try {
    var states = JSON.parse(localStorage.getItem('omurga_nav_groups') || '{}');
    document.querySelectorAll('.omg-nav-group').forEach(function(group){
      var id = group.getAttribute('data-group');
      if (Object.prototype.hasOwnProperty.call(states, id)) {
        group.classList.toggle('open', states[id] === 1);
      }
      if (group.querySelector('.omg-acc-link.active')) group.classList.add('open');
    });
  } catch(e) {}
});
</script>
<div class="dle-topbar">
  <div class="dle-brand"><img src="../assets/images/omurga-logo.png" alt="Omurga"></div>
  <button class="menu-toggle" type="button" aria-label="Menüyü daralt/aç" onclick="omurgaToggleSidebar()">☰</button>
  <div class="dle-search"><span>⌕</span><input type="search" placeholder="<?=e(om_t('admin.search_placeholder','Ara (içerik, kullanıcı, dosya... )'))?>" aria-label="<?=e(om_t('theme.search','Arama'))?>"></div>
  <div class="dle-icons">
    <a href="../" target="_blank" title="<?=e(om_t('admin.view_site','Siteyi Gör'))?>">▣</a>
    <a href="notifications.php" title="<?=e(om_t('admin.notifications',om_t('admin.notifications','Bildirimler')))?>">🔔<?php $nc=omurga_unread_notification_count((int)($user['id'] ?? 0)); if($nc>0): ?><em><?=e($nc)?></em><?php endif; ?></a>
    <a href="forms.php" title="<?=e(om_t('admin.forms',om_t('admin.forms','Formlar')))?>">✉</a>
    <a href="system.php" title="<?=e(om_t('admin.system',om_t('admin.system','Sistem')))?>">⚙</a>
  </div>
  <div class="dle-user-wrap">
    <button class="dle-user" type="button" id="omurgaUserMenuBtn" aria-haspopup="true" aria-expanded="false">
      <span class="avatar"><?=e($adminInitial)?></span>
      <div><b><?=e($adminName)?></b><small><?=e($adminRole)?></small></div>
      <span class="user-caret">⌄</span>
    </button>
    <div class="dle-user-menu" id="omurgaUserMenu" role="menu" aria-labelledby="omurgaUserMenuBtn">
      <a role="menuitem" href="users.php">Profilim</a>
      <a role="menuitem" href="settings.php">Hesap Ayarları</a>
      <a role="menuitem" href="notifications.php">Bildirimler</a>
      <a role="menuitem" class="danger" href="logout.php">Oturumu Kapat</a>
    </div>
  </div>
</div>
<div class="admin-shell dle-shell">
<aside class="sidebar dle-sidebar">
<nav class="nav dle-nav omg-accordion-nav">
  <div class="nav-title">OMURGA</div>
  <?php omg_nav_item('index.php',om_t('admin.dashboard','Başlangıç'),'⌂','index.php'); ?>

  <?php omg_nav_group('content',om_t('admin.content','İçerik'),'▤',['addnews.php','post-edit.php','page-edit.php','posts.php','pages.php','categories.php','tags.php','comments.php'], function(){ ?>
    <?php $ptype = primary_content_type(); ?>
    <?php omg_nav_item('post-edit.php?type='.rawurlencode($ptype), content_quick_add_label(), '✚', ['addnews.php','post-edit.php']); ?>
    <?php omg_nav_item('posts.php?type='.rawurlencode($ptype), content_label_plural(), '▤', 'posts.php'); ?>
    <?php omg_nav_item('pages.php',om_t('pages.static','Statik Sayfalar'),'▦',['pages.php','page-edit.php']); ?>
    <?php omg_nav_item('comments.php',om_t('comments.title','Yorumlar'),'☷','comments.php'); ?>
    <?php omg_nav_item('categories.php',content_category_label(),'▣','categories.php'); ?>
    <?php omg_nav_item('tags.php',content_tag_label(),'⌗','tags.php'); ?>
  <?php }); ?>

  <?php omg_nav_group('design',om_t('admin.design','Tasarım'),'▨',['themes.php','theme-editor.php','layout.php','layout-header-footer.php','templates.php','blocks.php','design.php','menus.php','ads.php'], function(){ ?>
    <?php omg_nav_item('themes.php',om_t('admin.themes','Temalar'),'▨','themes.php'); ?>
    <?php omg_nav_item('theme-editor.php',om_t('admin.theme_editor','Tema Düzenleyici'),'⌨','theme-editor.php'); ?>
    <?php omg_nav_item('layout.php',om_t('admin.layout','Düzen'),'▦',['layout.php','layout-header-footer.php']); ?>
    <?php omg_nav_item('layout-header-footer.php',om_t('admin.header_footer','Header / Footer'),'▥','layout-header-footer.php'); ?>
    <?php omg_nav_item('templates.php',om_t('admin.templates','Şablonlar'),'▤','templates.php'); ?>
    <?php omg_nav_item('blocks.php',om_t('admin.blocks','Bloklar'),'▩','blocks.php'); ?>
    <?php omg_nav_item('menus.php',om_t('admin.menu_manager','Menü Yönetimi'),'☰','menus.php'); ?>
    <?php omg_nav_item('ads.php',om_t('admin.ads','Reklam Alanları'),'▰','ads.php'); ?>
    <?php omg_nav_item('design.php',om_t('admin.theme_settings','Tema Ayarları'),'✎','design.php'); ?>
  <?php }); ?>

  <?php omg_nav_group('media',om_t('admin.media','Medya'),'▧',['media.php','media-webp.php','media-unused.php'], function(){ ?>
    <?php omg_nav_item('media.php',om_t('admin.media_library','Medya Kütüphanesi'),'▧','media.php'); ?>
    <?php omg_nav_item('media-webp.php',om_t('admin.webp_convert','WebP Dönüştür'),'◇','media-webp.php'); ?>
    <?php omg_nav_item('media-unused.php',om_t('admin.unused_files','Kullanılmayan Dosyalar'),'⌫','media-unused.php'); ?>
  <?php }); ?>

  <?php omg_nav_group('forms',om_t('admin.forms','Formlar'),'☷',['forms.php'], function(){ ?>
    <?php omg_nav_item('forms.php',om_t('admin.forms_submissions','Formlar ve Başvurular'),'☷','forms.php'); ?>
  <?php }); ?>

  <?php if(can('plugins.manage') || current_user_role()==='admin'): ?>
  <?php omg_nav_group('plugins',om_t('admin.plugins','Paketler'),'▣',['plugins.php','packages.php','plugin-page.php'], function(){ ?>
    <?php omg_nav_item('plugins.php',om_t('admin.loaded_plugins','Eski Eklentiler'),'▣','plugins.php'); ?>
    <?php omg_nav_item('packages.php','Paketler','▧','packages.php'); ?>
    <?php foreach(omurga_plugin_admin_pages() as $pp): ?>
      <?php if(can($pp['cap'] ?? 'plugins.manage') || current_user_role()==='admin'): ?>
        <?php omg_nav_item('plugin-page.php?plugin='.urlencode($pp['plugin'] ?? 'registered').'&page='.urlencode($pp['id'] ?? ''), $pp['menu_title'] ?? $pp['title'] ?? 'Eklenti Sayfası', $pp['icon'] ?? '▣', 'plugin-page.php'); ?>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php }); ?>
  <?php endif; ?>

  <?php if(can('users.manage') || current_user_role()==='admin'): ?>
  <?php omg_nav_group('users',om_t('admin.users','Kullanıcılar'),'♙',['users.php'], function(){ ?>
    <?php omg_nav_item('users.php',om_t('admin.users','Kullanıcılar'),'♙','users.php'); ?>
  <?php }); ?>
  <?php endif; ?>

  <?php if(can('users.manage') || current_user_role()==='admin'): ?>
  <?php omg_nav_group('system',om_t('admin.system','Sistem'),'⚙',['notifications.php','revisions.php','backups.php','cache.php','performance.php','logs.php','security.php','diagnostics.php','system.php'], function(){ ?>
    <?php omg_nav_item('notifications.php',om_t('admin.notifications','Bildirimler'),'🔔','notifications.php'); ?>
    <?php omg_nav_item('revisions.php',om_t('admin.revisions','Revizyonlar'),'↶','revisions.php'); ?>
    <?php omg_nav_item('backups.php',om_t('admin.backups','Yedekleme'),'◴','backups.php'); ?>
    <?php omg_nav_item('cache.php',om_t('admin.cache','Cache'),'⚡','cache.php'); ?>
    <?php omg_nav_item('performance.php',om_t('admin.performance','Performans'),'↯','performance.php'); ?>
    <?php omg_nav_item('logs.php',om_t('admin.logs','İşlem Kayıtları'),'▧','logs.php'); ?>
    <?php omg_nav_item('security.php',om_t('admin.security','Güvenlik'),'🛡','security.php'); ?>
    <?php omg_nav_item('diagnostics.php',om_t('admin.diagnostics','Kurulum Sonrası Test'),'✓','diagnostics.php'); ?>
    <?php omg_nav_item('system.php',om_t('admin.system_health','Sistem Sağlığı'),'⚙','system.php'); ?>
  <?php }); ?>
  <?php endif; ?>

  <?php omg_nav_group('settings',om_t('admin.settings','Ayarlar'),'⚙',['settings.php','seo.php','language-check.php'], function(){ ?>
    <?php omg_nav_item('settings.php',om_t('admin.general_settings','Genel Ayarlar'),'⚙','settings.php'); ?>
    <?php omg_nav_item('seo.php',om_t('admin.seo_settings','SEO Ayarları'),'⌁','seo.php'); ?>
    <?php omg_nav_item('language-check.php',om_t('admin.language_check','Dil Kontrolü'),'◇','language-check.php'); ?>
  <?php }); ?>

  <div class="nav-title"><?=e(om_t('admin.shortcuts','KISA YOLLAR'))?></div>
  <a class="omg-acc-link" href="../" target="_blank"><span class="nav-ico">↗</span><span><?=e(om_t('admin.view_site','Siteyi Gör'))?></span><i>›</i></a>
  <a class="omg-acc-link" href="logout.php"><span class="nav-ico">⏻</span><span><?=e(om_t('admin.logout','Çıkış Yap'))?></span><i>›</i></a>
</nav>
</aside>
<main class="main dle-main"><div class="content dle-content">
