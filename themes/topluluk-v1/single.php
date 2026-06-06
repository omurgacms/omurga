<?php include __DIR__.'/header.php'; $p=$post ?? null; ?>
<div class="dt-page"><div class="dt-page-meta"><?=e($p['category_name'] ?? 'Topluluk')?> · <?=e(tv1_date($p['published_at'] ?? $p['created_at'] ?? ''))?></div><h1><?=e($p['title'] ?? 'İçerik')?></h1><?php if(!empty($p['spot'])): ?><p class="dt-hero-desc" style="color:#555;max-width:760px"><?=e($p['spot'])?></p><?php endif; ?><div class="dt-page-content"><?=($p['content'] ?? '<p>İçerik bulunamadı.</p>')?></div></div>
<?php include __DIR__.'/footer.php'; ?>
