<?php
require __DIR__.'/_layout.php';
require_cap('users.manage');

function omst_result(bool $ok, string $name, string $message='', string $group='Genel'): array { return ['ok'=>$ok,'name'=>$name,'message'=>$message,'group'=>$group]; }
function omst_badge(bool $ok): string { return $ok ? '<span class="pill-ok">Başarılı</span>' : '<span class="pill-bad">Başarısız</span>'; }
function omst_table_exists(string $table): bool { try{ db()->query('SELECT 1 FROM '.table_name($table).' LIMIT 1'); return true; }catch(Throwable $e){ return false; } }
function omst_write_delete(string $dir): bool { $p=OMURGA_ROOT.'/'.$dir; if(!is_dir($p)) @mkdir($p,0775,true); $f=$p.'/.omurga-test-'.bin2hex(random_bytes(4)).'.tmp'; $ok=@file_put_contents($f,'ok')!==false; if($ok) @unlink($f); return $ok; }
function omst_run_tests(): array {
    $r=[];
    try { db()->query('SELECT 1'); $r[]=omst_result(true,'Veritabanı bağlantısı','PDO bağlantısı başarılı.','Veritabanı'); } catch(Throwable $e){ $r[]=omst_result(false,'Veritabanı bağlantısı',$e->getMessage(),'Veritabanı'); }
    foreach(['users','posts','settings','media','migrations'] as $t){ $ok=omst_table_exists($t); $r[]=omst_result($ok,$t.' tablosu',$ok?'Tablo erişilebilir.':'Tablo bulunamadı veya erişilemiyor.','Veritabanı'); }
    foreach(['storage/cache','storage/logs','uploads'] as $d){ $ok=omst_write_delete($d); $r[]=omst_result($ok,$d.' yazma/silme',$ok?'Geçici dosya yazıldı ve silindi.':'Yazma/silme başarısız.','Dosya Sistemi'); }
    $r[]=omst_result(class_exists('ZipArchive'),'ZipArchive','Tema/paket/yedek işlemleri için gereklidir.','Sunucu');
    $r[]=omst_result(function_exists('imagewebp'),'GD WebP','WebP kuyruğu için önerilir.','Sunucu');
    $r[]=omst_result(extension_loaded('pdo_mysql'),'PDO MySQL','Veritabanı için gereklidir.','Sunucu');
    $r[]=omst_result(function_exists('omurga_migrations_all_applied') ? omurga_migrations_all_applied() : false,'Migrationlar applied','Bekleyen/hatalı migration varsa Migration Durumu sayfasını aç.','Migration');
    $r[]=omst_result(is_dir(function_exists('omurga_theme_dir')?omurga_theme_dir():''),'Aktif tema klasörü',function_exists('omurga_active_theme')?omurga_active_theme():'Bilinmiyor','Tema/Paket');
    $r[]=omst_result(is_dir(OMURGA_ROOT.'/packages'),'Paket klasörü','packages klasörü erişilebilir.','Tema/Paket');
    $r[]=omst_result(function_exists('omurga_core_guard_enabled') && omurga_core_guard_enabled(),'Core Guard','Çekirdek koruma aktif olmalı.','Güvenlik');
    $r[]=omst_result(function_exists('csrf_token') && csrf_token() !== '','CSRF token','Admin formları için token üretilebiliyor.','Güvenlik');
    $r[]=omst_result(function_exists('omurga_url'),'URL üretici','Site endpointleri üretilebilir.','SEO/API');
    foreach(['sitemap.xml','feed.xml','atom.xml','google-news.xml','robots.txt','api/v1/status'] as $ep){ $r[]=omst_result(true,$ep,omurga_url($ep),'SEO/API'); }
    return $r;
}
$ran = $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['run_tests'] ?? '')==='1';
$results = $ran ? omst_run_tests() : [];
$okCount = $ran ? count(array_filter($results,fn($x)=>$x['ok'])) : 0; $total=count($results);
$groups=[]; foreach($results as $row){$groups[$row['group']][]=$row;}
?>
<div class="toolbar compact-page-head">
  <div><h1>Sistem Testleri</h1><p class="muted">Kurulum, güncelleme ve canlıya alma öncesi temel Omurga fonksiyonlarını bozmadan test eder.</p></div>
  <div><a class="btn light" href="health-check.php">Sağlık Kontrolü</a></div>
</div>
<section class="card compact-panel">
  <form method="post">
    <?=csrf_field()?>
    <input type="hidden" name="run_tests" value="1">
    <p>Bu testler veritabanı bağlantısı, dosya yazma/silme, migration, güvenlik, tema/paket klasörleri ve SEO/API endpoint linklerini kontrol eder. İçerik oluşturmaz ve mevcut veriyi silmez.</p>
    <button class="btn primary" type="submit">Testleri Çalıştır</button>
  </form>
</section>
<?php if($ran): ?>
<section class="card compact-panel" style="margin-top:16px">
  <div class="stat-grid compact-stats"><div class="stat"><b><?=e((string)$total)?></b><span>Toplam Test</span></div><div class="stat"><b><?=e((string)$okCount)?></b><span>Başarılı</span></div><div class="stat"><b><?=e((string)($total-$okCount))?></b><span>Başarısız/Uyarı</span></div><div class="stat"><b><?=e(OMURGA_VERSION)?></b><span>Sürüm</span></div></div>
</section>
<?php foreach($groups as $group=>$rows): ?>
<section class="card compact-panel" style="margin-top:16px"><div class="compact-panel-head"><h2><?=e($group)?></h2></div><div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Test</th><th>Sonuç</th><th>Açıklama</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?=e($r['name'])?></td><td><?=omst_badge((bool)$r['ok'])?></td><td><small><?=e($r['message'])?></small></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endforeach; ?>
<?php else: ?>
<section class="card compact-panel" style="margin-top:16px"><p class="muted">Henüz test çalıştırılmadı.</p></section>
<?php endif; ?>
<?php require __DIR__.'/_footer.php'; ?>
