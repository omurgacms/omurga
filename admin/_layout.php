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
  $aria = $active ? ' aria-current="page"' : '';
  echo '<a class="omg-acc-link '.$active.'" href="'.e($href).'" title="'.e($label).'" data-nav-label="'.e(mb_strtolower($label, 'UTF-8')).'"'.$aria.'><span class="nav-ico">'.$icon.'</span><span>'.e($label).'</span><i>›</i></a>';
}

function omg_group_is_active(array $files){
  global $current;
  return in_array($current, $files, true);
}

function omg_nav_group($id, $label, $icon, array $files, callable $callback, $desc = ''){
  $isActive = omg_group_is_active($files);
  $open = $isActive ? ' open' : '';
  echo '<section class="omg-nav-group'.$open.'" data-group="'.e($id).'" data-nav-label="'.e(mb_strtolower($label, 'UTF-8')).'">';
  echo '<button type="button" class="omg-nav-head" onclick="omurgaToggleNavGroup(this)" title="'.e($label).'"><span><b>'.$icon.'</b><strong>'.e($label).'</strong></span><em>⌄</em></button>';
  echo '<div class="omg-nav-body">';
  $callback();
  echo '</div></section>';
}

function omg_admin_location(){
  global $current;
  $map = [
    'index.php'=>['Başlangıç','Genel bakış'],
    'post-edit.php'=>['İçerikler','Yeni yazı / düzenle'], 'addnews.php'=>['İçerikler','Yeni yazı'], 'posts.php'=>['İçerikler','Yazılar'], 'pages.php'=>['İçerikler','Sayfalar'], 'page-edit.php'=>['İçerikler','Sayfa düzenle'], 'categories.php'=>['İçerikler','Kategoriler'], 'tags.php'=>['İçerikler','Etiketler'], 'comments.php'=>['İçerikler','Yorumlar'], 'custom-fields.php'=>['İçerikler','Özel Alanlar'],
    'themes.php'=>['Tasarım','Temalar'], 'theme-panel.php'=>['Tasarım','Tema paneli'], 'theme-editor.php'=>['Tasarım','Tema düzenleyici'], 'layout.php'=>['Tasarım','Sayfa düzeni'], 'layout-header-footer.php'=>['Tasarım','Üst / Alt alanlar'], 'templates.php'=>['Tasarım','Şablonlar'], 'blocks.php'=>['Tasarım','Blok merkezi'], 'menus.php'=>['Tasarım','Menü yönetimi'], 'ads.php'=>['Tasarım','Reklam alanları'], 'design.php'=>['Tasarım','Tema ayarları'],
    'media.php'=>['Medya','Kütüphane'], 'media-jobs.php'=>['Medya','Medya İşleri'], 'media-webp.php'=>['Medya','WebP dönüşüm'], 'media-unused.php'=>['Medya','Kullanılmayan dosyalar'],
    'forms.php'=>['Formlar','Başvurular'], 'packages.php'=>['Paketler','Paket yönetimi'], 'plugin-page.php'=>['Paketler','Paket sayfası'],
    'user-management.php'=>['Kullanıcılar','Kullanıcı Yönetimi'], 'users.php'=>['Kullanıcılar','Kullanıcı yönetimi'], 'roles.php'=>['Kullanıcılar','Roller'], 'permissions.php'=>['Kullanıcılar','Yetkiler'],
    'api.php'=>['Sistem','REST API'], 'rest-api.php'=>['Sistem','REST API'], 'api-settings.php'=>['Sistem','REST API'], 'rest-api-settings.php'=>['Sistem','REST API'], 'settings-api.php'=>['Sistem','REST API'], 'notifications.php'=>['Sistem','Bildirimler'], 'revisions.php'=>['Sistem','Revizyonlar'], 'backups.php'=>['Sistem','Yedekleme'], 'rollback.php'=>['Sistem','Geri dön'], 'cache.php'=>['Sistem','Performans / Cache / Temizlik'], 'performance.php'=>['Sistem','Performans / Cache / Temizlik'], 'logs.php'=>['Sistem','Aktivite kayıtları'], 'login-security.php'=>['Sistem','Giriş Güvenliği'], 'account-security.php'=>['Sistem','Hesap Güvenliği'], 'error-logs.php'=>['Sistem','Hata Kayıtları'], 'security.php'=>['Sistem','Güvenlik'], 'diagnostics.php'=>['Sistem','Kurulum sonrası test'], 'updates.php'=>['Sistem','Güncellemeler'], 'system.php'=>['Sistem','Sistem sağlığı'], 'migrations.php'=>['Sistem','Migration Durumu'],
    'settings.php'=>['Ayarlar','Genel ayarlar'], 'seo.php'=>['Ayarlar','SEO Merkezi'], 'seo-test.php'=>['Ayarlar','SEO Test'], 'permalinks.php'=>['Ayarlar','Kalıcı bağlantılar'], 'language-check.php'=>['Ayarlar','Dil kontrolü'],
  ];
  return $map[$current] ?? ['Panel','Geçerli sayfa'];
}
$omgLoc = omg_admin_location();
?>
<!doctype html>
<html lang="<?=e(omurga_admin_language())?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=e(om_t('admin.dashboard','Başlangıç'))?> - Omurga</title>
  <link rel="icon" type="image/png" href="../assets/images/omurga-icon.png">
  <link rel="apple-touch-icon" href="../assets/images/omurga-icon.png">
  <link rel="stylesheet" href="../assets/css/omurga.css?v=1.2.0-rc.1">
</head>
<body class="omurga-admin-page dle-skin">
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
  var navSearch = document.getElementById('omgNavSearch');
  if(navSearch){
    navSearch.addEventListener('input', function(){
      var q = (this.value || '').toLocaleLowerCase('tr-TR').trim();
      document.querySelectorAll('.omg-nav-group').forEach(function(group){
        var groupText = (group.getAttribute('data-nav-label') || '').toLocaleLowerCase('tr-TR');
        var any = !q || groupText.indexOf(q) > -1;
        group.querySelectorAll('.omg-acc-link').forEach(function(link){
          var txt = (link.getAttribute('data-nav-label') || link.textContent || '').toLocaleLowerCase('tr-TR');
          var match = !q || txt.indexOf(q) > -1 || groupText.indexOf(q) > -1;
          link.style.display = match ? '' : 'none';
          if(match) any = true;
        });
        group.style.display = any ? '' : 'none';
        if(q && any) group.classList.add('open');
      });
    });
  }
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
  <a class="dle-brand" href="index.php" title="Panel ana sayfası"><img src="../assets/images/omurga-logo.png" alt="Omurga"></a>
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
      <a role="menuitem" href="users.php?profile=1">Profilim</a>
      <a role="menuitem" href="account-security.php">Hesap Güvenliği</a>
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
  <label class="omg-nav-search"><span>⌕</span><input id="omgNavSearch" type="search" placeholder="Menüde ara..." autocomplete="off"></label>
  <?php omg_nav_item('index.php',om_t('admin.dashboard','Başlangıç'),'⌂','index.php'); ?>

  <?php omg_nav_group('content',om_t('admin.content','İçerikler'),'▤',['addnews.php','post-edit.php','page-edit.php','posts.php','pages.php','categories.php','tags.php','comments.php','custom-fields.php'], function(){ ?>
    <?php $ptype = primary_content_type(); ?>
    <?php omg_nav_item('post-edit.php?type='.rawurlencode($ptype), 'Yeni Yazı', '✚', ['addnews.php','post-edit.php']); ?>
    <?php omg_nav_item('posts.php', 'Yazılar', '▤', 'posts.php'); ?>
    <?php omg_nav_item('pages.php','Sayfalar','▦',['pages.php','page-edit.php']); ?>
    <?php omg_nav_item('comments.php',om_t('comments.title','Yorumlar'),'☷','comments.php'); ?>
    <?php omg_nav_item('categories.php',content_category_label(),'▣','categories.php'); ?>
    <?php omg_nav_item('tags.php',content_tag_label(),'⌗','tags.php'); ?>
    <?php omg_nav_item('custom-fields.php','Özel Alanlar','▧','custom-fields.php'); ?>
  <?php }, 'Yazı, sayfa, yorum ve sınıflandırma'); ?>

  <?php omg_nav_group('design',om_t('admin.design','Tasarım'),'▨',['themes.php','theme-panel.php','theme-editor.php','layout.php','layout-header-footer.php','templates.php','blocks.php','design.php','menus.php','ads.php'], function(){ ?>
    <?php omg_nav_item('themes.php',om_t('admin.themes','Temalar'),'▨','themes.php'); ?>
    <?php omg_nav_item('design.php',om_t('admin.theme_settings','Tema Ayarları'),'✎','design.php'); ?>
    <?php omg_nav_item('layout.php','Sayfa Düzeni','▦',['layout.php','layout-header-footer.php']); ?>
    <?php omg_nav_item('layout-header-footer.php','Üst / Alt Alanlar','▥','layout-header-footer.php'); ?>
    <?php omg_nav_item('blocks.php','Blok Merkezi','▩','blocks.php'); ?>
    <?php omg_nav_item('menus.php',om_t('admin.menu_manager','Menü Yönetimi'),'☰','menus.php'); ?>
    <?php omg_nav_item('templates.php',om_t('admin.templates','Şablonlar'),'▤','templates.php'); ?>
    <?php omg_nav_item('theme-editor.php',om_t('admin.theme_editor','Tema Düzenleyici'),'⌨','theme-editor.php'); ?>
    <?php omg_nav_item('ads.php',om_t('admin.ads','Reklam Alanları'),'▰','ads.php'); ?>
    <?php $omgThemePages = function_exists('omurga_theme_admin_pages') ? omurga_theme_admin_pages() : []; ?>
    <?php foreach($omgThemePages as $tp): ?>
      <?php if(can($tp['cap'] ?? 'design.manage') || current_user_role()==='admin'): ?>
        <?php omg_nav_item('theme-panel.php?page='.urlencode($tp['id'] ?? ''), $tp['menu_title'] ?? $tp['title'] ?? 'Tema Paneli', $tp['icon'] ?? '▨', 'theme-panel.php'); ?>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php }, 'Tema ayarı, blok ve yerleşim'); ?>

  <?php omg_nav_group('media',om_t('admin.media','Medya'),'▧',['media.php','media-jobs.php','media-webp.php','media-unused.php'], function(){ ?>
    <?php omg_nav_item('media.php',om_t('admin.media_library','Medya Kütüphanesi'),'▧','media.php'); ?>
    <?php omg_nav_item('media-jobs.php','Medya İşleri','↻','media-jobs.php'); ?>
    <?php omg_nav_item('media-webp.php',om_t('admin.webp_convert','WebP Dönüştür'),'◇','media-webp.php'); ?>
    <?php omg_nav_item('media-unused.php',om_t('admin.unused_files','Kullanılmayan Dosyalar'),'⌫','media-unused.php'); ?>
  <?php }, 'Görsel ve dosya yönetimi'); ?>

  <?php omg_nav_group('forms',om_t('admin.forms','Formlar'),'☷',['forms.php'], function(){ ?>
    <?php omg_nav_item('forms.php',om_t('admin.forms_submissions','Formlar ve Başvurular'),'☷','forms.php'); ?>
  <?php }, 'Form kayıtları ve başvurular'); ?>

  <?php if(can('plugins.manage') || current_user_role()==='admin'): ?>
  <?php
    $omgPluginPages = function_exists('omurga_plugin_admin_pages') ? omurga_plugin_admin_pages() : [];
    $omgMenuGroups = [];
    $omgPackagePages = [];
    foreach ($omgPluginPages as $pp) {
      $groupId = (string)($pp['menu_group'] ?? $pp['group'] ?? $pp['admin_group'] ?? '');
      $groupId = preg_replace('/[^a-zA-Z0-9_-]/', '-', $groupId);
      $isTopLevel = $groupId !== '' && $groupId !== 'packages' && $groupId !== 'paketler';
      if ($isTopLevel) {
        if (!isset($omgMenuGroups[$groupId])) {
          $omgMenuGroups[$groupId] = [
            'id' => $groupId,
            'title' => $pp['menu_group_title'] ?? $pp['group_title'] ?? $pp['admin_group_title'] ?? $pp['title'] ?? 'Paket',
            'icon' => $pp['menu_group_icon'] ?? $pp['group_icon'] ?? $pp['admin_group_icon'] ?? $pp['icon'] ?? '▣',
            'pages' => [],
          ];
        }
        $omgMenuGroups[$groupId]['pages'][] = $pp;
      } else {
        $omgPackagePages[] = $pp;
      }
    }
  ?>
  <?php foreach($omgMenuGroups as $omgGroup): ?>
    <?php omg_nav_group($omgGroup['id'], (string)$omgGroup['title'], (string)$omgGroup['icon'], ['plugin-page.php'], function() use ($omgGroup){ ?>
      <?php foreach($omgGroup['pages'] as $pp): ?>
        <?php if(can($pp['cap'] ?? 'plugins.manage') || current_user_role()==='admin'): ?>
          <?php omg_nav_item('plugin-page.php?plugin='.urlencode($pp['plugin'] ?? $pp['package'] ?? 'registered').'&page='.urlencode($pp['id'] ?? ''), $pp['menu_title'] ?? $pp['title'] ?? 'Paket Sayfası', $pp['icon'] ?? '▣', 'plugin-page.php'); ?>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php }, 'Paket tarafından eklenen yönetim sayfaları'); ?>
  <?php endforeach; ?>
  <?php omg_nav_group('plugins',om_t('admin.plugins','Paketler'),'▣',['packages.php','plugin-page.php'], function() use ($omgPackagePages){ ?>
    <?php omg_nav_item('packages.php','Paketler','▧','packages.php'); ?>
    <?php foreach($omgPackagePages as $pp): ?>
      <?php if(can($pp['cap'] ?? 'plugins.manage') || current_user_role()==='admin'): ?>
        <?php omg_nav_item('plugin-page.php?plugin='.urlencode($pp['plugin'] ?? $pp['package'] ?? 'registered').'&page='.urlencode($pp['id'] ?? ''), $pp['menu_title'] ?? $pp['title'] ?? 'Paket Sayfası', $pp['icon'] ?? '▣', 'plugin-page.php'); ?>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php }, 'Paket yükleme, güncelleme ve yönetim'); ?>
  <?php endif; ?>

  <?php if(can('users.manage') || current_user_role()==='admin'): ?>
  <?php omg_nav_group('users','Kullanıcı Yönetimi','♙',['user-management.php','users.php','roles.php','permissions.php'], function(){ ?>
    <?php omg_nav_item('user-management.php','Kullanıcı Yönetimi','♙',['user-management.php','users.php','roles.php','permissions.php']); ?>
  <?php }, 'Kullanıcı, rol ve yetki tek merkez'); ?>
  <?php endif; ?>

  <?php if(can('users.manage') || current_user_role()==='admin'): ?>
  <?php omg_nav_group('system',om_t('admin.system','Sistem'),'⚙',['api.php','rest-api.php','api-settings.php','rest-api-settings.php','settings-api.php','notifications.php','revisions.php','backups.php','rollback.php','cache.php','performance.php','logs.php','login-security.php','account-security.php','error-logs.php','security.php','diagnostics.php','health-check.php','system-tests.php','migrations.php','updates.php','system.php'], function(){ ?>
    <?php if(can('api.manage') || can('settings.manage') || can('system.manage')): ?><?php omg_nav_item('api.php','REST API','{ }','api.php'); ?><?php endif; ?>
    <?php omg_nav_item('notifications.php',om_t('admin.notifications','Bildirimler'),'🔔','notifications.php'); ?>
    <?php omg_nav_item('revisions.php',om_t('admin.revisions','Revizyonlar'),'↶','revisions.php'); ?>
    <?php omg_nav_item('backups.php',om_t('admin.backups','Yedekleme'),'◴','backups.php'); ?>
    <?php omg_nav_item('rollback.php','Rollback / Geri Dön','↺','rollback.php'); ?>
    <?php omg_nav_item('performance.php','Performans / Cache / Temizlik','⚡',['performance.php','cache.php']); ?>
    <?php omg_nav_item('logs.php',om_t('admin.logs','Aktivite Kayıtları'),'▧','logs.php'); ?>
    <?php omg_nav_item('login-security.php','Giriş Güvenliği','↯','login-security.php'); ?>
    <?php omg_nav_item('account-security.php','Hesap Güvenliği','◉','account-security.php'); ?>
    <?php omg_nav_item('error-logs.php','Hata Kayıtları','⚠','error-logs.php'); ?>
    <?php omg_nav_item('security.php',om_t('admin.security','Güvenlik'),'🛡','security.php'); ?>
    <?php omg_nav_item('diagnostics.php',om_t('admin.diagnostics','Kurulum Sonrası Test'),'✓','diagnostics.php'); ?>
    <?php omg_nav_item('health-check.php','Sağlık Kontrolü','☑','health-check.php'); ?>
    <?php omg_nav_item('system-tests.php','Sistem Testleri','☷','system-tests.php'); ?>
    <?php omg_nav_item('migrations.php','Migration Durumu','⇄','migrations.php'); ?>
    <?php if(can('system.update') || can('settings.manage') || can('system.manage')): ?><?php omg_nav_item('updates.php','Güncellemeler','↥','updates.php'); ?><?php endif; ?>
    <?php omg_nav_item('system.php',om_t('admin.system_health','Sistem Sağlığı'),'⚙','system.php'); ?>
  <?php }, 'Bakım, güvenlik ve güncelleme'); ?>
  <?php endif; ?>
  <?php omg_nav_group('settings',om_t('admin.settings','Ayarlar'),'⚙',['settings.php','seo.php','seo-test.php','permalinks.php','language-check.php'], function(){ ?>
    <?php omg_nav_item('settings.php',om_t('admin.general_settings','Genel Ayarlar'),'⚙','settings.php'); ?>
    <?php omg_nav_item('seo.php','SEO Merkezi','⌁','seo.php'); ?>
    <?php omg_nav_item('seo-test.php','SEO Test','✓','seo-test.php'); ?>
    <?php omg_nav_item('permalinks.php','Kalıcı Bağlantılar','🔗','permalinks.php'); ?>
    <?php omg_nav_item('language-check.php',om_t('admin.language_check','Dil Kontrolü'),'◇','language-check.php'); ?>
  <?php }, 'Site, bağlantı ve dil'); ?>

  <div class="nav-title"><?=e(om_t('admin.shortcuts','KISA YOLLAR'))?></div>
  <a class="omg-acc-link" href="../" target="_blank"><span class="nav-ico">↗</span><span><?=e(om_t('admin.view_site','Siteyi Gör'))?></span><i>›</i></a>
  <a class="omg-acc-link" href="logout.php"><span class="nav-ico">⏻</span><span><?=e(om_t('admin.logout','Çıkış Yap'))?></span><i>›</i></a>
</nav>
</aside>
<main class="main dle-main"><div class="content dle-content">
