<?php
require_once __DIR__.'/_layout.php';
require_cap('system.update');
omurga_migrate();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'check_updates') {
            $check = OmurgaUpdater::check(true);
            $msg = $check['message'] ?? 'Güncelleme kontrolü tamamlandı.';
            if (($check['status'] ?? 'ok') === 'error') {
                $err = $check['error'] ?? $msg;
                $msg = '';
            }
        } elseif ($action === 'auto_update') {
            $result = OmurgaUpdater::applyDownloadedLatest();
            $msg = 'Güncelleme başarıyla uygulandı. Hedef sürüm: '.$result['version'].'. Kopyalanan dosya: '.$result['copied'].', atlanan dosya: '.$result['skipped'].'.';
            try {
                db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([OMURGA_VERSION, $result['version'], 'completed', 'GitHub üzerinden otomatik güncelleme tamamlandı.', 'github-release', $_SESSION['omurga_user_id'] ?? null]);
            } catch (Throwable $e) {
                omurga_write_error($e);
            }
        } elseif ($action === 'manual_upload') {
            if (empty($_FILES['update_zip']['name']) || ($_FILES['update_zip']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Güncelleme zip dosyası seçilmedi.');
            }
            $name = basename((string)$_FILES['update_zip']['name']);
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
                throw new RuntimeException('Sadece .zip güncelleme paketi yüklenebilir.');
            }
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '-', pathinfo($name, PATHINFO_FILENAME)).'-'.date('YmdHis').'.zip';
            $target = OmurgaUpdater::tmpDir().'/'.$safe;
            if (!move_uploaded_file($_FILES['update_zip']['tmp_name'], $target)) {
                throw new RuntimeException('Güncelleme paketi yüklenemedi.');
            }
            $result = OmurgaUpdater::applyPackage($target, 'manual');
            @unlink($target);
            $msg = 'Manuel güncelleme başarıyla uygulandı. Hedef sürüm: '.$result['version'].'. Kopyalanan dosya: '.$result['copied'].', atlanan dosya: '.$result['skipped'].'.';
            try {
                db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([OMURGA_VERSION, $result['version'], 'completed', 'Manuel güncelleme paketi uygulandı.', $name, $_SESSION['omurga_user_id'] ?? null]);
            } catch (Throwable $e) {
                omurga_write_error($e);
            }
        }
    } catch (Throwable $e) {
        omurga_write_error($e);
        $err = $e->getMessage();
    }
}

$check = OmurgaUpdater::check(false);
$hasUpdate = !empty($check['has_update']) && !empty($check['download_url']);
$statusClass = ($check['status'] ?? 'ok') === 'error' ? 'pill-bad' : ($hasUpdate ? 'pill-info' : 'pill-ok');
$downloadUrl = (string)($check['download_url'] ?? '');
$recentBackups = OmurgaUpdater::recentBackups(8);
?>
<div class="page-head">
  <div>
    <h1>Güncellemeler</h1>
    <p>GitHub Releases üzerinden güvenli çekirdek güncelleme kontrolü, manuel paket yükleme ve güncelleme öncesi yedek yönetimi.</p>
  </div>
</div>

<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<div class="system-grid">
  <section class="system-card">
    <h2>Sürüm Durumu</h2>
    <table class="status-table">
      <tbody>
        <tr><td>Mevcut sürüm</td><td><?=e(OmurgaUpdater::displayVersion($check['current_version'] ?? OMURGA_VERSION))?></td></tr>
        <tr><td>Son kontrol</td><td><?=e(!empty($check['checked_at']) ? date('Y-m-d H:i:s', strtotime($check['checked_at'])) : 'Henüz yok')?></td></tr>
        <tr><td>Son sürüm</td><td><?=e(OmurgaUpdater::displayVersion($check['latest_version'] ?? OMURGA_VERSION))?></td></tr>
        <tr><td>Yayın tarihi</td><td><?=e(!empty($check['release_date']) ? date('Y-m-d H:i:s', strtotime($check['release_date'])) : '-')?></td></tr>
        <tr><td>Durum</td><td><span class="<?=$statusClass?>"><?=e($check['message'] ?? 'Bilinmiyor')?></span></td></tr>
      </tbody>
    </table>
    <?php if(!empty($check['error'])): ?><p class="muted">Teknik detay log dosyasına yazıldı. Özet: <?=e($check['error'])?></p><?php endif; ?>
  </section>

  <section class="system-card">
    <h2>Güvenlik</h2>
    <ul class="muted">
      <li>Güncelleme başlamadan önce veritabanı ve çekirdek dosya yedeği alınır.</li>
      <li><code>config.php</code>, <code>uploads</code>, <code>storage</code>, kullanıcı temaları ve kullanıcı paketleri korunur.</li>
      <li>Path traversal, symlink, beklenmeyen dosya ve sürüm düşürme kontrolleri uygulanır.</li>
      <li>İşlem sırasında bakım modu açılır ve bitince kapatılır.</li>
    </ul>
  </section>
</div>

<section class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Güncelleme İşlemleri</h2>
  </div>
  <div class="actions">
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="check_updates">
      <button class="btn primary">Güncellemeyi Kontrol Et</button>
    </form>
    <?php if($hasUpdate): ?>
      <form method="post" onsubmit="return confirm('Güncelleme öncesi yedek alınacak ve bakım modu açılacak. Devam edilsin mi?')">
        <?=csrf_field()?>
        <input type="hidden" name="action" value="auto_update">
        <button class="btn primary">Otomatik Güncelle</button>
      </form>
      <a class="btn light" href="<?=e($downloadUrl)?>" rel="noopener" target="_blank">Paketi İndir</a>
      <a class="btn light" href="#updates-changelog" onclick="var d=document.getElementById('updates-changelog'); if(d){d.open=true;}">Değişiklikleri Gör</a>
    <?php else: ?>
      <span class="muted">Otomatik güncelleme için yeni ve doğrulanmış bir paket bulunmalı.</span>
    <?php endif; ?>
  </div>
</section>

<section class="card" style="margin-top:18px">
  <h2>Manuel Güncelleme Paketi Yükle</h2>
  <p class="muted">Yüklenen paket de otomatik güncellemeyle aynı doğrulama, yedekleme, bakım modu ve migration akışından geçer.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid" onsubmit="return confirm('Manuel güncelleme paketi uygulanacak. Devam edilsin mi?')">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="manual_upload">
    <label>Güncelleme zipi <input type="file" name="update_zip" accept=".zip" required></label>
    <button class="btn primary">Manuel Paketi Yükle ve Uygula</button>
  </form>
</section>

<details class="card" id="updates-changelog" style="margin-top:18px">
  <summary><strong>Değişiklik Notları</strong></summary>
  <?php if(!empty($check['changelog'])): ?>
    <pre class="codebox"><?=e($check['changelog'])?></pre>
  <?php else: ?>
    <p class="muted">Bu release için değişiklik notu bulunamadı.</p>
  <?php endif; ?>
</details>

<section class="card" style="margin-top:18px">
  <h2>Son Yedekler</h2>
  <table>
    <thead><tr><th>Dosya</th><th>Boyut</th><th>Tarih</th></tr></thead>
    <tbody>
      <?php foreach($recentBackups as $backup): ?>
        <tr><td><?=e($backup['name'])?></td><td><?=number_format((int)$backup['size']/1024, 1)?> KB</td><td><?=e($backup['date'])?></td></tr>
      <?php endforeach; ?>
      <?php if(!$recentBackups): ?><tr><td colspan="3">Henüz güncelleme yedeği yok.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__.'/_footer.php'; ?>
