<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
verify_csrf();

/**
 * Omurga v3.0.3
 * Performans ekranı dayanıklı hale getirildi.
 * Cache helper fonksiyonları eksik olsa bile ekran 500 vermeden açılır.
 */
function omg_admin_performance_defaults(): array {
    if(function_exists('omurga_performance_defaults')) return omurga_performance_defaults();
    return [
        'perf_page_cache'=>'1','perf_block_cache'=>'1','perf_template_cache'=>'1','perf_minify_html'=>'0',
        'perf_page_cache_ttl'=>'900','perf_block_cache_ttl'=>'600','perf_template_cache_ttl'=>'3600','perf_asset_cache_ttl'=>'86400',
        'perf_auto_clear_on_publish'=>'1'
    ];
}
function omg_admin_is_ttl_key(string $key): bool {
    return substr($key, -4)==='_ttl';
}
function omg_admin_save_performance_settings(array $data): void {
    if(function_exists('omurga_save_performance_settings')){
        omurga_save_performance_settings($data);
        return;
    }
    foreach(omg_admin_performance_defaults() as $key=>$default){
        if(omg_admin_is_ttl_key($key)){
            $v=(string)max(30,min(86400,(int)($data[$key] ?? $default)));
        } else {
            $v=isset($data[$key]) ? '1' : '0';
        }
        update_setting($key,$v);
    }
}
$defaults=omg_admin_performance_defaults();
$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        omg_admin_save_performance_settings($_POST);
        $message='Performans ayarları kaydedildi.';
    }catch(Throwable $e){
        $error='Performans ayarları kaydedilemedi: '.$e->getMessage();
        if(function_exists('log_activity')) log_activity('performance.error',$error);
    }
}
function perf_checked($key){ $d=omg_admin_performance_defaults(); return setting($key, $d[$key] ?? '0')==='1' ? 'checked' : ''; }
function perf_value($key){ $d=omg_admin_performance_defaults(); return e(setting($key, $d[$key] ?? '')); }
?>
<div class="page-head"><div><h1>Performans Ayarları</h1><p>Omurga cache ve HTML çıktı ayarlarını buradan yönet.</p></div></div>
<?php if($message): ?><div class="notice success"><?=e($message)?></div><?php endif; ?>
<?php if($error): ?><div class="notice danger"><?=e($error)?></div><?php endif; ?>
<form method="post" class="panel-card"><?=csrf_field()?>
  <h2>Cache Ayarları</h2>
  <label><input type="checkbox" name="perf_page_cache" value="1" <?=perf_checked('perf_page_cache')?>> Sayfa cache aktif</label><br>
  <label><input type="checkbox" name="perf_block_cache" value="1" <?=perf_checked('perf_block_cache')?>> Blok cache aktif</label><br>
  <label><input type="checkbox" name="perf_template_cache" value="1" <?=perf_checked('perf_template_cache')?>> OMG şablon cache aktif</label><br>
  <label><input type="checkbox" name="perf_minify_html" value="1" <?=perf_checked('perf_minify_html')?>> HTML küçültme aktif</label><br>
  <label><input type="checkbox" name="perf_auto_clear_on_publish" value="1" <?=perf_checked('perf_auto_clear_on_publish')?>> İçerik yayınlanınca otomatik cache temizle</label>
  <div class="form-grid" style="margin-top:18px">
    <label>Sayfa cache süresi (saniye)<input type="number" name="perf_page_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_page_cache_ttl')?>"></label>
    <label>Blok cache süresi (saniye)<input type="number" name="perf_block_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_block_cache_ttl')?>"></label>
    <label>OMG şablon cache süresi (saniye)<input type="number" name="perf_template_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_template_cache_ttl')?>"></label>
    <label>Varlık cache süresi (saniye)<input type="number" name="perf_asset_cache_ttl" min="30" max="86400" value="<?=perf_value('perf_asset_cache_ttl')?>"></label>
  </div>
  <button class="btn primary" style="margin-top:18px">Kaydet</button>
</form>
<div class="panel-card" style="margin-top:20px"><h2>Not</h2><p>Bu ekran sadece Omurga çekirdeğinin hız ayarlarını yönetir. Vitrin ve tasarım alanları tema, blok veya eklenti tarafında kalır.</p></div>
<?php require_once __DIR__.'/_footer.php'; ?>
