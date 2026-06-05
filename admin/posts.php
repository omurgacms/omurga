<?php
require '_layout.php';
verify_csrf();
require_cap('posts.view');
if(($_GET['type'] ?? '')==='page'){ redirect('admin/pages.php'); }
$postsT=table_name('posts');
$catsT=table_name('categories');
$type=preg_replace('/[^a-z0-9_\-]/','', strtolower($_GET['type'] ?? ''));
$status=preg_replace('/[^a-z0-9_\-]/','', strtolower($_GET['status'] ?? ''));
$q=trim($_GET['q'] ?? '');
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0); $action=$_POST['action']??'';
  if($action==='trash'){ require_cap('posts.delete'); omurga_post_trash($id,'post'); echo '<div class="alert success">Yazı çöp kutusuna taşındı.</div>'; }
  if($action==='restore'){ require_cap('posts.delete'); omurga_post_restore($id,'post'); echo '<div class="alert success">Yazı geri yüklendi ve taslak yapıldı.</div>'; }
  if($action==='delete_permanent'){ require_cap('posts.delete'); omurga_post_delete_permanently($id,'post'); echo '<div class="alert success">Yazı kalıcı olarak silindi.</div>'; }
}
$where=[]; $params=[];
if($type){$where[]='p.type=?';$params[]=$type;} else { $where[]="p.type<>'page'"; }
if($status){$where[]='p.status=?';$params[]=$status;} else { $where[]="p.status<>'trash'"; }
if($q){$where[]='p.title LIKE ?';$params[]='%'.$q.'%';}
if(current_user_role()==='author'){ $where[]='p.author_id=?'; $params[]=(int)($_SESSION['omurga_user_id']??0); }
$sql="SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id" . ($where?' WHERE '.implode(' AND ',$where):'') . " ORDER BY p.created_at DESC LIMIT 150";
$stmt=db()->prepare($sql); $stmt->execute($params); $posts=$stmt->fetchAll();
$labels=type_labels(); unset($labels['page']); $pageTitle=$status==='trash'?'Çöp Kutusu - Yazılar':'Yazılar';
?>
<div class="toolbar"><h1><?=e($pageTitle)?></h1><a class="btn primary" href="post-edit.php?type=<?=e($type ?: primary_content_type())?>">+ Yeni Yazı</a></div>
<div class="card"><form method="get" class="toolbar"><input type="hidden" name="type" value="<?=e($type)?>"><input name="q" placeholder="<?=e(om_t('posts.search_title','Başlıkta ara...'))?>" value="<?=e($q)?>"><select name="status"><option value=""><?=e(om_t('status.all','Tüm durumlar'))?></option><?php foreach(omurga_status_labels() as $k=>$v): ?><option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select><button class="btn dark"><?=e(om_t('admin.filter','Filtrele'))?></button><a class="btn light" href="posts.php?type=<?=e($type)?>&status=trash">Çöp Kutusu</a></form>
<div class="type-tabs"><?php foreach($labels as $k=>$v): ?><a class="<?= $type===$k?'active':'' ?>" href="posts.php?type=<?=e($k)?>"><?=e($v)?></a><?php endforeach; ?></div>
<div class="alert info">Silinen yazılar önce çöp kutusuna taşınır. Çöp kutusundan geri yüklenebilir veya kalıcı silinebilir.</div>
<table class="table content-table"><thead><tr><th>Görsel</th><th>Başlık</th><th>Yazı Türü</th><th>Kategori</th><th>Durum</th><th>Yayın Tarihi</th><th>Medya</th><th>İşlem</th></tr></thead><tbody><?php foreach($posts as $p): ?><tr><td data-label="Görsel"><?php if($p['featured_image']): ?><img class="mini-thumb" src="<?=e(image_url($p['featured_image']))?>"><?php else: ?><span class="mini-empty">O</span><?php endif; ?></td><td data-label="Başlık"><strong><?=e($p['title'])?></strong><br><small><?=e($p['slug'])?></small></td><td data-label="Yazı Türü"><?=e(type_label($p['type']))?></td><td data-label="Kategori"><?=e($p['category_name'] ?? '-')?></td><td data-label="Durum"><span class="badge <?=e($p['status'])?>"><?=e(omurga_public_status_label($p))?></span></td><td data-label="Yayın"><small><?= $p['published_at'] ? e(date('d.m.Y H:i', strtotime($p['published_at']))) : '-' ?></small></td><td data-label="Medya"><small><?=!empty($p['video_url'])?'Video ':''?><?=!empty($p['gallery_images'])?'Galeri':''?></small></td><td data-label="İşlem"><?php if($p['status']!=='trash'): ?><a class="btn light" href="post-edit.php?id=<?=$p['id']?>">Düzenle</a><?php if($p['status']==='published'): ?><a class="btn light" target="_blank" href="<?=e(post_url($p))?>">Önizle</a><?php endif; ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$p['id']?>"><?php if(can('posts.delete')): ?><button name="action" value="trash" class="btn danger" onclick="return confirm('Çöp kutusuna taşınsın mı?')">Çöpe Taşı</button><?php endif; ?></form><?php else: ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$p['id']?>"><button name="action" value="restore" class="btn light">Geri Yükle</button><button name="action" value="delete_permanent" class="btn danger" onclick="return confirm('Kalıcı olarak silinsin mi? Bu işlem geri alınamaz.')">Kalıcı Sil</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$posts): ?><tr><td colspan="8">Kayıt bulunamadı.</td></tr><?php endif; ?></tbody></table></div>
<?php require '_footer.php'; ?>
