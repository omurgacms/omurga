<?php require '_layout.php'; verify_csrf(); require_cap('users.manage');
if($_SERVER['REQUEST_METHOD']==='POST'){
    $oldSiteType = site_type();
    update_setting('site_name', trim($_POST['site_name']??''));
    update_setting('site_description', trim($_POST['site_description']??''));
    $siteType = trim($_POST['site_type']??'haber');
    if(!array_key_exists($siteType, omurga_site_profiles())) $siteType='haber';
    update_setting('site_type', $siteType);
    $adminLang = omurga_normalize_language($_POST['admin_language'] ?? 'tr', 'tr');
    $siteLang = omurga_normalize_language($_POST['site_language'] ?? $adminLang, 'tr');
    update_setting('admin_language', $adminLang);
    update_setting('site_language', $siteLang);
    update_setting('primary_color', trim($_POST['primary_color']??'#d71920'));
    update_setting('secondary_color', trim($_POST['secondary_color']??'#111827'));
    update_setting('logo_text', trim($_POST['logo_text']??''));
    update_setting('site_logo_image', trim($_POST['site_logo_image']??''));
    update_setting('default_social_image', trim($_POST['default_social_image']??''));
    update_setting('webp_quality', (string)max(50,min(95,(int)($_POST['webp_quality']??82))));
    update_setting('robots_index', !empty($_POST['robots_index'])?'1':'0');
    update_setting('footer_text', trim($_POST['footer_text']??''));
    update_setting('facebook_url', trim($_POST['facebook_url']??''));
    update_setting('x_url', trim($_POST['x_url']??''));
    update_setting('instagram_url', trim($_POST['instagram_url']??''));
    $profileMsg = $oldSiteType !== $siteType
        ? ' Site profili değiştirildi. Mevcut içerik, sayfa, kategori, medya ve menüler silinmedi.'
        : '';
    echo '<div class="alert success">Tema, medya ve site ayarları kaydedildi.'.e($profileMsg).'</div>';
}
?>
<h1><?=e(om_t('admin.theme_settings','Tema / Site Ayarları'))?></h1>
<div class="card">
<form method="post" class="settings-tabs">
<input type="hidden" name="_csrf" value="<?=csrf_token()?>">
<h2><?=e(om_t('admin.general_settings','Genel'))?></h2>
<label>Site Adı<input name="site_name" value="<?=e(setting('site_name','Omurga'))?>"></label>
<label>Logo Yazısı<input name="logo_text" value="<?=e(setting('logo_text', setting('site_name','Omurga')))?>"></label>
<label>Logo Görsel Yolu<input name="site_logo_image" value="<?=e(setting('site_logo_image',''))?>" placeholder="uploads/2026/06/logo.webp"></label>
<label>Site Açıklaması<input name="site_description" value="<?=e(setting('site_description','Omurga yayın platformu'))?>"></label>
<label>Site Profili<select name="site_type"><?php foreach(omurga_site_profiles() as $key=>$profile): ?><option value="<?=e($key)?>" <?=site_type()===$key?'selected':''?>><?=e($profile['label'])?></option><?php endforeach; ?></select></label>
<h2><?=e(om_t('admin.language','Dil'))?></h2>
<div class="grid-2 equal">
<label><?=e(om_t('admin.panel_language','Panel Dili'))?><select name="admin_language"><?php foreach(omurga_supported_languages() as $code=>$lang): ?><option value="<?=e($code)?>" <?=omurga_admin_language()===$code?'selected':''?>><?=e($lang['label'])?></option><?php endforeach; ?></select></label>
<label><?=e(om_t('admin.site_language','Site Dili'))?><select name="site_language"><?php foreach(omurga_supported_languages() as $code=>$lang): ?><option value="<?=e($code)?>" <?=omurga_site_language()===$code?'selected':''?>><?=e($lang['label'])?></option><?php endforeach; ?></select></label>
</div>
<p class="muted"><b><?=e(om_t('admin.language','Dil'))?>:</b> <?=e(om_t('admin.language_help','Panel dili yönetim ekranlarını, site dili tema/ön yüz metinlerini etkiler.'))?></p>
<p class="muted"><b><?=e(om_t('admin.profile_safe_change','Güvenli profil değişimi'))?>:</b> Profil değiştirildiğinde mevcut yazılar, sabit sayfalar, kategoriler, medya dosyaları ve menüler silinmez. Sadece panel dili, varsayılan içerik tipi ve yeni bağlantı mantığı profile göre değişir.</p>
<div class="profile-help"><?php foreach(omurga_site_profiles() as $key=>$profile): ?><div><b><?=e($profile['label'])?></b><span><?=e($profile['description'])?></span><small>URL: /<?=e($profile['content_base'])?>/icerik-adi</small></div><?php endforeach; ?></div>
<h2>Renkler</h2>
<div class="grid-2 equal"><label>Ana Renk<input name="primary_color" value="<?=e(setting('primary_color','#d71920'))?>"></label><label>İkincil Renk<input name="secondary_color" value="<?=e(setting('secondary_color','#111827'))?>"></label></div>
<h2 id="media">Medya / WebP</h2>
<label>Varsayılan Sosyal Medya Görseli<input name="default_social_image" value="<?=e(setting('default_social_image',''))?>" placeholder="uploads/2026/06/sosyal.webp"></label>
<label>WebP Kalitesi<input type="number" min="50" max="95" name="webp_quality" value="<?=e(setting('webp_quality','82'))?>"></label>
<p style="color:var(--muted)">Medya bölümünden yüklenen JPG/PNG dosyaları temiz isimle kaydedilir ve WebP kopyası oluşturulur. Bu kalite değeri yeni dönüşümler için kullanılır.</p>
<h2>SEO</h2>
<label style="display:flex;gap:10px;align-items:center"><input type="checkbox" name="robots_index" value="1" <?=setting('robots_index','1')==='1'?'checked':''?>> Arama motorları siteyi tarayabilsin</label>
<p><a class="btn light" href="seo.php">SEO Araçlarına Git</a></p>
<h2>Sosyal Medya</h2>
<label>Facebook<input name="facebook_url" value="<?=e(setting('facebook_url',''))?>"></label>
<label>X / Twitter<input name="x_url" value="<?=e(setting('x_url',''))?>"></label>
<label>Instagram<input name="instagram_url" value="<?=e(setting('instagram_url',''))?>"></label>
<h2>Footer</h2>
<label>Footer Metni<input name="footer_text" value="<?=e(setting('footer_text','Omurga ile hazırlanmıştır.'))?>"></label>
<button class="btn primary"><?=e(om_t('admin.save','Ayarları Kaydet'))?></button>
</form></div>
<?php require '_footer.php'; ?>
