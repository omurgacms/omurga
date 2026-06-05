<?php
$url=trim((string)($settings['url'] ?? ''));
$title=trim((string)($settings['title'] ?? 'Harita'));
if($url==='') return;
?>
<section class="omg-core-block omg-map-block">
  <h2><?=e($title)?></h2>
  <?php if(str_contains($url,'google.com/maps') || str_contains($url,'openstreetmap.org')): ?><iframe src="<?=e($url)?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><?php else: ?><a href="<?=e($url)?>" target="_blank" rel="noopener"><?=e($title)?></a><?php endif; ?>
</section>
