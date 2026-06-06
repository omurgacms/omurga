<?php
if (!defined('OMURGA_ROOT')) { exit; }
$meta = omurga_theme_meta('kurumsal-v1');
$schema = is_array($meta['settings'] ?? null) ? $meta['settings'] : [];
$values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values('kurumsal-v1') : setting_json('theme_settings_kurumsal-v1', []);
$notice=''; $error='';
function kv1_panel_sanitize(array $input, array $schema): array {
    $out=[];
    foreach($schema as $key=>$field){
        if(!is_array($field)) continue;
        $type=(string)($field['type'] ?? 'text');
        if($type==='checkbox'){ $out[$key]=!empty($input[$key]) ? '1' : '0'; continue; }
        $value=$input[$key] ?? ($field['default'] ?? '');
        if(is_array($value)) $value=implode(',', array_map('strval',$value));
        $value=trim((string)$value);
        if($type==='url') $value=filter_var($value, FILTER_SANITIZE_URL);
        if($type==='color' && !preg_match('/^#[0-9a-fA-F]{6}$/',$value)) $value=(string)($field['default'] ?? '#c8a96e');
        $out[$key]=$value;
    }
    return $out;
}
try{
    if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST'){
        verify_csrf();
        $action=(string)($_POST['action'] ?? '');
        if($action==='save_settings'){
            $data=kv1_panel_sanitize(is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [], $schema);
            update_theme_settings($data, 'kurumsal-v1');
            $notice='Kurumsal V1 tema ayarları kaydedildi.';
        } elseif($action==='import_demo'){
            $r=kv1_demo_import(false);
            $notice=(string)($r['message'] ?? 'Demo yüklendi.');
            if(isset($r['created'])) $notice.=' İşlenen içerik: '.(int)$r['created'].'.';
        } elseif($action==='reset_visual'){
            $r=kv1_demo_import(true);
            $notice=(string)($r['message'] ?? 'Görünüm sıfırlandı.');
        }
        $values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values('kurumsal-v1') : setting_json('theme_settings_kurumsal-v1', []);
    }
}catch(Throwable $e){ omurga_write_error($e); $error=$e->getMessage(); }
$activeTheme=function_exists('omurga_active_theme') ? omurga_active_theme() : setting('active_theme','');
$installedAt=setting('kurumsal_v1_demo_installed_at','');
?>
<style>
.kv1-admin-hero{background:linear-gradient(135deg,#0a0a0a,#3b2f18);color:#fff;border-radius:22px;padding:24px;margin-bottom:18px;display:flex;justify-content:space-between;gap:18px;align-items:flex-start;box-shadow:0 18px 40px rgba(17,24,39,.18)}
.kv1-admin-hero h1{margin:0 0 8px;font-size:28px;color:#fff}.kv1-admin-hero p{margin:0;color:rgba(255,255,255,.82);max-width:760px}.kv1-admin-badge{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:9px 13px;white-space:nowrap;font-weight:700}.kv1-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.kv1-admin-card{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:18px;padding:18px;margin-bottom:18px;box-shadow:0 10px 30px rgba(15,23,42,.06)}.kv1-admin-card h2{margin-top:0}.kv1-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.kv1-form-grid label,.kv1-full label{display:block;font-weight:700;color:#111827}.kv1-form-grid input,.kv1-form-grid textarea,.kv1-full textarea,.kv1-full input{width:100%;margin-top:7px}.kv1-form-grid textarea,.kv1-full textarea{min-height:86px}.kv1-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:10px 0}.kv1-checks label{display:flex;gap:10px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#f8fafc}.kv1-checks input{width:auto}.kv1-demo-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:12px 0 0}.kv1-demo-list span{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:10px}.kv1-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.kv1-note{background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:14px;padding:12px;margin-top:12px}.kv1-mini{font-size:12px;color:#64748b;margin-top:6px}@media(max-width:900px){.kv1-admin-grid,.kv1-form-grid,.kv1-demo-list{grid-template-columns:1fr}.kv1-admin-hero{display:block}.kv1-admin-badge{display:inline-block;margin-top:12px}}
</style>
<div class="kv1-admin-hero">
  <div><h1>Kurumsal V1 Tema Paneli</h1><p>Bu panel Kurumsal V1 temasının kendi yönetim alanıdır. Demo kurulum, görünüm sıfırlama, hero/hizmet/portföy/CTA ayarları buradan yönetilir.</p></div>
  <div class="kv1-admin-badge"><?= $activeTheme === 'kurumsal-v1' ? 'Aktif Tema' : 'Pasif Tema' ?></div>
</div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<?php if($activeTheme !== 'kurumsal-v1'): ?><div class="alert pending">Kurumsal V1 şu anda aktif tema değil. Ayarları hazırlayabilirsin; ön yüzde görünmesi için temayı aktif etmen gerekir.</div><?php endif; ?>
<div class="kv1-admin-grid">
<section class="kv1-admin-card"><h2>Demo Kurulum</h2><p class="muted">Haber V1 mantığıyla tek tıkla kurumsal demo site kurar. İçerikler kullanıcı içeriği kabul edilir ve tema değişince silinmez.</p><?php if($installedAt): ?><div class="alert success">Demo daha önce kuruldu: <?=e($installedAt)?></div><?php endif; ?><div class="kv1-demo-list"><span>Sayfalar</span><span>Hizmetler</span><span>Projeler</span><span>Duyurular</span><span>Ana / mobil / footer menü</span><span>Hero / CTA ayarları</span><span>Portföy görünümü</span><span>Yönetici mesajı</span></div><form method="post" class="kv1-actions"><?=csrf_field()?><button class="btn primary" name="action" value="import_demo">Demo İçeriği Yükle</button><button class="btn light" name="action" value="reset_visual" onclick="return confirm('Sadece Kurumsal V1 görünüm ayarları sıfırlanacak. Sayfalar, yazılar, kategoriler, görseller ve menüler silinmeyecek. Devam edilsin mi?')">Sadece Görünümü Sıfırla</button><a class="btn light" href="<?=e(omurga_url())?>" target="_blank">Siteyi Gör</a></form><div class="kv1-note"><b>WordPress mantığı:</b> Demo içerikleri tema silinince silinmez. Tema sadece görünümü yönetir.</div></section>
<section class="kv1-admin-card"><h2>Kullanım</h2><ol><li>Kurumsal V1 temasını aktif et.</li><li>Demo İçeriği Yükle butonuna bas.</li><li>Aşağıdaki alanlardan hero, renk, CTA ve iletişim bilgilerini değiştir.</li><li>Demo sayfaları kendi şirket içeriğinle düzenle.</li></ol></section>
</div>
<section class="kv1-admin-card"><h2>Tema Ayarları</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_settings"><h3>Genel</h3><div class="kv1-form-grid"><label>Logo Yazısı<input name="settings[site_label]" value="<?=e($values['site_label'] ?? 'Kurumsal V1')?>"></label><label>Logo Vurgu<input name="settings[site_label_accent]" value="<?=e($values['site_label_accent'] ?? 'Studio')?>"></label><label>Logo Görsel Yolu<input name="settings[logo]" value="<?=e($values['logo'] ?? '')?>" placeholder="uploads/2026/06/logo.webp"></label><label>Ana Renk<input type="color" name="settings[primary_color]" value="<?=e($values['primary_color'] ?? '#c8a96e')?>"></label><label>Arka Plan<input type="color" name="settings[dark_bg]" value="<?=e($values['dark_bg'] ?? '#0a0a0a')?>"></label><label>Footer Yazısı<input name="settings[footer_text]" value="<?=e($values['footer_text'] ?? 'Tüm hakları saklıdır.')?>"></label></div><h3>Hero</h3><div class="kv1-form-grid"><label>Üst Etiket<input name="settings[hero_tag]" value="<?=e($values['hero_tag'] ?? 'Kurumsal Çözüm Ortağı')?>"></label><label>Vurgu Kelimesi<input name="settings[hero_highlight]" value="<?=e($values['hero_highlight'] ?? 'dijital')?>"></label><label>Hero Başlığı<input name="settings[hero_title]" value="<?=e($values['hero_title'] ?? 'Markanızı güçlü bir dijital deneyime dönüştürüyoruz.')?>"></label><label>Ana Buton Yazısı<input name="settings[hero_button_text]" value="<?=e($values['hero_button_text'] ?? 'Teklif Al')?>"></label><label>Ana Buton Linki<input name="settings[hero_button_url]" value="<?=e($values['hero_button_url'] ?? '#contact')?>"></label><label>İkinci Buton Yazısı<input name="settings[secondary_button_text]" value="<?=e($values['secondary_button_text'] ?? 'Çalışmalarımız')?>"></label><label>İkinci Buton Linki<input name="settings[secondary_button_url]" value="<?=e($values['secondary_button_url'] ?? '#work')?>"></label></div><div class="kv1-full"><label>Hero Açıklaması<textarea name="settings[hero_text]"><?=e($values['hero_text'] ?? '')?></textarea></label></div><h3>İstatistikler</h3><div class="kv1-form-grid"><label>1. Sayı<input name="settings[stat_1_number]" value="<?=e($values['stat_1_number'] ?? '80+')?>"></label><label>1. Yazı<input name="settings[stat_1_label]" value="<?=e($values['stat_1_label'] ?? 'Tamamlanan Proje')?>"></label><label>2. Sayı<input name="settings[stat_2_number]" value="<?=e($values['stat_2_number'] ?? '5★')?>"></label><label>2. Yazı<input name="settings[stat_2_label]" value="<?=e($values['stat_2_label'] ?? 'Müşteri Puanı')?>"></label><label>3. Sayı<input name="settings[stat_3_number]" value="<?=e($values['stat_3_number'] ?? '3+')?>"></label><label>3. Yazı<input name="settings[stat_3_label]" value="<?=e($values['stat_3_label'] ?? 'Yıl Deneyim')?>"></label></div><h3>Bölüm Başlıkları</h3><div class="kv1-form-grid"><label>Hizmetler Başlığı<input name="settings[services_title]" value="<?=e($values['services_title'] ?? 'Sunduğumuz Hizmetler')?>"></label><label>Portföy Başlığı<input name="settings[portfolio_title]" value="<?=e($values['portfolio_title'] ?? 'Seçilmiş Çalışmalar')?>"></label><label>Süreç Başlığı<input name="settings[process_title]" value="<?=e($values['process_title'] ?? 'Çalışma Sürecimiz')?>"></label><label>CTA Başlığı<input name="settings[cta_title]" value="<?=e($values['cta_title'] ?? 'Projenizi hayata geçirelim.')?>"></label></div><div class="kv1-form-grid"><label>Hizmetler Açıklaması<textarea name="settings[services_intro]"><?=e($values['services_intro'] ?? '')?></textarea></label><label>Portföy Açıklaması<textarea name="settings[portfolio_intro]"><?=e($values['portfolio_intro'] ?? '')?></textarea></label><label>CTA Açıklaması<textarea name="settings[cta_text]"><?=e($values['cta_text'] ?? '')?></textarea></label><label>Yönetici Mesajı<textarea name="settings[manager_text]"><?=e($values['manager_text'] ?? '')?></textarea></label></div><h3>Yönetici / CTA / İletişim</h3><div class="kv1-checks"><label><input type="checkbox" name="settings[show_manager]" value="1" <?=($values['show_manager'] ?? '1')==='1'?'checked':''?>> Yönetici mesajı göster</label></div><div class="kv1-form-grid"><label>Yönetici Başlığı<input name="settings[manager_title]" value="<?=e($values['manager_title'] ?? 'Kurucudan Mesaj')?>"></label><label>Yönetici Adı<input name="settings[manager_name]" value="<?=e($values['manager_name'] ?? 'Omurga Kurumsal')?>"></label><label>CTA Buton Yazısı<input name="settings[cta_button_text]" value="<?=e($values['cta_button_text'] ?? 'Ücretsiz Görüşme Talep Et')?>"></label><label>CTA Buton Linki<input name="settings[cta_button_url]" value="<?=e($values['cta_button_url'] ?? 'mailto:merhaba@example.com')?>"></label><label>E-posta<input name="settings[contact_email]" value="<?=e($values['contact_email'] ?? 'merhaba@example.com')?>"></label><label>Telefon<input name="settings[contact_phone]" value="<?=e($values['contact_phone'] ?? '+90 555 000 00 00')?>"></label><label>Instagram<input name="settings[instagram_url]" value="<?=e($values['instagram_url'] ?? '#')?>"></label><label>Behance<input name="settings[behance_url]" value="<?=e($values['behance_url'] ?? '#')?>"></label><label>LinkedIn<input name="settings[linkedin_url]" value="<?=e($values['linkedin_url'] ?? '#')?>"></label></div><p class="kv1-actions"><button class="btn primary">Tema Ayarlarını Kaydet</button></p></form></section>
<section class="kv1-admin-card"><h2>Panel Adresi</h2><p class="muted">Bu panel tema içindedir. Çekirdeğe kalıcı dosya eklemez.</p><code><?=e(omurga_url('themes/kurumsal-v1/admin/panel.php'))?></code></section>

