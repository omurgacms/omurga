<?php
require '_layout.php';
require_cap('roles.manage');
$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(function_exists('csrf_check')) csrf_check();
    try{
        $action=$_POST['action'] ?? '';
        if($action==='add_permission'){
            $cap=omurga_normalize_capability((string)($_POST['capability'] ?? ''));
            $label=trim((string)($_POST['label'] ?? ''));
            if(!$cap) throw new RuntimeException('Yetki anahtarı boş olamaz.');
            if(!$label) $label=$cap;
            $perms=omurga_custom_permissions();
            $perms[$cap]=$label;
            omurga_update_custom_permissions($perms);
            $message='Özel yetki eklendi.';
        } elseif($action==='delete_permission'){
            $cap=omurga_normalize_capability((string)($_POST['capability'] ?? ''));
            $core=omurga_core_capability_catalog();
            if(isset($core[$cap])) throw new RuntimeException('Çekirdek yetki silinemez.');
            $perms=omurga_custom_permissions(); unset($perms[$cap]); omurga_update_custom_permissions($perms);
            $message='Özel yetki silindi.';
        }
    }catch(Throwable $e){ $error=$e->getMessage(); }
}
$caps=omurga_capability_catalog();
$coreCaps=omurga_core_capability_catalog();
$customCaps=omurga_custom_permissions();
$roles=omurga_role_labels();
$roleCaps=omurga_role_capabilities();
$grouped=[];
foreach($caps as $cap=>$label){ $prefix=str_contains($cap,'.') ? strtok($cap,'.') : 'genel'; $grouped[$prefix][$cap]=$label; }
ksort($grouped);
?>
<div class="toolbar"><h1>Yetkiler</h1><div><a class="btn light" href="users.php">Kullanıcılar</a> <a class="btn light" href="roles.php">Roller</a></div></div>
<?php if($message): ?><div class="alert success"><?=e($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<div class="grid-2">
  <div class="card">
    <h2>Yetki Kataloğu</h2>
    <p>Çekirdek, tema ve paketlerin kullanabileceği resmi yetki anahtarları. Noktalı anahtar standardı korunur: <code>media.upload</code>, <code>system.update</code>.</p>
    <div class="permission-groups">
    <?php foreach($grouped as $group=>$items): ?>
      <details <?=in_array($group,['posts','pages','media','system','users','roles'],true)?'open':''?>>
        <summary><?=e(ucfirst($group))?> <span class="badge light"><?=count($items)?></span></summary>
        <table class="table compact-table"><thead><tr><th>Yetki</th><th>Açıklama</th><th>Varsayılan Roller</th><th>Tip</th></tr></thead><tbody>
        <?php foreach($items as $cap=>$label): $owners=[]; foreach($roles as $role=>$rlabel){ $list=$roleCaps[$role]??[]; if(in_array('*',$list,true) || in_array($cap,$list,true)) $owners[]=$rlabel; } ?>
          <tr>
            <td><code><?=e($cap)?></code></td>
            <td><?=e($label)?></td>
            <td><?=e(implode(', ',$owners) ?: '-')?></td>
            <td>
              <?=isset($coreCaps[$cap]) ? '<span class="badge">Çekirdek</span>' : '<span class="badge light">Özel/Paket</span>'?>
              <?php if(isset($customCaps[$cap])): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Özel yetki silinsin mi?');">
                  <?=function_exists('csrf_input') ? csrf_input() : ''?>
                  <input type="hidden" name="action" value="delete_permission"><input type="hidden" name="capability" value="<?=e($cap)?>">
                  <button class="btn danger small" type="submit">Sil</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody></table>
      </details>
    <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h2>Özel Yetki Ekle</h2>
    <p>Paket veya özel yönetim ekranları için yeni yetki anahtarı tanımla. Rollere atamak için <b>Roller</b> ekranını kullan.</p>
    <form method="post">
      <?=function_exists('csrf_input') ? csrf_input() : ''?>
      <input type="hidden" name="action" value="add_permission">
      <label>Yetki anahtarı<input name="capability" placeholder="ornek.manage"></label>
      <label>Açıklama<input name="label" placeholder="Örnek alanı yönetebilir"></label>
      <button class="btn" type="submit">Yetki Ekle</button>
    </form>
    <hr>
    <h2>Yetki Grupları</h2>
    <ul class="mini-list">
      <?php foreach($grouped as $group=>$items): ?><li><b><?=e($group)?></b> — <?=count($items)?> yetki</li><?php endforeach; ?>
    </ul>
  </div>
</div>
<style>
.permission-groups details{border:1px solid var(--border,#e5e7eb);border-radius:12px;margin:10px 0;background:#fff;overflow:hidden}.permission-groups summary{cursor:pointer;font-weight:700;padding:10px 12px;background:#f8fafc}.permission-groups table{margin:0}.small{padding:5px 8px;font-size:12px}.mini-list{margin:0;padding-left:18px}.compact-table td,.compact-table th{padding:8px 10px}@media(max-width:760px){.grid-2{display:block}.permission-groups{overflow:auto}}
</style>
<?php require '_footer.php'; ?>
