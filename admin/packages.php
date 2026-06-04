<?php
require_once __DIR__.'/_layout.php';
require_cap('plugins.manage');
verify_csrf();

$notice=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    $slug=omurga_package_slug($_POST['slug'] ?? '');
    try{
        if($action==='activate' && $slug){ omurga_activate_package($slug); $notice='Paket etkinleştirildi.'; }
        if($action==='deactivate' && $slug){ omurga_deactivate_package($slug); $notice='Paket devre dışı bırakıldı.'; }
    }catch(Throwable $e){ $error=$e->getMessage(); omurga_write_error($e); }
}

$packages=omurga_all_packages();
?>
<div class="page-head"><div><h1>Paketler</h1><p>Omurga v4 package.json standardıyla çalışan resmi paketler.</p></div></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>

<section class="card">
  <h2>Yüklü Paketler</h2>
  <?php if(!$packages): ?><p class="muted">Henüz package.json içeren paket yok. <code>packages/ornek-paket/package.json</code> yapısını kullanın.</p><?php endif; ?>
  <div class="plugin-grid">
    <?php foreach($packages as $slug=>$pkg): $active=omurga_package_is_active($slug); ?>
      <article class="plugin-card <?=$active?'active':''?>">
        <div class="plugin-top"><div><h3><?=e($pkg['name'])?></h3><small>v<?=e($pkg['version'])?><?=!empty($pkg['author'])?' · '.e($pkg['author']):''?></small></div><span class="badge <?=$active?'ok':'muted'?>"><?=$active?'Aktif':'Pasif'?></span></div>
        <p><?=e($pkg['description'] ?: 'Açıklama yok.')?></p>
        <?php if(empty($pkg['compatible'])): ?><div class="alert error"><?=e(implode(' ', $pkg['requirement_messages'] ?? ['Paket gereksinimleri karşılanmıyor.']))?></div><?php endif; ?>
        <div class="plugin-meta"><span>Slug: <code><?=e($slug)?></code></span><span>Blok: <?=count($pkg['blocks'] ?? [])?></span><span>Admin: <?=count($pkg['admin_pages'] ?? [])?></span></div>
        <form method="post" class="inline-form">
          <?=csrf_field()?><input type="hidden" name="slug" value="<?=e($slug)?>">
          <?php if($active): ?><input type="hidden" name="action" value="deactivate"><button class="btn">Devre Dışı Bırak</button><?php elseif(!empty($pkg['compatible'])): ?><input type="hidden" name="action" value="activate"><button class="btn primary">Etkinleştir</button><?php else: ?><button type="button" class="btn light" disabled>Etkinleştirilemez</button><?php endif; ?>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="card">
  <h2>Package Standardı</h2>
  <pre class="codebox">packages/ornek-paket/
  package.json
  package.php
  blocks/ornek-blok/block.json
  blocks/ornek-blok/view.omg</pre>
  <p class="muted">Eski <code>plugins/</code> klasörü geriye uyumluluk için okunur; yeni resmi geliştirme <code>packages/</code> içindir.</p>
</section>
<?php require_once __DIR__.'/_footer.php'; ?>
