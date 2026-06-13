<?php
require_once __DIR__.'/_layout.php';
$user = current_user();
$userId = (int)($user['id'] ?? 0);
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $new2 = (string)($_POST['new_password2'] ?? '');
        if (!$user || !password_verify($current, (string)($user['password'] ?? ''))) {
            $err = 'Mevcut şifre hatalı.';
        } elseif ($new !== $new2) {
            $err = 'Yeni şifreler eşleşmiyor.';
        } else {
            $policy = omurga_password_policy($new, $userId);
            if (!$policy['ok']) {
                $err = implode(' ', $policy['errors']);
            } else {
                db()->prepare('UPDATE '.table_name('users').' SET password=?, password_changed_at=NOW() WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
                log_activity('auth.password_change','Kullanıcı kendi şifresini değiştirdi.', $userId);
                omurga_security_log('account.password_changed', ['user_id'=>$userId, 'ip'=>omurga_client_ip()]);
                if (!headers_sent()) @session_regenerate_id(true);
                $msg = 'Şifreniz güncellendi.';
                $st=db()->prepare('SELECT * FROM '.table_name('users').' WHERE id=? LIMIT 1');
                $st->execute([$userId]);
                $user=$st->fetch() ?: $user;
            }
        }
    } elseif ($action === 'save_policy' && (can('settings.manage') || current_user_role()==='admin' || current_user_role()==='super_admin')) {
        update_setting('account_password_reset_enabled', !empty($_POST['account_password_reset_enabled']) ? '1' : '0');
        update_setting('account_password_reset_minutes', (string)max(10, min(1440, (int)($_POST['account_password_reset_minutes'] ?? 60))));
        update_setting('account_password_min_length', (string)max(8, min(64, (int)($_POST['account_password_min_length'] ?? 8))));
        $msg = 'Hesap güvenliği ayarları kaydedildi.';
    }
}

$attempts=[]; $resetRows=[];
try{
    $t=table_name('login_attempts');
    $st=db()->prepare("SELECT * FROM $t WHERE user_id=? OR username=? OR username=? ORDER BY created_at DESC LIMIT 20");
    $st->execute([$userId, mb_strtolower((string)($user['username'] ?? '')), mb_strtolower((string)($user['email'] ?? ''))]);
    $attempts=$st->fetchAll();
}catch(Throwable $e){}
try{
    $t=table_name('password_resets');
    $st=db()->prepare("SELECT * FROM $t WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
    $st->execute([$userId]);
    $resetRows=$st->fetchAll();
}catch(Throwable $e){}
$canManage = can('settings.manage') || current_user_role()==='admin' || current_user_role()==='super_admin';
?>
<div class="page-head compact-head">
  <div><h1>Hesap Güvenliği</h1><p>Şifre, oturum ve hesap güvenliği durumunuzu buradan takip edebilirsiniz.</p></div>
  <a class="btn" href="login-security.php">Giriş kayıtları</a>
</div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<div class="stat-grid">
  <div class="stat"><b>Son giriş</b><strong><?=e($user['last_login_at'] ?? 'Yok')?></strong><small><?=e($user['last_login_ip'] ?? '')?></small></div>
  <div class="stat"><b>Şifre değişimi</b><strong><?=e($user['password_changed_at'] ?? 'Kayıt yok')?></strong><small>Güçlü şifre önerilir</small></div>
  <div class="stat"><b>2FA Hazırlığı</b><strong><?=!empty($user['two_factor_enabled'])?'Aktif':'Hazır'?></strong><small>Bu sürümde alanlar hazırlandı</small></div>
  <div class="stat"><b>Reset kayıtları</b><strong><?=count($resetRows)?></strong><small>Son 10 kayıt</small></div>
</div>

<div class="grid two">
  <section class="card">
    <h2>Şifre Değiştir</h2>
    <form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="change_password">
      <label>Mevcut şifre<input type="password" name="current_password" required></label>
      <label>Yeni şifre<input type="password" name="new_password" required></label>
      <label>Yeni şifre tekrar<input type="password" name="new_password2" required></label>
      <p class="muted">En az <?=e(setting('account_password_min_length','8'))?> karakter, harf ve rakam kullanmanız önerilir.</p>
      <button class="btn primary">Şifreyi Güncelle</button>
    </form>
  </section>
  <section class="card">
    <h2>2FA Hazırlığı</h2>
    <p>İki aşamalı doğrulama için kullanıcı alanları ve güvenli saklama altyapısı hazırlandı. Gerçek TOTP ekranı sonraki güvenlik sürümünde açılacak.</p>
    <div class="badge">two_factor_enabled</div>
    <div class="badge">two_factor_secret</div>
    <div class="badge">recovery_codes</div>
  </section>
</div>

<?php if($canManage): ?>
<section class="card">
  <h2>Hesap Güvenliği Ayarları</h2>
  <form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="action" value="save_policy">
    <label><input type="checkbox" name="account_password_reset_enabled" value="1" <?=setting('account_password_reset_enabled','1')==='1'?'checked':''?>> Şifremi unuttum sistemi aktif</label>
    <label>Şifre sıfırlama geçerlilik süresi / dakika<input type="number" name="account_password_reset_minutes" min="10" max="1440" value="<?=e(setting('account_password_reset_minutes','60'))?>"></label>
    <label>Minimum şifre uzunluğu<input type="number" name="account_password_min_length" min="8" max="64" value="<?=e(setting('account_password_min_length','8'))?>"></label>
    <button class="btn primary">Ayarları Kaydet</button>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <h2>Son Giriş Denemeleri</h2>
  <div class="table-wrap"><table class="status-table"><thead><tr><th>Tarih</th><th>Kullanıcı</th><th>IP</th><th>Durum</th><th>Kilit</th></tr></thead><tbody>
  <?php foreach($attempts as $a): ?><tr><td><?=e($a['created_at'] ?? '')?></td><td><?=e($a['username'] ?? '')?></td><td><?=e($a['ip'] ?? '')?></td><td><?=!empty($a['success'])?'<span class="badge success">Başarılı</span>':'<span class="badge danger">Hatalı</span>'?></td><td><?=e($a['locked_until'] ?? '')?></td></tr><?php endforeach; ?>
  <?php if(!$attempts): ?><tr><td colspan="5">Kayıt yok.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>

<section class="card">
  <h2>Şifre Sıfırlama Kayıtları</h2>
  <div class="table-wrap"><table class="status-table"><thead><tr><th>Tarih</th><th>E-posta</th><th>Süre Sonu</th><th>Kullanıldı</th><th>IP</th></tr></thead><tbody>
  <?php foreach($resetRows as $r): ?><tr><td><?=e($r['created_at'] ?? '')?></td><td><?=e($r['email'] ?? '')?></td><td><?=e($r['expires_at'] ?? '')?></td><td><?=e($r['used_at'] ?? 'Hayır')?></td><td><?=e($r['ip_address'] ?? '')?></td></tr><?php endforeach; ?>
  <?php if(!$resetRows): ?><tr><td colspan="5">Kayıt yok.</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php require __DIR__.'/_footer.php'; ?>
