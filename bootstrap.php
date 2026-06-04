<?php
session_start();

define('OMURGA_ROOT', __DIR__);
define('OMURGA_VERSION', '1.0.0-beta');
define('OMURGA_SCHEMA_VERSION', '4.0.0');
define('OMURGA_INIT', true);
require_once OMURGA_ROOT.'/core/hooks.php';
require_once OMURGA_ROOT.'/core/BlockRegistry.php';
ini_set('display_errors', '0');
error_reporting(E_ALL);

// PHP 7.4 / eksik eklenti uyumlulukları
// Bazı hostinglerde mbstring veya PHP 8 string yardımcıları kapalı olabiliyor.
// Admin yazı ekleme ekranında 500 hatası vermemesi için güvenli yedekler.
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null): int {
        return strlen((string)$string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null): string {
        return $length === null ? substr((string)$string, (int)$start) : substr((string)$string, (int)$start, (int)$length);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null): string {
        return strtolower((string)$string);
    }
}
if (!function_exists('mb_convert_case')) {
    if (!defined('MB_CASE_TITLE')) define('MB_CASE_TITLE', 2);
    function mb_convert_case($string, $mode, $encoding = null): string {
        return ucwords(strtolower((string)$string));
    }
}


function omurga_config(): array {
    $file = OMURGA_ROOT . '/config.php';
    if (!file_exists($file)) return require OMURGA_ROOT . '/config.sample.php';
    return require $file;
}
function omurga_is_installed(): bool { $c=omurga_config(); return !empty($c['installed']) && file_exists(OMURGA_ROOT.'/storage/installed.lock'); }
function guess_base_url(): string { $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||(($_SERVER['SERVER_PORT']??'')==443); $scheme=$https?'https':'http'; $host=$_SERVER['HTTP_HOST']??'localhost'; $script=$_SERVER['SCRIPT_NAME']??'/index.php'; $dir=rtrim(str_replace('\\','/',dirname($script)),'/'); if($dir==='/'||$dir==='.')$dir=''; return $scheme.'://'.$host.$dir; }
function omurga_url(string $path=''): string { $c=omurga_config(); $base=rtrim($c['app_url']?:guess_base_url(),'/'); return $base.'/'.ltrim($path,'/'); }
function db(): PDO { static $pdo=null; if($pdo)return $pdo; $c=omurga_config(); $d=$c['db']; $dsn="mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}"; $pdo=new PDO($dsn,$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); return $pdo; }
function table_name(string $name): string { $c=omurga_config(); return preg_replace('/[^a-zA-Z0-9_]/','',$c['db']['prefix'].$name); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path, int $statusCode = 302): void {
    $statusCode = in_array($statusCode, [301,302,303,307,308], true) ? $statusCode : 302;
    header('Location: '.omurga_url($path), true, $statusCode);
    exit;
}
function is_admin_logged_in(): bool { return !empty($_SESSION['omurga_user_id']); }

function require_admin(): void {
    if(!is_admin_logged_in()) redirect('admin/login.php');
    omurga_migrate();
    if(empty($_SESSION['omurga_last_seen'])) $_SESSION['omurga_last_seen']=time();
    if(time() - (int)$_SESSION['omurga_last_seen'] > 7200){ session_destroy(); redirect('admin/login.php?timeout=1'); }
    $_SESSION['omurga_last_seen']=time();
}
function current_user(): ?array {
    if(empty($_SESSION['omurga_user_id'])) return null;
    static $user=null; if($user) return $user;
    try{ $st=db()->prepare('SELECT * FROM '.table_name('users').' WHERE id=? LIMIT 1'); $st->execute([(int)$_SESSION['omurga_user_id']]); $user=$st->fetch() ?: null; return $user; }catch(Throwable $e){ return null; }
}
function current_user_role(): string { $u=current_user(); return $u['role'] ?? 'guest'; }
function can(string $cap): bool {
    $role=current_user_role();
    $map=omurga_role_capabilities();
    if(in_array('*',$map[$role]??[],true) || in_array($cap,$map[$role]??[],true)) return true;
    $registered=$GLOBALS['omurga_plugin_permissions'][$cap] ?? null;
    if($registered && in_array($role, $registered['default_roles'] ?? [], true)) return true;
    return false;
}
function omurga_role_labels(): array {
    return [
        'admin'=>'Yönetici',
        'editor'=>'Editör',
        'reporter'=>'Muhabir',
        'author'=>'Yazar',
        'ad_manager'=>'Reklam Yöneticisi',
        'design_manager'=>'Tasarım Yöneticisi',
        'plugin_manager'=>'Eklenti Yöneticisi',
    ];
}
function omurga_role_capabilities(): array {
    return [
        'admin'=>['*'],
        'editor'=>['posts.view','posts.create','posts.edit','posts.publish','posts.delete','posts.review','comments.manage','categories.manage','media.manage','forms.manage','seo.view'],
        'reporter'=>['posts.view','posts.create','posts.edit_own','posts.submit_review','media.manage','seo.view'],
        'author'=>['posts.view','posts.create','posts.edit_own','posts.submit_review','media.manage','seo.view'],
        'ad_manager'=>['ads.manage'],
        'design_manager'=>['themes.manage','layout.manage','blocks.manage','menus.manage','design.manage'],
        'plugin_manager'=>['plugins.manage','blocks.manage','plugin_api.manage'],
    ];
}
function omurga_status_labels(): array {
    return ['draft'=>'Taslak','pending'=>'İncelemede','published'=>'Yayında','scheduled'=>'Planlandı','archived'=>'Arşivde'];
}
function omurga_status_options_for_current_user(string $current='draft'): array {
    $all=omurga_status_labels();
    if(can('posts.publish')) return $all;
    $allowed=['draft'=>$all['draft'],'pending'=>$all['pending']];
    if(isset($allowed[$current])===false && isset($all[$current])) $allowed[$current]=$all[$current];
    return $allowed;
}
function omurga_normalize_post_status(string $status): string {
    $valid=omurga_status_labels();
    if(!isset($valid[$status])) $status='draft';
    if(!can('posts.publish') && in_array($status,['published','scheduled'],true)) return 'pending';
    return $status;
}
function require_cap(string $cap): void { if(!can($cap)){ render_error_page(403, 'Yetkisiz Erişim', 'Bu işlem için yetkiniz yok.'); } }

function csrf_token(): string { if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if($_SERVER['REQUEST_METHOD']==='POST'){ $t=$_POST['_csrf']??''; if(!$t||!hash_equals($_SESSION['csrf_token']??'', $t)){ http_response_code(419); exit('Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin.'); } } }
function setting(string $key, ?string $default=null): ?string { try{ $s=db()->prepare('SELECT setting_value FROM '.table_name('settings').' WHERE setting_key=? LIMIT 1'); $s->execute([$key]); $r=$s->fetch(); return $r?$r['setting_value']:$default; }catch(Throwable $e){ return $default; } }
function update_setting(string $key,string $value): void { db()->prepare('INSERT INTO '.table_name('settings').' (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$key,$value]); }

function om_comment_statuses(): array {
    return [
        'pending'=>om_t('comments.pending','Bekleyen'),
        'approved'=>om_t('comments.approved','Onaylı'),
        'spam'=>om_t('comments.spam','Spam'),
        'trash'=>om_t('comments.trash','Çöp'),
    ];
}
function om_default_comments_enabled(?string $profile=null): bool {
    $profile = $profile ?: (function_exists('site_type') ? site_type() : 'bos');
    return in_array($profile, ['haber','topluluk'], true);
}
function om_post_comments_enabled(array $post): bool {
    if(array_key_exists('comments_enabled', $post) && $post['comments_enabled'] !== null && $post['comments_enabled'] !== '') return (int)$post['comments_enabled'] === 1;
    return om_default_comments_enabled();
}
function om_comments_count(int $postId): int {
    if($postId<=0) return 0;
    try{
        $t=table_name('comments');
        $st=db()->prepare("SELECT COUNT(*) FROM $t WHERE post_id=? AND status='approved'");
        $st->execute([$postId]);
        return (int)$st->fetchColumn();
    }catch(Throwable $e){ return 0; }
}
function om_comment_rows(int $postId, string $status='approved', int $limit=200): array {
    if($postId<=0) return [];
    try{
        $t=table_name('comments');
        $limit=max(1,min(500,$limit));
        $st=db()->prepare("SELECT * FROM $t WHERE post_id=? AND status=? ORDER BY COALESCE(parent_id,0) ASC, created_at ASC, id ASC LIMIT $limit");
        $st->execute([$postId,$status]);
        return $st->fetchAll();
    }catch(Throwable $e){ return []; }
}
function om_comments_list(int $postId): string {
    $rows=om_comment_rows($postId, 'approved');
    $out='<section class="om-comments" id="comments"><h2>'.e(om_t('comments.title','Yorumlar')).' <span>'.e((string)count($rows)).'</span></h2>';
    if(!$rows) return $out.'<p class="om-comments-empty">'.e(om_t('comments.no_comments','Henüz yorum yok.')).'</p></section>';
    $byParent=[];
    foreach($rows as $r){ $byParent[(int)($r['parent_id'] ?? 0)][]=$r; }
    $render=function($parentId, $depth=0) use (&$render,&$byParent): string {
        $html='';
        foreach($byParent[$parentId] ?? [] as $r){
            $html.='<article class="om-comment depth-'.(int)$depth.'"><header><strong>'.e($r['author_name']).'</strong><time>'.e(date('d.m.Y H:i', strtotime($r['created_at'] ?? 'now'))).'</time></header><div>'.nl2br(e($r['content'])).'</div>';
            $html.=$render((int)$r['id'], $depth+1);
            $html.='</article>';
        }
        return $html;
    };
    return $out.'<div class="om-comments-list">'.$render(0).'</div></section>';
}
function om_comment_form(int $postId): string {
    if($postId<=0) return '';
    try{
        $posts=table_name('posts');
        $st=db()->prepare("SELECT id,comments_enabled FROM $posts WHERE id=? LIMIT 1");
        $st->execute([$postId]);
        $post=$st->fetch();
        if(!$post || !om_post_comments_enabled($post)) return '';
    }catch(Throwable $e){ return ''; }
    $notice=$GLOBALS['omurga_comment_notice'][$postId] ?? '';
    $html='<section class="om-comment-form" id="comment-form"><h2>'.e(om_t('comments.add','Yorum Ekle')).'</h2>';
    if($notice) $html.='<div class="alert success">'.e($notice).'</div>';
    $html.='<form method="post"><input type="hidden" name="_csrf" value="'.e(csrf_token()).'"><input type="hidden" name="omurga_comment" value="1"><input type="hidden" name="post_id" value="'.e((string)$postId).'"><input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-10000px;width:1px;height:1px" aria-hidden="true">';
    $html.='<label>'.e(om_t('comments.name','Ad')).'<input name="author_name" required maxlength="120"></label>';
    $html.='<label>'.e(om_t('comments.email','E-posta')).'<input name="author_email" type="email" required maxlength="190"></label>';
    $html.='<label>'.e(om_t('comments.content','Yorum')).'<textarea name="content" rows="5" required maxlength="3000"></textarea></label>';
    $html.='<button class="btn primary">'.e(om_t('comments.submit','Gönder')).'</button></form></section>';
    return $html;
}
function om_handle_comment_submission(): void {
    if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['omurga_comment'] ?? '') !== '1') return;
    $postId=(int)($_POST['post_id'] ?? 0);
    $setNotice=function(string $msg) use($postId){ $GLOBALS['omurga_comment_notice'][$postId]=$msg; };
    try{
        $token=(string)($_POST['_csrf'] ?? '');
        if(!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        if(trim((string)($_POST['website'] ?? '')) !== '') throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        if(!empty($_SESSION['omurga_last_comment_at']) && time()-(int)$_SESSION['omurga_last_comment_at'] < 8) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        $posts=table_name('posts');
        $st=db()->prepare("SELECT id,comments_enabled FROM $posts WHERE id=? AND status='published' LIMIT 1");
        $st->execute([$postId]);
        $post=$st->fetch();
        if(!$post || !om_post_comments_enabled($post)) throw new RuntimeException(om_t('comments.disabled','Yorumlar kapalı.'));
        $name=trim((string)($_POST['author_name'] ?? ''));
        $email=trim((string)($_POST['author_email'] ?? ''));
        $content=trim(strip_tags((string)($_POST['content'] ?? '')));
        if($name==='' || mb_strlen($name,'UTF-8')>120) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        if(!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email,'UTF-8')>190) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        if($content==='' || mb_strlen($content,'UTF-8')>3000) throw new RuntimeException(om_t('comments.comment_error','Yorum kaydedilemedi.'));
        $ip=mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''),0,64,'UTF-8');
        $t=table_name('comments');
        $status='pending';
        omurga_do_action('omurga_before_comment_save', ['post_id'=>$postId,'author_name'=>$name,'author_email'=>$email,'content'=>$content,'status'=>$status]);
        db()->prepare("INSERT INTO $t (post_id,parent_id,author_name,author_email,author_ip,content,status,user_id) VALUES (?,?,?,?,?,?,?,?)")->execute([$postId,null,$name,$email,$ip,$content,$status,$_SESSION['omurga_user_id'] ?? null]);
        omurga_do_action('omurga_after_comment_save', (int)db()->lastInsertId(), $postId, $status);
        $_SESSION['omurga_last_comment_at']=time();
        $setNotice(om_t('comments.comment_saved','Yorumunuz onay bekliyor.'));
    }catch(Throwable $e){
        $setNotice($e->getMessage() ?: om_t('comments.comment_error','Yorum kaydedilemedi.'));
    }
}


function omurga_supported_languages(): array {
    return [
        'tr' => ['label' => 'Türkçe', 'locale' => 'tr_TR'],
        'en' => ['label' => 'English', 'locale' => 'en_US'],
    ];
}
function omurga_normalize_language(?string $lang, string $fallback='tr'): string {
    $lang = strtolower(trim((string)$lang));
    $lang = preg_replace('/[^a-z]/', '', $lang) ?: $fallback;
    return array_key_exists($lang, omurga_supported_languages()) ? $lang : $fallback;
}
function omurga_admin_language(): string {
    return omurga_normalize_language(setting('admin_language', 'tr'), 'tr');
}
function omurga_site_language(): string {
    return omurga_normalize_language(setting('site_language', omurga_admin_language()), 'tr');
}
function omurga_current_language(string $scope='auto'): string {
    if($scope === 'admin') return omurga_admin_language();
    if($scope === 'site' || $scope === 'theme') return omurga_site_language();
    $script = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
    return str_contains($script, '/admin/') ? omurga_admin_language() : omurga_site_language();
}
function omurga_load_language_file(string $file): array {
    static $cache = [];
    if(isset($cache[$file])) return $cache[$file];
    if(!is_file($file)) return $cache[$file] = [];
    $data = require $file;
    return $cache[$file] = is_array($data) ? $data : [];
}
function omurga_language_stack(string $lang, string $scope='auto'): array {
    $lang = omurga_normalize_language($lang);
    $files = [];
    $activeTheme = function_exists('omurga_active_theme') ? omurga_active_theme() : '';
    if($activeTheme){
        $files[] = OMURGA_ROOT.'/themes/'.$activeTheme.'/lang/'.$lang.'.php';
    }
    // Eklenti dil dosyaları ileride plugin bağlamına göre bu araya eklenebilir.
    $files[] = OMURGA_ROOT.'/core/lang/'.$lang.'.php';
    if($lang !== 'tr') $files[] = OMURGA_ROOT.'/core/lang/tr.php';
    return $files;
}
function om_t(string $key, ?string $fallback=null, array $replace=[], string $scope='auto'): string {
    $lang = omurga_current_language($scope);
    foreach(omurga_language_stack($lang, $scope) as $file){
        $items = omurga_load_language_file($file);
        if(array_key_exists($key, $items)){
            $text = (string)$items[$key];
            if($replace){
                foreach($replace as $rk=>$rv){ $text = str_replace('{'.$rk.'}', (string)$rv, $text); }
            }
            return $text;
        }
    }
    $text = $fallback ?? $key;
    if($replace){ foreach($replace as $rk=>$rv){ $text = str_replace('{'.$rk.'}', (string)$rv, $text); } }
    return $text;
}

function slugify(string $text): string { $map=['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']; $text=strtr($text,$map); $text=strtolower($text); $text=preg_replace('/[^a-z0-9]+/','-',$text); return trim($text,'-')?:'icerik'; }
function excerpt(string $html,int $limit=160): string { $text=trim(strip_tags($html)); if(mb_strlen($text,'UTF-8')<=$limit)return $text; return mb_substr($text,0,$limit,'UTF-8').'...'; }
function post_url(array $post): string {
    $slug = trim((string)($post['slug'] ?? ''), '/');
    if($slug==='') return omurga_url();
    if(($post['type'] ?? '') === 'page') return page_url($post);
    return omurga_url(content_url_base().'/'.$slug);
}
function page_url(array $page): string { return omurga_url(trim((string)($page['slug'] ?? ''), '/')); }
function category_url(array $cat): string { return omurga_url('kategori/'.($cat['slug'] ?? '')); }
function image_url(?string $path): string { if(!$path) return ''; if(str_starts_with($path,'http://')||str_starts_with($path,'https://')) return $path; return omurga_url($path); }
function clean_upload_name(string $name): string { $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION)); $base=slugify(pathinfo($name,PATHINFO_FILENAME)); return $base.'-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(3)),0,6).($ext?'.'.$ext:''); }
function omurga_safe_existing_file(string $relativePath, array $allowedRoots=['uploads']): ?string {
    $rel = str_replace('\\', '/', ltrim((string)$relativePath, '/'));
    if($rel==='' || str_contains($rel, '../') || str_starts_with($rel, '..') || preg_match('#^[a-zA-Z]:/#', $rel)) return null;
    $real = realpath(OMURGA_ROOT.'/'.$rel);
    if(!$real || !is_file($real)) return null;
    $realNorm = str_replace('\\', '/', $real);
    foreach($allowedRoots as $root){
        $base = realpath(OMURGA_ROOT.'/'.trim($root, '/'));
        if(!$base) continue;
        $baseNorm = rtrim(str_replace('\\', '/', $base), '/');
        if($realNorm === $baseNorm || str_starts_with($realNorm, $baseNorm.'/')) return $real;
    }
    return null;
}
function omurga_zip_entry_is_safe(string $name): bool {
    $name = str_replace('\\', '/', trim($name));
    if($name === '' || str_starts_with($name, '/') || preg_match('#^[a-zA-Z]:/#', $name)) return false;
    foreach(explode('/', $name) as $part){ if($part === '..') return false; }
    return true;
}
function omurga_safe_extract_zip(ZipArchive $zip, string $destination): void {
    if(!is_dir($destination)) mkdir($destination, 0775, true);
    for($i=0; $i<$zip->numFiles; $i++){
        $name = (string)$zip->getNameIndex($i);
        if(!omurga_zip_entry_is_safe($name)) throw new RuntimeException('Zip içinde güvensiz dosya yolu var: '.$name);
    }
    if(!$zip->extractTo($destination)) throw new RuntimeException('Zip dosyası çıkarılamadı.');
}
function omurga_image_info(string $absolute): array { $info=@getimagesize($absolute); return $info?['width'=>(int)$info[0],'height'=>(int)$info[1]]:['width'=>0,'height'=>0]; }
function omurga_webp_supported(): bool { return function_exists('imagewebp') && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng'); }
function create_webp_copy(string $absolutePath, string $mime, int $quality=82): ?string {
    if(!omurga_webp_supported()) return null;
    $image=null;
    if($mime==='image/jpeg') $image=@imagecreatefromjpeg($absolutePath);
    elseif($mime==='image/png') { $image=@imagecreatefrompng($absolutePath); if($image){ imagepalettetotruecolor($image); imagealphablending($image,true); imagesavealpha($image,true); } }
    elseif($mime==='image/webp') return $absolutePath;
    else return null;
    if(!$image) return null;
    $webp=preg_replace('/\.[a-zA-Z0-9]+$/','.webp',$absolutePath);
    if(!@imagewebp($image,$webp,$quality)){ imagedestroy($image); return null; }
    imagedestroy($image); return $webp;
}

function omurga_image_driver(string $absolutePath, string $mime) {
    if($mime==='image/jpeg') return @imagecreatefromjpeg($absolutePath);
    if($mime==='image/png') { $im=@imagecreatefrompng($absolutePath); if($im){ imagepalettetotruecolor($im); imagealphablending($im,true); imagesavealpha($im,true); } return $im; }
    if($mime==='image/webp' && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($absolutePath);
    return null;
}
function omurga_resize_image_if_needed(string $absolutePath, string $mime, int $maxWidth=1600, int $jpegQuality=86): bool {
    if($maxWidth<=0 || !function_exists('imagecreatetruecolor')) return false;
    $info=@getimagesize($absolutePath); if(!$info || (int)$info[0] <= $maxWidth) return false;
    $src=omurga_image_driver($absolutePath,$mime); if(!$src) return false;
    $w=(int)$info[0]; $h=(int)$info[1]; $newW=$maxWidth; $newH=max(1,(int)round($h*$newW/$w));
    $dst=imagecreatetruecolor($newW,$newH);
    if(in_array($mime,['image/png','image/webp'],true)){ imagealphablending($dst,false); imagesavealpha($dst,true); $transparent=imagecolorallocatealpha($dst,0,0,0,127); imagefilledrectangle($dst,0,0,$newW,$newH,$transparent); }
    imagecopyresampled($dst,$src,0,0,0,0,$newW,$newH,$w,$h);
    $ok=false;
    if($mime==='image/jpeg') $ok=@imagejpeg($dst,$absolutePath,$jpegQuality);
    elseif($mime==='image/png') $ok=@imagepng($dst,$absolutePath,6);
    elseif($mime==='image/webp' && function_exists('imagewebp')) $ok=@imagewebp($dst,$absolutePath,$jpegQuality);
    imagedestroy($src); imagedestroy($dst); return (bool)$ok;
}
function omurga_media_rel_dir(): string { return 'uploads/'.date('Y/m'); }
function omurga_human_size(int $bytes): string { if($bytes>=1048576) return round($bytes/1048576,2).' MB'; if($bytes>=1024) return round($bytes/1024,1).' KB'; return $bytes.' B'; }
function omurga_prepare_upload_name(string $originalName, string $titleHint=''): string {
    $ext=strtolower(pathinfo($originalName,PATHINFO_EXTENSION));
    $base=$titleHint!=='' ? slugify($titleHint) : slugify(pathinfo($originalName,PATHINFO_FILENAME));
    return trim($base,'-').'-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(3)),0,6).($ext?'.'.$ext:'');
}
function omurga_convert_existing_media_to_webp(array $media, bool $replaceRecord=false, int $quality=82): array {
    $path=$media['file_path'] ?? ''; $abs=OMURGA_ROOT.'/'.$path;
    if(!$path || !is_file($abs)) return ['ok'=>false,'message'=>'Dosya bulunamadı.'];
    $mime=mime_content_type($abs) ?: ($media['mime'] ?? '');
    if(!in_array($mime,['image/jpeg','image/png'],true)) return ['ok'=>false,'message'=>'Sadece JPG/PNG WebP’ye çevrilir.'];
    $webp=create_webp_copy($abs,$mime,$quality);
    if(!$webp || !is_file($webp)) return ['ok'=>false,'message'=>'WebP oluşturulamadı.'];
    $rel=trim(str_replace(OMURGA_ROOT.'/','',$webp),'/');
    $info=omurga_image_info($webp); $size=filesize($webp); $t=table_name('media');
    if($replaceRecord){
        db()->prepare("UPDATE $t SET file_path=?,file_name=?,mime='image/webp',original_path=COALESCE(original_path,?),width=?,height=?,file_size=? WHERE id=?")->execute([$rel,basename($rel),$path,$info['width'],$info['height'],$size,(int)$media['id']]);
    } else {
        db()->prepare("INSERT INTO $t (file_path,file_name,mime,alt_text,uploaded_by,original_path,width,height,file_size) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$rel,basename($rel),'image/webp',$media['alt_text']??'',$_SESSION['omurga_user_id']??null,$path,$info['width'],$info['height'],$size]);
    }
    return ['ok'=>true,'path'=>$rel,'message'=>'WebP oluşturuldu.'];
}

function save_uploaded_file(string $field, bool $createWebp=true): ?string {
    if(empty($_FILES[$field]['name'])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return null;
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    $tmp=$_FILES[$field]['tmp_name']; $mime=mime_content_type($tmp);
    if(!isset($allowed[$mime])) throw new RuntimeException('Sadece JPG, PNG, WEBP veya GIF yüklenebilir.');
    if(($_FILES[$field]['size']??0)>12*1024*1024) throw new RuntimeException('Görsel 12 MB üstünde olamaz.');
    $relDir='uploads/'.date('Y/m'); $dir=OMURGA_ROOT.'/'.$relDir; if(!is_dir($dir)) mkdir($dir,0775,true);
    $name=omurga_prepare_upload_name($_FILES[$field]['name'], $_POST['media_title_hint'] ?? ''); $target=$dir.'/'.$name;
    if(!move_uploaded_file($tmp,$target)) throw new RuntimeException('Dosya yüklenemedi.');
    omurga_resize_image_if_needed($target,$mime,(int)setting('media_max_width','1600'),(int)setting('media_jpeg_quality','86'));
    if($createWebp && in_array($mime,['image/jpeg','image/png'],true)){
        $webp=create_webp_copy($target,$mime,(int)setting('webp_quality','82'));
        if($webp) return $relDir.'/'.basename($webp);
    }
    return $relDir.'/'.$name;
}
function insert_media_record(string $path, string $alt='', ?int $userId=null, ?string $originalPath=null): void {
    try{
        $abs=OMURGA_ROOT.'/'.$path; $mime=file_exists($abs)?(mime_content_type($abs) ?: ''):''; $info=file_exists($abs)?omurga_image_info($abs):['width'=>0,'height'=>0];
        $t=table_name('media');
        $cols=db()->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('width',$cols,true)){
            db()->prepare("INSERT INTO $t (file_path,file_name,mime,alt_text,uploaded_by,original_path,width,height,file_size) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$path,basename($path),$mime,$alt,$userId,$originalPath,$info['width'],$info['height'],file_exists($abs)?filesize($abs):0]);
        } else {
            db()->prepare("INSERT INTO $t (file_path,file_name,mime,alt_text,uploaded_by) VALUES (?,?,?,?,?)")->execute([$path,basename($path),$mime,$alt,$userId]);
        }
    }catch(Throwable $e){}
}
function robots_txt_content(): string {
    $custom=trim((string)setting('robots_txt_custom',''));
    if($custom!=='') return str_replace('{sitemap}', omurga_url('sitemap.xml'), $custom)."\n";
    $allowIndex=setting('seo_allow_index','1')==='1';
    $lines=["User-agent: *", $allowIndex ? "Allow: /" : "Disallow: /", "Disallow: /admin/", "Disallow: /install/", "", 'Sitemap: '.omurga_url('sitemap.xml')];
    return implode("\n",$lines)."\n";
}
function default_social_image(): string { return setting('seo_default_og_image','') ?: setting('default_social_image','') ?: setting('site_logo_image',''); }
function omurga_seo_setting(string $key, string $default=''): string { return setting('seo_'.$key, $default); }
function omurga_seo_title(?array $post=null, ?array $category=null, string $fallback=''): string {
    $site=setting('site_name','Omurga');
    if($post){ $title=trim((string)($post['seo_title'] ?: $post['title'])); }
    elseif($category){ $title=trim((string)($category['seo_title'] ?? $category['name'] ?? $fallback)); }
    else { $title=trim($fallback ?: $site); }
    $format=omurga_seo_setting('title_format','{title} - {site}');
    if($title===$site) return $site;
    return trim(str_replace(['{title}','{site}'], [$title,$site], $format));
}
function omurga_seo_description(?array $post=null, ?array $category=null, string $fallback=''): string {
    if($post){ $d=trim((string)($post['meta_description'] ?: $post['spot'] ?: excerpt($post['content'] ?? '',160))); }
    elseif($category){ $d=trim((string)($category['seo_description'] ?? $category['description'] ?? '')); }
    else { $d=trim($fallback ?: setting('site_description','')); }
    return mb_substr($d,0,180,'UTF-8');
}
function omurga_canonical_url(?array $post=null, ?array $category=null, string $fallback=''): string {
    if($post && !empty($post['canonical_url'])) return (string)$post['canonical_url'];
    if($post) return post_url($post);
    if($category) return category_url($category);
    return $fallback ?: omurga_url();
}
function omurga_should_index(?array $post=null): bool {
    if(setting('seo_allow_index','1')!=='1') return false;
    if($post && !empty($post['seo_noindex'])) return false;
    return true;
}
function omurga_seo_head(array $ctx=[]): string {
    $post=$ctx['post'] ?? null; $category=$ctx['category'] ?? null;
    $title=$ctx['seo_title'] ?? omurga_seo_title(is_array($post)?$post:null, is_array($category)?$category:null, $ctx['title'] ?? '');
    $desc=$ctx['seo_description'] ?? omurga_seo_description(is_array($post)?$post:null, is_array($category)?$category:null, $ctx['meta'] ?? '');
    $canonical=$ctx['canonical_url'] ?? omurga_canonical_url(is_array($post)?$post:null, is_array($category)?$category:null, $ctx['canonical'] ?? omurga_url());
    $og=image_url($ctx['og_image'] ?? ($post['social_image'] ?? '') ?: ($post['featured_image'] ?? '') ?: default_social_image());
    $site=setting('site_name','Omurga');
    $out=[];
    $out[]='<title>'.e($title).'</title>';
    if($desc!=='') $out[]='<meta name="description" content="'.e($desc).'">';
    $out[]='<link rel="canonical" href="'.e($canonical).'">';
    $out[]='<meta name="robots" content="'.(omurga_should_index(is_array($post)?$post:null)?'index,follow':'noindex,nofollow').'">';
    if(setting('seo_enable_og','1')==='1'){
        $out[]='<meta property="og:site_name" content="'.e($site).'">';
        $out[]='<meta property="og:title" content="'.e($post['social_title'] ?? '' ?: $title).'">';
        $out[]='<meta property="og:description" content="'.e($post['social_description'] ?? '' ?: $desc).'">';
        $out[]='<meta property="og:url" content="'.e($canonical).'">';
        $out[]='<meta property="og:type" content="'.($post?'article':'website').'">';
        if($og) $out[]='<meta property="og:image" content="'.e($og).'">';
    }
    if(setting('seo_enable_twitter','1')==='1'){
        $out[]='<meta name="twitter:card" content="summary_large_image">';
        $out[]='<meta name="twitter:title" content="'.e($post['social_title'] ?? '' ?: $title).'">';
        if($desc) $out[]='<meta name="twitter:description" content="'.e($post['social_description'] ?? '' ?: $desc).'">';
        if($og) $out[]='<meta name="twitter:image" content="'.e($og).'">';
    }
    if(setting('seo_enable_schema','1')==='1'){
        $schema=['@context'=>'https://schema.org','@type'=>$post?(site_type()==='haber'?'NewsArticle':'Article'):setting('schema_org_type','Organization'),'name'=>$title,'url'=>$canonical];
        if($desc) $schema['description']=$desc;
        if($og) $schema['image']=$og;
        if($post){ $schema['headline']=$post['title'] ?? $title; $schema['datePublished']=$post['published_at'] ?? $post['created_at'] ?? null; $schema['dateModified']=$post['updated_at'] ?? $post['created_at'] ?? null; $schema['author']=['@type'=>'Person','name'=>$post['author_name'] ?? setting('site_name','Omurga')]; }
        $out[]='<script type="application/ld+json">'.json_encode(array_filter($schema), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
    }
    return implode("\n", $out)."\n";
}

function setting_json(string $key, array $default=[]): array { $raw=setting($key, ''); if(!$raw) return $default; $data=json_decode($raw,true); return is_array($data)?$data:$default; }
function update_setting_json(string $key, array $value): void { update_setting($key, json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
function omurga_schema_version(): string { return setting('schema_version', '0') ?: '0'; }
function omurga_table_exists(string $table): bool {
    try{
        $st=db()->prepare('SHOW TABLES LIKE ?');
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    }catch(Throwable $e){ return false; }
}
function omurga_builder_layout_key(?string $theme=null): string { return 'layout_global'; }
function omurga_read_builder_layout(string $key, array $default=[]): array {
    $settingsValue=setting_json($key, []);
    try{
        $table=table_name('builder_layouts');
        if(omurga_table_exists($table)){
            $st=db()->prepare("SELECT layout_json FROM $table WHERE layout_key=? AND status='active' LIMIT 1");
            $st->execute([$key]);
            $raw=$st->fetchColumn();
            if($raw){
                $data=json_decode((string)$raw,true);
                if(is_array($data)) return $data;
            }
        }
    }catch(Throwable $e){ omurga_write_error($e); }
    return $settingsValue ?: $default;
}
function omurga_save_builder_layout(string $key, array $layout, string $title='Site layout'): void {
    update_setting_json($key, $layout);
    try{
        $table=table_name('builder_layouts');
        if(!omurga_table_exists($table)) return;
        $json=json_encode($layout, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        db()->prepare("INSERT INTO $table (layout_key,title,layout_json,status) VALUES (?,?,?,'active') ON DUPLICATE KEY UPDATE title=VALUES(title), layout_json=VALUES(layout_json), status='active', updated_at=CURRENT_TIMESTAMP")->execute([$key,$title,$json]);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_update_layout(array $layout, ?string $theme=null): void { omurga_save_builder_layout(omurga_layout_key($theme), $layout, 'Site layout'); }
function theme_color(string $key, string $fallback): string { $v=setting($key,$fallback); return preg_match('/^#[0-9a-fA-F]{6}$/',$v)?$v:$fallback; }
function default_blocks_for_type(?string $type=null): array {
    $type=$type ?: site_type();
    if($type==='kurumsal') return [
        ['key'=>'hero','title'=>'Hero Alanı','enabled'=>1,'sort'=>10,'limit'=>1,'source'=>'category:hizmetler','mobile'=>1],
        ['key'=>'services','title'=>'Hizmetler','enabled'=>1,'sort'=>20,'limit'=>6,'source'=>'category:hizmetler','mobile'=>1],
        ['key'=>'portfolio','title'=>'Portföy / Projeler','enabled'=>1,'sort'=>30,'limit'=>6,'source'=>'category:projeler','mobile'=>1],
        ['key'=>'quote','title'=>'Teklif Formu','enabled'=>1,'sort'=>40,'limit'=>1,'source'=>'form','mobile'=>1],
        ['key'=>'contact','title'=>'İletişim','enabled'=>1,'sort'=>50,'limit'=>1,'source'=>'page','mobile'=>1],
    ];
    if($type==='topluluk') return [
        ['key'=>'hero','title'=>'Hero Alanı','enabled'=>1,'sort'=>10,'limit'=>1,'source'=>'category:duyurular','mobile'=>1],
        ['key'=>'announcements','title'=>'Duyurular','enabled'=>1,'sort'=>20,'limit'=>6,'source'=>'category:duyurular','mobile'=>1],
        ['key'=>'events','title'=>'Etkinlikler','enabled'=>1,'sort'=>30,'limit'=>6,'source'=>'category:etkinlikler','mobile'=>1],
        ['key'=>'projects','title'=>'Projeler','enabled'=>1,'sort'=>40,'limit'=>6,'source'=>'category:projeler','mobile'=>1],
        ['key'=>'board','title'=>'Yönetim Kurulu','enabled'=>1,'sort'=>50,'limit'=>8,'source'=>'page:yonetim-kurulu','mobile'=>1],
        ['key'=>'membership','title'=>'Üyelik Başvurusu','enabled'=>1,'sort'=>60,'limit'=>1,'source'=>'form','mobile'=>1],
    ];
    return [
        ['key'=>'latest','title'=>'Son İçerikler','enabled'=>1,'sort'=>40,'limit'=>12,'source'=>'latest','mobile'=>1],
        ['key'=>'ad-home','title'=>'Reklam Alanı','enabled'=>0,'sort'=>50,'limit'=>1,'source'=>'ad_home','mobile'=>1],
    ];
}
function home_blocks(): array { $blocks=setting_json('home_blocks', []); if(!$blocks) $blocks=default_blocks_for_type(); usort($blocks, fn($a,$b)=>(int)($a['sort']??0)<=>(int)($b['sort']??0)); return $blocks; }
function block_enabled(string $key): bool { foreach(home_blocks() as $b){ if(($b['key']??'')===$key) return !empty($b['enabled']); } return false; }
function block_conf(string $key): array { foreach(home_blocks() as $b){ if(($b['key']??'')===$key) return $b; } return []; }
function omurga_menu_locations(): array {
    return ['main'=>'Ana Menü','mobile'=>'Mobil Menü','footer'=>'Footer Menü','top'=>'Üst Menü'];
}
function omurga_normalize_menu_location(string $location='main'): string {
    $location=preg_replace('/[^a-z0-9_\-]/','', strtolower($location ?: 'main'));
    return array_key_exists($location, omurga_menu_locations()) ? $location : 'main';
}
function default_menu_items(string $location='main'): array {
    $location=omurga_normalize_menu_location($location);
    if($location==='top') return [
        ['id'=>1,'title'=>'İçerikler','url'=>omurga_url('#icerikler'),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
        ['id'=>2,'title'=>'Künye','url'=>omurga_url('kunye'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
    ];
    if($location==='footer') return [
        ['id'=>1,'title'=>'Anasayfa','url'=>omurga_url(),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
        ['id'=>2,'title'=>'İçerikler','url'=>omurga_url('#icerikler'),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
        ['id'=>3,'title'=>'İletişim','url'=>omurga_url('#form'),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],
    ];
    return [
        ['id'=>1,'title'=>'Anasayfa','url'=>omurga_url(),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
        ['id'=>2,'title'=>'İçerikler','url'=>omurga_url('#icerikler'),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
        ['id'=>3,'title'=>'İletişim','url'=>omurga_url('#form'),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],
    ];
}
function menu_setting_key(string $location='main'): string { return 'menu_'.omurga_normalize_menu_location($location); }
function menu_items(string $location='main', bool $activeOnly=true): array {
    $location=omurga_normalize_menu_location($location);
    $legacy=$location==='main' ? setting_json('main_menu', []) : [];
    $items=setting_json(menu_setting_key($location), $legacy ?: []);
    if(!$items) $items=default_menu_items($location);
    $out=[];
    foreach($items as $idx=>$it){
        $title=trim((string)($it['title']??'')); $url=trim((string)($it['url']??''));
        if($title==='' || $url==='') continue;
        $active=(int)($it['active'] ?? 1); if($activeOnly && !$active) continue;
        $out[]=['id'=>(int)($it['id']??($idx+1)),'title'=>$title,'url'=>$url,'type'=>preg_replace('/[^a-z0-9_\-]/','',strtolower((string)($it['type']??'custom'))) ?: 'custom','target'=>($it['target']??'_self')==='_blank'?'_blank':'_self','active'=>$active,'parent'=>(int)($it['parent']??0),'sort'=>(int)($it['sort']??(($idx+1)*10))];
    }
    usort($out, fn($a,$b)=>($a['sort']<=>$b['sort']) ?: ($a['id']<=>$b['id']));
    return $out;
}
function omurga_menu_tree(array $items): array {
    $by=[]; foreach($items as $it){ $it['children']=[]; $by[(int)$it['id']]=$it; }
    $tree=[];
    foreach(array_keys($by) as $id){ $parent=(int)($by[$id]['parent'] ?? 0); if($parent && isset($by[$parent])) $by[$parent]['children'][]=$by[$id]; else $tree[]=$by[$id]; }
    return $tree;
}
function omurga_render_menu_items(array $items): string {
    $html=''; foreach($items as $mi){ $target=$mi['target']==='_blank'?' target="_blank" rel="noopener"':''; $html.='<li class="omg-menu-item"><a href="'.e($mi['url']).'"'.$target.'>'.e($mi['title']).'</a>'; if(!empty($mi['children'])) $html.='<ul class="omg-submenu">'.omurga_render_menu_items($mi['children']).'</ul>'; $html.='</li>'; } return $html;
}
function omurga_menu(string $location='main'): string {
    $location=omurga_normalize_menu_location($location); $tree=omurga_menu_tree(menu_items($location,true)); if(!$tree) return '';
    return '<nav class="omg-menu omg-menu-'.e($location).'" aria-label="'.e(omurga_menu_locations()[$location]).'"><ul>'.omurga_render_menu_items($tree).'</ul></nav>';
}
function omurga_ad_locations(): array {
    return ['header'=>'Header Reklamı','content_top'=>'İçerik Üstü','content_inside'=>'İçerik İçi','sidebar'=>'Sidebar','mobile_fixed'=>'Mobil Sabit','footer'=>'Footer'];
}
function omurga_normalize_ad_area(string $area='header'): string { $area=preg_replace('/[^a-z0-9_\-]/','', strtolower($area ?: 'header')); return array_key_exists($area, omurga_ad_locations()) ? $area : 'header'; }
function omurga_default_ad_slots(): array { $out=[]; foreach(omurga_ad_locations() as $k=>$label){ $out[$k]=['enabled'=>0,'title'=>$label,'type'=>'image','image'=>'','link'=>'','html'=>'','target'=>'_blank','show_mobile'=>1,'show_desktop'=>1]; } return $out; }
function omurga_ad_slots(): array { return array_replace_recursive(omurga_default_ad_slots(), setting_json('ad_slots', [])); }
function omurga_ad_area(string $area='header'): string {
    $area=omurga_normalize_ad_area($area); $ad=omurga_ad_slots()[$area] ?? []; if(empty($ad['enabled'])) return '';
    $classes=['omg-ad','omg-ad-'.$area]; if(empty($ad['show_mobile'])) $classes[]='hide-mobile'; if(empty($ad['show_desktop'])) $classes[]='hide-desktop';
    $html=trim((string)($ad['html']??'')); $image=trim((string)($ad['image']??'')); $link=trim((string)($ad['link']??'')); $title=trim((string)($ad['title']??'')); $inner='';
    if($html!=='') $inner=$html; elseif($image!==''){ $img='<img src="'.e(image_url($image)).'" alt="'.e($title ?: 'Reklam').'">'; if($link!==''){ $target=($ad['target']??'_blank')==='_blank'?' target="_blank" rel="nofollow sponsored noopener"':' rel="nofollow sponsored"'; $img='<a href="'.e($link).'"'.$target.'>'.$img.'</a>'; } $inner=$img; }
    return $inner!=='' ? '<div class="'.e(implode(' ',$classes)).'">'.$inner.'</div>' : '';
}
function ad_slot(string $key): array { $ads=setting_json('ad_slots', []); return $ads[$key] ?? ['enabled'=>0,'title'=>'','image'=>'','link'=>'','html'=>'']; }
function render_ad_slot(string $key): string { $ad=ad_slot($key); if(empty($ad['enabled'])) return ''; $html=trim($ad['html']??''); if($html) return '<div class="ad-slot">'.$html.'</div>'; $img=trim($ad['image']??''); if($img){ $link=trim($ad['link']??''); $tag='<img src="'.e(image_url($img)).'" alt="'.e($ad['title']??'Reklam').'">'; return '<div class="ad-slot">'.($link?'<a href="'.e($link).'" target="_blank" rel="noopener">'.$tag.'</a>':$tag).'</div>'; } return ''; }


/* Omurga Düzen + Blok Sistemi v1.6.7
   - Bloklar çekirdekten, aktif temadan ve storage/blocks özel blok klasöründen gelir.
   - Gelişmiş blok: block.json + view.php. Basit blok: tek .php dosyası.
   - Bloklar içerik ekleme ekranına post_meta alanı tanımlayabilir. */
function omurga_core_blocks_dir(): string { return OMURGA_ROOT.'/core/blocks'; }
function omurga_block_safe_html(string $html): string {
    $html=preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is','',$html) ?? '';
    $html=preg_replace('#\s+on[a-z]+\s*=\s*(["\']).*?\1#is','',$html) ?? '';
    $html=preg_replace('#\s+(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#is','',$html) ?? '';
    return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><span><div><blockquote><code><pre><h2><h3><h4>');
}
function omurga_core_blocks(): array {
    $legacy=[
        // Yeni çekirdek bloklarla aynı işi yapan eski sluglar panelde gösterilmez.
        // Geriye uyumluluk omurga_block_aliases() ile sağlanır.
        'kategori-yazilari' => ['slug'=>'kategori-yazilari','name'=>'Kategori Yazıları','source'=>'core','category'=>'Varsayılan','usage'=>['home_main','sidebar','page'],'settings'=>['title'=>['type'=>'text','label'=>'Başlık','default'=>'Kategori Yazıları'],'limit'=>['type'=>'number','label'=>'İçerik Sayısı','default'=>5],'category_id'=>['type'=>'category','label'=>'Kategori','default'=>'']]],
        'reklam-alani' => ['slug'=>'reklam-alani','name'=>'Reklam Alanı','source'=>'core','category'=>'Varsayılan','usage'=>['home_top','home_main','sidebar','post_inside','footer','page','post'],'settings'=>['title'=>['type'=>'text','label'=>'Başlık','default'=>'Reklam'],'slot'=>['type'=>'select','label'=>'Reklam Alanı','default'=>'home','options'=>['header'=>'Header','home'=>'Anasayfa','post'=>'Yazı İçi','sidebar'=>'Sidebar','mobile'=>'Mobil']]]],
        'sosyal-medya' => ['slug'=>'sosyal-medya','name'=>'Sosyal Medya','source'=>'core','category'=>'Varsayılan','usage'=>['header','footer','sidebar'],'settings'=>['title'=>['type'=>'text','label'=>'Başlık','default'=>'Sosyal Medya']]],
        'sayfa-icerigi' => ['slug'=>'sayfa-icerigi','name'=>'Sayfa İçeriği','source'=>'core','category'=>'Varsayılan','usage'=>['page','post'],'settings'=>['title'=>['type'=>'text','label'=>'Başlık','default'=>'']]],
    ];
    return array_replace($legacy, omurga_scan_blocks_dir(omurga_core_blocks_dir(), 'core', null));
}
function omurga_theme_meta(?string $slug=null): array {
    $slug=$slug ?: omurga_active_theme();
    $file=omurga_theme_dir($slug).'/theme.json';
    $data=file_exists($file)?json_decode((string)file_get_contents($file),true):[];
    return is_array($data)?$data:[];
}
function omurga_theme_settings_definitions(?string $slug=null): array {
    $meta = omurga_theme_meta($slug);
    $settings = $meta['settings'] ?? [];
    return is_array($settings) ? $settings : [];
}
function omurga_theme_settings_key(?string $slug=null): string {
    return 'theme_settings_' . ($slug ?: omurga_active_theme());
}
function omurga_theme_settings_values(?string $slug=null): array {
    $slug = $slug ?: omurga_active_theme();
    $defs = omurga_theme_settings_definitions($slug);
    $saved = setting_json(omurga_theme_settings_key($slug), []);
    $out = [];
    foreach ($defs as $key => $field) {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key);
        if ($safeKey === '') continue;
        $out[$safeKey] = array_key_exists($safeKey, $saved) ? $saved[$safeKey] : ($field['default'] ?? '');
    }
    return $out;
}
function omurga_normalize_theme_setting_value(array $field, $value) {
    $type = $field['type'] ?? 'text';
    if ($type === 'checkbox') return !empty($value) ? '1' : '0';
    if ($type === 'number') return (string)(int)$value;
    if ($type === 'color') return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? (string)$value : (string)($field['default'] ?? '#f97316');
    if ($type === 'select') {
        $options = $field['options'] ?? [];
        return array_key_exists((string)$value, $options) ? (string)$value : (string)($field['default'] ?? array_key_first($options) ?? '');
    }
    if ($type === 'image' || $type === 'url') return trim((string)$value);
    return trim((string)$value);
}
function update_theme_settings(array $values, ?string $slug=null): void {
    $slug = $slug ?: omurga_active_theme();
    update_setting_json(omurga_theme_settings_key($slug), $values);
}
function theme_setting(string $key, $default=null, ?string $slug=null) {
    $slug = $slug ?: omurga_active_theme();
    $values = omurga_theme_settings_values($slug);
    return array_key_exists($key, $values) ? $values[$key] : $default;
}
function theme_setting_bool(string $key, bool $default=false, ?string $slug=null): bool {
    $v = theme_setting($key, $default ? '1' : '0', $slug);
    return in_array((string)$v, ['1','true','yes','on'], true);
}

function omurga_theme_regions(?string $slug=null): array {
    $meta=omurga_theme_meta($slug);
    $regions=$meta['regions'] ?? [];
    if(!is_array($regions) || !$regions){
        $regions=['header'=>'Üst Alan','home_top'=>'Ana Sayfa Üstü','home_main'=>'Ana İçerik','sidebar'=>'Yan Alan','post_inside'=>'Yazı Detay İçi','footer'=>'Alt Alan'];
    }
    return $regions;
}
function omurga_read_block_json(string $json, string $source, ?string $theme=null): ?array {
    $data=json_decode((string)file_get_contents($json), true);
    if(!is_array($data)) return null;
    $bslug=slugify($data['slug'] ?? $data['name'] ?? basename(dirname($json)));
    if($bslug==='') return null;
    $data['id']=$data['id'] ?? $bslug;
    $data['slug']=$bslug;
    $data['source']=$source;
    if($theme) $data['theme']=$theme;
    if(!empty($data['name_key'])) $data['name']=om_t((string)$data['name_key'], (string)($data['name'] ?? $bslug));
    if(!empty($data['category_key'])) $data['category']=om_t((string)$data['category_key'], (string)($data['category'] ?? 'Varsayilan'));
    $data['category']=$data['category'] ?? ($source==='theme' ? 'Tema Blokları' : ($source==='package' ? 'Paket Blokları' : ($source==='custom' ? 'Özel Bloklar' : 'Varsayılan')));
    $data['usage']=$data['usage'] ?? ['home_main'];
    $data['settings']=is_array($data['settings'] ?? null) ? $data['settings'] : [];
    $data['settings_schema']=is_array($data['settings_schema'] ?? null) ? $data['settings_schema'] : $data['settings'];
    if(!is_array($data['default_settings'] ?? null)){
        $data['default_settings']=[];
        foreach($data['settings_schema'] as $key=>$field){
            if(is_array($field)) $data['default_settings'][$key]=$field['default'] ?? '';
        }
    }
    if(!is_array($data['allowed_contexts'] ?? null)){
        $data['allowed_contexts']=array_values(array_unique(array_filter(array_map(function($region){
            return class_exists('Omurga_BlockRegistry') ? Omurga_BlockRegistry::contextFromRegion((string)$region) : null;
        }, $data['usage']))));
    }
    foreach($data['settings'] as $key=>$field){
        if(!is_array($field)) continue;
        if(!empty($field['label_key'])) $data['settings'][$key]['label']=om_t((string)$field['label_key'], (string)($field['label'] ?? $key));
        if(!empty($field['help_key'])) $data['settings'][$key]['help']=om_t((string)$field['help_key'], (string)($field['help'] ?? ''));
    }
    $data['post_meta']=is_array($data['post_meta'] ?? null) ? $data['post_meta'] : [];
    $dir=dirname($json);
    $view=trim((string)($data['view'] ?? ''));
    if($view!=='') $data['view']=$dir.'/'.ltrim($view,'/');
    elseif(file_exists($dir.'/view.omg')) $data['view']=$dir.'/view.omg';
    else $data['view']=$dir.'/view.php';
    return $data;
}
function omurga_simple_php_block(string $file, string $source, ?string $theme=null): ?array {
    $base=basename($file, '.php');
    $bslug=slugify($base);
    if($bslug==='' || in_array($base, ['view','index'], true)) return null;
    return [
        'slug'=>$bslug,
        'name'=>ucwords(str_replace('-', ' ', $bslug)),
        'source'=>$source,
        'theme'=>$theme,
        'category'=>$source==='theme' ? 'Tema Blokları' : ($source==='package' ? 'Paket Blokları' : 'Özel Bloklar'),
        'usage'=>['any'],
        'settings'=>[],
        'post_meta'=>[],
        'view'=>$file,
        'simple'=>true,
    ];
}
function omurga_simple_tpl_block(string $file, string $source, ?string $theme=null): ?array {
    return null; // v4: .tpl blok formatı pasif.
}
function omurga_simple_omg_block(string $file, string $source, ?string $theme=null): ?array {
    $base=basename($file, '.omg');
    $bslug=slugify($base);
    if($bslug==='' || in_array($base, ['view','index','home','header','footer','single','page','category','search','login'], true)) return null;
    return [
        'slug'=>$bslug,
        'name'=>ucwords(str_replace('-', ' ', $bslug)),
        'source'=>$source,
        'theme'=>$theme,
        'category'=>$source==='theme' ? 'Tema Blokları' : ($source==='package' ? 'Paket Blokları' : 'Özel Bloklar'),
        'usage'=>['any'],
        'settings'=>[],
        'post_meta'=>[],
        'view'=>$file,
        'simple'=>true,
        'engine'=>'omg',
    ];
}
function omurga_scan_blocks_dir(string $dir, string $source, ?string $theme=null): array {
    $blocks=[];
    if(!is_dir($dir)) return $blocks;
    foreach((glob($dir.'/*/block.json') ?: []) as $json){
        $data=omurga_read_block_json($json, $source, $theme);
        if($data) $blocks[$data['slug']]=$data;
    }
    foreach((glob($dir.'/*.php') ?: []) as $file){
        $data=omurga_simple_php_block($file, $source, $theme);
        if($data && !isset($blocks[$data['slug']])) $blocks[$data['slug']]=$data;
    }
    foreach((glob($dir.'/*.omg') ?: []) as $file){
        $data=omurga_simple_omg_block($file, $source, $theme);
        if($data && !isset($blocks[$data['slug']])) $blocks[$data['slug']]=$data;
    }
    return $blocks;
}
function omurga_theme_blocks(?string $slug=null): array {
    $slug=$slug ?: omurga_active_theme();
    return omurga_scan_blocks_dir(omurga_theme_dir($slug).'/blocks', 'theme', $slug);
}
function omurga_custom_blocks(): array {
    return omurga_scan_blocks_dir(OMURGA_ROOT.'/storage/blocks', 'custom', null);
}
function omurga_block_source_label(array $def): string {
    return match($def['source'] ?? 'core') {
        'theme' => 'Tema',
        'custom' => 'Özel',
        'plugin' => 'Eski Eklenti',
        'package' => 'Paket',
        'registered' => 'Kayıtlı',
        default => 'Sistem',
    };
}
function omurga_block_aliases(): array {
    return [
        'son-yazilar'=>'latest-content',
        'son-haberler'=>'latest-content',
        'html-kod'=>'html-text',
        'giris-kayit'=>'auth-box',
    ];
}
function omurga_normalize_block_slug(string $slug): string {
    $slug=slugify($slug);
    $aliases=omurga_block_aliases();
    return $aliases[$slug] ?? $slug;
}
function omurga_normalize_block_definition($id, array $definition=[]): ?array {
    return Omurga_BlockRegistry::normalize($id, $definition);
}
function omurga_register_block($id, array $definition=[]): void {
    Omurga_BlockRegistry::register($id, $definition);
}
function omurga_registered_blocks(): array {
    omurga_seed_block_registry();
    return Omurga_BlockRegistry::all();
}
function omurga_block_registry_warnings(): array {
    return Omurga_BlockRegistry::warnings();
}
function omurga_block_categories(): array {
    return Omurga_BlockRegistry::categories();
}
function omurga_block_context_from_region(?string $region): ?string {
    return Omurga_BlockRegistry::contextFromRegion($region);
}
function omurga_seed_block_registry(): void {
    static $seeded=false;
    if($seeded) return;
    $seeded=true;
    foreach([omurga_core_blocks(), omurga_theme_blocks(), omurga_custom_blocks(), omurga_package_blocks()] as $group){
        foreach($group as $id=>$block){
            Omurga_BlockRegistry::register($block['id'] ?? $block['slug'] ?? $id, $block);
        }
    }
}
function omurga_available_blocks(?string $region=null): array {
    omurga_seed_block_registry();
    return Omurga_BlockRegistry::all($region);
}
function omurga_block_definition(string $slug): ?array {
    $slug=omurga_normalize_block_slug($slug);
    omurga_seed_block_registry();
    return Omurga_BlockRegistry::get($slug);
}
function omurga_block_defaults(string $slug): array {
    $slug=omurga_normalize_block_slug($slug);
    $def=omurga_block_definition($slug); $out=[];
    foreach(($def['default_settings'] ?? []) as $key=>$value){ $out[$key]=$value; }
    foreach(($def['settings_schema'] ?? $def['settings'] ?? []) as $key=>$field){
        if(is_array($field) && !array_key_exists($key,$out)) $out[$key]=$field['default'] ?? '';
    }
    return $out;
}
function omurga_layout_key(?string $theme=null): string { return 'layout_global'; }
function omurga_legacy_layout_key(?string $theme=null): string { return 'layout_'.($theme ?: omurga_active_theme()); }
function omurga_default_layout(?string $theme=null): array {
    $theme=$theme ?: omurga_active_theme();
    $meta=omurga_theme_meta($theme);
    if(!empty($meta['default_layout']) && is_array($meta['default_layout'])) return $meta['default_layout'];
    return [
        'home_main'=>[
            ['id'=>'b'.time().'01','slug'=>'latest-content','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['title'=>'Son İçerikler','limit'=>8,'view'=>'card']],
        ],
        'footer'=>[
            ['id'=>'b'.time().'02','slug'=>'sosyal-medya','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['title'=>'Sosyal Medya']],
        ],
    ];
}
function omurga_layout(?string $theme=null): array {
    $theme=$theme ?: omurga_active_theme();
    $layout=omurga_read_builder_layout(omurga_layout_key($theme), []);
    if(!$layout){
        $legacy=setting_json(omurga_legacy_layout_key($theme), []);
        if($legacy){ $layout=$legacy; omurga_update_layout($layout, $theme); }
    }
    if(!$layout){ $layout=omurga_default_layout($theme); omurga_update_layout($layout, $theme); }
    foreach($layout as $region=>$blocks){ usort($layout[$region], fn($a,$b)=>(int)($a['sort']??0) <=>(int)($b['sort']??0)); }
    return $layout;
}
function omurga_block_widths(): array { return ['100'=>'%100','75'=>'%75','70'=>'%70','67'=>'%67','66'=>'%66','50'=>'%50','33'=>'%33','30'=>'%30','25'=>'%25']; }
function omurga_block_width_value($value): string { $v=(string)$value; return array_key_exists($v, omurga_block_widths()) ? $v : '100'; }
function omurga_block_width_int($value): int { return (int)omurga_block_width_value($value); }
function omurga_block_width_class(array $block): string { return 'omg-w-'.omurga_block_width_value($block['width'] ?? '100'); }
function omurga_block_tablet_width(array $block): string {
    if(!empty($block['width_tablet'])) return omurga_block_width_value($block['width_tablet']);
    $desktop=omurga_block_width_int($block['width'] ?? '100');
    return $desktop <= 33 ? '50' : ($desktop < 100 ? '100' : '100');
}
function omurga_block_mobile_width(array $block): string {
    return !empty($block['width_mobile']) ? omurga_block_width_value($block['width_mobile']) : '100';
}
function omurga_block_responsive_style(array $block): string {
    $desktop=omurga_block_width_value($block['width'] ?? '100');
    $tablet=omurga_block_tablet_width($block);
    $mobile=omurga_block_mobile_width($block);
    return '--omg-w-desktop:'.$desktop.'%;--omg-w-tablet:'.$tablet.'%;--omg-w-mobile:'.$mobile.'%;';
}
function omurga_layout_rows(array $blocks): array {
    usort($blocks, fn($a,$b)=>(int)($a['sort']??0) <=>(int)($b['sort']??0));
    $rows=[]; $row=[]; $sum=0;
    foreach($blocks as $block){
        if(empty($block['enabled']) && array_key_exists('enabled',$block)) { /* admin still shows disabled blocks in rows */ }
        $w=omurga_block_width_int($block['width'] ?? '100');
        if($row && ($sum + $w) > 100){ $rows[]=$row; $row=[]; $sum=0; }
        $row[]=$block; $sum += $w;
        if($sum >= 100){ $rows[]=$row; $row=[]; $sum=0; }
    }
    if($row) $rows[]=$row;
    return $rows;
}
function omurga_smart_width_for_new(array $blocks): string {
    $rows=omurga_layout_rows($blocks); $last=$rows ? end($rows) : [];
    $sum=0; foreach($last as $b){ $sum += omurga_block_width_int($b['width'] ?? '100'); }
    $remaining=max(0,100-$sum);
    if($remaining < 25) return '100';
    $allowed=array_keys(omurga_block_widths()); $allowed=array_map('intval',$allowed); rsort($allowed);
    foreach($allowed as $w){ if($w <= $remaining) return (string)$w; }
    return '100';
}
function omurga_posts_for_block(array $block, array $context=[]): array {
    $settings=$block['settings'] ?? []; $limit=max(1,(int)($settings['limit'] ?? $block['limit'] ?? 6));
    if(($settings['source'] ?? '')==='meta' && !empty($settings['meta_key'])) return omurga_posts_by_meta((string)$settings['meta_key'], (string)($settings['meta_value'] ?? '1'), $limit);
    $postsT=table_name('posts'); $catsT=table_name('categories');
    try{
        if(($settings['source'] ?? '')==='latest'){
            return db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.published_at DESC,p.id DESC LIMIT $limit")->fetchAll();
        }
        if(!empty($settings['category_id'])){
            $st=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.category_id=? ORDER BY p.published_at DESC,p.id DESC LIMIT $limit");
            $st->execute([(int)$settings['category_id']]); return $st->fetchAll();
        }
        return db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.published_at DESC,p.id DESC LIMIT $limit")->fetchAll();
    }catch(Throwable $e){ omurga_write_error($e); return []; }
}
function omurga_render_core_block(array $block, array $context=[]): string {
    $slug=$block['slug'] ?? ''; $settings=$block['settings'] ?? [];
    ob_start();
    if(in_array($slug, ['son-yazilar','kategori-yazilari','son-haberler','kategori-haberleri'], true)){
        $posts=omurga_posts_for_block($block,$context); $title=$settings['title'] ?? (in_array($slug,['son-yazilar','son-haberler'],true)?'Son Yazılar':'Kategori Yazıları');
        echo '<section class="omg-block omg-core-news"><div class="v11-section-head"><h2>'.e($title).'</h2></div><div class="post-grid v11-latest-grid">';
        foreach($posts as $item){ echo '<article class="post-card">'; if(!empty($item['featured_image'])) echo '<img class="card-img" src="'.e(image_url($item['featured_image'])).'" alt="'.e($item['title']).'">'; else echo '<div class="fake-img">Omurga</div>'; echo '<div class="body"><small>'.e($item['category_name'] ?? 'Genel').'</small><h3><a href="'.e(post_url($item)).'">'.e($item['title']).'</a></h3><p>'.e(excerpt($item['spot'] ?: $item['content'],110)).'</p></div></article>'; }
        echo '</div></section>';
    } elseif($slug==='reklam-alani'){
        echo render_ad_slot($settings['slot'] ?? 'home');
    } elseif($slug==='html-kod'){
        $title=trim((string)($settings['title'] ?? '')); echo '<section class="omg-block omg-html-block">'; if($title) echo '<h2>'.e($title).'</h2>'; echo (string)($settings['html'] ?? ''); echo '</section>';
    } elseif($slug==='menu'){
        echo '<div class="omg-block omg-menu-block"><strong>'.e($settings['title'] ?? 'Menü').'</strong>'; foreach(menu_items('main') as $mi){ echo '<a href="'.e($mi['url']).'">'.e($mi['title']).'</a>'; } echo '</div>';
    } elseif($slug==='sosyal-medya'){
        echo '<div class="omg-block omg-social-block"><strong>'.e($settings['title'] ?? 'Sosyal Medya').'</strong>'; if(setting('facebook_url')) echo '<a target="_blank" href="'.e(setting('facebook_url')).'">Facebook</a>'; if(setting('instagram_url')) echo '<a target="_blank" href="'.e(setting('instagram_url')).'">Instagram</a>'; if(setting('twitter_url')) echo '<a target="_blank" href="'.e(setting('twitter_url')).'">X</a>'; echo '</div>';
    } elseif($slug==='giris-kayit'){
        $title=trim((string)($settings['title'] ?? 'Üye Paneli'));
        $login=trim((string)($settings['login_url'] ?? 'admin/login.php'));
        $register=trim((string)($settings['register_url'] ?? '#kayit'));
        $showReg=!empty($settings['show_register']);
        echo '<section class="omg-block omg-login-block">';
        if($title) echo '<strong>'.e($title).'</strong>';
        echo '<p>İçerik yönetimi ve kullanıcı işlemleri için hızlı erişim.</p><div class="omg-login-actions">';
        echo '<a class="omg-login-btn primary" href="'.e(omurga_url($login)).'">Giriş Yap</a>';
        if($showReg) echo '<a class="omg-login-btn" href="'.e(str_starts_with($register,'http')?$register:omurga_url($register)).'">Kayıt Ol</a>';
        echo '</div></section>';
    } elseif($slug==='sayfa-icerigi' && !empty($context['post'])){
        echo omurga_render_shortcodes($context['post']['content'] ?? '');
    }
    return ob_get_clean();
}
function omurga_render_missing_block_placeholder(array $block, string $slug): string {
    $slug=omurga_normalize_block_slug($slug);
    $title=om_t('blocks.missing','Eksik blok');
    $desc=om_t('blocks.missing_description','Bu blok artık kayıtlı değil; düzen verisi korunuyor.');
    $classes='omg-layout-item '.omurga_block_width_class($block).' omg-block-missing omg-block-slug-'.e($slug);
    if(!empty($block['hide_mobile'])) $classes.=' omg-hide-mobile';
    if(!empty($block['mobile_scroll'])) $classes.=' omg-mobile-scroll';
    $html='<section class="omg-missing-block"><strong>'.e($title).'</strong><small>'.e($slug ?: 'unknown').'</small><p>'.e($desc).'</p></section>';
    return '<div class="'.$classes.'" style="'.e(omurga_block_responsive_style($block)).'">'.$html.'</div>';
}
function omurga_render_block(array $block, array $context=[]): string {
    if(empty($block['enabled'])) return '';
    $originalSlug=$block['slug'] ?? '';
    $slug=omurga_normalize_block_slug((string)$originalSlug);
    $block['slug']=$slug;
    $def=omurga_block_definition($slug);
    if(!$def) return omurga_render_missing_block_placeholder($block,$slug);
    $settings=array_merge(omurga_block_defaults($slug), $block['settings'] ?? []);
    $block['settings']=$settings;
    $content='';
    if(!empty($def['render_callback']) && is_callable($def['render_callback'])){
        try{ $content=(string)call_user_func($def['render_callback'], $block, $context); }
        catch(Throwable $e){ omurga_write_error($e); $content=''; }
    } elseif(in_array(($def['source'] ?? ''), ['core','theme','custom','plugin','package','registered'], true) && !empty($def['view']) && file_exists($def['view'])){
        $posts=omurga_posts_for_block($block,$context);
        if(str_ends_with((string)$def['view'], '.omg')){
            $content=omurga_render_omg($def['view'], ['block'=>$block,'settings'=>$settings,'posts'=>$posts]+$context);
        } else {
            ob_start(); include $def['view']; $content=ob_get_clean();
        }
    } else {
        $content=omurga_render_core_block($block,$context);
    }
    if(trim($content)==='') return '';
    $classes='omg-layout-item '.omurga_block_width_class($block).' omg-block-slug-'.e($slug);
    if(!empty($block['hide_mobile'])) $classes.=' omg-hide-mobile';
    if(!empty($block['mobile_scroll'])) $classes.=' omg-mobile-scroll';
    return '<div class="'.$classes.'" style="'.e(omurga_block_responsive_style($block)).'">'.$content.'</div>';
}
function omurga_render_region(string $region, array $context=[]): string {
    $layout=omurga_layout(); $blocks=$layout[$region] ?? [];
    $out=''; $rowNo=1;
    foreach(omurga_layout_rows($blocks) as $row){
        $rowHtml=''; foreach($row as $block){ $rowHtml.=omurga_render_block($block,$context); }
        if(trim($rowHtml)!=='') $out.='<div class="omg-layout-row omg-layout-row-'.$rowNo.'">'.$rowHtml.'</div>';
        $rowNo++;
    }
    return $out ? '<div class="omg-region omg-region-'.e($region).'">'.$out.'</div>' : '';
}
function omurga_parse_shortcode_attrs(string $text): array {
    $attrs=[]; preg_match_all('/([a-zA-Z0-9_\-]+)="([^"]*)"/', $text, $m, PREG_SET_ORDER);
    foreach($m as $a){ $attrs[$a[1]]=$a[2]; }
    return $attrs;
}
function omurga_render_shortcodes(?string $html): string {
    $html=(string)($html ?? '');
    $html=preg_replace_callback('/\[(?:form|omurga_form)\s*([^\]]*)\]/u', function($m){
        $attrs=omurga_parse_shortcode_attrs($m[1] ?? '');
        $id=$attrs['id'] ?? ($attrs['slug'] ?? '');
        return omurga_render_form($id, $attrs);
    }, $html);
    return preg_replace_callback('/\[blok\s+([^\]]+)\]/u', function($m){
        $attrs=omurga_parse_shortcode_attrs($m[1]); $slug=slugify($attrs['slug'] ?? ''); if(!$slug) return '';
        $settings=$attrs; unset($settings['slug']);
        $block=['slug'=>$slug,'enabled'=>1,'sort'=>0,'settings'=>$settings];
        return omurga_render_block($block, ['shortcode'=>true]);
    }, $html);
}


/* Forms v2: Form Builder + shortcode renderer + submissions */
function omurga_forms_v2_tables(): array {
    return [
        'definitions' => table_name('form_definitions'),
        'submissions' => table_name('forms'),
    ];
}
function omurga_ensure_forms_v2_tables(): void {
    static $done=false; if($done) return; $done=true;
    try{
        $t=omurga_forms_v2_tables();
        db()->exec("CREATE TABLE IF NOT EXISTS {$t['definitions']} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, form_type VARCHAR(60) NOT NULL DEFAULT 'contact', description TEXT NULL, fields MEDIUMTEXT NULL, status VARCHAR(30) NOT NULL DEFAULT 'active', submit_label VARCHAR(80) NOT NULL DEFAULT 'Gönder', success_message VARCHAR(255) NOT NULL DEFAULT 'Başvurunuz alındı. En kısa sürede dönüş yapılacaktır.', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX(status), INDEX(form_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $count=(int)db()->query("SELECT COUNT(*) FROM {$t['definitions']}")->fetchColumn();
        if($count===0){
            $fields=json_encode(omurga_default_form_fields(), JSON_UNESCAPED_UNICODE);
            $stmt=db()->prepare("INSERT INTO {$t['definitions']} (title,slug,form_type,description,fields,status,submit_label,success_message) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute(['İletişim Formu','iletisim-formu','contact','Varsayılan iletişim formu',$fields,'active','Gönder','Mesajınız alındı. En kısa sürede dönüş yapılacaktır.']);
        }
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_default_form_fields(): array {
    return [
        ['key'=>'name','label'=>'Ad Soyad','type'=>'text','required'=>1,'placeholder'=>'Adınız ve soyadınız'],
        ['key'=>'phone','label'=>'Telefon','type'=>'tel','required'=>0,'placeholder'=>'Telefon numaranız'],
        ['key'=>'email','label'=>'E-posta','type'=>'email','required'=>0,'placeholder'=>'E-posta adresiniz'],
        ['key'=>'message','label'=>'Mesaj','type'=>'textarea','required'=>0,'placeholder'=>'Mesajınız'],
    ];
}
function omurga_form_fields_decode($json): array {
    $arr=is_array($json)?$json:json_decode((string)$json,true);
    if(!is_array($arr) || !$arr) return omurga_default_form_fields();
    $out=[];
    foreach($arr as $f){
        if(!is_array($f)) continue;
        $key=preg_replace('/[^a-zA-Z0-9_\-]/','',(string)($f['key'] ?? ''));
        $label=trim((string)($f['label'] ?? ''));
        $type=strtolower(preg_replace('/[^a-z0-9_\-]/','',(string)($f['type'] ?? 'text')));
        if($key==='' || $label==='') continue;
        if(!in_array($type,['text','email','tel','textarea','select','checkbox','number','url'],true)) $type='text';
        $out[]=[
            'key'=>$key,
            'label'=>$label,
            'type'=>$type,
            'required'=>!empty($f['required'])?1:0,
            'placeholder'=>(string)($f['placeholder'] ?? ''),
            'options'=>(string)($f['options'] ?? ''),
        ];
    }
    return $out ?: omurga_default_form_fields();
}
function omurga_get_form_definition($idOrSlug=''): ?array {
    omurga_ensure_forms_v2_tables();
    $t=omurga_forms_v2_tables()['definitions'];
    try{
        if(is_numeric($idOrSlug) && (int)$idOrSlug>0){
            $stmt=db()->prepare("SELECT * FROM $t WHERE id=? AND status='active' LIMIT 1");
            $stmt->execute([(int)$idOrSlug]);
        } else {
            $slug=slugify((string)$idOrSlug);
            if($slug==='') $slug='iletisim-formu';
            $stmt=db()->prepare("SELECT * FROM $t WHERE slug=? AND status='active' LIMIT 1");
            $stmt->execute([$slug]);
        }
        $r=$stmt->fetch(); return $r ?: null;
    }catch(Throwable $e){ omurga_write_error($e); return null; }
}
function omurga_render_form($idOrSlug='', array $attrs=[]): string {
    $form=omurga_get_form_definition($idOrSlug ?: ($attrs['slug'] ?? 'iletisim-formu'));
    if(!$form) return '<div class="alert danger">Form bulunamadı veya pasif.</div>';
    $fields=omurga_form_fields_decode($form['fields'] ?? '');
    $html='<form method="post" class="form-grid omurga-dynamic-form" id="omurga-form-'.(int)$form['id'].'">';
    $html.='<input type="hidden" name="omurga_form" value="1"><input type="hidden" name="form_id" value="'.(int)$form['id'].'"><input type="hidden" name="form_type" value="'.e($form['form_type']).'">';
    foreach($fields as $f){
        $name='field_'.$f['key']; $required=$f['required']?' required':''; $ph=$f['placeholder']!==''?' placeholder="'.e($f['placeholder']).'"':'';
        $html.='<label>'.e($f['label']);
        if($f['type']==='textarea') $html.='<textarea name="'.e($name).'"'.$required.$ph.' style="min-height:120px"></textarea>';
        elseif($f['type']==='select'){
            $html.='<select name="'.e($name).'"'.$required.'><option value="">Seçiniz</option>';
            foreach(preg_split('/\r\n|\r|\n/', (string)$f['options']) as $opt){ $opt=trim($opt); if($opt!=='') $html.='<option value="'.e($opt).'">'.e($opt).'</option>'; }
            $html.='</select>';
        } elseif($f['type']==='checkbox') {
            $html.='<span class="checkline"><input type="checkbox" name="'.e($name).'" value="1"'.$required.'> '.e($f['placeholder'] ?: 'Onaylıyorum').'</span>';
        } else {
            $type=in_array($f['type'],['email','tel','number','url'],true)?$f['type']:'text';
            $html.='<input type="'.e($type).'" name="'.e($name).'"'.$required.$ph.'>';
        }
        $html.='</label>';
    }
    $html.='<button class="btn primary">'.e($form['submit_label'] ?: 'Gönder').'</button></form>';
    return $html;
}
function omurga_handle_form_submission(): string {
    if($_SERVER['REQUEST_METHOD']!=='POST' || ($_POST['omurga_form'] ?? '')!=='1') return '';
    omurga_ensure_forms_v2_tables();
    $tables=omurga_forms_v2_tables();
    try{
        $formId=(int)($_POST['form_id'] ?? 0);
        $form=$formId>0 ? omurga_get_form_definition($formId) : null;
        $fields=$form ? omurga_form_fields_decode($form['fields'] ?? '') : omurga_default_form_fields();
        $values=[]; $labels=[];
        foreach($fields as $f){
            $key=$f['key']; $raw=$_POST['field_'.$key] ?? ($_POST[$key] ?? '');
            $value=is_array($raw)?implode(', ', array_map('trim',$raw)):trim((string)$raw);
            if(!empty($f['required']) && $value==='') throw new RuntimeException($f['label'].' zorunludur.');
            $values[$key]=$value; $labels[$key]=$f['label'];
        }
        $name=trim($values['name'] ?? $values['ad_soyad'] ?? $values['ad'] ?? ($_POST['name'] ?? ''));
        if($name==='') $name='Form Başvurusu';
        $phone=trim($values['phone'] ?? $values['telefon'] ?? ($_POST['phone'] ?? ''));
        $email=trim($values['email'] ?? $values['eposta'] ?? ($_POST['email'] ?? ''));
        $message=trim($values['message'] ?? $values['mesaj'] ?? ($_POST['message'] ?? ''));
        $formType=trim((string)($form['form_type'] ?? ($_POST['form_type'] ?? 'contact')));
        $meta=['form_id'=>$formId ?: null,'form_title'=>$form['title'] ?? 'Klasik Form','fields'=>$values,'labels'=>$labels,'ip'=>$_SERVER['REMOTE_ADDR']??'','user_agent'=>$_SERVER['HTTP_USER_AGENT']??''];
        db()->prepare("INSERT INTO {$tables['submissions']} (form_type,name,phone,email,message,status,meta) VALUES (?,?,?,?,?,'new',?)")->execute([$formType,$name,$phone,$email,$message,json_encode($meta,JSON_UNESCAPED_UNICODE)]);
        return (string)($form['success_message'] ?? 'Başvurunuz alındı. En kısa sürede dönüş yapılacaktır.');
    }catch(Throwable $e){ omurga_write_error($e); return $e->getMessage(); }
}


/* Omurga ortak şablon yardımcıları. Resmi tema motoru .omg'dir. */
function omurga_tpl_dot(array $data, string $path, $default='') {
    $path=trim($path); if($path==='') return $default;
    $parts=explode('.', $path); $cur=$data;
    foreach($parts as $part){
        if(is_array($cur) && array_key_exists($part,$cur)) $cur=$cur[$part];
        else return $default;
    }
    return $cur;
}
function omurga_tpl_cache_dir(): string {
    $dir=OMURGA_ROOT.'/storage/cache/templates';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    return $dir;
}
function omurga_clear_tpl_cache(): void {
    foreach((glob(omurga_tpl_cache_dir().'/*.php') ?: []) as $file){ if(is_file($file)) @unlink($file); }
}
function omurga_omg_cache_dir(): string {
    $dir=OMURGA_ROOT.'/storage/cache/templates';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    return $dir;
}
function omurga_clear_omg_cache(): void {
    foreach((glob(omurga_omg_cache_dir().'/*.omg.cache') ?: []) as $file){ if(is_file($file)) @unlink($file); }
}
function omurga_omg_log(string $message): void {
    if(function_exists('omurga_write_error')){
        try{ omurga_write_error(new RuntimeException($message)); }catch(Throwable $e){}
    }
}
function omurga_tpl_strip_php(string $tpl): string {
    return (string)preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i','',$tpl);
}
function omurga_omg_strip_php(string $tpl, string $file=''): string {
    if(preg_match('/<\?(?:php|=)?/i', $tpl)){
        omurga_omg_log('OMG şablon içinde PHP etiketi engellendi'.($file ? ': '.$file : ''));
    }
    return omurga_tpl_strip_php($tpl);
}
function omurga_tpl_read_file(string $file): string {
    $real=realpath($file);
    if(!$real || !is_file($real)) return '';
    $cache=omurga_tpl_cache_dir().'/'.sha1($real.'|'.filemtime($real)).'.php';
    if(is_file($cache)) return (string)file_get_contents($cache);
    $tpl=omurga_tpl_strip_php((string)file_get_contents($real));
    @file_put_contents($cache, $tpl, LOCK_EX);
    return $tpl;
}
function omurga_omg_read_file(string $file): string {
    $real=realpath($file);
    if(!$real || !is_file($real)) return '';
    $cache=omurga_omg_cache_dir().'/'.sha1($real.'|'.filemtime($real)).'.omg.cache';
    if(is_file($cache)) return (string)file_get_contents($cache);
    $tpl=omurga_omg_strip_php((string)file_get_contents($real), $real);
    @file_put_contents($cache, $tpl, LOCK_EX);
    return $tpl;
}
function omurga_tpl_safe_include(string $baseDir, string $name): string {
    $name=str_replace('\\','/',$name);
    if($name==='' || str_contains($name, '..') || str_starts_with($name, '/')) return '';
    $file=realpath(rtrim($baseDir,'/\\').'/'.ltrim($name,'/'));
    $base=realpath($baseDir);
    if(!$file || !$base || !is_file($file)) return '';
    if(!str_starts_with(str_replace('\\','/',$file), rtrim(str_replace('\\','/',$base),'/').'/')) return '';
    return $file;
}
function omurga_tpl_safe_data($value) {
    if(is_scalar($value) || $value===null) return $value;
    if(!is_array($value)) return '';
    $out=[];
    foreach($value as $k=>$v){
        $key=is_int($k) ? $k : preg_replace('/[^a-zA-Z0-9_:-]/','',(string)$k);
        if($key==='' && !is_int($k)) continue;
        $out[$key]=omurga_tpl_safe_data($v);
    }
    return $out;
}
function omurga_tpl_safe_html(string $html): string {
    $html=(string)preg_replace('/<\s*script\b[^>]*>[\s\S]*?<\s*\/\s*script\s*>/i','',$html);
    $html=(string)preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i','',$html);
    return (string)preg_replace('/javascript\s*:/i','',$html);
}
function omurga_tpl_post(array $post): array {
    $img=$post['featured_image'] ?? '';
    $videoUrl=trim((string)($post['video_url'] ?? ''));
    $videoEmbed=omurga_video_embed_url($videoUrl);
    $galleryItems=omurga_gallery_items($post['gallery_images'] ?? '');
    $galleryHtml='';
    if($galleryItems){
        $galleryHtml='<div class="omg-tpl-gallery">';
        foreach($galleryItems as $g){ $galleryHtml.='<img src="'.e(image_url($g)).'" alt="'.e($post['title'] ?? '').'">'; }
        $galleryHtml.='</div>';
    }
    $videoHtml='';
    if($videoEmbed){
        if(preg_match('~\\.mp4($|\\?)~i',$videoEmbed)) $videoHtml='<video class="omg-tpl-video" controls src="'.e($videoEmbed).'"></video>';
        else $videoHtml='<div class="omg-tpl-video"><iframe src="'.e($videoEmbed).'" loading="lazy" allowfullscreen></iframe></div>';
    }
    return array_merge($post, [
        'title'=>$post['title'] ?? '',
        'spot'=>$post['spot'] ?? '',
        'short'=>excerpt($post['spot'] ?: ($post['content'] ?? ''), 160),
        'excerpt'=>excerpt($post['spot'] ?: ($post['content'] ?? ''), 160),
        'content'=>omurga_render_post_content($post),
        'full'=>omurga_render_post_content($post),
        'link'=>post_url($post),
        'url'=>post_url($post),
        'image'=>image_url($img),
        'category'=>$post['category_name'] ?? '',
        'category_link'=>!empty($post['category_slug']) ? omurga_url('kategori/'.$post['category_slug']) : '',
        'author'=>$post['author_name'] ?? '',
        'date'=>!empty($post['published_at']) ? date('d.m.Y H:i', strtotime($post['published_at'])) : '',
        'video_url'=>$videoUrl,
        'video'=>$videoHtml,
        'gallery'=>$galleryHtml,
        'comments_count'=>om_comments_count((int)($post['id'] ?? 0)),
        'comments_enabled'=>om_post_comments_enabled($post) ? '1' : '0',
    ]);
}
function omurga_tpl_context(array $vars=[]): array {
    if(!isset($vars['post']) && isset($vars['page']) && is_array($vars['page'])) $vars['post']=$vars['page'];
    $base=[
        'site'=>[
            'name'=>setting('site_name','Omurga'),
            'description'=>setting('site_description',''),
            'url'=>omurga_url(),
            'language'=>omurga_site_language(),
            'theme_url'=>omurga_theme_url(),
        ],
        'theme'=>[
            'url'=>omurga_theme_url(),
            'name'=>omurga_theme_meta()['name'] ?? omurga_active_theme(),
        ],
        'title'=>$vars['title'] ?? setting('site_name','Omurga'),
        'meta'=>$vars['meta'] ?? setting('site_description',''),
        'theme_url'=>omurga_theme_url(),
        'base_url'=>omurga_url(),
    ];
    if(isset($vars['post']) && is_array($vars['post'])){
        $post=omurga_tpl_post($vars['post']);
        $base['post']=$post;
        foreach(['title','spot','short','content','full','link','image','category','category_link','author','date','video','gallery'] as $k){ $base[$k]=$post[$k] ?? ''; }
    }
    if(isset($vars['posts']) && is_array($vars['posts'])){
        $base['posts']=array_map(fn($p)=>is_array($p)?omurga_tpl_post($p):$p, $vars['posts']);
    }
    if(isset($vars['latest']) && is_array($vars['latest'])){
        $base['latest']=array_map(fn($p)=>is_array($p)?omurga_tpl_post($p):$p, $vars['latest']);
    }
    if(isset($vars['category']) && is_array($vars['category'])){
        $base['category']=$vars['category'];
        $base['category_name']=$vars['category']['name'] ?? '';
        $base['category_description']=$vars['category']['description'] ?? '';
    }
    return omurga_tpl_safe_data(array_replace_recursive($base, $vars));
}
function omurga_render_tpl(string $file, array $vars=[]): string {
    throw new RuntimeException('.tpl desteği Omurga v4 ile kaldırıldı. Lütfen .omg şablon kullanın.');
}
function omurga_render_tpl_string(string $tpl, array $data, string $baseDir='', int $depth=0): string {
    return ''; // v4: TPL sözdizimi render edilmez.
}
/* Omurga OMG Template Engine
   HTML tabanli resmi tema motoru: PHP calistirmaz, varsayilan cikti escape edilir. */
function omurga_omg_file(string $name, ?string $slug=null): string {
    $name=str_replace('\\','/',trim($name));
    $name=preg_replace('/[^a-zA-Z0-9_\-\/\.]/','',$name);
    if($name==='' || str_contains($name,'..') || str_starts_with($name,'/')) return '';
    if(!str_ends_with($name,'.omg')) $name.='.omg';
    $base=realpath(omurga_theme_dir($slug));
    $file=realpath(omurga_theme_dir($slug).'/'.$name);
    if(!$base || !$file || !is_file($file)) return '';
    $base=str_replace('\\','/',$base);
    $file=str_replace('\\','/',$file);
    return str_starts_with($file, rtrim($base,'/').'/') ? $file : '';
}
function omurga_omg_truthy(string $expr, array $data): bool {
    $expr=trim($expr);
    if($expr==='') return false;
    $neg=false;
    if(str_starts_with($expr,'!')){ $neg=true; $expr=trim(substr($expr,1)); }
    $result=false;
    if(preg_match('/^([a-zA-Z0-9_\.]+)\s*(==|!=)\s*[\'"]?([^\'"]*)[\'"]?$/',$expr,$m)){
        $value=(string)omurga_tpl_dot($data,$m[1],'');
        $result=$m[2]==='==' ? $value===$m[3] : $value!==$m[3];
    } else {
        $value=omurga_tpl_dot($data,$expr,'');
        $result=!empty($value);
    }
    return $neg ? !$result : $result;
}
function omurga_omg_eval(string $expr, array $data) {
    $expr=trim($expr);
    if($expr==='') return '';
    if(preg_match('/^asset\s*\(\s*[\'"]([^\'"]*)[\'"]\s*\)$/i', $expr, $m)){
        $path=str_replace('\\','/',trim($m[1]));
        if($path==='' || str_contains($path,'..') || str_starts_with($path,'/')) return '';
        if(!str_starts_with($path,'assets/')) $path='assets/'.ltrim($path,'/');
        return omurga_theme_url($path);
    }
    if(preg_match('/^url\s*\(\s*[\'"]([^\'"]*)[\'"]\s*\)$/i', $expr, $m)){
        return omurga_url($m[1]);
    }
    if(!preg_match('/^[a-zA-Z0-9_\.]+$/', $expr)) return '';
    return omurga_tpl_dot($data,$expr,'');
}
function omurga_omg_content(array $data): string {
    $content=(string)omurga_tpl_dot($data,'content','');
    if($content!=='') return omurga_tpl_safe_html($content);
    $postContent=(string)omurga_tpl_dot($data,'post.content','');
    if($postContent!=='') return omurga_tpl_safe_html($postContent);
    $region=(string)omurga_tpl_dot($data,'content_region','home_main');
    return omurga_render_region($region, $data);
}
function omurga_render_omg(string $file, array $vars=[]): string {
    if(!is_file($file)) throw new RuntimeException('OMG dosyasi bulunamadi: '.$file);
    omurga_do_action('omurga_before_omg_render', $file, $vars);
    $html=omurga_render_omg_string(omurga_omg_read_file($file), omurga_tpl_context($vars), 0);
    return (string)omurga_apply_filters('omurga_after_omg_render', $html, $file, $vars);
}
function omurga_render_omg_string(string $tpl, array $data, int $depth=0): string {
    if($depth>10) return '';
    $tpl=omurga_omg_strip_php($tpl);
    $tpl=preg_replace_callback('/@include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/i', function($m) use($data,$depth){
        $file=omurga_omg_file($m[1]);
        if(!$file){ omurga_omg_log('OMG include bulunamadı veya tema dışı: '.$m[1]); return ''; }
        return omurga_render_omg_string(omurga_omg_read_file($file), $data, $depth+1);
    }, $tpl);
    $tpl=preg_replace_callback('/@foreach\s*\(\s*([a-zA-Z0-9_\.]+)\s+as\s+([a-zA-Z0-9_]+)\s*\)([\s\S]*?)@endforeach/i', function($m) use($data,$depth){
        $items=omurga_tpl_dot($data,$m[1],[]);
        if(!is_array($items)) return '';
        $out=''; $i=0;
        foreach($items as $item){
            $row=$data;
            $row[$m[2]]=is_array($item)?$item:['value'=>$item];
            $row['loop']=['index'=>$i,'number'=>$i+1,'first'=>$i===0?'1':'0'];
            $out.=omurga_render_omg_string($m[3], $row, $depth+1);
            $i++;
        }
        return $out;
    }, $tpl);
    $tpl=preg_replace_callback('/@if\s*\((.*?)\)([\s\S]*?)@endif/i', function($m) use($data,$depth){
        return omurga_omg_truthy($m[1], $data) ? omurga_render_omg_string($m[2], $data, $depth+1) : '';
    }, $tpl);
    $tpl=preg_replace_callback('/<omg:content\s*\/>/i', fn()=>omurga_omg_content($data), $tpl);
    $tpl=preg_replace_callback('/<omg:comments\s*\/>/i', function() use($data){
        $postId=(int)omurga_tpl_dot($data,'post.id',0);
        return $postId>0 ? om_comments_list($postId) : '';
    }, $tpl);
    $tpl=preg_replace_callback('/<omg:comment-form\s*\/>/i', function() use($data){
        $postId=(int)omurga_tpl_dot($data,'post.id',0);
        return $postId>0 ? om_comment_form($postId) : '';
    }, $tpl);
    $tpl=preg_replace_callback('/\{!!\s*([a-zA-Z0-9_\.]+)\s*!!\}/', function($m) use($data){
        $value=omurga_omg_eval($m[1],$data);
        return is_array($value) ? '' : omurga_tpl_safe_html((string)$value);
    }, $tpl);
    $tpl=preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+|(?:asset|url)\s*\(\s*[\'"][^\'"]*[\'"]\s*\))\s*\}\}/i', function($m) use($data){
        $value=omurga_omg_eval($m[1],$data);
        return is_array($value) ? '' : e((string)$value);
    }, $tpl);
    return $tpl;
}
function omurga_normalize_template_engine($engine): string {
    $engine=strtolower(trim((string)$engine));
    return in_array($engine,['omg','php','hybrid'],true) ? $engine : '';
}
function omurga_infer_theme_engine_from_dir(string $dir, array $meta=[]): string {
    $declared=omurga_normalize_template_engine($meta['template_engine'] ?? ($meta['engine'] ?? ''));
    if($declared) return $declared;
    if(is_file($dir.'/home.php') || is_file($dir.'/index.php')) return 'php';
    if(is_file($dir.'/home.omg')) return 'omg';
    return 'omg';
}
function omurga_theme_engine(?string $slug=null): string {
    $slug=$slug ?: omurga_active_theme();
    $meta=omurga_theme_meta($slug);
    return omurga_infer_theme_engine_from_dir(omurga_theme_dir($slug), $meta);
}

function omurga_theme_tpl_exists(string $file): bool { return false; }
function omurga_render_theme_omg(string $omgFile, array $vars=[]): bool {
    if(omurga_theme_engine()!=='omg') return false;
    $file=omurga_omg_file($omgFile);
    if(!$file) return false;
    try{
        echo omurga_render_omg($file, $vars);
    }catch(Throwable $e){
        omurga_write_error($e);
        if(is_admin_logged_in()){
            echo '<div class="omg-template-error">'.e('OMG şablon hatası: '.$e->getMessage()).'</div>';
            return true;
        }
        return false;
    }
    return true;
}
function omurga_render_theme_tpl(string $tplFile, array $vars=[], ?string $fallbackPhp=null): bool {
    // v4: .tpl resmi tema formatı değildir. Yalnızca eski çağrılar beyaz ekran vermesin diye false döner.
    return false;
}


function omurga_block_post_meta_definitions(): array {
    $out=[];
    foreach(omurga_available_blocks() as $slug=>$def){
        foreach(($def['post_meta'] ?? []) as $key=>$field){
            $safeKey=preg_replace('/[^a-zA-Z0-9_\-]/','', (string)$key);
            if($safeKey==='') continue;
            $field=is_array($field) ? $field : ['type'=>'checkbox','label'=>(string)$field];
            $field['key']=$safeKey;
            $field['block_slug']=$slug;
            $field['block_name']=$def['name'] ?? $slug;
            $field['source']=$def['source'] ?? 'core';
            $out[$safeKey]=$field;
        }
    }
    return $out;
}
function omurga_sanitize_meta_value(array $field, $value): string {
    $type=$field['type'] ?? 'text';
    if($type==='checkbox') return !empty($value) ? '1' : '0';
    if($type==='number') return (string)(int)$value;
    if($type==='select'){
        $options=$field['options'] ?? [];
        return array_key_exists((string)$value, $options) ? (string)$value : (string)($field['default'] ?? '');
    }
    return trim((string)$value);
}
function omurga_get_post_meta_values(int $postId): array {
    if($postId<=0) return [];
    try{
        $t=table_name('post_meta');
        $st=db()->prepare("SELECT meta_key, meta_value FROM $t WHERE post_id=?");
        $st->execute([$postId]);
        $out=[];
        foreach($st->fetchAll() as $r){ $out[$r['meta_key']]=$r['meta_value']; }
        return $out;
    }catch(Throwable $e){ return []; }
}
function omurga_get_post_meta(int $postId, string $key, $default=null) {
    $all=omurga_get_post_meta_values($postId);
    return array_key_exists($key,$all) ? $all[$key] : $default;
}

function omurga_set_post_meta(int $postId, string $key, $value): void {
    if($postId<=0 || $key==='') return;
    $key=preg_replace('/[^a-zA-Z0-9_\-]/','',$key);
    $t=table_name('post_meta');
    db()->exec("CREATE TABLE IF NOT EXISTS $t (post_id INT UNSIGNED NOT NULL, meta_key VARCHAR(120) NOT NULL, meta_value MEDIUMTEXT NULL, PRIMARY KEY(post_id, meta_key), INDEX(meta_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->prepare("INSERT INTO $t (post_id, meta_key, meta_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)")->execute([$postId,$key,(string)$value]);
}

function omurga_save_post_meta_values(int $postId, array $posted): void {
    if($postId<=0) return;
    $defs=omurga_block_post_meta_definitions();
    if(!$defs) return;
    $t=table_name('post_meta');
    db()->exec("CREATE TABLE IF NOT EXISTS $t (post_id INT UNSIGNED NOT NULL, meta_key VARCHAR(120) NOT NULL, meta_value MEDIUMTEXT NULL, PRIMARY KEY(post_id, meta_key), INDEX(meta_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach($defs as $key=>$field){
        $value=omurga_sanitize_meta_value($field, $posted[$key] ?? null);
        db()->prepare("INSERT INTO $t (post_id, meta_key, meta_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)")->execute([$postId,$key,$value]);
    }
}
function omurga_posts_by_meta(string $metaKey, string $metaValue='1', int $limit=6): array {
    $metaKey=preg_replace('/[^a-zA-Z0-9_\-]/','',$metaKey);
    $limit=max(1,(int)$limit);
    if($metaKey==='') return [];
    try{
        $postsT=table_name('posts'); $catsT=table_name('categories'); $metaT=table_name('post_meta');
        $st=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p INNER JOIN $metaT pm ON pm.post_id=p.id LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND pm.meta_key=? AND pm.meta_value=? ORDER BY p.sort_order ASC,p.published_at DESC,p.id DESC LIMIT $limit");
        $st->execute([$metaKey,$metaValue]); return $st->fetchAll();
    }catch(Throwable $e){ omurga_write_error($e); return []; }
}

function omurga_public_status_label(array $post): string {
    if(($post['status'] ?? '') !== 'published') return status_label($post['status'] ?? 'draft');
    $pa = $post['published_at'] ?? '';
    if($pa && strtotime($pa) && strtotime($pa) > time()) return 'Zamanlandı';
    return 'Yayında';
}
function omurga_reserved_root_slugs(): array {
    return ['admin','install','uploads','storage','themes','plugins','assets','api','core','kategori','etiket','sayfa','sitemap','sitemap.xml','robots.txt','news-sitemap.xml','login','logout'];
}
function omurga_slug_is_reserved(string $slug): bool {
    $slug = strtolower(trim($slug, '/'));
    return in_array($slug, omurga_reserved_root_slugs(), true);
}
function omurga_unique_slug(string $slug, int $ignoreId=0): string {
    $base = $slug ?: 'icerik';
    if(omurga_slug_is_reserved($base)) $base .= '-sayfa';
    $try = $base; $i = 2; $t = table_name('posts');
    while(true){
        $sql = "SELECT id FROM $t WHERE slug=?" . ($ignoreId?" AND id<>?":"") . " LIMIT 1";
        $st = db()->prepare($sql); $params = [$try]; if($ignoreId) $params[] = $ignoreId; $st->execute($params);
        if(!$st->fetch() && !omurga_slug_is_reserved($try)) return $try;
        $try = $base.'-'.$i++;
    }
}
function tag_names_for_post(int $postId): array {
    try{
        $tags=table_name('tags'); $pt=table_name('post_tags');
        $st=db()->prepare("SELECT t.name FROM $tags t INNER JOIN $pt pt ON pt.tag_id=t.id WHERE pt.post_id=? ORDER BY t.name");
        $st->execute([$postId]); return array_column($st->fetchAll(),'name');
    }catch(Throwable $e){ return []; }
}
function sync_post_tags(int $postId, string $tagLine): void {
    $tagsT=table_name('tags'); $pt=table_name('post_tags');
    db()->exec("CREATE TABLE IF NOT EXISTS $tagsT (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS $pt (post_id INT UNSIGNED NOT NULL, tag_id INT UNSIGNED NOT NULL, PRIMARY KEY(post_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->prepare("DELETE FROM $pt WHERE post_id=?")->execute([$postId]);
    $parts=preg_split('/[,;\n]+/u',$tagLine) ?: [];
    $clean=[];
    foreach($parts as $name){ $name=trim($name); if($name!=='' && !in_array(mb_strtolower($name,'UTF-8'),$clean,true)) $clean[]=mb_strtolower($name,'UTF-8'); }
    foreach($clean as $lower){
        $display=mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8'); $slug=omurga_unique_tag_slug(slugify($display));
        $ins=db()->prepare("INSERT INTO $tagsT (name,slug) VALUES (?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
        $ins->execute([$display,$slug]);
        $st=db()->prepare("SELECT id FROM $tagsT WHERE slug=? LIMIT 1"); $st->execute([$slug]); $tag=$st->fetch();
        if($tag) db()->prepare("INSERT IGNORE INTO $pt (post_id,tag_id) VALUES (?,?)")->execute([$postId,(int)$tag['id']]);
    }
}

function omurga_ensure_post_categories_table(): void {
    $pc=table_name('post_categories');
    db()->exec("CREATE TABLE IF NOT EXISTS $pc (post_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NOT NULL, PRIMARY KEY(post_id, category_id), INDEX(category_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function omurga_post_category_ids(int $postId, ?int $fallback=null): array {
    try{
        omurga_ensure_post_categories_table();
        $pc=table_name('post_categories');
        $st=db()->prepare("SELECT category_id FROM $pc WHERE post_id=? ORDER BY category_id");
        $st->execute([$postId]);
        $ids=array_map('intval', array_column($st->fetchAll(), 'category_id'));
        if(!$ids && $fallback) $ids=[$fallback];
        return array_values(array_unique(array_filter($ids)));
    }catch(Throwable $e){ return $fallback ? [$fallback] : []; }
}
function omurga_sync_post_categories(int $postId, array $ids): ?int {
    omurga_ensure_post_categories_table();
    $clean=[];
    foreach($ids as $id){ $id=(int)$id; if($id>0 && !in_array($id,$clean,true)) $clean[]=$id; }
    $pc=table_name('post_categories');
    db()->prepare("DELETE FROM $pc WHERE post_id=?")->execute([$postId]);
    foreach($clean as $id) db()->prepare("INSERT IGNORE INTO $pc (post_id,category_id) VALUES (?,?)")->execute([$postId,$id]);
    return $clean[0] ?? null;
}
function omurga_theme_sidebar_regions(?string $theme=null): array {
    $theme=$theme ?: omurga_active_theme();
    $regions=[];
    foreach(omurga_theme_regions($theme) as $key=>$label){
        if(stripos($key,'sidebar')!==false || stripos($key,'side')!==false || stripos($label,'sidebar')!==false || stripos($label,'yan')!==false) $regions[$key]=$label;
    }
    return $regions ?: ['sidebar'=>'Sidebar'];
}
function omurga_post_sidebar_enabled($post): bool {
    $id=is_array($post)?(int)($post['id']??0):(int)$post;
    if($id<=0) return true;
    return omurga_get_post_meta($id, '_omurga_sidebar_enabled', '1') !== '0';
}
function omurga_post_sidebar_region($post, string $default='sidebar'): string {
    $id=is_array($post)?(int)($post['id']??0):(int)$post;
    if($id<=0) return $default;
    $region=(string)omurga_get_post_meta($id, '_omurga_sidebar_region', $default);
    $allowed=omurga_theme_sidebar_regions();
    return isset($allowed[$region]) ? $region : $default;
}

function omurga_unique_tag_slug(string $slug): string {
    $t=table_name('tags');
    try{ $st=db()->prepare("SELECT slug FROM $t WHERE slug=? LIMIT 1"); $st->execute([$slug]); if(!$st->fetch()) return $slug; return $slug; }catch(Throwable $e){ return $slug; }
}
function omurga_datetime_local(?string $dt): string {
    if(!$dt || !strtotime($dt)) return '';
    return date('Y-m-d\TH:i', strtotime($dt));
}

function omurga_site_profiles(): array {
    return [
        'haber' => [
            'label' => om_t('profile.news', 'Haber'),
            'content_base' => 'haber',
            'primary_type' => 'news',
            'plural' => om_t('profile.news.plural', 'Haberler'),
            'quick_add' => om_t('profile.news.add', 'Haber Ekle'),
            'description' => om_t('profile.news.desc', 'DLE tarzı haber, kategori ve yayın akışı.')
        ],
        'kurumsal' => [
            'label' => om_t('profile.corporate', 'Kurumsal'),
            'content_base' => 'yazi',
            'primary_type' => 'post',
            'plural' => om_t('profile.corporate.plural', 'Yazılar'),
            'quick_add' => om_t('profile.corporate.add', 'Yazı Ekle'),
            'description' => om_t('profile.corporate.desc', 'Firma, hizmet, portföy ve teklif odaklı yapı.')
        ],
        'topluluk' => [
            'label' => om_t('profile.community', 'Topluluk'),
            'content_base' => 'duyuru',
            'primary_type' => 'post',
            'plural' => om_t('profile.community.plural', 'Duyurular'),
            'quick_add' => om_t('profile.community.add', 'Duyuru Ekle'),
            'description' => om_t('profile.community.desc', 'Dernek, platform, etkinlik ve proje odaklı yapı.')
        ],
        'bos' => [
            'label' => om_t('profile.blank', 'Boş'),
            'content_base' => 'yazi',
            'primary_type' => 'post',
            'plural' => om_t('profile.blank.plural', 'Yazılar'),
            'quick_add' => om_t('profile.blank.add', 'Yazı Ekle'),
            'description' => om_t('profile.blank.desc', 'Demo içerik, hazır menü ve hazır kategori eklemeden sade kurulum.')
        ],
    ];
}
function site_type(): string {
    $value = setting('site_type','haber') ?: 'haber';
    if($value === 'dernek') $value = 'topluluk';
    return array_key_exists($value, omurga_site_profiles()) ? $value : 'haber';
}
function site_profile(?string $key=null) {
    $profile = omurga_site_profiles()[site_type()] ?? omurga_site_profiles()['haber'];
    return $key === null ? $profile : ($profile[$key] ?? null);
}
function content_url_base(): string { return (string)(site_profile('content_base') ?: 'yazi'); }
function type_labels(): array {
    $st=site_type();
    $base=[
        'post'=>om_t('type.post','İçerik'),
        'page'=>om_t('type.page','Sayfa')
    ];
    if($st==='haber') return $base + ['news'=>om_t('type.news','Haber')];
    // Kurumsal ve Topluluk profillerinde çekirdek ayrı post tipi üretmez.
    // Hizmet, proje, etkinlik ve benzeri ayrımlar kategori veya sabit sayfa ile gelir.
    // Gelişmiş özel post tipi gerekiyorsa tema/eklenti tarafı ekler.
    return $base;
}
function type_label(string $type): string { $m=type_labels(); return $m[$type] ?? ucfirst($type); }
function content_category_label(): string {
    $key = 'profile.'.site_type().'.categories';
    return om_t($key, om_t('categories','Kategoriler'));
}
function content_tag_label(): string {
    $key = 'profile.'.site_type().'.tags';
    return om_t($key, om_t('tags','Etiketler'));
}
function primary_content_type(): string { return (string)(site_profile('primary_type') ?: 'post'); }
function content_label_plural(): string { return (string)(site_profile('plural') ?: 'İçerikler'); }
function content_quick_add_label(): string { return (string)(site_profile('quick_add') ?: 'İçerik Ekle'); }
function status_label(string $s): string { $labels=omurga_status_labels(); return $labels[$s] ?? $s; }



/* Omurga Tema Sistemi v1.4
   Kolay tema mantığı: theme.json + home/single/category/page dosyaları yeterlidir. */
function omurga_active_theme(): string {
    if(!empty($_GET['theme_preview']) && is_admin_logged_in()){
        $preview=preg_replace('/[^a-z0-9_-]/','',strtolower((string)$_GET['theme_preview']));
        $info=$preview ? omurga_theme_info($preview) : null;
        if($info && !empty($info['valid']) && empty($info['legacy_tpl'])) return $preview;
    }
    $fallback='omurga-kolay';
    foreach(['omurga-kolay','omurga-sabit'] as $safe){ $safeInfo=omurga_theme_info($safe); if($safeInfo && !empty($safeInfo['valid'])){ $fallback=$safe; break; } }
    $slug = setting('active_theme', $fallback);
    $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$slug));
    $info=$slug ? omurga_theme_info($slug) : null;
    if(!$info || empty($info['valid']) || !empty($info['legacy_tpl'])){
        if($slug && $slug!==$fallback) omurga_theme_log('theme.recovery','Aktif tema yüklenemedi. Güvenli temaya geçildi.', ['broken_theme'=>$slug,'fallback'=>$fallback]);
        return $fallback;
    }
    return $slug ?: $fallback;
}
function omurga_themes_dir(): string { return OMURGA_ROOT . '/themes'; }
function omurga_theme_dir(?string $slug=null): string { return omurga_themes_dir() . '/' . ($slug ?: omurga_active_theme()); }
function omurga_theme_url(?string $path='', ?string $slug=null): string {
    $base = 'themes/' . ($slug ?: omurga_active_theme());
    $path = ltrim((string)$path, '/');
    return omurga_url($path ? $base.'/'.$path : $base);
}
function omurga_system_theme_slugs(): array { return ['omurga-kolay','omurga-sabit']; }
function omurga_theme_log(string $action, string $message, array $details=[]): void {
    try{ log_activity($action, $message, null, 'themes', 'theme', null, $details); }catch(Throwable $e){}
    try{
        $dir=OMURGA_ROOT.'/storage/logs'; if(!is_dir($dir)) mkdir($dir,0775,true);
        file_put_contents($dir.'/themes.log','['.date('c').'] '.$action.' - '.$message.($details?' '.json_encode($details,JSON_UNESCAPED_UNICODE):'')."\n",FILE_APPEND);
    }catch(Throwable $e){}
}
function omurga_theme_is_system(array|string $theme): bool {
    if(is_array($theme)){
        $slug=(string)($theme['slug'] ?? '');
        if(!empty($theme['system_theme'])) return true;
    } else { $slug=(string)$theme; $theme=omurga_theme_info($slug) ?: []; if(!empty($theme['system_theme'])) return true; }
    return in_array($slug, omurga_system_theme_slugs(), true);
}
function omurga_theme_dir_stats(string $slug): array {
    $dir=omurga_theme_dir($slug); $files=0; $bytes=0;
    if(is_dir($dir)){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach($it as $f){ if($f->isFile()){ $files++; $bytes += (int)$f->getSize(); } }
    }
    return ['files'=>$files,'bytes'=>$bytes,'size'=>omurga_human_bytes($bytes)];
}
function omurga_human_bytes(int $bytes): string {
    $units=['B','KB','MB','GB']; $i=0; $v=max(0,$bytes);
    while($v>=1024 && $i<count($units)-1){ $v/=1024; $i++; }
    return ($i===0 ? (string)$v : number_format($v,2,',','.')).' '.$units[$i];
}
function omurga_theme_can_delete(string $slug, ?string &$reason=null): bool {
    $slug=preg_replace('/[^a-z0-9_-]/','',strtolower($slug));
    $theme=omurga_theme_info($slug);
    if(!$theme){ $reason='Tema bulunamadı.'; return false; }
    if($slug===omurga_active_theme()){ $reason='Aktif tema silinemez. Önce başka bir tema etkinleştirin.'; return false; }
    if(omurga_theme_is_system($theme)){ $reason='Sistem temaları silinemez.'; return false; }
    $themes=omurga_list_themes();
    if(count($themes)<=2){ $reason='Sistemde en az iki tema bulunmalıdır. Güvenlik nedeniyle tema silinemez.'; return false; }
    $reason=null; return true;
}
function omurga_backup_theme(string $slug): string {
    if(!class_exists('ZipArchive')) throw new RuntimeException('Tema yedeği için ZipArchive aktif olmalı.');
    $slug=preg_replace('/[^a-z0-9_-]/','',strtolower($slug));
    $dir=omurga_theme_dir($slug);
    if(!is_dir($dir)) throw new RuntimeException('Tema klasörü bulunamadı.');
    $backupDir=OMURGA_ROOT.'/storage/backups/themes'; if(!is_dir($backupDir)) mkdir($backupDir,0775,true);
    $zipPath=$backupDir.'/'.$slug.'-'.date('Ymd-His').'.zip';
    $zip=new ZipArchive();
    if($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Tema yedeği oluşturulamadı.');
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){ if($file->isFile()){ $rel=$slug.'/'.str_replace('\\','/',substr($file->getPathname(), strlen($dir)+1)); $zip->addFile($file->getPathname(), $rel); } }
    $zip->close();
    omurga_theme_log('theme.backup','Tema yedeği oluşturuldu: '.$slug, ['backup'=>$zipPath]);
    return $zipPath;
}
function omurga_delete_theme(string $slug, bool $backup=true): array {
    $reason=null;
    if(!omurga_theme_can_delete($slug,$reason)) throw new RuntimeException($reason ?: 'Tema silinemedi.');
    $slug=preg_replace('/[^a-z0-9_-]/','',strtolower($slug));
    $backupPath=null;
    if($backup) $backupPath=omurga_backup_theme($slug);
    omurga_rrmdir(omurga_theme_dir($slug));
    omurga_theme_log('theme.delete','Tema silindi: '.$slug, ['backup'=>$backupPath]);
    return ['slug'=>$slug,'backup'=>$backupPath];
}
function omurga_theme_contains_dangerous_code(string $dir): ?string {
    if(!is_dir($dir)) return null;
    $bad=['base64_decode','shell_exec','passthru','proc_open','popen','system(','exec(','assert(','eval('];
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if(!$file->isFile()) continue;
        $ext=strtolower($file->getExtension());
        if(!in_array($ext,['php','phtml','phar','omg','js'],true)) continue;
        $content=(string)@file_get_contents($file->getPathname());
        foreach($bad as $needle){ if(stripos($content,$needle)!==false) return str_replace('\\','/',substr($file->getPathname(), strlen($dir)+1)).' içinde riskli ifade: '.$needle; }
    }
    return null;
}
function omurga_theme_file(string $file, ?string $fallback=null): string {
    $file = ltrim($file, '/');
    $themeFile = omurga_theme_dir() . '/' . $file;
    if (file_exists($themeFile)) return $themeFile;
    $fallbackFile = OMURGA_ROOT . '/themes/omurga-kolay/' . ($fallback ? ltrim($fallback,'/') : $file);
    if (file_exists($fallbackFile)) return $fallbackFile;
    throw new RuntimeException('Tema dosyası bulunamadı: '.$file);
}
function omurga_theme_required_files(string $engine='omg'): array {
    $engine=strtolower($engine);
    if($engine==='omg') return ['home.omg','single.omg','page.omg','category.omg','header.omg','footer.omg','components/post-card.omg'];
    if($engine==='php') return ['home.php','header.php','footer.php'];
    return [];
}
function omurga_validate_theme_standard(string $dir, array $data): array {
    $engine=omurga_infer_theme_engine_from_dir($dir, $data);
    $missing=[];
    $warnings=[];
    if(!is_file($dir.'/theme.json')) $missing[]='theme.json';
        foreach(omurga_theme_required_files($engine) as $file){
        if(!is_file($dir.'/'.$file)) $missing[]=$file;
    }
    if($engine==='php') $warnings[]='Bu tema Gelişmiş PHP Tema motoruyla çalışır.';
    if($engine==='hybrid') $warnings[]='Bu tema hibrit tema motoruyla çalışır.';
    if(empty($data['slug'])) $warnings[]='theme.json slug alanı eksik.';
    if(empty($data['name'])) $warnings[]='theme.json name alanı eksik.';
    return ['missing'=>$missing,'warnings'=>$warnings,'valid'=>empty($missing)];
}
function omurga_theme_info(string $slug): ?array {
    $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
    $dir = omurga_theme_dir($slug);
    $json = $dir . '/theme.json';
    if (!is_dir($dir) || !file_exists($json)) return null;
    $data = json_decode(file_get_contents($json), true);
    if (!is_array($data)) return null;
    $data['slug'] = $data['slug'] ?? $slug;
    $data['system_theme'] = !empty($data['system_theme']) || in_array($slug, omurga_system_theme_slugs(), true);
    $data['name'] = $data['name'] ?? $slug;
    $data['version'] = $data['version'] ?? '1.0.0';
    $data['description'] = $data['description'] ?? '';
    $data['screenshot'] = file_exists($dir.'/screenshot.png') ? omurga_theme_url('screenshot.png', $slug) : '';
    $engine=omurga_infer_theme_engine_from_dir($dir, $data);
    $data['template_engine']=$engine;
    $data['engine']=$engine;
    $data['legacy_tpl']=false;
    $standard=omurga_validate_theme_standard($dir,$data);
    $data['missing'] = $standard['missing'];
    $data['warnings'] = $standard['warnings'];
    $data['valid'] = $standard['valid'];
    return $data;
}
function omurga_list_themes(): array {
    $dirs = glob(omurga_themes_dir().'/*', GLOB_ONLYDIR) ?: [];
    $themes=[];
    foreach($dirs as $dir){ $info=omurga_theme_info(basename($dir)); if($info && empty($info['legacy_tpl'])) $themes[]=$info; }
    usort($themes, fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));
    return $themes;
}
function omurga_validate_theme_zip(string $zipPath): array {
    if(!class_exists('ZipArchive')) throw new RuntimeException('Tema yüklemek için ZipArchive aktif olmalı.');
    $tmp=OMURGA_ROOT.'/storage/theme-temp/theme-'.date('YmdHis').'-'.bin2hex(random_bytes(3));
    if(!is_dir(dirname($tmp))) mkdir(dirname($tmp),0775,true);
    $zip=new ZipArchive();
    if($zip->open($zipPath)!==true) throw new RuntimeException('Tema zip dosyası açılamadı.');
    omurga_safe_extract_zip($zip, $tmp); $zip->close();
    $root=$tmp;
    if(!file_exists($root.'/theme.json')){
        $children=array_values(array_filter(glob($tmp.'/*') ?: [], 'is_dir'));
        if(count($children)===1 && file_exists($children[0].'/theme.json')) $root=$children[0];
    }
    if(!file_exists($root.'/theme.json')) { omurga_rrmdir($tmp); throw new RuntimeException('Tema geçersiz: theme.json bulunamadı.'); }
    $data=json_decode(file_get_contents($root.'/theme.json'), true);
    if(!is_array($data)) { omurga_rrmdir($tmp); throw new RuntimeException('theme.json okunamadı.'); }
    $slug=slugify($data['slug'] ?? $data['id'] ?? $data['name'] ?? basename($root));
    $engine=omurga_infer_theme_engine_from_dir($root, $data);
    if($engine==='tpl') { omurga_rrmdir($tmp); throw new RuntimeException('Eski şablon formatı resmi tema standardı değildir. Lütfen .omg tema yükleyin.'); }
    if(is_dir(omurga_themes_dir().'/'.$slug)){ omurga_rrmdir($tmp); throw new RuntimeException('Bu tema zaten kurulu. Aynı tema ID/slug ile tekrar yüklenemez.'); }
    $risk=omurga_theme_contains_dangerous_code($root);
    if($risk){ omurga_rrmdir($tmp); throw new RuntimeException('Tema güvenlik kontrolünden geçemedi: '.$risk); }
    $required=omurga_theme_required_files($engine);
    foreach($required as $req){ if(!file_exists($root.'/'.$req)){ omurga_rrmdir($tmp); throw new RuntimeException('Tema eksik: '.$req.' dosyası yok.'); } }
    return ['tmp'=>$tmp,'root'=>$root,'slug'=>$slug,'info'=>$data];
}
function omurga_install_theme_zip(string $zipPath): array {
    $pack=omurga_validate_theme_zip($zipPath);
    $target=omurga_themes_dir().'/'.$pack['slug'];
    if(is_dir($target)) throw new RuntimeException('Bu tema zaten kurulu. Önce eski temayı silin veya slug değiştirin.');
    if(!is_dir(dirname($target))) mkdir(dirname($target),0775,true);
    mkdir($target,0775,true);
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pack['root'], FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if(!$file->isFile()) continue;
        $rel=str_replace('\\','/',substr($file->getPathname(), strlen($pack['root'])+1));
        $dest=$target.'/'.$rel; if(!is_dir(dirname($dest))) mkdir(dirname($dest),0775,true);
        copy($file->getPathname(), $dest);
    }
    omurga_rrmdir($pack['tmp']);
    return omurga_theme_info($pack['slug']) ?: ['slug'=>$pack['slug'],'name'=>$pack['slug']];
}



/* Omurga Sayfa/Yazı Şablon Sistemi v1.6
   - Şablonlar aktif temadan gelir.
   - Sabit sayfa ve yazı/haber detay görünümü temaya bağlıdır.
   - İçerik bazlı seçilen şablon design_template alanında saklanır. */
function omurga_theme_templates(?string $slug=null): array {
    $slug=$slug ?: omurga_active_theme();
    $meta=omurga_theme_meta($slug);
    $templates=$meta['templates'] ?? [];
    if(!is_array($templates)) $templates=[];
    $defaults=[
        'page'=>[
            'default'=>['name'=>'Varsayılan Sayfa','file'=>'page.php','description'=>'Temanın varsayılan sabit sayfa görünümü.'],
            'fullwidth'=>['name'=>'Tam Genişlik Sayfa','file'=>'templates/page-fullwidth.php','description'=>'Sidebar olmadan geniş sayfa görünümü.'],
            'sidebar'=>['name'=>'Sidebarlı Sayfa','file'=>'templates/page-sidebar.php','description'=>'Sağ alanlı sabit sayfa görünümü.'],
            'contact'=>['name'=>'İletişim Sayfası','file'=>'templates/page-contact.php','description'=>'İletişim ve başvuru alanı için sayfa görünümü.'],
        ],
        'single'=>[
            'default'=>['name'=>'Varsayılan Yazı','file'=>'single.php','description'=>'Temanın varsayılan yazı detay görünümü.'],
            'wide'=>['name'=>'Geniş Görselli Yazı','file'=>'templates/single-wide.php','description'=>'Geniş görsel ve ferah okuma alanı.'],
            'sidebar'=>['name'=>'Sidebarlı Yazı','file'=>'templates/single-sidebar.php','description'=>'Sağ alanda benzer içerik/reklam bulunan görünüm.'],
            'video'=>['name'=>'Video / Galeri Yazısı','file'=>'templates/single-video.php','description'=>'Video ve görsel odaklı içerikler için.'],
        ],
    ];
    foreach($defaults as $group=>$items){
        if(empty($templates[$group]) || !is_array($templates[$group])) $templates[$group]=[];
        $templates[$group] = array_replace_recursive($items, $templates[$group]);
    }
    foreach($templates as $group=>$items){
        if(!is_array($items)) { unset($templates[$group]); continue; }
        foreach($items as $key=>$item){
            if(!is_array($item)) $item=['name'=>(string)$item,'file'=>''];
            $file=$item['file'] ?? '';
            $item['key']=$key;
            $item['name']=$item['name'] ?? ucfirst((string)$key);
            $item['description']=$item['description'] ?? '';
            $item['file']=$file;
            $item['exists']=$file ? file_exists(omurga_theme_dir($slug).'/'.ltrim($file,'/')) : false;
            $templates[$group][$key]=$item;
        }
    }
    return $templates;
}
function omurga_template_group_for_type(string $type): string { return $type==='page' ? 'page' : 'single'; }
function omurga_templates_for_type(string $type, ?string $theme=null): array {
    $all=omurga_theme_templates($theme);
    return $all[omurga_template_group_for_type($type)] ?? [];
}
function omurga_template_label(string $type, string $key, ?string $theme=null): string {
    $items=omurga_templates_for_type($type,$theme);
    return $items[$key]['name'] ?? 'Varsayılan Şablon';
}
function omurga_post_template_file(array $post): string {
    $type=(string)($post['type'] ?? 'post');
    $group=omurga_template_group_for_type($type);
    $key=preg_replace('/[^a-z0-9_\-]/','', strtolower((string)($post['design_template'] ?? 'default')));
    if($key==='') $key='default';
    $templates=omurga_theme_templates();
    $item=$templates[$group][$key] ?? null;
    if(!$item || empty($item['file']) || empty($item['exists'])) $item=$templates[$group]['default'] ?? null;
    if($item && !empty($item['file'])) return omurga_theme_file($item['file'], $group==='page' ? 'page.php' : 'single.php');
    return omurga_theme_file($group==='page' ? 'page.php' : 'single.php');
}


function omurga_normalize_gallery_input($input): string {
    $items=[];
    if(is_array($input)) $parts=$input; else $parts=preg_split('/[\r\n,]+/u', (string)$input) ?: [];
    foreach($parts as $p){ $p=trim((string)$p); if($p!=='' && !in_array($p,$items,true)) $items[]=$p; }
    return json_encode($items, JSON_UNESCAPED_UNICODE);
}
function omurga_gallery_items($value): array {
    if(is_array($value)) return $value;
    $value=(string)$value;
    if($value==='') return [];
    $decoded=json_decode($value,true);
    if(is_array($decoded)) return array_values(array_filter(array_map('trim',$decoded)));
    return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/u',$value) ?: [])));
}
function omurga_gallery_to_text($value): string { return implode("\n", omurga_gallery_items($value)); }
function omurga_video_embed_url(string $url): string {
    $url=trim($url); if($url==='') return '';
    if(preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)~',$url,$m)) return 'https://www.youtube.com/embed/'.$m[1];
    if(preg_match('~vimeo\.com/(\d+)~',$url,$m)) return 'https://player.vimeo.com/video/'.$m[1];
    return $url;
}
function omurga_normalize_editor_type(?string $type): string {
    return $type === 'blocks' ? 'blocks' : 'classic';
}
function omurga_decode_content_blocks($json): array {
    $rows=is_array($json) ? $json : json_decode((string)$json, true);
    if(!is_array($rows)) return [];
    $out=[];
    foreach($rows as $row){
        if(!is_array($row)) continue;
        $type=omurga_normalize_block_editor_type((string)($row['type'] ?? ''));
        if($type==='') continue;
        $value=trim((string)($row['value'] ?? ''));
        $caption=trim((string)($row['caption'] ?? ''));
        if($value==='' && $caption==='') continue;
        $out[]=['type'=>$type,'value'=>$value,'caption'=>$caption];
    }
    return $out;
}
function omurga_normalize_block_editor_type(string $type): string {
    $type=preg_replace('/[^a-z0-9_\-]/','', strtolower($type));
    return in_array($type, ['text','image','quote','video'], true) ? $type : '';
}
function omurga_default_content_blocks(string $content=''): array {
    $text=trim(strip_tags($content));
    return [['type'=>'text','value'=>$text,'caption'=>'']];
}
function omurga_content_blocks_to_json(array $blocks): string {
    return json_encode(omurga_decode_content_blocks($blocks), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
function omurga_content_blocks_to_html(array $blocks): string {
    $html='';
    foreach(omurga_decode_content_blocks($blocks) as $block){
        $type=$block['type']; $value=$block['value']; $caption=$block['caption'];
        if($type==='text'){
            $paras=array_filter(array_map('trim', preg_split("/\R{2,}/u", $value) ?: []));
            foreach($paras as $p){ $html.='<p>'.nl2br(e($p)).'</p>'; }
        } elseif($type==='image'){
            $src=trim($value);
            if($src==='') continue;
            $img='<img src="'.e(image_url($src)).'" alt="'.e($caption).'">';
            $html.='<figure class="om-block-image">'.$img.($caption!==''?'<figcaption>'.e($caption).'</figcaption>':'').'</figure>';
        } elseif($type==='quote'){
            $html.='<blockquote class="om-block-quote">'.nl2br(e($value)).($caption!==''?'<cite>'.e($caption).'</cite>':'').'</blockquote>';
        } elseif($type==='video'){
            $embed=omurga_video_embed_url($value);
            if($embed==='') continue;
            if(preg_match('~\.mp4($|\?)~i',$embed)) $html.='<video class="om-block-video" controls src="'.e($embed).'"></video>';
            else $html.='<div class="om-block-video"><iframe src="'.e($embed).'" loading="lazy" allowfullscreen></iframe></div>';
        }
    }
    return $html;
}
function omurga_render_post_content(array $post): string {
    omurga_do_action('omurga_before_post_render', $post);
    if(omurga_normalize_editor_type($post['editor_type'] ?? 'classic') === 'blocks'){
        $blocks=omurga_decode_content_blocks($post['content_blocks'] ?? '');
        if($blocks) return (string)omurga_apply_filters('omurga_after_post_render', omurga_content_blocks_to_html($blocks), $post);
    }
    return (string)omurga_apply_filters('omurga_after_post_render', omurga_render_shortcodes((string)($post['content'] ?? '')), $post);
}

function current_path(): string { $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH); $base=parse_url(omurga_url(),PHP_URL_PATH)?:''; if($base&&str_starts_with($uri,$base)) $uri=substr($uri,strlen($base)); return trim($uri,'/'); }
if(!omurga_is_installed() && !str_contains($_SERVER['SCRIPT_NAME']??'', '/install/')){ header('Location: install/'); exit; }


function omurga_migrate_07(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $p=table_name('activity_logs');
        db()->exec("CREATE TABLE IF NOT EXISTS $p (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(80) NOT NULL, message TEXT NULL, ip VARCHAR(64) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(action)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $b=table_name('backups');
        db()->exec("CREATE TABLE IF NOT EXISTS $b (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, backup_type VARCHAR(40) NOT NULL, file_path VARCHAR(255) NOT NULL, file_size INT UNSIGNED NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $users=table_name('users');
        $cols=db()->query("SHOW COLUMNS FROM $users")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('last_login_at',$cols,true)) db()->exec("ALTER TABLE $users ADD last_login_at DATETIME NULL");
        if(!in_array('last_login_ip',$cols,true)) db()->exec("ALTER TABLE $users ADD last_login_ip VARCHAR(64) NULL");
    }catch(Throwable $e){ }
}

function omurga_migrate(): void {
    omurga_migrate_07();
    omurga_migrate_08();
    omurga_migrate_12();
    omurga_migrate_14();
    omurga_migrate_15();
    omurga_migrate_16();
    omurga_migrate_164();
    omurga_migrate_1667();
    omurga_migrate_17();
    omurga_migrate_19();
    omurga_migrate_20();
    omurga_migrate_22();
    omurga_migrate_26();
    omurga_migrate_27();
    omurga_migrate_346();
    omurga_migrate_361();
    omurga_migrate_366();
    omurga_migrate_369();
    omurga_migrate_400_data_model();
    omurga_migrate_media_v2();
}

function omurga_migrate_369(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $posts=table_name('posts');
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('editor_type',$cols,true)) db()->exec("ALTER TABLE $posts ADD editor_type VARCHAR(20) NOT NULL DEFAULT 'classic' AFTER content");
        if(!in_array('content_blocks',$cols,true)) db()->exec("ALTER TABLE $posts ADD content_blocks MEDIUMTEXT NULL AFTER editor_type");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_400_data_model(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        if(setting('schema_version', null)===null) update_setting('schema_version', OMURGA_SCHEMA_VERSION);
        $theme=omurga_active_theme();
        $global=setting_json(omurga_layout_key($theme), []);
        if(!$global){
            $legacy=setting_json(omurga_legacy_layout_key($theme), []);
            omurga_update_layout($legacy ?: omurga_default_layout($theme), $theme);
        }
        update_setting('schema_version', OMURGA_SCHEMA_VERSION);
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_media_v2(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $t=table_name('media');
        if(!omurga_table_exists($t)) return;
        $cols=db()->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_COLUMN);
        $add=function(string $name, string $sql) use($t,&$cols){
            if(!in_array($name,$cols,true)){ db()->exec("ALTER TABLE $t ADD $sql"); $cols[]=$name; }
        };
        $add('filename', "filename VARCHAR(180) NULL AFTER id");
        $add('original_filename', "original_filename VARCHAR(220) NULL AFTER filename");
        $add('mime_type', "mime_type VARCHAR(120) NULL AFTER original_filename");
        $add('extension', "extension VARCHAR(20) NULL AFTER mime_type");
        $add('size', "size INT UNSIGNED NULL AFTER extension");
        $add('path', "path VARCHAR(255) NULL AFTER size");
        $add('url', "url VARCHAR(500) NULL AFTER path");
        $add('title', "title VARCHAR(220) NULL AFTER alt_text");
        $add('caption', "caption TEXT NULL AFTER title");
        db()->exec("UPDATE $t SET filename=COALESCE(filename,file_name), original_filename=COALESCE(original_filename,file_name), mime_type=COALESCE(mime_type,mime), extension=COALESCE(extension,LOWER(SUBSTRING_INDEX(COALESCE(file_name,filename),'.',-1))), size=COALESCE(size,file_size), path=COALESCE(path,file_path), title=COALESCE(title,title_text), caption=COALESCE(caption,description)");
        $rows=db()->query("SELECT id,path,file_path,url FROM $t WHERE (url IS NULL OR url='') LIMIT 1000")->fetchAll();
        foreach($rows as $row){
            $path=(string)($row['path'] ?: $row['file_path'] ?: '');
            if($path!=='') db()->prepare("UPDATE $t SET url=? WHERE id=?")->execute([image_url($path),(int)$row['id']]);
        }
        foreach(['media_auto_webp'=>'1','media_keep_original'=>'1','media_generate_sizes'=>'1','media_avif_enabled'=>'0'] as $key=>$value){
            if(setting($key,null)===null) update_setting($key,$value);
        }
        update_setting('schema_version', OMURGA_SCHEMA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_366(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $posts=table_name('posts');
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('comments_enabled',$cols,true)) db()->exec("ALTER TABLE $posts ADD comments_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER seo_noindex");
        $comments=table_name('comments');
        db()->exec("CREATE TABLE IF NOT EXISTS $comments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED NULL,
            author_name VARCHAR(120) NOT NULL,
            author_email VARCHAR(190) NOT NULL,
            author_ip VARCHAR(64) NULL,
            content TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            user_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX(post_id), INDEX(parent_id), INDEX(status), INDEX(author_ip), INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_361(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        if(setting('admin_language', null)===null) update_setting('admin_language','tr');
        if(setting('site_language', null)===null) update_setting('site_language', setting('admin_language','tr') ?: 'tr');
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_346(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        omurga_ensure_post_categories_table();
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_26(): void {
    try{
        $n=table_name('notifications');
        db()->exec("CREATE TABLE IF NOT EXISTS $n (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, type VARCHAR(40) NOT NULL DEFAULT 'info', title VARCHAR(190) NOT NULL, message TEXT NULL, link VARCHAR(255) NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(is_read), INDEX(type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $l=table_name('activity_logs');
        $cols=db()->query("SHOW COLUMNS FROM $l")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('module',$cols,true)) db()->exec("ALTER TABLE $l ADD module VARCHAR(80) NULL AFTER action");
        if(!in_array('entity_type',$cols,true)) db()->exec("ALTER TABLE $l ADD entity_type VARCHAR(80) NULL AFTER module");
        if(!in_array('entity_id',$cols,true)) db()->exec("ALTER TABLE $l ADD entity_id INT UNSIGNED NULL AFTER entity_type");
        if(!in_array('details',$cols,true)) db()->exec("ALTER TABLE $l ADD details LONGTEXT NULL AFTER message");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ }
}

function omurga_migrate_22(): void {
    try{
        $users=table_name('users');
        // Eski Admin rol etiketini yönetici mantığında kullanmaya devam eder; yeni roller formdan seçilir.
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ }
}


function omurga_migrate_20(): void {
    try{
        $posts=table_name('posts');
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('canonical_url',$cols,true)) db()->exec("ALTER TABLE $posts ADD canonical_url VARCHAR(500) NULL AFTER social_description");
        if(!in_array('seo_noindex',$cols,true)) db()->exec("ALTER TABLE $posts ADD seo_noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER canonical_url");
        if(setting('seo_title_format','')==='') update_setting('seo_title_format','{title} - {site}');
        if(setting('seo_enable_og','')==='') update_setting('seo_enable_og','1');
        if(setting('seo_enable_twitter','')==='') update_setting('seo_enable_twitter','1');
        if(setting('seo_enable_schema','')==='') update_setting('seo_enable_schema','1');
        if(setting('seo_allow_index','')==='') update_setting('seo_allow_index','1');
        if(setting('seo_sitemap_enabled','')==='') update_setting('seo_sitemap_enabled','1');
        if(setting('seo_news_sitemap_enabled','')==='') update_setting('seo_news_sitemap_enabled','1');
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_19(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        foreach(omurga_menu_locations() as $loc=>$label){ if(setting(menu_setting_key($loc), null)===null) update_setting_json(menu_setting_key($loc), default_menu_items($loc)); }
        if(setting('ad_slots', null)===null) update_setting_json('ad_slots', omurga_default_ad_slots());
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_16(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $posts=table_name('posts');
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('design_template',$cols,true)) db()->exec("ALTER TABLE $posts ADD design_template VARCHAR(80) NULL AFTER social_description");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_layout_has_block(array $layout, string $slug): bool {
    $slug=omurga_normalize_block_slug($slug);
    foreach($layout as $blocks){
        foreach((array)$blocks as $b){
            if(omurga_normalize_block_slug((string)($b['slug'] ?? ''))===$slug) return true;
        }
    }
    return false;
}
function omurga_sidebar_setting_key(?string $theme=null, string $scope='home'): string {
    $scope = preg_replace('/[^a-z0-9_\-]/','', $scope ?: 'home');
    return 'sidebar_'.$scope.'_enabled_'.($theme ?: omurga_active_theme());
}
function omurga_sidebar_enabled(?string $theme=null, string $scope='home'): bool { return setting(omurga_sidebar_setting_key($theme, $scope), '1') !== '0'; }
function omurga_migrate_164(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $theme=omurga_active_theme();
        
        // Eski tek sidebar ayarı varsa yeni Anasayfa/Genel ayarlarına taşı.
        $legacy = setting('sidebar_enabled_'.$theme, null);
        if(setting(omurga_sidebar_setting_key($theme, 'home'), null)===null) update_setting(omurga_sidebar_setting_key($theme, 'home'), $legacy !== null ? $legacy : '1');
        if(setting(omurga_sidebar_setting_key($theme, 'global'), null)===null) update_setting(omurga_sidebar_setting_key($theme, 'global'), $legacy !== null ? $legacy : '1');
        $key=omurga_layout_key($theme);
        $layout=setting_json($key, []);
        if($layout && site_type()==='haber' && !omurga_layout_has_block($layout,'auth-box')){
            $layout['sidebar']=$layout['sidebar'] ?? [];
            $layout['sidebar'][]=[
                'id'=>'b'.time().'64',
                'slug'=>'auth-box',
                'source'=>'core',
                'enabled'=>1,
                'sort'=>5,
                'width'=>'100',
                'settings'=>['title'=>'Üye Paneli','login_url'=>'admin/login.php','register_url'=>'#kayit','show_register'=>'1']
            ];
            omurga_update_layout($layout, $theme);
        }
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_migrate_1667(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $pm=table_name('post_meta');
        db()->exec("CREATE TABLE IF NOT EXISTS $pm (post_id INT UNSIGNED NOT NULL, meta_key VARCHAR(120) NOT NULL, meta_value MEDIUMTEXT NULL, PRIMARY KEY(post_id, meta_key), INDEX(meta_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $custom=OMURGA_ROOT.'/storage/blocks';
        if(!is_dir($custom)) mkdir($custom,0775,true);
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}


function omurga_migrate_17(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $posts=table_name('posts');
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('video_url',$cols,true)) db()->exec("ALTER TABLE $posts ADD video_url VARCHAR(500) NULL AFTER featured_image");
        if(!in_array('gallery_images',$cols,true)) db()->exec("ALTER TABLE $posts ADD gallery_images MEDIUMTEXT NULL AFTER video_url");
        $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
        $pm=table_name('post_meta');
        db()->exec("CREATE TABLE IF NOT EXISTS $pm (post_id INT UNSIGNED NOT NULL, meta_key VARCHAR(120) NOT NULL, meta_value MEDIUMTEXT NULL, PRIMARY KEY(post_id, meta_key), INDEX(meta_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");        if(in_array('is_featured',$cols,true)) db()->exec("INSERT INTO $pm (post_id,meta_key,meta_value) SELECT id,'legacy_is_featured',is_featured FROM $posts WHERE is_featured=1 ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)");        if(in_array('mobile_image',$cols,true)) db()->exec("INSERT INTO $pm (post_id,meta_key,meta_value) SELECT id,'legacy_mobile_image',mobile_image FROM $posts WHERE mobile_image IS NOT NULL AND mobile_image<>'' ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)");
        foreach(['mobile_image'] as $col){
            $cols=db()->query("SHOW COLUMNS FROM $posts")->fetchAll(PDO::FETCH_COLUMN);
            if(in_array($col,$cols,true)) { try{ db()->exec("ALTER TABLE $posts DROP COLUMN $col"); }catch(Throwable $e){ omurga_write_error($e); } }
        }
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_migrate_15(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        if(setting(omurga_layout_key(omurga_active_theme()), null)===null) omurga_update_layout(omurga_default_layout(omurga_active_theme()), omurga_active_theme());
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_migrate_14(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        if(setting('active_theme', null)===null) update_setting('active_theme','omurga-kolay');
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_migrate_12(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $tags=table_name('tags'); $pt=table_name('post_tags');
        db()->exec("CREATE TABLE IF NOT EXISTS $tags (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->exec("CREATE TABLE IF NOT EXISTS $pt (post_id INT UNSIGNED NOT NULL, tag_id INT UNSIGNED NOT NULL, PRIMARY KEY(post_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_migrate_08(): void {
    static $done=false; if($done) return; $done=true;
    if(!omurga_is_installed()) return;
    try{
        $u=table_name('update_logs');
        db()->exec("CREATE TABLE IF NOT EXISTS $u (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, from_version VARCHAR(30) NULL, to_version VARCHAR(30) NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', message TEXT NULL, package_name VARCHAR(190) NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
        if(setting('maintenance_mode', null)===null) update_setting('maintenance_mode','0');
        if(setting('maintenance_message', null)===null) update_setting('maintenance_message','Sitemiz kısa süreli bakımda. Lütfen daha sonra tekrar deneyin.');
        if(setting('update_log_max_count', null)===null) update_setting('update_log_max_count','30');
        if(setting('update_package_max_count', null)===null) update_setting('update_package_max_count','10');
    }catch(Throwable $e){ omurga_write_error($e); }
}


function omurga_migrate_27(): void {
    try{
        $r = table_name('post_revisions');
        db()->exec("CREATE TABLE IF NOT EXISTS $r (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL,
            revision_type VARCHAR(40) NOT NULL DEFAULT 'update',
            title VARCHAR(255) NULL,
            changed_fields TEXT NULL,
            snapshot LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(post_id), INDEX(user_id), INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if(setting('content_max_revisions', null)===null) update_setting('content_max_revisions','20');
        update_setting('omurga_version', OMURGA_VERSION);
        update_setting('db_version', OMURGA_VERSION);
    }catch(Throwable $e){ omurga_write_error($e); }
}

function omurga_revision_fields(): array {
    return ['title','slug','spot','content','editor_type','content_blocks','type','status','category_id','featured_image','video_url','gallery_images','social_image','sort_order','seo_title','meta_description','focus_keyword','social_title','social_description','canonical_url','seo_noindex','comments_enabled','design_template','published_at'];
}
function omurga_revision_snapshot(array $post): array {
    $snap=[];
    foreach(omurga_revision_fields() as $f){ $snap[$f]=$post[$f] ?? null; }
    try{ $snap['tags']=tag_names_for_post((int)($post['id'] ?? 0)); }catch(Throwable $e){ $snap['tags']=[]; }
    try{ $snap['block_meta']=omurga_get_post_meta_values((int)($post['id'] ?? 0)); }catch(Throwable $e){ $snap['block_meta']=[]; }
    return $snap;
}
function omurga_changed_fields(array $old, array $new): array {
    $fields=[];
    foreach(omurga_revision_fields() as $f){ if((string)($old[$f] ?? '') !== (string)($new[$f] ?? '')) $fields[]=$f; }
    return $fields;
}
function omurga_create_post_revision(int $postId, ?array $oldPost=null, array $newPost=[], string $type='update'): void {
    if($postId<=0) return;
    try{
        $posts=table_name('posts');
        if(!$oldPost){ $st=db()->prepare("SELECT * FROM $posts WHERE id=?"); $st->execute([$postId]); $oldPost=$st->fetch() ?: null; }
        if(!$oldPost) return;
        $changed=$newPost ? omurga_changed_fields($oldPost, $newPost) : [];
        if($type==='update' && $newPost && !$changed) return;
        $snap=omurga_revision_snapshot($oldPost);
        $r=table_name('post_revisions');
        db()->prepare("INSERT INTO $r (post_id,user_id,revision_type,title,changed_fields,snapshot) VALUES (?,?,?,?,?,?)")->execute([
            $postId,
            $_SESSION['omurga_user_id'] ?? null,
            $type,
            $oldPost['title'] ?? '',
            implode(',', $changed),
            json_encode($snap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        ]);
        omurga_prune_post_revisions($postId);
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_prune_post_revisions(int $postId): void {
    try{
        $max=max(1,min(200,(int)setting('content_max_revisions','20')));
        $r=table_name('post_revisions');
        $st=db()->prepare("SELECT id FROM $r WHERE post_id=? ORDER BY id DESC LIMIT 100000 OFFSET $max");
        $st->execute([$postId]);
        $ids=$st->fetchAll(PDO::FETCH_COLUMN);
        if($ids){ $in=implode(',', array_map('intval',$ids)); db()->exec("DELETE FROM $r WHERE id IN ($in)"); }
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_recent_revisions(int $postId, int $limit=10): array {
    try{ $r=table_name('post_revisions'); $st=db()->prepare("SELECT r.*,u.name user_name FROM $r r LEFT JOIN ".table_name('users')." u ON u.id=r.user_id WHERE r.post_id=? ORDER BY r.id DESC LIMIT ".(int)$limit); $st->execute([$postId]); return $st->fetchAll(); }catch(Throwable $e){ return []; }
}
function omurga_revision_label(string $field): string {
    $map=['title'=>'Başlık','slug'=>'Slug','spot'=>'Spot','content'=>'İçerik','type'=>'İçerik türü','status'=>'Durum','category_id'=>'Kategori','featured_image'=>'Öne çıkan görsel','video_url'=>'Video URL','gallery_images'=>'Galeri','social_image'=>'Sosyal görsel','sort_order'=>'Sıralama','seo_title'=>'SEO başlığı','meta_description'=>'Meta açıklama','focus_keyword'=>'Odak kelime','social_title'=>'Sosyal başlık','social_description'=>'Sosyal açıklama','canonical_url'=>'Canonical','seo_noindex'=>'Noindex','design_template'=>'Tasarım şablonu','published_at'=>'Yayın tarihi'];
    return $map[$field] ?? $field;
}
function omurga_restore_revision(int $revisionId): int {
    $r=table_name('post_revisions');
    $st=db()->prepare("SELECT * FROM $r WHERE id=?"); $st->execute([$revisionId]); $rev=$st->fetch();
    if(!$rev) throw new RuntimeException('Revizyon bulunamadı.');
    $postId=(int)$rev['post_id'];
    $posts=table_name('posts');
    $st=db()->prepare("SELECT * FROM $posts WHERE id=?"); $st->execute([$postId]); $current=$st->fetch();
    if(!$current) throw new RuntimeException('İçerik bulunamadı.');
    omurga_create_post_revision($postId, $current, [], 'before_restore');
    $snap=json_decode($rev['snapshot'] ?? '{}', true) ?: [];
    $fields=array_intersect(omurga_revision_fields(), array_keys($snap));
    if(!$fields) throw new RuntimeException('Revizyon verisi boş.');
    $sets=[]; $vals=[];
    foreach($fields as $f){ $sets[]="$f=?"; $vals[]=$snap[$f]; }
    $vals[]=$postId;
    db()->prepare("UPDATE $posts SET ".implode(',', $sets).", updated_at=NOW() WHERE id=?")->execute($vals);
    if(isset($snap['tags'])) sync_post_tags($postId, implode(',', (array)$snap['tags']));
    if(isset($snap['block_meta']) && is_array($snap['block_meta'])) omurga_save_post_meta_values($postId, $snap['block_meta']);
    log_activity('post.revision_restore','İçerik eski revizyona döndürüldü: #'.$postId);
    omurga_notify(null,'success','Revizyon geri yüklendi','Bir içerik eski revizyona döndürüldü.','admin/post-edit.php?id='.$postId);
    return $postId;
}

function omurga_write_error(Throwable $e): void {
    try{
        $dir=OMURGA_ROOT.'/storage/logs'; if(!is_dir($dir)) mkdir($dir,0775,true);
        $line='['.date('c').'] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n";
        file_put_contents($dir.'/error.log', $line, FILE_APPEND);
    }catch(Throwable $x){}
}
function render_error_page(int $code, string $title, string $message): void {
    http_response_code($code);
    $file=omurga_theme_file($code.'.php', $code.'.php');
    if(file_exists($file)){ include $file; } else { echo '<!doctype html><meta charset="utf-8"><title>'.e($title).'</title><div style="font-family:Arial;max-width:720px;margin:60px auto"><h1>'.e($title).'</h1><p>'.e($message).'</p><a href="'.e(omurga_url()).'">Ana sayfaya dön</a></div>'; }
    exit;
}

set_exception_handler(function(Throwable $e){
    omurga_write_error($e);
    if(!headers_sent()) render_error_page(500, 'Sistem Hatası', 'Beklenmeyen bir hata oluştu. Detaylar sistem kayıtlarına yazıldı.');
    echo 'Sistem hatası'; exit;
});
register_shutdown_function(function(){
    $err=error_get_last();
    if($err && in_array($err['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)){
        omurga_write_error(new ErrorException($err['message'],0,$err['type'],$err['file'],$err['line']));
    }
});

function omurga_system_status(): array {
    $cfg=omurga_config();
    $checks=[];
    $checks[]=['PHP Sürümü', PHP_VERSION, version_compare(PHP_VERSION,'8.0.0','>=')];
    $checks[]=['PDO MySQL', extension_loaded('pdo_mysql')?'Aktif':'Eksik', extension_loaded('pdo_mysql')];
    $checks[]=['GD / WebP', function_exists('imagewebp')?'Aktif':'Eksik', function_exists('imagewebp')];
    $checks[]=['ZipArchive', class_exists('ZipArchive')?'Aktif':'Eksik', class_exists('ZipArchive')];
    $checks[]=['uploads yazılabilir', is_writable(OMURGA_ROOT.'/uploads')?'Evet':'Hayır', is_writable(OMURGA_ROOT.'/uploads')];
    $checks[]=['storage yazılabilir', is_writable(OMURGA_ROOT.'/storage')?'Evet':'Hayır', is_writable(OMURGA_ROOT.'/storage')];
    $checks[]=['Maksimum yükleme', ini_get('upload_max_filesize'), true];
    $checks[]=['Omurga sürümü', OMURGA_VERSION, true];
    $checks[]=['Veritabanı sürümü', setting('db_version','bilinmiyor'), true];
    $checks[]=['Şema sürümü', setting('schema_version','bilinmiyor'), true];
    $checks[]=['Site türü', site_type(), true];
    $checks[]=['Panel dili', omurga_admin_language(), true];
    $checks[]=['Site dili', omurga_site_language(), true];
    $checks[]=['Aktif tema', omurga_active_theme(), true];
    return $checks;
}
function omurga_update_dir(): string { $dir=OMURGA_ROOT.'/storage/updates'; if(!is_dir($dir)) mkdir($dir,0775,true); return $dir; }


function omurga_rrmdir(string $dir): void {
    if(!is_dir($dir)) return;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $f){ $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
    @rmdir($dir);
}
function omurga_detect_update_root(string $extractDir): string {
    if(file_exists($extractDir.'/bootstrap.php')) return $extractDir;
    $items=array_values(array_filter(glob($extractDir.'/*') ?: [], 'is_dir'));
    foreach($items as $dir){ if(file_exists($dir.'/bootstrap.php')) return $dir; }
    throw new RuntimeException('Güncelleme paketi geçersiz: bootstrap.php bulunamadı.');
}
function omurga_package_version(string $packageRoot): string {
    $file=$packageRoot.'/bootstrap.php';
    $txt=file_exists($file)?file_get_contents($file):'';
    if(preg_match("/define\\(\\s*'OMURGA_VERSION'\\s*,\\s*'([^']+)'\\s*\\)/", $txt, $m)) return $m[1];
    return 'bilinmiyor';
}
function omurga_is_skipped_update_path(string $rel): bool {
    $rel=str_replace('\\','/',ltrim($rel,'/'));
    $skipExact=['config.php'];
    if(in_array($rel,$skipExact,true)) return true;
    foreach(['storage/','uploads/'] as $prefix){ if($rel==trim($prefix,'/') || str_starts_with($rel,$prefix)) return true; }
    return false;
}
function omurga_copy_update_files(string $srcRoot, string $dstRoot): array {
    $copied=0; $skipped=0;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){
        if(!$file->isFile()) continue;
        $rel=str_replace('\\','/',substr($file->getPathname(), strlen($srcRoot)+1));
        if(omurga_is_skipped_update_path($rel)){ $skipped++; continue; }
        $target=$dstRoot.'/'.$rel;
        $dir=dirname($target);
        if(!is_dir($dir)) mkdir($dir,0775,true);
        if(!copy($file->getPathname(), $target)) throw new RuntimeException('Dosya kopyalanamadı: '.$rel);
        $copied++;
    }
    return ['copied'=>$copied,'skipped'=>$skipped];
}
function omurga_apply_update_package(string $zipName): array {
    $zipName=basename($zipName);
    if(!preg_match('/^[a-zA-Z0-9._-]+\\.zip$/',$zipName)) throw new RuntimeException('Geçersiz paket adı.');
    $zipPath=omurga_update_dir().'/'.$zipName;
    if(!file_exists($zipPath)) throw new RuntimeException('Güncelleme paketi bulunamadı.');
    if(!class_exists('ZipArchive')) throw new RuntimeException('ZipArchive sunucuda aktif değil.');
    create_database_backup();
    create_uploads_backup();
    $tmp=OMURGA_ROOT.'/storage/update-temp/'.pathinfo($zipName,PATHINFO_FILENAME).'-'.date('YmdHis');
    if(is_dir($tmp)) omurga_rrmdir($tmp);
    if(!is_dir($tmp)) mkdir($tmp,0775,true);
    $zip=new ZipArchive();
    if($zip->open($zipPath)!==true) throw new RuntimeException('Güncelleme paketi açılamadı.');
    omurga_safe_extract_zip($zip, $tmp);
    $zip->close();
    $packageRoot=omurga_detect_update_root($tmp);
    $targetVersion=omurga_package_version($packageRoot);
    $result=omurga_copy_update_files($packageRoot, OMURGA_ROOT);
    update_setting('omurga_version', $targetVersion);
    update_setting('db_version', $targetVersion);
    log_activity('system.update_apply','Güncelleme uygulandı: '.$zipName.' → '.$targetVersion);
    omurga_rrmdir($tmp);
    return ['version'=>$targetVersion] + $result;
}


/* Omurga v3.4.5: Update package cleanup and update log limits */
function omurga_update_log_max_count(): int {
    $n=(int)setting('update_log_max_count','30');
    if($n<5) $n=5;
    if($n>200) $n=200;
    return $n;
}
function omurga_cleanup_old_update_logs(?int $max=null): void {
    $max=$max ?? omurga_update_log_max_count();
    try{
        $table=table_name('update_logs');
        $rows=db()->query("SELECT id FROM $table ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);
        $i=0;
        foreach($rows as $id){
            $i++;
            if($i>$max) db()->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
        }
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_delete_update_package(string $zipName): bool {
    $zipName=basename($zipName);
    if(!preg_match('/^[a-zA-Z0-9._-]+\\.zip$/',$zipName)) throw new RuntimeException('Geçersiz paket adı.');
    $file=omurga_update_dir().'/'.$zipName;
    if(!file_exists($file)) return false;
    if(!is_file($file)) throw new RuntimeException('Silinecek paket dosyası geçersiz.');
    if(!unlink($file)) throw new RuntimeException('Güncelleme paketi silinemedi.');
    log_activity('system.update_package_delete','Güncelleme paketi silindi: '.$zipName);
    return true;
}
function omurga_cleanup_uploaded_update_packages(?int $max=null): void {
    $max=$max ?? (int)setting('update_package_max_count','10');
    if($max<1) $max=1;
    if($max>100) $max=100;
    $files=glob(omurga_update_dir().'/*.zip') ?: [];
    usort($files, fn($a,$b)=>filemtime($b)<=>filemtime($a));
    $i=0;
    foreach($files as $f){
        $i++;
        if($i>$max && is_file($f)) @unlink($f);
    }
}

function omurga_uploaded_update_packages(): array {
    $files=glob(omurga_update_dir().'/*.zip') ?: [];
    usort($files, fn($a,$b)=>filemtime($b)<=>filemtime($a));
    return array_map(fn($f)=>['name'=>basename($f),'size'=>filesize($f),'date'=>date('Y-m-d H:i',filemtime($f))], $files);
}



/* Omurga v2.3: Backup limits, restore helpers and health checks */
function omurga_backup_max_count(): int {
    $n=(int)setting('backup_max_count','30');
    if($n<5) $n=5;
    if($n>200) $n=200;
    return $n;
}
function omurga_cleanup_old_backups(?int $max=null): void {
    $max=$max ?? omurga_backup_max_count();
    try{
        $rows=db()->query('SELECT * FROM '.table_name('backups').' ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        $i=0;
        foreach($rows as $r){
            $i++;
            if($i <= $max) continue;
            $path=omurga_safe_existing_file((string)($r['file_path'] ?? ''), ['storage/backups']);
            if($path) @unlink($path);
            db()->prepare('DELETE FROM '.table_name('backups').' WHERE id=?')->execute([(int)$r['id']]);
        }
    }catch(Throwable $e){ omurga_write_error($e); }
}
function omurga_backup_path_from_name(string $name): string {
    $name=basename($name);
    if(!preg_match('/^[a-zA-Z0-9._-]+\.(sql|zip)$/',$name)) throw new RuntimeException('Geçersiz yedek dosyası.');
    $path=backup_dir().'/'.$name;
    if(!is_file($path)) throw new RuntimeException('Yedek dosyası bulunamadı.');
    return $path;
}
function omurga_delete_backup(string $name): void {
    $path=omurga_backup_path_from_name($name);
    @unlink($path);
    db()->prepare('DELETE FROM '.table_name('backups').' WHERE file_path LIKE ?')->execute(['%/'.basename($name)]);
    log_activity('backup.delete','Yedek silindi: '.basename($name));
}
function omurga_restore_database_backup(string $name): void {
    $path=omurga_backup_path_from_name($name);
    if(strtolower(pathinfo($path,PATHINFO_EXTENSION))!=='sql') throw new RuntimeException('Sadece SQL yedeği veritabanına geri yüklenebilir.');
    create_database_backup();
    $sql=file_get_contents($path);
    if($sql===false || trim($sql)==='') throw new RuntimeException('SQL yedeği okunamadı.');
    db()->exec($sql);
    log_activity('backup.restore','Veritabanı yedeği geri yüklendi: '.basename($path));
}
function omurga_restore_uploads_backup(string $name): void {
    $path=omurga_backup_path_from_name($name);
    if(strtolower(pathinfo($path,PATHINFO_EXTENSION))!=='zip') throw new RuntimeException('Sadece ZIP dosya yedeği geri yüklenebilir.');
    if(!class_exists('ZipArchive')) throw new RuntimeException('ZipArchive aktif değil.');
    create_uploads_backup();
    $zip=new ZipArchive();
    if($zip->open($path)!==true) throw new RuntimeException('ZIP yedeği açılamadı.');
    omurga_safe_extract_zip($zip, OMURGA_ROOT);
    $zip->close();
    log_activity('backup.restore','Dosya yedeği geri yüklendi: '.basename($path));
}
function omurga_format_bytes(int $bytes): string {
    $units=['B','KB','MB','GB','TB']; $i=0; $v=max(0,$bytes);
    while($v>=1024 && $i<count($units)-1){$v/=1024;$i++;}
    return number_format($v, $i?1:0).' '.$units[$i];
}
function omurga_directory_size(string $dir): int {
    if(!is_dir($dir)) return 0;
    $size=0; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){ if($f->isFile()) $size += $f->getSize(); }
    return $size;
}
function omurga_v23_system_health(): array {
    $checks=[];
    $add=function($name,$value,$ok,$level='ok',$note='') use (&$checks){ $checks[]=['name'=>$name,'value'=>$value,'ok'=>(bool)$ok,'level'=>$ok?'ok':$level,'note'=>$note]; };
    $add('PHP sürümü', PHP_VERSION, version_compare(PHP_VERSION,'8.0.0','>='), 'error', 'PHP 8.0+ önerilir.');
    $add('PDO MySQL', extension_loaded('pdo_mysql')?'Aktif':'Eksik', extension_loaded('pdo_mysql'), 'error');
    $add('GD / WebP', function_exists('imagewebp')?'Aktif':'Eksik', function_exists('imagewebp'), 'warning', 'WebP dönüşümü için gerekir.');
    $add('Imagick', extension_loaded('imagick')?'Aktif':'Yok', true, 'ok');
    $add('ZipArchive', class_exists('ZipArchive')?'Aktif':'Eksik', class_exists('ZipArchive'), 'warning', 'Dosya yedeği ve güncelleme için gerekir.');
    $add('JSON', extension_loaded('json')?'Aktif':'Eksik', extension_loaded('json'), 'error');
    $add('cURL', extension_loaded('curl')?'Aktif':'Yok', true, 'ok');
    $add('Upload limiti', ini_get('upload_max_filesize'), true);
    $add('Post max size', ini_get('post_max_size'), true);
    $add('Memory limit', ini_get('memory_limit'), true);
    foreach(['uploads','storage','storage/backups','storage/cache','storage/logs','storage/updates'] as $d){
        $path=OMURGA_ROOT.'/'.$d; if(!is_dir($path)) @mkdir($path,0775,true);
        $add($d.' yazılabilir', is_writable($path)?'Evet':'Hayır', is_writable($path), 'error');
    }
    $add('Backup klasörü boyutu', omurga_format_bytes(omurga_directory_size(backup_dir())), true);
    $add('Omurga sürümü', OMURGA_VERSION, true);
    $add('Veritabanı sürümü', setting('db_version','bilinmiyor'), true);
    $add('Şema sürümü', setting('schema_version','bilinmiyor'), true);
    return $checks;
}
function omurga_recent_error_lines(int $limit=100): array {
    $file=OMURGA_ROOT.'/storage/logs/error.log';
    if(!is_file($file)) return [];
    $lines=file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    return array_slice($lines, -$limit);
}



/* Omurga v2.4: Cache + Performance helpers */
function omurga_cache_base_dir(): string {
    $dir=OMURGA_ROOT.'/storage/cache';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    foreach(['pages','blocks','templates','assets'] as $sub){ if(!is_dir($dir.'/'.$sub)) @mkdir($dir.'/'.$sub,0775,true); }
    return $dir;
}
function omurga_cache_enabled(string $type='page'): bool {
    $key = $type==='page' ? 'perf_page_cache' : ($type==='block' ? 'perf_block_cache' : ($type==='template' ? 'perf_template_cache' : 'perf_cache'));
    return setting($key,'1')==='1';
}
function omurga_cache_ttl(string $type='page'): int {
    $map=['page'=>'perf_page_cache_ttl','block'=>'perf_block_cache_ttl','template'=>'perf_template_cache_ttl','asset'=>'perf_asset_cache_ttl'];
    $ttl=(int)setting($map[$type] ?? 'perf_cache_ttl','900');
    if($ttl<30) $ttl=30;
    if($ttl>86400) $ttl=86400;
    return $ttl;
}
function omurga_cache_key(string $key): string { return sha1($key); }
function omurga_cache_path(string $type, string $key): string {
    $type=preg_replace('/[^a-z0-9_-]/i','',$type) ?: 'pages';
    $base=omurga_cache_base_dir().'/'.$type;
    if(!is_dir($base)) @mkdir($base,0775,true);
    return $base.'/'.omurga_cache_key($key).'.cache';
}
function omurga_cache_get(string $type, string $key, ?int $ttl=null): ?string {
    if(!omurga_cache_enabled($type==='pages'?'page':rtrim($type,'s'))) return null;
    $path=omurga_cache_path($type,$key);
    if(!is_file($path)) return null;
    $ttl=$ttl ?? omurga_cache_ttl($type==='pages'?'page':rtrim($type,'s'));
    if(time()-filemtime($path) > $ttl) { @unlink($path); return null; }
    $data=@file_get_contents($path);
    return $data===false ? null : $data;
}
function omurga_cache_set(string $type, string $key, string $content): void {
    if(!omurga_cache_enabled($type==='pages'?'page':rtrim($type,'s'))) return;
    $path=omurga_cache_path($type,$key);
    @file_put_contents($path,$content,LOCK_EX);
}
function omurga_cache_delete_type(string $type): int {
    $dir=omurga_cache_base_dir().'/'.preg_replace('/[^a-z0-9_-]/i','',$type);
    if(!is_dir($dir)) return 0;
    $count=0;
    foreach(glob($dir.'/*') ?: [] as $file){ if(is_file($file) && @unlink($file)) $count++; }
    return $count;
}
function omurga_cache_clear(?string $type=null): int {
    if($type) return omurga_cache_delete_type($type);
    $count=0; foreach(['pages','blocks','templates','assets'] as $t){ $count += omurga_cache_delete_type($t); }
    log_activity('cache.clear','Cache temizlendi'.($type?': '.$type:''));
    return $count;
}
function omurga_cache_stats(): array {
    $stats=[]; $base=omurga_cache_base_dir();
    foreach(['pages'=>'Sayfa cache','blocks'=>'Blok cache','templates'=>'OMG şablon cache','assets'=>'Varlık cache'] as $dir=>$label){
        $path=$base.'/'.$dir; $files=0; $size=0; if(is_dir($path)){ foreach(glob($path.'/*') ?: [] as $f){ if(is_file($f)){ $files++; $size+=filesize($f); } } }
        $stats[$dir]=['label'=>$label,'files'=>$files,'size'=>$size,'size_human'=>omurga_format_bytes($size)];
    }
    return $stats;
}
function omurga_current_page_cache_key(): string {
    $uri=$_SERVER['REQUEST_URI'] ?? '/';
    return ($_SERVER['HTTP_HOST'] ?? 'localhost').'|'.$uri;
}
function omurga_page_cache_start(): void {
    if(!omurga_cache_enabled('page') || is_admin_logged_in() || ($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET') return;
    $hit=omurga_cache_get('pages',omurga_current_page_cache_key(),omurga_cache_ttl('page'));
    if($hit!==null){ echo $hit; exit; }
    ob_start(function($html){
        if(http_response_code()===200 && is_string($html) && trim($html)!==''){
            if(setting('perf_minify_html','0')==='1') $html=omurga_minify_html($html);
            omurga_cache_set('pages',omurga_current_page_cache_key(),$html);
        }
        return $html;
    });
}
function omurga_minify_html(string $html): string {
    $html=preg_replace('/<!--(?!\[if).*?-->/s','',$html);
    $html=preg_replace('/>\s+</','><',$html);
    $html=preg_replace('/\s{2,}/',' ',$html);
    return trim($html);
}
function omurga_after_content_change(?int $postId=null, ?int $categoryId=null): void {
    omurga_cache_delete_type('pages');
    omurga_cache_delete_type('blocks');
    omurga_cache_delete_type('templates');
    log_activity('cache.auto_clear','İçerik değişikliği sonrası cache temizlendi.');
}
function omurga_block_cache(string $key, callable $callback, ?int $ttl=null): string {
    $cached=omurga_cache_get('blocks',$key,$ttl ?? omurga_cache_ttl('block'));
    if($cached!==null) return $cached;
    $html=(string)$callback();
    omurga_cache_set('blocks',$key,$html);
    return $html;
}
function omurga_template_cache_key(string $file): string { return $file.'|'.(@filemtime($file) ?: 0); }
function omurga_performance_defaults(): array {
    return [
        'perf_page_cache'=>'1','perf_block_cache'=>'1','perf_template_cache'=>'1','perf_minify_html'=>'0',
        'perf_page_cache_ttl'=>'900','perf_block_cache_ttl'=>'600','perf_template_cache_ttl'=>'3600','perf_asset_cache_ttl'=>'86400',
        'perf_auto_clear_on_publish'=>'1'
    ];
}
function omurga_save_performance_settings(array $data): void {
    foreach(omurga_performance_defaults() as $key=>$default){
        if(str_ends_with($key,'_ttl')){ $v=(string)max(30,min(86400,(int)($data[$key] ?? $default))); }
        else { $v=isset($data[$key]) ? '1' : '0'; }
        update_setting($key,$v);
    }
}

function log_activity(string $action, string $message='', ?int $userId=null, string $module='', string $entityType='', ?int $entityId=null, array $details=[]): void {
    try{
        omurga_migrate();
        $uid=$userId ?? (int)($_SESSION['omurga_user_id'] ?? 0) ?: null;
        $t=table_name('activity_logs');
        $cols=db()->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_COLUMN);
        if(in_array('module',$cols,true)){
            db()->prepare("INSERT INTO $t (user_id,action,module,entity_type,entity_id,message,details,ip) VALUES (?,?,?,?,?,?,?,?)")->execute([$uid,$action,$module?:null,$entityType?:null,$entityId,$message,$details?json_encode($details,JSON_UNESCAPED_UNICODE):null,$_SERVER['REMOTE_ADDR'] ?? '']);
        } else {
            db()->prepare('INSERT INTO '.$t.' (user_id,action,message,ip) VALUES (?,?,?,?)')->execute([$uid,$action,$message,$_SERVER['REMOTE_ADDR'] ?? '']);
        }
    }catch(Throwable $e){ }
}

function omurga_notify(string $title, string $message='', string $type='info', string $link='', ?int $userId=null): void {
    try{
        omurga_migrate();
        db()->prepare('INSERT INTO '.table_name('notifications').' (user_id,type,title,message,link) VALUES (?,?,?,?,?)')->execute([$userId,$type,$title,$message,$link]);
    }catch(Throwable $e){ }
}
function omurga_unread_notification_count(?int $userId=null): int {
    try{
        omurga_migrate();
        $t=table_name('notifications');
        if($userId){ $st=db()->prepare("SELECT COUNT(*) FROM $t WHERE is_read=0 AND (user_id IS NULL OR user_id=?)"); $st->execute([$userId]); return (int)$st->fetchColumn(); }
        return (int)db()->query("SELECT COUNT(*) FROM $t WHERE is_read=0")->fetchColumn();
    }catch(Throwable $e){ return 0; }
}
function omurga_mark_notifications_read(array $ids=[]): void {
    try{
        omurga_migrate(); $t=table_name('notifications');
        if($ids){ $ids=array_values(array_filter(array_map('intval',$ids))); if(!$ids)return; db()->exec("UPDATE $t SET is_read=1 WHERE id IN (".implode(',',$ids).")"); }
        else db()->exec("UPDATE $t SET is_read=1 WHERE is_read=0");
    }catch(Throwable $e){ }
}
function omurga_clear_old_notifications(int $days=90): void {
    try{ $days=max(7,min(365,$days)); db()->prepare('DELETE FROM '.table_name('notifications').' WHERE is_read=1 AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)')->execute([$days]); }catch(Throwable $e){}
}
function backup_dir(): string { $dir=OMURGA_ROOT.'/storage/backups'; if(!is_dir($dir)) mkdir($dir,0775,true); return $dir; }
function create_database_backup(): string {
    $cfg=omurga_config(); $prefix=$cfg['db']['prefix']; $file=backup_dir().'/omurga-db-'.date('Ymd-His').'.sql';
    $tables=db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $out="-- Omurga veritabanı yedeği\n-- Tarih: ".date('c')."\n\n";
    foreach($tables as $table){ if(strpos($table,$prefix)!==0) continue; $create=db()->query('SHOW CREATE TABLE `'.$table.'`')->fetch(); $out.="DROP TABLE IF EXISTS `$table`;\n".$create['Create Table'].";\n\n"; $rows=db()->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC); foreach($rows as $row){ $cols=array_map(fn($c)=>'`'.$c.'`', array_keys($row)); $vals=array_map(fn($v)=>$v===null?'NULL':db()->quote((string)$v), array_values($row)); $out.='INSERT INTO `'.$table.'` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).');' . "\n"; } $out.="\n"; }
    file_put_contents($file,$out); db()->prepare('INSERT INTO '.table_name('backups').' (backup_type,file_path,file_size,created_by) VALUES (?,?,?,?)')->execute(['database','storage/backups/'.basename($file),filesize($file),$_SESSION['omurga_user_id']??null]); log_activity('backup.create','Veritabanı yedeği alındı: '.basename($file)); omurga_cleanup_old_backups(); return $file;
}
function create_uploads_backup(): ?string {
    if(!class_exists('ZipArchive')) return null; $src=OMURGA_ROOT.'/uploads'; $file=backup_dir().'/omurga-uploads-'.date('Ymd-His').'.zip'; $zip=new ZipArchive(); if($zip->open($file,ZipArchive::CREATE)!==true) return null;
    if(is_dir($src)){ $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,FilesystemIterator::SKIP_DOTS)); foreach($it as $f){ if($f->isFile()){ $zip->addFile($f->getPathname(), 'uploads/'.substr($f->getPathname(), strlen($src)+1)); } } }
    $zip->close(); db()->prepare('INSERT INTO '.table_name('backups').' (backup_type,file_path,file_size,created_by) VALUES (?,?,?,?)')->execute(['uploads','storage/backups/'.basename($file),file_exists($file)?filesize($file):0,$_SESSION['omurga_user_id']??null]); log_activity('backup.create','Dosya yedeği alındı: '.basename($file)); omurga_cleanup_old_backups(); return $file;
}


/* Omurga v4.0 - Package API */
function omurga_packages_dir(): string { $dir=OMURGA_ROOT.'/packages'; if(!is_dir($dir)) @mkdir($dir,0775,true); return $dir; }
function omurga_package_slug(string $slug): string { return preg_replace('/[^a-z0-9_-]/','', strtolower($slug)); }
function omurga_active_packages(): array { return setting_json('active_packages', []); }
function omurga_update_active_packages(array $packages): void { update_setting_json('active_packages', array_values(array_unique(array_filter(array_map('omurga_package_slug',$packages))))); }
function omurga_package_path(string $slug): string { return omurga_packages_dir().'/'.omurga_package_slug($slug); }
function omurga_package_main_file(array $meta): string {
    $main=preg_replace('/[^a-zA-Z0-9_\-\.\/]/','', (string)($meta['main'] ?? 'package.php'));
    $main=str_replace(['..','\\'], ['', '/'], $main);
    return rtrim((string)($meta['path'] ?? ''),'/').'/'.ltrim($main ?: 'package.php','/');
}
function omurga_package_requirement_messages(array $meta, array $active=[]): array {
    $messages=[];
    $minPhp=(string)($meta['min_php'] ?? '');
    if($minPhp!=='' && version_compare(PHP_VERSION, $minPhp, '<')) $messages[]='PHP '.$minPhp.' veya üzeri gerekli.';
    $minOmurga=(string)($meta['min_omurga'] ?? '');
    if($minOmurga!=='' && defined('OMURGA_VERSION') && version_compare(OMURGA_VERSION, $minOmurga, '<')) $messages[]='Omurga '.$minOmurga.' veya üzeri gerekli.';
    foreach((array)($meta['requires'] ?? []) as $req){
        $req=omurga_package_slug((string)$req);
        if($req!=='' && !in_array($req,$active,true)) $messages[]='Gerekli paket aktif değil: '.$req;
    }
    $main=omurga_package_main_file($meta);
    if(!is_file($main)) $messages[]='Ana paket dosyası bulunamadı: '.basename($main);
    return $messages;
}
function omurga_read_package_json(string $json): ?array {
    if(!is_file($json)) return null;
    $data=json_decode((string)file_get_contents($json), true);
    if(!is_array($data)) return null;
    $slug=omurga_package_slug($data['id'] ?? $data['slug'] ?? basename(dirname($json)));
    if($slug==='') return null;
    $meta=[
        'slug'=>$slug,
        'id'=>$slug,
        'name'=>$data['name'] ?? ucwords(str_replace('-', ' ', $slug)),
        'version'=>$data['version'] ?? '1.0.0',
        'description'=>$data['description'] ?? '',
        'author'=>$data['author'] ?? '',
        'min_php'=>$data['min_php'] ?? '',
        'min_omurga'=>$data['min_omurga_version'] ?? $data['min_omurga'] ?? '',
        'requires'=>is_array($data['requires'] ?? null) ? array_values(array_map('omurga_package_slug', $data['requires'])) : [],
        'main'=>$data['entry'] ?? $data['main'] ?? 'package.php',
        'settings'=>is_array($data['settings'] ?? null) ? $data['settings'] : [],
        'admin_pages'=>is_array($data['admin_pages'] ?? null) ? $data['admin_pages'] : [],
        'blocks'=>is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
        'path'=>dirname($json),
        'package_file'=>dirname($json).'/'.ltrim(str_replace(['..','\\'], ['', '/'], (string)($data['entry'] ?? $data['main'] ?? 'package.php')),'/'),
        'manifest'=>$json,
        'type'=>'package',
    ];
    $meta['requirement_messages']=omurga_package_requirement_messages($meta, omurga_active_packages());
    $meta['compatible']=empty($meta['requirement_messages']);
    return $meta;
}
function omurga_all_packages(): array {
    $packages=[];
    foreach((glob(omurga_packages_dir().'/*/package.json') ?: []) as $json){
        $meta=omurga_read_package_json($json);
        if($meta) $packages[$meta['slug']]=$meta;
    }
    ksort($packages);
    return $packages;
}
function omurga_package_is_active(string $slug): bool { return in_array(omurga_package_slug($slug), omurga_active_packages(), true); }
function omurga_activate_package(string $slug): bool {
    $slug=omurga_package_slug($slug); $all=omurga_all_packages(); if(!isset($all[$slug])) return false;
    if(empty($all[$slug]['compatible'])) throw new RuntimeException('Paket etkinleştirilemedi: '.implode(' ', $all[$slug]['requirement_messages'] ?? []));
    $active=omurga_active_packages(); if(!in_array($slug,$active,true)) $active[]=$slug;
    omurga_update_active_packages($active);
    $file=$all[$slug]['package_file'] ?? '';
    if(is_file($file)){ try{ require_once $file; }catch(Throwable $e){ omurga_write_error($e); } }
    omurga_action('omurga_package_activated', $slug, $all[$slug]);
    return true;
}
function omurga_deactivate_package(string $slug): bool {
    $slug=omurga_package_slug($slug); $all=omurga_all_packages();
    omurga_action('omurga_package_deactivated', $slug, $all[$slug] ?? []);
    omurga_update_active_packages(array_values(array_filter(omurga_active_packages(), fn($p)=>$p!==$slug)));
    return true;
}
function omurga_load_active_packages(): void {
    static $loaded=false; if($loaded) return; $loaded=true;
    $all=omurga_all_packages();
    foreach(omurga_active_packages() as $slug){
        if(empty($all[$slug]) || empty($all[$slug]['compatible'])) continue;
        $file=$all[$slug]['package_file'] ?? '';
        if(is_file($file)){ try{ require_once $file; omurga_action('omurga_package_loaded', $slug, $all[$slug]); }catch(Throwable $e){ omurga_write_error($e); } }
    }
}
function omurga_package_blocks(): array {
    $blocks=[]; $all=omurga_all_packages();
    foreach(omurga_active_packages() as $slug){
        if(empty($all[$slug])) continue;
        $found=omurga_scan_blocks_dir($all[$slug]['path'].'/blocks', 'package', null);
        foreach($found as $key=>$block){ $block['package']=$slug; $block['category']=$block['category'] ?? 'Paket Blokları'; $blocks[$key]=$block; }
    }
    return $blocks;
}

/* Omurga v2.5 - Eklenti Sistemi (legacy uyumluluk) */
function omurga_plugins_dir(): string { $dir=OMURGA_ROOT.'/plugins'; if(!is_dir($dir)) @mkdir($dir,0775,true); return $dir; }
function omurga_active_plugins(): array { return setting_json('active_plugins', []); }
function omurga_update_active_plugins(array $plugins): void { update_setting_json('active_plugins', array_values(array_unique(array_filter($plugins)))); }
function omurga_plugin_slug(string $slug): string { return preg_replace('/[^a-z0-9_-]/','', strtolower($slug)); }
function omurga_plugin_path(string $slug): string { return omurga_plugins_dir().'/'.omurga_plugin_slug($slug); }
function omurga_plugin_main_file(array $meta): string {
    $main=preg_replace('/[^a-zA-Z0-9_\-\.\/]/','', (string)($meta['main'] ?? 'plugin.php'));
    $main=str_replace(['..','\\'], ['', '/'], $main);
    return rtrim((string)($meta['path'] ?? ''),'/').'/'.ltrim($main ?: 'plugin.php','/');
}
function omurga_plugin_requirement_messages(array $meta, array $active=[]): array {
    $messages=[];
    $minPhp=(string)($meta['min_php'] ?? '');
    if($minPhp!=='' && version_compare(PHP_VERSION, $minPhp, '<')) $messages[]='PHP '.$minPhp.' veya üzeri gerekli.';
    $minOmurga=(string)($meta['min_omurga'] ?? '');
    if($minOmurga!=='' && defined('OMURGA_VERSION') && version_compare(OMURGA_VERSION, $minOmurga, '<')) $messages[]='Omurga '.$minOmurga.' veya üzeri gerekli.';
    foreach((array)($meta['requires'] ?? []) as $req){
        $req=omurga_plugin_slug((string)$req);
        if($req!=='' && !in_array($req,$active,true)) $messages[]='Gerekli eklenti aktif değil: '.$req;
    }
    $main=omurga_plugin_main_file($meta);
    if(!is_file($main)) $messages[]='Ana eklenti dosyası bulunamadı: '.basename($main);
    return $messages;
}
function omurga_read_plugin_json(string $json): ?array {
    if(!is_file($json)) return null;
    $data=json_decode((string)file_get_contents($json), true);
    if(!is_array($data)) return null;
    $slug=omurga_plugin_slug($data['slug'] ?? basename(dirname($json)));
    if($slug==='') return null;
    $meta=[
        'slug'=>$slug,
        'name'=>$data['name'] ?? ucwords(str_replace('-', ' ', $slug)),
        'version'=>$data['version'] ?? '1.0.0',
        'description'=>$data['description'] ?? '',
        'author'=>$data['author'] ?? '',
        'min_php'=>$data['min_php'] ?? '',
        'min_omurga'=>$data['min_omurga_version'] ?? $data['min_omurga'] ?? '',
        'requires'=>is_array($data['requires'] ?? null) ? array_values(array_map('omurga_plugin_slug', $data['requires'])) : [],
        'main'=>$data['main'] ?? 'plugin.php',
        'settings'=>is_array($data['settings'] ?? null) ? $data['settings'] : [],
        'admin_pages'=>is_array($data['admin_pages'] ?? null) ? $data['admin_pages'] : [],
        'blocks'=>is_array($data['blocks'] ?? null) ? $data['blocks'] : [],
        'path'=>dirname($json),
        'plugin_file'=>dirname($json).'/'.ltrim(str_replace(['..','\\'], ['', '/'], (string)($data['main'] ?? 'plugin.php')),'/'),
        'manifest'=>$json,
        'legacy'=>false,
    ];
    $meta['requirement_messages']=omurga_plugin_requirement_messages($meta, omurga_active_plugins());
    $meta['compatible']=empty($meta['requirement_messages']);
    return $meta;
}
function omurga_read_legacy_plugin(string $dir): ?array {
    $slug=omurga_plugin_slug(basename($dir));
    $file=rtrim($dir,'/\\').'/plugin.php';
    if($slug==='' || !is_file($file)) return null;
    $meta=[
        'slug'=>$slug,
        'name'=>ucwords(str_replace('-', ' ', $slug)),
        'version'=>'1.0.0',
        'description'=>'Eski eklenti yapısı: plugin.json yok.',
        'author'=>'',
        'min_php'=>'',
        'min_omurga'=>'',
        'requires'=>[],
        'main'=>'plugin.php',
        'settings'=>[],
        'admin_pages'=>[],
        'blocks'=>[],
        'path'=>$dir,
        'plugin_file'=>$file,
        'manifest'=>'',
        'legacy'=>true,
    ];
    $meta['requirement_messages']=omurga_plugin_requirement_messages($meta, omurga_active_plugins());
    $meta['compatible']=empty($meta['requirement_messages']);
    return $meta;
}
function omurga_all_plugins(): array { return []; /* v4: plugins/ pasif; resmi sistem packages/ */ }
function omurga_plugin_is_active(string $slug): bool { return false; }
function omurga_activate_plugin(string $slug): bool {
    $slug=omurga_plugin_slug($slug); $all=omurga_all_plugins(); if(!isset($all[$slug])) return false;
    if(empty($all[$slug]['compatible'])) throw new RuntimeException('Eklenti etkinleştirilemedi: '.implode(' ', $all[$slug]['requirement_messages'] ?? []));
    $active=omurga_active_plugins(); if(!in_array($slug,$active,true)) $active[]=$slug;
    omurga_update_active_plugins($active);
    // Aktivasyon anında eklenti dosyasını yükle, kurulum/migration kancalarını çalıştır.
    $file=$all[$slug]['plugin_file'] ?? '';
    if(is_file($file)){ try{ require_once $file; }catch(Throwable $e){ omurga_write_error($e); } }
    omurga_do_action('plugin.activate', $slug, $all[$slug]);
    omurga_run_plugin_migrations($slug);
    log_activity('plugin.activate', $all[$slug]['name'].' eklentisi etkinleştirildi.');
    omurga_notify('Eklenti etkinleştirildi', $all[$slug]['name'].' eklentisi aktif edildi.', 'plugin', 'admin/plugins.php');
    return true;
}
function omurga_deactivate_plugin(string $slug): bool {
    $slug=omurga_plugin_slug($slug); $all=omurga_all_plugins();
    omurga_do_action('plugin.deactivate', $slug, $all[$slug] ?? []);
    $active=array_values(array_filter(omurga_active_plugins(), fn($p)=>$p!==$slug));
    omurga_update_active_plugins($active); log_activity('plugin.deactivate', $slug.' eklentisi pasifleştirildi.'); return true;
}
$GLOBALS['omurga_plugin_admin_pages']=$GLOBALS['omurga_plugin_admin_pages'] ?? [];
$GLOBALS['omurga_plugin_permissions']=$GLOBALS['omurga_plugin_permissions'] ?? [];
$GLOBALS['omurga_plugin_migrations']=$GLOBALS['omurga_plugin_migrations'] ?? [];
$GLOBALS['omurga_admin_boxes']=$GLOBALS['omurga_admin_boxes'] ?? [];
$GLOBALS['omurga_form_field_types']=$GLOBALS['omurga_form_field_types'] ?? [];
$GLOBALS['omurga_registered_blocks']=$GLOBALS['omurga_registered_blocks'] ?? [];
/* Omurga v3.1 - Güçlendirilmiş Eklenti API */
function omurga_register_admin_page(string $slug, string $title, $fileOrCallback, string $capability='plugins.manage', string $icon='▣', int $position=50): void {
    $slug=omurga_plugin_slug($slug); if($slug==='') return;
    $GLOBALS['omurga_plugin_admin_pages'][$slug]=['id'=>$slug,'title'=>$title,'menu_title'=>$title,'file'=>$fileOrCallback,'cap'=>$capability,'icon'=>$icon,'position'=>$position,'registered'=>true];
}
function omurga_register_permission(string $capability, string $label='', array $defaultRoles=[]): void {
    $capability=preg_replace('/[^a-zA-Z0-9_.:-]/','',$capability); if($capability==='') return;
    $GLOBALS['omurga_plugin_permissions'][$capability]=['capability'=>$capability,'label'=>$label ?: $capability,'default_roles'=>$defaultRoles];
}
function omurga_register_plugin_migration(string $pluginSlug, string $version, callable $callback): void {
    $pluginSlug=omurga_plugin_slug($pluginSlug); if($pluginSlug==='' || $version==='') return;
    $GLOBALS['omurga_plugin_migrations'][$pluginSlug][$version]=$callback;
}
function omurga_plugin_migration_key(string $pluginSlug): string { return 'plugin_migrations_'.omurga_plugin_slug($pluginSlug); }
function omurga_run_plugin_migrations(?string $pluginSlug=null): void {
    $targets=$pluginSlug ? [omurga_plugin_slug($pluginSlug)] : array_keys($GLOBALS['omurga_plugin_migrations'] ?? []);
    foreach($targets as $slug){ $migs=$GLOBALS['omurga_plugin_migrations'][$slug] ?? []; if(!$migs) continue; ksort($migs); $done=setting_json(omurga_plugin_migration_key($slug), []);
        foreach($migs as $version=>$cb){ if(in_array($version,$done,true)) continue; try{ $cb(); $done[]=$version; update_setting_json(omurga_plugin_migration_key($slug), $done); log_activity('plugin.migration', $slug.' migration çalıştı: '.$version); }catch(Throwable $e){ omurga_write_error($e); } }
    }
}
function omurga_register_tpl_tag(string $tag, callable $callback): void { /* v4: TPL etiketi desteği kaldırıldı. */ }
function omurga_registered_tpl_tags(): array { return []; }
function omurga_register_admin_box(string $screen, string $id, string $title, callable $callback, string $capability='posts.edit'): void {
    $screen=preg_replace('/[^a-zA-Z0-9_.:-]/','',$screen); $id=preg_replace('/[^a-zA-Z0-9_.:-]/','',$id); if($screen===''||$id==='') return;
    $GLOBALS['omurga_admin_boxes'][$screen][$id]=['id'=>$id,'title'=>$title,'callback'=>$callback,'cap'=>$capability];
}
function omurga_render_admin_boxes(string $screen, array $context=[]): string {
    $boxes=$GLOBALS['omurga_admin_boxes'][$screen] ?? []; if(!$boxes) return '';
    ob_start(); foreach($boxes as $box){ if(!can($box['cap']) && current_user_role()!=='admin') continue; echo '<div class="card plugin-admin-box"><h2>'.e($box['title']).'</h2>'; try{ echo (string)$box['callback']($context); }catch(Throwable $e){ omurga_write_error($e); echo '<p class="alert danger">Eklenti kutusu yüklenemedi.</p>'; } echo '</div>'; } return (string)ob_get_clean();
}
function omurga_register_form_field_type(string $type, string $label, callable $renderer=null, callable $sanitizer=null): void {
    $type=preg_replace('/[^a-zA-Z0-9_-]/','',$type); if($type==='') return; $GLOBALS['omurga_form_field_types'][$type]=['type'=>$type,'label'=>$label,'renderer'=>$renderer,'sanitizer'=>$sanitizer];
}
function omurga_registered_form_field_types(): array { return $GLOBALS['omurga_form_field_types'] ?? []; }
function omurga_plugin_setting_key(string $slug): string { return 'plugin_settings_'.omurga_plugin_slug($slug); }
function omurga_plugin_settings(string $slug): array { return setting_json(omurga_plugin_setting_key($slug), []); }
function omurga_plugin_setting(string $slug, string $key, $default=null){ $settings=omurga_plugin_settings($slug); return $settings[$key] ?? $default; }
function omurga_update_plugin_settings(string $slug, array $settings): void { update_setting_json(omurga_plugin_setting_key($slug), $settings); }
function omurga_load_active_plugins(): void { /* v4: legacy plugins pasif. Paket API kullanılır. */ }
function omurga_plugin_blocks(): array { return []; }
function omurga_plugin_admin_pages(): array {
    $pages=[];
    foreach(omurga_all_plugins() as $slug=>$meta){
        if(!omurga_plugin_is_active($slug)) continue;
        foreach($meta['admin_pages'] as $i=>$page){
            if(!is_array($page)) continue;
            $file=$meta['path'].'/'.ltrim((string)($page['file'] ?? ''),'/'); if(!is_file($file)) continue;
            $id=omurga_plugin_slug((string)($page['id'] ?? $page['slug'] ?? ('manifest-'.$slug.'-'.$i)));
            $pages[$slug.':'.$id]=['id'=>$id,'plugin'=>$slug,'title'=>$page['title'] ?? $meta['name'],'menu_title'=>$page['menu_title'] ?? $page['title'] ?? $meta['name'],'file'=>$file,'cap'=>$page['capability'] ?? 'plugins.manage','icon'=>$page['icon'] ?? '▣','position'=>(int)($page['position'] ?? 50),'registered'=>false];
        }
    }
    foreach(($GLOBALS['omurga_plugin_admin_pages'] ?? []) as $id=>$page){
        $page['plugin']=$page['plugin'] ?? 'registered'; $page['id']=$page['id'] ?? $id; $pages['registered:'.$id]=$page;
    }
    uasort($pages, fn($a,$b)=>($a['position'] ?? 50)<=>($b['position'] ?? 50));
    return array_values($pages);
}
function omurga_find_plugin_admin_page(string $plugin, string $pageId): ?array {
    $plugin=omurga_plugin_slug($plugin); $pageId=omurga_plugin_slug($pageId);
    foreach(omurga_plugin_admin_pages() as $page){ if(($page['plugin'] ?? '')===$plugin && ($page['id'] ?? '')===$pageId) return $page; }
    if($plugin==='registered'){ foreach(omurga_plugin_admin_pages() as $page){ if(($page['id'] ?? '')===$pageId) return $page; } }
    return null;
}
function omurga_render_plugin_settings_form(array $plugin): string {
    $defs=$plugin['settings'] ?? []; if(!$defs) return '<p class="muted">Bu eklenti için ayar tanımlanmamış.</p>';
    $values=omurga_plugin_settings($plugin['slug']); ob_start();
    foreach($defs as $key=>$field){ $type=$field['type'] ?? 'text'; $label=$field['label'] ?? $key; $value=$values[$key] ?? ($field['default'] ?? ''); echo '<label class="form-label">'.e($label).'</label>';
        if($type==='textarea') echo '<textarea name="settings['.e($key).']" rows="4">'.e($value).'</textarea>';
        elseif($type==='checkbox') echo '<label class="check"><input type="checkbox" name="settings['.e($key).']" value="1" '.((string)$value==='1'||$value===true?'checked':'').'> Aktif</label>';
        elseif($type==='select'){ echo '<select name="settings['.e($key).']">'; foreach(($field['options'] ?? []) as $ov=>$ol){ echo '<option value="'.e($ov).'" '.((string)$value===(string)$ov?'selected':'').'>'.e($ol).'</option>'; } echo '</select>'; }
        else echo '<input type="'.e(in_array($type,['text','url','number','color'],true)?$type:'text').'" name="settings['.e($key).']" value="'.e($value).'">';
    }
    return (string)ob_get_clean();
}


/* Omurga v3.2 - Güvenlik Merkezi yardımcıları */
function omurga_security_setting(string $key, string $default='1'): string { return setting('security_'.$key, $default); }
function omurga_security_enabled(string $key, bool $default=true): bool { return omurga_security_setting($key, $default ? '1':'0') === '1'; }
function omurga_security_is_ip_blocked(?string $ip=null): bool {
    $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '');
    $list = preg_split('/\r\n|\r|\n/', setting('security_blocked_ips','') ?: '');
    foreach($list as $line){ $line=trim($line); if($line!=='' && $line===$ip) return true; }
    return false;
}
function omurga_maintenance_enabled(): bool { return setting('security_maintenance_mode','0') === '1'; }
function omurga_maintenance_message(): string { return setting('security_maintenance_message','Sitemiz kısa süreli bakım modundadır. Lütfen daha sonra tekrar deneyin.'); }
function omurga_security_can_edit_theme(): bool { return omurga_security_enabled('theme_editor', true) && (can('users.manage') || current_user_role()==='admin'); }
function omurga_security_can_edit_php_files(): bool { return omurga_security_enabled('php_file_edit', false) && current_user_role()==='admin'; }
function omurga_security_can_upload_plugins(): bool { return omurga_security_enabled('plugin_upload', true) && (can('plugins.manage') || current_user_role()==='admin'); }
function omurga_security_can_delete_plugins(): bool { return omurga_security_enabled('plugin_delete', false) && current_user_role()==='admin'; }

omurga_load_active_packages();
omurga_load_active_plugins();
omurga_run_plugin_migrations();
omurga_do_action('omurga_theme_loaded', function_exists('omurga_active_theme') ? omurga_active_theme() : '');
