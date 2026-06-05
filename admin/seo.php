<?php require '_layout.php'; verify_csrf(); require_cap('seo.view');
if($_SERVER['REQUEST_METHOD']==='POST'){
    update_setting('site_description', trim($_POST['site_description']??''));
    update_setting('seo_title_format', trim($_POST['seo_title_format']??'{title} - {site}') ?: '{title} - {site}');
    update_setting('seo_default_og_image', trim($_POST['seo_default_og_image']??''));
    update_setting('seo_allow_index', !empty($_POST['seo_allow_index'])?'1':'0');
    update_setting('seo_enable_og', !empty($_POST['seo_enable_og'])?'1':'0');
    update_setting('seo_enable_twitter', !empty($_POST['seo_enable_twitter'])?'1':'0');
    update_setting('seo_enable_schema', !empty($_POST['seo_enable_schema'])?'1':'0');
    update_setting('seo_sitemap_enabled', !empty($_POST['seo_sitemap_enabled'])?'1':'0');
    update_setting('seo_news_sitemap_enabled', !empty($_POST['seo_news_sitemap_enabled'])?'1':'0');
    update_setting('schema_org_name', trim($_POST['schema_org_name']??setting('site_name','Omurga')));
    update_setting('schema_org_type', trim($_POST['schema_org_type']??'Organization'));
    update_setting('robots_txt_custom', trim($_POST['robots_txt_custom']??''));
    echo '<div class="alert success">SEO ve sitemap ayarları kaydedildi.</div>';
}
$robots=setting('robots_txt_custom','');
if($robots==='') $robots=robots_txt_content();
?>
<div class="toolbar">
  <h1>SEO + Sitemap</h1>
  <div>
    <a class="btn light" target="_blank" href="../sitemap.xml">Sitemap</a>
    <a class="btn light" target="_blank" href="../news-sitemap.xml">News Sitemap</a>
    <a class="btn light" target="_blank" href="../robots.txt">Robots.txt</a>
  </div>
</div>
<form method="post" class="grid-2"><input type="hidden" name="_csrf" value="<?=csrf_token()?>">
  <div class="card">
    <h2>Genel SEO</h2>
    <label>Site Başlık Formatı<input name="seo_title_format" value="<?=e(setting('seo_title_format','{title} - {site}'))?>"><small>{title} içerik başlığı, {site} site adıdır.</small></label>
    <label>Varsayılan Meta Açıklaması<input name="site_description" value="<?=e(setting('site_description',''))?>" maxlength="255"></label>
    <label>Varsayılan Open Graph Görseli<input name="seo_default_og_image" value="<?=e(setting('seo_default_og_image', default_social_image()))?>" placeholder="uploads/2026/06/default.webp"></label>
    <div class="mini-grid two">
      <label>Schema Kurum Adı<input name="schema_org_name" value="<?=e(setting('schema_org_name', setting('site_name','Omurga')))?>"></label>
      <label>Schema Tipi<select name="schema_org_type">
        <?php foreach(['Organization'=>'Organization','NewsMediaOrganization'=>'NewsMediaOrganization','LocalBusiness'=>'LocalBusiness','NGO'=>'NGO / Dernek'] as $k=>$v): ?>
        <option value="<?=e($k)?>" <?=setting('schema_org_type','Organization')===$k?'selected':''?>><?=e($v)?></option>
        <?php endforeach; ?>
      </select></label>
    </div>
    <label class="check-line"><input type="checkbox" name="seo_allow_index" value="1" <?=setting('seo_allow_index','1')==='1'?'checked':''?>> Site arama motorlarına açık olsun</label>
    <label class="check-line"><input type="checkbox" name="seo_enable_og" value="1" <?=setting('seo_enable_og','1')==='1'?'checked':''?>> Open Graph etiketleri aktif</label>
    <label class="check-line"><input type="checkbox" name="seo_enable_twitter" value="1" <?=setting('seo_enable_twitter','1')==='1'?'checked':''?>> Twitter/X kartları aktif</label>
    <label class="check-line"><input type="checkbox" name="seo_enable_schema" value="1" <?=setting('seo_enable_schema','1')==='1'?'checked':''?>> JSON-LD Schema aktif</label>
  </div>
  <div class="card">
    <h2>Sitemap ve Robots</h2>
    <label class="check-line"><input type="checkbox" name="seo_sitemap_enabled" value="1" <?=setting('seo_sitemap_enabled','1')==='1'?'checked':''?>> Sitemap aktif</label>
    <label class="check-line"><input type="checkbox" name="seo_news_sitemap_enabled" value="1" <?=setting('seo_news_sitemap_enabled','1')==='1'?'checked':''?>> Google News sitemap aktif</label>
    <p>Otomatik oluşan sitemap dosyaları:</p>
    <ul>
      <li><code>/sitemap.xml</code> — sitemap index</li>
      <li><code>/sitemap-posts.xml</code> — yazı içerikleri</li>
      <li><code>/sitemap-pages.xml</code> — sayfalar</li>
      <li><code>/sitemap-categories.xml</code> — kategoriler</li>
      <li><code>/news-sitemap.xml</code> — son 2 gün yayınları</li>
    </ul>
    <label>Robots.txt<textarea name="robots_txt_custom" rows="8" placeholder="Boş bırakılırsa Omurga otomatik üretir."><?=e($robots)?></textarea><small>{sitemap} yazarsan otomatik sitemap adresiyle değiştirilir.</small></label>
    <button class="btn primary">Kaydet</button>
  </div>
  <div class="card" style="grid-column:1/-1">
    <h2>OMG Kullanımı</h2>
    <p>Tema içinde en temiz kullanım:</p>
    <pre>{head}</pre>
    <p>Bu etiket otomatik olarak title, meta description, canonical, robots, Open Graph, Twitter kartları ve Schema kodlarını basar.</p>
  </div>
</form>
<?php require '_footer.php'; ?>
