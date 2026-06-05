<?php
$logo=trim((string)(theme_setting('logo','') ?: setting('site_logo_image','')));
$siteName=setting('site_name','Omurga');
$width=max(40,min(480,(int)($settings['width'] ?? 180)));
$link=trim((string)($settings['link'] ?? ''));
$href=$link!=='' ? (str_starts_with($link,'http') ? $link : omurga_url($link)) : omurga_url();
?>
<div class="omg-core-block omg-logo-block">
  <a href="<?=e($href)?>">
    <?php if($logo!==''): ?><img src="<?=e(image_url($logo))?>" alt="<?=e($siteName)?>" style="max-width:<?=e((string)$width)?>px"><?php else: ?><strong><?=e($siteName)?></strong><?php endif; ?>
  </a>
</div>
