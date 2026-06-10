<?php
require_once __DIR__.'/_layout.php';
require_cap('plugins.manage');
verify_csrf();

$notice=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    $slug=omurga_package_slug($_POST['slug'] ?? '');
    try{
        if($action==='upload_package'){
            $file=$_FILES['package_zip'] ?? [];
            if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Paket dosyası alınamadı.');
            if(($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Yükleme hatası: '.(int)$file['error']);
            if(strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip') throw new RuntimeException('Sadece .zip paketleri yüklenebilir.');
            $result=omurga_install_package_zip($file['tmp_name'], [
                'activate_after_upload'=>!empty($_POST['activate_after_upload']),
                'version_policy'=>(string)($_POST['version_policy'] ?? 'auto'),
                'permissions_accepted'=>!empty($_POST['permissions_accepted']),
            ]);
            $notice='Paket '.$result['install_action'].': '.$result['name'].' ('.(($result['installed_version'] ?? '') ?: 'yok').' → '.$result['uploaded_version'].')'.(!empty($result['active_after_upload']) ? ' — etkinleştirildi.' : ' — pasif bırakıldı.').(!empty($result['backup']) ? ' — önceki sürüm yedeklendi.' : '');
        }
        if($action==='activate' && $slug){ omurga_activate_package($slug); $notice='Paket etkinleştirildi.'; }
        if($action==='deactivate' && $slug){
            omurga_deactivate_package($slug);
            $notice='Paket devre dışı bırakıldı.';
            if(!empty($_POST['delete_after_deactivate'])){
                $result=omurga_delete_package($slug, !empty($_POST['backup_before_delete']));
                $notice='Paket devre dışı bırakıldı ve silindi.'.(!empty($result['backup']) ? ' Yedek alındı.' : '');
            }
        }
        if($action==='delete' && $slug){
            $result=omurga_delete_package($slug, !empty($_POST['backup_before_delete']));
            $notice='Paket silindi.'.(!empty($result['backup']) ? ' Yedek alındı.' : '');
        }
    }catch(Throwable $e){ $error=$e->getMessage(); omurga_write_error($e); }
}

$packages=omurga_all_packages();
?>
<div class="page-head"><div><h1>Paketler</h1><p>Omurga package.json standardıyla çalışan resmi paket sistemi.</p></div></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>

<section class="card">
  <h2>Paket Yükle / Güncelle</h2>
  <p class="muted">.zip içinde <code>package.json</code> bulunan paketleri yükleyebilirsin. Paket yüklenince artık otomatik etkinleşmez; aşağıdan isteğe bağlı etkinleştirme seçilir.</p>
  <div class="alert pending"><b>İzin sistemi:</b> Paket özel yetki istiyorsa kurulum için izin onayı gerekir. <code>database</code>, <code>media</code>, <code>cron</code>, <code>users</code>, <code>network</code>, <code>storage</code>, <code>settings</code>, <code>admin_pages</code>, <code>blocks</code> izinleri desteklenir.</div>
  <form method="post" enctype="multipart/form-data" class="inline-form">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="upload_package">
    <input type="file" name="package_zip" accept=".zip,application/zip" required>
    <label><input type="checkbox" name="activate_after_upload" value="1"> Yükledikten sonra etkinleştir</label>
    <label><input type="checkbox" name="permissions_accepted" value="1"> Paket manifestindeki izinleri okudum ve kabul ediyorum</label>
    <select name="version_policy">
      <option value="auto">Aynı paket varsa: yüksekse güncelle, aynıysa yenile, düşükse sürüm düşür</option>
      <option value="only_newer">Sadece daha yüksek sürümse güncelle</option>
      <option value="only_same">Sadece aynı sürümü yeniden kur</option>
      <option value="only_lower">Sadece düşük sürüme düşür</option>
    </select>
    <button class="btn primary">Paket Yükle</button>
  </form>
</section>

<section class="card" style="margin-top:18px">
  <h2>Yüklü Paketler</h2>
  <?php if(!$packages): ?><p class="muted">Henüz package.json içeren paket yok. <code>packages/ornek-paket/package.json</code> yapısını kullanın.</p><?php endif; ?>
  <?php $pkgSummary=['total'=>count($packages),'active'=>0,'passive'=>0,'warning'=>0]; foreach($packages as $s=>$pkg){ $isActive=omurga_package_is_active($s); if($isActive) $pkgSummary['active']++; else $pkgSummary['passive']++; if(empty($pkg['compatible']) || !empty($pkg['standard_warnings'])) $pkgSummary['warning']++; } ?>
  <div class="omg-summary-strip">
    <span><b><?=e((string)$pkgSummary['total'])?></b> Toplam paket</span>
    <span><b><?=e((string)$pkgSummary['active'])?></b> Aktif</span>
    <span><b><?=e((string)$pkgSummary['passive'])?></b> Pasif</span>
    <span><b><?=e((string)$pkgSummary['warning'])?></b> Uyarı/kontrol</span>
  </div>
  <div class="omg-view-toggle" aria-label="Görünüm seçimi"><span>Görünüm</span><button type="button" class="active" data-omg-view="list">Liste</button><button type="button" data-omg-view="grid">Kart</button></div>
  <div class="package-list compact-object-list" data-omg-list-view="list">
    <?php foreach($packages as $slug=>$pkg): $active=omurga_package_is_active($slug); $warningCount=(empty($pkg['compatible'])?1:0)+(!empty($pkg['standard_warnings'])?count($pkg['standard_warnings']):0); ?>
      <article class="object-row package-row <?=$active?'active':''?>">
        <div class="object-icon"><?=e(strtoupper(substr((string)($pkg['name'] ?: $slug),0,1)))?></div>
        <div class="object-main">
          <div class="object-head">
            <div>
              <h3><?=e($pkg['name'])?></h3>
              <p class="object-meta">v<?=e($pkg['version'])?><?=!empty($pkg['author'])?' · '.e($pkg['author']):''?> · slug: <code><?=e($slug)?></code> · blok: <?=count($pkg['blocks'] ?? [])?> · admin: <?=count($pkg['admin_pages'] ?? [])?></p>
            </div>
            <div class="object-badges"><span class="badge <?=$active?'published':'muted'?>"><?=$active?'Aktif':'Pasif'?></span><?php if($warningCount>0): ?><span class="badge pending"><?=e((string)$warningCount)?> uyarı</span><?php else: ?><span class="badge published">Uyumlu</span><?php endif; ?></div>
          </div>
          <p class="object-desc"><?=e($pkg['description'] ?: 'Açıklama yok.')?></p>
          <details class="object-details">
            <summary>İzinler ve teknik detaylar</summary>
            <p class="muted">İzinler: <code><?=e(omurga_format_permissions($pkg['permissions'] ?? []))?></code></p>
            <?php if(!empty($pkg['permissions'])): ?><pre class="codebox"><?=e(omurga_permissions_html_summary($pkg['permissions']))?></pre><?php endif; ?>
            <?php if(empty($pkg['compatible'])): ?><div class="alert error"><?=e(implode(' ', $pkg['requirement_messages'] ?? ['Paket gereksinimleri karşılanmıyor.']))?></div><?php endif; ?>
            <?php if(!empty($pkg['standard_warnings'])): ?><div class="alert pending"><?=e(implode(' ', $pkg['standard_warnings']))?></div><?php endif; ?>
          </details>
        </div>
        <div class="object-actions">
          <form method="post" class="inline-form">
            <?=csrf_field()?><input type="hidden" name="slug" value="<?=e($slug)?>">
            <?php if($active): ?>
              <input type="hidden" name="action" value="deactivate">
              <label><input type="checkbox" name="delete_after_deactivate" value="1"> Devre dışı bırakınca sil</label>
              <label><input type="checkbox" name="backup_before_delete" value="1" checked> Yedekle</label>
              <button class="btn">Devre Dışı Bırak</button>
            <?php elseif(!empty($pkg['compatible'])): ?>
              <input type="hidden" name="action" value="activate"><button class="btn primary">Etkinleştir</button>
            <?php else: ?><button type="button" class="btn light" disabled>Etkinleştirilemez</button><?php endif; ?>
          </form>
          <?php if(!$active): ?>
            <form method="post" onsubmit="return confirm('Paketi silmek istediğinize emin misiniz?');">
              <?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="slug" value="<?=e($slug)?>">
              <label class="muted"><input type="checkbox" name="backup_before_delete" value="1" checked> Yedekle</label>
              <button class="btn danger">Sil</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="card">
  <h2>Package Standardı</h2>
  <pre class="codebox">packages/ornek-paket/
  package.json  (zorunlu: name, slug, version, author, permissions)
  package.php    (veya package.json main alanında belirtilen dosya)
  install.php    (önerilir)
  update.php     (önerilir)
  uninstall.php  (önerilir)
  assets/
  src/
  languages/</pre>
  <p class="muted">Resmi geliştirme ve yönetim artık yalnızca <code>packages/</code> içindeki Paketler sistemiyle yapılır.</p>
</section>
<?php require_once __DIR__.'/_footer.php'; ?>
