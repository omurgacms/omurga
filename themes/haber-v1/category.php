<?php include __DIR__.'/header.php'; $rows=$posts ?? $latest ?? []; ?>
<div class="category-title"><small>Kategori</small><h1><?=e($category['name'] ?? $category_name ?? 'Kategori')?></h1><?php if(!empty($category['description'])): ?><p><?=e($category['description'])?></p><?php endif; ?></div>
<main class="page-list"><div class="news3"><?php foreach($rows as $i=>$p) hv1_card($p,'n3',$i+1); ?></div></main>
<?php include __DIR__.'/footer.php'; ?>
