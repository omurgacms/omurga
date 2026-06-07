<?php
require '_layout.php';
verify_csrf();
require_cap('comments.manage');

$commentsT=table_name('comments');
$postsT=table_name('posts');
$msg=''; $err='';
$statuses=om_comment_statuses();

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $id=(int)($_POST['id']??0);
    $action=$_POST['action']??'';
    if($action==='reply'){
      $parentId=$id;
      $content=trim(strip_tags((string)($_POST['reply_content']??'')));
      if($content==='' || mb_strlen($content,'UTF-8')>3000) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
      $st=db()->prepare("SELECT * FROM $commentsT WHERE id=? LIMIT 1"); $st->execute([$parentId]); $parent=$st->fetch();
      if(!$parent) throw new RuntimeException('Yorum bulunamadı.');
      $user=current_user();
      db()->prepare("INSERT INTO $commentsT (post_id,parent_id,author_name,author_email,author_ip,content,status,user_id) VALUES (?,?,?,?,?,?,?,?)")->execute([(int)$parent['post_id'],$parentId,$user['name'] ?? 'Omurga', $user['email'] ?? 'admin@example.com', $_SERVER['REMOTE_ADDR'] ?? '', $content, 'approved', $_SESSION['omurga_user_id'] ?? null]);
      $msg=om_t('comments.reply','Cevapla').' kaydedildi.';
    } elseif(in_array($action, ['pending','approved','spam','trash'], true)){
      db()->prepare("UPDATE $commentsT SET status=? WHERE id=?")->execute([$action,$id]);
      omurga_notify('Yorum durumu güncellendi', '#'.$id.' yorum durumu '.($statuses[$action] ?? $action).' yapıldı.', 'comment', 'admin/comments.php');
      $msg='Yorum durumu güncellendi.';
    } elseif($action==='delete'){
      db()->prepare("DELETE FROM $commentsT WHERE id=? OR parent_id=?")->execute([$id,$id]);
      $msg='Yorum kalıcı silindi.';
    }
  }catch(Throwable $e){ $err=$e->getMessage(); }
}

$status=$_GET['status'] ?? '';
if(!array_key_exists($status,$statuses)) $status='';
$where='1=1'; $params=[];
if($status!==''){ $where='c.status=?'; $params[]=$status; }
$stmt=db()->prepare("SELECT c.*, p.title post_title, p.slug post_slug, p.type post_type FROM $commentsT c LEFT JOIN $postsT p ON p.id=c.post_id WHERE $where ORDER BY c.created_at DESC, c.id DESC LIMIT 250");
$stmt->execute($params);
$rows=$stmt->fetchAll();
?>
<div class="toolbar"><h1><?=e(om_t('comments.title','Yorumlar'))?></h1></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>
<div class="card">
  <div class="type-tabs">
    <a class="<?=$status===''?'active':''?>" href="comments.php">Tüm yorumlar</a>
    <?php foreach($statuses as $sk=>$sv): ?><a class="<?=$status===$sk?'active':''?>" href="comments.php?status=<?=e($sk)?>"><?=e($sv)?></a><?php endforeach; ?>
  </div>
  <table class="table content-table">
    <thead><tr><th>Yazar</th><th>Yorum</th><th>İçerik</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): $postRef=['slug'=>$r['post_slug'] ?? '', 'type'=>$r['post_type'] ?? 'post']; $postLink=$postRef['type']==='page' ? page_url($postRef) : post_url($postRef); ?>
      <tr>
        <td data-label="Yazar"><strong><?=e($r['author_name'])?></strong><br><small><?=e($r['author_email'])?></small><br><small><?=e($r['author_ip'])?></small></td>
        <td data-label="Yorum"><?=nl2br(e(excerpt($r['content'],220)))?><?php if(!empty($r['parent_id'])): ?><br><small>Yanıt: #<?=e($r['parent_id'])?></small><?php endif; ?></td>
        <td data-label="İçerik"><a target="_blank" href="<?=e($postLink)?>"><?=e($r['post_title'] ?: ('#'.$r['post_id']))?></a></td>
        <td data-label="Durum"><span class="badge <?=e($r['status'])?>"><?=e($statuses[$r['status']] ?? $r['status'])?></span></td>
        <td data-label="Tarih"><small><?=e($r['created_at'])?></small></td>
        <td data-label="İşlem">
          <form method="post" class="inline-form">
            <?=csrf_field()?><input type="hidden" name="id" value="<?=e($r['id'])?>">
            <button class="btn light" name="action" value="approved"><?=e(om_t('comments.approve','Onayla'))?></button>
            <button class="btn light" name="action" value="pending"><?=e(om_t('comments.pending','Bekleyen'))?></button>
            <button class="btn light" name="action" value="spam"><?=e(om_t('comments.spam','Spam'))?></button>
            <button class="btn light" name="action" value="trash"><?=e(om_t('comments.trash','Çöp'))?></button>
            <button class="btn danger" name="action" value="delete" onclick="return confirm('Yorum kalıcı silinsin mi?')"><?=e(om_t('comments.delete','Sil'))?></button>
          </form>
          <details style="margin-top:8px"><summary><?=e(om_t('comments.reply','Cevapla'))?></summary>
            <form method="post" class="form-grid">
              <?=csrf_field()?><input type="hidden" name="id" value="<?=e($r['id'])?>"><input type="hidden" name="action" value="reply">
              <textarea name="reply_content" maxlength="3000" rows="3" required></textarea>
              <button class="btn primary"><?=e(om_t('comments.reply','Cevapla'))?></button>
            </form>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$rows): ?><tr><td colspan="6"><?=e(om_t('comments.no_comments','Henüz yorum yok.'))?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require '_footer.php'; ?>
