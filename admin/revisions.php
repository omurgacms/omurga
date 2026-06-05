<?php
require '_layout.php';
verify_csrf();
require_cap('posts.view');
$postId=(int)($_GET['post_id'] ?? 0);
$revId=(int)($_GET['id'] ?? 0);
$action=$_POST['action'] ?? $_GET['action'] ?? '';
if($_SERVER['REQUEST_METHOD']==='POST' && $action==='restore'){
  require_cap('posts.edit');
  try{ $pid=omurga_restore_revision((int)($_POST['revision_id'] ?? 0)); redirect('admin/post-edit.php?id='.$pid.'&restored=1'); }
  catch(Throwable $e){ echo '<div class="alert error">'.e($e->getMessage()).'</div>'; }
}
$postsT=table_name('posts');
$post=null;
if($postId){ $st=db()->prepare("SELECT * FROM $postsT WHERE id=?"); $st->execute([$postId]); $post=$st->fetch(); }
$revision=null;
if($revId){ $rt=table_name('post_revisions'); $st=db()->prepare("SELECT r.*,u.name user_name FROM $rt r LEFT JOIN ".table_name('users')." u ON u.id=r.user_id WHERE r.id=?"); $st->execute([$revId]); $revision=$st->fetch(); if($revision){ $postId=(int)$revision['post_id']; } }
?>
<div class="toolbar"><div><h1>İçerik Revizyonları</h1><p>İçeriklerin eski sürümlerini görüntüle ve gerektiğinde geri al.</p></div><div class="toolbar-actions"><?php if($postId): ?><a class="btn light" href="post-edit.php?id=<?=e($postId)?>">İçeriğe Dön</a><?php endif; ?></div></div>
<?php if($revision): $snap=json_decode($revision['snapshot'] ?? '{}', true) ?: []; ?>
<div class="card">
  <h2>Revizyon Detayı</h2>
  <p><b>Tarih:</b> <?=e($revision['created_at'])?> &nbsp; <b>Kullanıcı:</b> <?=e($revision['user_name'] ?: 'Sistem')?> &nbsp; <b>Tür:</b> <?=e($revision['revision_type'])?></p>
  <p><b>Değişen alanlar:</b> <?php $fields=array_filter(explode(',', $revision['changed_fields'] ?? '')); echo $fields ? e(implode(', ', array_map('omurga_revision_label',$fields))) : '—'; ?></p>
  <form method="post" onsubmit="return confirm('Bu revizyona geri dönülsün mü? Mevcut hal ayrıca revizyon olarak saklanacak.');"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="revision_id" value="<?=e($revision['id'])?>"><button class="btn primary">Bu Sürüme Geri Dön</button></form>
</div>
<div class="card" style="margin-top:16px"><h2>Önizleme</h2><div class="mini-grid two"><label>Başlık<input readonly value="<?=e($snap['title'] ?? '')?>"></label><label>Durum<input readonly value="<?=e($snap['status'] ?? '')?>"></label></div><label>Spot<textarea readonly><?=e($snap['spot'] ?? '')?></textarea></label><label>İçerik<textarea readonly style="min-height:320px"><?=e(strip_tags((string)($snap['content'] ?? '')))?></textarea></label></div>
<?php else: ?>
<?php
$rt=table_name('post_revisions');
if($postId){ $st=db()->prepare("SELECT r.*,u.name user_name FROM $rt r LEFT JOIN ".table_name('users')." u ON u.id=r.user_id WHERE r.post_id=? ORDER BY r.id DESC"); $st->execute([$postId]); $rows=$st->fetchAll(); }
else { $rows=db()->query("SELECT r.*,p.title post_title,u.name user_name FROM $rt r LEFT JOIN $postsT p ON p.id=r.post_id LEFT JOIN ".table_name('users')." u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 100")->fetchAll(); }
?>
<div class="card"><h2><?= $post ? e($post['title']).' Revizyonları' : 'Son Revizyonlar' ?></h2>
<table class="table"><thead><tr><th>Tarih</th><th>İçerik</th><th>Kullanıcı</th><th>Değişen Alanlar</th><th>İşlem</th></tr></thead><tbody>
<?php foreach($rows as $r): $fields=array_filter(explode(',', $r['changed_fields'] ?? '')); ?>
<tr><td><?=e($r['created_at'])?></td><td><?=e($post['title'] ?? $r['post_title'] ?? $r['title'])?></td><td><?=e($r['user_name'] ?: 'Sistem')?></td><td><?= $fields ? e(implode(', ', array_map('omurga_revision_label',$fields))) : '—' ?></td><td><a class="btn light" href="revisions.php?id=<?=e($r['id'])?>">Görüntüle</a></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="5">Henüz revizyon yok.</td></tr><?php endif; ?>
</tbody></table></div>
<?php endif; ?>
<?php require '_footer.php'; ?>
