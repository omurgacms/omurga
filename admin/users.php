<?php
require '_layout.php';
verify_csrf();

$t = table_name('users');
$roles = function_exists('omurga_role_labels') ? omurga_role_labels() : ['super_admin'=>'Süper Yönetici','admin'=>'Yönetici','editor'=>'Editör','author'=>'Yazar','reporter'=>'Muhabir','member'=>'Üye'];
$statuses = ['active'=>'Aktif','passive'=>'Pasif'];

function omurga_users_notice(string $type, string $message): void {
    echo '<div class="alert '.e($type).'">'.e($message).'</div>';
}

function omurga_users_current_id(): int {
    return (int)($_SESSION['omurga_user_id'] ?? 0);
}

function omurga_users_fetch_user(int $id): ?array {
    if ($id <= 0) return null;
    try {
        $st = db()->prepare('SELECT * FROM '.table_name('users').' WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function omurga_users_fetch_all(): array {
    try {
        return db()->query('SELECT * FROM '.table_name('users').' ORDER BY id DESC')->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function omurga_users_can_manage(): bool {
    if (function_exists('omurga_is_super_admin') && omurga_is_super_admin()) return true;
    return can('users.manage') || can('system.manage') || can('settings.manage');
}

function omurga_users_can_modify(array $target): bool {
    if (function_exists('omurga_can_modify_user')) return omurga_can_modify_user($target);
    return omurga_users_can_manage();
}

function omurga_users_can_assign(string $role): bool {
    if (function_exists('omurga_can_assign_role')) return omurga_can_assign_role($role);
    return omurga_users_can_manage();
}

$currentUserId = omurga_users_current_id();
$profileMode = isset($_GET['profile']) || (($_GET['edit'] ?? '') === 'me');

if (!$profileMode && !omurga_users_can_manage()) {
    render_error_page(403, 'Yetkisiz Erişim', 'Kullanıcı yönetimi için yetkiniz yok.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $role = (string)($_POST['role'] ?? 'reporter');
        $status = (string)($_POST['status'] ?? 'active');
        $password = (string)($_POST['password'] ?? '');

        if ($profileMode) {
            $id = $currentUserId;
            $target = omurga_users_fetch_user($id);
            if (!$target) {
                omurga_users_notice('danger', 'Profil kaydı bulunamadı. Oturum kullanıcı ID: '.$id);
            } elseif ($name === '' || $email === '' || $username === '') {
                omurga_users_notice('danger', 'Ad soyad, e-posta ve kullanıcı adı boş olamaz.');
            } else {
                if ($password !== '') {
                    db()->prepare("UPDATE $t SET name=?, email=?, username=?, password=? WHERE id=?")->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    db()->prepare("UPDATE $t SET name=?, email=?, username=? WHERE id=?")->execute([$name, $email, $username, $id]);
                }
                $_SESSION['omurga_user_name'] = $name;
                log_activity('user.profile_update', 'Profil güncellendi: '.$username, $id);
                omurga_users_notice('success', 'Profil bilgileriniz güncellendi.');
            }
        } else {
            if (!isset($roles[$role])) $role = 'reporter';
            if (!isset($statuses[$status])) $status = 'active';

            if ($name === '' || $email === '' || $username === '') {
                omurga_users_notice('danger', 'Ad soyad, e-posta ve kullanıcı adı boş olamaz.');
            } elseif (!omurga_users_can_assign($role)) {
                omurga_users_notice('danger', 'Bu rolü atama yetkiniz yok.');
            } elseif ($id > 0) {
                $target = omurga_users_fetch_user($id);
                if (!$target) {
                    omurga_users_notice('danger', 'Kullanıcı bulunamadı.');
                } elseif (!omurga_users_can_modify($target)) {
                    omurga_users_notice('danger', 'Bu kullanıcıyı düzenleme yetkiniz yok.');
                } elseif (($target['role'] ?? '') === 'super_admin' && $role !== 'super_admin' && !(function_exists('omurga_is_super_admin') && omurga_is_super_admin())) {
                    omurga_users_notice('danger', 'Süper Yönetici rolünü sadece Süper Yönetici değiştirebilir.');
                } else {
                    if ($password !== '') {
                        db()->prepare("UPDATE $t SET name=?, email=?, username=?, role=?, status=?, password=? WHERE id=?")->execute([$name, $email, $username, $role, $status, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        db()->prepare("UPDATE $t SET name=?, email=?, username=?, role=?, status=? WHERE id=?")->execute([$name, $email, $username, $role, $status, $id]);
                    }
                    log_activity('user.update', 'Kullanıcı güncellendi: '.$username);
                    omurga_users_notice('success', 'Kullanıcı güncellendi.');
                }
            } else {
                if ($password === '') $password = bin2hex(random_bytes(4));
                db()->prepare("INSERT INTO $t (name,email,username,password,role,status) VALUES (?,?,?,?,?,?)")->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
                log_activity('user.create', 'Kullanıcı eklendi: '.$username);
                omurga_users_notice('success', 'Kullanıcı eklendi.');
            }
        }
    }

    if (!$profileMode && $action === 'delete' && $id > 0) {
        if ($id === $currentUserId) {
            omurga_users_notice('danger', 'Kendi hesabınızı silemezsiniz.');
        } else {
            $target = omurga_users_fetch_user($id);
            if (!$target) {
                omurga_users_notice('danger', 'Kullanıcı bulunamadı.');
            } elseif (!omurga_users_can_modify($target)) {
                omurga_users_notice('danger', 'Bu kullanıcıyı silme yetkiniz yok.');
            } elseif (($target['role'] ?? '') === 'super_admin') {
                omurga_users_notice('danger', 'Süper Yönetici silinemez.');
            } else {
                db()->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);
                log_activity('user.delete', 'Kullanıcı silindi: #'.$id);
                omurga_users_notice('success', 'Kullanıcı silindi.');
            }
        }
    }
}

$edit = null;
if ($profileMode) {
    $edit = omurga_users_fetch_user($currentUserId);
} elseif (isset($_GET['edit'])) {
    $edit = omurga_users_fetch_user((int)$_GET['edit']);
}
$users = $profileMode ? [] : omurga_users_fetch_all();
$isEditing = !$profileMode && $edit;
?>
<div class="toolbar">
  <h1><?= $profileMode ? 'Profilim' : 'Kullanıcılar' ?></h1>
  <div>
    <?php if ($profileMode): ?>
      <a class="btn light" href="users.php">Kullanıcı Listesi</a>
      <a class="btn light" href="index.php">Panele Dön</a>
    <?php else: ?>
      <a class="btn light" href="users.php?profile=1">Profilim</a>
      <a class="btn light" href="roles.php">Roller</a>
      <a class="btn light" href="permissions.php">Yetkiler</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($profileMode && !$edit): ?>
  <div class="card"><h2>Profil bulunamadı</h2><p>Oturum açmış kullanıcı kaydı bulunamadı. Çıkış yapıp tekrar giriş yapmayı deneyin.</p></div>
<?php else: ?>
<div class="grid-2 users-admin-grid">
  <div class="card">
    <h2><?= $profileMode ? 'Profil Bilgileri' : ($isEditing ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı') ?></h2>
    <?php if ($profileMode): ?><p class="muted">Kendi profil bilgilerinizi buradan güncelleyebilirsiniz.</p><?php endif; ?>
    <form method="post" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? 0)) ?>">
      <label>Ad Soyad<input name="name" required value="<?= e((string)($edit['name'] ?? '')) ?>"></label>
      <label>E-posta<input type="email" name="email" required value="<?= e((string)($edit['email'] ?? '')) ?>"></label>
      <label>Kullanıcı adı<input name="username" required value="<?= e((string)($edit['username'] ?? '')) ?>"></label>

      <?php if ($profileMode): ?>
        <div class="alert info">Rol ve durum profil ekranından değiştirilemez.</div>
      <?php else: ?>
        <label>Rol<select name="role">
          <?php foreach ($roles as $k => $v): ?>
            <?php if (!omurga_users_can_assign($k) && (($edit['role'] ?? '') !== $k)) continue; ?>
            <option value="<?= e($k) ?>" <?= (($edit['role'] ?? 'reporter') === $k) ? 'selected' : '' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select></label>
        <label>Durum<select name="status">
          <?php foreach ($statuses as $k => $v): ?><option value="<?= e($k) ?>" <?= (($edit['status'] ?? 'active') === $k) ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
        </select></label>
      <?php endif; ?>

      <label>Şifre <?= ($edit || $profileMode) ? '<small>Boş bırakırsan değişmez</small>' : '' ?><input type="password" name="password" <?= (!$edit && !$profileMode) ? 'required' : '' ?>></label>
      <button class="btn primary"><?= $profileMode ? 'Profilimi Kaydet' : ($isEditing ? 'Kullanıcıyı Kaydet' : 'Yeni Kullanıcı Ekle') ?></button>
      <?php if ($isEditing): ?><a class="btn light" href="users.php">Yeni kullanıcı formuna dön</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <?php if ($profileMode): ?>
      <h2>Hesap Özeti</h2>
      <p><b>Rol:</b> <?= e($roles[$edit['role'] ?? ''] ?? ($edit['role'] ?? '-')) ?></p>
      <p><b>Durum:</b> <?= e($statuses[$edit['status'] ?? ''] ?? ($edit['status'] ?? '-')) ?></p>
      <p><b>Son giriş:</b> <?= e((string)($edit['last_login_at'] ?? '-')) ?></p>
    <?php else: ?>
      <h2>Kullanıcı Yönetimi</h2>
      <p>Kullanıcı ekleyebilir, mevcut kullanıcıları düzenleyebilir ve rol/yetki ekranlarından erişim seviyelerini yönetebilirsiniz.</p>
      <p><b>Süper Yönetici</b> korumalı roldür. Bu rol yanlışlıkla silinemez veya sıradan yönetici tarafından düşürülemez.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!$profileMode): ?>
<div class="card">
  <div class="toolbar compact"><h2>Mevcut Kullanıcılar</h2><span class="badge"><?= count($users) ?> kullanıcı</span></div>
  <div class="table-wrap"><table class="table users-table">
    <thead><tr><th>Ad</th><th>Kullanıcı</th><th>Rol</th><th>Durum</th><th>Son Giriş</th><th>İşlem</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><b><?= e((string)($u['name'] ?? '-')) ?></b><br><small><?= e((string)($u['email'] ?? '')) ?></small></td>
        <td><?= e((string)($u['username'] ?? '-')) ?><?= ((int)($u['id'] ?? 0) === $currentUserId) ? '<br><small>Bu sizsiniz</small>' : '' ?></td>
        <td><span class="badge"><?= e((string)($roles[$u['role'] ?? ''] ?? ($u['role'] ?? '-'))) ?></span></td>
        <td><?= e((string)($statuses[$u['status'] ?? ''] ?? ($u['status'] ?? '-'))) ?></td>
        <td><?= e((string)($u['last_login_at'] ?? '-')) ?></td>
        <td>
          <?php if ((int)($u['id'] ?? 0) === $currentUserId): ?>
            <a class="btn light" href="users.php?profile=1">Profilim</a>
          <?php elseif (omurga_users_can_modify($u)): ?>
            <a class="btn light" href="users.php?edit=<?= (int)$u['id'] ?>">Düzenle</a>
          <?php endif; ?>
          <?php if ((int)($u['id'] ?? 0) !== $currentUserId && ($u['role'] ?? '') !== 'super_admin' && omurga_users_can_modify($u)): ?>
            <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button name="action" value="delete" class="btn danger" onclick="return confirm('Kullanıcı silinsin mi?')">Sil</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$users): ?><tr><td colspan="6">Kullanıcı bulunamadı. Veritabanında kullanıcı kaydı yoksa kurulumdaki yönetici hesabını kontrol edin.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php require '_footer.php'; ?>
