<?php
require '_layout.php';
verify_csrf();
require_cap('media.manage');

$t = table_name('media');
$postsT = table_name('posts');
$msg=''; $err='';

function media_cols_v34(): array { static $cols=null; global $t; if($cols===null){ try{$cols=db()->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_COLUMN);}catch(Throwable $e){$cols=[];} } return $cols; }
function media_has_col_v34(string $col): bool { return in_array($col, media_cols_v34(), true); }
function media_safe_order_v34(string $v): string { $allowed=['created_at','file_name','file_size','mime','width']; return in_array($v,$allowed,true)?$v:'created_at'; }
function media_type_group_v34(array $m): string { $mime=(string)($m['mime']??''); if(str_starts_with($mime,'image/')) return 'image'; if(str_starts_with($mime,'video/')) return 'video'; if($mime==='application/pdf') return 'pdf'; return 'other'; }
function media_usage_v34(string $path): array {
    global $postsT;
    $empty=['count'=>0,'labels'=>[],'ids'=>[]]; if($path==='') return $empty;
    try{
        $like='%'.$path.'%';
        $sql="SELECT id,title,featured_image,social_image,content,gallery_images FROM $postsT WHERE featured_image=? OR social_image=? OR content LIKE ? OR gallery_images LIKE ? ORDER BY updated_at DESC, id DESC LIMIT 8";
        $st=db()->prepare($sql); $st->execute([$path,$path,$like,$like]); $rows=$st->fetchAll();
        $labels=[]; $ids=[];
        foreach($rows as $r){ $ids[]=(int)$r['id']; $where=[]; if(($r['featured_image']??'')===$path) $where[]='öne çıkan'; if(($r['social_image']??'')===$path) $where[]='sosyal'; if(strpos((string)($r['content']??''),$path)!==false) $where[]='içerik'; if(strpos((string)($r['gallery_images']??''),$path)!==false) $where[]='galeri'; $labels[]=trim(($r['title']??'İçerik').' · '.implode(', ',$where)); }
        return ['count'=>count($rows),'labels'=>$labels,'ids'=>$ids];
    }catch(Throwable $e){ return $empty; }
}
function media_update_meta_v34(int $id, array $data): void { global $t; $sets=[]; $vals=[]; foreach(['alt_text','title_text','description'] as $c){ if(media_has_col_v34($c) && array_key_exists($c,$data)){ $sets[]="$c=?"; $vals[]=$data[$c]; } } if(!$sets) return; $vals[]=$id; db()->prepare("UPDATE $t SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }
function media_delete_row_v34(array $m): void { global $t; $abs=omurga_safe_existing_file((string)($m['file_path']??''), ['uploads']); if($abs) @unlink($abs); if(!empty($m['original_path'])){ $orig=omurga_safe_existing_file((string)$m['original_path'], ['uploads']); if($orig) @unlink($orig); } db()->prepare("DELETE FROM $t WHERE id=?")->execute([(int)$m['id']]); }

// Lightweight media metadata migration. It is safe on older installs and ignored when columns exist.
try{
    $cols=media_cols_v34();
    if($cols && !in_array('title_text',$cols,true)) db()->exec("ALTER TABLE $t ADD title_text VARCHAR(220) NULL AFTER alt_text");
    if($cols && !in_array('description',$cols,true)) db()->exec("ALTER TABLE $t ADD description TEXT NULL AFTER title_text");
}catch(Throwable $e){}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    try{
        if($action==='delete'){
            $id=(int)($_POST['id']??0); $stmt=db()->prepare("SELECT * FROM $t WHERE id=?"); $stmt->execute([$id]); $m=$stmt->fetch();
            if($m){ media_delete_row_v34($m); $msg='Dosya silindi.'; }
        }
        if($action==='bulk'){
            $ids=array_values(array_filter(array_map('intval', $_POST['ids'] ?? []))); $bulk=$_POST['bulk_action'] ?? '';
            if(!$ids) throw new RuntimeException('Seçili medya yok.');
            $in=implode(',', array_fill(0,count($ids),'?'));
            $st=db()->prepare("SELECT * FROM $t WHERE id IN ($in)"); $st->execute($ids); $rows=$st->fetchAll();
            $done=0;
            foreach($rows as $m){
                if($bulk==='delete'){ media_delete_row_v34($m); $done++; }
                elseif($bulk==='webp') { $r=omurga_convert_existing_media_to_webp($m,false,(int)setting('webp_quality','82')); if($r['ok']??false) $done++; }
            }
            $msg=$done.' işlem tamamlandı.';
        }
        if($action==='update_meta'){
            $id=(int)($_POST['id']??0);
            media_update_meta_v34($id,[
                'alt_text'=>trim($_POST['alt_text']??''),
                'title_text'=>trim($_POST['title_text']??''),
                'description'=>trim($_POST['description']??''),
            ]);
            $msg='Medya bilgileri güncellendi.';
        }
        if($action==='upload'){
            $files=$_FILES['files'] ?? null; if(!$files || empty($files['name'][0])) throw new RuntimeException('Dosya seçilmedi.');
            $count=count($files['name']); $ok=0; $createdWebp=0; $skipped=0;
            for($i=0;$i<$count;$i++){
                if(($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){ $skipped++; continue; }
                $tmp=$files['tmp_name'][$i]; $mime=mime_content_type($tmp) ?: '';
                $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','application/pdf'=>'pdf','video/mp4'=>'mp4'];
                if(!isset($allowed[$mime])){ $skipped++; continue; }
                if(($files['size'][$i]??0)>64*1024*1024){ $skipped++; continue; }
                $relDir=omurga_media_rel_dir(); $dir=dirname(__DIR__).'/'.$relDir; if(!is_dir($dir)) mkdir($dir,0775,true);
                $name=omurga_prepare_upload_name_for_dir($dir, $files['name'][$i], trim($_POST['title_hint']??'')); $originalPath=$relDir.'/'.$name; $target=$dir.'/'.$name; $GLOBALS['omurga_last_uploaded_original_filename']=basename((string)($one['name'] ?? $files['name'][$i] ?? '')); $GLOBALS['omurga_last_uploaded_original_path']=$originalPath;
                if(!move_uploaded_file($tmp,$target)){ $skipped++; continue; }
                if(str_starts_with($mime,'image/')) omurga_resize_image_if_needed($target,$mime,(int)setting('media_max_width','1600'),(int)setting('media_jpeg_quality','86'));
                $finalPath=$originalPath;
                if(!empty($_POST['make_webp']) && in_array($mime,['image/jpeg','image/png'],true)){
                    $webp=create_webp_copy($target,$mime,(int)setting('webp_quality','82'));
                    if($webp){ $finalPath=$relDir.'/'.basename($webp); $createdWebp++; }
                }
                $alt=omurga_auto_image_alt(trim($_POST['alt_text']??''), trim($_POST['title_hint']??''), $finalPath);
                insert_media_record($finalPath, $alt, $_SESSION['omurga_user_id'] ?? null, $originalPath===$finalPath ? null : $originalPath);
                $ok++;
            }
            $msg=$ok.' dosya yüklendi'.($createdWebp?' · '.$createdWebp.' WebP üretildi.':'').($skipped?' · '.$skipped.' dosya atlandı.':'');
        }
    }catch(Throwable $e){ $err=$e->getMessage(); }
}

$q=trim($_GET['q']??'');
$type=trim($_GET['type']??'');
$usage=trim($_GET['usage']??'');
$month=trim($_GET['month']??'');
$view=($_GET['view']??'grid')==='list'?'list':'grid';
$order=media_safe_order_v34($_GET['order']??'created_at');
$page=max(1,(int)($_GET['page']??1)); $per=max(24,min(120,(int)($_GET['per']??60))); $offset=($page-1)*$per;
$where=[]; $params=[];
if($q!==''){ $where[]='(file_name LIKE ? OR alt_text LIKE ? OR file_path LIKE ?'.(media_has_col_v34('title_text')?' OR title_text LIKE ?':'').')'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; if(media_has_col_v34('title_text')) $params[]='%'.$q.'%'; }
if($type==='image') $where[]="mime LIKE 'image/%'";
elseif($type==='webp') $where[]="mime='image/webp'";
elseif($type==='pdf') $where[]="mime='application/pdf'";
elseif($type==='video') $where[]="mime LIKE 'video/%'";
elseif($type==='other') $where[]="(mime NOT LIKE 'image/%' AND mime NOT LIKE 'video/%' AND mime<>'application/pdf')";
if($month!=='' && preg_match('/^\d{4}-\d{2}$/',$month)){ $where[]='DATE_FORMAT(created_at, "%Y-%m")=?'; $params[]=$month; }
$sqlWhere=$where ? 'WHERE '.implode(' AND ',$where) : '';
$countSt=db()->prepare("SELECT COUNT(*) FROM $t $sqlWhere"); $countSt->execute($params); $total=(int)$countSt->fetchColumn();
$st=db()->prepare("SELECT * FROM $t $sqlWhere ORDER BY $order DESC, id DESC LIMIT $per OFFSET $offset"); $st->execute($params); $media=$st->fetchAll();
if($usage==='unused'){ $media=array_values(array_filter($media, fn($m)=>media_usage_v34($m['file_path']??'')['count']===0)); }
$months=[]; try{$months=db()->query("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') m FROM $t ORDER BY m DESC LIMIT 36")->fetchAll(PDO::FETCH_COLUMN);}catch(Throwable $e){}
$queryBase=$_GET; unset($queryBase['page']);
function media_qs_v34(array $extra=[]): string { global $queryBase; return http_build_query(array_merge($queryBase,$extra)); }
?>
<div class="toolbar"><h1>Medya Kütüphanesi</h1><div><a class="btn light" href="media-webp.php">WebP Dönüştür</a><a class="btn light" href="media-unused.php">Kullanılmayan Dosyalar</a><a class="btn light" href="settings.php#media">Medya Ayarları</a></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?><?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<div class="media-v34-shell">
  <section class="media-v34-main">
    <div class="card media-v34-uploader"><h2>Dosya Yükle</h2><p style="color:var(--muted)">Toplu yükleme desteklenir. Görseller küçültülebilir, JPG/PNG isteğe bağlı WebP olarak kaydedilir. PDF ve MP4 dosyaları da kütüphaneye alınabilir.</p><form method="post" enctype="multipart/form-data" class="media-v34-upload-form"><?=csrf_field()?><input type="hidden" name="action" value="upload"><label>Dosyalar<input type="file" name="files[]" accept="image/*,.pdf,video/mp4" multiple required></label><label>Dosya adı ipucu<input name="title_hint" placeholder="Örn: kurum-tanitimi veya site-gorseli"></label><label>Alt metin<input name="alt_text" placeholder="Görseli açıklayan kısa metin"></label><label class="check-line"><input type="checkbox" name="make_webp" value="1" checked> JPG/PNG için WebP oluştur</label><button class="btn primary">Yükle</button></form></div>

    <div class="card media-v34-filter"><form method="get" class="media-v34-filter-grid"><label>Arama<input name="q" value="<?=e($q)?>" placeholder="Dosya adı, alt metin, yol"></label><label>Tür<select name="type"><option value="">Tümü</option><option value="image" <?=$type==='image'?'selected':''?>>Görseller</option><option value="webp" <?=$type==='webp'?'selected':''?>>WebP</option><option value="pdf" <?=$type==='pdf'?'selected':''?>>PDF</option><option value="video" <?=$type==='video'?'selected':''?>>Video</option><option value="other" <?=$type==='other'?'selected':''?>>Diğer</option></select></label><label>Tarih<select name="month"><option value="">Tüm tarihler</option><?php foreach($months as $m): ?><option value="<?=e($m)?>" <?=$month===$m?'selected':''?>><?=e($m)?></option><?php endforeach; ?></select></label><label>Kullanım<select name="usage"><option value="">Tümü</option><option value="unused" <?=$usage==='unused'?'selected':''?>>Bu sayfada kullanılmayan</option></select></label><label>Sıralama<select name="order"><option value="created_at" <?=$order==='created_at'?'selected':''?>>Yeni eklenen</option><option value="file_name" <?=$order==='file_name'?'selected':''?>>Dosya adı</option><option value="file_size" <?=$order==='file_size'?'selected':''?>>Boyut</option><option value="mime" <?=$order==='mime'?'selected':''?>>Tür</option><option value="width" <?=$order==='width'?'selected':''?>>Ölçü</option></select></label><label>Görünüm<select name="view"><option value="grid" <?=$view==='grid'?'selected':''?>>Grid</option><option value="list" <?=$view==='list'?'selected':''?>>Liste</option></select></label><button class="btn primary">Filtrele</button><a class="btn light" href="media.php">Temizle</a></form></div>

    <form method="post" id="mediaBulkForm"><?=csrf_field()?><input type="hidden" name="action" value="bulk"></form><div class="card media-v34-list-card"><div class="media-v34-list-head"><div><strong><?=number_format($total)?> dosya</strong><small> · sayfa <?=$page?> · gösterilen <?=count($media)?></small></div><div class="media-v34-bulk"><select name="bulk_action" form="mediaBulkForm"><option value="">Toplu işlem</option><option value="webp">Seçilileri WebP yap</option><option value="delete">Seçilileri sil</option></select><button class="btn light" form="mediaBulkForm" onclick="return confirm('Toplu işlem uygulansın mı?')">Uygula</button></div></div>
      <?php if(!$media): ?><div class="empty-state">Bu filtreye uygun medya bulunamadı.</div><?php endif; ?>
      <div class="media-v34-<?=$view?>">
        <?php foreach($media as $m): $path=(string)($m['file_path']??''); $group=media_type_group_v34($m); $usageInfo=media_usage_v34($path); $isImage=$group==='image'; ?>
        <article class="media-v34-item" data-id="<?=$m['id']?>"><label class="media-v34-check"><input type="checkbox" form="mediaBulkForm" name="ids[]" value="<?=$m['id']?>"></label><div class="media-v34-thumb"><?php if($isImage): ?><img src="../<?=e($path)?>" alt="<?=e($m['alt_text']??'')?>"><?php else: ?><span><?=e(strtoupper($group))?></span><?php endif; ?></div><div class="media-v34-info"><strong><?=e($m['file_name'])?></strong><small><?=e($path)?></small><div class="media-v34-meta"><span><?=e($m['mime']??'-')?></span><?php if(isset($m['width'])): ?><span><?=e(($m['width']?:'?').'×'.($m['height']?:'?'))?></span><?php endif; ?><span><?=isset($m['file_size'])?e(omurga_human_size((int)$m['file_size'])):'-'?></span><span><?=e(date('d.m.Y', strtotime($m['created_at'] ?? 'now')))?></span></div><div class="media-v34-usage"><b><?=$usageInfo['count'] ? 'Kullanılıyor' : 'Kullanılmıyor'?></b><?php if($usageInfo['labels']): ?><small><?=e(implode(' | ', $usageInfo['labels']))?></small><?php endif; ?></div><input onclick="this.select()" value="<?=e($path)?>" readonly></div><details class="media-v34-details"><summary>Detay</summary><form method="post" class="media-v34-meta-form"><?=csrf_field()?><input type="hidden" name="action" value="update_meta"><input type="hidden" name="id" value="<?=$m['id']?>"><label>Başlık<input name="title_text" value="<?=e($m['title_text']??'')?>"></label><label>Alt metin<input name="alt_text" value="<?=e($m['alt_text']??'')?>"></label><label>Açıklama<textarea name="description"><?=e($m['description']??'')?></textarea></label><button class="btn light">Bilgileri Kaydet</button></form><form method="post" onsubmit="return confirm('Bu dosya silinsin mi?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$m['id']?>"><button class="btn danger">Sil</button></form></details></article>
        <?php endforeach; ?>
      </div>
      <div class="media-v34-pager"><?php if($page>1): ?><a class="btn light" href="?<?=e(media_qs_v34(['page'=>$page-1]))?>">Önceki</a><?php endif; ?><?php if($offset+$per<$total): ?><a class="btn primary" href="?<?=e(media_qs_v34(['page'=>$page+1]))?>">Daha Fazla Yükle</a><?php endif; ?></div>
    </div>
  </section>
  <aside class="media-v34-side"><div class="card"><h3>Hızlı Bilgi</h3><p>Çok görsel olduğunda arama, tür, tarih ve kullanım filtresiyle listeyi daralt. Varsayılan gösterim 60 dosyadır.</p><ul><li>Grid: görsel seçimi için hızlıdır.</li><li>Liste: dosya yolu ve kullanım kontrolü için uygundur.</li><li>Kullanılmayan taraması silmeden önce kontrol amaçlıdır.</li></ul></div><div class="card"><h3>Seçici Uyumu</h3><p>Yazı ekleme ekranındaki öne çıkan görsel ve galeri seçici bu medya yapısıyla uyumludur.</p></div></aside>
</div>
<?php require '_footer.php'; ?>
