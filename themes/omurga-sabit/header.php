<!doctype html>
<html lang="<?= e(omurga_site_language()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? setting('site_name','Omurga')) ?></title>
  <meta name="description" content="<?= e($meta ?? setting('site_description','')) ?>">
  <link rel="stylesheet" href="<?= e(omurga_theme_url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= e(omurga_url()) ?>"><?= e(setting('site_name','Omurga')) ?></a>
    <nav class="nav">
      <?php foreach(menu_items() as $m): ?><a href="<?= e($m['url']) ?>"><?= e($m['title']) ?></a><?php endforeach; ?>
      <a href="<?= e(omurga_url('admin')) ?>">Panel</a>
    </nav>
  </div>
</header>
