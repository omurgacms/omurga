<?php
$settings = $settings ?? ($block['settings'] ?? []);
$title = trim((string)($settings['title'] ?? 'İletişim Formu'));
$formId = trim((string)($settings['form_id'] ?? 'iletisim-formu'));
$showTitle = !empty($settings['show_title']);
?>
<section class="omg-block omg-form-block">
  <?php if ($showTitle && $title !== ''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <?php if (!empty($GLOBALS['formNotice'])): ?><div class="alert success"><?=e((string)$GLOBALS['formNotice'])?></div><?php endif; ?>
  <?=omurga_render_form($formId)?>
</section>
