<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
verify_csrf();

/**
 * Omurga CMS 1.0.2.2 Beta
 * Cache ekranı dayanıklı hale getirildi.
 * Bazı kurulumlarda v2.4 cache helper fonksiyonları eksik kaldığında 500 hatası oluşuyordu.
 */
function omg_admin_cache_base_dir(): string {
    $dir = OMURGA_ROOT.'/storage/cache';
    if(!is_dir($dir)) @mkdir($dir, 0775, true);
    foreach(['pages','blocks','templates','assets'] as $sub){
        if(!is_dir($dir.'/'.$sub)) @mkdir($dir.'/'.$sub, 0775, true);
    }
    return $dir;
}
function omg_admin_format_bytes($bytes): string {
    if(function_exists('omurga_format_bytes')) return omurga_format_bytes((int)$bytes);
    $bytes=(int)$bytes; $units=['B','KB','MB','GB']; $i=0;
    while($bytes>=1024 && $i<count($units)-1){ $bytes/=1024; $i++; }
    return round($bytes, $i?2:0).' '.$units[$i];
}
function omg_admin_delete_cache_type(string $type): int {
    if(function_exists('omurga_cache_delete_type')) return (int)omurga_cache_delete_type($type);
    $safe = preg_replace('/[^a-z0-9_-]/i','',$type) ?: 'pages';
    $dir = omg_admin_cache_base_dir().'/'.$safe;
    if(!is_dir($dir)) return 0;
    $count=0;
    foreach(glob($dir.'/*') ?: [] as $file){
        if(is_file($file) && @unlink($file)) $count++;
    }
    return $count;
}
function omg_admin_cache_clear(?string $type=null): int {
    if(function_exists('omurga_cache_clear')) return (int)omurga_cache_clear($type);
    if($type) return omg_admin_delete_cache_type($type);
    $count=0;
    foreach(['pages','blocks','templates','assets'] as $t) $count += omg_admin_delete_cache_type($t);
    return $count;
}
function omg_admin_cache_stats(): array {
    if(function_exists('omurga_cache_stats')) return omurga_cache_stats();
    $stats=[]; $base=omg_admin_cache_base_dir();
    foreach(['pages'=>'Sayfa cache','blocks'=>'Blok cache','templates'=>'OMG şablon cache','assets'=>'Varlık cache'] as $dir=>$label){
        $path=$base.'/'.$dir; $files=0; $size=0;
        if(is_dir($path)){
            foreach(glob($path.'/*') ?: [] as $f){
                if(is_file($f)){ $files++; $size += (int)@filesize($f); }
            }
        }
        $stats[$dir]=['label'=>$label,'files'=>$files,'size'=>$size,'size_human'=>omg_admin_format_bytes($size)];
    }
    return $stats;
}

$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action'] ?? '';
        $map=['all'=>null,'pages'=>'pages','blocks'=>'blocks','templates'=>'templates','assets'=>'assets'];
        if(array_key_exists($action,$map)){
            $count=omg_admin_cache_clear($map[$action]);
            $message=$count.' cache dosyası temizlendi.';
        }
    }catch(Throwable $e){
        $error='Cache temizlenirken hata oluştu: '.$e->getMessage();
        if(function_exists('log_activity')) log_activity('cache.error',$error);
    }
}
try{ $stats=omg_admin_cache_stats(); }
catch(Throwable $e){ $stats=[]; $error='Cache bilgileri alınamadı: '.$e->getMessage(); }
?>
<div class="page-head"><div><h1>Cache Yönetimi</h1><p>Sayfa, blok, OMG şablon ve varlık cache dosyalarını kontrol et.</p></div></div>
<?php if($message): ?><div class="notice success"><?=e($message)?></div><?php endif; ?>
<?php if($error): ?><div class="notice danger"><?=e($error)?></div><?php endif; ?>
<div class="card-grid">
<?php foreach($stats as $key=>$s): ?>
  <div class="stat-card"><b><?=e($s['label'])?></b><strong><?=e((string)$s['files'])?></strong><small><?=e($s['size_human'])?></small>
    <form method="post" style="margin-top:12px"><?=csrf_field()?><input type="hidden" name="action" value="<?=e($key)?>"><button class="btn secondary">Bu alanı temizle</button></form>
  </div>
<?php endforeach; ?>
</div>
<div class="panel-card" style="margin-top:20px"><h2>Toplu Temizleme</h2><p>Güncelleme, tema değişimi veya beklenmeyen görünüm sorunlarında tüm cache temizlenebilir.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="all"><button class="btn danger">Tüm Cache Temizle</button></form></div>
<div class="panel-card" style="margin-top:20px"><h2>Otomatik Temizleme Mantığı</h2><ul><li>İçerik yayınlanınca sayfa ve blok cache temizlenir.</li><li>Menü veya reklam değişikliklerinde ilgili ön yüz cache temizlenir.</li><li>OMG şablon dosyası değişirse şablon cache anahtarı otomatik yenilenir.</li></ul></div>
<?php require_once __DIR__.'/_footer.php'; ?>
