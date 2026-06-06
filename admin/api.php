<?php
require_once __DIR__.'/../bootstrap.php';
require_admin();
if(!can('api.manage') && !can('settings.manage') && !can('system.manage')){ http_response_code(403); exit('Erişim engellendi.'); }
$notice=''; $error=''; $newToken='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $action=$_POST['action'] ?? '';
    try{
        if($action==='settings'){
            update_setting('api_enabled', !empty($_POST['api_enabled']) ? '1' : '0');
            update_setting('api_cors_origin', trim((string)($_POST['api_cors_origin'] ?? '')));
            $notice='API ayarları kaydedildi.';
        } elseif($action==='create_token'){
            $name=trim((string)($_POST['name'] ?? '')) ?: 'API Anahtarı';
            $scopes=$_POST['scopes'] ?? [];
            if(!is_array($scopes)) $scopes=[];
            $allowed=['read','write','*'];
            $scopes=array_values(array_intersect($allowed, $scopes));
            if(!$scopes) $scopes=['read'];
            $newToken=omurga_api_make_token();
            $tokens=omurga_api_tokens();
            $tokens[]=['id'=>bin2hex(random_bytes(6)),'name'=>$name,'hash'=>omurga_api_hash_token($newToken),'last4'=>omurga_api_token_last4($newToken),'scopes'=>$scopes,'status'=>'active','created_at'=>date('c'),'created_by'=>$_SESSION['omurga_user_id'] ?? null];
            omurga_update_api_tokens($tokens);
            $notice='API anahtarı oluşturuldu. Bu anahtarı yalnızca şimdi görebilirsiniz.';
        } elseif($action==='revoke'){
            $id=(string)($_POST['id'] ?? ''); $tokens=[];
            foreach(omurga_api_tokens() as $row){ if(($row['id'] ?? '')!==$id) $tokens[]=$row; }
            omurga_update_api_tokens($tokens); $notice='API anahtarı silindi.';
        }
    }catch(Throwable $e){ omurga_write_error($e); $error='İşlem tamamlanamadı.'; }
}
$tokens=omurga_api_tokens();
include __DIR__.'/_layout.php';
?>
<div class="page-head"><div><h1>REST API</h1><p>Harici uygulamalar ve entegrasyonlar için Omurga API erişimi.</p></div></div>
<?php if($notice): ?><div class="notice success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="notice error"><?=e($error)?></div><?php endif; ?>
<?php if($newToken): ?><div class="notice warning"><b>Yeni API anahtarı:</b><br><code style="word-break:break-all"><?=e($newToken)?></code><br><small>Bu değer tekrar gösterilmez.</small></div><?php endif; ?>
<div class="grid two">
  <section class="card">
    <h2>API Ayarları</h2>
    <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="settings">
      <label class="check"><input type="checkbox" name="api_enabled" value="1" <?=omurga_api_enabled()?'checked':''?>> REST API aktif</label>
      <label>CORS Origin <small>Boşsa kapalı. Örnek: https://site.com</small><input type="text" name="api_cors_origin" value="<?=e(setting('api_cors_origin',''))?>" placeholder="https://example.com"></label>
      <button class="btn primary">Ayarları Kaydet</button>
    </form>
  </section>
  <section class="card">
    <h2>Yeni API Anahtarı</h2>
    <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="create_token">
      <label>Ad <input type="text" name="name" placeholder="Mobil uygulama / entegrasyon"></label>
      <label class="check"><input type="checkbox" name="scopes[]" value="read" checked> Okuma</label>
      <label class="check"><input type="checkbox" name="scopes[]" value="write"> Yazma</label>
      <label class="check"><input type="checkbox" name="scopes[]" value="*"> Tam erişim</label>
      <button class="btn primary">Anahtar Oluştur</button>
    </form>
  </section>
</div>
<section class="card">
  <h2>Kayıtlı Anahtarlar</h2>
  <table class="wide"><thead><tr><th>Ad</th><th>Yetkiler</th><th>Son 4</th><th>Oluşturma</th><th></th></tr></thead><tbody>
    <?php if(!$tokens): ?><tr><td colspan="5" class="muted">Henüz API anahtarı yok.</td></tr><?php endif; ?>
    <?php foreach($tokens as $t): ?><tr><td><?=e($t['name'] ?? '')?></td><td><?=e(implode(', ', $t['scopes'] ?? []))?></td><td><code><?=e($t['last4'] ?? '')?></code></td><td><?=e($t['created_at'] ?? '')?></td><td><form method="post" onsubmit="return confirm('API anahtarı silinsin mi?')"><?=csrf_field()?><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?=e($t['id'] ?? '')?>"><button class="btn danger small">Sil</button></form></td></tr><?php endforeach; ?>
  </tbody></table>
</section>
<section class="card">
  <h2>Örnek Endpointler</h2>
  <p><code><?=e(omurga_url('api/status'))?></code></p>
  <p><code><?=e(omurga_url('api/posts'))?></code></p>
  <p><code><?=e(omurga_url('api/pages'))?></code></p>
  <p><code><?=e(omurga_url('api/categories'))?></code></p>
</section>
<?php include __DIR__.'/_footer.php'; ?>
