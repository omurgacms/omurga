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
  if(str_starts_with($action,'bulk_')){
    $ids=array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
    if($ids){
      if($action==='bulk_publish'){
        require_cap('posts.publish');
        $st=db()->prepare("UPDATE $postsT SET status='published', published_at=COALESCE(published_at,NOW()), updated_at=NOW() WHERE id=? AND type='page'");
        foreach($ids as $bulkId){ $st->execute([$bulkId]); }
      } elseif($action==='bulk_draft'){
        require_cap('posts.edit');
        $st=db()->prepare("UPDATE $postsT SET status='draft', updated_at=NOW() WHERE id=? AND type='page'");
        foreach($ids as $bulkId){ $st->execute([$bulkId]); }
      } elseif($action==='bulk_trash'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_trash($bulkId,'page'); }
      } elseif($action==='bulk_restore'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_restore($bulkId,'page'); }
      } elseif($action==='bulk_delete_permanent'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_delete_permanently($bulkId,'page'); }
      }
      echo '<div class="alert success">Seçili sayfalara işlem uygulandı.</div>';
    }
  }
}
$q=trim($_GET['q']??'');
$status=preg_replace('/[^a-z0-9_\-]/','', strtolower($_GET['status']??''));
$where=["type='page'"]; $params=[];
if($status){$where[]='status=?';$params[]=$status;} else { $where[]="status<>'trash'"; }
if($q){$where[]='title LIKE ?';$params[]='%'.$q.'%';}
$sql="SELECT * FROM $postsT WHERE ".implode(' AND ',$where)." ORDER BY sort_order ASC, created_at DESC LIMIT 200";
$stmt=db()->prepare($sql); $stmt->execute($params); $pages=$stmt->fetchAll();
$pageTitle=$status==='trash'?'Çöp Kutusu - Sayfalar':'Sayfalar';
$total=count($pages); $publishedCount=0; $draftCount=0; $trashCount=0;
foreach($pages as $row){ if(($row['status'] ?? '')==='published') $publishedCount++; elseif(($row['status'] ?? '')==='trash') $trashCount++; else $draftCount++; }
?>
<div class="om-list-page om-content-list-page">
  <div class="om-list-hero">
    <div class="om-list-hero-main">
      <span class="om-list-hero-icon">▣</span>
      <div><h1><?=e($pageTitle)?></h1><p>İçerik yönetimi / Sayfalar</p></div>
    </div>
    <a class="btn primary om-list-new-btn" href="page-edit.php">+ Yeni Sayfa</a>
  </div>
  <div class="om-list-stats">
    <div><span>Toplam</span><b><?=e((string)$total)?></b></div>
    <div><span>Yayında</span><b class="ok"><?=e((string)$publishedCount)?></b></div>
    <div><span>Taslak/Bekleyen</span><b class="warn"><?=e((string)$draftCount)?></b></div>
    <div><span>Çöp</span><b class="danger"><?=e((string)$trashCount)?></b></div>
  </div>
  <section class="om-list-panel">
    <div class="om-list-head">
      <div><h2>Sitedeki sabit sayfaların listesi</h2><span>Arama, filtre ve toplu işlemler</span></div>
      <details class="om-advanced-search" <?=($q || $status)?'open':''?>>
        <summary>🔎 Gelişmiş arama</summary>
        <form method="get" class="om-filter-grid">
          <input name="q" placeholder="Sayfa başlığında ara..." value="<?=e($q)?>">
          <select name="status"><option value="">Tüm durumlar</option><?php foreach(omurga_status_labels() as $k=>$v): ?><option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select>
          <button class="btn dark">Filtrele</button><a class="btn light" href="pages.php">Sıfırla</a><a class="btn light" href="pages.php?status=trash">Çöp Kutusu</a>
        </form>
      </details>
    </div>
    <form method="post" class="om-bulk-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="0">
      <div class="om-list-table-wrap"><table class="om-list-table"><thead><tr><th class="om-col-check"><input type="checkbox" onclick="document.querySelectorAll('.om-row-check').forEach(c=>c.checked=this.checked)"></th><th>Başlık</th><th>Durum</th><th>Yayın</th><th>Sıra</th><th class="om-col-actions">İşlem</th></tr></thead><tbody>
        <?php foreach($pages as $p): ?><tr><td class="om-col-check" data-label="Seç"><input class="om-row-check" type="checkbox" name="ids[]" value="<?=$p['id']?>"></td><td class="om-title-cell" data-label="Başlık"><a class="om-row-title" href="page-edit.php?id=<?=$p['id']?>"><?=e($p['title'])?></a><div class="om-row-slug"><?=e($p['slug'])?></div></td><td data-label="Durum"><span class="om-status-dot <?=e($p['status'])?>">●</span> <span class="badge <?=e($p['status'])?>"><?=e(omurga_public_status_label($p))?></span></td><td data-label="Yayın"><small><?= $p['published_at'] ? e(date('d.m.Y H:i', strtotime($p['published_at']))) : '-' ?></small></td><td data-label="Sıra"><?=e((string)($p['sort_order'] ?? 100))?></td><td class="om-col-actions" data-label="İşlem"><?php if($p['status']!=='trash'): ?><a class="btn light small" href="page-edit.php?id=<?=$p['id']?>">Düzenle</a><?php if($p['status']==='published'): ?><a class="btn light small" target="_blank" href="<?=e(page_url($p))?>">Önizle</a><?php endif; ?><?php if(can('posts.delete')): ?><button name="action" value="trash" class="btn danger small" onclick="this.form.id.value='<?=$p['id']?>';return confirm('Çöp kutusuna taşınsın mı?')">Çöp</button><?php endif; ?><?php else: ?><button name="action" value="restore" class="btn light small" onclick="this.form.id.value='<?=$p['id']?>'">Geri Yükle</button><button name="action" value="delete_permanent" class="btn danger small" onclick="this.form.id.value='<?=$p['id']?>';return confirm('Kalıcı olarak silinsin mi?')">Kalıcı Sil</button><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$pages): ?><tr><td colspan="6" class="om-empty-row">Kayıt bulunamadı.</td></tr><?php endif; ?>
      </tbody></table></div>
      <div class="om-list-footer"><span><?=e((string)$total)?> kayıt listeleniyor</span><div class="om-bulk-actions"><select name="action"><option value="">-- Toplu İşlemler --</option><?php if($status==='trash'): ?><option value="bulk_restore">Seçilileri geri yükle</option><option value="bulk_delete_permanent">Seçilileri kalıcı sil</option><?php else: ?><option value="bulk_publish">Seçilileri yayınla</option><option value="bulk_draft">Seçilileri taslağa al</option><option value="bulk_trash">Seçilileri çöpe taşı</option><?php endif; ?></select><button class="btn dark small" onclick="return this.form.action.value ? confirm('Seçili kayıtlara işlem uygulansın mı?') : false">Uygula</button></div></div>
    </form>
  </section>
</div>
<?php require '_footer.php'; ?>
