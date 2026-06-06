<?php
require_once __DIR__.'/_layout.php';
if (!can('system.update') && !can('settings.manage') && !can('system.manage')) {
    render_error_page(403, 'Yetkisiz Erişim', 'Bu sayfaya erişmek için sistem güncelleme veya genel ayar yetkisi gerekir.');
}

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
        } elseif ($action === 'download_update') {
            $path = OmurgaUpdater::downloadLatest();
            $info = OmurgaUpdater::inspectPackage($path);
            $msg = 'Güncelleme paketi indirildi ve bekleyen paketlere eklendi: ' . $info['file'] . ' / Sürüm: ' . OmurgaUpdater::displayVersion($info['version'] ?? 'bilinmiyor') . '. Uygulamak için aşağıdaki listeden “Uygula” butonuna basın.';
        } elseif ($action === 'upload_package') {
            $info = OmurgaUpdater::stageUploadedPackage($_FILES['update_zip'] ?? []);
            $msg = 'Manuel güncelleme paketi yüklendi ve bekleyen paketlere eklendi: ' . $info['file'] . ' / Sürüm: ' . OmurgaUpdater::displayVersion($info['version'] ?? 'bilinmiyor') . '. Uygulamak için aşağıdaki listeden “Uygula” butonuna basın.';
            if (empty($info['valid']) && !empty($info['error'])) {
                $err = 'Paket yüklendi ancak doğrulama uyarısı var: ' . $info['error'];
            }
        } elseif ($action === 'apply_staged') {
            $file = (string)($_POST['package_file'] ?? '');
            $result = OmurgaUpdater::applyStagedPackage($file);
            $msg = 'Bekleyen güncelleme paketi uygulandı. Hedef sürüm: ' . OmurgaUpdater::displayVersion($result['version']) . '. Kopyalanan dosya: ' . $result['copied'] . ', atlanan dosya: ' . $result['skipped'] . '.';
            if (!empty($result['version_warning'])) { $msg .= ' Uyarı: ' . $result['version_warning']; }
            try {
                db()->prepare('INSERT INTO '.table_name('update_logs').' (from_version,to_version,status,message,package_name,created_by) VALUES (?,?,?,?,?,?)')->execute([OMURGA_VERSION, $result['version'], 'completed', 'Bekleyen güncelleme paketi uygulandı.', basename($file), $_SESSION['omurga_user_id'] ?? null]);
            } catch (Throwable $e) {
                omurga_write_error($e);
            }
        } elseif ($action === 'delete_staged') {
            $file = (string)($_POST['package_file'] ?? '');
            OmurgaUpdater::deleteStagedPackage($file);
            $msg = 'Bekleyen güncelleme paketi silindi: ' . basename($file);
        } elseif ($action === 'auto_update') {
            // GitHub güncellemesi: indirir ve doğrudan uygular.
            $result = OmurgaUpdater::applyDownloadedLatest();
            $msg = 'Güncelleme başarıyla uygulandı. Hedef sürüm: ' . OmurgaUpdater::displayVersion($result['version']) . '. Kopyalanan dosya: ' . $result['copied'] . ', atlanan dosya: ' . $result['skipped'] . '.';
            if (!empty($result['version_warning'])) { $msg .= ' Uyarı: ' . $result['version_warning']; }
        } elseif ($action === 'manual_upload') {
            // Geriye uyumluluk: Eski action artık doğrudan uygulamaz; sadece beklemeye alır.
            $info = OmurgaUpdater::stageUploadedPackage($_FILES['update_zip'] ?? []);
            $msg = 'Manuel güncelleme paketi yüklendi ve beklemeye alındı: ' . $info['file'] . '. Uygulamak için aşağıdaki listeden “Uygula” butonuna basın.';
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
$stagedPackages = OmurgaUpdater::stagedPackages(20);
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
      <li><code>config.php</code>, <code>uploads</code>, kullanıcı temaları ve kullanıcı paketleri korunur. Geliştirme aşamasında <code>storage</code> yazma engeli kapalıdır.</li>
      <li>Path traversal, symlink ve beklenmeyen dosya kontrolleri uygulanır.</li>
      <li>Aynı sürüm veya sürüm düşürme engellenmez; işlem öncesi onay uyarısı gösterilir ve loglanır.</li>
      <li>İşlem sırasında bakım modu açılır ve bitince kapatılır.</li>
    </ul>
  </section>
</div>

<section class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Güncelleme İşlemleri</h2>
  </div>
  <p class="muted">GitHub üzerinden bulunan güncellemeler istenirse tek tıkla hemen uygulanır. Manuel yüklenen paketler ise güvenlik için bekleyen paketlere alınır ve ayrıca uygulanır.</p>
  <div class="actions">
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="check_updates">
      <button class="btn primary">Güncellemeyi Kontrol Et</button>
    </form>
    <?php if($downloadUrl): ?>
      <?php if($hasUpdate): ?>
        <form method="post" onsubmit="return confirm('GitHub üzerindeki yeni sürüm indirilecek, yedek alınacak ve hemen uygulanacak. Devam edilsin mi?')">
          <?=csrf_field()?>
          <input type="hidden" name="action" value="auto_update">
          <button class="btn primary">Hemen Güncelle</button>
        </form>
        <form method="post" onsubmit="return confirm('Güncelleme paketi indirilecek ve bekleyen paketlere eklenecek. Daha sonra listeden uygulayabilirsiniz. Devam edilsin mi?')">
          <?=csrf_field()?>
          <input type="hidden" name="action" value="download_update">
          <button class="btn light">Daha Sonra Güncelle</button>
        </form>
      <?php else: ?>
        <form method="post" onsubmit="return confirm('Son GitHub paketi indirilecek ve bekleyen paketlere eklenecek. Kurulum hemen yapılmayacak. Devam edilsin mi?')">
          <?=csrf_field()?>
          <input type="hidden" name="action" value="download_update">
          <button class="btn light">Son Paketi İndir ve Beklet</button>
        </form>
      <?php endif; ?>
      <a class="btn light" href="<?=e($downloadUrl)?>" rel="noopener" target="_blank">Paketi Tarayıcıda İndir</a>
      <a class="btn light" href="#updates-changelog" onclick="var d=document.getElementById('updates-changelog'); if(d){d.open=true;}">Değişiklikleri Gör</a>
    <?php else: ?>
      <span class="muted">GitHub üzerinde indirilebilir Omurga güncelleme paketi bulunamadı.</span>
    <?php endif; ?>
  </div>
</section>

<section class="card" style="margin-top:18px">
  <h2>Manuel Güncelleme Paketi Yükle</h2>
  <p class="muted">Bu işlem paketi sadece <code>storage/updates</code> içine yükler ve doğrular. Kurulum için aşağıdaki listeden ayrıca “Uygula” demen gerekir.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="upload_package">
    <label>Güncelleme zipi <input type="file" name="update_zip" accept=".zip" required></label>
    <button class="btn primary">Manuel Paketi Yükle</button>
  </form>
</section>

<section class="card" style="margin-top:18px">
  <h2>Bekleyen Güncelleme Paketleri</h2>
  <p class="muted">Daha önce indirilen veya manuel yüklenen paketleri buradan uygulayabilir ya da silebilirsin.</p>
  <table>
    <thead><tr><th>Paket</th><th>Sürüm</th><th>Boyut</th><th>Tarih</th><th>Durum</th><th>İşlem</th></tr></thead>
    <tbody>
      <?php foreach($stagedPackages as $pkg): ?>
        <tr>
          <td><?=e($pkg['file'])?></td>
          <td><?=e(OmurgaUpdater::displayVersion($pkg['version'] ?? 'bilinmiyor'))?></td>
          <td><?=number_format((int)($pkg['size'] ?? 0)/1024, 1)?> KB</td>
          <td><?=e($pkg['date'] ?? '-')?></td>
          <td>
            <?php if(!empty($pkg['valid'])): ?>
              <span class="pill-ok">Doğrulandı</span>
            <?php else: ?>
              <span class="pill-bad">Sorunlu</span>
              <?php if(!empty($pkg['error'])): ?><div class="muted"><?=e($pkg['error'])?></div><?php endif; ?>
            <?php endif; ?>
          </td>
          <td>
            <div class="actions">
              <?php if(!empty($pkg['valid'])): ?>
                <form method="post" onsubmit="return confirm('Bu güncelleme paketi uygulanacak. Önce yedek alınacak ve bakım modu açılacak. Aynı sürüm veya düşük sürüm engellenmeyecek. Devam edilsin mi?')">
                  <?=csrf_field()?>
                  <input type="hidden" name="action" value="apply_staged">
                  <input type="hidden" name="package_file" value="<?=e($pkg['file'])?>">
                  <button class="btn primary">Uygula</button>
                </form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Bu bekleyen paketi silmek istiyor musun?')">
                <?=csrf_field()?>
                <input type="hidden" name="action" value="delete_staged">
                <input type="hidden" name="package_file" value="<?=e($pkg['file'])?>">
                <button class="btn light">Sil</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$stagedPackages): ?><tr><td colspan="6">Bekleyen güncelleme paketi yok. Önce GitHub’dan indir veya manuel zip yükle.</td></tr><?php endif; ?>
    </tbody>
  </table>
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
