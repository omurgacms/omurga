<?php
require '_layout.php'; require_cap('users.manage'); omurga_migrate();
$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); $action=$_POST['action'] ?? '';
    try{
        if($action==='restore'){
            $result=omurga_restore_extension_backup((string)($_POST['file'] ?? ''), !empty($_POST['backup_current']));
            $msg=ucfirst($result['type']).' geri döndürüldü: '.$result['slug'].(!empty($result['current_backup'])?' Mevcut sürüm ayrıca yedeklendi.':'');
        }
        if($action==='delete'){ omurga_delete_rollback_backup((string)($_POST['file'] ?? '')); $msg='Rollback yedeği silindi.'; }
        if($action==='settings'){ update_setting('auto_backup_before_extension_update', !empty($_POST['auto_backup_before_extension_update']) ? '1' : '0'); $msg='Rollback ayarları kaydedildi.'; }
    }catch(Throwable $e){ omurga_write_error($e); $err='Rollback hatası: '.$e->getMessage(); }
}
$type=$_GET['type'] ?? ''; if(!in_array($type,['theme','package'],true)) $type=''; $rows=omurga_list_rollbacks($type ?: null);
$themeCount=0; $packageCount=0; $totalSize=0; foreach($rows as $r){ if(($r['type'] ?? '')==='theme') $themeCount++; else $packageCount++; $totalSize += (int)($r['size'] ?? 0); }
?>
<div class="page-head compact-head"><div><h1>Geri Dön</h1><p>Tema ve paket yedeklerinden eski sürüme dönüş.</p></div><div class="compact-actions"><a class="btn" href="packages.php">Paketler</a><a class="btn" href="themes.php">Temalar</a></div></div>
<?php if($msg): ?><div class="notice success compact-notice"><?=e($msg)?></div><?php endif; ?><?php if($err): ?><div class="notice danger compact-notice"><?=e($err)?></div><?php endif; ?>
<div class="omg-summary-strip compact-summary"><span><b><?=count($rows)?></b> yedek</span><span><b><?=e((string)$themeCount)?></b> tema</span><span><b><?=e((string)$packageCount)?></b> paket</span><span><b><?=e(omurga_format_bytes($totalSize))?></b> toplam</span></div>
<section class="compact-panel"><div class="compact-panel-head"><h2>Ayarlar</h2><span class="muted">Güncellemeden önce otomatik yedek</span></div><form method="post" class="compact-inline"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="settings"><label><input type="checkbox" name="auto_backup_before_extension_update" value="1" <?=omurga_should_auto_backup_before_update()?'checked':''?>> Tema/paket güncellemeden önce yedek al</label><button class="btn primary">Kaydet</button></form></section>
<section class="compact-panel"><div class="compact-panel-head"><div><h2>Yedekler</h2><p class="muted"><a href="rollback.php">Tümü</a> · <a href="rollback.php?type=theme">Temalar</a> · <a href="rollback.php?type=package">Paketler</a></p></div><span class="badge published"><?=count($rows)?> yedek</span></div><div class="compact-rollback-list">
<?php foreach($rows as $r): ?>
  <article class="compact-rollback-row"><div class="rollback-type"><span class="badge"><?=e($r['type']==='theme'?'Tema':'Paket')?></span></div><div class="rollback-main"><strong><?=e($r['slug'])?></strong><span><?=e($r['version'] ?: '-')?> · <?=e(omurga_format_bytes((int)$r['size']))?> · <?=e($r['modified']?date('Y-m-d H:i',(int)$r['modified']):'-')?></span><small><?=e($r['file'])?></small></div><div class="rollback-actions"><form method="post" onsubmit="return confirm('Bu yedeğe geri dönülsün mü?');"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="file" value="<?=e($r['file'])?>"><label class="muted"><input type="checkbox" name="backup_current" value="1" checked> Mevcut sürümü yedekle</label><button class="btn primary">Geri Dön</button></form><form method="post" onsubmit="return confirm('Bu rollback yedeği silinsin mi?');"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?=e($r['file'])?>"><button class="btn danger">Sil</button></form></div></article>
<?php endforeach; if(!$rows): ?><div class="compact-empty">Henüz rollback yedeği yok. Tema/paket güncellemesi veya silme işlemi yaptığında burada listelenecek.</div><?php endif; ?>
</div></section>
<?php require '_footer.php'; ?>
