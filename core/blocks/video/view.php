<?php
$url=trim((string)($settings['url'] ?? ''));
$title=trim((string)($settings['title'] ?? ''));
if($url==='') return;
$embed=function_exists('omurga_video_embed_url') ? omurga_video_embed_url($url) : $url;
?>
<section class="omg-core-block omg-video-block">
  <?php if($title!==''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <?php if(preg_match('~\.mp4($|\?)~i',$embed)): ?><video controls preload="metadata" src="<?=e($embed)?>"></video><?php else: ?><iframe src="<?=e($embed)?>" loading="lazy" allowfullscreen></iframe><?php endif; ?>
</section>
