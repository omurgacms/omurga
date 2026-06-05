<?php require_once dirname(__DIR__) . '/bootstrap.php'; require_admin(); require_cap('system.manage'); omurga_migrate();
$logsT=table_name('activity_logs'); $usersT=table_name('users');

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $do=$_POST['do'] ?? '';
    if($do==='settings'){
        $retention=(int)($_POST['retention_days'] ?? 90);
        if(!in_array($retention,[0,30,90,180,365],true)) $retention=90;
        update_setting('activity_log_retention_days',(string)$retention);
        if($retention>0) omurga_activity_purge_old($retention);
        log_activity('activity.settings','Aktivite kayıtları saklama süresi güncellendi: '.($retention===0?'Sınırsız':$retention.' gün'), null, 'system');
        redirect('admin/logs.php?saved=1');
    }
    if($do==='clear'){
        if(!omurga_is_super_admin()) die('Bu işlem için Süper Yönetici gerekir.');
        db()->exec("DELETE FROM $logsT");
        log_activity('activity.clear','Aktivite kayıtları temizlendi.', null, 'security');
        redirect('admin/logs.php?cleared=1');
    }
}

$module=trim($_GET['module'] ?? '');
$level=trim($_GET['level'] ?? '');
$q=trim($_GET['q'] ?? '');
$dateFrom=trim($_GET['from'] ?? '');
$dateTo=trim($_GET['to'] ?? '');
$export=trim($_GET['export'] ?? '');
$where=[]; $params=[];
$cols=db()->query("SHOW COLUMNS FROM $logsT")->fetchAll(PDO::FETCH_COLUMN);
$hasLevel=in_array('level',$cols,true);
$hasModule=in_array('module',$cols,true);
if($module!=='' && $hasModule){ $where[]='l.module=?'; $params[]=$module; }
if($level!=='' && $hasLevel){ $where[]='l.level=?'; $params[]=$level; }
if($q!==''){
    $where[]='(l.action LIKE ? OR l.message LIKE ? OR l.ip LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    for($i=0;$i<6;$i++) $params[]='%'.$q.'%';
}
if($dateFrom!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)){ $where[]='DATE(l.created_at)>=?'; $params[]=$dateFrom; }
if($dateTo!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)){ $where[]='DATE(l.created_at)<=?'; $params[]=$dateTo; }
$selectLevel=$hasLevel?'l.level':'NULL AS level';
$selectModule=$hasModule?'l.module':'NULL AS module';
$sql="SELECT l.*, $selectLevel, $selectModule, u.name user_name, u.username FROM $logsT l LEFT JOIN $usersT u ON u.id=l.user_id".($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY l.id DESC LIMIT 1000";
$st=db()->prepare($sql); $st->execute($params); $logs=$st->fetchAll();

if($export==='csv' || $export==='json'){
    $safeDate=date('Ymd-His');
    if($export==='json'){
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="omurga-activity-logs-'.$safeDate.'.json"');
        echo json_encode($logs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="omurga-activity-logs-'.$safeDate.'.csv"');
    $out=fopen('php://output','w');
    fputcsv($out,['Tarih','Seviye','Modül','Kullanıcı','İşlem','Varlık','Açıklama','IP']);
    foreach($logs as $l){
        fputcsv($out,[
            $l['created_at'] ?? '',
            $l['level'] ?? 'info',
            omurga_activity_module_label($l['module'] ?? ''),
            $l['user_name'] ?: ($l['username'] ?: 'Sistem'),
            omurga_activity_action_label($l['action'] ?? ''),
            trim(($l['entity_type'] ?? '').(!empty($l['entity_id'])?' #'.$l['entity_id']:'')),
            $l['message'] ?? '',
            $l['ip'] ?? '',
        ]);
    }
    fclose($out); exit;
}

$counts=omurga_activity_counts();
$retention=(int)setting('activity_log_retention_days','90');
$modules=omurga_activity_modules();
$levels=omurga_activity_levels();
$query=$_GET; unset($query['export']); $base='logs.php'.($query?'?'.http_build_query($query).'&':'?');
?>
<?php require '_layout.php'; ?>
<div class="toolbar"><h1>Aktivite Kayıtları</h1><div><a class="btn" href="<?=e($base)?>export=csv">CSV</a> <a class="btn" href="<?=e($base)?>export=json">JSON</a></div></div>
<?php if(isset($_GET['saved'])): ?><div class="alert success">Ayarlar kaydedildi.</div><?php endif; ?>
<?php if(isset($_GET['cleared'])): ?><div class="alert success">Aktivite kayıtları temizlendi.</div><?php endif; ?>
<div class="grid cols-4">
  <div class="card"><b>Toplam Kayıt</b><h2><?=e((string)($counts['total'] ?? 0))?></h2></div>
  <div class="card"><b>Bugün</b><h2><?=e((string)($counts['today'] ?? 0))?></h2></div>
  <div class="card"><b>Güvenlik</b><h2><?=e((string)($counts['security'] ?? 0))?></h2></div>
  <div class="card"><b>Hata/Uyarı</b><h2><?=e((string)($counts['warning'] ?? 0))?></h2></div>
</div>
<div class="card"><p>Omurga içinde yapılan önemli işlemler burada tutulur. Bu ekran kim, ne zaman, hangi bölümde, hangi işlemi yaptı sorusuna cevap verir.</p>
<form method="get" class="filters">
  <input name="q" value="<?=e($q)?>" placeholder="İşlem, kullanıcı, IP veya açıklama ara">
  <select name="module"><option value="">Tüm modüller</option><?php foreach($modules as $k=>$v): ?><option value="<?=e($k)?>" <?=$module===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select>
  <select name="level"><option value="">Tüm seviyeler</option><?php foreach($levels as $k=>$v): ?><option value="<?=e($k)?>" <?=$level===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select>
  <input type="date" name="from" value="<?=e($dateFrom)?>">
  <input type="date" name="to" value="<?=e($dateTo)?>">
  <button class="btn">Filtrele</button><a class="btn" href="logs.php">Temizle</a>
</form>
<table class="table"><thead><tr><th>Tarih</th><th>Seviye</th><th>Modül</th><th>Kullanıcı</th><th>İşlem</th><th>Varlık</th><th>Açıklama</th><th>IP</th></tr></thead><tbody>
<?php foreach($logs as $l): $lvl=$l['level'] ?: omurga_activity_level_for_action($l['action'] ?? ''); ?>
<tr><td><?=e($l['created_at'])?></td><td><span class="badge"><?=e(omurga_activity_level_label($lvl))?></span></td><td><?=e(omurga_activity_module_label($l['module'] ?? ''))?></td><td><?=e($l['user_name'] ?: ($l['username'] ?: 'Sistem'))?></td><td><span class="badge"><?=e(omurga_activity_action_label($l['action']))?></span><br><small><?=e($l['action'])?></small></td><td><?=e(($l['entity_type'] ?? '').(!empty($l['entity_id'])?' #'.$l['entity_id']:''))?></td><td><?=e($l['message'])?><?php if(!empty($l['details'])): ?><details><summary>Detay</summary><pre><?=e($l['details'])?></pre></details><?php endif; ?></td><td><?=e($l['ip'])?></td></tr>
<?php endforeach; ?>
<?php if(!$logs): ?><tr><td colspan="8">Kayıt bulunamadı.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="card"><h2>Saklama Politikası</h2><form method="post"><?=csrf_field()?><input type="hidden" name="do" value="settings"><label>Saklama süresi</label><select name="retention_days"><option value="30" <?=$retention===30?'selected':''?>>30 Gün</option><option value="90" <?=$retention===90?'selected':''?>>90 Gün</option><option value="180" <?=$retention===180?'selected':''?>>180 Gün</option><option value="365" <?=$retention===365?'selected':''?>>1 Yıl</option><option value="0" <?=$retention===0?'selected':''?>>Sınırsız</option></select> <button class="btn primary">Kaydet</button></form><?php if(omurga_is_super_admin()): ?><form method="post" onsubmit="return confirm('Tüm aktivite kayıtları silinsin mi? Bu işlem geri alınamaz.');" style="margin-top:12px"><?=csrf_field()?><input type="hidden" name="do" value="clear"><button class="btn danger">Tüm Kayıtları Temizle</button></form><?php endif; ?></div>
<?php require '_footer.php'; ?>
