<?php
$title=trim((string)($settings['title'] ?? ''));
$text=trim((string)($settings['text'] ?? ''));
$html=trim((string)($settings['html'] ?? ''));
?>
<section class="omg-core-block omg-html-text">
  <?php if($title!==''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <?php if($text!==''): ?><div class="omg-text-body"><?=nl2br(e($text))?></div><?php endif; ?>
  <?php if($html!==''): ?><div class="omg-html-body"><?=omurga_block_safe_html($html)?></div><?php endif; ?>
</section>
