<?php
require '_layout.php';

$postsT=table_name('posts');
$catsT=table_name('categories');
$usersT=table_name('users');
$commentsT=table_name('comments');
$formsT=table_name('forms');
$logsT=table_name('activity_logs');

/**
 * Safe database query wrapper with comprehensive error handling
 * Prevents errors from crashing the dashboard
 */
$safeCount=function(string $sql, array $params=[]): int {
    try {
        $st=db()->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    } catch(Throwable $e) {
        error_log('Dashboard count query error: ' . $e->getMessage());
        omurga_write_error($e);
        return 0;
    }
};

$safeRows=function(string $sql, array $params=[]): array {
    try {
        $st=db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch(Throwable $e) {
        error_log('Dashboard rows query error: ' . $e->getMessage());
        omurga_write_error($e);
        return [];
    }
};

$tableExists=function(string $table): bool {
    try {
        return omurga_table_exists($table);
    } catch(Throwable $e) {
        error_log('Table exists check error: ' . $e->getMessage());
        return false;
    }
};

$todayStart=date('Y-m-d 00:00:00');
$tomorrowStart=date('Y-m-d 00:00:00', strtotime('+1 day'));

// Safe stat counters
$postCount=$safeCount("SELECT COUNT(*) FROM $postsT WHERE type<>'page' AND COALESCE(deleted_at,'')=''");
$pageCount=$safeCount("SELECT COUNT(*) FROM $postsT WHERE type='page' AND COALESCE(deleted_at,'')=''");
$publishedCount=$safeCount("SELECT COUNT(*) FROM $postsT WHERE status='published' AND type<>'page' AND COALESCE(deleted_at,'')=''");
$draftCount=$safeCount("SELECT COUNT(*) FROM $postsT WHERE status='draft' AND COALESCE(deleted_at,'')=''");
$todayPosts=$safeCount("SELECT COUNT(*) FROM $postsT WHERE created_at>=? AND created_at<? AND type<>'page' AND COALESCE(deleted_at,'')<>''", [$todayStart,$tomorrowStart]);
$trashCount=$safeCount("SELECT COUNT(*) FROM $postsT WHERE COALESCE(deleted_at,'')<>''");
$userCount=$safeCount("SELECT COUNT(*) FROM $usersT");
$formCount=$tableExists($formsT) ? $safeCount("SELECT COUNT(*) FROM $formsT") : 0;
$pendingComments=$tableExists($commentsT) ? $safeCount("SELECT COUNT(*) FROM $commentsT WHERE status='pending'") : 0;
$todayActivities=$tableExists($logsT) ? $safeCount("SELECT COUNT(*) FROM $logsT WHERE created_at>=? AND created_at<?", [$todayStart,$tomorrowStart]) : 0;

// Safe data fetchers
$latestPosts=$safeRows("SELECT p.id,p.title,p.type,p.status,p.created_at,c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.type<>'page' AND COALESCE(p.deleted_at,'')<>'' ORDER BY p.created_at DESC LIMIT 5");
$latestPages=$safeRows("SELECT id,title,status,created_at FROM $postsT WHERE type='page' AND COALESCE(deleted_at,'')<>'' ORDER BY created_at DESC LIMIT 5");
$latestLogs=$tableExists($logsT) ? $safeRows("SELECT * FROM $logsT ORDER BY id DESC LIMIT 7") : [];
$pendingRows=$tableExists($commentsT) ? $safeRows("SELECT c.*, p.title post_title FROM $commentsT c LEFT JOIN $postsT p ON p.id=c.post_id WHERE c.status='pending' ORDER BY c.created_at DESC LIMIT 5") : [];

$health=function_exists('omurga_system_health_full') ? omurga_system_health_full() : (function_exists('omurga_system_status') ? array_map(fn($r)=>['name'=>$r[0]??'', 'value'=>$r[1]??'', 'ok'=>(bool)($r[2]??true)], omurga_system_status()) : []);
$healthTotal=count($health);
$healthOk=count(array_filter($health, fn($r)=>($r['level'] ?? (($r['ok']??false)?'ok':'warning'))==='ok' || !empty($r['ok'])));
$healthWarn=max(0,$healthTotal-$healthOk);
$security=function_exists('omurga_security_center_report') ? omurga_security_center_report() : ['items'=>[]];
$securityItems=$security['items'] ?? [];
$securityOk=count(array_filter($securityItems, fn($r)=>!empty($r['ok'])));
$securityWarn=max(0,count($securityItems)-$securityOk);

$quick='post-edit.php?type='.primary_content_type();
$today=date('d.m.Y H:i');
$profile=site_profile();
?>
<div class="dle-page-head dashboard-head"><div><h1>Kontrol Paneli</h1><p><span>⌂</span> Omurga / Genel durum, içerik özeti ve son hareketler</p></div><div class="today">Bugün: <?=e($today)?></div></div>

<div class="dle-stat-row dashboard-stats">
  <a class="dle-stat" href="posts.php"><span class="sicon blue">▤</span><div><b>Yazılar</b><strong><?=$postCount?></strong><small><?=$publishedCount?> yayında · <?=$todayPosts?> bugün</small></div><i>→</i></a>
  <a class="dle-stat" href="pages.php"><span class="sicon purple">▦</span><div><b>Sayfalar</b><strong><?=$pageCount?></strong><small>Sabit sayfa mantığı</small></div><i>→</i></a>
  <a class="dle-stat" href="comments.php?status=pending"><span class="sicon orange">☵</span><div><b>Bekleyen yorum</b><strong><?=$pendingComments?></strong><small>Moderasyon bekleyenler</small></div><i>→</i></a>
  <a class="dle-stat" href="logs.php"><span class="sicon green">◷</span><div><b>Bugünkü hareket</b><strong><?=$todayActivities?></strong><small>Aktivite kayıtları</small></div><i>→</i></a>
</div>

<div class="dle-panel dashboard-actions"><div class="dle-panel-title">Hızlı İşlemler</div><div class="dle-quick-grid">
  <a class="dle-quick primary-quick" href="<?=e($quick)?>"><span class="qicon add">✚</span><div><b><?=e(content_quick_add_label())?></b><small>Yeni yazı/haber oluştur.</small></div></a>
  <a class="dle-quick" href="post-edit.php?type=page"><span class="qicon doc">▦</span><div><b>Yeni Sayfa</b><small>Hakkımızda, iletişim, KVKK gibi sabit sayfa oluştur.</small></div></a>
  <a class="dle-quick" href="media.php"><span class="qicon media">▧</span><div><b>Medya Yükle</b><small>Görsel ve dosya kütüphanesini yönet.</small></div></a>
  <a class="dle-quick" href="menus.php"><span class="qicon gear">☰</span><div><b>Menüler</b><small>Sayfa, yazı, kategori ve özel bağlantı ekle.</small></div></a>
  <a class="dle-quick" href="layout.php"><span class="qicon gear">▦</span><div><b>Sayfa Tasarımcısı</b><small>Blok alanları ve yerleşimleri düzenle.</small></div></a>
  <a class="dle-quick" href="backups.php"><span class="qicon backup">▰</span><div><b>Yedek Al</b><small>Veritabanı ve dosya yedeği oluştur.</small></div></a>
  <a class="dle-quick" href="security.php"><span class="qicon gear">🛡</span><div><b>Güvenlik Merkezi</b><small>Çekirdek ve paket güvenliğini kontrol et.</small></div></a>
  <a class="dle-quick" href="system.php"><span class="qicon update">↻</span><div><b>Sistem Sağlığı</b><small>PHP, disk, cache, cron ve hata kayıtlarını gör.</small></div></a>
</div></div>

<div class="dle-two-col dashboard-main">
  <div class="dle-panel"><div class="dle-panel-title flex-title"><span>Son Yazılar</span><a href="posts.php">Tümünü Gör</a></div>
    <div class="recent-list compact-recent">
      <?php if($latestPosts): foreach($latestPosts as $p): ?>
        <a href="post-edit.php?id=<?=(int)$p['id']?>"><span class="file-ico">▤</span><div><b><?=e($p['title'])?></b><small><?=e(type_label($p['type']))?> · <?=e($p['category_name'] ?: '-')?> · <?=e(date('d.m.Y', strtotime($p['created_at'])))?></small></div><em><?=e(substr(strip_tags($p['title'] ?? ''), 0, 100))?></em></a>
      <?php endforeach; else: ?><p class="muted empty-note">Henüz yazı yok.</p><?php endif; ?>
    </div>
  </div>
  <div class="dle-panel"><div class="dle-panel-title flex-title"><span>Son Sayfalar</span><a href="pages.php">Tümünü Gör</a></div>
    <div class="recent-list compact-recent">
      <?php if($latestPages): foreach($latestPages as $p): ?>
        <a href="post-edit.php?id=<?=(int)$p['id']?>"><span class="file-ico">▦</span><div><b><?=e($p['title'])?></b><small>Sabit sayfa · <?=e($p['status'])?></small></div><em><?=e(substr(strip_tags($p['title'] ?? ''), 0, 100))?></em></a>
      <?php endforeach; else: ?><p class="muted empty-note">Henüz sayfa yok.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="dle-two-col dashboard-main">
  <div class="dle-panel"><div class="dle-panel-title flex-title"><span>Sistem ve Güvenlik</span><a href="system.php">Detay</a></div>
    <div class="dashboard-health">
      <div class="health-box <?= $healthWarn ? 'warn' : 'ok' ?>"><b>Sistem Sağlığı</b><strong><?=$healthOk?> / <?=$healthTotal?></strong><small><?=$healthWarn?> uyarı</small></div>
      <div class="health-box <?= $securityWarn ? 'warn' : 'ok' ?>"><b>Güvenlik</b><strong><?=$securityOk?> / <?=count($securityItems)?></strong><small><?=$securityWarn?> uyarı</small></div>
      <div class="health-box <?= $trashCount ? 'warn' : 'ok' ?>"><b>Çöp Kutusu</b><strong><?=$trashCount?></strong><small>Silinmiş içerik</small></div>
      <div class="health-box ok"><b>Kullanıcılar</b><strong><?=$userCount?></strong><small>Kayıtlı kullanıcı</small></div>
    </div>
    <table class="stats-table mini-table">
      <tr><td>Aktif profil</td><td><?=e((string)($profile['label'] ?? site_type()))?></td></tr>
      <tr><td>Aktif tema</td><td><?=e(omurga_active_theme())?></td></tr>
      <tr><td>Omurga sürümü</td><td><?=e(OMURGA_VERSION)?></td></tr>
      <tr><td>Taslak içerik</td><td><?=$draftCount?></td></tr>
      <tr><td>Form başvurusu</td><td><?=$formCount?></td></tr>
    </table>
  </div>
  <div class="dle-panel"><div class="dle-panel-title flex-title"><span>Bekleyen Yorumlar</span><a href="comments.php?status=pending">Tümünü Gör</a></div>
    <div class="recent-list compact-recent">
      <?php if($pendingRows): foreach($pendingRows as $c): ?>
        <a href="comments.php?status=pending"><span class="file-ico">☵</span><div><b><?=e($c['author_name'] ?? 'Ziyaretçi')?></b><small><?=e($c['post_title'] ?? '-')?> · <?=e(mb_substr(strip_tags($c['content'] ?? ''), 0, 80))?></small></div></a>
      <?php endforeach; else: ?><p class="muted empty-note">Bekleyen yorum yok.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="dle-panel"><div class="dle-panel-title flex-title"><span>Son Aktiviteler</span><a href="logs.php">Aktivite Kayıtları</a></div>
  <div class="recent-list activity-list">
    <?php if($latestLogs): foreach($latestLogs as $l): $lvl=$l['level'] ?: (function_exists('omurga_activity_level_for_action') ? omurga_activity_level_for_action($l['action'] ?? '') : 'info'); ?>
      <a href="logs.php"><span class="file-ico level-<?=e($lvl)?>">●</span><div><b><?=e(function_exists('omurga_activity_action_label') ? omurga_activity_action_label($l['action'] ?? '') : ($l['action'] ?? 'Unknown'))?></b><small><?=e(date('d.m.Y H:i', strtotime($l['created_at'] ?? 'now'))))?> - <?=e($l['user_id'] ? 'User #' . $l['user_id'] : 'System')?></small></div></a>
    <?php endforeach; else: ?><p class="muted empty-note">Henüz aktivite kaydı yok.</p><?php endif; ?>
  </div>
</div>

<style>
.dashboard-head{align-items:flex-start}.dashboard-stats .dle-stat{text-decoration:none;color:inherit}.dashboard-actions{margin-top:18px}.dashboard-main{margin-top:18px}.compact-recent .empty-note{color:#999;font-size:13px}.health-box{padding:12px;border-radius:4px;margin-bottom:8px}.health-box.ok{background:#e8f5e9;color:#2e7d32}.health-box.warn{background:#fff3e0;color:#e65100}.activity-list{font-size:13px}
</style>
<?php require '_footer.php'; ?>
