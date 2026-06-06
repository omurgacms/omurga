<?php include __DIR__.'/header.php'; $p=$post ?? $page ?? null; ?>
<div class="dt-page"><h1><?=e($p['title'] ?? 'Sayfa')?></h1><div class="dt-page-content"><?=($p['content'] ?? '<p>İçerik bulunamadı.</p>')?></div></div>
<?php include __DIR__.'/footer.php'; ?>
