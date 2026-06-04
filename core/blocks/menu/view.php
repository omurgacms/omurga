<?php
$title=trim((string)($settings['title'] ?? ''));
$location=omurga_normalize_menu_location((string)($settings['location'] ?? 'main'));
$html=omurga_menu($location);
?>
<section class="omg-core-block omg-menu-block">
  <?php if($title!==''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <?=$html ?: '<p class="omg-block-empty">'.e(om_t('blocks.no_content','Icerik bulunamadi.')).'</p>'?>
</section>
