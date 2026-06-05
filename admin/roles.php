<?php require '_layout.php'; require_cap('roles.manage');
$roles=omurga_role_labels();
$caps=omurga_capability_catalog();
$roleCaps=omurga_role_capabilities();
?>
<div class="toolbar"><h1>Roller</h1><div><a class="btn light" href="users.php">Kullanıcılar</a> <a class="btn light" href="permissions.php">Yetkiler</a></div></div>
<div class="card"><p>Omurga çekirdeğinde varsayılan roller aşağıdaki gibidir. <b>Süper Yönetici</b> korumalıdır; tema ve paketler bu rolü değiştiremez.</p></div>
<div class="grid-2">
<?php foreach($roles as $role=>$label): $list=$roleCaps[$role]??[]; ?>
  <div class="card">
    <h2><?=e($label)?> <?php if(omurga_role_is_protected($role)): ?><span class="badge">Korumalı</span><?php endif; ?></h2>
    <p><?=e(omurga_role_description($role))?></p>
    <?php if(in_array('*',$list,true)): ?>
      <p><b>Tüm yetkilere sahiptir.</b></p>
    <?php else: ?>
      <ul><?php foreach($list as $cap): ?><li><code><?=e($cap)?></code> — <?=e($caps[$cap] ?? $cap)?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php require '_footer.php'; ?>
