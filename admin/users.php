<?php
require '_layout.php';
verify_csrf();
require_cap('users.manage');

$t=table_name('users');
$roles=omurga_role_labels();
$statuses=['active'=>'Aktif','passive'=>'Pasif'];
$currentUser=current_user();

function omurga_user_flash(string $type,string $message): void { echo '<div class="alert '.e($type).'">'.e($message).'</div>'; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $id=(int)($_POST['id']??0);

    if($action==='save'){
        $name=trim($_POST['name']??'');
        $email=trim($_POST['email']??'');
        $username=trim($_POST['username']??'');
        $role=$_POST['role']??'reporter';
        $status=$_POST['status']??'active';
        $password=$_POST['password']??'';
        if(!isset($roles[$role])) $role='reporter';
        if(!isset($statuses[$status])) $status='active';
        if(!omurga_can_assign_role($role)){
            omurga_user_flash('danger','Bu rolü atama yetkiniz yok.');
        } elseif($id){
            $st=db()->prepare("SELECT * FROM $t WHERE id=?");
            $st->execute([$id]);
            $target=$st->fetch();
            if(!$target){
                omurga_user_flash('danger','Kullanıcı bulunamadı.');
            } elseif(!omurga_can_modify_user($target)){
                omurga_user_flash('danger','Bu kullanıcıyı düzenleme yetkiniz yok.');
            } else {
                if(($target['role'] ?? '')==='super_admin' && $role!=='super_admin' && !omurga_is_super_admin()){
                    omurga_user_flash('danger','Süper Yönetici rolünü sadece Süper Yönetici değiştirebilir.');
                } else {
                    if($password){
                        db()->prepare("UPDATE $t SET name=?,email=?,username=?,role=?,status=?,password=? WHERE id=?")->execute([$name,$email,$username,$role,$status,password_hash($password,PASSWORD_DEFAULT),$id]);
                    } else {
                        db()->prepare("UPDATE $t SET name=?,email=?,username=?,role=?,status=? WHERE id=?")->execute([$name,$email,$username,$role,$status,$id]);
                    }
                    log_activity('user.update','Kullanıcı güncellendi: '.$username);
                    omurga_user_flash('success','Kullanıcı güncellendi.');
                }
            }
        } else {
            if(!$password) $password=bin2hex(random_bytes(4));
            db()->prepare("INSERT INTO $t (name,email,username,password,role,status) VALUES (?,?,?,?,?,?)")->execute([$name,$email,$username,password_hash($password,PASSWORD_DEFAULT),$role,$status]);
            log_activity('user.create','Kullanıcı eklendi: '.$username);
            omurga_user_flash('success','Kullanıcı eklendi.');
        }
    }

    if($action==='delete' && $id){
        if($id===(int)($_SESSION['omurga_user_id']??0)){
            omurga_user_flash('danger','Kendi hesabınızı silemezsiniz.');
        } else {
            $st=db()->prepare("SELECT * FROM $t WHERE id=?");
            $st->execute([$id]);
            $target=$st->fetch();
            if(!$target){
                omurga_user_flash('danger','Kullanıcı bulunamadı.');
            } elseif(!omurga_can_modify_user($target)){
                omurga_user_flash('danger','Bu kullanıcıyı silme yetkiniz yok.');
            } elseif(($target['role'] ?? '')==='super_admin'){
                omurga_user_flash('danger','Süper Yönetici silinemez. Önce başka bir Süper Yönetici oluşturup rolü değiştirin.');
            } else {
                db()->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);
                log_activity('user.delete','Kullanıcı silindi: #'.$id);
                omurga_user_flash('success','Kullanıcı silindi.');
            }
        }
    }
}

$edit=null;
if(isset($_GET['edit'])){ $s=db()->prepare("SELECT * FROM $t WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$users=db()->query("SELECT * FROM $t ORDER BY id DESC")->fetchAll();
?>
<div class="toolbar"><h1>Kullanıcılar</h1><div><a class="btn light" href="roles.php">Roller</a> <a class="btn light" href="permissions.php">Yetkiler</a></div></div>
<div class="grid-2">
  <div class="card">
    <h2><?= $edit?'Kullanıcı Düzenle':'Yeni Kullanıcı' ?></h2>
    <form method="post" class="form-grid">
      <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?=e($edit['id']??0)?>">
      <label>Ad Soyad<input name="name" required value="<?=e($edit['name']??'')?>"></label>
      <label>E-posta<input type="email" name="email" required value="<?=e($edit['email']??'')?>"></label>
      <label>Kullanıcı adı<input name="username" required value="<?=e($edit['username']??'')?>"></label>
      <label>Rol<select name="role">
        <?php foreach($roles as $k=>$v): ?>
          <?php if(!omurga_can_assign_role($k) && (($edit['role']??'')!==$k)) continue; ?>
          <option value="<?=$k?>" <?=($edit['role']??'reporter')===$k?'selected':''?>><?=$v?></option>
        <?php endforeach; ?>
      </select></label>
      <label>Durum<select name="status"><?php foreach($statuses as $k=>$v): ?><option value="<?=$k?>" <?=($edit['status']??'active')===$k?'selected':''?>><?=$v?></option><?php endforeach; ?></select></label>
      <label>Şifre <?= $edit?'<small>Boş bırakırsan değişmez</small>':'' ?><input type="password" name="password" <?= $edit?'':'required' ?>></label>
      <button class="btn primary">Kaydet</button>
    </form>
  </div>
  <div class="card">
    <h2>Rol Mantığı</h2>
    <p><b>Süper Yönetici:</b> Korumalı tam yetki. Başka roller tarafından değiştirilemez.</p>
    <p><b>Yönetici:</b> Site yönetimi için geniş yetki; günlük kullanım için önerilir.</p>
    <p><b>Editör:</b> İçerik, sayfa, yorum, kategori, etiket, medya ve form süreçlerini yönetir.</p>
    <p><b>Yazar / Muhabir:</b> İçerik oluşturur, kendi içeriklerini düzenler ve incelemeye gönderir.</p>
  </div>
</div>
<div class="card"><table class="table"><thead><tr><th>Ad</th><th>Kullanıcı</th><th>Rol</th><th>Durum</th><th>Son Giriş</th><th>İşlem</th></tr></thead><tbody>
<?php foreach($users as $u): ?>
<tr>
  <td><?=e($u['name'])?><br><small><?=e($u['email'])?></small></td>
  <td><?=e($u['username'])?></td>
  <td><span class="badge"><?=e($roles[$u['role']]??$u['role'])?></span><?php if(($u['role']??'')==='super_admin'): ?><br><small>Korumalı rol</small><?php endif; ?></td>
  <td><?=e($statuses[$u['status']]??$u['status'])?></td>
  <td><?=e($u['last_login_at']??'-')?></td>
  <td>
    <?php if(omurga_can_modify_user($u)): ?><a class="btn light" href="users.php?edit=<?=$u['id']?>">Düzenle</a><?php endif; ?>
    <?php if($u['id']!=(int)($_SESSION['omurga_user_id']??0) && ($u['role']??'')!=='super_admin' && omurga_can_modify_user($u)): ?>
      <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$u['id']?>"><button name="action" value="delete" class="btn danger" onclick="return confirm('Kullanıcı silinsin mi?')">Sil</button></form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if(!$users): ?><tr><td colspan="6">Kullanıcı bulunamadı.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require '_footer.php'; ?>
