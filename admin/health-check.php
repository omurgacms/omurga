<?php
require __DIR__.'/_layout.php';
require_cap('users.manage');

function omhc_badge(bool $ok, string $okText='Tamam', string $badText='Kontrol gerekli'): string {
    return $ok ? '<span class="pill-ok">'.e($okText).'</span>' : '<span class="pill-bad">'.e($badText).'</span>';
}
function omhc_add(array &$checks, string $group, string $name, string $value, bool $ok, string $note=''): void {
    $checks[]=['group'=>$group,'name'=>$name,'value'=>$value,'ok'=>$ok,'note'=>$note];
}
function omhc_table_exists(string $table): bool {
    try { db()->query('SELECT 1 FROM '.table_name($table).' LIMIT 1'); return true; }
    catch(Throwable $e){ return false; }
}
function omhc_setting_ok(string $key): bool { try { setting($key, null); return true; } catch(Throwable $e){ return false; } }
function omhc_write_test(string $dir): bool {
    $path = OMURGA_ROOT.'/'.$dir;
    if(!is_dir($path)) @mkdir($path, 0775, true);
    if(!is_writable($path)) return false;
    $file = $path.'/.omurga-health-'.bin2hex(random_bytes(3)).'.tmp';
    $ok = @file_put_contents($file, 'ok') !== false;
    if($ok) @unlink($file);
    return $ok;
}

$checks=[];
omhc_add($checks,'Sunucu','PHP sürümü',PHP_VERSION,version_compare(PHP_VERSION,'8.0.0','>='),'PHP 8.0+ önerilir; PHP 8.1+ daha iyi olur.');
omhc_add($checks,'Sunucu','PDO MySQL',extension_loaded('pdo_mysql')?'Aktif':'Eksik',extension_loaded('pdo_mysql'),'Veritabanı bağlantısı için gerekir.');
omhc_add($checks,'Sunucu','Mbstring',extension_loaded('mbstring')?'Aktif':'Eksik',extension_loaded('mbstring'),'Türkçe karakter ve çok dilli içerikler için önerilir.');
omhc_add($checks,'Sunucu','JSON',extension_loaded('json')?'Aktif':'Eksik',extension_loaded('json'),'Manifest, blok ve ayar verileri için gerekir.');
omhc_add($checks,'Sunucu','ZipArchive',class_exists('ZipArchive')?'Aktif':'Eksik',class_exists('ZipArchive'),'Tema/paket yükleme, yedek ve güncelleme için gerekir.');
omhc_add($checks,'Sunucu','GD / WebP',function_exists('imagewebp')?'Aktif':'Eksik',function_exists('imagewebp'),'WebP ve küçük görsel üretimi için önerilir.');
omhc_add($checks,'Sunucu','Imagick',extension_loaded('imagick')?'Aktif':'Yok',true,'Zorunlu değildir; büyük görseller için faydalıdır.');

foreach(['storage','storage/cache','storage/logs','storage/backups','storage/updates','uploads','packages','themes'] as $dir){
    $writeOk = omhc_write_test($dir);
    omhc_add($checks,'Dosya İzinleri',$dir,$writeOk?'Yazılabilir':'Yazılamıyor',$writeOk,'Klasör 755 olabilir ama PHP kullanıcısı yazabilmelidir.');
}

try { db()->query('SELECT 1'); $dbOk=true; } catch(Throwable $e) { $dbOk=false; }
omhc_add($checks,'Veritabanı','Bağlantı',$dbOk?'Başarılı':'Başarısız',$dbOk,'Config ve veritabanı kullanıcı izinlerini kontrol et.');
foreach(['users','posts','categories','media','settings','migrations','login_attempts','rate_limits','media_jobs','password_resets'] as $t){
    $tableOk = omhc_table_exists($t);
    omhc_add($checks,'Veritabanı',$t.' tablosu',$tableOk?'Var':'Yok',$tableOk,'Eksikse Sistem > Migration Durumu üzerinden kontrol çalıştır.');
}

$migOk = function_exists('omurga_migrations_all_applied') ? omurga_migrations_all_applied() : false;
omhc_add($checks,'Migration','Migration durumu',$migOk?'Tamam':'Bekleyen/Hatalı',$migOk,'/admin/migrations.php üzerinden ayrıntı görülebilir.');
$themeDir = function_exists('omurga_theme_dir') ? omurga_theme_dir() : '';
omhc_add($checks,'Tema','Aktif tema',function_exists('omurga_active_theme')?omurga_active_theme():'Bilinmiyor',is_dir($themeDir),'Aktif tema klasörü bulunmalı.');
omhc_add($checks,'Güvenlik','Core Guard',function_exists('omurga_core_guard_enabled') && omurga_core_guard_enabled() ? 'Aktif':'Pasif',function_exists('omurga_core_guard_enabled') && omurga_core_guard_enabled(),'Production ortamında aktif olmalı.');
omhc_add($checks,'Güvenlik','Session cookie',ini_get('session.cookie_httponly')?'HTTPOnly':'Kontrol gerekli',(bool)ini_get('session.cookie_httponly'),'Admin oturumu için HTTPOnly önerilir.');

$rewriteNote = 'Apache mod_rewrite sunucu tarafından kontrol edilir; .htaccess dosyası varsa URL yönlendirme kuralları çalışabilir.';
omhc_add($checks,'URL','Ana .htaccess',is_file(OMURGA_ROOT.'/.htaccess')?'Var':'Yok',is_file(OMURGA_ROOT.'/.htaccess'),$rewriteNote);
foreach(['sitemap.xml','feed.xml','atom.xml','google-news.xml','robots.txt','api/v1/status'] as $ep){
    omhc_add($checks,'Endpoint',$ep,omurga_url($ep),true,'Bağlantıyı yeni sekmede açarak HTTP sonucunu kontrol et.');
}

$okCount=count(array_filter($checks,fn($r)=>$r['ok'])); $total=count($checks); $warn=$total-$okCount;
$groups=[]; foreach($checks as $c){$groups[$c['group']][]=$c;}
?>
<div class="toolbar compact-page-head">
  <div><h1>Sağlık Kontrolü</h1><p class="muted">Canlı kurulum, sunucu uyumluluğu, dosya izinleri, migration ve temel endpointler için kapsamlı kontrol.</p></div>
  <div><a class="btn light" href="system-tests.php">Sistem Testleri</a> <a class="btn light" href="migrations.php">Migration Durumu</a></div>
</div>
<section class="card compact-panel">
  <div class="stat-grid compact-stats">
    <div class="stat"><b><?=e(OMURGA_VERSION)?></b><span>Sürüm</span></div>
    <div class="stat"><b><?=e((string)$total)?></b><span>Toplam kontrol</span></div>
    <div class="stat"><b><?=e((string)$okCount)?></b><span>Sorunsuz</span></div>
    <div class="stat"><b><?=e((string)$warn)?></b><span>Uyarı</span></div>
  </div>
</section>
<?php foreach($groups as $group=>$rows): ?>
<section class="card compact-panel" style="margin-top:16px">
  <div class="compact-panel-head"><h2><?=e($group)?></h2></div>
  <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Kontrol</th><th>Durum</th><th>Sonuç</th><th>Not</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?><tr><td><?=e($r['name'])?></td><td><?=e($r['value'])?></td><td><?=omhc_badge((bool)$r['ok'])?></td><td><small><?=e($r['note'])?></small></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endforeach; ?>
<section class="card compact-panel" style="margin-top:16px">
  <div class="compact-panel-head"><h2>Hızlı Endpoint Test Linkleri</h2></div>
  <div class="dle-quick-grid compact-admin-links">
    <?php foreach(['sitemap.xml','feed.xml','atom.xml','google-news.xml','robots.txt','api/v1/status'] as $ep): ?>
      <a class="dle-quick" target="_blank" href="<?=e(omurga_url($ep))?>"><span class="qicon gear">↗</span><span><b><?=e($ep)?></b><small><?=e(omurga_url($ep))?></small></span></a>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__.'/_footer.php'; ?>
