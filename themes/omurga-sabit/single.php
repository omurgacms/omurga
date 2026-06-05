<?php include __DIR__.'/header.php'; ?>
<main class="container narrow">
  <article class="single">
    <small><?= e($post['category_name'] ?? 'Genel') ?><?php if(!empty($post['published_at'])): ?> · <?= e(date('d.m.Y H:i', strtotime($post['published_at']))) ?><?php endif; ?></small>
    <h1><?= e($post['title'] ?? '') ?></h1>
    <?php if(!empty($post['featured_image'])): ?><img class="cover" src="<?= e(image_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>"><?php endif; ?>
    <div class="content"><?= $post['content'] ?? '' ?></div>
  </article>
  <?= om_comments_list((int)($post['id'] ?? 0)) ?>
  <?= om_comment_form((int)($post['id'] ?? 0)) ?>
</main>
<?php include __DIR__.'/footer.php'; ?>
