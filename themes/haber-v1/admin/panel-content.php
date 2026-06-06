<?php
if (!function_exists('hv1_panel_sanitize_settings')) {
function hv1_panel_sanitize_settings(array $input, array $schema): array {
    $out = [];
    foreach ($schema as $key => $field) {
        if (!is_array($field)) continue;
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key);
        if ($safeKey === '') continue;
        $type = (string)($field['type'] ?? 'text');
        if ($type === 'checkbox') { $out[$safeKey] = !empty($input[$safeKey]) ? '1' : '0'; continue; }
        $value = $input[$safeKey] ?? ($field['default'] ?? '');
        if (is_array($value)) $value = implode(',', array_map('strval', $value));
        $value = trim((string)$value);
        if ($type === 'number') $value = (string)(int)$value;
        if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/',$value)) $value = (string)($field['default'] ?? '#D32F2F');
        if ($type === 'url') $value = filter_var($value, FILTER_SANITIZE_URL);
        $out[$safeKey] = $value;
    }
    return $out;
}}

require_cap('themes.manage');
$notice = '';
$error = '';
$schema = function_exists('omurga_theme_settings_definitions') ? omurga_theme_settings_definitions('haber-v1') : [];
$values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values('haber-v1') : setting_json('theme_settings_haber-v1', []);
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_settings') {
            $data = hv1_panel_sanitize_settings(is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [], $schema);
            if (function_exists('update_theme_settings')) update_theme_settings($data, 'haber-v1'); else update_setting_json('theme_settings_haber-v1', $data);
            $notice = 'Haber V1 tema ayarları kaydedildi.';
        } elseif ($action === 'import_demo') {
            $result = hv1_demo_import(false);
            $notice = (string)($result['message'] ?? 'Demo içeriği yüklendi.');
            if (isset($result['created'])) $notice .= ' Oluşturulan yeni haber: ' . (int)$result['created'] . '.';
        } elseif ($action === 'reset_visual') {
            $result = hv1_demo_import(true);
            $notice = (string)($result['message'] ?? 'Görünüm ayarları sıfırlandı.');
        }
        $values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values('haber-v1') : setting_json('theme_settings_haber-v1', []);
    }
} catch (Throwable $e) { omurga_write_error($e); $error = $e->getMessage(); }
$installedAt = setting('haber_v1_demo_installed_at', '');
$activeTheme = function_exists('omurga_active_theme') ? omurga_active_theme() : setting('active_theme', '');
?>
<style>
.hv1-admin-hero{background:linear-gradient(135deg,#111827,#991b1b);color:#fff;border-radius:22px;padding:24px;margin-bottom:18px;display:flex;justify-content:space-between;gap:18px;align-items:flex-start;box-shadow:0 18px 40px rgba(17,24,39,.18)}
.hv1-admin-hero h1{margin:0 0 8px;font-size:28px;color:#fff}.hv1-admin-hero p{margin:0;color:rgba(255,255,255,.82);max-width:760px}.hv1-admin-badge{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:9px 13px;white-space:nowrap;font-weight:700}.hv1-admin-tabs{display:grid;grid-template-columns:1fr 1fr;gap:18px}.hv1-admin-card{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:18px;padding:18px;margin-bottom:18px;box-shadow:0 10px 30px rgba(15,23,42,.06)}.hv1-admin-card h2{margin-top:0}.hv1-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.hv1-form-grid label,.hv1-full label{display:block;font-weight:700;color:#111827}.hv1-form-grid input,.hv1-form-grid select,.hv1-full input,.hv1-form-grid textarea,.hv1-full textarea{width:100%;margin-top:7px}.hv1-form-grid textarea,.hv1-full textarea{min-height:82px;font-family:monospace}.hv1-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:10px 0}.hv1-checks label{display:flex;gap:10px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#f8fafc}.hv1-checks input{width:auto}.hv1-demo-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:12px 0 0}.hv1-demo-list span{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:10px}.hv1-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.hv1-danger-note{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:14px;padding:12px;margin-top:12px}.hv1-mini{font-size:12px;color:#64748b;margin-top:6px}@media(max-width:900px){.hv1-admin-tabs,.hv1-form-grid,.hv1-demo-list{grid-template-columns:1fr}.hv1-admin-hero{display:block}.hv1-admin-badge{display:inline-block;margin-top:12px}}
</style>
<div class="hv1-admin-hero">
  <div>
    <h1>Haber V1 Tema Paneli</h1>
    <p>Bu panel Haber V1 temasının kendi yönetim alanıdır. Demo kurulum, görünüm sıfırlama, manşet/kategori seçimleri, piyasa değerleri ve tema görünüm ayarları buradan yönetilir.</p>
  </div>
  <div class="hv1-admin-badge"><?= $activeTheme === 'haber-v1' ? 'Aktif Tema' : 'Pasif Tema' ?></div>
</div>
<?php if ($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<?php if ($activeTheme !== 'haber-v1'): ?><div class="alert pending">Haber V1 şu anda aktif tema değil. Ayarları yine hazırlayabilirsin; ön yüzde görünmesi için temayı aktif etmen gerekir.</div><?php endif; ?>

<div class="hv1-admin-tabs">
  <section class="hv1-admin-card">
    <h2>Demo Kurulum</h2>
    <p class="muted">WordPress temalarındaki gibi tek tıkla hazır haber sitesi demosu kurar. İçerikler kullanıcı içeriği kabul edilir ve tema değişince silinmez.</p>
    <?php if ($installedAt): ?><div class="alert success">Demo görünümü daha önce kuruldu: <?=e($installedAt)?></div><?php endif; ?>
    <div class="hv1-demo-list">
      <span>15 demo haber</span><span>Kategoriler</span><span>Etiketler</span><span>Sayfalar</span><span>Ana / mobil / footer menü</span><span>Demo görseller</span><span>Manşet yerleşimi</span><span>Sidebar / footer düzeni</span>
    </div>
    <form method="post" class="hv1-actions">
      <?=csrf_field()?>
      <button class="btn primary" name="action" value="import_demo">Demo İçeriği Yükle</button>
      <button class="btn light" name="action" value="reset_visual" onclick="return confirm('Sadece Haber V1 görünüm ve tema ayarları sıfırlanacak. Yazılar, sayfalar, kategoriler, etiketler, görseller ve menüler silinmeyecek. Devam edilsin mi?')">Sadece Görünümü Sıfırla</button>
      <a class="btn light" href="<?=e(omurga_url())?>" target="_blank">Siteyi Gör</a>
    </form>
    <div class="hv1-danger-note"><b>Silinmeyen içerikler:</b> Yazılar, sayfalar, kategoriler, etiketler, medya dosyaları ve menüler korunur. Görünümü sıfırla sadece Haber V1 ayarlarını temizler.</div>
  </section>

  <section class="hv1-admin-card">
    <h2>Hızlı Kullanım</h2>
    <p class="muted">Önerilen akış:</p>
    <ol>
      <li>Haber V1 temasını aktif et.</li>
      <li>Bu panelden <b>Demo İçeriği Yükle</b> butonuna bas.</li>
      <li>Manşet ve kategori ayarlarını aşağıdan isteğine göre değiştir.</li>
      <li>Siteyi kontrol et, sonra demo haberleri kendi haberlerinle değiştir.</li>
    </ol>
    <p class="muted">Demo tekrar yüklenirse aynı slug’lar kontrol edilir; aynı haberlerden gereksiz kopya üretmemeye çalışır.</p>
  </section>
</div>

<section class="hv1-admin-card">
  <h2>Tema Ayarları</h2>
  <form method="post">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="save_settings">
    <h3>Genel Görünüm</h3>
    <div class="hv1-form-grid">
      <label>Logo Yazısı<input name="settings[site_label]" value="<?=e($values['site_label'] ?? 'HABER V1')?>"></label>
      <label>Logo Görsel Yolu<input name="settings[logo]" value="<?=e($values['logo'] ?? '')?>" placeholder="uploads/2026/06/logo.webp"></label>
      <label>Ana Renk<input type="color" name="settings[primary_color]" value="<?=e($values['primary_color'] ?? '#D32F2F')?>"></label>
      <label>Footer Yazısı<input name="settings[footer_text]" value="<?=e($values['footer_text'] ?? '')?>" placeholder="© Haber V1"></label>
    </div>
    <div class="hv1-checks">
      <label><input type="checkbox" name="settings[show_breaking]" value="1" <?=($values['show_breaking'] ?? '1')==='1'?'checked':''?>> Son dakika şeridi</label>
      <label><input type="checkbox" name="settings[show_markets]" value="1" <?=($values['show_markets'] ?? '1')==='1'?'checked':''?>> Piyasa şeridi</label>
      <label><input type="checkbox" name="settings[show_authors]" value="1" <?=($values['show_authors'] ?? '1')==='1'?'checked':''?>> Yazarlar alanı</label>
      <label><input type="checkbox" name="settings[show_sidebar]" value="1" <?=($values['show_sidebar'] ?? '1')==='1'?'checked':''?>> Sidebar</label>
    </div>

    <h3>Kategori Yerleşimi</h3>
    <div class="hv1-form-grid">
      <label>Manşet Kategorisi Slug<input name="settings[hero_category]" value="<?=e($values['hero_category'] ?? 'gundem')?>"><div class="hv1-mini">Örnek: gundem</div></label>
      <label>Yerel Blok Slug<input name="settings[local_category]" value="<?=e($values['local_category'] ?? 'yerel')?>"></label>
      <label>Ekonomi Blok Slug<input name="settings[economy_category]" value="<?=e($values['economy_category'] ?? 'ekonomi')?>"></label>
      <label>Spor Blok Slug<input name="settings[sport_category]" value="<?=e($values['sport_category'] ?? 'spor')?>"></label>
      <label>Teknoloji Blok Slug<input name="settings[tech_category]" value="<?=e($values['tech_category'] ?? 'teknoloji')?>"></label>
    </div>

    <h3>Piyasa Şeridi</h3>
    <div class="hv1-form-grid">
      <label>Dolar<input name="settings[market_dollar]" value="<?=e($values['market_dollar'] ?? '32,41')?>"></label>
      <label>Euro<input name="settings[market_euro]" value="<?=e($values['market_euro'] ?? '35,17')?>"></label>
      <label>Altın<input name="settings[market_gold]" value="<?=e($values['market_gold'] ?? '3.241')?>"></label>
      <label>BIST 100<input name="settings[market_bist]" value="<?=e($values['market_bist'] ?? '9.842')?>"></label>
    </div>


    <h3>Reklam Yönetimi</h3>
    <div class="hv1-checks">
      <label><input type="checkbox" name="settings[show_ad_placeholders]" value="1" <?=($values['show_ad_placeholders'] ?? '1')==='1'?'checked':''?>> Kod yoksa boş reklam alanlarını göster</label>
    </div>
    <div class="hv1-form-grid">
      <label>Header Reklam Kodu<textarea name="settings[ad_header]" placeholder="AdSense veya HTML reklam kodu"><?=e($values['ad_header'] ?? '')?></textarea></label>
      <label>Manşet Altı Reklam Kodu<textarea name="settings[ad_home_after_hero]" placeholder="Ana sayfa manşet altı reklam kodu"><?=e($values['ad_home_after_hero'] ?? '')?></textarea></label>
      <label>Sidebar Reklam Kodu<textarea name="settings[ad_sidebar]" placeholder="Sağ alan reklam kodu"><?=e($values['ad_sidebar'] ?? '')?></textarea></label>
      <label>Yazı İçi Reklam Kodu<textarea name="settings[ad_article]" placeholder="Yazı detay reklam kodu"><?=e($values['ad_article'] ?? '')?></textarea></label>
    </div>

    <h3>Yazar Kartları</h3>
    <div class="hv1-form-grid">
      <label>Yazar 1 Adı<input name="settings[author_1_name]" value="<?=e($values['author_1_name'] ?? 'Ahmet Yılmaz')?>"></label>
      <label>Yazar 1 Başlık<input name="settings[author_1_title]" value="<?=e($values['author_1_title'] ?? 'Günün politik notları')?>"></label>
      <label>Yazar 1 Baş Harf<input name="settings[author_1_initials]" value="<?=e($values['author_1_initials'] ?? 'AY')?>"></label>
      <label>Yazar 2 Adı<input name="settings[author_2_name]" value="<?=e($values['author_2_name'] ?? 'Elif Kaya')?>"></label>
      <label>Yazar 2 Başlık<input name="settings[author_2_title]" value="<?=e($values['author_2_title'] ?? 'Ekonomi ve piyasalar')?>"></label>
      <label>Yazar 2 Baş Harf<input name="settings[author_2_initials]" value="<?=e($values['author_2_initials'] ?? 'EK')?>"></label>
      <label>Yazar 3 Adı<input name="settings[author_3_name]" value="<?=e($values['author_3_name'] ?? 'Murat Demir')?>"></label>
      <label>Yazar 3 Başlık<input name="settings[author_3_title]" value="<?=e($values['author_3_title'] ?? 'Yerelden kısa kısa')?>"></label>
      <label>Yazar 3 Baş Harf<input name="settings[author_3_initials]" value="<?=e($values['author_3_initials'] ?? 'MD')?>"></label>
    </div>

    <h3>Sosyal / Butonlar</h3>
    <div class="hv1-form-grid">
      <label>E-Gazete Linki<input name="settings[epaper_url]" value="<?=e($values['epaper_url'] ?? '')?>" placeholder="#"></label>
      <label>Abone Ol Linki<input name="settings[subscribe_url]" value="<?=e($values['subscribe_url'] ?? '')?>" placeholder="#"></label>
      <label>Facebook<input name="settings[facebook_url]" value="<?=e($values['facebook_url'] ?? '')?>"></label>
      <label>X / Twitter<input name="settings[x_url]" value="<?=e($values['x_url'] ?? '')?>"></label>
      <label>Instagram<input name="settings[instagram_url]" value="<?=e($values['instagram_url'] ?? '')?>"></label>
    </div>
    <p class="hv1-actions"><button class="btn primary">Tema Ayarlarını Kaydet</button></p>
  </form>
</section>

<section class="hv1-admin-card">
  <h2>Panel Adresi</h2>
  <p class="muted">Bu panel Haber V1 temasının kendi yönetim alanıdır. Tema aktifken admin menüsünde <b>Haber V1 Paneli</b> olarak görünür.</p>
</section>

