<?php if (!defined('OMURGA_ROOT')) { exit; } include omurga_theme_file('header.php'); ?>
<main class="kv1-page-wrap">
  <article class="kv1-page-card">
    <div class="kv1-section-tag">Sayfa</div>
    <h1><?=e($post['title'] ?? $page['title'] ?? $title ?? 'Sayfa')?></h1>
    <?php $content=$post['content'] ?? $page['content'] ?? ''; ?>
    <div class="kv1-content"><?=kv1_render_content($content)?></div>
  </article>
</main>
<?php include omurga_theme_file('footer.php'); ?>
