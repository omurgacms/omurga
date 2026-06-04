<?php require '_layout.php'; require_cap('users.manage');
function omurga_lang_file_items(string $file): array {
    if(!is_file($file)) return [];
    $items = include $file;
    return is_array($items) ? $items : [];
}
$coreTr = omurga_lang_file_items(OMURGA_ROOT.'/core/lang/tr.php');
$coreEn = omurga_lang_file_items(OMURGA_ROOT.'/core/lang/en.php');
$theme = active_theme_slug();
$themeTr = omurga_lang_file_items(OMURGA_ROOT.'/themes/'.$theme.'/lang/tr.php');
$themeEn = omurga_lang_file_items(OMURGA_ROOT.'/themes/'.$theme.'/lang/en.php');
$coreKeys = array_unique(array_merge(array_keys($coreTr), array_keys($coreEn)));
$themeKeys = array_unique(array_merge(array_keys($themeTr), array_keys($themeEn)));
$missingCoreTr = array_values(array_diff($coreKeys, array_keys($coreTr)));
$missingCoreEn = array_values(array_diff($coreKeys, array_keys($coreEn)));
$missingThemeTr = array_values(array_diff($themeKeys, array_keys($themeTr)));
$missingThemeEn = array_values(array_diff($themeKeys, array_keys($themeEn)));
$overridden = array_values(array_intersect($themeKeys, $coreKeys));
?>
<h1><?=e(om_t('admin.language_check','Dil Kontrolü'))?></h1>
<div class="grid-2 equal">
<section class="card"><h2><?=e(om_t('admin.core_keys','Çekirdek Anahtarları'))?></h2><p class="muted">core/lang/tr.php: <b><?=count($coreTr)?></b> · core/lang/en.php: <b><?=count($coreEn)?></b></p><p class="muted">Eksik TR: <b><?=count($missingCoreTr)?></b> · Eksik EN: <b><?=count($missingCoreEn)?></b></p></section>
<section class="card"><h2><?=e(om_t('admin.theme_keys','Tema Anahtarları'))?></h2><p class="muted"><?=e($theme)?>/lang/tr.php: <b><?=count($themeTr)?></b> · <?=e($theme)?>/lang/en.php: <b><?=count($themeEn)?></b></p><p class="muted">Çekirdeği ezen tema anahtarı: <b><?=count($overridden)?></b></p></section>
</div>
<section class="card" style="margin-top:18px"><h2><?=e(om_t('admin.missing_keys','Eksik Anahtarlar'))?></h2>
<table><thead><tr><th><?=e(om_t('admin.source','Kaynak'))?></th><th><?=e(om_t('admin.tr_value','Türkçe'))?></th><th><?=e(om_t('admin.en_value','İngilizce'))?></th></tr></thead><tbody>
<?php $rows=0; foreach($coreKeys as $k): $trOk=array_key_exists($k,$coreTr); $enOk=array_key_exists($k,$coreEn); if($trOk && $enOk) continue; $rows++; ?><tr><td><?=e(om_t('admin.core','Çekirdek'))?>: <code><?=e($k)?></code></td><td><?= $trOk ? e($coreTr[$k]) : '<span class="badge bad">Eksik</span>' ?></td><td><?= $enOk ? e($coreEn[$k]) : '<span class="badge bad">Missing</span>' ?></td></tr><?php endforeach; ?>
<?php foreach($themeKeys as $k): $trOk=array_key_exists($k,$themeTr); $enOk=array_key_exists($k,$themeEn); if($trOk && $enOk) continue; $rows++; ?><tr><td><?=e(om_t('admin.theme','Tema'))?>: <code><?=e($k)?></code></td><td><?= $trOk ? e($themeTr[$k]) : '<span class="badge bad">Eksik</span>' ?></td><td><?= $enOk ? e($themeEn[$k]) : '<span class="badge bad">Missing</span>' ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="3"><?=e(om_t('admin.no_missing_keys','Eksik anahtar görünmüyor.'))?></td></tr><?php endif; ?>
</tbody></table></section>
<section class="card" style="margin-top:18px"><h2><?=e(om_t('admin.override_keys','Ezilen Anahtarlar'))?></h2>
<table><thead><tr><th><?=e(om_t('admin.key','Anahtar'))?></th><th><?=e(om_t('admin.theme','Tema'))?></th><th><?=e(om_t('admin.core','Çekirdek'))?></th></tr></thead><tbody><?php foreach($overridden as $k): ?><tr><td><code><?=e($k)?></code></td><td><?=e($themeTr[$k] ?? $themeEn[$k] ?? '')?></td><td><?=e($coreTr[$k] ?? $coreEn[$k] ?? '')?></td></tr><?php endforeach; if(!$overridden): ?><tr><td colspan="3">-</td></tr><?php endif; ?></tbody></table>
<p class="muted">Tema anahtarı varsa önce tema çevirisi kullanılır; yoksa çekirdek çevirisine düşer.</p></section>
<?php require '_footer.php'; ?>
