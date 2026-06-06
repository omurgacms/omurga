<?php if (!defined('OMURGA_ROOT')) { exit; } include omurga_theme_file('header.php'); ?>
<main class="kv1-page-wrap">
  <article class="kv1-page-card">
    <div class="kv1-section-tag"><?=e($post['category_name'] ?? 'İçerik')?></div>
    <h1><?=e($post['title'] ?? $title ?? 'İçerik')?></h1>
    <?php if(!empty($post['spot'])): ?><p class="kv1-lead"><?=e($post['spot'])?></p><?php endif; ?>
    <?php if(!empty($post['featured_image'])): ?><img class="kv1-cover" src="<?=e(image_url($post['featured_image']))?>" alt="<?=e($post['title'] ?? '')?>"><?php endif; ?>
    <div class="kv1-content"><?=kv1_render_content($post['content'] ?? '')?></div>
  </article>
</main>
<?php include omurga_theme_file('footer.php'); ?>
