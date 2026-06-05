<?php $title=$settings['title'] ?? 'Editörün Seçimi'; ?>
<section class="omg-block omg-editor-choice">
  <div class="v11-section-head"><h2><?=e($title)?></h2></div>
  <div class="post-grid v11-latest-grid">
    <?php foreach($posts as $item): ?>
      <article class="post-card">
        <?php if(!empty($item['featured_image'])): ?><img class="card-img" src="<?=e(image_url($item['featured_image']))?>" alt="<?=e($item['title'])?>"><?php else: ?><div class="fake-img">Omurga</div><?php endif; ?>
        <div class="body"><small><?=e($item['category_name'] ?? 'Genel')?></small><h3><a href="<?=e(post_url($item))?>"><?=e($item['title'])?></a></h3></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
