<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
verify_csrf();

function omg_admin_performance_defaults(): array {
    if(function_exists('omurga_performance_defaults')) return omurga_performance_defaults();
    return [
        'perf_page_cache'=>'1','perf_block_cache'=>'1','perf_template_cache'=>'1','perf_minify_html'=>'0',
        'perf_page_cache_ttl'=>'900','perf_block_cache_ttl'=>'600','perf_template_cache_ttl'=>'3600','perf_asset_cache_ttl'=>'86400',
        'perf_auto_clear_on_publish'=>'1'
    ];
}
function omg_admin_is_ttl_key(string $key): bool { return substr($key, -4)==='_ttl'; }
function omg_admin_save_performance_settings(array $data): void {
    if(function_exists('omurga_save_performance_settings')){ omurga_save_performance_settings($data); return; }
    foreach(omg_admin_performance_defaults() as $key=>$default){
        if(omg_admin_is_ttl_key($key)) $v=(string)max(30,min(86400,(int)($data[$key] ?? $default)));
        else $v=isset($data[$key]) ? '1' : '0';
        update_setting($key,$v);
    }
}
function omg_admin_cache_base_dir(): string {
    $dir = OMURGA_ROOT.'/storage/cache';
    if(!is_dir($dir)) @mkdir($dir, 0775, true);
    foreach(['pages','blocks','templates','assets'] as $sub){ if(!is_dir($dir.'/'.$sub)) @mkdir($dir.'/'.$sub, 0775, true); }
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
    foreach(glob($dir.'/*') ?: [] as $file){ if(is_file($file) && @unlink($file)) $count++; }
    return $count;
}
function omg_admin_cache_clear(?string $type=null): int {
    if(function_exists('omurga_cache_clear')) return (int)omurga_cache_clear($type);
    if($type) return omg_admin_delete_cache_type($type);
    $count=0; foreach(['pages','blocks','templates','assets'] as $t) $count += omg_admin_delete_cache_type($t); return $count;
}
function omg_admin_cache_stats(): array {
    if(function_exists('omurga_cache_stats')) return omurga_cache_stats();
    $stats=[]; $base=omg_admin_cache_base_dir();
    foreach(['pages'=>'Sayfa','blocks'=>'Blok','templates'=>'Şablon','assets'=>'Varlık'] as $dir=>$label){
        $path=$base.'/'.$dir; $files=0; $size=0;
        if(is_dir($path)) foreach(glob($path.'/*') ?: [] as $f){ if(is_file($f)){ $files++; $size += (int)@filesize($f); } }
        $stats[$dir]=['label'=>$label,'files'=>$files,'size'=>$size,'size_human'=>omg_admin_format_bytes($size)];
    }
    return $stats;
}
function omg_admin_system_cleanup(array $opts): array {
    $result=['items'=>0,'notes'=>[]];
    if(!empty($opts['autosaves'])){
        try{
            $t=table_name('post_autosaves');
            $n=db()->exec("DELETE FROM $t WHERE updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $result['items'] += (int)$n; $result['notes'][]=(int)$n.' eski otomatik kayıt temizlendi.';
        }catch(Throwable $e){ $result['notes'][]='Otomatik kayıt temizliği yapılamadı: '.$e->getMessage(); }
    }
    if(!empty($opts['notifications']) && function_exists('omurga_clear_old_notifications')){
        try{ omurga_clear_old_notifications(90); $result['notes'][]='90 günden eski bildirimler temizlendi.'; }catch(Throwable $e){ $result['notes'][]='Bildirim temizliği yapılamadı: '.$e->getMessage(); }
    }
    if(!empty($opts['update_logs']) && function_exists('omurga_cleanup_old_update_logs')){
        try{ omurga_cleanup_old_update_logs(); $result['notes'][]='Eski güncelleme logları sınırlandı.'; }catch(Throwable $e){ $result['notes'][]='Güncelleme log temizliği yapılamadı: '.$e->getMessage(); }
    }
    if(!empty($opts['update_packages']) && function_exists('omurga_cleanup_uploaded_update_packages')){
        try{ omurga_cleanup_uploaded_update_packages(); $result['notes'][]='Eski yükleme paketleri sınırlandı.'; }catch(Throwable $e){ $result['notes'][]='Paket temizliği yapılamadı: '.$e->getMessage(); }
    }
    if(!empty($opts['tmp_files'])){
        $dirs=[OMURGA_ROOT.'/storage/tmp', OMURGA_ROOT.'/storage/cache'];
        $removed=0;
        foreach($dirs as $dir){
            if(!is_dir($dir)) continue;
            $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach($it as $file){
                if(!$file->isFile()) continue;
                $name=$file->getFilename();
                if(!preg_match('/\.(tmp|temp|part)$/i',$name)) continue;
                if($file->getMTime() < time()-86400 && @unlink($file->getPathname())) $removed++;
            }
        }
        $result['items'] += $removed; $result['notes'][]=$removed.' geçici dosya temizlendi.';
    }
    if(function_exists('log_activity')) log_activity('system.cleanup','Performans / Cache ekranından sistem temizliği çalıştırıldı.', null, 'system');
    return $result;
}
function perf_checked($key){ $d=omg_admin_performance_defaults(); return setting($key, $d[$key] ?? '0')==='1' ? 'checked' : ''; }
function perf_value($key){ $d=omg_admin_performance_defaults(); return e(setting($key, $d[$key] ?? '')); }

$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action'] ?? 'save_performance';
        if($action==='save_performance'){
            omg_admin_save_performance_settings($_POST);
            $message='Performans ve cache ayarları kaydedildi.';
        } elseif($action==='clear_cache'){
            $map=['all'=>null,'pages'=>'pages','blocks'=>'blocks','templates'=>'templates','assets'=>'assets'];
            $type=$_POST['cache_type'] ?? 'all';
            if(array_key_exists($type,$map)){
                $count=omg_admin_cache_clear($map[$type]);
                $message=$count.' cache dosyası temizlendi.';
            }
        } elseif($action==='system_cleanup'){
            $cleanup=omg_admin_system_cleanup($_POST['cleanup'] ?? []);
            $message='Sistem temizliği tamamlandı. '.implode(' ', $cleanup['notes']);
        }
    }catch(Throwable $e){
        $error='İşlem tamamlanamadı: '.$e->getMessage();
        if(function_exists('log_activity')) log_activity('performance_cache.error',$error,null,'system');
    }
}
try{ $stats=omg_admin_cache_stats(); }catch(Throwable $e){ $stats=[]; $error='Cache bilgileri alınamadı: '.$e->getMessage(); }
$totalFiles=0; $totalSize=0; foreach($stats as $s){ $totalFiles += (int)$s['files']; $totalSize += (int)$s['size']; }
?>
<div class="page-head compact-head"><div><h1>Performans / Cache / Temizlik</h1><p>Hız ayarları, cache temizliği ve güvenli sistem temizliği tek ekranda.</p></div></div>
<?php if($message): ?><div class="notice success compact-notice"><?=e($message)?></div><?php endif; ?>
<?php if($error): ?><div class="notice danger compact-notice"><?=e($error)?></div><?php endif; ?>

<div class="omg-summary-strip compact-summary">
  <span><b><?=e((string)$totalFiles)?></b> cache dosyası</span>
  <span><b><?=e(omg_admin_format_bytes($totalSize))?></b> toplam boyut</span>
  <span><b><?=setting('perf_page_cache','1')==='1'?'Açık':'Kapalı'?></b> sayfa cache</span>
  <span><b><?=setting('perf_auto_clear_on_publish','1')==='1'?'Açık':'Kapalı'?></b> otomatik temizleme</span>
</div>

<section class="compact-panel">
  <div class="compact-panel-head"><h2>Cache Durumu</h2><form method="post" onsubmit="return confirm('Tüm cache temizlensin mi?');"><?=csrf_field()?><input type="hidden" name="action" value="clear_cache"><input type="hidden" name="cache_type" value="all"><button class="btn danger">Tümünü Temizle</button></form></div>
  <div class="compact-cache-list">
    <?php foreach($stats as $key=>$s): ?>
      <div class="compact-cache-row">
        <strong><?=e($s['label'])?></strong>
        <span><?=e((string)$s['files'])?> dosya</span>
        <small><?=e($s['size_human'])?></small>
        <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="clear_cache"><input type="hidden" name="cache_type" value="<?=e($key)?>"><button class="btn secondary">Temizle</button></form>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<form method="post" class="compact-panel compact-form"><?=csrf_field()?><input type="hidden" name="action" value="save_performance">
  <div class="compact-panel-head"><h2>Performans Ayarları</h2><button class="btn primary">Kaydet</button></div>
  <div class="compact-check-grid">
    <label><input type="checkbox" name="perf_page_cache" value="1" <?=perf_checked('perf_page_cache')?>> Sayfa cache</label>
    <label><input type="checkbox" name="perf_block_cache" value="1" <?=perf_checked('perf_block_cache')?>> Blok cache</label>
    <label><input type="checkbox" name="perf_template_cache" value="1" <?=perf_checked('perf_template_cache')?>> Şablon cache</label>
    <label><input type="checkbox" name="perf_minify_html" value="1" <?=perf_checked('perf_minify_html')?>> HTML küçültme</label>
    <label><input type="checkbox" name="perf_auto_clear_on_publish" value="1" <?=perf_checked('perf_auto_clear_on_publish')?>> Yayında otomatik temizle</label>
  </div>
  <div class="compact-input-grid">
    <label>Sayfa TTL <input type="number" name="perf_page_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_page_cache_ttl')?>"></label>
    <label>Blok TTL <input type="number" name="perf_block_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_block_cache_ttl')?>"></label>
    <label>Şablon TTL <input type="number" name="perf_template_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_template_cache_ttl')?>"></label>
    <label>Varlık TTL <input type="number" name="perf_asset_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_asset_cache_ttl')?>"></label>
  </div>
</form>
<?php require_once __DIR__.'/_footer.php'; ?>
