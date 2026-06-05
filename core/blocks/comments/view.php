<?php
$post=$context['post'] ?? $post ?? null;
$postId=is_array($post) ? (int)($post['id'] ?? 0) : 0;
$title=trim((string)($settings['title'] ?? ''));
if($postId<=0){
    echo '<section class="omg-core-block omg-comments-block"><p class="omg-block-empty">'.e(om_t('comments.disabled','Yorumlar kapali.')).'</p></section>';
    return;
}
$list=!empty($settings['show_list']) ? om_comments_list($postId) : '';
$form=!empty($settings['show_form']) ? om_comment_form($postId) : '';
?>
<section class="omg-core-block omg-comments-block">
  <?php if($title!==''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <?php if($list!=='' || $form!==''): ?><?=$list?><?=$form?><?php else: ?><p class="omg-block-empty"><?=e(om_t('comments.disabled','Yorumlar kapali.'))?></p><?php endif; ?>
</section>
