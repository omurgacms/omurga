<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
verify_csrf();
$notice=''; $error='';

$defaults = [
  'theme_editor'=>'1',
  'php_file_edit'=>'0',
  'plugin_upload'=>'1',
  'plugin_delete'=>'0',
  'login_fail_limit'=>'5',
  'login_lock_minutes'=>'15',
  'maintenance_mode'=>'0',
  'maintenance_message'=>'Sitemiz kısa süreli bakım modundadır. Lütfen daha sonra tekrar deneyin.',
  'blocked_ips'=>'',
];

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $checks=['theme_editor','php_file_edit','plugin_upload','plugin_delete','maintenance_mode'];
        foreach($checks as $k){ update_setting('security_'.$k, isset($_POST[$k]) ? '1':'0'); }
        update_setting('security_login_fail_limit', (string)max(1,min(20,(int)($_POST['login_fail_limit'] ?? 5))));
        update_setting('security_login_lock_minutes', (string)max(1,min(1440,(int)($_POST['login_lock_minutes'] ?? 15))));
        update_setting('security_maintenance_message', trim((string)($_POST['maintenance_message'] ?? $defaults['maintenance_message'])));
        $ips=[]; foreach(preg_split('/\r\n|\r|\n/', (string)($_POST['blocked_ips'] ?? '')) as $line){ $line=trim($line); if($line!=='' && preg_match('/^[0-9a-fA-F:.]+$/',$line)) $ips[]=$line; }
        update_setting('security_blocked_ips', implode("\n", array_unique($ips)));
        log_activity('security.save','Güvenlik ayarları güncellendi.');
        if(function_exists('omurga_notify')) omurga_notify('Güvenlik ayarları güncellendi','Güvenlik merkezi ayarları kaydedildi.','security','admin/security.php');
        $notice='Güvenlik ayarları kaydedildi.';
    }catch(Throwable $e){ $error=$e->getMessage(); omurga_write_error($e); }
}
function secv($key,$default=''){ return e(setting('security_'.$key,$default)); }
function secc($key,$default='1'){ return setting('security_'.$key,$default)==='1' ? 'checked' : ''; }
?>
<div class="page-head"><div><h1>Güvenlik Merkezi</h1><p>Tema düzenleyici, eklenti yükleme, PHP dosya düzenleme, bakım modu ve giriş güvenliği ayarları.</p></div></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<form method="post" class="settings-grid">
<input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
<div class="card"><h2>Dosya ve tema güvenliği</h2>
  <label class="check"><input type="checkbox" name="theme_editor" value="1" <?=secc('theme_editor','1')?>> Tema Düzenleyici açık olsun</label>
  <label class="check"><input type="checkbox" name="php_file_edit" value="1" <?=secc('php_file_edit','0')?>> Tema Düzenleyici içinde PHP dosyası düzenlemeye izin ver</label>
  <p class="muted">OMG, CSS, JS ve JSON düzenleme açık kalabilir; PHP düzenleme varsayılan olarak kapalıdır.</p>
</div>
<div class="card"><h2>Eklenti güvenliği</h2>
  <label class="check"><input type="checkbox" name="plugin_upload" value="1" <?=secc('plugin_upload','1')?>> Eklenti ZIP yükleme açık olsun</label>
  <label class="check"><input type="checkbox" name="plugin_delete" value="1" <?=secc('plugin_delete','0')?>> Eklenti silmeye izin ver</label>
  <p class="muted">Eklenti silme varsayılan olarak kapalıdır. Aktif/pasif işlemleri normal eklenti ekranında yapılır.</p>
</div>
<div class="card"><h2>Giriş güvenliği</h2>
  <label>Başarısız giriş limiti</label><input type="number" name="login_fail_limit" min="1" max="20" value="<?=secv('login_fail_limit','5')?>">
  <label>Kilit süresi / dakika</label><input type="number" name="login_lock_minutes" min="1" max="1440" value="<?=secv('login_lock_minutes','15')?>">
  <label>Engellenen IP adresleri</label><textarea name="blocked_ips" rows="5" placeholder="Her satıra bir IP"><?=secv('blocked_ips','')?></textarea>
</div>
<div class="card"><h2>Bakım modu</h2>
  <label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?=secc('maintenance_mode','0')?>> Bakım modunu aç</label>
  <label>Bakım mesajı</label><textarea name="maintenance_message" rows="4"><?=secv('maintenance_message',$defaults['maintenance_message'])?></textarea>
</div>
<div class="card full"><button class="btn primary">Güvenlik Ayarlarını Kaydet</button></div>
</form>
<div class="card"><h2>Korunan çekirdek alanları</h2><p><code>admin</code>, <code>core</code>, <code>config</code>, <code>storage</code>, <code>uploads</code> ve sistem dosyaları tema düzenleyiciden düzenlenemez. Tema düzenleyici sadece <code>themes/</code> içinde çalışır.</p></div>
<?php require_once __DIR__.'/_footer.php'; ?>
