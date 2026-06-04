<?php
require '_layout.php';
verify_csrf();
require_cap('posts.view');
$postsT=table_name('posts');
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0);
  $action=$_POST['action']??'';
  if($action==='delete'){
    require_cap('posts.delete');
    db()->prepare("DELETE FROM $postsT WHERE id=? AND type='page'")->execute([$id]);
    sync_post_tags($id, '');
    log_activity('page.delete','Sabit sayfa silindi: #'.$id);
    echo '<div class="alert success">Sabit sayfa silindi.</div>';
  }
}
$q=trim($_GET['q']??'');
$status=trim($_GET['status']??'');
$where=["type='page'"]; $params=[];
if($status){$where[]='status=?';$params[]=$status;}
if($q){$where[]='title LIKE ?';$params[]='%'.$q.'%';}
$sql="SELECT * FROM $postsT WHERE ".implode(' AND ',$where)." ORDER BY sort_order ASC, created_at DESC LIMIT 200";
$stmt=db()->prepare($sql); $stmt->execute($params); $pages=$stmt->fetchAll();
?>
<div class="toolbar"><h1>Statik Sayfalar</h1><a class="btn primary" href="page-edit.php">+ Yeni Sabit Sayfa</a></div>
<div class="card"><form method="get" class="toolbar"><input name="q" placeholder="Sayfa başlığında ara..." value="<?=e($q)?>"><select name="status"><option value="">Tüm durumlar</option><option value="published" <?=$status==='published'?'selected':''?>>Yayında</option><option value="draft" <?=$status==='draft'?'selected':''?>>Taslak</option><option value="pending" <?=$status==='pending'?'selected':''?>>Beklemede</option></select><button class="btn dark">Filtrele</button></form>
<div class="alert info">Sabit sayfalar WordPress mantığındadır; kategori ve etiket kullanmaz. Hakkımızda, İletişim, KVKK gibi içerikler burada yönetilir.</div>
<table class="table content-table"><thead><tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>Yayın Tarihi</th><th>Sıra</th><th>İşlem</th></tr></thead><tbody><?php foreach($pages as $p): ?><tr><td data-label="Başlık"><strong><?=e($p['title'])?></strong></td><td data-label="Slug"><small><?=e($p['slug'])?></small></td><td data-label="Durum"><span class="badge <?=e($p['status'])?>"><?=e(omurga_public_status_label($p))?></span></td><td data-label="Yayın"><small><?= $p['published_at'] ? e(date('d.m.Y H:i', strtotime($p['published_at']))) : '-' ?></small></td><td data-label="Sıra"><?=e($p['sort_order'] ?? 100)?></td><td data-label="İşlem"><a class="btn light" href="page-edit.php?id=<?=$p['id']?>">Düzenle</a><?php if($p['status']==='published'): ?><a class="btn light" target="_blank" href="<?=e(page_url($p))?>">Önizle</a><?php endif; ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$p['id']?>"><?php if(can('posts.delete')): ?><button name="action" value="delete" class="btn danger" onclick="return confirm('Sabit sayfa silinsin mi?')">Sil</button><?php endif; ?></form></td></tr><?php endforeach; ?><?php if(!$pages): ?><tr><td colspan="6">Henüz sabit sayfa yok.</td></tr><?php endif; ?></tbody></table></div>
<?php require '_footer.php'; ?>
