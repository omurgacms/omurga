<?php
if (!defined('OMURGA_ROOT')) { exit; }

function tv1_setting(string $key, $default='') { return function_exists('om_theme_setting') ? om_theme_setting($key,$default,'topluluk-v1') : $default; }
function tv1_color(string $key, string $default): string { $c=(string)tv1_setting($key,$default); return preg_match('/^#[0-9a-fA-F]{6}$/',$c)?$c:$default; }
function tv1_asset(string $path): string { return function_exists('omurga_theme_url') ? omurga_theme_url(ltrim($path,'/'),'topluluk-v1') : 'themes/topluluk-v1/'.ltrim($path,'/'); }
function tv1_site_label(): string { return trim((string)tv1_setting('site_label','')) ?: (function_exists('setting') ? setting('site_name','Ankara Kültür Derneği') : 'Ankara Kültür Derneği'); }
function tv1_excerpt($text, int $len=120): string { if(function_exists('excerpt')) return excerpt($text,$len); $s=strip_tags((string)$text); return function_exists('mb_substr') ? mb_substr($s,0,$len) : substr($s,0,$len); }
function tv1_post_url(array $p): string { return function_exists('post_url') ? post_url($p) : '#'; }
function tv1_menu_items(string $location='main'): array {
    if(function_exists('menu_items')) { $items=menu_items($location); if($items) return $items; }
    $fallback=[
        ['title'=>'Anasayfa','url'=>function_exists('omurga_url')?omurga_url():'#','active'=>1],
        ['title'=>'Hakkımızda','url'=>'#hakkimizda','active'=>1],
        ['title'=>'Etkinlikler','url'=>'#etkinlikler','active'=>1],
        ['title'=>'Haberler','url'=>'#haberler','active'=>1],
        ['title'=>'Üyelik','url'=>'#uyelik','active'=>1],
        ['title'=>'İletişim','url'=>'#iletisim','active'=>1],
    ];
    if($location==='footer') return [
        ['title'=>'Hakkımızda','url'=>'#hakkimizda','active'=>1],['title'=>'Etkinlik Takvimi','url'=>'#etkinlikler','active'=>1],['title'=>'Projeler','url'=>'#','active'=>1],['title'=>'Basın Odası','url'=>'#haberler','active'=>1],['title'=>'Yönetmelik','url'=>'#','active'=>1]
    ];
    return $fallback;
}
function tv1_posts(int $limit=3, string $categorySlug=''): array {
    try{
        $posts=table_name('posts'); $cats=table_name('categories'); $params=[];
        $joins=" LEFT JOIN $cats c ON c.id=p.category_id ";
        $where="p.status='published' AND p.type<>'page'";
        if($categorySlug!==''){ $where.=" AND c.slug=?"; $params[]=$categorySlug; }
        $sql="SELECT p.*, c.name category_name, c.slug category_slug FROM $posts p $joins WHERE $where ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT ".max(1,$limit);
        $st=db()->prepare($sql); $st->execute($params); return $st->fetchAll();
    }catch(Throwable $e){ if(function_exists('omurga_write_error')) omurga_write_error($e); return []; }
}
function tv1_date($dt): string { if(!$dt || !strtotime((string)$dt)) return ''; return date('d F Y', strtotime((string)$dt)); }
function tv1_event_settings(): array { return [
    ['e1',tv1_setting('event_1_icon','🎭'),tv1_setting('event_1_date','15 TEM'),tv1_setting('event_1_tag','Kültür & Sanat'),tv1_setting('event_1_title','Geleneksel El Sanatları Sergisi'),tv1_setting('event_1_place','Kültür Merkezi')],
    ['e2',tv1_setting('event_2_icon','📚'),tv1_setting('event_2_date','22 TEM'),tv1_setting('event_2_tag','Eğitim'),tv1_setting('event_2_title','Hukuki Haklar Sempozyumu'),tv1_setting('event_2_place','Konferans Salonu')],
    ['e3',tv1_setting('event_3_icon','🍽️'),tv1_setting('event_3_date','30 TEM'),tv1_setting('event_3_tag','Sosyal'),tv1_setting('event_3_title','Yaz Dayanışma Yemeği'),tv1_setting('event_3_place','Bahçe Tesisi')],
    ['e4',tv1_setting('event_4_icon','🤝'),tv1_setting('event_4_date','05 AĞU'),tv1_setting('event_4_tag','Dayanışma'),tv1_setting('event_4_title','Üye Tanışma ve Dayanışma Buluşması'),tv1_setting('event_4_place','Dernek Merkezi')],
]; }
function tv1_board_members(): array { return [
    ['a1','AK','Ahmet Kaya','Başkan'],['a2','SY','Selin Yılmaz','Başkan Yrd.'],['a3','MÇ','Murat Çelik','Sayman'],['a4','FD','Fatma Demir','Sekreter']
]; }
function tv1_default_settings(): array {
    $meta=function_exists('omurga_theme_meta') ? omurga_theme_meta('topluluk-v1') : [];
    $settings=is_array($meta['settings'] ?? null) ? $meta['settings'] : [];
    $out=[];
    foreach($settings as $k=>$v){ if(is_array($v) && array_key_exists('default',$v)) $out[$k]=(string)$v['default']; }
    return $out;
}
function tv1_demo_category(string $name, string $slug, int $sort=10): int {
    $t=table_name('categories');
    $st=db()->prepare("SELECT id FROM $t WHERE slug=? LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn(); if($id>0) return $id;
    db()->prepare("INSERT INTO $t (name,slug,description,sort_order) VALUES (?,?,?,?)")->execute([$name,$slug,$name,$sort]);
    return (int)db()->lastInsertId();
}
function tv1_demo_page(string $title, string $slug, string $content): int {
    $posts=table_name('posts'); $st=db()->prepare("SELECT id FROM $posts WHERE slug=? AND type='page' LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn(); if($id>0) return $id;
    return omurga_api_upsert_post(['title'=>$title,'slug'=>$slug,'content'=>$content,'status'=>'published'], 'page');
}
function tv1_reset_visual(): array {
    update_setting_json('theme_settings_topluluk-v1', tv1_default_settings());
    update_setting('topluluk_v1_demo_installed_at', date('c'));
    return ['message'=>'Topluluk V1 görünüm ayarları sıfırlandı. Yazılar, sayfalar, kategoriler, etiketler, görseller ve menüler korunur.'];
}
function tv1_demo_import(bool $resetVisual=false): array {
    if($resetVisual) return tv1_reset_visual();
    $cats=[['Duyurular','duyurular'],['Etkinlikler','etkinlikler'],['Projeler','projeler'],['Haberler','haberler'],['Basın','basin']];
    $catIds=[]; foreach($cats as $i=>$c){ $catIds[$c[1]]=tv1_demo_category($c[0],$c[1],($i+1)*10); }
    tv1_demo_page('Anasayfa','anasayfa','<p>Topluluk V1 demo ana sayfası.</p>');
    tv1_demo_page('Hakkımızda','hakkimizda','<p>Derneğimiz kültürel miras, dayanışma ve toplumsal gelişim için faaliyet gösteren örnek bir topluluk yapısıdır.</p>');
    tv1_demo_page('Haberler','haberler','<p>Derneğimizden duyurular, proje haberleri ve basın açıklamaları bu sayfada toplanır. Demo paketinde beş örnek haber oluşturulur.</p><ul><li>2026 bütçesi ve genel kurul duyuruları</li><li>Gençlik bursu başvuruları</li><li>Kültür projeleri ve iş birlikleri</li><li>Üyelik bilgilendirmeleri</li><li>Basın ve kamuoyu açıklamaları</li></ul>');
    tv1_demo_page('Etkinlikler','etkinlikler','<p>Yaklaşan etkinlikler, toplantılar ve sosyal programlar burada listelenir. Demo paketinde dört örnek etkinlik oluşturulur.</p><ul><li>Geleneksel el sanatları sergisi</li><li>Hukuki haklar sempozyumu</li><li>Yaz dayanışma yemeği</li><li>Üye tanışma buluşması</li></ul>');
    tv1_demo_page('Üyelik','uyelik','<p>Üyelik başvuru bilgileri, avantajlar ve başvuru şartları bu sayfada yer alır.</p>');
    tv1_demo_page('İletişim','iletisim','<p>Adres, telefon, e-posta ve çalışma saatleri bilgilerinizi bu sayfadan paylaşabilirsiniz.</p>');
    tv1_demo_page('Yönetim Kurulu','yonetim-kurulu','<p>Yönetim kurulu üyeleri ve görev dağılımı.</p>');
    $posts=table_name('posts'); $created=0;
    $rows=[
      ['Haberler','haberler','2026 Bütçesi Oybirliğiyle Kabul Edildi','Yeni dönem bütçesi üyelerin katılımıyla onaylandı.'],
      ['Haberler','haberler','Gençlik Bursu Başvuruları Açıldı','Topluluk burs programı için başvurular başladı.'],
      ['Haberler','haberler','Belediye ile Kültür Protokolü İmzalandı','Kültürel etkinlikler için iş birliği protokolü imzalandı.'],
      ['Haberler','haberler','Yeni Dönem Üyelik Başvuruları Başladı','Topluluğa katılmak isteyenler için yeni dönem üyelik başvuruları açıldı.'],
      ['Haberler','haberler','Kültür Gecesi Programı Yayınlandı','Geleneksel kültür gecesinin programı ve katılım bilgileri duyuruldu.'],
      ['Duyurular','duyurular','Genel Kurul Toplantısı Duyurusu','Genel kurul toplantısı 15 Temmuz 2026 tarihinde yapılacaktır.'],
      ['Etkinlikler','etkinlikler','Geleneksel El Sanatları Sergisi','Kültür merkezinde el sanatları sergisi düzenlenecek.'],
      ['Etkinlikler','etkinlikler','Hukuki Haklar Sempozyumu','Üyelere yönelik bilgilendirme sempozyumu yapılacak.'],
      ['Etkinlikler','etkinlikler','Yaz Dayanışma Yemeği','Üyelerimizin katılımıyla yaz yemeği organize edilecek.'],
      ['Etkinlikler','etkinlikler','Üye Tanışma ve Dayanışma Buluşması','Yeni üyeler ve gönüllüler dernek merkezinde bir araya gelecek.']
    ];
    foreach($rows as $i=>$r){ [$catName,$catSlug,$title,$spot]=$r; $slug=slugify($title); $st=db()->prepare("SELECT id FROM $posts WHERE slug=? LIMIT 1"); $st->execute([$slug]); if((int)$st->fetchColumn()>0) continue;
        omurga_api_upsert_post(['title'=>$title,'slug'=>$slug,'spot'=>$spot,'content'=>'<p>'.$spot.'</p><p>Bu içerik Topluluk V1 demo paketi tarafından örnek içerik olarak oluşturulmuştur. Gerçek yayına geçerken kendi metinlerinizle değiştirebilirsiniz.</p>','status'=>'published','category_id'=>$catIds[$catSlug] ?? null,'published_at'=>date('Y-m-d H:i:s', time()-($i*86400))],'post');
        $created++;
    }
    $main=[['id'=>1,'title'=>'Anasayfa','url'=>omurga_url(),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],['id'=>2,'title'=>'Hakkımızda','url'=>omurga_url('hakkimizda'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],['id'=>3,'title'=>'Etkinlikler','url'=>omurga_url('etkinlikler'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],['id'=>4,'title'=>'Haberler','url'=>omurga_url('haberler'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>40],['id'=>5,'title'=>'Üyelik','url'=>omurga_url('uyelik'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>50],['id'=>6,'title'=>'İletişim','url'=>omurga_url('iletisim'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>60]];
    update_setting_json('menu_main',$main); update_setting_json('menu_mobile',$main);
    update_setting_json('menu_footer',[['id'=>1,'title'=>'Hakkımızda','url'=>omurga_url('hakkimizda'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],['id'=>2,'title'=>'Etkinlik Takvimi','url'=>omurga_url('etkinlikler'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],['id'=>3,'title'=>'Projeler','url'=>omurga_url('kategori/projeler'),'type'=>'category','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],['id'=>4,'title'=>'Basın Odası','url'=>omurga_url('kategori/basin'),'type'=>'category','target'=>'_self','active'=>1,'parent'=>0,'sort'=>40],['id'=>5,'title'=>'Yönetmelik','url'=>'#','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>50]]);
    update_setting_json('theme_settings_topluluk-v1', tv1_default_settings()); update_setting('topluluk_v1_demo_installed_at', date('c'));
    return ['created'=>$created,'message'=>'Topluluk V1 demo içeriği yüklendi. İçerikler kullanıcı içeriği kabul edilir; tema görünümü sıfırlansa bile silinmez.'];
}

if (function_exists('omurga_register_admin_page')) { omurga_register_admin_page('topluluk-v1-panel', 'Topluluk V1 Paneli', 'tv1_render_admin_panel_page', 'themes.manage', '◉', 34, ['menu_group'=>'active_theme','menu_group_title'=>'Aktif Tema','menu_group_icon'=>'▨']); }
function tv1_render_admin_panel_page(): string { if(function_exists('require_cap')) require_cap('themes.manage'); ob_start(); include __DIR__.'/admin/panel-content.php'; return (string)ob_get_clean(); }
