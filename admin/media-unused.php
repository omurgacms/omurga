<?php
require '_layout.php';
verify_csrf();
require_cap('media.manage');
$t=table_name('media'); $postsT=table_name('posts'); $msg=''; $err='';
function media_unused_usage_v34(string $path): int { global $postsT; if($path==='') return 0; try{$like='%'.$path.'%'; $st=db()->prepare("SELECT COUNT(*) FROM $postsT WHERE featured_image=? OR social_image=? OR content LIKE ? OR gallery_images LIKE ?"); $st->execute([$path,$path,$like,$like]); return (int)$st->fetchColumn();}catch(Throwable $e){return 0;} }
function media_unused_delete_v34(array $m): void { global $t; $abs=omurga_safe_existing_file((string)($m['file_path']??''), ['uploads']); if($abs) @unlink($abs); if(!empty($m['original_path'])){ $orig=omurga_safe_existing_file((string)$m['original_path'], ['uploads']); if($orig) @unlink($orig); } db()->prepare("DELETE FROM $t WHERE id=?")->execute([(int)$m['id']]); }
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete_unused'){
  try{ $ids=array_values(array_filter(array_map('intval', $_POST['ids']??[]))); if(!$ids) throw new RuntimeException('Seçili dosya yok.'); $in=implode(',',array_fill(0,count($ids),'?')); $st=db()->prepare("SELECT * FROM $t WHERE id IN ($in)"); $st->execute($ids); $done=0; foreach($st->fetchAll() as $m){ if(media_unused_usage_v34((string)$m['file_path'])===0){ media_unused_delete_v34($m); $done++; } } $msg=$done.' kullanılmayan dosya silindi.'; }catch(Throwable $e){ $err=$e->getMessage(); }
}
$limit=max(50,min(500,(int)($_GET['limit']??200))); $rows=[]; try{ $rows=db()->query("SELECT * FROM $t ORDER BY created_at DESC LIMIT $limit")->fetchAll(); }catch(Throwable $e){ $err=$e->getMessage(); }
$unused=[]; foreach($rows as $m){ if(media_unused_usage_v34((string)$m['file_path'])===0) $unused[]=$m; }
?>
<div class="toolbar"><h1>Kullanılmayan Dosyalar</h1><div><a class="btn light" href="media.php">Medya Kütüphanesi</a><a class="btn light" href="media-webp.php">WebP Dönüştür</a></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>
<div class="card"><h2>Tarama</h2><p style="color:var(--muted)">Bu ekran son dosyalar içinde içerik, galeri, öne çıkan görsel veya sosyal görsel alanlarında kullanılmayan medyaları gösterir. Silmeden önce mutlaka kontrol et.</p><form method="get" style="display:flex;gap:10px;align-items:end"><label>Tarama limiti<select name="limit"><option <?=$limit===100?'selected':''?> value="100">Son 100 dosya</option><option <?=$limit===200?'selected':''?> value="200">Son 200 dosya</option><option <?=$limit===500?'selected':''?> value="500">Son 500 dosya</option></select></label><button class="btn light">Yeniden Tara</button></form></div>
<form method="post" class="card"><?=csrf_field()?><input type="hidden" name="action" value="delete_unused"><div class="media-v34-list-head"><strong><?=count($unused)?> kullanılmayan dosya bulundu</strong><button class="btn danger" onclick="return confirm('Seçili kullanılmayan dosyalar silinsin mi?')">Seçilileri Sil</button></div>
<?php if(!$unused): ?><div class="empty-state">Seçilen tarama limitinde kullanılmayan dosya bulunamadı.</div><?php endif; ?>
<div class="media-v34-grid">
<?php foreach($unused as $m): $mime=(string)($m['mime']??''); $isImg=str_starts_with($mime,'image/'); ?>
<article class="media-v34-item"><label class="media-v34-check"><input type="checkbox" name="ids[]" value="<?=$m['id']?>"></label><div class="media-v34-thumb"><?php if($isImg): ?><img src="../<?=e($m['file_path'])?>" alt="<?=e($m['alt_text']??'')?>"><?php else: ?><span><?=e(strtoupper(pathinfo($m['file_name'],PATHINFO_EXTENSION) ?: 'DOSYA'))?></span><?php endif; ?></div><div class="media-v34-info"><strong><?=e($m['file_name'])?></strong><small><?=e($m['file_path'])?></small><div class="media-v34-meta"><span><?=e($mime ?: '-')?></span><span><?=isset($m['file_size'])?e(omurga_human_size((int)$m['file_size'])):'-'?></span></div><input onclick="this.select()" value="<?=e($m['file_path'])?>" readonly></div></article>
<?php endforeach; ?>
</div></form>
<?php require '_footer.php'; ?>
