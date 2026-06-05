<?php require '_layout.php'; require_cap('users.manage'); omurga_migrate(); verify_csrf();
$t=table_name('notifications');
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    if($action==='read_selected'){
        omurga_mark_notifications_read($_POST['ids'] ?? []);
        log_activity('notifications.read','Seçili bildirimler okundu olarak işaretlendi.', null, 'system');
        echo '<div class="alert success">Seçili bildirimler okundu yapıldı.</div>';
    } elseif($action==='read_all'){
        omurga_mark_notifications_read();
        log_activity('notifications.read_all','Tüm bildirimler okundu yapıldı.', null, 'system');
        echo '<div class="alert success">Tüm bildirimler okundu yapıldı.</div>';
    } elseif($action==='delete_selected'){
        $ids=array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
        if($ids){ db()->exec("DELETE FROM $t WHERE id IN (".implode(',',$ids).")"); log_activity('notifications.delete','Seçili bildirimler silindi.', null, 'system'); }
        echo '<div class="alert success">Seçili bildirimler silindi.</div>';
    } elseif($action==='test'){
        omurga_notify('Test bildirimi','Bildirim sistemi çalışıyor.', 'success', 'admin/notifications.php');
        log_activity('notifications.test','Test bildirimi oluşturuldu.', null, 'system');
        echo '<div class="alert success">Test bildirimi oluşturuldu.</div>';
    }
}
$filter=$_GET['filter'] ?? 'all';
$where='1=1';
if($filter==='unread') $where='is_read=0';
if($filter==='read') $where='is_read=1';
$rows=db()->query("SELECT * FROM $t WHERE $where ORDER BY id DESC LIMIT 200")->fetchAll();
$unread=omurga_unread_notification_count((int)($_SESSION['omurga_user_id'] ?? 0));
?>
<div class="toolbar"><h1>Bildirimler</h1><div><a class="btn" href="?filter=all">Tümü</a> <a class="btn" href="?filter=unread">Okunmamış</a> <a class="btn" href="?filter=read">Okunmuş</a></div></div>
<div class="card"><p><b><?=e((string)$unread)?></b> okunmamış bildirim var. Bildirimler sistem, güncelleme, yedekleme, içerik onayı ve paket işlemleri için kullanılır.</p>
<form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
<div class="actions"><button class="btn primary" name="action" value="read_selected">Seçilileri Okundu Yap</button> <button class="btn" name="action" value="read_all">Tümünü Okundu Yap</button> <button class="btn danger" name="action" value="delete_selected" onclick="return confirm('Seçili bildirimler silinsin mi?')">Seçilileri Sil</button> <button class="btn" name="action" value="test">Test Bildirimi Oluştur</button></div>
<table class="table"><thead><tr><th></th><th>Durum</th><th>Tür</th><th>Başlık</th><th>Mesaj</th><th>Bağlantı</th><th>Tarih</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr class="<?=empty($r['is_read'])?'row-strong':''?>"><td><input type="checkbox" name="ids[]" value="<?=e($r['id'])?>"></td><td><?=empty($r['is_read'])?'<span class="badge warn">Okunmadı</span>':'<span class="badge">Okundu</span>'?></td><td><span class="badge"><?=e($r['type'])?></span></td><td><b><?=e($r['title'])?></b></td><td><?=e($r['message'])?></td><td><?php if($r['link']): ?><a href="<?=e(omurga_url($r['link']))?>">Aç</a><?php endif; ?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="7">Bildirim bulunamadı.</td></tr><?php endif; ?>
</tbody></table></form></div>
<?php require '_footer.php'; ?>
