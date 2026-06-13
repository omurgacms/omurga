<?php
session_start();
define('OMURGA_ROOT', dirname(__DIR__));
if (file_exists(OMURGA_ROOT . '/storage/installed.lock')) {
    header('Location: ../admin/');
    exit;
}

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function req($key,$default=''){ return $_POST[$key] ?? $default; }
function slugify_install($text){
    $map=['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'];
    $text=strtolower(strtr($text,$map));
    $text=preg_replace('/[^a-z0-9]+/','-',$text);
    return trim($text,'-') ?: 'icerik';
}
function base_url_guess(){
    $https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme=$https?'https':'http';
    $host=$_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir=rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'))),'/');
    if ($dir==='/' || $dir==='.') $dir='';
    return $scheme.'://'.$host.$dir;
}

$errors=[];
$step=(int)($_POST['step'] ?? ($_GET['step'] ?? 1));

foreach (['storage','storage/cache','storage/logs','storage/backups','storage/updates','uploads','packages','themes'] as $omDir) {
    if (!is_dir(OMURGA_ROOT . '/' . $omDir)) @mkdir(OMURGA_ROOT . '/' . $omDir, 0775, true);
}
$requirements = [
    'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'JSON' => extension_loaded('json'),
    'Mbstring' => extension_loaded('mbstring'),
    'ZipArchive' => class_exists('ZipArchive'),
    'GD / WebP desteği' => function_exists('imagewebp'),
    'uploads yazılabilir' => is_writable(OMURGA_ROOT . '/uploads'),
    'storage/cache yazılabilir' => is_writable(OMURGA_ROOT . '/storage/cache'),
    'storage/logs yazılabilir' => is_writable(OMURGA_ROOT . '/storage/logs'),
    'packages yazılabilir' => is_writable(OMURGA_ROOT . '/packages'),
    'themes yazılabilir' => is_writable(OMURGA_ROOT . '/themes'),
];

if ($_SERVER['REQUEST_METHOD']==='POST' && $step===2) {
    if (in_array(false, $requirements, true)) {
        $errors[]='Sunucu kontrolünde eksikler var. Eksikleri düzeltmeden devam edilemez.';
        $step=1;
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $step===3) {
    $dbHost=req('db_host','localhost'); $dbName=req('db_name'); $dbUser=req('db_user'); $dbPass=req('db_pass'); $prefix=req('db_prefix','omg_');
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) $errors[]='Tablo ön eki sadece harf, rakam ve alt çizgi içermeli.';
    try {
        $pdo=new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $_SESSION['install_db']=['host'=>$dbHost,'name'=>$dbName,'user'=>$dbUser,'pass'=>$dbPass,'prefix'=>$prefix,'charset'=>'utf8mb4'];
    } catch(Throwable $e) {
        $errors[]='Veritabanı bağlantısı kurulamadı: '.$e->getMessage();
    }
    if ($errors) $step=2;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $step===4) {
    $_SESSION['install_site']=[
        'site_name'=>trim(req('site_name','Omurga Site')),
        'site_type'=>in_array(req('site_type','haber'), ['haber','kurumsal','topluluk','bos'], true) ? req('site_type','haber') : 'haber',
        'app_url'=>rtrim(req('app_url', base_url_guess()),'/'),
        'timezone'=>req('timezone','Europe/Istanbul'),
        'admin_language'=>in_array(req('admin_language','tr'), ['tr','en'], true) ? req('admin_language','tr') : 'tr',
        'site_language'=>in_array(req('site_language', req('admin_language','tr')), ['tr','en'], true) ? req('site_language', req('admin_language','tr')) : 'tr'
    ];
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $step===5) {
    $_SESSION['install_admin']=[
        'name'=>trim(req('admin_name')),
        'email'=>trim(req('admin_email')),
        'username'=>trim(req('admin_username','admin')),
        'password'=>req('admin_password')
    ];
    if (!$_SESSION['install_admin']['name'] || !filter_var($_SESSION['install_admin']['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($_SESSION['install_admin']['password']) < 6) {
        $errors[]='Yönetici adı, geçerli e-posta ve en az 6 karakter şifre gerekli.';
        $step=4;
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && $step===6) {
    $db=$_SESSION['install_db'] ?? null; $site=$_SESSION['install_site'] ?? null; $admin=$_SESSION['install_admin'] ?? null;
    if (!$db || !$site || !$admin) { $errors[]='Kurulum bilgileri eksik.'; $step=1; }
    else {
        try {
            $pdo=new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $p=preg_replace('/[^a-zA-Z0-9_]/','',$db['prefix']);
            $schema=[];
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, username VARCHAR(80) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role VARCHAR(40) NOT NULL DEFAULT 'admin', status VARCHAR(20) NOT NULL DEFAULT 'active', last_login_at DATETIME NULL, last_login_ip VARCHAR(64) NULL, password_reset_token VARCHAR(128) NULL, password_reset_expires DATETIME NULL, password_changed_at DATETIME NULL, two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0, two_factor_secret VARCHAR(190) NULL, two_factor_recovery_codes MEDIUMTEXT NULL, force_password_change TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(180) NOT NULL UNIQUE, description TEXT NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}posts (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(220) NOT NULL, slug VARCHAR(240) NOT NULL UNIQUE, spot TEXT NULL, content MEDIUMTEXT NULL, editor_type VARCHAR(20) NOT NULL DEFAULT 'classic', content_blocks MEDIUMTEXT NULL, type VARCHAR(40) NOT NULL DEFAULT 'post', status VARCHAR(30) NOT NULL DEFAULT 'draft', category_id INT UNSIGNED NULL, featured_image VARCHAR(255) NULL, video_url VARCHAR(500) NULL, gallery_images MEDIUMTEXT NULL, social_image VARCHAR(255) NULL, sort_order INT NOT NULL DEFAULT 100, seo_title VARCHAR(220) NULL, meta_description VARCHAR(255) NULL, focus_keyword VARCHAR(160) NULL, social_title VARCHAR(220) NULL, social_description VARCHAR(255) NULL, canonical_url VARCHAR(500) NULL, seo_noindex TINYINT(1) NOT NULL DEFAULT 0, comments_enabled TINYINT(1) NOT NULL DEFAULT 0, design_template VARCHAR(80) NULL, author_id INT UNSIGNED NULL, published_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, deleted_at DATETIME NULL, INDEX(status), INDEX(type), INDEX(category_id), INDEX(deleted_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}tags (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}post_tags (post_id INT UNSIGNED NOT NULL, tag_id INT UNSIGNED NOT NULL, PRIMARY KEY(post_id, tag_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$schema[]="CREATE TABLE IF NOT EXISTS {$p}post_meta (post_id INT UNSIGNED NOT NULL, meta_key VARCHAR(120) NOT NULL, meta_value MEDIUMTEXT NULL, PRIMARY KEY(post_id, meta_key), INDEX(meta_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                        $schema[]="CREATE TABLE IF NOT EXISTS {$p}media (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, file_path VARCHAR(255) NOT NULL, file_name VARCHAR(180) NOT NULL, mime VARCHAR(120) NULL, alt_text VARCHAR(190) NULL, uploaded_by INT UNSIGNED NULL, original_path VARCHAR(255) NULL, width INT UNSIGNED NULL, height INT UNSIGNED NULL, file_size INT UNSIGNED NULL, filename VARCHAR(180) NULL, original_filename VARCHAR(220) NULL, mime_type VARCHAR(120) NULL, extension VARCHAR(20) NULL, size INT UNSIGNED NULL, path VARCHAR(255) NULL, url VARCHAR(500) NULL, title VARCHAR(220) NULL, caption TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}forms (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, form_type VARCHAR(60) NOT NULL DEFAULT 'contact', name VARCHAR(160) NOT NULL, phone VARCHAR(60) NULL, email VARCHAR(190) NULL, message TEXT NULL, status VARCHAR(40) NOT NULL DEFAULT 'new', meta MEDIUMTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(form_type), INDEX(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}form_definitions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, form_type VARCHAR(60) NOT NULL DEFAULT 'contact', description TEXT NULL, fields MEDIUMTEXT NULL, status VARCHAR(30) NOT NULL DEFAULT 'active', submit_label VARCHAR(80) NOT NULL DEFAULT 'Gönder', success_message VARCHAR(255) NOT NULL DEFAULT 'Başvurunuz alındı. En kısa sürede dönüş yapılacaktır.', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX(status), INDEX(form_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}comments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, post_id INT UNSIGNED NOT NULL, parent_id INT UNSIGNED NULL, author_name VARCHAR(120) NOT NULL, author_email VARCHAR(190) NOT NULL, author_ip VARCHAR(64) NULL, content TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', user_id INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX(post_id), INDEX(parent_id), INDEX(status), INDEX(author_ip), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}password_resets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, email VARCHAR(190) NOT NULL, token_hash VARCHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip_address VARCHAR(64) NULL, user_agent VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(email), INDEX(expires_at), INDEX(used_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}settings (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(120) NOT NULL UNIQUE, setting_value MEDIUMTEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}activity_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(120) NOT NULL, module VARCHAR(80) NULL, entity_type VARCHAR(80) NULL, entity_id INT UNSIGNED NULL, level VARCHAR(20) NOT NULL DEFAULT 'info', message TEXT NULL, details LONGTEXT NULL, ip VARCHAR(64) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(action), INDEX(module), INDEX(level), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}post_autosaves (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, autosave_key VARCHAR(120) NOT NULL, post_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL, content_type VARCHAR(40) NOT NULL DEFAULT 'post', payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_autosave_user_key (user_id, autosave_key), INDEX(post_id), INDEX(user_id), INDEX(updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}backups (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, backup_type VARCHAR(40) NOT NULL, file_path VARCHAR(255) NOT NULL, file_size INT UNSIGNED NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $schema[]="CREATE TABLE IF NOT EXISTS {$p}update_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, from_version VARCHAR(30) NULL, to_version VARCHAR(30) NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', message TEXT NULL, package_name VARCHAR(190) NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            foreach($schema as $sql) $pdo->exec($sql);

            $stmt=$pdo->prepare("INSERT INTO {$p}users (name,email,username,password,role) VALUES (?,?,?,?, 'super_admin')");
            $stmt->execute([$admin['name'],$admin['email'],$admin['username'],password_hash($admin['password'], PASSWORD_DEFAULT)]);
            $userId=(int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['site_name',$site['site_name']]);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['site_type',$site['site_type']]);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['admin_language',$site['admin_language'] ?? 'tr']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['site_language',$site['site_language'] ?? ($site['admin_language'] ?? 'tr')]);
            $activeTheme = ['haber'=>'haber-v1','kurumsal'=>'kurumsal-v1','topluluk'=>'topluluk-v1','bos'=>'haber-v1'][$site['site_type']] ?? 'haber-v1';
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['active_theme',$activeTheme]);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['webp_quality','82']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['robots_index','1']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['omurga_version','1.2.0-rc.1']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['db_version','1.2.0-rc.1']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['autosave_enabled','1']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['autosave_interval_seconds','30']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['schema_version','4.0.0']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['maintenance_mode','0']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['maintenance_message','Sitemiz kısa süreli bakımda. Lütfen daha sonra tekrar deneyin.']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['membership_registration_enabled','0']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['membership_default_role','member']);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['membership_default_status','pending']);
            $profilePresets = [
                'haber'=>[
                    'description'=>'Güncel içerikler, kategoriler ve yayın akışı için haber profili.',
                    'menus'=>['Gündem'=>'#icerikler','Yerel'=>'#icerikler','Spor'=>'#icerikler','İletişim'=>'#form'],
                    'theme'=>['primary_color'=>'#f97316','secondary_color'=>'#071323','header_type'=>'news','show_topbar'=>'1','footer_type'=>'dark','site_width'=>'wide','card_radius'=>'8','footer_text'=>'Omurga haber profili ile hazırlanmıştır.'],
                    'layout'=>'news'
                ],
                'kurumsal'=>[
                    'description'=>'Hizmetlerinizi, projelerinizi ve iletişim taleplerinizi yöneten kurumsal web sitesi.',
                    'menus'=>['Hizmetler'=>'/kategori/hizmetler','Projeler'=>'/kategori/projeler','Teklif Al'=>'#form','İletişim'=>'/iletisim'],
                    'theme'=>['primary_color'=>'#f97316','secondary_color'=>'#0f172a','header_type'=>'classic','show_topbar'=>'1','footer_type'=>'dark','site_width'=>'wide','card_radius'=>'18','footer_text'=>'Omurga Kurumsal profili ile hazırlanmıştır.'],
                    'layout'=>'corporate'
                ],
                'topluluk'=>[
                    'description'=>'Duyuru, etkinlik, proje ve üyelik başvurularını yöneten topluluk sitesi.',
                    'menus'=>['Duyurular'=>'/kategori/duyurular','Etkinlikler'=>'/kategori/etkinlikler','Projeler'=>'/kategori/projeler','Yönetim Kurulu'=>'/yonetim-kurulu','Üyelik'=>'#form'],
                    'theme'=>['primary_color'=>'#16a34a','secondary_color'=>'#10261a','header_type'=>'centered','show_topbar'=>'1','footer_type'=>'dark','site_width'=>'wide','card_radius'=>'20','footer_text'=>'Omurga Topluluk profili ile hazırlanmıştır.'],
                    'layout'=>'community'
                ],
                'bos'=>[
                    'description'=>'Demo içerik eklenmemiş sade Omurga kurulumu.',
                    'menus'=>[],
                    'theme'=>['primary_color'=>'#f97316','secondary_color'=>'#0f172a','header_type'=>'classic','show_topbar'=>'0','footer_type'=>'dark','site_width'=>'wide','card_radius'=>'14','footer_text'=>'Omurga ile hazırlanmıştır.'],
                    'layout'=>'blank'
                ],
            ];
            $preset = $profilePresets[$site['site_type']] ?? $profilePresets['haber'];
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['site_description',$preset['description']]);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['site_profile_layout',$preset['layout']]);
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['theme_settings_'.$activeTheme,json_encode($preset['theme'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)]);
            $profileMenu=[]; $profileMenu[]=['id'=>1,'title'=>'Anasayfa','url'=>$site['app_url'],'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10];
            $menuSort=20; foreach(($preset['menus'] ?? []) as $mt=>$mu){ $url=(str_starts_with($mu,'/') ? rtrim($site['app_url'],'/').$mu : $site['app_url'].$mu); $profileMenu[]=['id'=>count($profileMenu)+1,'title'=>$mt,'url'=>$url,'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>$menuSort]; $menuSort+=10; }
            $defaultMenus=[
                'menu_main'=>json_encode($profileMenu, JSON_UNESCAPED_UNICODE),
                'menu_mobile'=>json_encode(array_slice($profileMenu,0,4), JSON_UNESCAPED_UNICODE),
                'menu_footer'=>json_encode($profileMenu, JSON_UNESCAPED_UNICODE),
                'menu_top'=>json_encode(array_slice($profileMenu,1,3), JSON_UNESCAPED_UNICODE),
            ];
            foreach($defaultMenus as $mk=>$mv){ $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$mk,$mv]); }
            $defaultAds=[]; foreach(['header'=>'Header Alanı','home_top'=>'Anasayfa Üstü','content_inside'=>'İçerik İçi','sidebar'=>'Sidebar','mobile_fixed'=>'Mobil Sabit','footer'=>'Footer'] as $ak=>$al){ $defaultAds[$ak]=['enabled'=>0,'title'=>$al,'type'=>'image','image'=>'','link'=>'','html'=>'','target'=>'_blank','show_mobile'=>1,'show_desktop'=>1]; }
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['ad_slots',json_encode($defaultAds, JSON_UNESCAPED_UNICODE)]);
            $defaultBlocks = [
                'kurumsal'=>[
                    ['key'=>'hero','title'=>'Hero Alanı','enabled'=>1,'sort'=>10,'limit'=>1,'source'=>'category:hizmetler','mobile'=>1],
                    ['key'=>'services','title'=>'Hizmetler','enabled'=>1,'sort'=>20,'limit'=>6,'source'=>'category:hizmetler','mobile'=>1],
                    ['key'=>'portfolio','title'=>'Portföy / Projeler','enabled'=>1,'sort'=>30,'limit'=>6,'source'=>'category:projeler','mobile'=>1],
                    ['key'=>'quote','title'=>'Teklif Formu','enabled'=>1,'sort'=>40,'limit'=>1,'source'=>'form','mobile'=>1],
                ],
                'topluluk'=>[
                    ['key'=>'hero','title'=>'Hero Alanı','enabled'=>1,'sort'=>10,'limit'=>1,'source'=>'category:duyurular','mobile'=>1],
                    ['key'=>'announcements','title'=>'Duyurular','enabled'=>1,'sort'=>20,'limit'=>6,'source'=>'category:duyurular','mobile'=>1],
                    ['key'=>'events','title'=>'Etkinlikler','enabled'=>1,'sort'=>30,'limit'=>6,'source'=>'category:etkinlikler','mobile'=>1],
                    ['key'=>'projects','title'=>'Projeler','enabled'=>1,'sort'=>40,'limit'=>6,'source'=>'category:projeler','mobile'=>1],
                    ['key'=>'board','title'=>'Yönetim Kurulu','enabled'=>1,'sort'=>50,'limit'=>8,'source'=>'page:yonetim-kurulu','mobile'=>1],
                    ['key'=>'membership','title'=>'Üyelik Başvurusu','enabled'=>1,'sort'=>60,'limit'=>1,'source'=>'form','mobile'=>1],
                ],
                'haber'=>[
                    ['key'=>'latest','title'=>'Son İçerikler','enabled'=>1,'sort'=>40,'limit'=>12,'source'=>'latest','mobile'=>1],
                    ['key'=>'ad-home','title'=>'Reklam Alanı','enabled'=>0,'sort'=>50,'limit'=>1,'source'=>'ad_home','mobile'=>1],
                ],
            ];
            $menuItems=[]; foreach($profileMenu as $mi){ $menuItems[]=['title'=>$mi['title'],'url'=>$mi['url'],'sort'=>$mi['sort']]; }
            $moreSettings=[
                'primary_color'=>$preset['theme']['primary_color'],'secondary_color'=>$preset['theme']['secondary_color'],'logo_text'=>$site['site_name'],'footer_text'=>$preset['theme']['footer_text'],
                'home_blocks'=>json_encode($defaultBlocks[$site['site_type']] ?? [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),
                'main_menu'=>json_encode($menuItems, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)
            ];
            foreach($moreSettings as $sk=>$sv){ $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$sk,$sv]); }
            $globalLayout=[
                'home_top'=>[],
                'home_main'=>[
                    ['id'=>'install_latest','slug'=>'latest-content','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['limit'=>6]],
                ],
                'sidebar'=>[
                    ['id'=>'install_search','slug'=>'search','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>[]],
                ],
                'post_inside'=>[
                    ['id'=>'install_comments','slug'=>'comments','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>[]],
                ],
                'header'=>[],
                'footer'=>[],
            ];
            $pdo->prepare("INSERT INTO {$p}settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['layout_global',json_encode($globalLayout, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)]);


            $defaults = [
                'haber'=>['Gündem','Yerel','Siyaset','Ekonomi','Spor','Asayiş','Kültür Sanat'],
                'kurumsal'=>['Hizmetler','Projeler','Blog','Duyurular'],
                'topluluk'=>['Duyurular','Etkinlikler','Projeler','Haberler','Belgeler'],
                'bos'=>['Genel'],
            ];
            $cats=$defaults[$site['site_type']] ?? [];
            $catStmt=$pdo->prepare("INSERT IGNORE INTO {$p}categories (name,slug,sort_order) VALUES (?,?,?)");
            foreach($cats as $i=>$cat) $catStmt->execute([$cat, slugify_install($cat), $i+1]);
            $firstCat=(int)$pdo->query("SELECT id FROM {$p}categories ORDER BY sort_order,id LIMIT 1")->fetchColumn();
            $catMap=[];
            $catRows=$pdo->query("SELECT id,name FROM {$p}categories")->fetchAll(PDO::FETCH_ASSOC);
            foreach($catRows as $cr) $catMap[$cr['name']] = (int)$cr['id'];
            if(!$firstCat){
                $catStmt->execute(['Genel','genel',1]);
                $firstCat=(int)$pdo->lastInsertId();
            }
            $demoTitle = 'Omurga CMS ile İlk Yazınız';
            $demoSpot = 'Omurga CMS 1.0.7.5 Beta kurulumundan sonra gelen örnek başlangıç yazısı.';
            $demoContent = '<p>Bu örnek yazı, Omurga CMS kurulumundan sonra sitenin boş görünmemesi için eklenir.</p><p>Panelden bu yazıyı düzenleyebilir, silebilir veya kendi içeriklerinizle değiştirebilirsiniz.</p>';
            $defaultCommentsEnabled = 1;
            $postStmt=$pdo->prepare("INSERT INTO {$p}posts (title,slug,spot,content,type,status,category_id,sort_order,author_id,published_at,seo_title,meta_description,comments_enabled) VALUES (?,?,?,?,?,'published',?,?,?,NOW(),?,?,?)");
            $postStmt->execute([$demoTitle, slugify_install($demoTitle), $demoSpot, $demoContent, 'post', $firstCat, 10, $userId, $demoTitle, mb_substr($demoSpot,0,250), $defaultCommentsEnabled]);
            $demoPostId=(int)$pdo->lastInsertId();
            $commentStmt=$pdo->prepare("INSERT INTO {$p}comments (post_id,author_name,author_email,author_ip,content,status,user_id) VALUES (?,?,?,?,?,'approved',NULL)");
            $commentStmt->execute([$demoPostId,'Omurga Ekibi','merhaba@omurgacms.com',$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1','Omurga CMS 1.0.7.5 Beta sürümüne hoş geldiniz. Bu örnek yorum panelde yorum yönetimini test etmek için eklenmiştir.']);

            $defaultFormFields=json_encode([
                ['key'=>'name','label'=>'Ad Soyad','type'=>'text','required'=>1,'placeholder'=>'Adınız ve soyadınız'],
                ['key'=>'phone','label'=>'Telefon','type'=>'tel','required'=>0,'placeholder'=>'Telefon numaranız'],
                ['key'=>'email','label'=>'E-posta','type'=>'email','required'=>0,'placeholder'=>'E-posta adresiniz'],
                ['key'=>'message','label'=>'Mesaj','type'=>'textarea','required'=>0,'placeholder'=>'Mesajınız'],
            ], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("INSERT IGNORE INTO {$p}form_definitions (title,slug,form_type,description,fields,status,submit_label,success_message) VALUES (?,?,?,?,?,?,?,?)")->execute(['İletişim Formu','iletisim-formu','contact','Varsayılan iletişim formu',$defaultFormFields,'active','Gönder','Mesajınız alındı. En kısa sürede dönüş yapılacaktır.']);
            $formSeed=$pdo->prepare("INSERT INTO {$p}forms (form_type,name,phone,email,message,status,meta) VALUES (?,?,?,?,?,'new',?)");
            if($site['site_type']==='kurumsal'){
                $formSeed->execute(['quote','Örnek Müşteri','0555 000 00 00','musteri@example.com','Boya ve tadilat için teklif almak istiyorum.',json_encode(['demo'=>true],JSON_UNESCAPED_UNICODE)]);
            } elseif($site['site_type']==='topluluk'){
                $formSeed->execute(['membership','Örnek Üye Adayı','0555 000 00 00','uye@example.com','Platform üyeliği hakkında bilgi almak istiyorum.',json_encode(['demo'=>true],JSON_UNESCAPED_UNICODE)]);
            }

            $config="<?php\nreturn " . var_export([
                'installed'=>true,
                'app_name'=>'Omurga',
                'app_url'=>$site['app_url'],
                'db'=>$db,
            ], true) . ";\n";
            file_put_contents(OMURGA_ROOT.'/config.php', $config);
            $pdo->prepare("INSERT INTO {$p}activity_logs (user_id,action,message,ip) VALUES (?,?,?,?)")->execute([$userId,'install.complete','Omurga kurulumu tamamlandı',$_SERVER['REMOTE_ADDR'] ?? '']);
            file_put_contents(OMURGA_ROOT.'/storage/installed.lock', 'installed '.date('c'));
            session_destroy();
            header('Location: ../admin/login.php?installed=1'); exit;
        } catch(Throwable $e) {
            $errors[]='Kurulum tamamlanamadı: '.$e->getMessage();
            $step=5;
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Omurga Kurulum</title>
<link rel="icon" type="image/png" href="../assets/images/omurga-icon.png">
<link rel="apple-touch-icon" href="../assets/images/omurga-icon.png">
<link rel="stylesheet" href="../assets/css/omurga.css?v=1.2.0-rc.1">
<style>
:root{--om-dark:#0f172a;--om-dark2:#162033;--om-orange:#f97316;--om-orange2:#fb923c;--om-soft:#fff7ed;--om-line:#e5e7eb;--om-muted:#64748b;--om-green:#16a34a;--om-red:#dc2626}
.install-body{min-height:100vh;display:block;padding:0;background:radial-gradient(circle at 14% 12%,rgba(249,115,22,.35),transparent 25%),radial-gradient(circle at 86% 18%,rgba(59,130,246,.22),transparent 28%),linear-gradient(135deg,#0b1220,#111827 48%,#1e293b);color:#0f172a}
.install-page{min-height:100vh;display:grid;grid-template-columns:380px minmax(0,1fr);gap:28px;width:min(1180px,calc(100% - 36px));margin:0 auto;padding:32px 0;align-items:stretch}
.install-side{position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:30px;background:linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.04));box-shadow:0 30px 90px rgba(0,0,0,.35);padding:28px;color:#fff;display:flex;flex-direction:column;justify-content:space-between;min-height:680px}
.install-side:before{content:"";position:absolute;inset:auto -90px -110px auto;width:260px;height:260px;border-radius:999px;background:rgba(249,115,22,.35);filter:blur(4px)}
.install-logo{display:flex;align-items:center;gap:14px;position:relative;z-index:1}.install-logo img{width:74px;height:auto;display:block}.install-logo-mark{width:74px;height:74px;border-radius:22px;background:linear-gradient(135deg,var(--om-orange2),var(--om-orange));display:grid;place-items:center;font-size:34px;font-weight:950;box-shadow:0 18px 50px rgba(249,115,22,.38)}
.install-logo h1{margin:0;font-size:28px;letter-spacing:-.03em}.install-logo p{margin:4px 0 0;color:#cbd5e1;font-weight:700}.install-intro{position:relative;z-index:1}.install-intro h2{font-size:34px;line-height:1.08;margin:34px 0 12px;letter-spacing:-.04em}.install-intro p{color:#cbd5e1;line-height:1.65;margin:0;font-weight:600}.install-profile-mini{position:relative;z-index:1;display:grid;gap:10px;margin-top:24px}.install-profile-mini div{display:flex;justify-content:space-between;gap:10px;border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:13px 14px;background:rgba(15,23,42,.28);backdrop-filter:blur(8px)}.install-profile-mini b{font-size:14px}.install-profile-mini span{font-size:12px;color:#cbd5e1;font-weight:700;text-align:right}.install-note{position:relative;z-index:1;border-radius:18px;background:rgba(15,23,42,.42);border:1px solid rgba(255,255,255,.12);padding:16px;color:#ffedd5;font-weight:700;line-height:1.45}
.install-card{width:100%;background:rgba(255,255,255,.98);border:1px solid rgba(255,255,255,.65);border-radius:30px;padding:0;box-shadow:0 30px 90px rgba(0,0,0,.25);overflow:hidden}.install-card-inner{padding:28px}.install-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;padding:24px 28px;border-bottom:1px solid var(--om-line);background:linear-gradient(180deg,#fff,#f8fafc)}.install-head h2{margin:0;font-size:25px;letter-spacing:-.03em}.install-head p{margin:7px 0 0;color:var(--om-muted);font-weight:700}.step-badge{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;border-radius:999px;background:var(--om-soft);color:#9a3412;border:1px solid #fed7aa;padding:9px 13px;font-weight:900;font-size:13px}.progress{height:8px;background:#e2e8f0}.progress span{display:block;height:100%;background:linear-gradient(90deg,var(--om-orange2),var(--om-orange));border-radius:0 999px 999px 0;transition:.2s ease}.steps{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:24px}.step-chip{display:flex;align-items:center;gap:9px;border:1px solid var(--om-line);border-radius:14px;padding:10px;background:#fff;color:#64748b;font-weight:800;font-size:13px}.step-chip i{width:24px;height:24px;border-radius:9px;display:grid;place-items:center;background:#f1f5f9;font-style:normal;font-size:12px}.step-chip.done{border-color:#fed7aa;background:#fff7ed;color:#9a3412}.step-chip.done i,.step-chip.active i{background:var(--om-orange);color:#fff}.step-chip.active{border-color:#fdba74;box-shadow:0 12px 28px rgba(249,115,22,.12);color:#0f172a}.alert.error{border:1px solid #fecaca;border-radius:16px;background:#fef2f2;color:#991b1b;font-weight:700}.check-list{display:grid;gap:10px;border:0;margin:0 0 22px}.check-row{display:flex;justify-content:space-between;align-items:center;gap:16px;border:1px solid var(--om-line);border-radius:15px;padding:14px 15px;background:#fff}.check-row strong{border-radius:999px;padding:6px 10px;font-size:12px}.check-row strong.ok{background:#dcfce7;color:#166534}.check-row strong.bad{background:#fee2e2;color:#991b1b}.install-form{display:grid;gap:14px}.field-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.install-form label{margin:0;display:grid;gap:7px;font-weight:900;color:#0f172a}.install-form small{display:block;color:#64748b;font-weight:700;margin-top:-2px}.install-form input{border:1px solid #e5e7eb;border-radius:14px;padding:13px 14px;background:#fff;font:inherit}.install-form input:focus{outline:3px solid #ffedd5;border-color:#fb923c}.type-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin:8px 0 4px}.type-grid label{position:relative;border:1px solid #e2e8f0;border-radius:20px;padding:16px 16px 16px 48px;background:#fff;cursor:pointer;display:block;min-height:122px}.type-grid label:hover{border-color:#fdba74;background:#fffaf5}.type-grid input{position:absolute;left:16px;top:18px;width:18px;height:18px;accent-color:#f97316}.type-grid input:checked+span{color:#9a3412}.type-grid b{display:block;margin:0 0 7px;font-size:17px}.type-grid span{display:block;color:var(--om-muted);font-weight:700;font-size:13px;line-height:1.45}.profile-icon{position:absolute;right:15px;top:15px;width:36px;height:36px;border-radius:13px;background:#f8fafc;display:grid;place-items:center;font-size:18px}.type-grid input:checked~.profile-icon{background:#ffedd5}.summary-box{display:grid;gap:12px;margin:18px 0}.summary-row{border:1px solid var(--om-line);border-radius:16px;background:#fff;padding:14px 15px;display:flex;align-items:center;justify-content:space-between;gap:16px}.summary-row span{color:#64748b;font-weight:800}.summary-row b{text-align:right}.btn-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:22px}.btn,button{border-radius:14px;padding:12px 18px}.btn.primary,button.primary{background:linear-gradient(135deg,#fb923c,#f97316);box-shadow:0 14px 30px rgba(249,115,22,.22);color:#fff}.btn.light{background:#fff;border:1px solid var(--om-line);color:#334155}.ghost-link{display:inline-flex;align-items:center;gap:7px;color:#64748b;font-weight:900}.install-help{margin-top:16px;border:1px dashed #fed7aa;background:#fff7ed;color:#9a3412;border-radius:17px;padding:14px 15px;font-weight:800;line-height:1.45}.mini-list{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin:16px 0}.mini-list div{border:1px solid #e2e8f0;border-radius:15px;padding:12px;background:#f8fafc}.mini-list b{display:block;margin-bottom:4px}.mini-list span{color:#64748b;font-size:13px;font-weight:700}
@media(max-width:980px){.install-page{grid-template-columns:1fr;padding:18px 0}.install-side{min-height:auto}.install-intro h2{font-size:28px}.steps{grid-template-columns:1fr 1fr}.field-grid,.type-grid,.mini-list{grid-template-columns:1fr}.install-head{display:block}.step-badge{margin-top:14px}.install-card-inner{padding:20px}.install-head{padding:22px}.btn-row{flex-direction:column-reverse;align-items:stretch}.btn-row .btn,.btn-row button{justify-content:center;width:100%}}
</style>
</head>
<body class="install-body">
<?php
$stepTitles = [1=>'Sunucu Kontrolü',2=>'Veritabanı',3=>'Site Profili',4=>'Yönetici',5=>'Tamamla'];
$percent = min(100, max(0, ($step / 5) * 100));
function step_class_install($current,$target){ if($current===$target) return 'active'; return $current>$target ? 'done' : ''; }
?>
<div class="install-page">
  <aside class="install-side">
    <div>
      <div class="install-logo">
        <?php if(file_exists(OMURGA_ROOT.'/assets/images/omurga-logo.png')): ?>
          <img src="../assets/images/omurga-logo.png" alt="Omurga">
        <?php else: ?>
          <div class="install-logo-mark">O</div>
        <?php endif; ?>
        <div><h1>Omurga</h1><p>CMS Kurulum Sihirbazı</p></div>
      </div>
      <div class="install-intro">
        <h2>Siteyi birkaç adımda hazır hale getir.</h2>
        <p>Haber, kurumsal, topluluk veya boş profil seç; Omurga gerekli panel dilini, URL yapısını ve hazır düzeni buna göre başlatsın.</p>
      </div>
      <div class="install-profile-mini">
        <div><b>Haber</b><span>Hızlı yayın</span></div>
        <div><b>Kurumsal</b><span>Firma / hizmet</span></div>
        <div><b>Topluluk</b><span>Dernek / platform</span></div>
        <div><b>Boş</b><span>Sade başlangıç</span></div>
      </div>
    </div>
    <div class="install-note">Çekirdek temiz kalır; profil başlangıç dili, örnek içerik ve hazır düzeni belirler.</div>
  </aside>

  <main class="install-card">
    <div class="install-head">
      <div><h2><?=e($stepTitles[$step] ?? 'Kurulum')?></h2><p>Omurga kurulumu güvenli ve sade adımlarla tamamlanır.</p></div>
      <span class="step-badge">Adım <?= (int)$step ?> / 5</span>
    </div>
    <div class="progress"><span style="width:<?=$percent?>%"></span></div>
    <div class="install-card-inner">
      <div class="steps">
        <div class="step-chip <?=step_class_install($step,1)?>"><i>1</i>Kontrol</div>
        <div class="step-chip <?=step_class_install($step,2)?>"><i>2</i>Veritabanı</div>
        <div class="step-chip <?=step_class_install($step,3)?>"><i>3</i>Profil</div>
        <div class="step-chip <?=step_class_install($step,4)?>"><i>4</i>Yönetici</div>
        <div class="step-chip <?=step_class_install($step,5)?>"><i>5</i>Kurulum</div>
      </div>
      <?php if($errors): ?><div class="alert error"><?php foreach($errors as $er) echo '<p>'.e($er).'</p>'; ?></div><?php endif; ?>

<?php if($step===1): ?>
      <div class="check-list"><?php foreach($requirements as $label=>$ok): ?><div class="check-row"><span><?=e($label)?></span><strong class="<?= $ok?'ok':'bad' ?>"><?= $ok?'Uygun':'Eksik' ?></strong></div><?php endforeach; ?></div>
      <div class="install-help">Eksik görünen alan varsa hosting panelinden PHP eklentilerini ve klasör yazma izinlerini kontrol et.</div>
      <form method="post" class="install-form"><input type="hidden" name="step" value="2"><div class="btn-row"><span class="ghost-link">Hazırsa devam et</span><button class="btn primary">Devam Et</button></div></form>
<?php elseif($step===2): ?>
      <form method="post" class="install-form"><input type="hidden" name="step" value="3">
        <div class="field-grid"><label>Sunucu<input name="db_host" value="<?=e(req('db_host','localhost'))?>"><small>Genelde localhost olur.</small></label><label>Veritabanı Adı<input name="db_name" required value="<?=e(req('db_name'))?>"></label></div>
        <div class="field-grid"><label>Kullanıcı Adı<input name="db_user" required value="<?=e(req('db_user'))?>"></label><label>Şifre<input type="password" name="db_pass" value="<?=e(req('db_pass'))?>"></label></div>
        <label>Tablo Ön Eki<input name="db_prefix" value="<?=e(req('db_prefix','omg_'))?>"><small>Aynı veritabanında birden fazla kurulum için değiştirilebilir.</small></label>
        <div class="btn-row"><a class="btn light" href="?step=1">Geri</a><button class="btn primary">Bağlantıyı Test Et ve Devam Et</button></div>
      </form>
<?php elseif($step===3): ?>
      <form method="post" class="install-form"><input type="hidden" name="step" value="4">
        <div class="field-grid"><label>Site Adı<input name="site_name" required value="<?=e(req('site_name','Omurga Site'))?>"></label><label>Site URL<input name="app_url" value="<?=e(base_url_guess())?>"></label></div>
        <label>Zaman Dilimi<input name="timezone" value="Europe/Istanbul"></label>
        <div class="field-grid">
          <label>Panel Dili<select name="admin_language"><option value="tr">Türkçe</option><option value="en">English</option></select><small>Yönetim paneli, butonlar ve sistem mesajları.</small></label>
          <label>Site Dili<select name="site_language"><option value="tr">Türkçe</option><option value="en">English</option></select><small>Ön yüzde tema metinleri için varsayılan dil.</small></label>
        </div>
        <div class="type-grid">
<label><input type="radio" name="site_type" value="haber" checked><span><b>Haber</b>Haber, kategori ve yayın akışı.</span><em class="profile-icon">📰</em></label>
          <label><input type="radio" name="site_type" value="kurumsal"><span><b>Kurumsal</b>Firma, hizmet, proje ve teklif odaklı yapı.</span><em class="profile-icon">🏢</em></label>
          <label><input type="radio" name="site_type" value="topluluk"><span><b>Topluluk</b>Dernek, platform, etkinlik ve duyuru yapısı.</span><em class="profile-icon">🤝</em></label>
          <label><input type="radio" name="site_type" value="bos"><span><b>Boş</b>Sade başlangıç; yine de 1 örnek yazı ve 1 örnek yorum eklenir.</span><em class="profile-icon">○</em></label>
        </div>
        <div class="btn-row"><a class="btn light" href="?step=2">Geri</a><button class="btn primary">Devam Et</button></div>
      </form>
<?php elseif($step===4): ?>
      <form method="post" class="install-form"><input type="hidden" name="step" value="5">
        <div class="field-grid"><label>Ad Soyad<input name="admin_name" required></label><label>E-posta<input type="email" name="admin_email" required></label></div>
        <div class="field-grid"><label>Kullanıcı Adı<input name="admin_username" value="admin" required></label><label>Şifre<input type="password" name="admin_password" required minlength="6"><small>En az 6 karakter olmalı.</small></label></div>
        <div class="btn-row"><a class="btn light" href="?step=3">Geri</a><button class="btn primary">Kuruluma Hazırla</button></div>
      </form>
<?php elseif($step===5): ?>
      <div class="summary-box">
        <div class="summary-row"><span>Veritabanı tabloları</span><b>Oluşturulacak</b></div>
        <div class="summary-row"><span>Yönetici hesabı</span><b>Tanımlanacak</b></div>
        <div class="summary-row"><span>Profil ayarları</span><b>Uygulanacak</b></div>
        <div class="summary-row"><span>Boş profil değilse</span><b>Demo içerik eklenecek</b></div>
      </div>
      <div class="mini-list"><div><b>Güvenli başlangıç</b><span>Mevcut kurulum varsa işlem başlamaz.</span></div><div><b>Temiz çekirdek</b><span>Profil, çekirdeği haber odaklı yapmaz.</span></div></div>
      <form method="post" class="install-form"><input type="hidden" name="step" value="6"><div class="btn-row"><a class="btn light" href="?step=4">Geri</a><button class="btn primary">Kurulumu Tamamla</button></div></form>
<?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
