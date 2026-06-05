<?php
$max=max(320,min(1920,(int)($settings['max_width'] ?? 1200)));
$note=trim((string)($settings['note'] ?? ''));
?>
<section class="omg-core-block omg-container-block" style="max-width:<?=e((string)$max)?>px;margin-left:auto;margin-right:auto">
  <?php if($note!==''): ?><small><?=e($note)?></small><?php endif; ?>
</section>
