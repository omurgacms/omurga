<?php
$images=array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/u', (string)($settings['images'] ?? '')) ?: [])));
if(!$images) return;
$columns=in_array(($settings['columns'] ?? '3'), ['2','3','4'], true) ? $settings['columns'] : '3';
?>
<section class="omg-core-block omg-gallery-block omg-gallery-cols-<?=e($columns)?>">
  <?php foreach($images as $img): ?><figure><img loading="lazy" src="<?=e(image_url($img))?>" alt=""></figure><?php endforeach; ?>
</section>
