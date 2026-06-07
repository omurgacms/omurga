<?php
require_once __DIR__.'/_layout.php';
require_cap('themes.manage');
omurga_migrate();
$msg=''; $err='';
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
        if($action==='upload'){
            if(empty($_FILES['theme_zip']['name']) || ($_FILES['theme_zip']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Tema zip dosyası seçilmedi.');
            if(strtolower(pathinfo($_FILES['theme_zip']['name'],PATHINFO_EXTENSION))!=='zip') throw new RuntimeException('Sadece .zip tema paketi yüklenebilir.');
            $info=omurga_install_theme_zip($_FILES['theme_zip']['tmp_name'], [
                'version_policy'=>(string)($_POST['version_policy'] ?? 'auto'),
            ]);
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

        if($action==='import_active_theme_demo'){
            $activeSlug=omurga_active_theme();
            $demoFns=[
                'haber-v1'=>'hv1_demo_import',
                'kurumsal-v1'=>'kv1_demo_import',
                'topluluk-v1'=>'tv1_demo_import',
            ];
            $fn=$demoFns[$activeSlug] ?? '';
            if($fn==='' || !function_exists($fn)) throw new RuntimeException('Aktif tema için demo yükleyici bulunamadı.');
            $result=call_user_func($fn, false);
            update_setting('theme_demo_imported_'.$activeSlug, date('c'));
            update_setting('theme_demo_import_result_'.$activeSlug, json_encode($result, JSON_UNESCAPED_UNICODE));
            log_activity('theme.demo_import','Aktif tema demosu manuel yüklendi: '.$activeSlug);
            $msg=(string)($result['message'] ?? 'Aktif tema demosu yüklendi.');
            if(isset($result['created'])) $msg.=' Oluşturulan yeni içerik: '.(int)$result['created'].'.';
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
        return (bool)preg_match('/(?:omurga_render_region|om_region)\(\s*[\'"]'.$q.'[\'"]|\{region\s+name=[\'"]'.$q.'[\'"]\}/i', $text);
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

<?php
$activeDemoFns=['haber-v1'=>'hv1_demo_import','kurumsal-v1'=>'kv1_demo_import','topluluk-v1'=>'tv1_demo_import'];
$activeHasDemo=!empty($activeDemoFns[$active] ?? '') && function_exists($activeDemoFns[$active]);
if($activeInfo && $activeHasDemo):
  $lastDemoImport=(string)setting('theme_demo_imported_'.$active,'');
?>
<section class="card">
  <div class="toolbar">
    <div>
      <h2>Aktif Tema Demo İçeriği</h2>
      <p class="muted">Demo içerik yalnızca ilk kurulumda otomatik yüklenir. Güncellemelerde mevcut içerik, menü ve tema ayarları korunur. İsterseniz aktif temanın demosunu buradan manuel olarak tekrar çalıştırabilirsiniz.</p>
    </div>
    <?php if($lastDemoImport): ?><span class="badge">Son demo: <?=e($lastDemoImport)?></span><?php endif; ?>
  </div>
  <form method="post" onsubmit="return confirm('Aktif tema demosu yüklenecek. Demo içe aktarma güvenli ve tekrar çalıştırılabilir şekilde tasarlanmıştır; yine de mevcut sitenizde yeni örnek içerikler oluşabilir. Devam edilsin mi?');">
    <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="action" value="import_active_theme_demo">
    <button class="btn primary">Demo İçeriği Yükle</button>
  </form>
</section>
<?php endif; ?>
<section class="card">
  <div class="toolbar"><div><h2>Yüklü Temalar</h2><p class="muted">Tema sitenin ön yüzünü yönetir. Admin panel tasarımı ve Omurga logosu temadan etkilenmez.</p></div><a class="btn light" href="../" target="_blank">Siteyi Gör</a></div>
  <div class="theme-grid">
    <?php foreach($themes as $theme): ?>
      <article class="theme-card <?=($theme['slug']===$active?'active':'')?>">
        <div class="theme-shot">
          <?php if(!empty($theme['screenshot'])): ?><img src="<?=e($theme['screenshot'])?>" alt="<?=e($theme['name'])?>"><?php else: ?><div class="theme-empty">Omurga</div><?php endif; ?>
        </div>
        <div class="theme-body">
          <?php $isSystem=omurga_theme_is_system($theme); $stats=omurga_theme_dir_stats($theme['slug']); $deleteReason=null; $canDelete=omurga_theme_can_delete($theme['slug'],$deleteReason); ?>
          <div class="theme-title"><h3><?=e($theme['name'])?></h3><?php if($theme['slug']===$active): ?><span>Şu Anda Kullanılıyor</span><?php endif; ?></div>
          <div class="theme-badges">
            <?php if($isSystem): ?><span class="badge published">Sistem Teması</span><?php endif; ?>
            <span class="badge"><?=e(strtoupper($theme['template_engine'] ?? 'php'))?></span>
          </div>
          <p><?=e($theme['description'] ?: 'Omurga teması')?></p>
          <small>Sürüm: <?=e($theme['version'])?> · Klasör: <?=e($theme['slug'])?> · <?=e((string)$stats['files'])?> dosya · <?=e($stats['size'])?></small>
          <?php if(empty($theme['valid'])): ?><div class="alert error">Eksik dosyalar: <?=e(implode(', ', $theme['missing'] ?? []))?></div><?php endif; ?>
          <?php if(!empty($theme['warnings'])): ?><div class="alert pending"><?=e(implode(' ', $theme['warnings']))?></div><?php endif; ?>
          <?php if(!empty($theme['permissions'])): ?><div class="alert error">Tema izinleri: <?=e(omurga_format_permissions($theme['permissions']))?>. Temalarda kullanıcı/rol/veritabanı/sistem izinleri yasaktır.</div><?php endif; ?>
          <div class="theme-actions">
            <a class="btn light" href="../?theme_preview=<?=e($theme['slug'])?>" target="_blank">Önizle</a>
            <a class="btn light" href="theme-editor.php?theme=<?=e($theme['slug'])?>">Detaylar</a>
            <?php if($theme['slug']!==$active && !empty($theme['valid'])): ?>
              <form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="theme_slug" value="<?=e($theme['slug'])?>"><button class="btn primary">Etkinleştir</button></form>
            <?php endif; ?>
            <?php if($canDelete): ?>
              <form method="post" class="theme-delete-form" onsubmit="return confirm('Temayı Sil\n\nBu tema kalıcı olarak silinecek. Bu işlem geri alınamaz.\n\nTema: <?=e(addslashes($theme['name']))?>\nDosya: <?=e((string)$stats['files'])?>\nBoyut: <?=e($stats['size'])?>');">
                <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="theme_slug" value="<?=e($theme['slug'])?>">
                <label class="muted" style="display:block;margin:6px 0"><input type="checkbox" name="backup_before_delete" value="1" checked> Silmeden önce yedek oluştur</label>
                <button class="btn danger">Sil</button>
              </form>
            <?php else: ?>
              <span class="muted" title="<?=e($deleteReason ?? '')?>">Silinemez: <?=e($deleteReason ?? 'Güvenlik koruması')?></span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<section class="card">
  <h2>Yeni Tema Yükle / Güncelle</h2>
  <p class="muted">Aynı slug varsa sürüme göre otomatik güncelleme, yeniden kurma veya sürüm düşürme yapılır; eski tema yedeklenir. Tema zip içinde <code>theme.json</code> olmalı. Tema görünüm işidir; kullanıcı, rol, veritabanı, SQL, sistem ve çekirdek izinleri tema tarafında engellenir.</p>
  <div class="alert pending"><b>Resmi tema standardı:</b> <code>theme.json</code> zorunlu; <code>functions.php</code>, <code>screenshot.jpg</code>, <code>preview.jpg</code>, <code>assets/</code>, <code>views/</code>, <code>blocks/</code>, <code>demos/</code>, <code>languages/</code> önerilen standart yapıdır.</div>
  <form method="post" enctype="multipart/form-data" class="inline-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="upload"><input type="file" name="theme_zip" accept=".zip" required><label><input type="checkbox" name="activate_after_upload" value="1"> Yükledikten sonra etkinleştir</label><select name="version_policy"><option value="auto">Aynı tema varsa: yüksekse güncelle, aynıysa yenile, düşükse sürüm düşür</option><option value="only_newer">Sadece daha yüksek sürümse güncelle</option><option value="only_same">Sadece aynı sürümü yeniden kur</option><option value="only_lower">Sadece düşük sürüme düşür</option></select><button class="btn primary">Tema Zip Yükle</button></form>
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
