<?php require_once __DIR__.'/functions.php'; include __DIR__.'/header.php'; $p=$post ?? []; ?>
<main class="omh-main"><div class="omh-container omh-page-wrap"><article class="omh-page"><h1><?=omh_e($p['title'] ?? 'Sayfa')?></h1><div class="omh-article-content"><?=function_exists('omurga_render_shortcodes') ? omurga_render_shortcodes($p['content'] ?? '') : ($p['content'] ?? '')?></div></article></div></main>
<?php include __DIR__.'/footer.php'; ?>
