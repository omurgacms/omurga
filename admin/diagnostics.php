<?php
require '_layout.php';
require_cap('users.manage');

function omg_diag_status(bool $ok, string $warnText='Uyarı', string $okText='Sorunsuz'): string {
    return $ok ? '<span class="pill-ok">'.e($okText).'</span>' : '<span class="pill-bad">'.e($warnText).'</span>';
}

$profile = site_profile();
$checks = [];
$add = function(string $name, string $value, bool $ok, string $note='') use (&$checks){
    $checks[] = ['name'=>$name, 'value'=>$value, 'ok'=>$ok, 'note'=>$note];
};

$add('PHP sürümü', PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0+ önerilir.');
$add('PDO MySQL', extension_loaded('pdo_mysql') ? 'Aktif' : 'Eksik', extension_loaded('pdo_mysql'), 'Veritabanı bağlantısı için gerekir.');
$add('GD / WebP', function_exists('imagewebp') ? 'Aktif' : 'Eksik', function_exists('imagewebp'), 'WebP dönüşümü için önerilir.');
$add('ZipArchive', class_exists('ZipArchive') ? 'Aktif' : 'Eksik', class_exists('ZipArchive'), 'Yedekleme ve güncelleme için önerilir.');
foreach(['uploads','storage','storage/backups','storage/cache','storage/logs','storage/updates'] as $dir){
    $path = OMURGA_ROOT.'/'.$dir;
    if(!is_dir($path)) @mkdir($path, 0775, true);
    $add($dir.' yazılabilir', is_writable($path) ? 'Evet' : 'Hayır', is_writable($path), 'Bu klasör yazılabilir olmalı.');
}
$add('Aktif site profili', (string)($profile['label'] ?? site_type()), true);
$add('İçerik URL tabanı', '/'.content_url_base().'/icerik-adi', true);
$add('Aktif tema', omurga_active_theme(), is_dir(omurga_theme_dir()), 'Aktif tema klasörü bulunamadı.');
$themeEngine = omurga_theme_engine();
$themeDir = omurga_theme_dir();
$homeFile = $themeEngine === 'php' ? 'home.php' : 'home.omg';
$singleFile = $themeEngine === 'php' ? 'single.php' : 'single.omg';
$pageFile = $themeEngine === 'php' ? 'page.php' : 'page.omg';
$add('Tema motoru', strtoupper($themeEngine), true);
$add('Ana sayfa dosyası', is_file($themeDir.'/'.$homeFile) ? 'Var' : 'Yok', is_file($themeDir.'/'.$homeFile), 'Aktif tema motoruna göre '.$homeFile.' kontrol edilir.');
$add('Yazı şablonu', is_file($themeDir.'/'.$singleFile) ? 'Var' : 'Yok', is_file($themeDir.'/'.$singleFile), 'Yazı görüntüleme için '.$singleFile.' önerilir.');
$add('Sayfa şablonu', is_file($themeDir.'/'.$pageFile) ? 'Var' : 'Yok', is_file($themeDir.'/'.$pageFile), 'Sabit sayfa görüntüleme için '.$pageFile.' önerilir.');

$count = function(string $where='1=1'){
    try { return (int)db()->query('SELECT COUNT(*) FROM '.table_name('posts').' WHERE '.$where)->fetchColumn(); }
    catch(Throwable $e){ omurga_write_error($e); return 0; }
};
$contentCount = $count("type <> 'page'");
$pageCount = $count("type = 'page'");
$add('Yazı/içerik kaydı', (string)$contentCount, true);
$add('Sabit sayfa kaydı', (string)$pageCount, true);
$add('Hata kayıt dosyası', is_file(OMURGA_ROOT.'/storage/logs/error.log') ? 'Var' : 'Yok', true);
$errors = omurga_recent_error_lines(12);
$okCount = count(array_filter($checks, fn($r)=>$r['ok']));
$total = count($checks);
?>
<div class="toolbar compact-page-head"><div><h1>Kurulum Sonrası Test</h1><p class="muted">Admin sayfaları, tema, profil, yazılabilir klasörler ve sunucu uyumluluğu için hızlı kontrol.</p></div><a class="btn light" href="system.php">Sistem Sağlığına Git</a></div>

<section class="card">
  <h2>Genel Durum</h2>
  <p><b><?=e((string)$okCount)?> / <?=e((string)$total)?></b> kontrol sorunsuz görünüyor.</p>
  <div class="omg-summary-strip"><span><b><?=e((string)$total)?></b> Toplam kontrol</span><span><b><?=e((string)$okCount)?></b> Sorunsuz</span><span><b><?=e((string)($total-$okCount))?></b> Uyarı</span><span><b><?=e(omurga_active_theme())?></b> Aktif tema</span></div>
  <div class="profile-help">
    <div><b>Profil</b><span><?=e((string)($profile['label'] ?? site_type()))?></span><small>Panel dili ve URL yapısı bu profile göre çalışır.</small></div>
    <div><b>URL</b><span>/<?=e(content_url_base())?>/icerik-adi</span><small>Profil değişirse yeni içerik bağlantısı buna göre oluşur.</small></div>
    <div><b>Tema</b><span><?=e(omurga_active_theme())?></span><small>Tema/blok işleri çekirdekten ayrı tutulur.</small></div>
  </div>
</section>

<section class="card" style="margin-top:18px">
  <h2>Kontrol Listesi</h2>
  <table class="table status-table"><thead><tr><th>Kontrol</th><th>Durum</th><th>Sonuç</th><th>Not</th></tr></thead><tbody>
  <?php foreach($checks as $row): ?>
    <tr>
      <td><?=e($row['name'])?></td>
      <td><?=e($row['value'])?></td>
      <td><?=omg_diag_status((bool)$row['ok'])?></td>
      <td><small><?=e($row['note'])?></small></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table>
</section>

<section class="card" style="margin-top:18px">
  <h2>Hızlı Admin Sayfa Testleri</h2>
  <p class="muted">Aşağıdaki bağlantıları açarak 500 veren ekran varsa doğrudan tespit edebilirsin.</p>
  <div class="dle-quick-grid compact-admin-links">
    <a class="dle-quick" href="post-edit.php"><span class="qicon doc">✚</span><span><b><?=e(content_quick_add_label())?></b><small>Yazı/içerik editörü</small></span></a>
    <a class="dle-quick" href="posts.php"><span class="qicon doc">▤</span><span><b><?=e(content_label_plural())?></b><small>İçerik listesi</small></span></a>
    <a class="dle-quick" href="pages.php"><span class="qicon doc">▦</span><span><b>Sayfalar</b><small>Sayfa listesi</small></span></a>
    <a class="dle-quick" href="media.php"><span class="qicon media">▧</span><span><b>Medya</b><small>Dosya yükleme alanı</small></span></a>
    <a class="dle-quick" href="menus.php"><span class="qicon gear">☰</span><span><b>Menüler</b><small>Menü düzenleme</small></span></a>
    <a class="dle-quick" href="layout.php"><span class="qicon gear">▦</span><span><b>Düzen</b><small>Blok yerleşimi</small></span></a>
    <a class="dle-quick" href="theme-editor.php"><span class="qicon gear">⌨</span><span><b>Tema Düzenleyici</b><small>Tema dosyaları</small></span></a>
    <a class="dle-quick" href="settings.php"><span class="qicon gear">⚙</span><span><b>Ayarlar</b><small>Profil ve site ayarları</small></span></a>
  </div>
</section>

<section class="card" style="margin-top:18px">
  <h2>Son Hata Kayıtları</h2>
  <?php if($errors): ?><pre class="codebox"><?=e(implode("\n", $errors))?></pre><?php else: ?><p>Son hata kaydı görünmüyor.</p><?php endif; ?>
</section>
<?php require '_footer.php'; ?>
