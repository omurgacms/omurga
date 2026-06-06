<?php
require '_layout.php';
require_cap('roles.manage');

$message=''; $error='';
$action=$_POST['action'] ?? $_GET['action'] ?? '';

if(($_GET['action'] ?? '')==='export' && !empty($_GET['role'])){
    $role=omurga_normalize_role_key((string)$_GET['role']);
    $labels=omurga_role_labels(); $caps=omurga_role_capabilities();
    if(!isset($labels[$role])){ http_response_code(404); exit('Rol bulunamadı.'); }
    $payload=[
        'type'=>'omurga_role',
        'version'=>1,
        'role'=>$role,
        'label'=>$labels[$role],
        'description'=>omurga_role_description($role),
        'capabilities'=>$caps[$role] ?? [],
        'exported_at'=>date('c'),
    ];
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="omurga-role-'.$role.'.json"');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(function_exists('csrf_check')) csrf_check();
    try{
        if($action==='save_role' || $action==='copy_role'){
            $role=(string)($_POST['role'] ?? '');
            if($action==='copy_role') $role=omurga_normalize_role_key((string)($_POST['new_role'] ?? ''));
            $label=(string)($_POST['label'] ?? '');
            $desc=(string)($_POST['description'] ?? '');
            $capabilities=(array)($_POST['capabilities'] ?? []);
            [$ok,$msg]=omurga_save_role_definition($role,$label,$desc,$capabilities);
            $ok ? $message=$msg : $error=$msg;
        } elseif($action==='delete_role'){
            [$ok,$msg]=omurga_delete_role_definition((string)($_POST['role'] ?? ''),(string)($_POST['reassign'] ?? 'reporter'));
            $ok ? $message=$msg : $error=$msg;
        } elseif($action==='import_role'){
            $json=(string)($_POST['import_json'] ?? '');
            $data=json_decode($json,true);
            if(!is_array($data)) throw new RuntimeException('Geçerli JSON girilmedi.');
            if(($data['type'] ?? '')!=='omurga_role') throw new RuntimeException('Bu dosya Omurga rol dışa aktarımı değil.');
            [$ok,$msg]=omurga_save_role_definition((string)($data['role'] ?? ''),(string)($data['label'] ?? ''),(string)($data['description'] ?? ''),(array)($data['capabilities'] ?? []));
            $ok ? $message='Rol içe aktarıldı.' : $error=$msg;
        }
    }catch(Throwable $e){ $error=$e->getMessage(); }
}

$roles=omurga_role_labels();
$caps=omurga_capability_catalog();
$roleCaps=omurga_role_capabilities();
$currentEdit=omurga_normalize_role_key((string)($_GET['edit'] ?? ''));
if($currentEdit && !isset($roles[$currentEdit])) $currentEdit='';
$editRole=$currentEdit ?: '';
$grouped=[];
foreach($caps as $cap=>$label){ $prefix=str_contains($cap,'.') ? strtok($cap,'.') : 'genel'; $grouped[$prefix][$cap]=$label; }
ksort($grouped);
?>
<div class="toolbar"><h1>Roller</h1><div><a class="btn light" href="users.php">Kullanıcılar</a> <a class="btn light" href="permissions.php">Yetkiler</a></div></div>
<?php if($message): ?><div class="alert success"><?=e($message)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>

<div class="grid-2">
  <div class="card">
    <h2><?= $editRole ? 'Rolü Düzenle' : 'Yeni Rol Oluştur' ?></h2>
    <?php $formRole=$editRole; $formCaps=$formRole ? ($roleCaps[$formRole] ?? []) : []; ?>
    <?php if($formRole==='super_admin'): ?>
      <p><b>Süper Yönetici</b> rolü korumalıdır ve panelden değiştirilemez.</p>
    <?php else: ?>
    <form method="post">
      <?=function_exists('csrf_input') ? csrf_input() : ''?>
      <input type="hidden" name="action" value="save_role">
      <label>Rol anahtarı
        <input name="role" value="<?=e($formRole)?>" placeholder="ornek_rol" <?= $formRole ? 'readonly' : '' ?>>
      </label>
      <label>Rol adı
        <input name="label" value="<?=e($formRole ? ($roles[$formRole] ?? '') : '')?>" placeholder="Örn: Haber Editörü">
      </label>
      <label>Açıklama
        <textarea name="description" rows="2" placeholder="Bu rol ne işe yarar?"><?=e($formRole ? omurga_role_description($formRole) : '')?></textarea>
      </label>
      <div class="compact-permissions">
        <?php foreach($grouped as $group=>$items): ?>
          <details <?=in_array($group, ['posts','pages','media','settings','system','roles','users'], true)?'open':''?>>
            <summary><?=e(ucfirst($group))?> <small><?=count($items)?> yetki</small></summary>
            <div class="perm-grid">
            <?php foreach($items as $cap=>$label): ?>
              <label class="check"><input type="checkbox" name="capabilities[]" value="<?=e($cap)?>" <?=in_array($cap,$formCaps,true)?'checked':''?>> <span><code><?=e($cap)?></code><small><?=e($label)?></small></span></label>
            <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
      <button class="btn" type="submit">Kaydet</button>
      <?php if($formRole): ?><a class="btn light" href="roles.php">Yeni rol</a><?php endif; ?>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>İçe Aktar</h2>
    <p>Daha önce dışa aktarılan Omurga rol JSON içeriğini yapıştır.</p>
    <form method="post">
      <?=function_exists('csrf_input') ? csrf_input() : ''?>
      <input type="hidden" name="action" value="import_role">
      <textarea name="import_json" rows="8" placeholder='{"type":"omurga_role", ...}'></textarea>
      <button class="btn light" type="submit">Rolü İçe Aktar</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Rol Listesi</h2>
  <div class="table-wrap"><table class="table compact-table"><thead><tr><th>Rol</th><th>Açıklama</th><th>Yetki</th><th>İşlem</th></tr></thead><tbody>
  <?php foreach($roles as $role=>$label): $list=$roleCaps[$role]??[]; ?>
    <tr>
      <td><b><?=e($label)?></b><br><code><?=e($role)?></code> <?php if(omurga_role_is_protected($role)): ?><span class="badge">Korumalı</span><?php endif; ?></td>
      <td><?=e(omurga_role_description($role))?></td>
      <td><?=in_array('*',$list,true) ? 'Tüm yetkiler' : count($list).' yetki'?></td>
      <td class="row-actions">
        <a class="btn light small" href="roles.php?edit=<?=urlencode($role)?>">Düzenle</a>
        <a class="btn light small" href="roles.php?action=export&role=<?=urlencode($role)?>">Dışa aktar</a>
        <?php if(!omurga_role_is_protected($role)): ?>
        <details class="inline-menu"><summary class="btn light small">Kopyala / Sil</summary>
          <div class="dropdown-panel">
            <form method="post">
              <?=function_exists('csrf_input') ? csrf_input() : ''?>
              <input type="hidden" name="action" value="copy_role"><input type="hidden" name="role" value="<?=e($role)?>">
              <input name="new_role" placeholder="yeni_rol_anahtari">
              <input name="label" value="<?=e($label.' Kopya')?>">
              <textarea name="description" rows="2"><?=e(omurga_role_description($role))?></textarea>
              <?php foreach($list as $cap): ?><input type="hidden" name="capabilities[]" value="<?=e($cap)?>"><?php endforeach; ?>
              <button class="btn small" type="submit">Kopyala</button>
            </form>
            <hr>
            <form method="post" onsubmit="return confirm('Rol silinsin mi? Bu roldeki kullanıcılar seçilen role aktarılır.');">
              <?=function_exists('csrf_input') ? csrf_input() : ''?>
              <input type="hidden" name="action" value="delete_role"><input type="hidden" name="role" value="<?=e($role)?>">
              <select name="reassign"><?php foreach($roles as $rk=>$rv): if($rk===$role) continue; ?><option value="<?=e($rk)?>"><?=e($rv)?></option><?php endforeach; ?></select>
              <button class="btn danger small" type="submit">Sil</button>
            </form>
          </div>
        </details>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
</div>

<style>
.compact-permissions details{border:1px solid var(--border,#e5e7eb);border-radius:12px;margin:8px 0;padding:8px;background:#fff}.compact-permissions summary{cursor:pointer;font-weight:700}.perm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:6px;margin-top:8px}.check{display:flex;gap:8px;align-items:flex-start;border:1px solid #edf0f3;border-radius:10px;padding:7px}.check small{display:block;color:#667085}.row-actions{white-space:nowrap}.inline-menu{display:inline-block;position:relative}.inline-menu summary{list-style:none}.dropdown-panel{position:absolute;right:0;z-index:20;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 14px 30px rgba(15,23,42,.12);padding:12px;min-width:260px}.small{padding:6px 10px;font-size:12px}.table-wrap{overflow:auto}@media(max-width:760px){.grid-2{display:block}.row-actions{white-space:normal}.dropdown-panel{position:static;margin-top:8px}.perm-grid{grid-template-columns:1fr}}
</style>
<?php require '_footer.php'; ?>
