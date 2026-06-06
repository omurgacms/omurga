<?php include __DIR__.'/header.php'; $p=$page ?? $post ?? null; ?>
<main class="article-wrap"><article class="article-card"><?php if($p): ?><h1><?=e($p['title'])?></h1><div class="article-content"><?=omurga_render_post_content($p)?></div><?php endif; ?></article><?php if(hv1_setting('show_sidebar','1')==='1') hv1_sidebar(); ?></main>
<?php include __DIR__.'/footer.php'; ?>
