<?php
if (!defined('OMURGA_INIT')) { http_response_code(403); exit; }

function omurga_user_center_url(string $tab='profile', array $extra=[]): string {
    $params = array_merge(['tab'=>$tab], $extra);
    return omurga_url('hesabim'.($params ? '?'.http_build_query($params) : ''));
}

function omurga_user_center_can_write(): bool {
    return can('posts.create') || can('posts.edit') || can('posts.edit_own') || can('posts.submit_review');
}

function omurga_user_center_tabs(): array {
    $tabs = ['profile'=>om_t('user_center.profile','Profilim')];
    if (omurga_user_center_can_write()) {
        $tabs['posts'] = om_t('user_center.my_posts','Yazılarım');
        $tabs['submit'] = om_t('user_center.new_post','Yeni Yazı');
    }
    $tabs['notifications'] = om_t('user_center.notifications','Bildirimler');
    $tabs['settings'] = om_t('user_center.settings','Ayarlar');
    return $tabs;
}

function omurga_user_center_tab(?string $requested=null): string {
    $requested = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($requested ?: ($_GET['tab'] ?? 'profile'))));
    $tabs = omurga_user_center_tabs();
    return isset($tabs[$requested]) ? $requested : 'profile';
}

function omurga_user_center_notice(string $type, string $message): void {
    $_SESSION['omurga_user_center_notice'] = ['type'=>$type, 'message'=>$message];
}

function omurga_user_center_take_notice(): array {
    $notice = $_SESSION['omurga_user_center_notice'] ?? [];
    unset($_SESSION['omurga_user_center_notice']);
    return is_array($notice) ? $notice : [];
}

function omurga_user_center_current_user(): ?array {
    return function_exists('current_user') ? current_user() : null;
}

function omurga_user_center_posts(int $userId, int $limit=50): array {
    try {
        $posts = table_name('posts');
        $st = db()->prepare("SELECT id,title,slug,status,type,created_at,updated_at,published_at FROM $posts WHERE author_id=? AND type<>'page' ORDER BY COALESCE(updated_at,created_at) DESC,id DESC LIMIT ".max(1, min(100, $limit)));
        $st->execute([$userId]);
        return $st->fetchAll();
    } catch (Throwable $e) { omurga_write_error($e); return []; }
}

function omurga_user_center_post_for_edit(int $postId, int $userId): ?array {
    if ($postId <= 0 || !omurga_user_center_can_write()) return null;
    try {
        $posts = table_name('posts');
        $where = 'id=? AND type<>? AND author_id=?';
        $params = [$postId, 'page', $userId];
        $st = db()->prepare("SELECT * FROM $posts WHERE $where LIMIT 1");
        $st->execute($params);
        return $st->fetch() ?: null;
    } catch (Throwable $e) { omurga_write_error($e); return null; }
}

function omurga_user_center_handle(): void {
    static $handled = false;
    if ($handled) return;
    $handled = true;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || empty($_POST['user_center_action'])) return;
    $token = (string)($_POST['_csrf'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        omurga_user_center_notice('error', om_t('user_center.csrf_error','Güvenlik doğrulaması başarısız.'));
        return;
    }
    $action = (string)$_POST['user_center_action'];
    $user = omurga_user_center_current_user();
    try {
        if (!$user && $action === 'login') {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $st = db()->prepare('SELECT * FROM '.table_name('users').' WHERE (username=? OR email=?) AND status="active" LIMIT 1');
            $st->execute([$username, $username]);
            $found = $st->fetch();
            if (!$found || !password_verify($password, $found['password'])) throw new RuntimeException(om_t('user_center.login_failed','Kullanıcı adı veya şifre hatalı.'));
            $_SESSION['omurga_user_id'] = (int)$found['id'];
            $_SESSION['omurga_user_name'] = $found['name'];
            $_SESSION['omurga_last_seen'] = time();
            db()->prepare('UPDATE '.table_name('users').' SET last_login_at=NOW(), last_login_ip=? WHERE id=?')->execute([$_SERVER['REMOTE_ADDR'] ?? '', (int)$found['id']]);
            log_activity('auth.login','Ön yüz kullanıcı merkezinden giriş yapıldı.', (int)$found['id']);
            omurga_user_center_notice('success', om_t('user_center.login_ok','Giriş yapıldı.'));
        } elseif (!$user && $action === 'register') {
            if (setting('membership_registration_enabled','0') !== '1') throw new RuntimeException(om_t('user_center.register_closed','Kayıt işlemi şu anda kapalı.'));
            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $password2 = (string)($_POST['password2'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username) || strlen($password) < 6 || $password !== $password2) {
                throw new RuntimeException(om_t('user_center.register_invalid','Kayıt bilgilerini kontrol edin.'));
            }
            $u = table_name('users');
            $st = db()->prepare("SELECT id FROM $u WHERE username=? OR email=? LIMIT 1");
            $st->execute([$username, $email]);
            if ($st->fetch()) throw new RuntimeException(om_t('user_center.register_exists','Bu kullanıcı adı veya e-posta zaten kayıtlı.'));
            $role = 'member';
            $status = setting('membership_default_status','pending') === 'active' ? 'active' : 'pending';
            db()->prepare("INSERT INTO $u (name,email,username,password,role,status) VALUES (?,?,?,?,?,?)")->execute([$name, $email, $username, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
            omurga_notify_admins(om_t('user_center.register_notice_title','Yeni kullanıcı kaydı'), $username, 'user', 'admin/users.php');
            omurga_user_center_notice('success', $status === 'active' ? om_t('user_center.register_ok','Kayıt tamamlandı. Giriş yapabilirsiniz.') : om_t('user_center.register_pending','Kayıt alındı. Hesabınız onay bekliyor.'));
        } elseif (!$user) {
            omurga_user_center_notice('error', om_t('user_center.login_required','Bu alan için giriş yapmalısınız.'));
        } elseif ($action === 'profile') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException(om_t('user_center.profile_invalid','Ad ve geçerli e-posta zorunludur.'));
            $u = table_name('users');
            $st = db()->prepare("SELECT id FROM $u WHERE email=? AND id<>? LIMIT 1");
            $st->execute([$email, (int)$user['id']]);
            if ($st->fetch()) throw new RuntimeException(om_t('user_center.email_exists','Bu e-posta başka kullanıcıda kayıtlı.'));
            db()->prepare("UPDATE $u SET name=?, email=? WHERE id=?")->execute([$name, $email, (int)$user['id']]);
            $_SESSION['omurga_user_name'] = $name;
            omurga_user_center_notice('success', om_t('user_center.profile_saved','Profiliniz kaydedildi.'));
        } elseif ($action === 'password') {
            $pass = (string)($_POST['password'] ?? '');
            $pass2 = (string)($_POST['password2'] ?? '');
            if (strlen($pass) < 6 || $pass !== $pass2) throw new RuntimeException(om_t('user_center.password_invalid','Şifre en az 6 karakter olmalı ve tekrar ile eşleşmelidir.'));
            db()->prepare('UPDATE '.table_name('users').' SET password=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$user['id']]);
            omurga_user_center_notice('success', om_t('user_center.password_saved','Şifreniz güncellendi.'));
        } elseif ($action === 'post_save') {
            if (!omurga_user_center_can_write()) throw new RuntimeException(om_t('user_center.no_write_permission','Yazı gönderme yetkiniz yok.'));
            $postId = (int)($_POST['post_id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            if ($title === '' || $content === '') throw new RuntimeException(om_t('user_center.post_invalid','Başlık ve içerik zorunludur.'));
            $existing = $postId ? omurga_user_center_post_for_edit($postId, (int)$user['id']) : null;
            if ($postId && !$existing) throw new RuntimeException(om_t('user_center.post_not_found','Yazı bulunamadı veya düzenleme yetkiniz yok.'));
            $status = can('posts.publish') ? omurga_normalize_post_status($_POST['status'] ?? ($existing['status'] ?? 'draft')) : 'pending';
            $publishedAt = $status === 'published' ? (($existing['published_at'] ?? '') ?: date('Y-m-d H:i:s')) : null;
            $slug = omurga_unique_slug(slugify(trim((string)($_POST['slug'] ?? '')) ?: $title), $postId);
            $fields = [
                'title'=>$title,
                'slug'=>$slug,
                'spot'=>trim((string)($_POST['spot'] ?? '')),
                'content'=>$content,
                'editor_type'=>'classic',
                'content_blocks'=>'',
                'type'=>$existing['type'] ?? primary_content_type(),
                'status'=>$status,
                'author_id'=>(int)$user['id'],
                'comments_enabled'=>om_default_comments_enabled() ? 1 : 0,
                'published_at'=>$publishedAt,
            ];
            $posts = table_name('posts');
            if ($existing) {
                $sets=[]; $params=[];
                foreach ($fields as $k=>$v) { if ($k === 'author_id') continue; $sets[]="$k=?"; $params[]=$v; }
                $params[]=$postId;
                db()->prepare("UPDATE $posts SET ".implode(',', $sets).", updated_at=NOW() WHERE id=?")->execute($params);
                $savedId = $postId;
            } else {
                $cols = array_keys($fields);
                $marks = implode(',', array_fill(0, count($cols), '?'));
                db()->prepare("INSERT INTO $posts (".implode(',', $cols).") VALUES ($marks)")->execute(array_values($fields));
                $savedId = (int)db()->lastInsertId();
            }
            omurga_do_action('omurga_after_post_save', $savedId, $fields);
            omurga_notify_admins(om_t('user_center.post_submitted_title','Yeni kullanıcı yazısı'), $title, 'user', 'admin/post-edit.php?id='.$savedId);
            omurga_user_center_notice('success', can('posts.publish') ? om_t('user_center.post_saved','Yazınız kaydedildi.') : om_t('user_center.post_pending','Yazınız incelemeye gönderildi.'));
        } elseif ($action === 'notifications_read') {
            $ids = array_map('intval', (array)($_POST['ids'] ?? []));
            if ($ids) {
                $t = table_name('notifications');
                db()->exec("UPDATE $t SET is_read=1 WHERE user_id=".(int)$user['id']." AND id IN (".implode(',', $ids).")");
            }
            omurga_user_center_notice('success', om_t('user_center.notifications_read','Bildirimler okundu olarak işaretlendi.'));
        }
    } catch (Throwable $e) {
        omurga_write_error($e);
        omurga_user_center_notice('error', $e->getMessage());
    }
}

function omurga_user_center_login_box(): string {
    omurga_user_center_handle();
    $register = setting('membership_registration_enabled','0') === '1';
    $notice = omurga_user_center_take_notice();
    ob_start(); ?>
    <section class="om-user-center om-user-center-login">
      <style>.om-user-center{--om-accent:#f97316;--om-line:#e5e7eb;display:block;width:100%;box-sizing:border-box}.om-user-center *{box-sizing:border-box}.om-user-card{border:1px solid var(--om-line);border-radius:14px;background:#fff;padding:18px;margin-bottom:14px}.om-user-center label{display:grid;gap:6px;margin:10px 0;font-weight:700;color:#334155}.om-user-center input{width:100%;border:1px solid var(--om-line);border-radius:10px;padding:10px 12px;font:inherit}.om-user-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.om-user-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--om-line);border-radius:10px;background:#fff;color:#1f2937;text-decoration:none;padding:10px 13px;font-weight:800;cursor:pointer}.om-user-btn.primary{background:var(--om-accent);border-color:var(--om-accent);color:#fff}.om-user-alert{border-radius:12px;padding:11px 13px;margin-bottom:14px;background:#f8fafc;border:1px solid var(--om-line)}.om-user-alert.success{background:#ecfdf5;border-color:#bbf7d0}.om-user-alert.error{background:#fef2f2;border-color:#fecaca}</style>
      <div class="om-user-card">
        <h2><?=e(om_t('user_center.title','Hesabım'))?></h2>
        <p><?=e(om_t('user_center.login_required','Bu alan için giriş yapmalısınız.'))?></p>
        <?php if(!empty($notice['message'])): ?><div class="om-user-alert <?=e($notice['type'] ?? '')?>"><?=e($notice['message'])?></div><?php endif; ?>
        <form method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="login">
          <label><?=e(om_t('user_center.username','Kullanıcı adı veya e-posta'))?><input name="username" required></label>
          <label><?=e(om_t('user_center.password_login','Şifre'))?><input type="password" name="password" required></label>
          <div class="om-user-actions"><button class="om-user-btn primary"><?=e(om_t('blocks.login','Giriş'))?></button></div>
        </form>
      </div>
      <?php if($register): ?><form class="om-user-card" method="post"><?=csrf_field()?><input type="hidden" name="user_center_action" value="register">
        <h3><?=e(om_t('blocks.register','Kayıt'))?></h3>
        <label><?=e(om_t('user_center.name','Ad Soyad'))?><input name="name" required></label>
        <label><?=e(om_t('user_center.email','E-posta'))?><input type="email" name="email" required></label>
        <label><?=e(om_t('user_center.username','Kullanıcı adı veya e-posta'))?><input name="username" required></label>
        <label><?=e(om_t('user_center.password_login','Şifre'))?><input type="password" name="password" required></label>
        <label><?=e(om_t('user_center.password_repeat','Şifre tekrar'))?><input type="password" name="password2" required></label>
        <div class="om-user-actions"><button class="om-user-btn"><?=e(om_t('blocks.register','Kayıt'))?></button></div>
      </form><?php endif; ?>
    </section>
    <?php return (string)ob_get_clean();
}

function omurga_user_center_render(string $mode='center', array $settings=[], array $context=[]): string {
    omurga_user_center_handle();
    $user = omurga_user_center_current_user();
    if (!$user) return omurga_user_center_login_box();
    $mode = in_array($mode, ['center','profile','posts','submit','notifications'], true) ? $mode : 'center';
    if (in_array($mode, ['posts','submit'], true) && !omurga_user_center_can_write()) return '';
    $tab = $mode === 'center' ? omurga_user_center_tab() : $mode;
    $tabs = omurga_user_center_tabs();
    $notice = omurga_user_center_take_notice();
    $editPost = ($tab === 'submit') ? omurga_user_center_post_for_edit((int)($_GET['edit'] ?? 0), (int)$user['id']) : null;
    ob_start();
    include OMURGA_ROOT.'/core/user-center-view.php';
    return (string)ob_get_clean();
}
