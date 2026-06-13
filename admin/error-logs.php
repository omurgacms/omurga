<?php
require_once __DIR__.'/_layout.php';
require_capability('system.manage');
$logFile = OMURGA_ROOT.'/storage/logs/error.log';
$lines=[];
if(file_exists($logFile)){
    $raw=file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lines=array_slice(array_reverse($raw), 0, 200);
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['clear'])){
    verify_csrf();
    @file_put_contents($logFile, '');
    log_activity('system.error_log_clear','Hata kayıtları temizlendi.');
    redirect('admin/error-logs.php?cleared=1');
}
?>
<div class="page-head compact"><div><h1>Hata Kayıtları</h1><p>Production ortamında kullanıcıya gösterilmeyen PHP hata ve exception kayıtları.</p></div><form method="post" onsubmit="return confirm('Hata kayıtları temizlensin mi?')"><?=csrf_field()?><button class="btn danger" name="clear" value="1">Temizle</button></form></div>
<?php if(isset($_GET['cleared'])): ?><div class="alert success">Hata kayıtları temizlendi.</div><?php endif; ?>
<div class="card compact-card">
  <h2>Son 200 kayıt</h2>
  <?php if(!$lines): ?><p class="muted">Henüz hata kaydı yok.</p><?php else: ?>
    <div class="om-log-list">
    <?php foreach($lines as $line): $json=json_decode($line,true); ?>
      <details class="om-log-row"><summary><?php if(is_array($json)): ?><b><?=e($json['level'] ?? 'log')?></b> <?=e($json['message'] ?? '')?> <small><?=e($json['time'] ?? '')?></small><?php else: ?><?=e(mb_substr($line,0,180))?><?php endif; ?></summary><pre><?=e(is_array($json)?json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT):$line)?></pre></details>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__.'/_footer.php'; ?>
