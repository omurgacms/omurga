<?php
require_once __DIR__.'/_layout.php';
require_cap('system.manage');

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'run') {
            if (function_exists('create_database_backup')) create_database_backup();
            omurga_migrate(true);
            if (function_exists('log_activity')) log_activity('system.migrations', 'Migration runner elle çalıştırıldı');
            $msg = 'Migration kontrolleri çalıştırıldı. İşlem öncesi yedek alınmaya çalışıldı.';
        }
        if ($action === 'refresh') {
            omurga_migrate();
            $msg = 'Migration durumu yenilendi.';
        }
    } catch (Throwable $e) {
        omurga_write_error($e);
        $err = $e->getMessage();
    }
}

omurga_register_core_migrations();
$rows = omurga_migrations_status();
$total = count($rows);
$applied = count(array_filter($rows, fn($r) => ($r['status'] ?? '') === 'applied'));
$failed = count(array_filter($rows, fn($r) => ($r['status'] ?? '') === 'failed'));
$pending = max(0, $total - $applied - $failed);
?>
<div class="page-head compact-head">
  <div>
    <h1>Migration Durumu</h1>
    <p>Veritabanı güncellemeleri tek seferlik runner ile takip edilir. Böylece SEO, medya, özel alan ve sistem tabloları her istekte tekrar tekrar kontrol edilmez.</p>
  </div>
  <div class="head-actions">
    <form method="post" style="display:inline-flex;gap:8px;flex-wrap:wrap">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
      <button class="btn" name="action" value="refresh">Yenile</button>
      <button class="btn primary" name="action" value="run">Kontrolü Çalıştır</button>
    </form>
  </div>
</div>
<?php if($msg): ?><div class="notice ok"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="notice error"><?=e($err)?></div><?php endif; ?>

<div class="compact-summary">
  <div><strong><?=e((string)$total)?></strong><span>Toplam</span></div>
  <div><strong><?=e((string)$applied)?></strong><span>Uygulandı</span></div>
  <div><strong><?=e((string)$pending)?></strong><span>Bekliyor</span></div>
  <div><strong><?=e((string)$failed)?></strong><span>Hata</span></div>
</div>

<section class="card compact-card">
  <div class="table-responsive">
    <table class="admin-table compact-table">
      <thead><tr><th>Durum</th><th>Migration</th><th>Sürüm</th><th>Açıklama</th><th>Çalışma zamanı</th><th>Hata</th></tr></thead>
      <tbody>
      <?php if(!$rows): ?>
        <tr><td colspan="6">Migration kaydı bulunamadı.</td></tr>
      <?php endif; ?>
      <?php foreach($rows as $row): ?>
        <?php $status=(string)($row['status'] ?? 'pending'); ?>
        <tr>
          <td><span class="badge <?= $status==='applied'?'ok':($status==='failed'?'danger':'warn') ?>"><?=e($status==='applied'?'Uygulandı':($status==='failed'?'Hata':'Bekliyor'))?></span></td>
          <td><code><?=e($row['migration_key'] ?? '')?></code></td>
          <td><?=e($row['version'] ?? '')?></td>
          <td><?=e($row['description'] ?? '')?></td>
          <td><?=e($row['executed_at'] ?? '')?></td>
          <td class="muted"><?=e(mb_substr((string)($row['error_message'] ?? ''),0,120))?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="card compact-card">
  <h2>Not</h2>
  <p>Yeni runner, eski migration fonksiyonlarını korur; sadece hangi migration'ın uygulandığını kayıt altına alır. Eski URL ve fonksiyon isimleri bozulmadan devam eder.</p>
</section>
<?php require __DIR__.'/_footer.php'; ?>
