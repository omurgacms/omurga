<?php include __DIR__.'/header.php'; ?>
<main class="container">
  <section class="hero">
      <span>Omurga CMS 1.0.2.2 Beta</span>
    <h1><?= e(setting('site_name','Omurga')) ?></h1>
    <p><?= e(setting('site_description','Sade, genişletilebilir ve hızlı içerik yönetim sistemi.')) ?></p>
  </section>
  <section class="grid">
    <?php foreach(($latest ?? $posts ?? []) as $item): ?>
      <article class="card">
        <?php if(!empty($item['featured_image'])): ?><a href="<?= e(post_url($item)) ?>"><img src="<?= e(image_url($item['featured_image'])) ?>" alt="<?= e($item['title']) ?>"></a><?php endif; ?>
        <div class="card-body">
          <small><?= e($item['category_name'] ?? 'Genel') ?></small>
          <h2><a href="<?= e(post_url($item)) ?>"><?= e($item['title']) ?></a></h2>
          <p><?= e(excerpt($item['spot'] ?: ($item['content'] ?? ''), 130)) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
<?php include __DIR__.'/footer.php'; ?>
