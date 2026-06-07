<?php
if (!defined('OMURGA_ROOT')) { exit; }

function hv1_setting(string $key, $default='') { return function_exists('om_theme_setting') ? om_theme_setting($key,$default,'haber-v1') : $default; }
function hv1_primary(): string { $c=(string)hv1_setting('primary_color','#D32F2F'); return preg_match('/^#[0-9a-fA-F]{6}$/',$c)?$c:'#D32F2F'; }
function hv1_logo_text(): string { return trim((string)hv1_setting('site_label','')) ?: setting('site_name','HABER V1'); }
function hv1_asset(string $path): string { return omurga_theme_url(ltrim($path,'/'),'haber-v1'); }
function hv1_img(?string $path, int $fallback=1): string { return $path ? image_url($path) : hv1_asset('assets/images/demo-'.max(1,min(15,$fallback)).'.svg'); }
function hv1_ad_slot(string $slot, string $label='Reklam Alanı'): string {
    $code = trim((string)hv1_setting('ad_'.$slot, ''));
    if ($code !== '') return '<div class="hv1-ad hv1-ad-'.$slot.'">'.$code.'</div>';
    if (hv1_setting('show_ad_placeholders','1') !== '1') return '';
    return '<div class="hv1-ad hv1-ad-placeholder hv1-ad-'.$slot.'"><span>'.$label.'</span></div>';
}
function hv1_author_cards(): array {
    return [
        ['name'=>hv1_setting('author_1_name','Ahmet Yılmaz'),'title'=>hv1_setting('author_1_title','Günün politik notları'),'initials'=>hv1_setting('author_1_initials','AY')],
        ['name'=>hv1_setting('author_2_name','Elif Kaya'),'title'=>hv1_setting('author_2_title','Ekonomi ve piyasalar'),'initials'=>hv1_setting('author_2_initials','EK')],
        ['name'=>hv1_setting('author_3_name','Murat Demir'),'title'=>hv1_setting('author_3_title','Yerelden kısa kısa'),'initials'=>hv1_setting('author_3_initials','MD')],
    ];
}
function hv1_posts(int $limit=6, string $categorySlug='', string $tagSlug=''): array {
    try{
        $posts=table_name('posts'); $cats=table_name('categories');
        $params=[]; $joins=" LEFT JOIN $cats c ON c.id=p.category_id ";
        $where="p.status='published' AND p.type<>'page'";
        if($categorySlug!==''){ $where.=" AND c.slug=?"; $params[]=$categorySlug; }
        if($tagSlug!==''){
            $tags=table_name('tags'); $pt=table_name('post_tags');
            $joins.=" INNER JOIN $pt pt ON pt.post_id=p.id INNER JOIN $tags t ON t.id=pt.tag_id ";
            $where.=" AND t.slug=?"; $params[]=$tagSlug;
        }
        $sql="SELECT p.*, c.name category_name, c.slug category_slug FROM $posts p $joins WHERE $where ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT ".max(1,$limit);
        $st=db()->prepare($sql); $st->execute($params); return $st->fetchAll();
    }catch(Throwable $e){ if(function_exists('omurga_write_error')) omurga_write_error($e); return []; }
}
function hv1_featured(int $limit=6, string $categorySlug=''): array { $rows=hv1_posts($limit,$categorySlug,'manset'); return $rows ?: hv1_posts($limit,$categorySlug,'one-cikan'); }
function hv1_breaking(): array { $rows=hv1_posts(6,'','son-dakika'); return $rows ?: hv1_posts(6); }
function hv1_category_name(string $slug, string $fallback): string { try{ $t=table_name('categories'); $st=db()->prepare("SELECT name FROM $t WHERE slug=? LIMIT 1"); $st->execute([$slug]); $v=$st->fetchColumn(); return $v ?: $fallback; }catch(Throwable $e){ return $fallback; } }
function hv1_time_ago($dt): string { if(!$dt||!strtotime($dt)) return ''; $diff=max(1,time()-strtotime($dt)); if($diff<3600) return floor($diff/60).' dk önce'; if($diff<86400) return floor($diff/3600).' saat önce'; return date('d.m.Y',strtotime($dt)); }
function hv1_card(array $p, string $class='n3', int $imgNo=1): void { $url=post_url($p); ?>
  <article class="<?=e($class)?> hv1-card-link" data-url="<?=e($url)?>">
    <div class="<?= $class==='sn-item' ? 'sn-img' : 'n3-img' ?> has-real news-img-bg" style="background-image:url('<?=e(hv1_img($p['featured_image'] ?? '',$imgNo))?>')"></div>
    <div class="<?= $class==='sn-item' ? 'sn-cat' : 'n3-cat' ?>"><?=e($p['category_name'] ?? 'Haber')?></div>
    <div class="<?= $class==='sn-item' ? 'sn-title' : 'n3-title' ?>"><a href="<?=e($url)?>"><?=e($p['title'] ?? '')?></a></div>
    <?php if($class!=='sn-item'): ?><div class="n3-desc"><?=e(excerpt($p['spot'] ?: ($p['content'] ?? ''),85))?></div><?php endif; ?>
    <div class="<?= $class==='sn-item' ? 'sn-time' : 'n3-time' ?>"><?=e(hv1_time_ago($p['published_at'] ?? $p['created_at'] ?? ''))?></div>
  </article>
<?php }
function hv1_sidebar(): void { $popular=hv1_posts(5); ?>
  <aside class="hv1-sidebar">
    <div class="side-box"><div class="side-title">ARA</div><form class="search-box" action="<?=e(omurga_url('arama'))?>"><input name="q" placeholder="Haber ara"><button>Ara</button></form></div>
    <div class="side-box"><div class="side-title">EN ÇOK OKUNANLAR</div><div class="mr-list"><?php $i=1; foreach($popular as $p): ?><a class="mr-item" href="<?=e(post_url($p))?>"><span class="mr-num"><?= $i++ ?></span><span class="mr-body"><span class="mr-title"><?=e($p['title'])?></span><span class="mr-meta"><?=e(hv1_time_ago($p['published_at'] ?? ''))?></span></span></a><?php endforeach; ?></div></div>
    <div class="side-box"><div class="side-title">REKLAM</div><?=hv1_ad_slot('sidebar','Sidebar Reklam')?></div>
  </aside>
<?php }

function hv1_demo_url(): string { return omurga_url('themes/haber-v1/admin/demo-import.php'); }
function hv1_demo_category(string $name, string $slug, int $sort=10): int {
    $t=table_name('categories');
    try{ $st=db()->prepare("SELECT id FROM $t WHERE slug=? LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn(); if($id>0) return $id; }catch(Throwable $e){}
    db()->prepare("INSERT INTO $t (name,slug,description,sort_order) VALUES (?,?,?,?)")->execute([$name,$slug,$name.' haberleri',$sort]);
    return (int)db()->lastInsertId();
}
function hv1_demo_page(string $title, string $slug, string $content): int {
    $posts=table_name('posts'); $st=db()->prepare("SELECT id FROM $posts WHERE slug=? AND type='page' LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn(); if($id>0) return $id;
    return omurga_api_upsert_post(['title'=>$title,'slug'=>$slug,'content'=>$content,'status'=>'published'], 'page');
}
function hv1_demo_default_layout(): array {
    return [
        'header'=>[
            ['id'=>'hv1_header_logo','slug'=>'logo','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'33','settings'=>['title'=>'Logo']],
            ['id'=>'hv1_header_menu','slug'=>'menu','source'=>'core','enabled'=>1,'sort'=>20,'width'=>'67','settings'=>['title'=>'Ana Menü','location'=>'main']],
        ],
        'home_top'=>[
            ['id'=>'hv1_breaking','slug'=>'html','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['title'=>'Son Dakika','html'=>'Haber V1 son dakika alanı tema tarafından gösterilir.']],
        ],
        'home_main'=>[
            ['id'=>'hv1_hero','slug'=>'featured-content','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['title'=>'Büyük Manşet','category'=>'gundem','limit'=>4]],
            ['id'=>'hv1_local','slug'=>'latest-content','source'=>'core','enabled'=>1,'sort'=>20,'width'=>'50','settings'=>['title'=>'Yerel','category'=>'yerel','limit'=>5]],
            ['id'=>'hv1_economy','slug'=>'latest-content','source'=>'core','enabled'=>1,'sort'=>30,'width'=>'50','settings'=>['title'=>'Ekonomi','category'=>'ekonomi','limit'=>3]],
            ['id'=>'hv1_latest','slug'=>'latest-content','source'=>'core','enabled'=>1,'sort'=>40,'width'=>'100','settings'=>['title'=>'Son Haberler','limit'=>9]],
        ],
        'sidebar'=>[
            ['id'=>'hv1_sidebar_search','slug'=>'search','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'100','settings'=>['title'=>'Arama']],
            ['id'=>'hv1_sidebar_popular','slug'=>'popular-content','source'=>'core','enabled'=>1,'sort'=>20,'width'=>'100','settings'=>['title'=>'En Çok Okunanlar','limit'=>5]],
            ['id'=>'hv1_sidebar_ad','slug'=>'html','source'=>'core','enabled'=>1,'sort'=>30,'width'=>'100','settings'=>['title'=>'Reklam','html'=>'<div class="ad-placeholder">Reklam Alanı</div>']],
        ],
        'footer'=>[
            ['id'=>'hv1_footer_menu','slug'=>'menu','source'=>'core','enabled'=>1,'sort'=>10,'width'=>'50','settings'=>['title'=>'Footer Menü','location'=>'footer']],
            ['id'=>'hv1_footer_social','slug'=>'sosyal-medya','source'=>'core','enabled'=>1,'sort'=>20,'width'=>'50','settings'=>['title'=>'Sosyal Medya']],
        ],
    ];
}
function hv1_demo_apply_visual_settings(): void {
    $settings=['primary_color'=>'#E11D48','site_label'=>'HABER V1','show_breaking'=>'1','show_markets'=>'1','show_authors'=>'1','show_sidebar'=>'1','show_ad_placeholders'=>'1','hero_category'=>'gundem','local_category'=>'yerel','economy_category'=>'ekonomi','sport_category'=>'spor','tech_category'=>'teknoloji','market_dollar'=>'32,41','market_euro'=>'35,17','market_gold'=>'3.241','market_bist'=>'9.842','footer_text'=>'','epaper_url'=>'#','subscribe_url'=>'#','facebook_url'=>'','x_url'=>'','instagram_url'=>'','ad_header'=>'','ad_home_after_hero'=>'','ad_sidebar'=>'','ad_article'=>'','author_1_name'=>'Ahmet Yılmaz','author_1_title'=>'Günün politik notları','author_1_initials'=>'AY','author_2_name'=>'Elif Kaya','author_2_title'=>'Ekonomi ve piyasalar','author_2_initials'=>'EK','author_3_name'=>'Murat Demir','author_3_title'=>'Yerelden kısa kısa','author_3_initials'=>'MD'];
    if(function_exists('update_theme_settings')) update_theme_settings($settings,'haber-v1'); else update_setting_json('theme_settings_haber-v1',$settings);
    update_setting_json('haber_v1_demo_layout', hv1_demo_default_layout());
    update_setting('haber_v1_demo_installed_at', date('c'));
}
function hv1_demo_reset_visual(): array {
    update_setting_json('theme_settings_haber-v1', []);
    update_setting_json('haber_v1_demo_layout', []);
    update_setting('haber_v1_demo_installed_at', '');
    return ['message'=>'Haber V1 görünüm ayarları sıfırlandı. Yazılar, sayfalar, kategoriler, etiketler, görseller ve menüler korunur.'];
}
function hv1_demo_import(bool $resetVisual=false): array {
    if($resetVisual){ return hv1_demo_reset_visual(); }
    $cats=[['Gündem','gundem'],['Yerel','yerel'],['Ekonomi','ekonomi'],['Spor','spor'],['Teknoloji','teknoloji'],['Dünya','dunya'],['Kültür Sanat','kultur-sanat'],['Siyaset','siyaset']];
    $catIds=[]; foreach($cats as $i=>$c){ $catIds[$c[1]]=hv1_demo_category($c[0],$c[1],($i+1)*10); }
    $titles=[
      ['Gündem','gundem','Türkiye-AB ilişkilerinde yeni dönem başladı','Dış politika trafiğinde kritik başlıklar masada.','son-dakika,manset,one-cikan,gundem'],
      ['Gündem','gundem','Meclis gündeminde yoğun hafta','Yeni düzenlemeler komisyonlarda görüşülecek.','manset,gundem'],
      ['Gündem','gundem','Kamu hizmetlerinde dijital dönüşüm hızlandı','Vatandaş işlemleri için yeni dönem başlıyor.','one-cikan,gundem'],
      ['Gündem','gundem','Şehirlerde ulaşım yatırımları artıyor','Yeni projelerle ulaşım ağları güçlendiriliyor.','gundem'],
      ['Yerel','yerel','Yerel yönetimlerden ortak hizmet hamlesi','İlçelerde altyapı ve çevre düzenleme çalışmaları başladı.','yerel,one-cikan'],
      ['Yerel','yerel','Kent meydanında yenileme çalışması','Sosyal yaşam alanları yeniden düzenleniyor.','yerel'],
      ['Yerel','yerel','Mahalle yollarında bakım çalışmaları','Ekipler planlı çalışma takvimini uyguluyor.','yerel'],
      ['Ekonomi','ekonomi','Piyasalarda haftanın ilk rakamları açıklandı','Döviz, altın ve borsa yeni haftaya hareketli başladı.','ekonomi,son-dakika'],
      ['Ekonomi','ekonomi','KOBİ desteklerinde yeni başvuru dönemi','İşletmelere finansman ve danışmanlık desteği sağlanacak.','ekonomi'],
      ['Spor','spor','Temsilcimiz Avrupa’da kritik maça çıkıyor','Takımda hazırlıklar tamamlandı, hedef galibiyet.','spor,manset'],
      ['Spor','spor','Genç sporculardan madalya başarısı','Ulusal turnuvada önemli dereceler elde edildi.','spor'],
      ['Teknoloji','teknoloji','Yapay zekada yerli girişimlerden yeni atılım','Yeni ürünler dünya pazarına açılmaya hazırlanıyor.','teknoloji,one-cikan'],
      ['Teknoloji','teknoloji','Siber güvenlikte yeni uyarı yayımlandı','Uzmanlar güçlü parola ve yedekleme çağrısı yaptı.','teknoloji'],
      ['Dünya','dunya','Dünya liderleri enerji zirvesinde buluştu','Küresel enerji güvenliği için yeni kararlar alındı.','dunya'],
      ['Kültür Sanat','kultur-sanat','Kültür sanat etkinliklerinde yaz programı açıklandı','Konserler, sergiler ve festivaller için takvim netleşti.','kultur-sanat']
    ];
    $created=0; $posts=table_name('posts');
    foreach($titles as $i=>$r){ [$catName,$catSlug,$title,$spot,$tags]=$r; $slug=slugify($title); $st=db()->prepare("SELECT id FROM $posts WHERE slug=? LIMIT 1"); $st->execute([$slug]); $id=(int)$st->fetchColumn();
      $content='<p>'.$spot.'</p><p>Bu içerik Haber V1 demo paketi tarafından örnek haber olarak oluşturulmuştur. Tema manşet, kategori vitrini, son dakika ve sidebar alanlarını gerçek içeriklerle göstermek için hazırlanmıştır.</p><p>Gerçek yayına geçerken bu demo metinleri silebilir veya kendi haberlerinizle değiştirebilirsiniz.</p>';
      $data=['title'=>$title,'slug'=>$slug,'spot'=>$spot,'content'=>$content,'status'=>'published','category_id'=>$catIds[$catSlug] ?? null,'featured_image'=>'themes/haber-v1/assets/images/demo-'.(($i%15)+1).'.svg','tags'=>$tags,'published_at'=>date('Y-m-d H:i:s', time()-($i*3600))];
      if($id<=0){ $id=omurga_api_upsert_post($data,'post'); $created++; } else { omurga_api_upsert_post($data,'post',$id); }
      omurga_set_post_meta($id,'haber_v1_demo','1');
    }
    hv1_demo_page('Hakkımızda','hakkimizda','<p>Haber V1 demo sitesi için örnek hakkımızda sayfası.</p>');
    hv1_demo_page('İletişim','iletisim','<p>İletişim bilgilerinizi bu alandan düzenleyebilirsiniz.</p>');
    hv1_demo_page('Gizlilik Politikası','gizlilik-politikasi','<p>Gizlilik politikası metni.</p>');
    $main=[]; $sort=10; $main[]=['id'=>1,'title'=>'Ana Sayfa','url'=>omurga_url(),'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>$sort]; foreach($cats as $idx=>$c){ $main[]=['id'=>$idx+2,'title'=>$c[0],'url'=>omurga_url('kategori/'.$c[1]),'type'=>'category','target'=>'_self','active'=>1,'parent'=>0,'sort'=>$sort+=10]; } $main[]=['id'=>99,'title'=>'İletişim','url'=>omurga_url('iletisim'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>999];
    update_setting_json('menu_main',$main); update_setting_json('menu_mobile',$main);
    update_setting_json('menu_top',[['id'=>1,'title'=>'Yazarlar','url'=>'#yazarlar','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],['id'=>2,'title'=>'Video','url'=>'#video','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],['id'=>3,'title'=>'Galeri','url'=>'#galeri','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30]]);
    update_setting_json('menu_footer',[['id'=>1,'title'=>'Hakkımızda','url'=>omurga_url('hakkimizda'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],['id'=>2,'title'=>'Gizlilik Politikası','url'=>omurga_url('gizlilik-politikasi'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],['id'=>3,'title'=>'İletişim','url'=>omurga_url('iletisim'),'type'=>'page','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30]]);
    hv1_demo_apply_visual_settings();
    return ['created'=>$created,'message'=>'Haber V1 demo içeriği yüklendi. İçerikler artık kullanıcı içeriği kabul edilir; görünüm sıfırlansa bile silinmez.'];
}
function hv1_demo_remove(): array { return hv1_demo_reset_visual(); }


/* Haber V1 admin menü entegrasyonu: çekirdeğe dosya eklemeden mevcut kayıtlı admin sayfası sistemiyle paneli menüye ekler. */
if (function_exists('omurga_register_admin_page')) {
    omurga_register_admin_page('haber-v1-panel', 'Haber V1 Paneli', 'hv1_render_admin_panel_page', 'themes.manage', '▨', 31, ['menu_group'=>'active_theme','menu_group_title'=>'Aktif Tema','menu_group_icon'=>'▨']);
}

function hv1_render_admin_panel_page(): string {
    if (function_exists('require_cap')) require_cap('themes.manage');
    ob_start();
    $file = __DIR__ . '/admin/panel-content.php';
    if (is_file($file)) {
        include $file;
    } else {
        echo '<div class="alert error">Haber V1 panel dosyası bulunamadı.</div>';
    }
    return (string)ob_get_clean();
}
