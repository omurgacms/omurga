<?php include __DIR__.'/header.php'; ?>
<main class="container">
  <section class="page-title">
    <h1><?= e($category['name'] ?? 'Kategori') ?></h1>
    <?php if(!empty($category['description'])): ?><p><?= e($category['description']) ?></p><?php endif; ?>
  </section>
  <section class="grid">
    <?php foreach(($posts ?? []) as $item): ?>
      <article class="card"><div class="card-body"><small><?= e($item['category_name'] ?? '') ?></small><h2><a href="<?= e(post_url($item)) ?>"><?= e($item['title']) ?></a></h2><p><?= e(excerpt($item['spot'] ?: ($item['content'] ?? ''), 130)) ?></p></div></article>
    <?php endforeach; ?>
  </section>
</main>
<?php include __DIR__.'/footer.php'; ?>
