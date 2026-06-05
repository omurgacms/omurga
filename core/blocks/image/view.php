<?php
$title=trim((string)($settings['title'] ?? ''));
$image=trim((string)($settings['image'] ?? ''));
$alt=trim((string)($settings['alt'] ?? $title));
$link=trim((string)($settings['link'] ?? ''));
if($image==='') return;
$src=image_url($image);
$img='<img src="'.e($src).'" alt="'.e($alt).'">';
if($link!==''){
    $href=str_starts_with($link,'http') ? $link : omurga_url($link);
    $img='<a href="'.e($href).'">'.$img.'</a>';
}
?>
<figure class="omg-core-block omg-image-block">
  <?=$img?>
  <?php if($title!==''): ?><figcaption><?=e($title)?></figcaption><?php endif; ?>
</figure>
