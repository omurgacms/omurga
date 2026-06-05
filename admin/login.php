<?php
require_once dirname(__DIR__) . '/bootstrap.php';
if (!omurga_is_installed()) {
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/login.php')), '/');
    $base = preg_replace('#/admin$#', '', $dir) ?: '';
    header('Location: '.($base === '' ? '' : $base).'/install/');
    exit;
}
omurga_migrate_07();
if (is_admin_logged_in()) redirect('admin/');
$error='';
$lockedUntil = (int)($_SESSION['login_locked_until'] ?? 0);
if ($lockedUntil && time() < $lockedUntil) {
    $error='Çok fazla hatalı giriş denemesi oldu. Lütfen birkaç dakika sonra tekrar deneyin.';
}
if ($_SERVER['REQUEST_METHOD']==='POST' && !$error) {
    $username=trim($_POST['username'] ?? ''); $password=$_POST['password'] ?? '';
    $stmt=db()->prepare('SELECT * FROM '.table_name('users').' WHERE (username=? OR email=?) AND status="active" LIMIT 1');
    $stmt->execute([$username,$username]); $user=$stmt->fetch();
    if ($user && password_verify($password,$user['password'])) {
        $_SESSION['login_attempts']=0; unset($_SESSION['login_locked_until']);
        $_SESSION['omurga_user_id']=$user['id']; $_SESSION['omurga_user_name']=$user['name']; $_SESSION['omurga_last_seen']=time();
        db()->prepare('UPDATE '.table_name('users').' SET last_login_at=NOW(), last_login_ip=? WHERE id=?')->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
        log_activity('auth.login','Panele giriş yapıldı.', (int)$user['id']);
        redirect('admin/');
    } else {
        $_SESSION['login_attempts']=(int)($_SESSION['login_attempts'] ?? 0)+1;
        if($_SESSION['login_attempts']>=5){ $_SESSION['login_locked_until']=time()+300; }
        log_activity('auth.failed','Hatalı giriş denemesi: '.$username, null);
        $error='Kullanıcı adı veya şifre hatalı.';
    }
}
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Omurga Giriş</title><link rel="stylesheet" href="../assets/css/omurga.css"></head><body><div class="login-wrap"><form class="login-card" method="post"><div class="brand"><div class="brand-mark">O</div><div><h1>Omurga Panel</h1><p>Yayın yönetiminin ana yapısı</p></div></div><?php if(isset($_GET['installed'])): ?><div class="alert success">Kurulum tamamlandı. Şimdi giriş yapabilirsiniz.</div><?php endif; ?><?php if(isset($_GET['timeout'])): ?><div class="alert error">Oturum süresi doldu. Lütfen tekrar giriş yapın.</div><?php endif; ?><?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?><label>Kullanıcı adı veya e-posta<input name="username" required></label><label>Şifre<input type="password" name="password" required></label><button class="btn primary" style="width:100%;justify-content:center">Giriş Yap</button></form></div></body></html>
