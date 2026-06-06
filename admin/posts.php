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
$categoryFilter=(int)($_GET['category_id'] ?? 0);
$tagFilter=trim($_GET['tag'] ?? '');
$categories=[];
try { $categories=db()->query("SELECT id,name FROM $catsT ORDER BY sort_order,name LIMIT 500")->fetchAll(); } catch(Throwable $e) { $categories=[]; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id=(int)($_POST['id']??0); $action=$_POST['action']??'';
  if($action==='trash'){ require_cap('posts.delete'); omurga_post_trash($id,'post'); echo '<div class="alert success">Yazı çöp kutusuna taşındı.</div>'; }
  if($action==='restore'){ require_cap('posts.delete'); omurga_post_restore($id,'post'); echo '<div class="alert success">Yazı geri yüklendi ve taslak yapıldı.</div>'; }
  if($action==='delete_permanent'){ require_cap('posts.delete'); omurga_post_delete_permanently($id,'post'); echo '<div class="alert success">Yazı kalıcı olarak silindi.</div>'; }
  if($action==='publish'){ require_cap('posts.publish'); db()->prepare("UPDATE $postsT SET status='published', published_at=COALESCE(published_at,NOW()), updated_at=NOW() WHERE id=? AND type<>'page'")->execute([$id]); echo '<div class="alert success">Yazı yayınlandı.</div>'; }
  if($action==='set_draft'){ require_cap('posts.edit'); db()->prepare("UPDATE $postsT SET status='draft', updated_at=NOW() WHERE id=? AND type<>'page'")->execute([$id]); echo '<div class="alert success">Yazı taslağa alındı.</div>'; }
  if(str_starts_with($action,'bulk_')){
    $ids=array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
    if($ids){
      if($action==='bulk_publish'){
        require_cap('posts.publish');
        $st=db()->prepare("UPDATE $postsT SET status='published', published_at=COALESCE(published_at,NOW()), updated_at=NOW() WHERE id=? AND type<>'page'");
        foreach($ids as $bulkId){ $st->execute([$bulkId]); }
      } elseif($action==='bulk_draft'){
        require_cap('posts.edit');
        $st=db()->prepare("UPDATE $postsT SET status='draft', updated_at=NOW() WHERE id=? AND type<>'page'");
        foreach($ids as $bulkId){ $st->execute([$bulkId]); }
      } elseif($action==='bulk_trash'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_trash($bulkId,'post'); }
      } elseif($action==='bulk_restore'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_restore($bulkId,'post'); }
      } elseif($action==='bulk_delete_permanent'){
        require_cap('posts.delete'); foreach($ids as $bulkId){ omurga_post_delete_permanently($bulkId,'post'); }
      } elseif($action==='bulk_category'){
        require_cap('posts.edit'); $catId=(int)($_POST['bulk_category_id'] ?? 0);
        if($catId>0){ $st=db()->prepare("UPDATE $postsT SET category_id=?, updated_at=NOW() WHERE id=? AND type<>'page'"); foreach($ids as $bulkId){ $st->execute([$catId,$bulkId]); if(function_exists('omurga_sync_post_categories')) omurga_sync_post_categories($bulkId, [$catId]); } }
      } elseif($action==='bulk_tag_add'){
        require_cap('posts.edit'); $tagLine=trim((string)($_POST['bulk_tags'] ?? ''));
        if($tagLine!==''){ foreach($ids as $bulkId){ $existing=function_exists('tag_names_for_post') ? tag_names_for_post($bulkId) : []; $new=preg_split('/[,;\n]+/u',$tagLine) ?: []; sync_post_tags($bulkId, implode(',', array_merge($existing, $new))); } }
      } elseif($action==='bulk_tag_remove'){
        require_cap('posts.edit'); $remove=preg_split('/[,;\n]+/u', trim((string)($_POST['bulk_tags'] ?? ''))) ?: [];
        $remove=array_filter(array_map(fn($v)=>mb_strtolower(trim($v),'UTF-8'),$remove));
        foreach($ids as $bulkId){ $keep=[]; foreach(tag_names_for_post($bulkId) as $name){ if(!in_array(mb_strtolower(trim($name),'UTF-8'),$remove,true)) $keep[]=$name; } sync_post_tags($bulkId, implode(',',$keep)); }
      }
      echo '<div class="alert success">Seçili yazılara işlem uygulandı.</div>';
    }
  }
}
$where=[]; $params=[];
if($type){$where[]='p.type=?';$params[]=$type;} else { $where[]="p.type<>'page'"; }
if($status){$where[]='p.status=?';$params[]=$status;} else { $where[]="p.status<>'trash'"; }
if($q){$where[]='(p.title LIKE ? OR p.slug LIKE ?)';$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
if($categoryFilter>0){$where[]='p.category_id=?';$params[]=$categoryFilter;}
if($tagFilter!==''){
  try{ $tagsT=table_name('tags'); $pt=table_name('post_tags'); $where[]="p.id IN (SELECT pt.post_id FROM $pt pt INNER JOIN $tagsT t ON t.id=pt.tag_id WHERE t.name LIKE ? OR t.slug LIKE ?)"; $params[]='%'.$tagFilter.'%'; $params[]='%'.slugify($tagFilter).'%'; }catch(Throwable $e){}
}
if(current_user_role()==='author'){ $where[]='p.author_id=?'; $params[]=(int)($_SESSION['omurga_user_id']??0); }
$sql="SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id" . ($where?' WHERE '.implode(' AND ',$where):'') . " ORDER BY p.created_at DESC LIMIT 150";
$stmt=db()->prepare($sql); $stmt->execute($params); $posts=$stmt->fetchAll();
$pageTitle=$status==='trash'?'Çöp Kutusu - Yazılar':'Yazılar';
$total=count($posts); $publishedCount=0; $draftCount=0; $trashCount=0;
foreach($posts as $row){ if(($row['status'] ?? '')==='published') $publishedCount++; elseif(($row['status'] ?? '')==='trash') $trashCount++; else $draftCount++; }
?>
<div class="om-list-page om-content-list-page">
  <div class="om-list-hero">
    <div class="om-list-hero-main">
      <span class="om-list-hero-icon">✎</span>
      <div><h1><?=e($pageTitle)?></h1><p>İçerik yönetimi / Yazılar</p></div>
    </div>
    <a class="btn primary om-list-new-btn" href="post-edit.php?type=<?=e($type ?: primary_content_type())?>">+ Yeni Yazı</a>
  </div>
  <div class="om-list-stats">
    <div><span>Toplam</span><b><?=e((string)$total)?></b></div>
    <div><span>Yayında</span><b class="ok"><?=e((string)$publishedCount)?></b></div>
    <div><span>Taslak/Bekleyen</span><b class="warn"><?=e((string)$draftCount)?></b></div>
    <div><span>Çöp</span><b class="danger"><?=e((string)$trashCount)?></b></div>
  </div>
  <section class="om-list-panel">
    <div class="om-list-head">
      <div><h2>Sitedeki yazıların listesi</h2><span>Arama, filtre ve toplu işlemler</span></div>
      <details class="om-advanced-search" <?=($q || $status || $categoryFilter || $tagFilter)?'open':''?>>
        <summary>🔎 Gelişmiş arama</summary>
        <form method="get" class="om-filter-grid">
          <input type="hidden" name="type" value="<?=e($type)?>">
          <input name="q" placeholder="Yazı başlığında ara..." value="<?=e($q)?>">
          <select name="status"><option value="">Tüm durumlar</option><?php foreach(omurga_status_labels() as $k=>$v): ?><option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select>
          <select name="category_id"><option value="0">Tüm kategoriler</option><?php foreach($categories as $c): ?><option value="<?=e((string)$c['id'])?>" <?=$categoryFilter===(int)$c['id']?'selected':''?>><?=e($c['name'])?></option><?php endforeach; ?></select>
          <input name="tag" placeholder="Etiket" value="<?=e($tagFilter)?>">
          <button class="btn dark">Filtrele</button><a class="btn light" href="posts.php?type=<?=e($type)?>">Sıfırla</a><a class="btn light" href="posts.php?type=<?=e($type)?>&status=trash">Çöp Kutusu</a>
        </form>
      </details>
    </div>
    <form method="post" class="om-bulk-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="0">
      <div class="om-list-table-wrap"><table class="om-list-table"><thead><tr><th class="om-col-check"><input type="checkbox" onclick="document.querySelectorAll('.om-row-check').forEach(c=>c.checked=this.checked)"></th><th>Başlık</th><th>Durum</th><th>Kategori</th><th>Yayın</th><th class="om-col-actions">İşlem</th></tr></thead><tbody>
        <?php foreach($posts as $p): ?><tr><td class="om-col-check" data-label="Seç"><input class="om-row-check" type="checkbox" name="ids[]" value="<?=$p['id']?>"></td><td class="om-title-cell" data-label="Başlık"><a class="om-row-title" href="post-edit.php?id=<?=$p['id']?>"><?=e($p['title'])?></a><div class="om-row-slug"><?=e($p['slug'])?></div></td><td data-label="Durum"><span class="om-status-dot <?=e($p['status'])?>">●</span> <span class="badge <?=e($p['status'])?>"><?=e(omurga_public_status_label($p))?></span></td><td data-label="Kategori"><?=e($p['category_name'] ?? '-')?></td><td data-label="Yayın"><small><?= $p['published_at'] ? e(date('d.m.Y H:i', strtotime($p['published_at']))) : '-' ?></small></td><td class="om-col-actions" data-label="İşlem"><?php if($p['status']!=='trash'): ?><a class="btn light small" href="post-edit.php?id=<?=$p['id']?>">Düzenle</a><?php if($p['status']==='published'): ?><a class="btn light small" target="_blank" href="<?=e(post_url($p))?>">Önizle</a><?php endif; ?><?php if(can('posts.delete')): ?><button name="action" value="trash" class="btn danger small" onclick="this.form.id.value='<?=$p['id']?>';return confirm('Çöp kutusuna taşınsın mı?')">Çöp</button><?php endif; ?><?php else: ?><button name="action" value="restore" class="btn light small" onclick="this.form.id.value='<?=$p['id']?>'">Geri Yükle</button><button name="action" value="delete_permanent" class="btn danger small" onclick="this.form.id.value='<?=$p['id']?>';return confirm('Kalıcı olarak silinsin mi?')">Kalıcı Sil</button><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$posts): ?><tr><td colspan="6" class="om-empty-row">Kayıt bulunamadı.</td></tr><?php endif; ?>
      </tbody></table></div>
      <div class="om-list-footer"><span><?=e((string)$total)?> kayıt listeleniyor</span><div class="om-bulk-actions"><select name="action" id="omBulkAction"><option value="">-- Toplu İşlemler --</option><?php if($status==='trash'): ?><option value="bulk_restore">Seçilileri geri yükle</option><option value="bulk_delete_permanent">Seçilileri kalıcı sil</option><?php else: ?><option value="bulk_publish">Seçilileri yayınla</option><option value="bulk_draft">Seçilileri taslağa al</option><option value="bulk_trash">Seçilileri çöpe taşı</option><option value="bulk_category">Kategoriyi değiştir</option><option value="bulk_tag_add">Etiket ekle</option><option value="bulk_tag_remove">Etiket kaldır</option><?php endif; ?></select><select name="bulk_category_id" class="om-bulk-extra om-bulk-category"><option value="0">Kategori seç</option><?php foreach($categories as $c): ?><option value="<?=e((string)$c['id'])?>"><?=e($c['name'])?></option><?php endforeach; ?></select><input name="bulk_tags" class="om-bulk-extra om-bulk-tags" placeholder="Etiketleri virgülle yaz"><button class="btn dark small" onclick="return this.form.action.value ? confirm('Seçili kayıtlara işlem uygulansın mı?') : false">Uygula</button></div></div>
    </form>
  </section>
</div>
<script>(function(){const s=document.getElementById('omBulkAction');if(!s)return;const cat=document.querySelector('.om-bulk-category');const tag=document.querySelector('.om-bulk-tags');function sync(){const v=s.value;if(cat)cat.style.display=v==='bulk_category'?'inline-flex':'none';if(tag)tag.style.display=(v==='bulk_tag_add'||v==='bulk_tag_remove')?'inline-flex':'none';}s.addEventListener('change',sync);sync();})();</script>
<?php require '_footer.php'; ?>
