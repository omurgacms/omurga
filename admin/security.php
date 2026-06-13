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
  'core_guard'=>'1',
  'developer_mode'=>'0',
  'login_fail_limit'=>'5',
  'login_lock_minutes'=>'15',
  'maintenance_mode'=>'0',
  'maintenance_message'=>'Sitemiz kısa süreli bakım modundadır. Lütfen daha sonra tekrar deneyin.',
  'blocked_ips'=>'',
];

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action'] ?? 'save_settings';
        if($action==='create_integrity'){
            $manifest=omurga_core_integrity_save();
            log_activity('security.integrity_create','Çekirdek bütünlük kaydı oluşturuldu.');
            $notice='Çekirdek bütünlük kaydı oluşturuldu. Dosya sayısı: '.count($manifest);
        } else {
        $checks=['theme_editor','php_file_edit','plugin_upload','plugin_delete','maintenance_mode'];
        foreach($checks as $k){ update_setting('security_'.$k, isset($_POST[$k]) ? '1':'0'); }
        update_setting('security_core_guard', '1');
        update_setting('security_developer_mode', (!omurga_is_production() && isset($_POST['developer_mode'])) ? '1':'0');
        update_setting('security_login_fail_limit', (string)max(1,min(20,(int)($_POST['login_fail_limit'] ?? 5))));
        update_setting('security_login_lock_minutes', (string)max(1,min(1440,(int)($_POST['login_lock_minutes'] ?? 15))));
        update_setting('security_maintenance_message', trim((string)($_POST['maintenance_message'] ?? $defaults['maintenance_message'])));
        $ips=[]; foreach(preg_split('/\r\n|\r|\n/', (string)($_POST['blocked_ips'] ?? '')) as $line){ $line=trim($line); if($line!=='' && preg_match('/^[0-9a-fA-F:.]+$/',$line)) $ips[]=$line; }
        update_setting('security_blocked_ips', implode("\n", array_unique($ips)));
        log_activity('security.save','Güvenlik ayarları güncellendi.');
        if(function_exists('omurga_notify')) omurga_notify('Güvenlik ayarları güncellendi','Güvenlik merkezi ayarları kaydedildi.','security','admin/security.php');
        $notice='Güvenlik ayarları kaydedildi.';
        }
    }catch(Throwable $e){ $error=$e->getMessage(); omurga_write_error($e); }
}
$integrity=omurga_core_integrity_check();
function secv($key,$default=''){ return e(setting('security_'.$key,$default)); }
function secc($key,$default='1'){ return setting('security_'.$key,$default)==='1' ? 'checked' : ''; }
?>
<div class="page-head"><div><h1>Güvenlik Merkezi</h1><p>Çekirdek koruması, tema/paket güvenliği, güvenli oturum, PHP dosya düzenleme, bakım modu ve giriş güvenliği ayarları.</p><p class="muted">Ortam: <?=e(omurga_environment())?> · Core Guard: zorunlu aktif</p></div></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>

<?php $report=omurga_security_center_report(); $scan=omurga_security_scan_extensions();
$okCount=0; $warnCount=0; $errCount=0; foreach($report['items'] as $it){ if($it['level']==='ok') $okCount++; elseif($it['level']==='error') $errCount++; else $warnCount++; }
?>
<div class="system-grid">
  <section class="system-card"><h2>Genel Güvenlik Durumu</h2>
    <div class="status-metrics"><span class="pill-ok">Sorunsuz: <?=e((string)$okCount)?></span> <span class="pill-info">Uyarı: <?=e((string)$warnCount)?></span> <span class="pill-bad">Hata: <?=e((string)$errCount)?></span></div>
    <table class="status-table"><tbody><?php foreach($report['items'] as $row): ?><tr><td><?=e($row['name'])?></td><td><?=e($row['value'])?></td><td><span class="<?=$row['level']==='error'?'pill-bad':($row['level']==='warning'?'pill-info':'pill-ok')?>"><?=$row['level']==='error'?'Hata':($row['level']==='warning'?'Uyarı':'Sorunsuz')?></span></td></tr><?php endforeach; ?></tbody></table>
  </section>
  <section class="system-card"><h2>Tema / Paket Güvenlik Taraması</h2>
    <table class="status-table"><thead><tr><th>Tür</th><th>Ad</th><th>Riskli Kullanım</th><th>Mod</th></tr></thead><tbody><?php foreach($scan as $s): ?><tr><td><?=e($s['type'])?></td><td><?=e($s['slug'])?></td><td><?php if(empty($s['risk'])): ?><span class="pill-ok">Temiz</span><?php else: ?><span class="pill-info"><?=e(implode(', ',$s['risk']))?></span><?php endif; ?></td><td><span class="pill-info"><?=e($s['mode'] ?? 'Bilgilendirme')?></span></td></tr><?php endforeach; if(!$scan): ?><tr><td colspan="4">Yüklü tema/paket bulunamadı.</td></tr><?php endif; ?></tbody></table>
    <p class="muted">Bu tarama yalnızca bilgilendirme amaçlıdır; tema veya paket yükleme/etkinleştirme işlemini engellemez. Çekirdek koruması ayrı çalışır ve core/admin/install/bootstrap alanlarını korumaya devam eder.</p>
  </section>
</div>

<form method="post" class="settings-grid">
<input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_settings">
<div class="card"><h2>Dosya ve tema güvenliği</h2>
  <label class="check"><input type="checkbox" name="theme_editor" value="1" <?=secc('theme_editor','1')?>> Tema Düzenleyici açık olsun</label>
  <label class="check"><input type="checkbox" name="php_file_edit" value="1" <?=secc('php_file_edit','0')?>> Tema Düzenleyici içinde PHP dosyası düzenlemeye izin ver</label>
  <p class="muted">OMG, CSS, JS ve JSON düzenleme açık kalabilir; PHP düzenleme varsayılan olarak kapalıdır.</p>
</div>
<div class="card"><h2>Çekirdek koruması</h2>
  <label class="check"><input type="checkbox" name="core_guard" value="1" checked disabled> Çekirdek koruması her zaman aktif kalsın</label>
  <label class="check"><input type="checkbox" name="developer_mode" value="1" <?=secc('developer_mode','0')?> <?=omurga_is_production()?'disabled':''?>> Geliştirici modu açık olsun</label>
  <p class="muted">Geliştirici modu üretim ortamında korumayı kapatamaz. Production ortamında devre dışıdır; geliştirme ortamında yalnızca daha ayrıntılı log/uyarı üretir.</p>
</div>
<div class="card"><h2>Paket güvenliği</h2>
  <label class="check"><input type="checkbox" name="plugin_upload" value="1" <?=secc('plugin_upload','1')?>> Paket ZIP yükleme açık olsun</label>
  <label class="check"><input type="checkbox" name="plugin_delete" value="1" <?=secc('plugin_delete','0')?>> Paket silmeye izin ver</label>
  <p class="muted">Paket silme varsayılan olarak kapalıdır. Aktif/pasif işlemleri Paketler ekranında yapılır.</p>
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
<div class="card"><h2>Çekirdek Bütünlük Kontrolü</h2>
  <p><strong>Durum:</strong> <span class="badge <?=($integrity['status'] ?? '')==='ok'?'published':(($integrity['status'] ?? '')==='missing'?'pending':'draft')?>"><?=e($integrity['message'] ?? '')?></span></p>
  <?php if(!empty($integrity['created_at'])): ?><p class="muted">Kayıt tarihi: <?=e($integrity['created_at'])?></p><?php endif; ?>
  <?php if(($integrity['status'] ?? '')==='changed'): ?>
    <div class="alert error">Değişen: <?=count($integrity['changed'] ?? [])?> · Eksik: <?=count($integrity['missing'] ?? [])?> · Yeni: <?=count($integrity['new'] ?? [])?></div>
    <details><summary>Detayları göster</summary><pre class="codebox"><?=e(json_encode(['changed'=>$integrity['changed'],'missing'=>$integrity['missing'],'new'=>$integrity['new']], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))?></pre></details>
  <?php endif; ?>
  <form method="post" onsubmit="return confirm('Mevcut çekirdek dosyaları güvenilir kabul edilerek yeni bütünlük kaydı oluşturulacak. Devam edilsin mi?');">
    <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create_integrity">
    <button class="btn light">Bütünlük Kaydını Oluştur / Yenile</button>
  </form>
</div>
<div class="card"><h2>Korunan çekirdek alanları</h2><p><code>admin</code>, <code>core</code>, <code>install</code>, <code>vendor</code>, <code>bootstrap.php</code>, <code>config.php</code> ve sistem dosyaları tema/paket tarafından değiştirilemez. Temalar <code>themes/</code>, paketler <code>packages/</code>, medya ise <code>uploads/</code> ve <code>storage</code> içinde çalışır.</p><p class="muted">Engellenen yazma/silme denemeleri <code>storage/logs/security.log</code> dosyasına yazılır.</p></div>
<?php require_once __DIR__.'/_footer.php'; ?>
