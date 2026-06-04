<?php
$text=trim((string)($settings['text'] ?? ''));
$align=in_array(($settings['align'] ?? 'left'), ['left','center','right'], true) ? $settings['align'] : 'left';
if($text==='') return;
?>
<section class="omg-core-block omg-text-block text-<?=e($align)?>">
  <p><?=nl2br(e($text))?></p>
</section>
