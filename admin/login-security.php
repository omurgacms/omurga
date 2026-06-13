<?php
require_once __DIR__.'/_layout.php';
require_capability('users.manage');
omurga_migrate();
$loginT = table_name('login_attempts');
$rows=[]; $stats=['total'=>0,'failed'=>0,'locked'=>0,'success'=>0];
try{
    $stats['total']=(int)db()->query("SELECT COUNT(*) c FROM $loginT")->fetch()['c'];
    $stats['failed']=(int)db()->query("SELECT COUNT(*) c FROM $loginT WHERE success=0 AND created_at>DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['c'];
    $stats['success']=(int)db()->query("SELECT COUNT(*) c FROM $loginT WHERE success=1 AND created_at>DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['c'];
    $stats['locked']=(int)db()->query("SELECT COUNT(*) c FROM $loginT WHERE locked_until IS NOT NULL AND locked_until>NOW()")->fetch()['c'];
    $rows=db()->query("SELECT * FROM $loginT ORDER BY id DESC LIMIT 100")->fetchAll();
}catch(Throwable $e){ $err=$e->getMessage(); }
?>
<div class="page-head compact"><div><h1>Giriş Güvenliği</h1><p>Başarılı/başarısız panel giriş denemeleri ve geçici kilit kayıtları.</p></div><a class="btn" href="security.php">Güvenlik</a></div>
<?php if(!empty($err)): ?><div class="alert error">Tablo okunamadı: <?=e($err)?></div><?php endif; ?>
<div class="mini-stats">
  <div><b><?=e($stats['total'])?></b><span>Toplam kayıt</span></div>
  <div><b><?=e($stats['success'])?></b><span>24s başarılı</span></div>
  <div><b><?=e($stats['failed'])?></b><span>24s hatalı</span></div>
  <div><b><?=e($stats['locked'])?></b><span>Aktif kilit</span></div>
</div>
<div class="card compact-card">
  <h2>Son giriş denemeleri</h2>
  <?php if(!$rows): ?><p class="muted">Henüz kayıt yok.</p><?php else: ?>
  <div class="table-wrap"><table class="table compact-table"><thead><tr><th>Zaman</th><th>Kullanıcı</th><th>IP</th><th>Durum</th><th>Kilit</th><th>Tarayıcı</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?><tr>
    <td><?=e($r['created_at'] ?? '')?></td>
    <td><?=e($r['username'] ?? '')?></td>
    <td><?=e($r['ip'] ?? '')?></td>
    <td><?=((int)$r['success']===1)?'<span class="badge success">Başarılı</span>':'<span class="badge danger">Hatalı</span>'?></td>
    <td><?=!empty($r['locked_until'])?'<span class="badge warning">'.e($r['locked_until']).'</span>':'-'?></td>
    <td><small><?=e(mb_substr((string)($r['user_agent'] ?? ''),0,80))?></small></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/_footer.php'; ?>
