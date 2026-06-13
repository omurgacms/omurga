<?php
require_once __DIR__.'/_layout.php';
require_cap('media.manage');

omurga_media_jobs_ensure_table();
$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!csrf_check($_POST['_csrf'] ?? '')){ $err='Güvenlik doğrulaması başarısız.'; }
    else{
        $action=(string)($_POST['action'] ?? '');
        try{
            if($action==='process_pending'){
                $limit=max(1,min(50,(int)($_POST['limit'] ?? setting('media_jobs_batch_limit','10'))));
                $r=omurga_media_jobs_process_pending($limit);
                $msg=$r['processed'].' iş işlendi. Başarılı: '.$r['ok'].' · Hatalı: '.$r['failed'];
                if(!empty($r['messages'])) $err=implode(' | ', array_slice(array_unique($r['messages']),0,3));
            }elseif($action==='retry'){
                $id=(int)($_POST['id'] ?? 0); $t=omurga_media_jobs_table();
                db()->prepare("UPDATE $t SET status='pending', error_message=NULL, updated_at=NOW() WHERE id=?")->execute([$id]);
                $msg='İş tekrar kuyruğa alındı.';
            }elseif($action==='process_one'){
                $id=(int)($_POST['id'] ?? 0); $t=omurga_media_jobs_table();
                $st=db()->prepare("SELECT * FROM $t WHERE id=? LIMIT 1"); $st->execute([$id]); $job=$st->fetch();
                if(!$job) throw new RuntimeException('İş bulunamadı.');
                $r=omurga_media_job_process($job);
                if($r['ok']??false) $msg='İş işlendi.'; else $err=$r['message'] ?? 'İşlenemedi.';
            }elseif($action==='clear_done'){
                $t=omurga_media_jobs_table(); db()->exec("DELETE FROM $t WHERE status='done'"); $msg='Tamamlanan işler temizlendi.';
            }elseif($action==='save_settings'){
                update_setting('media_jobs_enabled', !empty($_POST['media_jobs_enabled'])?'1':'0');
                update_setting('media_jobs_webp_enabled', !empty($_POST['media_jobs_webp_enabled'])?'1':'0');
                update_setting('media_jobs_thumbnail_enabled', !empty($_POST['media_jobs_thumbnail_enabled'])?'1':'0');
                update_setting('media_jobs_batch_limit', (string)max(1,min(50,(int)($_POST['media_jobs_batch_limit'] ?? 10))));
                $msg='Medya iş ayarları kaydedildi.';
            }
        }catch(Throwable $e){ omurga_write_error($e); $err=$e->getMessage(); }
    }
}

$t=omurga_media_jobs_table();
$stats=['total'=>0,'pending'=>0,'running'=>0,'done'=>0,'failed'=>0];
try{
    foreach(db()->query("SELECT status, COUNT(*) c FROM $t GROUP BY status")->fetchAll() as $r){ $stats[$r['status']] = (int)$r['c']; $stats['total'] += (int)$r['c']; }
}catch(Throwable $e){}
$status=trim((string)($_GET['status'] ?? ''));
$allowedStatus=['pending','running','done','failed'];
$where=''; $params=[];
if(in_array($status,$allowedStatus,true)){ $where='WHERE j.status=?'; $params[]=$status; }
$sql="SELECT j.*, m.file_path, m.file_name, m.mime, m.width, m.height FROM $t j LEFT JOIN ".table_name('media')." m ON m.id=j.media_id $where ORDER BY j.id DESC LIMIT 200";
$st=db()->prepare($sql); $st->execute($params); $jobs=$st->fetchAll();
function om_job_badge(string $s): string { $map=['pending'=>'Bekliyor','running'=>'İşleniyor','done'=>'Tamamlandı','failed'=>'Hata']; $cls=$s==='done'?'ok':($s==='failed'?'bad':($s==='running'?'warn':'muted')); return '<span class="badge '.$cls.'">'.e($map[$s] ?? $s).'</span>'; }
?>
<div class="toolbar"><h1>Medya İşleri</h1><div><a class="btn light" href="media.php">Medya Kütüphanesi</a><a class="btn light" href="media-webp.php">WebP Dönüştür</a></div></div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert danger"><?=e($err)?></div><?php endif; ?>

<div class="stats-grid compact">
  <div class="stat-card"><b><?=number_format($stats['total'])?></b><span>Toplam iş</span></div>
  <div class="stat-card"><b><?=number_format($stats['pending'])?></b><span>Bekleyen</span></div>
  <div class="stat-card"><b><?=number_format($stats['done'])?></b><span>Tamamlanan</span></div>
  <div class="stat-card"><b><?=number_format($stats['failed'])?></b><span>Hatalı</span></div>
</div>

<div class="card compact-card">
  <h2>İşleme Kuyruğu</h2>
  <p class="muted">Büyük görsellerde WebP, küçük görsel ve SEO işlemleri yükleme anında değil, kuyruk üzerinden kontrollü yapılır.</p>
  <form method="post" class="inline-form"><?=csrf_field()?><input type="hidden" name="action" value="process_pending"><label>İş adedi <input type="number" name="limit" min="1" max="50" value="<?=e(setting('media_jobs_batch_limit','10'))?>"></label><button class="btn primary">Bekleyenleri İşle</button><a class="btn light" href="?status=pending">Bekleyenler</a><a class="btn light" href="?status=failed">Hatalılar</a><a class="btn light" href="media-jobs.php">Tümü</a></form>
</div>

<div class="card compact-card">
  <details>
    <summary><strong>Ayarlar</strong></summary>
    <form method="post" class="form-grid" style="margin-top:12px"><?=csrf_field()?><input type="hidden" name="action" value="save_settings">
      <label class="check-line"><input type="checkbox" name="media_jobs_enabled" value="1" <?=setting('media_jobs_enabled','1')==='1'?'checked':''?>> Medya işleme kuyruğu aktif</label>
      <label class="check-line"><input type="checkbox" name="media_jobs_webp_enabled" value="1" <?=setting('media_jobs_webp_enabled','1')==='1'?'checked':''?>> WebP dönüşümü kuyruğa alınsın</label>
      <label class="check-line"><input type="checkbox" name="media_jobs_thumbnail_enabled" value="1" <?=setting('media_jobs_thumbnail_enabled','1')==='1'?'checked':''?>> Küçük görsel üretimi kuyruğa alınsın</label>
      <label>Varsayılan batch limiti<input type="number" min="1" max="50" name="media_jobs_batch_limit" value="<?=e(setting('media_jobs_batch_limit','10'))?>"></label>
      <button class="btn primary">Ayarları Kaydet</button>
    </form>
  </details>
</div>

<div class="card compact-card">
  <div class="media-v34-list-head"><strong>Son 200 medya işi</strong><form method="post" onsubmit="return confirm('Tamamlanan işler temizlensin mi?')"><?=csrf_field()?><input type="hidden" name="action" value="clear_done"><button class="btn light">Tamamlananları Temizle</button></form></div>
  <?php if(!$jobs): ?><div class="empty-state">Henüz medya işi yok.</div><?php endif; ?>
  <div class="compact-list">
    <?php foreach($jobs as $j): $path=(string)($j['file_path'] ?? ''); ?>
      <article class="compact-row">
        <div class="compact-thumb"><?php if($path && str_starts_with((string)($j['mime']??''),'image/')): ?><img src="../<?=e($path)?>" alt=""><?php else: ?><span>JOB</span><?php endif; ?></div>
        <div class="compact-main"><strong><?=e($j['job_type'])?></strong><small><?=e($j['file_name'] ?: $path ?: ('Medya #'.$j['media_id']))?></small><div class="muted">Deneme: <?=e((string)$j['attempts'])?> · Oluşturma: <?=e((string)$j['created_at'])?><?=!empty($j['processed_at'])?' · İşlendi: '.e($j['processed_at']):''?></div><?php if(!empty($j['error_message'])): ?><div class="alert danger" style="margin-top:8px"><?=e($j['error_message'])?></div><?php endif; ?></div>
        <div class="compact-actions"><?=om_job_badge((string)$j['status'])?>
          <?php if(($j['status']??'')!=='done'): ?><form method="post" style="display:inline"><?=csrf_field()?><input type="hidden" name="action" value="process_one"><input type="hidden" name="id" value="<?=e($j['id'])?>"><button class="btn light">İşle</button></form><?php endif; ?>
          <?php if(($j['status']??'')==='failed'): ?><form method="post" style="display:inline"><?=csrf_field()?><input type="hidden" name="action" value="retry"><input type="hidden" name="id" value="<?=e($j['id'])?>"><button class="btn light">Tekrar Dene</button></form><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__.'/_footer.php'; ?>
