<?php require_once __DIR__.'/functions.php'; include __DIR__.'/header.php'; $items=$posts ?? ($latest ?? []); ?>
<main class="omh-main"><div class="omh-container omh-two-col">
  <section class="omh-archive"><div class="omh-section-title"><h1><?=omh_e($category['name'] ?? 'Kategori')?></h1></div><?php if($items): ?><div class="omh-archive-grid"><?php foreach($items as $item): ?><article class="omh-news-card"><a href="<?=omh_e(omh_post_url($item))?>"><img src="<?=omh_e(omh_img($item))?>" alt="<?=omh_e($item['title'])?>"><span><?=omh_e($item['category_name'] ?? 'Haber')?></span><h2><?=omh_e($item['title'])?></h2><p><?=omh_e(omh_excerpt($item['spot'] ?: ($item['content'] ?? ''),110))?></p></a></article><?php endforeach; ?></div><?php else: ?><div class="omh-empty">Bu kategoride henüz haber bulunmuyor.</div><?php endif; ?></section>
  <aside class="omh-sidebar"><?=omh_render_region_safe('sidebar', ['category'=>$category ?? []])?></aside>
</div></main><?php include __DIR__.'/footer.php'; ?>
