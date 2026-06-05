<?php
$text=trim((string)($settings['text'] ?? 'Baslik'));
$level=in_array(($settings['level'] ?? 'h2'), ['h1','h2','h3','h4'], true) ? $settings['level'] : 'h2';
$align=in_array(($settings['align'] ?? 'left'), ['left','center','right'], true) ? $settings['align'] : 'left';
?>
<section class="omg-core-block omg-heading-block text-<?=e($align)?>">
  <<?=$level?>><?=e($text)?></<?=$level?>>
</section>
