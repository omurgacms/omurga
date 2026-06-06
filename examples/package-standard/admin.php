<?php
if(!defined('OMURGA_INIT')) exit;
$title = Omurga::getPackageSetting('package-standard', 'title', 'Standart Paket');
?>
<div class="card">
  <h2><?=e($title)?></h2>
  <p class="muted">Bu ekran Paket API ile eklenmiş örnek yönetim sayfasıdır.</p>
</div>
