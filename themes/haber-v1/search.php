<?php include __DIR__.'/header.php'; $rows=$posts ?? $latest ?? []; ?>
<div class="category-title"><small>Arama</small><h1>Arama Sonuçları</h1></div><main class="page-list"><div class="news3"><?php foreach($rows as $i=>$p) hv1_card($p,'n3',$i+1); ?></div></main>
<?php include __DIR__.'/footer.php'; ?>
