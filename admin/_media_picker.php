<?php
$omMediaPickerItems = $omMediaPickerItems ?? [];
if(!$omMediaPickerItems){
    try{
        $omMediaPickerItems=db()->query("SELECT id,file_path,file_name,alt_text,mime_type,width,height,created_at FROM ".table_name('media')." ORDER BY created_at DESC LIMIT 80")->fetchAll();
    }catch(Throwable $e){ $omMediaPickerItems=[]; }
}
?>
<div id="omMediaModal" class="om-media-modal" aria-hidden="true" data-endpoint="media-picker-api.php">
  <div class="om-media-panel" role="dialog" aria-modal="true" aria-label="Medya seç">
    <div class="om-media-head"><strong>Medya Kütüphanesi</strong><button type="button" class="om-media-close" aria-label="Kapat">×</button></div>
    <div class="om-media-actions">
      <input id="omMediaSearch" placeholder="Medya ara...">
      <select id="omMediaType"><option value="">Tüm türler</option><option value="image">Görseller</option><option value="video">Videolar</option><option value="file">Dosyalar</option></select>
      <input id="omImageUrl" placeholder="URL veya yol: uploads/ornek.webp">
      <button type="button" class="btn primary" id="omInsertUrlImage">Ekle / Seç</button>
      <a class="btn light" href="media.php" target="_blank" rel="noopener">Yeni dosya yükle</a>
    </div>
    <div class="om-media-grid" id="omMediaGrid">
      <?php if(empty($omMediaPickerItems)): ?><div class="om-media-empty">Henüz medya yok. Yukarıdaki URL alanını kullanabilir veya Medya sayfasından dosya yükleyebilirsin.</div><?php endif; ?>
      <?php foreach($omMediaPickerItems as $m): ?><button type="button" class="om-media-item" data-id="<?=e($m['id'] ?? '')?>" data-src="<?=e($m['file_path'])?>" data-thumb="<?=e(image_url($m['file_path']))?>" data-alt="<?=e(($m['alt_text'] ?? '') ?: ($m['file_name'] ?? ''))?>"><img src="<?=e(image_url($m['file_path']))?>" alt="<?=e(($m['alt_text'] ?? '') ?: ($m['file_name'] ?? ''))?>"><span><?=e($m['file_name'] ?? basename($m['file_path']))?></span></button><?php endforeach; ?>
    </div>
    <div class="om-media-foot"><button type="button" class="btn light" id="omMediaLoadMore" data-page="1">Daha fazla yükle</button></div>
  </div>
</div>
