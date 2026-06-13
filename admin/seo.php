<?php require '_layout.php'; verify_csrf(); require_cap('seo.view');
omurga_migrate();
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!can('seo.manage') && !can('settings.manage') && current_user_role()!=='admin') { http_response_code(403); exit('Yetki yok'); }
    $action=$_POST['seo_action'] ?? 'save';
    if($action==='save'){
        update_setting('site_description', trim($_POST['site_description']??''));
        update_setting('seo_title_format', trim($_POST['seo_title_format']??'{title} - {site}') ?: '{title} - {site}');
        update_setting('seo_default_og_image', trim($_POST['seo_default_og_image']??''));
        update_setting('seo_allow_index', !empty($_POST['seo_allow_index'])?'1':'0');
        update_setting('seo_enable_og', !empty($_POST['seo_enable_og'])?'1':'0');
        update_setting('seo_enable_twitter', !empty($_POST['seo_enable_twitter'])?'1':'0');
        update_setting('seo_enable_schema', !empty($_POST['seo_enable_schema'])?'1':'0');
        update_setting('seo_hreflang_enabled', !empty($_POST['seo_hreflang_enabled'])?'1':'0');
        update_setting('twitter_site', trim($_POST['twitter_site']??''));
        update_setting('seo_image_filename_from_title', !empty($_POST['seo_image_filename_from_title'])?'1':'0');
        update_setting('seo_image_alt_from_title', !empty($_POST['seo_image_alt_from_title'])?'1':'0');
        update_setting('seo_image_keep_original_filename', !empty($_POST['seo_image_keep_original_filename'])?'1':'0');
        update_setting('seo_image_discover_width', (string)max(600,min(2400,(int)($_POST['seo_image_discover_width']??1200))));
        update_setting('seo_sitemap_enabled', !empty($_POST['seo_sitemap_enabled'])?'1':'0');
        update_setting('seo_news_sitemap_enabled', !empty($_POST['seo_news_sitemap_enabled'])?'1':'0');
        update_setting('seo_sitemap_tags_enabled', !empty($_POST['seo_sitemap_tags_enabled'])?'1':'0');
        update_setting('seo_sitemap_images_enabled', !empty($_POST['seo_sitemap_images_enabled'])?'1':'0');
        update_setting('seo_sitemap_authors_enabled', !empty($_POST['seo_sitemap_authors_enabled'])?'1':'0');
        update_setting('seo_atom_enabled', !empty($_POST['seo_atom_enabled'])?'1':'0');
        update_setting('seo_feed_enabled', !empty($_POST['seo_feed_enabled'])?'1':'0');
        update_setting('seo_category_feed_enabled', !empty($_POST['seo_category_feed_enabled'])?'1':'0');
        update_setting('seo_google_news_feed_enabled', !empty($_POST['seo_google_news_feed_enabled'])?'1':'0');
        update_setting('seo_indexnow_enabled', !empty($_POST['seo_indexnow_enabled'])?'1':'0');
        update_setting('seo_index_queue_enabled', !empty($_POST['seo_index_queue_enabled'])?'1':'0');
        update_setting('seo_redirects_enabled', !empty($_POST['seo_redirects_enabled'])?'1':'0');
        update_setting('seo_404_logging_enabled', !empty($_POST['seo_404_logging_enabled'])?'1':'0');
        update_setting('seo_feed_limit', (string)max(5,min(200,(int)($_POST['seo_feed_limit']??50))));
        update_setting('seo_google_news_feed_limit', (string)max(10,min(100,(int)($_POST['seo_google_news_feed_limit']??100))));
        update_setting('schema_org_name', trim($_POST['schema_org_name']??setting('site_name','Omurga')));
        update_setting('schema_org_type', trim($_POST['schema_org_type']??'Organization'));
        update_setting('schema_org_logo', trim($_POST['schema_org_logo']??''));
        update_setting('schema_sameas', trim($_POST['schema_sameas']??''));
        update_setting('schema_phone', trim($_POST['schema_phone']??''));
        update_setting('schema_address', trim($_POST['schema_address']??''));
        update_setting('schema_search_action', !empty($_POST['schema_search_action'])?'1':'0');
        update_setting('seo_eeat_about_slug', trim($_POST['seo_eeat_about_slug']??'hakkimizda'));
        update_setting('seo_eeat_contact_slug', trim($_POST['seo_eeat_contact_slug']??'iletisim'));
        update_setting('seo_eeat_editorial_slug', trim($_POST['seo_eeat_editorial_slug']??'yayin-ilkeleri'));
        update_setting('seo_eeat_privacy_slug', trim($_POST['seo_eeat_privacy_slug']??'gizlilik-politikasi'));
        update_setting('seo_eeat_kvkk_slug', trim($_POST['seo_eeat_kvkk_slug']??'kvkk'));
        update_setting('seo_eeat_ads_slug', trim($_POST['seo_eeat_ads_slug']??'reklam'));
        update_setting('seo_cwv_lcp_image_check', !empty($_POST['seo_cwv_lcp_image_check'])?'1':'0');
        update_setting('seo_theme_audit_enabled', !empty($_POST['seo_theme_audit_enabled'])?'1':'0');
        update_setting('robots_txt_custom', trim($_POST['robots_txt_custom']??''));
        $notice='SEO Merkezi ayarları kaydedildi.';
    } elseif($action==='regenerate_indexnow'){
        update_setting('seo_indexnow_key', bin2hex(random_bytes(16)));
        $notice='IndexNow anahtarı yenilendi.';
    } elseif($action==='add_redirect'){
        $rawSrc=trim((string)($_POST['source_path']??''));
        $src=function_exists('omurga_seo_normalize_path')?omurga_seo_normalize_path($rawSrc):('/'.trim($rawSrc,'/'));
        $target=trim((string)($_POST['target_url']??''));
        $code=(int)($_POST['status_code']??301); if(!in_array($code,[301,302,307,308],true)) $code=301;
        $srcTrim=trim($src,'/');
        $firstSegment=strtolower(strtok($srcTrim,'/') ?: $srcTrim);
        $blocked=array_merge(['','admin','install','storage','uploads','themes','packages','core','api'], function_exists('omurga_reserved_root_slugs') ? omurga_reserved_root_slugs() : []);
        if($rawSrc==='' || $src==='/' || $srcTrim==='' || in_array($firstSegment,$blocked,true)){
            $notice='Yönlendirme kaydedilmedi: Eski URL boş, ana sayfa veya korunan sistem yolu olamaz.';
        } elseif($target===''){
            $notice='Yönlendirme kaydedilmedi: Yeni URL boş olamaz.';
        } elseif(!preg_match('#^https?://#i',$target) && trim($target,'/')===$srcTrim){
            $notice='Yönlendirme kaydedilmedi: Eski ve yeni URL aynı olamaz.';
        } else {
            try{ $r=table_name('seo_redirects'); db()->prepare("INSERT INTO $r (source_path,target_url,status_code,active) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE target_url=VALUES(target_url), status_code=VALUES(status_code), active=1")->execute([$src,$target,$code]); $notice='Yönlendirme kaydedildi.'; }catch(Throwable $e){ $notice='Yönlendirme kaydedilemedi: '.$e->getMessage(); }
        }
    } elseif($action==='delete_redirect'){
        try{ $r=table_name('seo_redirects'); db()->prepare("DELETE FROM $r WHERE id=?")->execute([(int)($_POST['redirect_id']??0)]); $notice='Yönlendirme silindi.'; }catch(Throwable $e){ $notice='Yönlendirme silinemedi: '.$e->getMessage(); }
    } elseif($action==='clear_404'){
        try{ db()->exec('TRUNCATE TABLE '.table_name('seo_404_logs')); $notice='404 kayıtları temizlendi.'; }catch(Throwable $e){ $notice='404 kayıtları temizlenemedi: '.$e->getMessage(); }
    } elseif($action==='clear_queue'){
        try{ db()->exec('TRUNCATE TABLE '.table_name('seo_index_queue')); $notice='İndeks kuyruğu temizlendi.'; }catch(Throwable $e){ $notice='Kuyruk temizlenemedi: '.$e->getMessage(); }
    } elseif($action==='send_url'){
        $url=trim($_POST['manual_url'] ?? '');
        if($url!=='') { $r=omurga_indexnow_ping($url,null); $notice=$r['ok']?'URL IndexNow ile gönderildi.':'IndexNow hata: '.($r['code']??0).' '.($r['body']??''); }
    }
}
$robots=setting('robots_txt_custom',''); if($robots==='') $robots=robots_txt_content();
$site=trim(omurga_url(''),'/');
$feedLinks=[
  'Genel RSS'=>omurga_url('feed.xml'),
  'Atom Feed'=>omurga_url('atom.xml'),
  'Google News RSS'=>omurga_url('google-news.xml'),
  'News Sitemap'=>omurga_url('news-sitemap.xml'),
  'Sitemap Index'=>omurga_url('sitemap.xml'),
  'Etiket Sitemap'=>omurga_url('sitemap-tags.xml'),
  'Görsel Sitemap'=>omurga_url('sitemap-images.xml'),
  'Yazar Sitemap'=>omurga_url('sitemap-authors.xml'),
  'Robots.txt'=>omurga_url('robots.txt'),
];
$cats=[]; try{ $cats=db()->query('SELECT name,slug FROM '.table_name('categories').' ORDER BY sort_order ASC, name ASC LIMIT 20')->fetchAll(); }catch(Throwable $e){}
$queue=[]; try{ $queue=db()->query('SELECT * FROM '.table_name('seo_index_queue').' ORDER BY id DESC LIMIT 30')->fetchAll(); }catch(Throwable $e){}
$redirects=[]; try{ $redirects=db()->query('SELECT * FROM '.table_name('seo_redirects').' ORDER BY id DESC LIMIT 50')->fetchAll(); }catch(Throwable $e){}
$logs404=[]; try{ $logs404=db()->query('SELECT * FROM '.table_name('seo_404_logs').' ORDER BY last_seen_at DESC, id DESC LIMIT 50')->fetchAll(); }catch(Throwable $e){}

$quality=[]; try{
  $postsT=table_name('posts'); $usersT=table_name('users'); $catsT=table_name('categories');
  $rows=db()->query("SELECT p.*, u.name author_name, c.name category_name FROM $postsT p LEFT JOIN $usersT u ON u.id=p.author_id LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' ORDER BY p.published_at DESC, p.id DESC LIMIT 20")->fetchAll();
  foreach($rows as $r){
    $issues=[];
    if(trim((string)($r['spot'] ?? ''))==='') $issues[]='Spot/açıklama boş';
    if(empty($r['featured_image'])) $issues[]='Öne çıkan görsel yok';
    if(empty($r['author_name'])) $issues[]='Yazar yok';
    if(empty($r['category_name'])) $issues[]='Kategori yok';
    if(mb_strlen(strip_tags((string)($r['content'] ?? '')),'UTF-8') < 250) $issues[]='İçerik kısa';
    if(!empty($r['featured_image'])){
      $rel=str_replace('\\','/', ltrim((string)$r['featured_image'],'/'));
      $file=OMURGA_ROOT.'/'.$rel;
      if(is_file($file)) { $dim=@getimagesize($file); if($dim && (int)$dim[0] < (int)setting('seo_image_discover_width','1200')) $issues[]='Görsel '.(int)setting('seo_image_discover_width','1200').'px altında'; }
      $base=pathinfo(basename($rel), PATHINFO_FILENAME);
      if(preg_match('/^(img|dsc|screenshot|whatsapp|image)[-_0-9]/i', $base) || preg_match('/^[a-z0-9]{8,}$/i', $base)) $issues[]='Görsel dosya adı SEO zayıf';
      try{ $mt=table_name('media'); $mst=db()->prepare("SELECT alt_text FROM $mt WHERE file_path=? LIMIT 1"); $mst->execute([$rel]); $altRow=$mst->fetch(); if($altRow && trim((string)($altRow['alt_text'] ?? ''))==='') $issues[]='Görsel alt metni boş'; }catch(Throwable $e){}
    }
    if($issues) $quality[]=['title'=>$r['title'] ?? '', 'url'=>post_url($r), 'issues'=>$issues];
  }
}catch(Throwable $e){}

?>
<div class="toolbar compact-head">
  <div><h1>Omurga SEO Merkezi</h1><p>Genel SEO, RSS/Sitemap, IndexNow, kuyruk, Schema ve Robots tek sayfada sekmeli yönetilir.</p></div>
  <div>
    <a class="btn light" target="_blank" href="../feed.xml">RSS</a>
    <a class="btn light" target="_blank" href="../google-news.xml">Google News RSS</a>
    <a class="btn light" target="_blank" href="../sitemap.xml">Sitemap</a>
    <a class="btn light" href="seo-test.php">SEO Test</a>
  </div>
</div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<nav class="omg-tabs compact-tabs" aria-label="SEO Merkezi sekmeleri">
  <button type="button" class="omg-tab active" data-tab="general">Genel SEO</button>
  <button type="button" class="omg-tab" data-tab="images">Görsel SEO</button>
  <button type="button" class="omg-tab" data-tab="feeds">RSS / Sitemap</button>
  <button type="button" class="omg-tab" data-tab="indexnow">IndexNow</button>
  <button type="button" class="omg-tab" data-tab="queue">Kuyruk</button>
  <button type="button" class="omg-tab" data-tab="schema">Schema</button>
  <button type="button" class="omg-tab" data-tab="redirects">Yönlendirme / 404</button>
  <button type="button" class="omg-tab" data-tab="eeat">E-E-A-T</button>
  <button type="button" class="omg-tab" data-tab="themeaudit">Tema SEO</button>
  <button type="button" class="omg-tab" data-tab="health">Sağlık Raporu</button>
  <button type="button" class="omg-tab" data-tab="robots">Robots</button>
  <button type="button" class="omg-tab" data-tab="quality">Kontrol</button>
</nav>
<form method="post" class="seo-tab-form" id="seoSettingsForm"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="save">
  <section class="seo-tab-section active" data-section="general">
    <div class="card compact-panel"><div class="compact-panel-head"><h2>Genel SEO</h2><button class="btn primary">Kaydet</button></div>
      <label>Site Başlık Formatı<input name="seo_title_format" value="<?=e(setting('seo_title_format','{title} - {site}'))?>"><small>{title} içerik başlığı, {site} site adıdır.</small></label>
      <label>Varsayılan Meta Açıklaması<input name="site_description" value="<?=e(setting('site_description',''))?>" maxlength="255"></label>
      <label>Varsayılan Open Graph Görseli<input name="seo_default_og_image" value="<?=e(setting('seo_default_og_image', default_social_image()))?>" placeholder="uploads/2026/06/default.webp"></label>
      <div class="compact-check-grid">
        <label><input type="checkbox" name="seo_allow_index" value="1" <?=setting('seo_allow_index','1')==='1'?'checked':''?>> Site arama motorlarına açık</label>
        <label><input type="checkbox" name="seo_enable_og" value="1" <?=setting('seo_enable_og','1')==='1'?'checked':''?>> Open Graph aktif</label>
        <label><input type="checkbox" name="seo_enable_twitter" value="1" <?=setting('seo_enable_twitter','1')==='1'?'checked':''?>> Twitter/X kartları aktif</label>
        <label><input type="checkbox" name="seo_enable_schema" value="1" <?=setting('seo_enable_schema','1')==='1'?'checked':''?>> JSON-LD Schema aktif</label>
        <label><input type="checkbox" name="seo_hreflang_enabled" value="1" <?=setting('seo_hreflang_enabled','0')==='1'?'checked':''?>> Hreflang aktif</label>
        <label>Twitter/X site kullanıcı adı<input name="twitter_site" value="<?=e(setting('twitter_site',''))?>" placeholder="@siteadi"></label>
      </div>
    </div>
  </section>
  <section class="seo-tab-section" data-section="images">
    <div class="grid-2 compact-grid">
      <div class="card compact-panel"><div class="compact-panel-head"><h2>Görsel SEO</h2><button class="btn primary">Kaydet</button></div>
        <div class="compact-check-grid">
          <label><input type="checkbox" name="seo_image_filename_from_title" value="1" <?=setting('seo_image_filename_from_title','1')==='1'?'checked':''?>> Görsel dosya adını yazı başlığından oluştur</label>
          <label><input type="checkbox" name="seo_image_alt_from_title" value="1" <?=setting('seo_image_alt_from_title','1')==='1'?'checked':''?>> Alt metni başlıktan otomatik doldur</label>
          <label><input type="checkbox" name="seo_image_keep_original_filename" value="1" <?=setting('seo_image_keep_original_filename','1')==='1'?'checked':''?>> Orijinal dosya adını medya kaydında sakla</label>
        </div>
        <label>Discover önerilen minimum genişlik<input type="number" name="seo_image_discover_width" value="<?=e(setting('seo_image_discover_width','1200'))?>" min="600" max="2400"></label>
        <p><strong>Örnek:</strong> <code>IMG_20260610.jpg</code> dosyası, yazı başlığı “Bitlis’te Kar Yağışı Etkili Oldu” ise <code>bitliste-kar-yagisi-etkili-oldu.webp</code> olarak kaydedilir. Aynı ad varsa sonuna <code>-2</code>, <code>-3</code> eklenir.</p>
      </div>
      <div class="card compact-panel"><div class="compact-panel-head"><h2>Ne işe yarar?</h2></div>
        <ul>
          <li>Saçma dosya adlarını SEO uyumlu hale getirir.</li>
          <li>Muhabir paneli, admin yazı ekranı ve medya yüklemede ortak çalışır.</li>
          <li>WebP üretildiyse son dosya adı da başlığa göre kalır.</li>
          <li>Orijinal yüklenen dosya adı veritabanında saklanır.</li>
        </ul>
      </div>
    </div>
  </section>
  <section class="seo-tab-section" data-section="feeds">
    <div class="grid-2 compact-grid">
      <div class="card compact-panel"><div class="compact-panel-head"><h2>RSS ve Sitemap Ayarları</h2><button class="btn primary">Kaydet</button></div>
        <div class="compact-check-grid">
          <label><input type="checkbox" name="seo_sitemap_enabled" value="1" <?=setting('seo_sitemap_enabled','1')==='1'?'checked':''?>> Sitemap aktif</label>
          <label><input type="checkbox" name="seo_news_sitemap_enabled" value="1" <?=setting('seo_news_sitemap_enabled','1')==='1'?'checked':''?>> Google News sitemap aktif</label>
          <label><input type="checkbox" name="seo_feed_enabled" value="1" <?=setting('seo_feed_enabled','1')==='1'?'checked':''?>> Genel RSS aktif</label>
          <label><input type="checkbox" name="seo_category_feed_enabled" value="1" <?=setting('seo_category_feed_enabled','1')==='1'?'checked':''?>> Kategori RSS aktif</label>
          <label><input type="checkbox" name="seo_google_news_feed_enabled" value="1" <?=setting('seo_google_news_feed_enabled','1')==='1'?'checked':''?>> Google News RSS aktif</label>
          <label><input type="checkbox" name="seo_atom_enabled" value="1" <?=setting('seo_atom_enabled','1')==='1'?'checked':''?>> Atom feed aktif</label>
          <label><input type="checkbox" name="seo_sitemap_tags_enabled" value="1" <?=setting('seo_sitemap_tags_enabled','1')==='1'?'checked':''?>> Etiket sitemap aktif</label>
          <label><input type="checkbox" name="seo_sitemap_images_enabled" value="1" <?=setting('seo_sitemap_images_enabled','1')==='1'?'checked':''?>> Görsel sitemap aktif</label>
          <label><input type="checkbox" name="seo_sitemap_authors_enabled" value="1" <?=setting('seo_sitemap_authors_enabled','1')==='1'?'checked':''?>> Yazar sitemap aktif</label>
        </div>
        <div class="compact-input-grid"><label>Genel RSS limit<input type="number" name="seo_feed_limit" value="<?=e(setting('seo_feed_limit','50'))?>" min="5" max="200"></label><label>Google News RSS limit<input type="number" name="seo_google_news_feed_limit" value="<?=e(setting('seo_google_news_feed_limit','100'))?>" min="10" max="100"></label></div>
      </div>
      <div class="card compact-panel"><div class="compact-panel-head"><h2>Feed Linkleri</h2></div>
        <div class="compact-link-list"><?php foreach($feedLinks as $label=>$url): ?><a target="_blank" href="<?=e($url)?>"><b><?=e($label)?></b><span><?=e($url)?></span></a><?php endforeach; ?></div>
        <?php if($cats): ?><details class="compact-details"><summary>Kategori RSS linkleri</summary><div class="compact-link-list"><?php foreach($cats as $c): $u=omurga_url('kategori/'.$c['slug'].'/feed.xml'); ?><a target="_blank" href="<?=e($u)?>"><b><?=e($c['name'])?></b><span><?=e($u)?></span></a><?php endforeach; ?></div></details><?php endif; ?>
      </div>
    </div>
  </section>
  <section class="seo-tab-section" data-section="indexnow">
    <div class="grid-2 compact-grid">
      <div class="card compact-panel"><div class="compact-panel-head"><h2>IndexNow Ayarları</h2><button class="btn primary">Kaydet</button></div>
        <label><input type="checkbox" name="seo_indexnow_enabled" value="1" <?=setting('seo_indexnow_enabled','0')==='1'?'checked':''?>> Yazı yayınlanınca IndexNow bildirimi gönder</label>
        <label><input type="checkbox" name="seo_index_queue_enabled" value="1" <?=setting('seo_index_queue_enabled','1')==='1'?'checked':''?>> İndeks kuyruğu/log sistemi aktif</label>
        <label><input type="checkbox" name="seo_redirects_enabled" value="1" <?=setting('seo_redirects_enabled','1')==='1'?'checked':''?>> 301/302 yönlendirme aktif</label>
        <label><input type="checkbox" name="seo_404_logging_enabled" value="1" <?=setting('seo_404_logging_enabled','1')==='1'?'checked':''?>> 404 izleme aktif</label>
        <label>IndexNow Anahtarı<input readonly value="<?=e(setting('seo_indexnow_key',''))?>"></label>
        <p><strong>Anahtar doğrulama URL:</strong><br><code><?=e(omurga_indexnow_key_location())?></code></p>
      </div>
      <div class="card compact-panel"><div class="compact-panel-head"><h2>IndexNow Araçları</h2></div>
        <p>Manuel gönderim ve anahtar işlemleri ayar formundan bağımsız çalışır.</p>
      </div>
    </div>
  </section>
  <section class="seo-tab-section" data-section="schema">
    <div class="card compact-panel"><div class="compact-panel-head"><h2>Schema</h2><button class="btn primary">Kaydet</button></div>
      <div class="compact-input-grid"><label>Schema Kurum Adı<input name="schema_org_name" value="<?=e(setting('schema_org_name', setting('site_name','Omurga')))?>"></label><label>Schema Tipi<select name="schema_org_type"><?php foreach(['Organization'=>'Organization','NewsMediaOrganization'=>'NewsMediaOrganization','LocalBusiness'=>'LocalBusiness','NGO'=>'NGO / Dernek'] as $k=>$v): ?><option value="<?=e($k)?>" <?=setting('schema_org_type','Organization')===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select></label></div>
      <label>Schema Logo<input name="schema_org_logo" value="<?=e(setting('schema_org_logo',''))?>" placeholder="uploads/logo.png veya https://..."></label>
      <label>SameAs sosyal profil linkleri<textarea name="schema_sameas" rows="4" placeholder="Her satıra bir sosyal profil URL'si"><?=e(setting('schema_sameas',''))?></textarea></label>
      <div class="compact-input-grid"><label>Telefon<input name="schema_phone" value="<?=e(setting('schema_phone',''))?>"></label><label>Adres<input name="schema_address" value="<?=e(setting('schema_address',''))?>"></label></div>
      <label><input type="checkbox" name="schema_search_action" value="1" <?=setting('schema_search_action','1')==='1'?'checked':''?>> WebSite SearchAction schema aktif</label>
      <p>Tema içinde <code>{head}</code> kullanılırsa title, meta description, canonical, robots, Open Graph, Twitter kartları ve Schema kodları otomatik basılır.</p>
    </div>
  </section>
  <section class="seo-tab-section" data-section="robots">
    <div class="card compact-panel"><div class="compact-panel-head"><h2>Robots.txt</h2><button class="btn primary">Kaydet</button></div>
      <label>Robots.txt<textarea name="robots_txt_custom" rows="12" placeholder="Boş bırakılırsa Omurga otomatik üretir."><?=e($robots)?></textarea><small>{sitemap} yazarsan otomatik sitemap adresiyle değiştirilir.</small></label>
    </div>
  </section>
</form>
<section class="seo-tab-section" data-section="indexnow">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>IndexNow Araçları</h2></div>
    <form method="post" class="compact-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="send_url"><label>Manuel URL gönder<input name="manual_url" placeholder="https://site.com/yazi-url"></label><button class="btn primary">IndexNow'a Gönder</button></form>
    <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="regenerate_indexnow"><button class="btn light">Anahtarı Yenile</button></form>
    <form method="post" style="display:inline" onsubmit="return confirm('Kuyruk temizlensin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="clear_queue"><button class="btn danger">Kuyruğu Temizle</button></form>
  </div>
</section>

<section class="seo-tab-section" data-section="queue">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>İndeks Kuyruğu</h2></div>
    <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>URL</th><th>Servis</th><th>Durum</th><th>Kod</th><th>Tarih</th><th>Cevap</th></tr></thead><tbody>
      <?php foreach($queue as $q): ?><tr><td style="max-width:360px;word-break:break-all"><a target="_blank" href="<?=e($q['url'])?>"><?=e($q['url'])?></a></td><td><?=e($q['service'])?></td><td><?=e($q['status'])?></td><td><?=e($q['http_code'])?></td><td><?=e($q['created_at'])?></td><td><?=e(mb_substr((string)$q['response'],0,90))?></td></tr><?php endforeach; ?>
      <?php if(!$queue): ?><tr><td colspan="6">Henüz kayıt yok.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</section>
<section class="seo-tab-section" data-section="quality">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>Site Kalite Kontrolü</h2></div>
    <p>Son yayınlanan 20 içerikte haber, kurumsal ve topluluk siteleri için temel kalite kontrolleri yapılır.</p>
    <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>İçerik</th><th>Uyarılar</th></tr></thead><tbody>
      <?php foreach($quality as $item): ?><tr><td><a target="_blank" href="<?=e($item['url'])?>"><?=e($item['title'])?></a></td><td><?=e(implode(', ', $item['issues']))?></td></tr><?php endforeach; ?>
      <?php if(!$quality): ?><tr><td colspan="2">Son içeriklerde temel kalite uyarısı görünmüyor.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</section>


<section class="seo-tab-section" data-section="eeat">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>E-E-A-T / Güven Sayfaları</h2><button form="seoSettingsForm" class="btn primary">Kaydet</button></div>
    <p>Haber, kurumsal ve topluluk sitelerinde güven sinyalleri için künye, iletişim, yayın ilkeleri ve gizlilik sayfaları izlenir.</p>
    <div class="compact-input-grid">
      <label>Hakkımızda slug<input name="seo_eeat_about_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_about_slug','hakkimizda'))?>"></label>
      <label>İletişim slug<input name="seo_eeat_contact_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_contact_slug','iletisim'))?>"></label>
      <label>Yayın ilkeleri slug<input name="seo_eeat_editorial_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_editorial_slug','yayin-ilkeleri'))?>"></label>
      <label>Gizlilik slug<input name="seo_eeat_privacy_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_privacy_slug','gizlilik-politikasi'))?>"></label>
      <label>KVKK slug<input name="seo_eeat_kvkk_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_kvkk_slug','kvkk'))?>"></label>
      <label>Reklam/ilan slug<input name="seo_eeat_ads_slug" form="seoSettingsForm" value="<?=e(setting('seo_eeat_ads_slug','reklam'))?>"></label>
    </div>
    <?php $eeatSlugs=['Hakkımızda'=>setting('seo_eeat_about_slug','hakkimizda'),'İletişim'=>setting('seo_eeat_contact_slug','iletisim'),'Yayın İlkeleri'=>setting('seo_eeat_editorial_slug','yayin-ilkeleri'),'Gizlilik'=>setting('seo_eeat_privacy_slug','gizlilik-politikasi'),'KVKK'=>setting('seo_eeat_kvkk_slug','kvkk'),'Reklam'=>setting('seo_eeat_ads_slug','reklam')]; $postsT=table_name('posts'); ?>
    <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Sayfa</th><th>Slug</th><th>Durum</th></tr></thead><tbody><?php foreach($eeatSlugs as $label=>$slugCheck): $exists=false; try{ $stp=db()->prepare("SELECT id FROM $postsT WHERE slug=? AND type='page' AND status='published' LIMIT 1"); $stp->execute([$slugCheck]); $exists=(bool)$stp->fetchColumn(); }catch(Throwable $e){} ?><tr><td><?=e($label)?></td><td><?=e($slugCheck)?></td><td><span class="badge <?=$exists?'ok':'warn'?>"><?=$exists?'Var':'Eksik'?></span></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
</section>
<section class="seo-tab-section" data-section="themeaudit">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>Tema SEO Denetimi</h2><button form="seoSettingsForm" class="btn primary">Kaydet</button></div>
    <label><input type="checkbox" form="seoSettingsForm" name="seo_theme_audit_enabled" value="1" <?=setting('seo_theme_audit_enabled','1')==='1'?'checked':''?>> Tema SEO denetimi aktif</label>
    <label><input type="checkbox" form="seoSettingsForm" name="seo_cwv_lcp_image_check" value="1" <?=setting('seo_cwv_lcp_image_check','1')==='1'?'checked':''?>> LCP/ilk görsel kontrolü aktif</label>
    <?php $theme=omurga_active_theme(); $themeDir=OMURGA_ROOT.'/themes/'.$theme; $themeChecks=[]; $header=@file_get_contents($themeDir.'/header.php') ?: ''; $single=@file_get_contents($themeDir.'/single.php') ?: ''; $page=@file_get_contents($themeDir.'/page.php') ?: ''; $themeChecks[]=['{head} / SEO head', (str_contains($header,'{head}') || str_contains($header,'omurga_seo_head') || str_contains($header,'$head'))]; $themeChecks[]=['Tekil yazı şablonu', is_file($themeDir.'/single.php') || is_file($themeDir.'/single.omg')]; $themeChecks[]=['Sayfa şablonu', is_file($themeDir.'/page.php') || is_file($themeDir.'/page.omg')]; $themeChecks[]=['Breadcrumb kullanımı', str_contains($single,'breadcrumb') || str_contains($page,'breadcrumb')]; $themeChecks[]=['Yazar kutusu desteği', str_contains($single,'author_box') || str_contains($single,'author')]; $themeChecks[]=['Footer hook / kapanış alanı', is_file($themeDir.'/footer.php') || is_file($themeDir.'/footer.omg')]; ?>
    <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Kontrol</th><th>Durum</th></tr></thead><tbody><?php foreach($themeChecks as $tc): ?><tr><td><?=e($tc[0])?></td><td><span class="badge <?=$tc[1]?'ok':'warn'?>"><?=$tc[1]?'Uygun':'Kontrol gerekli'?></span></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
</section>

<section class="seo-tab-section" data-section="redirects">
  <div class="grid-2 compact-grid">
    <div class="card compact-panel"><div class="compact-panel-head"><h2>Yönlendirme Ekle</h2></div>
      <form method="post" class="compact-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="add_redirect">
        <div class="compact-input-grid"><label>Eski URL / Yol<input name="source_path" placeholder="/eski-yazi"></label><label>Yeni URL / Yol<input name="target_url" placeholder="/yeni-yazi veya https://..."></label></div>
        <label>Durum Kodu<select name="status_code"><option value="301">301 Kalıcı</option><option value="302">302 Geçici</option><option value="307">307 Geçici</option><option value="308">308 Kalıcı</option></select></label>
        <button class="btn primary">Kaydet</button>
      </form>
    </div>
    <div class="card compact-panel"><div class="compact-panel-head"><h2>404 İzleme</h2></div>
      <p>Bulunamayan URL'ler burada toplanır; sık tekrar edenlere 301 yönlendirme ekleyebilirsiniz.</p>
      <form method="post" onsubmit="return confirm('404 kayıtları temizlensin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="clear_404"><button class="btn light">404 Loglarını Temizle</button></form>
    </div>
  </div>
  <div class="card compact-panel"><div class="compact-panel-head"><h2>Yönlendirmeler</h2></div><div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Eski</th><th>Yeni</th><th>Kod</th><th>Hit</th><th></th></tr></thead><tbody><?php foreach($redirects as $r): ?><tr><td><?=e($r['source_path'])?></td><td><?=e($r['target_url'])?></td><td><?=e($r['status_code'])?></td><td><?=e($r['hits'])?></td><td><form method="post" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="seo_action" value="delete_redirect"><input type="hidden" name="redirect_id" value="<?=e($r['id'])?>"><button class="btn danger small">Sil</button></form></td></tr><?php endforeach; ?><?php if(!$redirects): ?><tr><td colspan="5">Henüz yönlendirme yok.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="card compact-panel"><div class="compact-panel-head"><h2>Son 404 Kayıtları</h2></div><div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Yol</th><th>Hit</th><th>Son Görülme</th><th>Referrer</th></tr></thead><tbody><?php foreach($logs404 as $l): ?><tr><td><?=e($l['path'])?></td><td><?=e($l['hits'])?></td><td><?=e($l['last_seen_at'])?></td><td><?=e(mb_substr((string)$l['referrer'],0,80))?></td></tr><?php endforeach; ?><?php if(!$logs404): ?><tr><td colspan="4">Henüz 404 kaydı yok.</td></tr><?php endif; ?></tbody></table></div></div>
</section>
<section class="seo-tab-section" data-section="health">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>SEO Sağlık Raporu</h2></div>
    <?php $score=100; $health=[]; if(setting('seo_sitemap_enabled','1')!=='1'){ $score-=10; $health[]='Sitemap kapalı'; } if(setting('seo_enable_schema','1')!=='1'){ $score-=10; $health[]='Schema kapalı'; } if(setting('seo_enable_og','1')!=='1'){ $score-=6; $health[]='Open Graph kapalı'; } if(setting('seo_image_filename_from_title','1')!=='1'){ $score-=5; $health[]='Görsel dosya adı otomasyonu kapalı'; } if(setting('seo_redirects_enabled','1')!=='1'){ $score-=5; $health[]='Yönlendirme merkezi kapalı'; } if(setting('schema_org_logo','')==='' && setting('site_logo_image','')===''){ $score-=8; $health[]='Schema/logo eksik'; } if(setting('seo_404_logging_enabled','1')!=='1'){ $score-=4; $health[]='404 izleme kapalı'; } if(setting('seo_sitemap_images_enabled','1')!=='1'){ $score-=5; $health[]='Görsel sitemap kapalı'; } if(setting('seo_theme_audit_enabled','1')!=='1'){ $score-=4; $health[]='Tema SEO denetimi kapalı'; } $score=max(0,$score); ?>
    <div class="stat-grid compact-stats"><div class="stat"><b><?=e($score)?>/100</b><span>Teknik SEO Sağlığı</span></div><div class="stat"><b><?=count($quality)?></b><span>İçerik Uyarısı</span></div><div class="stat"><b><?=count($logs404)?></b><span>404 Kaydı</span></div><div class="stat"><b><?=count($redirects)?></b><span>Yönlendirme</span></div></div>
    <?php if($health): ?><ul><?php foreach($health as $h): ?><li><?=e($h)?></li><?php endforeach; ?></ul><?php else: ?><p>Temel teknik SEO ayarlarında kritik eksik görünmüyor.</p><?php endif; ?>
  </div>
</section>

<style>
.compact-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}.omg-tab{border:1px solid #e5e7eb;background:#fff;border-radius:999px;padding:8px 12px;color:#334155;font-weight:800;font-size:13px;cursor:pointer}.omg-tab.active{background:#0f172a;color:#fff;border-color:#0f172a}.seo-tab-section{display:none}.seo-tab-section.active{display:block}.compact-link-list{display:grid;gap:8px}.compact-link-list a{display:grid;grid-template-columns:150px 1fr;gap:10px;align-items:center;border:1px solid #edf0f3;border-radius:12px;padding:9px;text-decoration:none;color:#0f172a}.compact-link-list span{font-size:12px;color:#64748b;word-break:break-all}.compact-details{margin-top:10px}.compact-table-wrap{overflow:auto}.compact-table td,.compact-table th{padding:8px 10px}.seo-tab-form-hidden{display:none}@media(max-width:760px){.compact-tabs{overflow:auto;flex-wrap:nowrap}.omg-tab{white-space:nowrap}.compact-link-list a{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var tabs=document.querySelectorAll('.omg-tab[data-tab]');
  var sections=document.querySelectorAll('.seo-tab-section[data-section]');
  function show(id){ tabs.forEach(function(t){t.classList.toggle('active', t.dataset.tab===id)}); sections.forEach(function(s){s.classList.toggle('active', s.dataset.section===id)}); try{localStorage.setItem('omurga_seo_tab', id)}catch(e){} }
  tabs.forEach(function(t){t.addEventListener('click', function(){show(t.dataset.tab)})});
  show(localStorage.getItem('omurga_seo_tab') || 'general');
});
</script>
<?php require '_footer.php'; ?>
