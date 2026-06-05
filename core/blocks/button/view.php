<?php
$text=trim((string)($settings['text'] ?? ''));
$link=trim((string)($settings['link'] ?? ''));
if($text==='' || $link==='') return;
$align=in_array(($settings['align'] ?? 'left'), ['left','center','right'], true) ? $settings['align'] : 'left';
$style=in_array(($settings['style'] ?? 'filled'), ['filled','outline','plain'], true) ? $settings['style'] : 'filled';
$href=str_starts_with($link,'http') ? $link : omurga_url($link);
$target=!empty($settings['new_tab']) ? ' target="_blank" rel="noopener"' : '';
?>
<div class="omg-core-block omg-button-block align-<?=e($align)?>">
  <a class="omg-button omg-button-<?=e($style)?>" href="<?=e($href)?>"<?=$target?>><?=e($text)?></a>
</div>
