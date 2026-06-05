<?php require '_layout.php'; require_cap('roles.manage');
$caps=omurga_capability_catalog();
$roles=omurga_role_labels();
$roleCaps=omurga_role_capabilities();
?>
<div class="toolbar"><h1>Yetkiler</h1><div><a class="btn light" href="users.php">Kullanıcılar</a> <a class="btn light" href="roles.php">Roller</a></div></div>
<div class="card"><p>Bu ekran çekirdekteki resmi yetki anahtarlarını gösterir. Paketler kendi yetkilerini ayrıca kaydedebilir; ancak Süper Yönetici rolü ve çekirdek güvenlik yetkileri korumalıdır.</p></div>
<div class="card"><table class="table"><thead><tr><th>Yetki</th><th>Açıklama</th><th>Varsayılan Roller</th></tr></thead><tbody>
<?php foreach($caps as $cap=>$label): $owners=[]; foreach($roles as $role=>$rlabel){ $list=$roleCaps[$role]??[]; if(in_array('*',$list,true) || in_array($cap,$list,true)) $owners[]=$rlabel; } ?>
<tr><td><code><?=e($cap)?></code></td><td><?=e($label)?></td><td><?=e(implode(', ',$owners))?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php require '_footer.php'; ?>
