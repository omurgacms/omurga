<?php
require_once __DIR__.'/_layout.php';
require_cap('plugins.manage');
verify_csrf();

$notice=''; $error=''; $uploadReport=null; $uploadStage=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    $slug=omurga_package_slug($_POST['slug'] ?? '');
    try{
        if($action==='analyze_package_upload'){
            $stage=omurga_stage_extension_zip($_FILES['package_zip'] ?? [], 'package');
            $uploadReport=omurga_analyze_package_zip($stage['path']);
            $uploadStage=$stage;
            $notice='Paket zip yüklendi ve analiz edildi. Kurulum için aşağıdaki raporu onayla.';
        }
        if($action==='confirm_package_upload'){
            $token=(string)($_POST['stage_token'] ?? '');
            $zipPath=omurga_staged_extension_zip_path($token,'package');
            $result=omurga_install_package_zip($zipPath, [
                'activate_after_upload'=>!empty($_POST['activate_after_upload']),
                'version_policy'=>(string)($_POST['version_policy'] ?? 'auto'),
                'permissions_accepted'=>!empty($_POST['permissions_accepted']),
            ]);
            omurga_clear_staged_extension_zip($token,'package');
            $notice='Paket '.$result['install_action'].': '.$result['name'].' ('.(($result['installed_version'] ?? '') ?: 'yok').' → '.$result['uploaded_version'].')'.(!empty($result['active_after_upload']) ? ' — etkinleştirildi.' : ' — pasif bırakıldı.').(!empty($result['backup']) ? ' — önceki sürüm yedeklendi.' : '');
        }
        if($action==='upload_package'){
            $stage=omurga_stage_extension_zip($_FILES['package_zip'] ?? [], 'package');
            $result=omurga_install_package_zip($stage['path'], [
                'activate_after_upload'=>!empty($_POST['activate_after_upload']),
                'version_policy'=>(string)($_POST['version_policy'] ?? 'auto'),
                'permissions_accepted'=>!empty($_POST['permissions_accepted']),
            ]);
            omurga_clear_staged_extension_zip($stage['token'],'package');
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
  <div class="upload-wizard">
    <div class="upload-steps"><span class="active">1 ZIP seç</span><span>2 Analiz</span><span>3 Onayla</span></div>
    <form method="post" enctype="multipart/form-data" class="upload-form omurga-loading-form" data-loading-title="Paket yükleniyor..." data-loading-text="ZIP dosyası sunucuya aktarılıyor, manifest ve izinler analiz ediliyor.">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="analyze_package_upload">
      <label class="upload-drop"><strong>Paket ZIP dosyası seç</strong><span>package.json içeren .zip paketini yükle. Büyük dosyalarda işlem birkaç saniye sürebilir.</span><input type="file" name="package_zip" accept=".zip,application/zip" required></label>
      <label><input type="checkbox" name="activate_after_upload" value="1"> Kurulumdan sonra etkinleştir</label>
      <button class="btn primary">Yükle ve Kontrol Et</button>
    </form>
    <p class="muted">Sunucu limiti: upload_max_filesize <?=e((string)ini_get('upload_max_filesize'))?>, post_max_size <?=e((string)ini_get('post_max_size'))?>.</p>
  </div>
  <?php if($uploadReport && ($uploadReport['type'] ?? '')==='package' && $uploadStage): ?>
    <div class="install-report">
      <h3><?=e($uploadReport['action_title'])?></h3>
      <p><?=e($uploadReport['action_description'])?></p>
      <div class="omg-summary-strip"><span><b><?=e($uploadReport['name'])?></b> Paket</span><span><b><?=e($uploadReport['slug'])?></b> Slug</span><span><b><?=e($uploadReport['installed_version'] ?: 'yok')?></b> Kurulu</span><span><b><?=e($uploadReport['uploaded_version'])?></b> Yüklenen</span><span><b><?=e($uploadReport['size'])?></b> Boyut</span></div>
      <details class="object-details" open>
        <summary>ZIP güvenlik kontrolü</summary>
        <div class="omg-summary-strip">
          <span><b><?=e((string)($uploadReport['checked_files'] ?? ($uploadReport['files'] ?? 0)))?></b> kontrol edilen dosya</span>
          <span><b><?=e((string)count($uploadReport['ignored_system_files'] ?? []))?></b> yok sayılan sistem dosyası</span>
          <span><b><?=e((string)count($uploadReport['allowed_admin_files'] ?? []))?></b> izinli admin dosyası</span>
          <span><b><?=e((string)count($uploadReport['blocked_files'] ?? []))?></b> engellenen dosya</span>
          <span><b><?=!empty($uploadReport['install_allowed'])?'Evet':'Hayır'?></b> kuruluma izin</span>
        </div>
        <?php if(!empty($uploadReport['ignored_system_files'])): ?><div class="alert pending"><b>Yok sayılan sistem dosyaları:</b><br><?=nl2br(e(implode("\n", array_slice($uploadReport['ignored_system_files'],0,12))))?></div><?php endif; ?>
        <?php if(!empty($uploadReport['allowed_admin_files'])): ?><div class="alert success"><b>Paket içi admin dosyaları izinli:</b><br><?=nl2br(e(implode("\n", array_map(fn($b)=>($b['path'] ?? '').' → '.($b['target'] ?? ''), array_slice($uploadReport['allowed_admin_files'],0,12)))))?></div><?php endif; ?>
        <?php if(!empty($uploadReport['blocked_files'])): ?><div class="alert error"><b>Engellenen dosyalar:</b><br><?=nl2br(e(implode("\n", array_map(fn($b)=>($b['path'] ?? '').(!empty($b['reason'])?' - '.$b['reason']:''), $uploadReport['blocked_files']))))?></div><?php endif; ?>
      </details>
      <?php if(!empty($uploadReport['errors'])): ?><div class="alert error"><b>Kurulumu engelleyen hata:</b> <?=e(implode(' | ', $uploadReport['errors']))?></div><?php endif; ?>
      <?php if(!empty($uploadReport['requirement_messages'])): ?><div class="alert error"><b>Uyumluluk:</b> <?=e(implode(' | ', $uploadReport['requirement_messages']))?></div><?php endif; ?>
      <?php if(!empty($uploadReport['warnings'])): ?><div class="alert pending"><b>Uyarılar:</b> <?=e(implode(' | ', $uploadReport['warnings']))?></div><?php endif; ?>
      <?php if(!empty($uploadReport['permissions'])): ?><div class="alert pending"><b>İstenen izinler:</b><pre class="codebox"><?=e(omurga_permissions_html_summary($uploadReport['permissions']))?></pre></div><?php else: ?><div class="alert success">Paket özel izin istemiyor.</div><?php endif; ?>
      <?php if(!empty($uploadReport['security_issues'])): ?><div class="alert pending"><b>Güvenlik notları:</b><br><?=nl2br(e(implode("\n", array_slice($uploadReport['security_issues'],0,8))))?></div><?php else: ?><div class="alert success">Güvenlik taramasında kritik risk bulunmadı.</div><?php endif; ?>
      <?php if(empty($uploadReport['errors']) && empty($uploadReport['requirement_messages'])): ?>
      <form method="post" class="inline-form omurga-loading-form" data-loading-title="Paket kuruluyor..." data-loading-text="Mevcut sürüm yedekleniyor ve yeni paket dosyaları güvenli şekilde yazılıyor.">
        <?=csrf_field()?><input type="hidden" name="action" value="confirm_package_upload"><input type="hidden" name="stage_token" value="<?=e($uploadStage['token'])?>"><input type="hidden" name="version_policy" value="<?=e($uploadReport['recommended_policy'])?>">
        <label><input type="checkbox" name="activate_after_upload" value="1"> Kurulumdan sonra etkinleştir</label>
        <label><input type="checkbox" name="permissions_accepted" value="1" required> Paket izinlerini okudum ve kabul ediyorum</label>
        <button class="btn primary"><?=e($uploadReport['action_key']==='update' ? $uploadReport['uploaded_version'].' sürümüne güncelle' : ($uploadReport['action_key']==='overwrite' ? 'Aynı sürüm üzerine yaz' : ($uploadReport['action_key']==='downgrade' ? 'Eski sürüme dön' : 'Paketi kur')))?></button>
        <a class="btn light" href="packages.php">İptal</a>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="omurga-loading-overlay" hidden><div><strong>Yükleniyor...</strong><p>Lütfen bekleyin.</p></div></div>
  <script>document.addEventListener('submit',function(e){var f=e.target;if(!f.classList||!f.classList.contains('omurga-loading-form'))return;var o=document.querySelector('.omurga-loading-overlay');if(o){o.hidden=false;o.querySelector('strong').textContent=f.dataset.loadingTitle||'Yükleniyor...';o.querySelector('p').textContent=f.dataset.loadingText||'İşlem devam ediyor, lütfen bekleyin.';}f.querySelectorAll('button').forEach(function(b){b.disabled=true;});});</script>
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
