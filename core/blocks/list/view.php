<?php
$items=array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)($settings['items'] ?? '')) ?: [])));
if(!$items) return;
$tag=!empty($settings['ordered']) ? 'ol' : 'ul';
?>
<section class="omg-core-block omg-list-block">
  <<?=$tag?>><?php foreach($items as $item): ?><li><?=e($item)?></li><?php endforeach; ?></<?=$tag?>>
</section>
