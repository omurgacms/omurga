<?php
$file=trim((string)($settings['file'] ?? ''));
if($file==='') return;
$title=trim((string)($settings['title'] ?? 'Dosya indir'));
$desc=trim((string)($settings['description'] ?? ''));
?>
<section class="omg-core-block omg-file-download">
  <a href="<?=e(image_url($file))?>" download><?=e($title)?></a>
  <?php if($desc!==''): ?><p><?=e($desc)?></p><?php endif; ?>
</section>
