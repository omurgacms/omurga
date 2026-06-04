<?php require '_layout.php'; require_cap('users.manage'); omurga_migrate();
$logsT=table_name('activity_logs'); $usersT=table_name('users');
$action=trim($_GET['action'] ?? ''); $module=trim($_GET['module'] ?? '');
$where=[]; $params=[];
if($action!==''){ $where[]='l.action LIKE ?'; $params[]='%'.$action.'%'; }
if($module!==''){ $where[]='l.module=?'; $params[]=$module; }
$sql="SELECT l.*, u.name user_name FROM $logsT l LEFT JOIN $usersT u ON u.id=l.user_id".($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY l.id DESC LIMIT 500";
$st=db()->prepare($sql); $st->execute($params); $logs=$st->fetchAll();
?>
<div class="toolbar"><h1>İşlem Kayıtları</h1></div>
<div class="card"><p>Panelde yapılan önemli işlemler burada tutulur. Kim ne yaptı, ne zaman yaptı ve hangi modülde yaptı burada izlenir.</p>
<form method="get" class="filters"><input name="action" value="<?=e($action)?>" placeholder="İşlem ara"><input name="module" value="<?=e($module)?>" placeholder="Modül"><button class="btn">Filtrele</button><a class="btn" href="logs.php">Temizle</a></form>
<table class="table"><thead><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th><th>Modül</th><th>Varlık</th><th>Açıklama</th><th>IP</th></tr></thead><tbody>
<?php foreach($logs as $l): ?><tr><td><?=e($l['created_at'])?></td><td><?=e($l['user_name'] ?: 'Sistem')?></td><td><span class="badge"><?=e($l['action'])?></span></td><td><?=e($l['module'] ?? '')?></td><td><?=e(($l['entity_type'] ?? '').(!empty($l['entity_id'])?' #'.$l['entity_id']:''))?></td><td><?=e($l['message'])?><?php if(!empty($l['details'])): ?><details><summary>Detay</summary><pre><?=e($l['details'])?></pre></details><?php endif; ?></td><td><?=e($l['ip'])?></td></tr><?php endforeach; ?>
<?php if(!$logs): ?><tr><td colspan="7">Kayıt bulunamadı.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require '_footer.php'; ?>
