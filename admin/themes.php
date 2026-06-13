<?php
require_once __DIR__.'/_layout.php';
require_cap('themes.manage');
omurga_migrate();
$msg=''; $err=''; $uploadReport=null; $uploadStage=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $action=$_POST['action'] ?? '';
        if($action==='activate'){
            $slug=preg_replace('/[^a-z0-9_-]/','',strtolower($_POST['theme_slug'] ?? ''));
            $info=omurga_theme_info($slug);
            if(!$info) throw new RuntimeException('Tema bulunamadı.');
            if(empty($info['valid'])) throw new RuntimeException('Tema eksik dosyalar içeriyor: '.implode(', ', $info['missing'] ?? []));
            update_setting('active_theme',$slug);
            log_activity('theme.activate','Tema etkinleştirildi: '.$slug);
            $msg='Tema etkinleştirildi: '.$info['name'];
        }
        if($action==='analyze_upload'){
            $stage=omurga_stage_extension_zip($_FILES['theme_zip'] ?? [], 'theme');
            $uploadReport=omurga_analyze_theme_zip($stage['path']);
            $uploadStage=$stage;
            $msg='Tema zip yüklendi ve analiz edildi. Kurulum için aşağıdaki raporu onayla.';
        }
        if($action==='confirm_upload'){
            $token=(string)($_POST['stage_token'] ?? '');
            $zipPath=omurga_staged_extension_zip_path($token,'theme');
            $info=omurga_install_theme_zip($zipPath, [
                'version_policy'=>(string)($_POST['version_policy'] ?? 'auto'),
            ]);
            omurga_clear_staged_extension_zip($token,'theme');
            log_activity('theme.upload','Tema '.($info['install_action'] ?? 'yüklendi').': '.($info['slug'] ?? ''));
            $msg='Tema '.($info['install_action'] ?? 'yüklendi').': '.($info['name'] ?? $info['slug']).' ('.(($info['installed_version'] ?? '') ?: 'yok').' → '.($info['uploaded_version'] ?? $info['version'] ?? '').')'.(!empty($info['backup']) ? ' — önceki sürüm yedeklendi.' : '');
            if(!empty($_POST['activate_after_upload'])){
                if(empty($info['valid'])) throw new RuntimeException('Tema yüklendi ancak eksik dosyalar nedeniyle etkinleştirilemedi.');
                update_setting('active_theme', $info['slug']);
                log_activity('theme.activate','Tema yükleme sonrası etkinleştirildi: '.$info['slug']);
                $msg .= ' — etkinleştirildi.';
            } else {
                $msg .= ' — pasif bırakıldı.';
            }
        }
        if($action==='upload'){
            $stage=omurga_stage_extension_zip($_FILES['theme_zip'] ?? [], 'theme');
            $info=omurga_install_theme_zip($stage['path'], ['version_policy'=>(string)($_POST['version_policy'] ?? 'auto')]);
            omurga_clear_staged_extension_zip($stage['token'],'theme');
            log_activity('theme.upload','Tema '.($info['install_action'] ?? 'yüklendi').': '.($info['slug'] ?? ''));
            $msg='Tema '.($info['install_action'] ?? 'yüklendi').': '.($info['name'] ?? $info['slug']).' ('.(($info['installed_version'] ?? '') ?: 'yok').' → '.($info['uploaded_version'] ?? $info['version'] ?? '').')'.(!empty($info['backup']) ? ' — önceki sürüm yedeklendi.' : '');
        }
        if($action==='delete'){
            $slug=preg_replace('/[^a-z0-9_-]/','',strtolower($_POST['theme_slug'] ?? ''));
            $createBackup=!empty($_POST['backup_before_delete']);
            $result=omurga_delete_theme($slug, $createBackup);
            $msg='Tema silindi: '.$result['slug'].(!empty($result['backup']) ? ' — yedek alındı.' : '');
        }
    }catch(Throwable $e){ omurga_write_error($e); $err=$e->getMessage(); }
}
$themes=omurga_list_themes();
$active=omurga_active_theme();
$activeInfo=omurga_theme_info($active);
$configuredTheme=preg_replace('/[^a-z0-9_-]/','',strtolower((string)setting('active_theme','')));
$configuredInfo=$configuredTheme ? omurga_theme_info($configuredTheme) : null;

if(!function_exists('omg_theme_support_scan')){
function omg_theme_support_scan(string $slug): array {
    $dir=omurga_theme_dir($slug);
    $meta=omurga_theme_meta($slug);
    $text='';
    $files=[];
    if(is_dir($dir)){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){
            if(!$file->isFile()) continue;
            $ext=strtolower($file->getExtension());
            if(!in_array($ext, ['php','omg','json','css'], true)) continue;
            $rel=str_replace('\\','/',substr($file->getPathname(), strlen($dir)+1));
            $files[$rel]=(string)@file_get_contents($file->getPathname());
            $text.="\n".$files[$rel];
        }
    }
    $hasRegion=function(string $name) use($text): bool {
        $q=preg_quote($name,'/');
        return (bool)preg_match('/(?:omurga_render_region|om_region|omh_render_region_safe)\(\s*[\'"]'.$q.'[\'"]|\{region\s+name=[\'"]'.$q.'[\'"]\}|(?:region|builder)\(\s*[\'"]'.$q.'[\'"]\s*\)/i', $text);
    };
    $hasAny=function(array $patterns) use($text): bool {
        foreach($patterns as $p){ if(preg_match($p, $text)) return true; }
        return false;
    };
    $themeMenuLocations=!empty($meta['menu_locations']) && is_array($meta['menu_locations']);
    $usesCoreMenus=$hasAny(['/menu_items\s*\(/i','/omurga_menu\s*\(/i','/\{menu\s+name=/i']);
    $pageFiles=array_filter(array_keys($files), fn($f)=>$f==='page.php' || preg_match('#(^|/)page[^/]*\.php$#i',$f));
    $singleFiles=array_filter(array_keys($files), fn($f)=>$f==='single.php' || preg_match('#(^|/)single[^/]*\.php$#i',$f));
    $safeFiles=function(array $list) use($files): bool {
        if(!$list) return false;
        foreach($list as $f){
            $c=$files[$f] ?? '';
            if(strpos($c, '!isset($post)')===false && strpos($c, 'empty($post)')===false && strpos($c, 'page.not_found')===false) return false;
        }
        return true;
    };
    return [
        ['label'=>'Header düzen alanı', 'ok'=>$hasRegion('header'), 'note'=>$hasRegion('header')?'header region çağrısı var':'header region çağrısı bulunamadı'],
        ['label'=>'Footer düzen alanı', 'ok'=>$hasRegion('footer'), 'note'=>$hasRegion('footer')?'footer region çağrısı var':'footer region çağrısı bulunamadı'],
        ['label'=>'Ana sayfa blok alanı', 'ok'=>$hasRegion('home') || $hasRegion('home_main'), 'note'=>($hasRegion('home') || $hasRegion('home_main'))?'home/home_main region çağrısı var':'ana sayfa region çağrısı bulunamadı'],
        ['label'=>'Sidebar alanı', 'ok'=>$hasRegion('sidebar') || $hasAny(['/omurga_render_region\(\s*\$sidebarRegion/i']), 'note'=>($hasRegion('sidebar') || $hasAny(['/omurga_render_region\(\s*\$sidebarRegion/i']))?'sidebar region çağrısı var':'sidebar region çağrısı bulunamadı'],
        ['label'=>'Menü konumları', 'ok'=>$themeMenuLocations, 'note'=>$themeMenuLocations?'theme.json içinde menu_locations var':($usesCoreMenus?'theme.json menu_locations yok; tema çekirdek menü çağrılarını kullanıyor':'menü çağrısı veya konum tanımı bulunamadı')],
        ['label'=>'Dil dosyası', 'ok'=>is_file($dir.'/lang/tr.php') || is_file($dir.'/lang/en.php'), 'note'=>(is_file($dir.'/lang/tr.php') || is_file($dir.'/lang/en.php'))?'tema dil dosyası var':'lang/tr.php veya lang/en.php yok'],
        ['label'=>'Tema ayarları', 'ok'=>!empty($meta['settings']) && is_array($meta['settings']), 'note'=>(!empty($meta['settings']) && is_array($meta['settings']))?'theme.json settings alanı var':'theme.json settings alanı yok'],
        ['label'=>'Yorum desteği', 'ok'=>$hasAny(['/om_comments_list\s*\(/i','/om_comment_form\s*\(/i','/comments/i','/comment_form/i']), 'note'=>$hasAny(['/om_comments_list\s*\(/i','/om_comment_form\s*\(/i','/comments/i','/comment_form/i'])?'yorum fonksiyonu veya OMG alanı var':'yorum fonksiyonu/OMG alanı bulunamadı'],
        ['label'=>'Sayfa şablonu güvenliği', 'ok'=>$safeFiles($pageFiles), 'note'=>$safeFiles($pageFiles)?'page şablonları boş veri için korumalı':'page şablonlarında boş veri koruması eksik olabilir'],
        ['label'=>'Yazı şablonu güvenliği', 'ok'=>$safeFiles($singleFiles), 'note'=>$safeFiles($singleFiles)?'single şablonları boş veri için korumalı':'single şablonlarında boş veri koruması eksik olabilir'],
    ];
}
}
?>
<h1>Temalar</h1>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>
<?php if($configuredTheme && $configuredTheme!==$active): ?><div class="alert pending">Kayıtlı aktif tema <b><?=e($configuredTheme)?></b> Omurga CMS tema standardıyla uyumlu değil veya eksik. Site geçici olarak <b><?=e($active)?></b> temasıyla çalışıyor.</div><?php endif; ?>
<section class="card">
  <div class="toolbar"><div><h2>Tema Güvenliği</h2><p class="muted">Aktif tema ve sistem temaları silinemez. Sistemde güvenlik için her zaman en az 2 tema kalır.</p></div><span class="badge published"><?=count($themes)?> tema</span></div>
  <p class="muted">Bozuk veya eksik aktif tema algılanırsa Omurga otomatik olarak güvenli sistem temasına döner ve admin panel beyaz ekrana düşmez.</p>
</section>
<?php if($activeInfo): $supportRows=omg_theme_support_scan($active); ?>
<section class="card">
  <div class="toolbar"><div><h2>Aktif Tema Omurga Destekleri</h2><p class="muted"><b><?=e($activeInfo['name'] ?? $active)?></b> temasının düzen, dil, ayar, yorum ve şablon uyumluluğu.</p></div><a class="btn light" href="theme-editor.php?theme=<?=e($active)?>">Tema Düzenleyici</a></div>
  <table class="table content-table"><thead><tr><th>Kontrol</th><th>Durum</th><th>Not</th></tr></thead><tbody>
    <?php foreach($supportRows as $row): ?><tr><td data-label="Kontrol"><strong><?=e($row['label'])?></strong></td><td data-label="Durum"><span class="badge <?=$row['ok']?'published':'pending'?>"><?=$row['ok']?'Destekli':'Kontrol gerekli'?></span></td><td data-label="Not"><?=e($row['note'])?></td></tr><?php endforeach; ?>
  </tbody></table>
</section>
<?php endif; ?>
<section class="card">
  <div class="toolbar"><div><h2>Yüklü Temalar</h2><p class="muted">Tema sitenin ön yüzünü yönetir. Admin panel tasarımı ve Omurga logosu temadan etkilenmez.</p></div><a class="btn light" href="../" target="_blank">Siteyi Gör</a></div>
  <?php
    $themeSummary=['total'=>count($themes),'active'=>0,'system'=>0,'warning'=>0];
    foreach($themes as $t){
      if(($t['slug'] ?? '')===$active) $themeSummary['active']++;
      if(omurga_theme_is_system($t)) $themeSummary['system']++;
      if(empty($t['valid']) || !empty($t['warnings']) || !empty($t['permissions'])) $themeSummary['warning']++;
    }
  ?>
  <div class="omg-summary-strip">
    <span><b><?=e((string)$themeSummary['total'])?></b> Toplam tema</span>
    <span><b><?=e((string)$themeSummary['active'])?></b> Aktif</span>
    <span><b><?=e((string)$themeSummary['system'])?></b> Sistem teması</span>
    <span><b><?=e((string)$themeSummary['warning'])?></b> Uyarı/kontrol</span>
  </div>
  <div class="omg-view-toggle" aria-label="Görünüm seçimi"><span>Görünüm</span><button type="button" class="active" data-omg-view="list">Liste</button><button type="button" data-omg-view="grid">Kart</button></div>
  <div class="theme-list compact-object-list" data-omg-list-view="list">
    <?php foreach($themes as $theme): ?>
      <?php $isSystem=omurga_theme_is_system($theme); $stats=omurga_theme_dir_stats($theme['slug']); $deleteReason=null; $canDelete=omurga_theme_can_delete($theme['slug'],$deleteReason); $warningCount=(empty($theme['valid'])?1:0)+(!empty($theme['warnings'])?count($theme['warnings']):0)+(!empty($theme['permissions'])?1:0); ?>
      <article class="object-row theme-row <?=($theme['slug']===$active?'active':'')?>">
        <div class="object-shot">
          <?php if(!empty($theme['screenshot'])): ?><img src="<?=e($theme['screenshot'])?>" alt="<?=e($theme['name'])?>"><?php else: ?><span>Omurga</span><?php endif; ?>
        </div>
        <div class="object-main">
          <div class="object-head">
            <div>
              <h3><?=e($theme['name'])?></h3>
              <p class="object-meta"><?=e(strtoupper($theme['template_engine'] ?? 'php'))?> · v<?=e($theme['version'])?> · <?=e($theme['slug'])?> · <?=e((string)$stats['files'])?> dosya · <?=e($stats['size'])?></p>
            </div>
            <div class="object-badges">
              <?php if($theme['slug']===$active): ?><span class="badge pending">Kullanılıyor</span><?php endif; ?>
              <?php if($isSystem): ?><span class="badge published">Sistem</span><?php endif; ?>
              <?php if($warningCount>0): ?><span class="badge pending"><?=e((string)$warningCount)?> uyarı</span><?php else: ?><span class="badge published">Uygun</span><?php endif; ?>
            </div>
          </div>
          <p class="object-desc"><?=e($theme['description'] ?: 'Omurga teması')?></p>
          <?php if($warningCount>0 || !$canDelete): ?>
            <details class="object-details">
              <summary>Teknik detaylar ve uyarılar</summary>
              <?php if(empty($theme['valid'])): ?><div class="alert error">Eksik dosyalar: <?=e(implode(', ', $theme['missing'] ?? []))?></div><?php endif; ?>
              <?php if(!empty($theme['warnings'])): ?><div class="alert pending"><?=e(implode(' ', $theme['warnings']))?></div><?php endif; ?>
              <?php if(!empty($theme['permissions'])): ?><div class="alert error">Tema izinleri: <?=e(omurga_format_permissions($theme['permissions']))?>. Temalarda kullanıcı/rol/veritabanı/sistem izinleri yasaktır.</div><?php endif; ?>
              <?php if(!$canDelete): ?><p class="muted">Silinemez: <?=e($deleteReason ?? 'Güvenlik koruması')?></p><?php endif; ?>
            </details>
          <?php endif; ?>
        </div>
        <div class="object-actions">
          <a class="btn light" href="../?theme_preview=<?=e($theme['slug'])?>" target="_blank">Önizle</a>
          <a class="btn light" href="theme-editor.php?theme=<?=e($theme['slug'])?>">Detaylar</a>
          <?php if($theme['slug']!==$active && !empty($theme['valid'])): ?>
            <form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="theme_slug" value="<?=e($theme['slug'])?>"><button class="btn primary">Etkinleştir</button></form>
          <?php endif; ?>
          <?php if($canDelete): ?>
            <form method="post" class="object-delete" onsubmit="return confirm('Temayı Sil\n\nBu tema kalıcı olarak silinecek. Bu işlem geri alınamaz.\n\nTema: <?=e(addslashes($theme['name']))?>\nDosya: <?=e((string)$stats['files'])?>\nBoyut: <?=e($stats['size'])?>');">
              <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="theme_slug" value="<?=e($theme['slug'])?>">
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
  <h2>Yeni Tema Yükle / Güncelle</h2>
  <p class="muted">Aynı slug varsa sürüme göre otomatik güncelleme, yeniden kurma veya sürüm düşürme yapılır; eski tema yedeklenir. Tema zip içinde <code>theme.json</code> olmalı. Tema görünüm işidir; kullanıcı, rol, veritabanı, SQL, sistem ve çekirdek izinleri tema tarafında engellenir.</p>
  <div class="alert pending"><b>Resmi tema standardı:</b> <code>theme.json</code> zorunlu; <code>functions.php</code>, <code>screenshot.jpg</code>, <code>preview.jpg</code>, <code>assets/</code>, <code>views/</code>, <code>blocks/</code>, <code>demos/</code>, <code>languages/</code> önerilen standart yapıdır.</div>
  <div class="upload-wizard">
    <div class="upload-steps"><span class="active">1 ZIP seç</span><span>2 Analiz</span><span>3 Onayla</span></div>
    <form method="post" enctype="multipart/form-data" class="upload-form omurga-loading-form" data-loading-title="Tema yükleniyor..." data-loading-text="ZIP dosyası sunucuya aktarılıyor, güvenlik ve sürüm kontrolü yapılacak.">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="analyze_upload">
      <label class="upload-drop"><strong>Tema ZIP dosyası seç</strong><span>theme.json içeren .zip paketini yükle. Büyük dosyalarda sayfadan ayrılma.</span><input type="file" name="theme_zip" accept=".zip,application/zip" required></label>
      <label><input type="checkbox" name="activate_after_upload" value="1"> Kurulumdan sonra etkinleştir</label>
      <button class="btn primary">Yükle ve Kontrol Et</button>
    </form>
    <p class="muted">Sunucu limiti: upload_max_filesize <?=e((string)ini_get('upload_max_filesize'))?>, post_max_size <?=e((string)ini_get('post_max_size'))?>.</p>
  </div>
  <?php if($uploadReport && ($uploadReport['type'] ?? '')==='theme' && $uploadStage): ?>
    <div class="install-report">
      <h3><?=e($uploadReport['action_title'])?></h3>
      <p><?=e($uploadReport['action_description'])?></p>
      <div class="omg-summary-strip"><span><b><?=e($uploadReport['name'])?></b> Tema</span><span><b><?=e($uploadReport['slug'])?></b> Slug</span><span><b><?=e($uploadReport['installed_version'] ?: 'yok')?></b> Kurulu</span><span><b><?=e($uploadReport['uploaded_version'])?></b> Yüklenen</span><span><b><?=e($uploadReport['size'])?></b> Boyut</span></div>
      <?php $zr=$uploadReport['zip_report'] ?? []; ?>
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
        <?php if(!empty($uploadReport['allowed_admin_files'])): ?><div class="alert success"><b>Tema içi admin dosyaları izinli:</b><br><?=nl2br(e(implode("\n", array_map(fn($b)=>($b['path'] ?? '').' → '.($b['target'] ?? ''), array_slice($uploadReport['allowed_admin_files'],0,12)))))?></div><?php endif; ?>
        <?php if(!empty($uploadReport['blocked_files'])): ?><div class="alert error"><b>Engellenen dosyalar:</b><br><?=nl2br(e(implode("\n", array_map(fn($b)=>($b['path'] ?? '').(!empty($b['reason'])?' - '.$b['reason']:''), $uploadReport['blocked_files']))))?></div><?php endif; ?>
      </details>
      <?php if(!empty($uploadReport['errors'])): ?><div class="alert error"><b>Kurulumu engelleyen hata:</b> <?=e(implode(' | ', $uploadReport['errors']))?></div><?php endif; ?>
      <?php if(!empty($uploadReport['warnings'])): ?><div class="alert pending"><b>Uyarılar:</b> <?=e(implode(' | ', $uploadReport['warnings']))?></div><?php endif; ?>
      <?php if(!empty($uploadReport['security_issues'])): ?><div class="alert pending"><b>Güvenlik notları:</b><br><?=nl2br(e(implode("\n", array_slice($uploadReport['security_issues'],0,8))))?></div><?php else: ?><div class="alert success">Güvenlik taramasında kritik risk bulunmadı.</div><?php endif; ?>
      <?php if(empty($uploadReport['errors'])): ?>
      <form method="post" class="inline-form omurga-loading-form" data-loading-title="Tema kuruluyor..." data-loading-text="Mevcut sürüm yedekleniyor ve yeni tema dosyaları güvenli şekilde yazılıyor.">
        <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="confirm_upload"><input type="hidden" name="stage_token" value="<?=e($uploadStage['token'])?>"><input type="hidden" name="version_policy" value="<?=e($uploadReport['recommended_policy'])?>">
        <label><input type="checkbox" name="activate_after_upload" value="1"> Kurulumdan sonra etkinleştir</label>
        <button class="btn primary"><?=e($uploadReport['action_key']==='update' ? $uploadReport['uploaded_version'].' sürümüne güncelle' : ($uploadReport['action_key']==='overwrite' ? 'Aynı sürüm üzerine yaz' : ($uploadReport['action_key']==='downgrade' ? 'Eski sürüme dön' : 'Temayı kur')))?></button>
        <a class="btn light" href="themes.php">İptal</a>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="omurga-loading-overlay" hidden><div><strong>Yükleniyor...</strong><p>Lütfen bekleyin.</p></div></div>
  <script>document.addEventListener('submit',function(e){var f=e.target;if(!f.classList||!f.classList.contains('omurga-loading-form'))return;var o=document.querySelector('.omurga-loading-overlay');if(o){o.hidden=false;o.querySelector('strong').textContent=f.dataset.loadingTitle||'Yükleniyor...';o.querySelector('p').textContent=f.dataset.loadingText||'İşlem devam ediyor, lütfen bekleyin.';}f.querySelectorAll('button').forEach(function(b){b.disabled=true;});});</script>
</section>
<section class="card">
  <h2>Kolay Tema Yapısı / PHP Tema Yapısı</h2>
  <pre class="codebox">benim-temam/
  theme.json      (zorunlu: name, slug, version, author)
  functions.php    (önerilir)
  screenshot.jpg   (önerilir)
  preview.jpg      (önerilir)
  assets/
  views/
  blocks/
  demos/
  languages/

OMG motoru için ayrıca: home.omg, single.omg, page.omg, category.omg, header.omg, footer.omg
PHP motoru için ayrıca: home.php, header.php, footer.php</pre>
</section>
<?php require __DIR__.'/_footer.php'; ?>
