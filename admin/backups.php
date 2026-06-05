<?php
require '_layout.php';
require_cap('users.manage');
omurga_migrate();

$msg=''; $err='';
if(isset($_GET['download'])){
    try{
        $path=omurga_backup_path_from_name((string)$_GET['download']);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Content-Length: '.filesize($path));
        readfile($path); exit;
    }catch(Throwable $e){ $err=$e->getMessage(); }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action'] ?? '';
    try{
        if($action==='settings'){
            $max=(int)($_POST['backup_max_count'] ?? 30);
            if($max<5) $max=5;
            if($max>200) $max=200;
            update_setting('backup_max_count',(string)$max);
            omurga_cleanup_old_backups($max);
            log_activity('backup.settings','Yedek liste limiti güncellendi: '.$max);
            $msg='Yedek limiti kaydedildi. En fazla '.$max.' yedek listede tutulacak.';
        }
        if($action==='db'){
            create_database_backup();
            $msg='Veritabanı yedeği alındı.';
        }
        if($action==='uploads'){
            $f=create_uploads_backup();
            $msg=$f?'Dosya yedeği alındı.':'ZipArchive desteği yok. Hosting’de zip desteği açılmalı.';
        }
        if($action==='full'){
            create_database_backup();
            $f=create_uploads_backup();
            $msg=$f?'Tam yedek alındı.':'Veritabanı yedeği alındı, dosya yedeği için ZipArchive desteği yok.';
        }
        if($action==='delete'){
            omurga_delete_backup((string)($_POST['file'] ?? ''));
            $msg='Yedek silindi.';
        }
        if($action==='restore_db'){
            omurga_restore_database_backup((string)($_POST['file'] ?? ''));
            $msg='Veritabanı yedeği geri yüklendi. İşlem öncesi otomatik SQL yedeği alındı.';
        }
        if($action==='restore_uploads'){
            omurga_restore_uploads_backup((string)($_POST['file'] ?? ''));
            $msg='Dosya yedeği geri yüklendi. İşlem öncesi otomatik dosya yedeği alındı.';
        }
    }catch(Throwable $e){ omurga_write_error($e); $err='Yedekleme hatası: '.$e->getMessage(); }
}

$max=omurga_backup_max_count();
$rows=[];
try{ $rows=db()->query('SELECT * FROM '.table_name('backups').' ORDER BY id DESC LIMIT '.(int)$max)->fetchAll(); }catch(Throwable $e){ omurga_write_error($e); }
$total=0; try{ $total=(int)db()->query('SELECT COUNT(*) FROM '.table_name('backups'))->fetchColumn(); }catch(Throwable $e){}
$backupSize=omurga_directory_size(backup_dir());
?>
<div class="toolbar"><h1>Yedekleme</h1><a class="btn light" href="rollback.php">Rollback / Geri Dön</a></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<div class="grid-3">
  <div class="card"><h2>Veritabanı Yedeği</h2><p>İçerikler, ayarlar, kullanıcılar ve kayıtlar için SQL yedeği oluşturur.</p><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button class="btn primary" name="action" value="db">SQL Yedeği Al</button></form></div>
  <div class="card"><h2>Dosya Yedeği</h2><p><code>uploads</code> klasörünü ZIP olarak paketler. Sunucuda ZipArchive gerekir.</p><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button class="btn dark" name="action" value="uploads">Dosya Yedeği Al</button></form></div>
  <div class="card"><h2>Tam Yedek</h2><p>Veritabanı ve yüklenen dosyaları birlikte yedekler. Eski yedekler limite göre temizlenir.</p><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button class="btn dark" name="action" value="full">Tam Yedek Al</button></form></div>
</div>

<div class="card" style="margin-top:18px">
  <h2>Yedek Limiti</h2>
  <p>Sunucunun şişmemesi için yedek listesi sınırlı tutulur. Varsayılan limit 30’dur. Limit aşılırsa en eski yedekler hem listeden hem dosyadan silinir.</p>
  <form method="post" class="inline-form">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="settings">
    <label>Listede tutulacak maksimum yedek <input type="number" name="backup_max_count" min="5" max="200" value="<?=e((string)$max)?>"></label>
    <button class="btn primary">Kaydet</button>
  </form>
  <p class="muted">Toplam yedek boyutu: <b><?=e(omurga_format_bytes($backupSize))?></b> · Kayıt sayısı: <b><?=e((string)$total)?></b></p>
</div>

<div class="card" style="margin-top:18px"><h2>Son Yedekler</h2>
<table class="table"><thead><tr><th>Tür</th><th>Dosya</th><th>Boyut</th><th>Tarih</th><th>İndir</th><th>Geri Yükle</th><th>Sil</th></tr></thead><tbody>
<?php foreach($rows as $r): $file=basename($r['file_path']); $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION)); ?>
<tr>
  <td><?=e($r['backup_type'])?></td>
  <td><?=e($file)?></td>
  <td><?=e(omurga_format_bytes((int)$r['file_size']))?></td>
  <td><?=e($r['created_at'])?></td>
  <td><a class="btn light" href="backups.php?download=<?=urlencode($file)?>">İndir</a></td>
  <td>
    <?php if($ext==='sql'): ?>
      <form method="post" onsubmit="return confirm('SQL yedeği geri yüklenecek. Mevcut veritabanının otomatik yedeği alınır. Devam edilsin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="restore_db"><input type="hidden" name="file" value="<?=e($file)?>"><button class="btn light">SQL Geri Yükle</button></form>
    <?php elseif($ext==='zip'): ?>
      <form method="post" onsubmit="return confirm('Dosya yedeği geri yüklenecek. uploads klasörü üzerine yazılabilir. Devam edilsin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="restore_uploads"><input type="hidden" name="file" value="<?=e($file)?>"><button class="btn light">Dosya Geri Yükle</button></form>
    <?php endif; ?>
  </td>
  <td><form method="post" onsubmit="return confirm('Bu yedek silinsin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?=e($file)?>"><button class="btn danger">Sil</button></form></td>
</tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="7">Henüz yedek yok.</td></tr><?php endif; ?>
</tbody></table>
</div>
<?php require '_footer.php'; ?>
