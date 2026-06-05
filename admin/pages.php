<?php
require '_layout.php';
verify_csrf();
require_cap('posts.view');
$postsT=table_name('posts');
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0); $action=$_POST['action']??'';
  if($action==='trash'){ require_cap('posts.delete'); omurga_post_trash($id,'page'); echo '<div class="alert success">Sayfa çöp kutusuna taşındı.</div>'; }
  if($action==='restore'){ require_cap('posts.delete'); omurga_post_restore($id,'page'); echo '<div class="alert success">Sayfa geri yüklendi ve taslak yapıldı.</div>'; }
  if($action==='delete_permanent'){ require_cap('posts.delete'); omurga_post_delete_permanently($id,'page'); echo '<div class="alert success">Sayfa kalıcı olarak silindi.</div>'; }
}
$q=trim($_GET['q']??'');
$status=preg_replace('/[^a-z0-9_\-]/','', strtolower($_GET['status']??''));
$where=["type='page'"]; $params=[];
if($status){$where[]='status=?';$params[]=$status;} else { $where[]="status<>'trash'"; }
if($q){$where[]='title LIKE ?';$params[]='%'.$q.'%';}
$sql="SELECT * FROM $postsT WHERE ".implode(' AND ',$where)." ORDER BY sort_order ASC, created_at DESC LIMIT 200";
$stmt=db()->prepare($sql); $stmt->execute($params); $pages=$stmt->fetchAll();
?>
<div class="toolbar"><h1><?= $status==='trash' ? 'Çöp Kutusu - Sayfalar' : 'Sayfalar' ?></h1><a class="btn primary" href="page-edit.php">+ Yeni Sayfa</a></div>
<div class="card"><form method="get" class="toolbar"><input name="q" placeholder="Sayfa başlığında ara..." value="<?=e($q)?>"><select name="status"><option value="">Tüm durumlar</option><?php foreach(omurga_status_labels() as $k=>$v): ?><option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select><button class="btn dark">Filtrele</button><a class="btn light" href="pages.php?status=trash">Çöp Kutusu</a></form>
<div class="alert info">Sayfalar Omurga CMS sabit sayfa mantığıyla çalışır; kategori, etiket ve içerik akışı kullanmaz. Silinen sayfalar önce çöp kutusuna taşınır.</div>
<table class="table content-table"><thead><tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>Yayın Tarihi</th><th>Sıra</th><th>İşlem</th></tr></thead><tbody><?php foreach($pages as $p): ?><tr><td data-label="Başlık"><strong><?=e($p['title'])?></strong></td><td data-label="Slug"><small><?=e($p['slug'])?></small></td><td data-label="Durum"><span class="badge <?=e($p['status'])?>"><?=e(omurga_public_status_label($p))?></span></td><td data-label="Yayın"><small><?= $p['published_at'] ? e(date('d.m.Y H:i', strtotime($p['published_at']))) : '-' ?></small></td><td data-label="Sıra"><?=e($p['sort_order'] ?? 100)?></td><td data-label="İşlem"><?php if($p['status']!=='trash'): ?><a class="btn light" href="page-edit.php?id=<?=$p['id']?>">Düzenle</a><?php if($p['status']==='published'): ?><a class="btn light" target="_blank" href="<?=e(page_url($p))?>">Önizle</a><?php endif; ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$p['id']?>"><?php if(can('posts.delete')): ?><button name="action" value="trash" class="btn danger" onclick="return confirm('Sayfa çöp kutusuna taşınsın mı?')">Çöpe Taşı</button><?php endif; ?></form><?php else: ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$p['id']?>"><button name="action" value="restore" class="btn light">Geri Yükle</button><button name="action" value="delete_permanent" class="btn danger" onclick="return confirm('Kalıcı olarak silinsin mi? Bu işlem geri alınamaz.')">Kalıcı Sil</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$pages): ?><tr><td colspan="6">Kayıt bulunamadı.</td></tr><?php endif; ?></tbody></table></div>
<?php require '_footer.php'; ?>
