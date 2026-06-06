<?php
require_once __DIR__.'/_layout.php';
require_cap('users.manage');
omurga_migrate();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'maintenance') {
            update_setting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0');
            update_setting('maintenance_message', trim($_POST['maintenance_message'] ?? 'Sitemiz kısa süreli bakımda.'));
            log_activity('system.maintenance', 'Bakım modu güncellendi');
            $msg = 'Bakım modu ayarları kaydedildi.';
        }
        if ($action === 'run_migration') {
            create_database_backup();
            omurga_migrate();
            log_activity('system.migrate', 'Migration kontrolleri çalıştırıldı');
            $msg = 'Migration kontrolleri çalıştırıldı. İşlem öncesi veritabanı yedeği alındı.';
        }
    } catch (Throwable $e) {
        omurga_write_error($e);
        $err = $e->getMessage();
    }
}

$health = omurga_system_health_full();
$errors = omurga_recent_error_lines(100);
$canUpdate = can('system.update') || can('settings.manage') || can('system.manage');
?>
<div class="page-head">
  <div>
    <h1>Sistem Sağlığı</h1>
    <p>PHP, veritabanı, disk, cache, log, upload izinleri ve hata kayıtlarını kontrol eder. Güncelleme işlemleri ayrı Güncellemeler ekranından yapılır.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<?php
$healthOk = 0;
$healthWarn = 0;
$healthErr = 0;
foreach ($health as $h) {
    if (($h['level'] ?? 'ok') === 'ok') {
        $healthOk++;
    } elseif (($h['level'] ?? 'ok') === 'error') {
        $healthErr++;
    } else {
        $healthWarn++;
    }
}
?>

<div class="system-grid">
  <section class="system-card">
    <h2>Sistem Sağlığı</h2>
    <p>
      <span class="pill-ok">Sorunsuz: <?=e((string)$healthOk)?></span>
      <span class="pill-info">Uyarı: <?=e((string)$healthWarn)?></span>
      <span class="pill-bad">Hata: <?=e((string)$healthErr)?></span>
    </p>
    <table class="status-table">
      <thead>
        <tr><th>Kontrol</th><th>Değer</th><th>Durum</th><th>Not</th></tr>
      </thead>
      <tbody>
        <?php foreach($health as $row): ?>
          <tr>
            <td><?=e($row['name'])?></td>
            <td><?=e($row['value'])?></td>
            <td><span class="<?=$row['level']==='error'?'pill-bad':($row['level']==='warning'?'pill-info':'pill-ok')?>"><?=$row['level']==='error'?'Hata':($row['level']==='warning'?'Uyarı':'Sorunsuz')?></span></td>
            <td><?=e($row['note'] ?? '')?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="system-card">
    <h2>Bakım Modu</h2>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="maintenance">
      <label class="check"><input type="checkbox" name="maintenance_mode" value="1" <?=setting('maintenance_mode','0')==='1'?'checked':''?>> Bakım modunu aç</label>
      <label>Bakım Mesajı<textarea name="maintenance_message" rows="4"><?=e(setting('maintenance_message','Sitemiz kısa süreli bakımda.'))?></textarea></label>
      <button class="btn primary">Kaydet</button>
    </form>
  </section>
</div>

<div class="system-grid" style="margin-top:18px">
  <section class="system-card">
    <h2>Migration / Veritabanı Kontrolü</h2>
    <p>Eksik tabloları ve ayarları kontrol eder. İşlemden önce otomatik SQL yedeği alır.</p>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="run_migration">
      <button class="btn primary">Migration Kontrolünü Çalıştır</button>
    </form>
  </section>

  <section class="system-card">
    <h2>Güncelleme Durumu</h2>
    <p>GitHub sürüm kontrolü, otomatik güncelleme, manuel güncelleme paketi yükleme, güncelleme logları ve yedekler artık tek merkezden yönetilir.</p>
    <?php if($canUpdate): ?>
      <a class="btn primary" href="updates.php">Güncellemeler Sayfasına Git</a>
    <?php else: ?>
      <p class="muted">Güncellemeleri yönetmek için sistem güncelleme veya genel ayar yetkisi gerekir.</p>
    <?php endif; ?>
  </section>
</div>

<section class="card" style="margin-top:18px">
  <h2>Son 100 Hata Kaydı</h2>
  <?php if($errors): ?>
    <pre class="codebox"><?=e(implode("\n", $errors))?></pre>
  <?php else: ?>
    <p>Henüz hata kaydı yok.</p>
  <?php endif; ?>
</section>

<?php require __DIR__.'/_footer.php'; ?>
