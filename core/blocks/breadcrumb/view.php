<?php
$home=trim((string)($settings['home_label'] ?? 'Anasayfa'));
$current=$context['post']['title'] ?? $context['category']['name'] ?? ($context['title'] ?? '');
?>
<nav class="omg-core-block omg-breadcrumb" aria-label="Breadcrumb">
  <a href="<?=e(omurga_url())?>"><?=e($home)?></a>
  <?php if($current!==''): ?><span>/</span><span><?=e($current)?></span><?php endif; ?>
</nav>
