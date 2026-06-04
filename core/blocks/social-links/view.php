<?php
$lines=array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)($settings['links'] ?? '')) ?: [])));
if(!$lines) return;
?>
<nav class="omg-core-block omg-social-links">
  <?php foreach($lines as $line): [$label,$url]=array_pad(array_map('trim', explode('|',$line,2)),2,''); if($label==='' || $url==='') continue; ?><a href="<?=e($url)?>" target="_blank" rel="noopener"><?=e($label)?></a><?php endforeach; ?>
</nav>
