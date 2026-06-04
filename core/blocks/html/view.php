<?php
$html=trim((string)($settings['html'] ?? ''));
if($html==='') return;
$allowScript=!empty($settings['allow_script']) && can('settings.manage');
?>
<section class="omg-core-block omg-html-block">
  <?=$allowScript ? $html : omurga_block_safe_html($html)?>
</section>
