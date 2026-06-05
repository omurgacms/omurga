<?php include __DIR__.'/header.php'; ?>
<main class="container narrow">
  <article class="single">
    <h1><?= e($post['title'] ?? '') ?></h1>
    <div class="content"><?= $post['content'] ?? '' ?></div>
  </article>
</main>
<?php include __DIR__.'/footer.php'; ?>
