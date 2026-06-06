<!doctype html>
<html lang="<?= e(omurga_site_language()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? setting('site_name','Haber V1')) ?></title>
  <meta name="description" content="<?= e($meta ?? setting('site_description','')) ?>">
  <link rel="stylesheet" href="<?= e(hv1_asset('assets/css/style.css')) ?>">
  <script defer src="<?= e(hv1_asset('assets/js/main.js')) ?>"></script>
</head>
<body class="<?= om_body_class() ?>" style="--hv1-primary:<?=e(hv1_primary())?>">
<div class="nw">
  <div class="topbar">
    <div class="tb-left"><?php foreach(menu_items('top') as $m): ?><a class="tb-link" href="<?=e($m['url'])?>"><?=e($m['title'])?></a><?php endforeach; ?></div>
    <span class="tb-right"><?= e(date('d.m.Y')) ?> · Türkiye</span>
  </div>
  <header class="hdr">
    <div class="hdr-inner">
      <a class="logo" href="<?=e(omurga_url())?>"><?php $logo=hv1_setting('logo',''); if($logo): ?><img class="logo-img" src="<?=e(image_url($logo))?>" alt="<?=e(setting('site_name','Haber V1'))?>"><?php else: ?><?=e(hv1_logo_text())?><?php endif; ?></a>
      <div class="hdr-ad"><?=hv1_ad_slot('header','Header Reklam')?></div>
      <div class="hdr-actions">
        <div class="hdr-date"><?= e(date('d F Y')) ?></div>
        <div class="hdr-btns"><a class="btn-epaper" href="<?=e(hv1_setting('epaper_url','#'))?>">E-Gazete</a><a class="btn-abone" href="<?=e(hv1_setting('subscribe_url','#'))?>">Abone Ol</a></div>
      </div>
    </div>
  </header>
  <nav class="nav"><div class="nav-inner"><?php $menu=menu_items('main'); foreach($menu as $i=>$m): ?><a class="ni <?= $i===0?'a':'' ?>" href="<?=e($m['url'])?>"><?=e($m['title'])?></a><?php endforeach; ?></div></nav>
  <?php if(hv1_setting('show_breaking','1')==='1'): $br=hv1_breaking(); if($br): ?><div class="brk"><div class="brk-inner"><span class="brk-lbl">SON DAKİKA</span><span class="brk-txt"><a href="<?=e(post_url($br[0]))?>"><?=e($br[0]['title'])?></a></span></div></div><?php endif; endif; ?>
