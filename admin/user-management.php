<?php
require '_layout.php';
require_cap('users.manage');
$tab = preg_replace('/[^a-z_]/','', (string)($_GET['tab'] ?? 'summary'));
if(!in_array($tab, ['summary','users','roles','permissions'], true)) $tab='summary';
$usersT=table_name('users');
$roles=omurga_role_labels();
$roleCaps=omurga_role_capabilities();
$capCatalog=omurga_capability_catalog();
$stats=['users'=>0,'admins'=>0,'roles'=>count($roles),'caps'=>count($capCatalog)];
$recent=[];
try{
  $stats['users']=(int)db()->query("SELECT COUNT(*) FROM $usersT")->fetchColumn();
  $stats['admins']=(int)db()->query("SELECT COUNT(*) FROM $usersT WHERE role='admin'")->fetchColumn();
  $recent=db()->query("SELECT id,name,email,role,created_at FROM $usersT ORDER BY id DESC LIMIT 8")->fetchAll();
}catch(Throwable $e){}
function om_user_mgmt_tab($id,$label,$href){ global $tab; $active=$tab===$id?' active':''; echo '<a class="omg-tab'.$active.'" href="'.e($href).'">'.e($label).'</a>'; }
?>
<div class="page-head compact-head">
  <div><h1>Kullanıcı Yönetimi</h1><p>Kullanıcı, rol ve yetki ekranları tek merkezde toplandı. Eski sayfalar çalışmaya devam eder.</p></div>
  <div class="page-actions"><a class="btn primary" href="users.php?action=new">Yeni Kullanıcı</a></div>
</div>
<div class="omg-summary-strip compact-summary">
  <span><b><?=e((string)$stats['users'])?></b> kullanıcı</span>
  <span><b><?=e((string)$stats['admins'])?></b> admin</span>
  <span><b><?=e((string)$stats['roles'])?></b> rol</span>
  <span><b><?=e((string)$stats['caps'])?></b> yetki</span>
</div>
<nav class="omg-tabs compact-tabs" aria-label="Kullanıcı yönetimi sekmeleri">
  <?php om_user_mgmt_tab('summary','Özet','user-management.php'); ?>
  <?php om_user_mgmt_tab('users','Kullanıcılar','users.php'); ?>
  <?php om_user_mgmt_tab('roles','Roller','roles.php'); ?>
  <?php om_user_mgmt_tab('permissions','Yetkiler','permissions.php'); ?>
</nav>
<div class="grid-2 compact-grid">
  <section class="compact-panel">
    <div class="compact-panel-head"><h2>Son Kullanıcılar</h2><a class="btn secondary" href="users.php">Tümünü Aç</a></div>
    <div class="compact-record-list">
      <?php foreach($recent as $u): ?>
        <div class="compact-record-row">
          <div><strong><?=e($u['name'] ?: $u['email'])?></strong><small><?=e($u['email'])?></small></div>
          <span class="badge light"><?=e($roles[$u['role']] ?? $u['role'])?></span>
          <a class="btn secondary" href="users.php?edit=<?=e($u['id'])?>">Düzenle</a>
        </div>
      <?php endforeach; ?>
      <?php if(!$recent): ?><div class="empty-state compact-empty">Henüz kullanıcı yok.</div><?php endif; ?>
    </div>
  </section>
  <section class="compact-panel">
    <div class="compact-panel-head"><h2>Rol Özeti</h2><a class="btn secondary" href="roles.php">Rolleri Yönet</a></div>
    <div class="compact-record-list">
      <?php foreach($roles as $key=>$label): $list=$roleCaps[$key] ?? []; ?>
        <div class="compact-record-row">
          <div><strong><?=e($label)?></strong><small><code><?=e($key)?></code></small></div>
          <span class="badge light"><?=in_array('*',$list,true) ? 'Tüm yetkiler' : e((string)count($list)).' yetki'?></span>
          <a class="btn secondary" href="roles.php?edit=<?=urlencode($key)?>">Düzenle</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<section class="compact-panel">
  <div class="compact-panel-head"><h2>Hızlı İşlemler</h2></div>
  <div class="compact-action-grid">
    <a class="compact-action" href="users.php"><b>Kullanıcılar</b><span>Kullanıcı ekle, düzenle, rol ata.</span></a>
    <a class="compact-action" href="roles.php"><b>Roller</b><span>Rol oluştur, kopyala, yetki bağla.</span></a>
    <a class="compact-action" href="permissions.php"><b>Yetkiler</b><span>Çekirdek, tema ve paket yetkilerini incele.</span></a>
  </div>
</section>
<style>
.compact-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}.omg-tab{border:1px solid #e5e7eb;background:#fff;border-radius:999px;padding:8px 12px;text-decoration:none;color:#334155;font-weight:700;font-size:13px}.omg-tab.active{background:#0f172a;color:#fff;border-color:#0f172a}.compact-record-list{display:grid;gap:8px}.compact-record-row{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:center;border:1px solid #edf0f3;border-radius:12px;background:#fff;padding:10px}.compact-record-row small{display:block;color:#64748b;margin-top:2px}.compact-action-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px}.compact-action{display:block;border:1px solid #e5e7eb;border-radius:14px;background:#fff;text-decoration:none;color:#0f172a;padding:12px}.compact-action span{display:block;color:#64748b;font-size:12px;margin-top:4px}.compact-empty{padding:18px;text-align:center;color:#64748b}@media(max-width:760px){.compact-record-row{grid-template-columns:1fr}.compact-tabs{overflow:auto;flex-wrap:nowrap}.omg-tab{white-space:nowrap}}
</style>
<?php require '_footer.php'; ?>
