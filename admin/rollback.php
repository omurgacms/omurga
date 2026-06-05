<?php
require '_layout.php';
require_cap('users.manage');
omurga_migrate();

$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action'] ?? '';
    try{
        if($action==='restore'){
            $result=omurga_restore_extension_backup((string)($_POST['file'] ?? ''), !empty($_POST['backup_current']));
            $msg=ucfirst($result['type']).' geri döndürüldü: '.$result['slug'].(!empty($result['current_backup'])?' Mevcut sürüm ayrıca yedeklendi.':'');
        }
        if($action==='delete'){
            omurga_delete_rollback_backup((string)($_POST['file'] ?? ''));
            $msg='Rollback yedeği silindi.';
        }
        if($action==='settings'){
            update_setting('auto_backup_before_extension_update', !empty($_POST['auto_backup_before_extension_update']) ? '1' : '0');
            $msg='Rollback ayarları kaydedildi.';
        }
    }catch(Throwable $e){ omurga_write_error($e); $err='Rollback hatası: '.$e->getMessage(); }
}
$type=$_GET['type'] ?? '';
if(!in_array($type,['theme','package'],true)) $type='';
$rows=omurga_list_rollbacks($type ?: null);
?>
<div class="toolbar"><h1>Rollback / Geri Dön</h1></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<section class="card">
  <h2>Rollback Mantığı</h2>
  <p class="muted">Tema veya paket güncellenmeden, yeniden kurulmadan, sürüm düşürülmeden ya da silinmeden önce alınan yedekler burada görünür. Bir sorun çıkarsa eski sürüme dönebilirsin.</p>
  <div class="alert pending"><b>Güvenlik:</b> Aktif tema doğrudan geri alınmaz. Önce başka bir tema etkinleştirilmeli. Paket geri alınırken aktifse önce devre dışı bırakılır.</div>
</section>

<section class="card">
  <h2>Ayarlar</h2>
  <form method="post" class="inline-form">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="settings">
    <label><input type="checkbox" name="auto_backup_before_extension_update" value="1" <?=omurga_should_auto_backup_before_update()?'checked':''?>> Tema/paket güncellemeden önce otomatik yedek al</label>
    <button class="btn primary">Kaydet</button>
  </form>
</section>

<section class="card">
  <div class="toolbar"><div><h2>Geri Dönülebilir Yedekler</h2><p class="muted">Filtre: <a href="rollback.php">Tümü</a> · <a href="rollback.php?type=theme">Temalar</a> · <a href="rollback.php?type=package">Paketler</a></p></div><span class="badge published"><?=count($rows)?> yedek</span></div>
  <table class="table content-table">
    <thead><tr><th>Tür</th><th>Slug</th><th>Sürüm</th><th>Dosya</th><th>Boyut</th><th>Tarih</th><th>İşlem</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td data-label="Tür"><span class="badge"><?=e($r['type']==='theme'?'Tema':'Paket')?></span></td>
        <td data-label="Slug"><strong><?=e($r['slug'])?></strong></td>
        <td data-label="Sürüm"><?=e($r['version'] ?: '-')?></td>
        <td data-label="Dosya"><code><?=e($r['file'])?></code></td>
        <td data-label="Boyut"><?=e(omurga_format_bytes((int)$r['size']))?></td>
        <td data-label="Tarih"><?=e($r['modified']?date('Y-m-d H:i:s',(int)$r['modified']):'-')?></td>
        <td data-label="İşlem">
          <form method="post" style="display:inline" onsubmit="return confirm('Bu yedeğe geri dönülsün mü? Mevcut sürüm yedeklenebilir.');">
            <input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="file" value="<?=e($r['file'])?>">
            <label class="muted"><input type="checkbox" name="backup_current" value="1" checked> Mevcut sürümü yedekle</label>
            <button class="btn primary">Geri Dön</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Bu rollback yedeği silinsin mi?');">
            <input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?=e($r['file'])?>">
            <button class="btn danger">Sil</button>
          </form>
        </td>
      </tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="7">Henüz rollback yedeği yok. Tema/paket güncellemesi veya silme işlemi yaptığında burada listelenecek.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php require '_footer.php'; ?>
