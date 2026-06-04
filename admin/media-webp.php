<?php require '_layout.php'; verify_csrf(); require_cap('media.manage'); $t=table_name('media');
$msg=''; $err=''; $converted=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $quality=max(50,min(95,(int)($_POST['quality'] ?? setting('webp_quality','82')))); $replace=!empty($_POST['replace_record']);
        if(($_POST['action'] ?? '')==='convert_selected'){
            $ids=array_map('intval', $_POST['ids'] ?? []); if(!$ids) throw new RuntimeException('Dönüştürülecek görsel seçilmedi.');
            $in=implode(',', array_fill(0,count($ids),'?')); $st=db()->prepare("SELECT * FROM $t WHERE id IN ($in)"); $st->execute($ids); $rows=$st->fetchAll();
            foreach($rows as $m){ $converted[]=omurga_convert_existing_media_to_webp($m,$replace,$quality); }
        } elseif(($_POST['action'] ?? '')==='convert_batch'){
            $rows=db()->query("SELECT * FROM $t WHERE mime IN ('image/jpeg','image/png') ORDER BY id DESC LIMIT 30")->fetchAll();
            foreach($rows as $m){ $converted[]=omurga_convert_existing_media_to_webp($m,$replace,$quality); }
        }
        $ok=count(array_filter($converted,fn($r)=>!empty($r['ok']))); $msg=$ok.' görsel WebP’ye çevrildi.';
    }catch(Throwable $e){ $err=$e->getMessage(); }
}
$items=db()->query("SELECT * FROM $t WHERE mime IN ('image/jpeg','image/png') ORDER BY created_at DESC LIMIT 120")->fetchAll();
?>
<div class="toolbar"><h1>WebP Dönüştür</h1><div><a class="btn light" href="media.php">Medya Kütüphanesi</a></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>
<div class="card"><h2>Toplu WebP İşlemi</h2><p style="color:var(--muted)">JPG/PNG görselleri WebP’ye çevirir. İstersen kayıtları WebP dosyasına taşıyabilir veya yeni WebP kopyası olarak ekleyebilirsin.</p><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="convert_batch"><label>Kalite<input type="number" name="quality" min="50" max="95" value="<?=e(setting('webp_quality','82'))?>"></label><label style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="replace_record" value="1"> Medya kaydını WebP dosyasına taşı</label><button class="btn primary">Son 30 JPG/PNG Görseli Dönüştür</button></form></div>
<?php if($converted): ?><div class="card"><h2>İşlem Sonucu</h2><?php foreach($converted as $r): ?><p style="margin:6px 0;color:<?=!empty($r['ok'])?'#15803d':'#b91c1c'?>"><?=e($r['message'] ?? '')?> <?=!empty($r['path'])?e($r['path']):''?></p><?php endforeach; ?></div><?php endif; ?>
<form method="post" class="card"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="convert_selected"><h2>Seçili Görselleri Dönüştür</h2><div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:15px"><label>Kalite <input type="number" name="quality" min="50" max="95" value="<?=e(setting('webp_quality','82'))?>" style="width:100px"></label><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="replace_record" value="1"> Kaydı WebP’ye taşı</label><button class="btn primary">Seçilileri Dönüştür</button></div><div class="post-grid"><?php foreach($items as $m): ?><label class="post-card" style="cursor:pointer"><img src="../<?=e($m['file_path'])?>" style="width:100%;height:140px;object-fit:cover"><div class="body"><input type="checkbox" name="ids[]" value="<?=$m['id']?>"> <strong><?=e($m['file_name'])?></strong><p style="font-size:12px;color:var(--muted)"><?=e(($m['width']??'?').'×'.($m['height']??'?'))?> · <?=isset($m['file_size'])?e(omurga_human_size((int)$m['file_size'])):''?></p></div></label><?php endforeach; ?></div></form>
<?php require '_footer.php'; ?>
