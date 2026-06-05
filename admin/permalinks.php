<?php
require '_layout.php';
verify_csrf();
require_cap('settings.manage');
$msg=''; $err='';
$currentBase = content_url_base();
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $base = trim((string)($_POST['content_base'] ?? 'yazi'));
        omurga_set_content_base($base);
        $currentBase = content_url_base();
        log_activity('settings.permalinks','Kalıcı bağlantı yapısı güncellendi: /'.$currentBase.'/{slug}');
        $msg='Kalıcı bağlantılar kaydedildi.';
    }catch(Throwable $e){ $err=$e->getMessage(); }
}
$options = omurga_permalink_bases();
?>
<div class="toolbar"><h1>Kalıcı Bağlantılar</h1></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>
<form method="post" class="grid-2">
  <?=csrf_field()?>
  <div class="card">
    <h2>Yazı URL Yapısı</h2>
  <p class="muted">Sayfalar kökten çalışır: <code>/hakkimizda</code>. Yazılar ise seçtiğin tabanla çalışır.</p>
    <?php foreach($options as $base=>$label): ?>
      <label class="check-line"><input type="radio" name="content_base" value="<?=e($base)?>" <?=$currentBase===$base?'checked':''?>> <?=e($label)?></label>
    <?php endforeach; ?>
    <label>Özel Yazı Tabanı
      <input name="content_base" value="<?=e($currentBase)?>" placeholder="yazi">
      <small>Sadece harf, rakam ve tire kullan. Rezerve yollar otomatik reddedilir.</small>
    </label>
    <button class="btn primary">Kaydet</button>
  </div>
  <div class="card">
    <h2>Örnek Bağlantılar</h2>
    <ul>
      <li>Yazı örneği: <code><?=e(omurga_permalink_example($currentBase))?></code></li>
      <li>Sayfa örneği: <code><?=e(omurga_url('hakkimizda'))?></code></li>
      <li>Kategori örneği: <code><?=e(omurga_url('kategori/gundem'))?></code></li>
      <li>Etiket örneği: <code><?=e(omurga_url('etiket/omurga'))?></code></li>
    </ul>
    <p class="muted">Not: Sayfalar kategori/etiket/yazı akışına dahil değildir; sadece doğrudan link ve menü üzerinden kullanılır.</p>
  </div>
</form>
<?php require '_footer.php'; ?>
