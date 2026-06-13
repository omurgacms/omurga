<?php
require_once dirname(__DIR__) . '/bootstrap.php';
if (!omurga_is_installed()) {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/login.php')), '/');
    $base = preg_replace('#/admin$#', '', $dir) ?: '';
    header('Location: '.($base === '' ? '' : $base).'/install/');
    exit;
}
omurga_migrate();

function omurga_login_url(array $params=[]): string {
    $base='admin/login.php';
    return omurga_url($base.($params ? '?'.http_build_query($params) : ''));
}
function omurga_auth_registration_enabled(): bool {
    return setting('membership_registration_enabled', '0') === '1';
}
function omurga_auth_default_role(): string {
    $role = omurga_normalize_role_key((string)setting('membership_default_role', 'member'));
    $labels = omurga_role_labels();
    return isset($labels[$role]) && $role !== 'super_admin' ? $role : 'member';
}
function omurga_auth_default_status(): string {
    $status = (string)setting('membership_default_status', 'pending');
    return in_array($status, ['active','pending'], true) ? $status : 'pending';
}

if (is_admin_logged_in()) redirect('admin/');

$tab = (string)($_GET['tab'] ?? $_POST['tab'] ?? 'login');
if (isset($_GET['reset'])) $tab = 'reset';
if (!in_array($tab, ['login','register','forgot','reset'], true)) $tab = 'login';
$error='';
$success='';
$resetToken = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['reset'] ?? $_POST['reset_token'] ?? ''));
$lockedRemaining = 0;
if ($tab === 'login') {
    $probeUser = trim((string)($_POST['username'] ?? $_GET['username'] ?? ''));
    $lockedRemaining = $probeUser !== '' ? omurga_login_lock_remaining($probeUser) : 0;
    if ($lockedRemaining > 0) {
        $error='Çok fazla hatalı giriş denemesi oldu. Lütfen '.ceil($lockedRemaining/60).' dakika sonra tekrar deneyin.';
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
    verify_csrf();
    if ($tab === 'login') {
        $username=trim($_POST['username'] ?? ''); $password=$_POST['password'] ?? '';
        $stmt=db()->prepare('SELECT * FROM '.table_name('users').' WHERE (username=? OR email=?) AND status="active" LIMIT 1');
        $stmt->execute([$username,$username]); $user=$stmt->fetch();
        if ($user && password_verify($password,$user['password'])) {
            omurga_record_login_attempt($username, true, (int)$user['id']);
            if (!headers_sent()) @session_regenerate_id(true);
            $_SESSION['omurga_user_id']=$user['id']; $_SESSION['omurga_user_name']=$user['name']; $_SESSION['omurga_last_seen']=time();
            db()->prepare('UPDATE '.table_name('users').' SET last_login_at=NOW(), last_login_ip=? WHERE id=?')->execute([omurga_client_ip(), $user['id']]);
            log_activity('auth.login','Panele giriş yapıldı.', (int)$user['id']);
            redirect('admin/');
        } else {
            omurga_record_login_attempt($username, false, null);
            log_activity('auth.failed','Hatalı giriş denemesi: '.$username, null);
            $remaining = omurga_login_lock_remaining($username);
            $error = $remaining > 0 ? 'Çok fazla hatalı giriş denemesi oldu. Lütfen '.ceil($remaining/60).' dakika sonra tekrar deneyin.' : 'Kullanıcı adı veya şifre hatalı.';
        }
    } elseif ($tab === 'register') {
        if (!omurga_auth_registration_enabled()) {
            $error='Kayıt işlemi şu anda kapalı.';
        } else {
            $name=trim((string)($_POST['name'] ?? ''));
            $email=trim((string)($_POST['email'] ?? ''));
            $username=trim((string)($_POST['username'] ?? ''));
            $password=(string)($_POST['password'] ?? '');
            $password2=(string)($_POST['password2'] ?? '');
            if($name==='' || $email==='' || $username==='' || $password==='') $error='Tüm zorunlu alanları doldurun.';
            elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) $error='Geçerli bir e-posta adresi girin.';
            elseif(!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) $error='Kullanıcı adı en az 3 karakter olmalı; harf, rakam, nokta, tire ve alt çizgi kullanabilirsiniz.';
            elseif(strlen($password)<6) $error='Şifre en az 6 karakter olmalı.';
            elseif($password!==$password2) $error='Şifreler eşleşmiyor.';
            else {
                $st=db()->prepare('SELECT id FROM '.table_name('users').' WHERE username=? OR email=? LIMIT 1');
                $st->execute([$username,$email]);
                if($st->fetch()) $error='Bu kullanıcı adı veya e-posta zaten kullanılıyor.';
                else {
                    $role=omurga_auth_default_role();
                    $status=omurga_auth_default_status();
                    $hash=password_hash($password, PASSWORD_DEFAULT);
                    $ins=db()->prepare('INSERT INTO '.table_name('users').' (name,email,username,password,role,status) VALUES (?,?,?,?,?,?)');
                    $ins->execute([$name,$email,$username,$hash,$role,$status]);
                    $userId=(int)db()->lastInsertId();
                    log_activity('auth.register','Yeni kayıt oluşturuldu: '.$username, $userId);
                    if(function_exists('om_notify')) om_notify('Yeni kullanıcı kaydı', $name.' kayıt oldu.', 'info', 'admin/users.php?edit='.$userId, null);
                    if($status==='active') {
                        $success='Kayıt tamamlandı. Şimdi giriş yapabilirsiniz.';
                        $tab='login';
                    } else {
                        $success='Kayıt alındı. Hesabınız yönetici onayından sonra aktifleşecek.';
                    }
                }
            }
        }
    } elseif ($tab === 'forgot') {
        if(setting('account_password_reset_enabled','1') !== '1') {
            $error='Şifre sıfırlama şu anda kapalı.';
        } else {
            $email=trim((string)($_POST['email'] ?? ''));
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $error='Geçerli bir e-posta adresi girin.';
            else {
                $st=db()->prepare('SELECT id,name,email FROM '.table_name('users').' WHERE email=? AND status="active" LIMIT 1');
                $st->execute([$email]);
                $user=$st->fetch();
                if($user){
                    $token=omurga_create_password_reset($user);
                    if($token){
                        $link=omurga_login_url(['reset'=>$token]);
                        $minutes=omurga_password_reset_expire_minutes();
                        $body="Merhaba {$user['name']},

Şifrenizi yenilemek için bağlantı:
{$link}

Bu bağlantı {$minutes} dakika geçerlidir ve tek kullanımlıktır.";
                        $mailSent=function_exists('om_mail') ? om_mail($user['email'], 'Omurga Şifre Sıfırlama', $body) : false;
                        if($mailSent) $success='Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.';
                        else $success='Şifre sıfırlama bağlantısı oluşturuldu. Mail ayarları aktif değilse bağlantı gönderilemeyebilir.';
                    } else {
                        $error='Sıfırlama bağlantısı oluşturulamadı.';
                    }
                } else {
                    $success='Eğer bu e-posta ile aktif bir hesap varsa sıfırlama bağlantısı gönderilecektir.';
                }
            }
        }
    } elseif ($tab === 'reset') {
        $password=(string)($_POST['password'] ?? '');
        $password2=(string)($_POST['password2'] ?? '');
        if(!$resetToken) $error='Geçersiz sıfırlama bağlantısı.';
        elseif($password!==$password2) $error='Şifreler eşleşmiyor.';
        else {
            $result=omurga_consume_password_reset($resetToken, $password);
            if(!empty($result['ok'])) { $success=$result['message']; $tab='login'; }
            else $error=$result['message'] ?? 'Şifre yenilenemedi.';
        }
    }
}
$registerEnabled = omurga_auth_registration_enabled();
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Omurga Giriş</title><link rel="icon" type="image/png" href="../assets/images/omurga-icon.png"><link rel="apple-touch-icon" href="../assets/images/omurga-icon.png"><link rel="stylesheet" href="../assets/css/omurga.css?v=1.2.0-rc.1"></head><body>
<div class="login-wrap">
  <div class="login-card om-auth-card">
    <div class="brand"><div class="brand-mark brand-icon"><img src="../assets/images/omurga-icon.png" alt="" aria-hidden="true"></div><div><h1>Omurga Panel</h1><p>Yayın yönetiminin ana yapısı</p></div></div>
    <div class="om-auth-tabs">
      <a class="<?= $tab==='login'?'active':'' ?>" href="<?=e(omurga_login_url(['tab'=>'login']))?>">Giriş</a>
      <?php if($registerEnabled): ?><a class="<?= $tab==='register'?'active':'' ?>" href="<?=e(omurga_login_url(['tab'=>'register']))?>">Kayıt Ol</a><?php endif; ?>
      <a class="<?= $tab==='forgot'?'active':'' ?>" href="<?=e(omurga_login_url(['tab'=>'forgot']))?>">Şifremi Unuttum</a>
    </div>
    <?php if(isset($_GET['installed'])): ?><div class="alert success">Kurulum tamamlandı. Şimdi giriş yapabilirsiniz.</div><?php endif; ?>
    <?php if(isset($_GET['timeout'])): ?><div class="alert error">Oturum süresi doldu. Lütfen tekrar giriş yapın.</div><?php endif; ?>
    <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
    <?php if($tab==='login'): ?>
      <form method="post"><input type="hidden" name="tab" value="login"><?=csrf_field()?>
        <label>Kullanıcı adı veya e-posta<input name="username" required></label>
        <label>Şifre<input type="password" name="password" required></label>
        <button class="btn primary" style="width:100%;justify-content:center">Giriş Yap</button>
      </form>
    <?php elseif($tab==='register' && $registerEnabled): ?>
      <form method="post"><input type="hidden" name="tab" value="register"><?=csrf_field()?>
        <label>Ad Soyad<input name="name" required></label>
        <label>E-posta<input type="email" name="email" required></label>
        <label>Kullanıcı adı<input name="username" required></label>
        <label>Şifre<input type="password" name="password" required></label>
        <label>Şifre tekrar<input type="password" name="password2" required></label>
        <button class="btn primary" style="width:100%;justify-content:center">Kayıt Ol</button>
      </form>
    <?php elseif($tab==='forgot'): ?>
      <form method="post"><input type="hidden" name="tab" value="forgot"><?=csrf_field()?>
        <label>E-posta adresiniz<input type="email" name="email" required></label>
        <button class="btn primary" style="width:100%;justify-content:center">Sıfırlama Bağlantısı Gönder</button>
      </form>
    <?php elseif($tab==='reset'): ?>
      <form method="post"><input type="hidden" name="tab" value="reset"><input type="hidden" name="reset_token" value="<?=e($resetToken)?>"><?=csrf_field()?>
        <label>Yeni şifre<input type="password" name="password" required></label><small class="om-auth-hint">En az 8 karakter, harf ve rakam kullanmanız önerilir.</small><small class="om-auth-hint">En az 8 karakter, harf ve rakam kullanmanız önerilir.</small>
        <label>Yeni şifre tekrar<input type="password" name="password2" required></label>
        <button class="btn primary" style="width:100%;justify-content:center">Şifreyi Yenile</button>
      </form>
    <?php else: ?>
      <div class="alert error">Kayıt işlemi kapalı.</div>
    <?php endif; ?>
  </div>
</div>
<style>.om-auth-tabs{display:flex;gap:6px;margin:14px 0 16px;flex-wrap:wrap}.om-auth-tabs a{padding:8px 10px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#374151;background:#fff;font-size:13px}.om-auth-tabs a.active{border-color:#f97316;color:#9a3412;background:#fff7ed}.om-auth-card form{display:grid;gap:10px}.om-auth-card label{display:grid;gap:5px;font-size:13px;color:#374151}.om-auth-card input{width:100%;box-sizing:border-box}.om-auth-hint{display:block;color:#6b7280;font-size:12px;margin-top:-4px}</style>
</body></html>
