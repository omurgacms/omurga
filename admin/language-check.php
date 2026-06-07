<?php
require '_layout.php';

// Dil kontrolü yardımcı/teşhis ekranıdır. Eski kurulumlarda users.manage verilmiş
// olabilir; yeni kurulumlarda sistem/ayar yetkileri de yeterli kabul edilir.
if(!can('settings.manage') && !can('system.manage') && !can('users.manage')){
    render_error_page(403, 'Yetkisiz Erişim', 'Dil kontrolü için yetkiniz yok.');
}

function omurga_lang_file_items_safe(string $file): array {
    if(!is_file($file)) return [];
    try {
        $items = include $file;
        return is_array($items) ? $items : [];
    } catch (Throwable $e) {
        return ['__file_error__' => $e->getMessage()];
    }
}
function omurga_lang_theme_slug_safe(): string {
    if(function_exists('omurga_active_theme')) return omurga_active_theme();
    if(function_exists('active_theme_slug')) return active_theme_slug();
    return 'omurga-kolay';
}
function omurga_lang_rows(array $keys, array $tr, array $en, string $source): int {
    $rows = 0;
    foreach($keys as $k){
        $trOk=array_key_exists($k,$tr);
        $enOk=array_key_exists($k,$en);
        if($trOk && $enOk) continue;
        $rows++;
        echo '<tr><td>'.e($source).': <code>'.e((string)$k).'</code></td><td>'.($trOk ? e((string)$tr[$k]) : '<span class="badge bad">Eksik</span>').'</td><td>'.($enOk ? e((string)$en[$k]) : '<span class="badge bad">Missing</span>').'</td></tr>';
    }
    return $rows;
}

$coreTr = omurga_lang_file_items_safe(OMURGA_ROOT.'/core/lang/tr.php');
$coreEn = omurga_lang_file_items_safe(OMURGA_ROOT.'/core/lang/en.php');
$theme = omurga_lang_theme_slug_safe();
$theme = preg_replace('/[^a-z0-9_-]/','',strtolower((string)$theme));
$themeLangDir = OMURGA_ROOT.'/themes/'.$theme.'/lang';
$themeTr = omurga_lang_file_items_safe($themeLangDir.'/tr.php');
$themeEn = omurga_lang_file_items_safe($themeLangDir.'/en.php');
$coreKeys = array_values(array_unique(array_merge(array_keys($coreTr), array_keys($coreEn))));
$themeKeys = array_values(array_unique(array_merge(array_keys($themeTr), array_keys($themeEn))));
$missingCoreTr = array_values(array_diff($coreKeys, array_keys($coreTr)));
$missingCoreEn = array_values(array_diff($coreKeys, array_keys($coreEn)));
$missingThemeTr = array_values(array_diff($themeKeys, array_keys($themeTr)));
$missingThemeEn = array_values(array_diff($themeKeys, array_keys($themeEn)));
$overridden = array_values(array_intersect($themeKeys, $coreKeys));
$themeLangExists = is_dir($themeLangDir);
?>
<h1><?=e(om_t('admin.language_check','Dil Kontrolü'))?></h1>
<?php if(!$themeLangExists): ?>
<div class="notice info">Aktif tema için ayrı dil klasörü bulunamadı: <code><?=e('themes/'.$theme.'/lang')?></code>. Bu hata değildir; tema çekirdek çevirilerine düşer.</div>
<?php endif; ?>
<div class="grid-2 equal">
<section class="card"><h2><?=e(om_t('admin.core_keys','Çekirdek Anahtarları'))?></h2><p class="muted">core/lang/tr.php: <b><?=count($coreTr)?></b> · core/lang/en.php: <b><?=count($coreEn)?></b></p><p class="muted">Eksik TR: <b><?=count($missingCoreTr)?></b> · Eksik EN: <b><?=count($missingCoreEn)?></b></p></section>
<section class="card"><h2><?=e(om_t('admin.theme_keys','Tema Anahtarları'))?></h2><p class="muted"><?=e($theme)?>/lang/tr.php: <b><?=count($themeTr)?></b> · <?=e($theme)?>/lang/en.php: <b><?=count($themeEn)?></b></p><p class="muted">Eksik TR: <b><?=count($missingThemeTr)?></b> · Eksik EN: <b><?=count($missingThemeEn)?></b> · Çekirdeği ezen tema anahtarı: <b><?=count($overridden)?></b></p></section>
</div>
<section class="card" style="margin-top:18px"><h2><?=e(om_t('admin.missing_keys','Eksik Anahtarlar'))?></h2>
<table><thead><tr><th><?=e(om_t('admin.source','Kaynak'))?></th><th><?=e(om_t('admin.tr_value','Türkçe'))?></th><th><?=e(om_t('admin.en_value','İngilizce'))?></th></tr></thead><tbody>
<?php $rows=0; $rows += omurga_lang_rows($coreKeys,$coreTr,$coreEn,om_t('admin.core','Çekirdek')); $rows += omurga_lang_rows($themeKeys,$themeTr,$themeEn,om_t('admin.theme','Tema')); ?>
<?php if(!$rows): ?><tr><td colspan="3"><?=e(om_t('admin.no_missing_keys','Eksik anahtar görünmüyor.'))?></td></tr><?php endif; ?>
</tbody></table></section>
<section class="card" style="margin-top:18px"><h2><?=e(om_t('admin.override_keys','Ezilen Anahtarlar'))?></h2>
<table><thead><tr><th><?=e(om_t('admin.key','Anahtar'))?></th><th><?=e(om_t('admin.theme','Tema'))?></th><th><?=e(om_t('admin.core','Çekirdek'))?></th></tr></thead><tbody><?php foreach($overridden as $k): ?><tr><td><code><?=e((string)$k)?></code></td><td><?=e((string)($themeTr[$k] ?? $themeEn[$k] ?? ''))?></td><td><?=e((string)($coreTr[$k] ?? $coreEn[$k] ?? ''))?></td></tr><?php endforeach; if(!$overridden): ?><tr><td colspan="3">-</td></tr><?php endif; ?></tbody></table>
<p class="muted">Tema anahtarı varsa önce tema çevirisi kullanılır; yoksa çekirdek çevirisine düşer.</p></section>
<?php require '_footer.php'; ?>
