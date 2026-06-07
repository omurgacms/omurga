<?php require '_layout.php'; verify_csrf(); require_cap('ads.manage');
$areas=omurga_ad_locations(); $ads=omurga_ad_slots(); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $new=omurga_default_ad_slots();
    foreach($areas as $key=>$label){
        $cur=$ads[$key] ?? [];
        $img=trim((string)($_POST['ads'][$key]['image'] ?? ($cur['image'] ?? '')));
        try{ if($up=save_uploaded_file('upload_'.$key)) { $img=$up; insert_media_record($img, $_POST['ads'][$key]['title'] ?? $label, current_user()['id'] ?? null); } }catch(Throwable $e){ echo '<div class="alert danger">'.e($label.': '.$e->getMessage()).'</div>'; }
        $new[$key]=[
            'enabled'=>!empty($_POST['ads'][$key]['enabled'])?1:0,
            'title'=>trim((string)($_POST['ads'][$key]['title'] ?? $label)),
            'type'=>($_POST['ads'][$key]['type'] ?? 'image')==='html' ? 'html' : 'image',
            'image'=>$img,
            'link'=>trim((string)($_POST['ads'][$key]['link'] ?? '')),
            'html'=>trim((string)($_POST['ads'][$key]['html'] ?? '')),
            'target'=>($_POST['ads'][$key]['target'] ?? '_blank')==='_self' ? '_self' : '_blank',
            'show_mobile'=>!empty($_POST['ads'][$key]['show_mobile'])?1:0,
            'show_desktop'=>!empty($_POST['ads'][$key]['show_desktop'])?1:0,
        ];
    }
    update_setting_json('ad_slots',$new); $ads=$new; $msg='Reklam alanları kaydedildi.';
}
?>
<div class="toolbar"><h1>Reklam Alanları</h1><div class="muted">OMG: <code>{{ ad.header }}</code> · PHP: <code>omurga_ad_area('header')</code></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<div class="card"><p class="muted">Reklam sistemi çekirdekte tutulur. Tema sadece reklam alanını çağırır; reklam içeriğini bu ekrandan değiştirirsin.</p></div>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=csrf_token()?>">
<div class="v19-ad-grid">
<?php foreach($areas as $key=>$label): $ad=$ads[$key] ?? omurga_default_ad_slots()[$key]; ?>
  <section class="card ad-editor v19-ad-card">
    <div class="v19-ad-head"><h2><?=e($label)?></h2><code>{ad area="<?=e($key)?>"}</code></div>
    <label class="check-row"><input type="checkbox" name="ads[<?=e($key)?>][enabled]" value="1" <?=!empty($ad['enabled'])?'checked':''?>> Aktif</label>
    <div class="grid-2 equal">
      <label>Reklam Başlığı<input name="ads[<?=e($key)?>][title]" value="<?=e($ad['title'] ?? $label)?>"></label>
      <label>Tür<select name="ads[<?=e($key)?>][type]"><option value="image" <?=(($ad['type']??'image')==='image'?'selected':'')?>>Görsel reklam</option><option value="html" <?=(($ad['type']??'')==='html'?'selected':'')?>>HTML / Adsense</option></select></label>
    </div>
    <label>Görsel Yolu<input name="ads[<?=e($key)?>][image]" value="<?=e($ad['image'] ?? '')?>" placeholder="uploads/2026/06/reklam.webp"></label>
    <label>Görsel Yükle<input type="file" name="upload_<?=e($key)?>" accept="image/*"></label>
    <label>Bağlantı<input name="ads[<?=e($key)?>][link]" value="<?=e($ad['link'] ?? '')?>" placeholder="https://..."></label>
    <label>HTML / Adsense Kodu<textarea name="ads[<?=e($key)?>][html]" style="min-height:90px" placeholder="HTML reklam kodu buraya"><?=e($ad['html'] ?? '')?></textarea></label>
    <div class="grid-3 equal">
      <label class="check-row"><input type="checkbox" name="ads[<?=e($key)?>][target]" value="_blank" <?=(($ad['target']??'_blank')==='_blank'?'checked':'')?>> Yeni sekme</label>
      <label class="check-row"><input type="checkbox" name="ads[<?=e($key)?>][show_desktop]" value="1" <?=!empty($ad['show_desktop'])?'checked':''?>> Masaüstü</label>
      <label class="check-row"><input type="checkbox" name="ads[<?=e($key)?>][show_mobile]" value="1" <?=!empty($ad['show_mobile'])?'checked':''?>> Mobil</label>
    </div>
    <div class="v19-ad-preview"><?=omurga_ad_area($key) ?: '<span class="muted">Önizleme yok veya reklam pasif.</span>'?></div>
  </section>
<?php endforeach; ?>
</div>
<button class="btn primary">Reklamları Kaydet</button>
</form>
<?php require '_footer.php'; ?>
