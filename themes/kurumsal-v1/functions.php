<?php
if (!defined('OMURGA_ROOT')) { exit; }

function kv1_setting(string $key, $default='') { return function_exists('om_theme_setting') ? om_theme_setting($key, $default, 'kurumsal-v1') : $default; }
function kv1_bool(string $key, bool $default=false): bool { return function_exists('om_theme_setting_bool') ? om_theme_setting_bool($key, $default, 'kurumsal-v1') : $default; }
function kv1_asset(string $path): string { return function_exists('omurga_theme_url') ? omurga_theme_url(ltrim($path,'/'), 'kurumsal-v1') : ''; }
function kv1_color(string $key, string $default): string { $v=(string)kv1_setting($key,$default); return preg_match('/^#[0-9a-fA-F]{6}$/',$v)?$v:$default; }
function kv1_logo_text(): string { $main=trim((string)kv1_setting('site_label','Kurumsal V1')); return $main!=='' ? $main : setting('site_name','Kurumsal V1'); }
function kv1_logo_accent(): string { return trim((string)kv1_setting('site_label_accent','Studio')); }
function kv1_text(string $key, string $default=''): string { $v=trim((string)kv1_setting($key,$default)); return $v!==''?$v:$default; }
function kv1_image(int $no=1): string { return kv1_asset('assets/images/corporate-'.$no.'.svg'); }
function kv1_render_content(string $html): string { return function_exists('omurga_render_post_content') ? omurga_render_post_content(['content'=>$html]) : $html; }
function kv1_excerpt(string $text, int $len=130): string { return function_exists('excerpt') ? excerpt($text,$len) : mb_substr(strip_tags($text),0,$len); }

function kv1_posts(string $categorySlug='', int $limit=6): array {
    try{
        $posts=table_name('posts'); $cats=table_name('categories');
        $params=[]; $where="p.status='published' AND p.type<>'page'";
        if($categorySlug!==''){ $where.=" AND c.slug=?"; $params[]=$categorySlug; }
        $sql="SELECT p.*, c.name category_name, c.slug category_slug FROM $posts p LEFT JOIN $cats c ON c.id=p.category_id WHERE $where ORDER BY p.sort_order ASC, COALESCE(p.published_at,p.created_at) DESC, p.id DESC LIMIT ".max(1,$limit);
        $st=db()->prepare($sql); $st->execute($params); return $st->fetchAll();
    }catch(Throwable $e){ if(function_exists('omurga_write_error')) omurga_write_error($e); return []; }
}
function kv1_pages(int $limit=4): array {
    try{ $posts=table_name('posts'); $st=db()->query("SELECT * FROM $posts WHERE status='published' AND type='page' ORDER BY sort_order ASC,id ASC LIMIT ".max(1,$limit)); return $st->fetchAll(); }catch(Throwable $e){ return []; }
}
function kv1_default_services(): array {
    return [
        ['title'=>'Web Tasarımı','spot'=>'Markanızı yansıtan, hızlı, mobil uyumlu ve modern web siteleri hazırlıyoruz.','icon'=>'◈'],
        ['title'=>'Kurumsal Kimlik','spot'=>'Logo, renk paleti, tipografi ve görsel dili tek bir güçlü marka çizgisinde topluyoruz.','icon'=>'⬡'],
        ['title'=>'UI / UX Tasarım','spot'=>'Kullanıcının kolay anlayacağı, sade ve etkili arayüz deneyimleri tasarlıyoruz.','icon'=>'◎'],
        ['title'=>'Dijital Danışmanlık','spot'=>'Web, içerik ve dijital görünüm tarafında işletmenize yol haritası çıkarıyoruz.','icon'=>'▷'],
    ];
}
function kv1_default_projects(): array {
    return [
        ['title'=>'Kurumsal Web Sitesi','spot'=>'Hizmet firması için modern kurumsal web arayüzü.','cat'=>'Web Tasarımı','img'=>1],
        ['title'=>'Marka Kimliği','spot'=>'Yeni nesil marka dili ve kurumsal kimlik sistemi.','cat'=>'Marka Kimliği','img'=>2],
        ['title'=>'Dashboard Arayüzü','spot'=>'Yönetim paneli ve kullanıcı deneyimi tasarımı.','cat'=>'UI / UX','img'=>3],
    ];
}
function kv1_service_cards(array $services=[]): array {
    if(!$services) return kv1_default_services();
    $out=[]; foreach($services as $i=>$p){ $out[]=['title'=>$p['title'] ?? '', 'spot'=>($p['spot'] ?? '') ?: kv1_excerpt($p['content'] ?? '',150), 'icon'=>['◈','⬡','◎','▷','◇','▣'][$i%6], 'url'=>function_exists('post_url') ? post_url($p) : '#']; }
    return $out ?: kv1_default_services();
}
function kv1_project_cards(array $portfolio=[]): array {
    if(!$portfolio) return kv1_default_projects();
    $out=[]; foreach($portfolio as $i=>$p){ $out[]=['title'=>$p['title'] ?? '', 'spot'=>($p['spot'] ?? '') ?: kv1_excerpt($p['content'] ?? '',120), 'cat'=>$p['category_name'] ?? 'Proje', 'img'=>($i%3)+1, 'url'=>function_exists('post_url') ? post_url($p) : '#']; }
    return $out ?: kv1_default_projects();
}
function kv1_main_menu(): array { $m=function_exists('menu_items') ? menu_items('main') : []; if($m) return $m; return [['title'=>'Hizmetler','url'=>'#services'],['title'=>'Çalışmalar','url'=>'#work'],['title'=>'Süreç','url'=>'#process'],['title'=>'İletişim','url'=>'#contact']]; }
function kv1_footer_menu(): array { $m=function_exists('menu_items') ? menu_items('footer') : []; if($m) return $m; return [['title'=>'Gizlilik','url'=>'#'],['title'=>'Instagram','url'=>kv1_text('instagram_url','#')],['title'=>'LinkedIn','url'=>kv1_text('linkedin_url','#')]]; }

function kv1_demo_category(string $name, string $slug, int $sort=10): int {
    $t=table_name('categories');
    $st=db()->prepare("SELECT id FROM $t WHERE slug=? LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn(); if($id>0) return $id;
    db()->prepare("INSERT INTO $t (name,slug,description,sort_order) VALUES (?,?,?,?)")->execute([$name,$slug,$name,$sort]);
    return (int)db()->lastInsertId();
}
function kv1_demo_post_exists(string $slug, string $type): int { $posts=table_name('posts'); $st=db()->prepare("SELECT id FROM $posts WHERE slug=? AND type".($type==='page'?"='page'":"<>'page'")." LIMIT 1"); $st->execute([$slug]); return (int)$st->fetchColumn(); }
function kv1_demo_upsert_post(array $data, string $type='post'): int {
    $exists=kv1_demo_post_exists((string)($data['slug'] ?? ''), $type); if($exists>0) return $exists;
    return omurga_api_upsert_post($data, $type);
}
function kv1_demo_default_settings(): array {
    return [
        'site_label'=>'Kurumsal','site_label_accent'=>'V1','primary_color'=>'#c8a96e','dark_bg'=>'#0a0a0a',
        'hero_tag'=>'Kurumsal Çözüm Ortağı','hero_title'=>'Markanızı güçlü bir dijital deneyime dönüştürüyoruz.','hero_highlight'=>'dijital',
        'hero_text'=>'Web tasarımından kurumsal kimliğe, işletmenizi dijital dünyada daha profesyonel ve güvenilir gösterecek çözümler üretiyoruz.',
        'hero_button_text'=>'Teklif Al','hero_button_url'=>'#contact','secondary_button_text'=>'Çalışmalarımız','secondary_button_url'=>'#work',
        'stat_1_number'=>'80+','stat_1_label'=>'Tamamlanan Proje','stat_2_number'=>'5★','stat_2_label'=>'Müşteri Puanı','stat_3_number'=>'3+','stat_3_label'=>'Yıl Deneyim',
        'services_title'=>'Sunduğumuz Hizmetler','services_intro'=>'İşlevsellik ile estetiği dengeli biçimde bir araya getiriyor, markanıza özel sürdürülebilir çözümler üretiyoruz.',
        'portfolio_title'=>'Seçilmiş Çalışmalar','portfolio_intro'=>'Her proje müşterinin vizyonunu anlamakla başlar. Ortaya çıkan sadece güzel bir tasarım değil, değer yaratan bir çözümdür.',
        'show_manager'=>'1','manager_title'=>'Kurucudan Mesaj','manager_name'=>'Omurga Kurumsal','manager_text'=>'Her projede sade, güvenilir ve uzun ömürlü bir dijital yapı kurmayı hedefliyoruz.',
        'process_title'=>'Çalışma Sürecimiz','cta_title'=>'Projenizi hayata geçirelim.','cta_text'=>'Fikriniz varsa, geri kalanını birlikte planlayalım. İlk görüşme ücretsiz ve bağlayıcı değildir.',
        'cta_button_text'=>'Ücretsiz Görüşme Talep Et','cta_button_url'=>'mailto:merhaba@example.com','contact_email'=>'merhaba@example.com','contact_phone'=>'+90 555 000 00 00','footer_text'=>'Tüm hakları saklıdır.',
        'instagram_url'=>'#','behance_url'=>'#','linkedin_url'=>'#'
    ];
}
function kv1_demo_import(bool $visualOnly=false): array {
    $created=0;
    update_theme_settings(kv1_demo_default_settings(), 'kurumsal-v1');
    if(function_exists('update_setting_json')){
        update_setting_json('kurumsal_v1_layout', ['version'=>'1.0.0','installed_at'=>date('c')]);
    }
    if($visualOnly){ update_setting('kurumsal_v1_demo_visual_reset_at', date('Y-m-d H:i:s')); return ['message'=>'Kurumsal V1 görünüm ayarları sıfırlandı. İçeriklere dokunulmadı.']; }

    $hizmetler=kv1_demo_category('Hizmetler','hizmetler',10);
    $projeler=kv1_demo_category('Projeler','projeler',20);
    $duyurular=kv1_demo_category('Duyurular','duyurular',30);

    $pages=[
        ['Ana Sayfa','anasayfa','Kurumsal V1 ana sayfa demosu.'],
        ['Hakkımızda','hakkimizda','Firmamız; sade, güvenilir ve sürdürülebilir dijital çözümler üretmek için çalışır.'],
        ['Hizmetler','hizmetler-sayfasi','Web tasarımı, kurumsal kimlik, UI/UX tasarım ve dijital danışmanlık hizmetleri sunuyoruz.'],
        ['Projeler','projeler-sayfasi','Tamamlanan işlerimiz ve örnek proje çalışmalarımız.'],
        ['İletişim','iletisim','Bizimle iletişime geçin ve projenizi birlikte planlayalım.'],
        ['Gizlilik Politikası','gizlilik-politikasi','Gizlilik politikası örnek sayfası.'],
    ];
    foreach($pages as $p){ $id=kv1_demo_upsert_post(['title'=>$p[0],'slug'=>$p[1],'spot'=>$p[0],'content'=>'<p>'.$p[2].'</p>','status'=>'published'], 'page'); if($id) $created++; }

    $services=[
        ['Web Tasarımı','web-tasarimi','Markanızı yansıtan, kullanıcı dostu ve mobil uyumlu web siteleri hazırlıyoruz.'],
        ['Kurumsal Kimlik','kurumsal-kimlik','Logo, renk, tipografi ve kurumsal görsel dili tek bir güçlü marka çizgisinde topluyoruz.'],
        ['UI / UX Tasarım','ui-ux-tasarim','Kullanıcı deneyimini merkeze alan sade ve etkili arayüzler tasarlıyoruz.'],
        ['Dijital Danışmanlık','dijital-danismanlik','İşletmenizin dijital yol haritasını sade, ölçülebilir ve uygulanabilir şekilde planlıyoruz.'],
    ];
    foreach($services as $s){ $id=kv1_demo_upsert_post(['title'=>$s[0],'slug'=>$s[1],'spot'=>$s[2],'content'=>'<p>'.$s[2].'</p>','category_id'=>$hizmetler,'status'=>'published','tags'=>'hizmet,kurumsal'], 'post'); if($id) $created++; }

    $projects=[
        ['Kurumsal Web Sitesi','kurumsal-web-sitesi','Bir hizmet firması için modern, hızlı ve mobil uyumlu kurumsal web sitesi hazırlandı.'],
        ['Marka Kimliği Çalışması','marka-kimligi-calismasi','Yeni marka dili, logo ve görsel kimlik sistemi oluşturuldu.'],
        ['Yönetim Paneli Arayüzü','yonetim-paneli-arayuzu','Kullanıcı deneyimi güçlendirilmiş sade yönetim paneli arayüzü tasarlandı.'],
    ];
    foreach($projects as $p){ $id=kv1_demo_upsert_post(['title'=>$p[0],'slug'=>$p[1],'spot'=>$p[2],'content'=>'<p>'.$p[2].'</p>','category_id'=>$projeler,'status'=>'published','tags'=>'proje,referans'], 'post'); if($id) $created++; }
    $news=[
        ['Yeni Web Sitemiz Yayında','yeni-web-sitemiz-yayinda','Kurumsal V1 demo sitesi modern arayüzüyle yayına hazır.'],
        ['Dijital Dönüşümde Yeni Dönem','dijital-donusumde-yeni-donem','Firmalar için güçlü dijital varlık artık daha önemli.']
    ];
    foreach($news as $n){ $id=kv1_demo_upsert_post(['title'=>$n[0],'slug'=>$n[1],'spot'=>$n[2],'content'=>'<p>'.$n[2].'</p>','category_id'=>$duyurular,'status'=>'published','tags'=>'duyuru'], 'post'); if($id) $created++; }

    $main=[
        ['id'=>1,'title'=>'Ana Sayfa','url'=>omurga_url(),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
        ['id'=>2,'title'=>'Hizmetler','url'=>'#services','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
        ['id'=>3,'title'=>'Çalışmalar','url'=>'#work','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],
        ['id'=>4,'title'=>'Süreç','url'=>'#process','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>40],
        ['id'=>5,'title'=>'İletişim','url'=>'#contact','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>50]
    ];
    update_setting_json('menu_main',$main); update_setting_json('menu_mobile',$main); update_setting_json('menu_footer',[
        ['id'=>1,'title'=>'Hakkımızda','url'=>omurga_url('hakkimizda'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
        ['id'=>2,'title'=>'Hizmetler','url'=>'#services','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
        ['id'=>3,'title'=>'Projeler','url'=>'#work','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],
        ['id'=>4,'title'=>'İletişim','url'=>omurga_url('iletisim'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>40]
    ]);
    update_setting('kurumsal_v1_demo_installed_at', date('Y-m-d H:i:s'));
    return ['message'=>'Kurumsal V1 demosu yüklendi. İçerikler korunacak şekilde oluşturuldu.','created'=>$created];
}

function kv1_admin_panel_content(): string {
    ob_start();
    require __DIR__.'/admin/panel-content.php';
    return (string)ob_get_clean();
}
if(function_exists('omurga_register_admin_page')){
    omurga_register_admin_page('kurumsal-v1-panel', 'Kurumsal V1 Paneli', 'kv1_admin_panel_content', 'themes.manage', '🏢', 46, ['menu_group'=>'active_theme','menu_group_title'=>'Aktif Tema','menu_group_icon'=>'▨']);
}
