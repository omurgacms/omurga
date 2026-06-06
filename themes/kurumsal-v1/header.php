<?php if (!defined('OMURGA_ROOT')) { exit; } ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=e($title ?? setting('site_name','Kurumsal V1'))?></title>
<meta name="description" content="<?=e($meta ?? setting('site_description','Kurumsal web sitesi'))?>">
<?=function_exists('omurga_seo_head') ? omurga_seo_head(['title'=>$title ?? setting('site_name','Kurumsal V1'), 'meta'=>$meta ?? setting('site_description',''), 'canonical'=>$canonical ?? omurga_url(), 'og_image'=>$ogImage ?? '']) : ''?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(kv1_asset('assets/css/style.css'))?>">
<style>:root{--kv1-bg:<?=e(kv1_color('dark_bg','#0a0a0a'))?>;--kv1-accent:<?=e(kv1_color('primary_color','#c8a96e'))?>;--kv1-accent2:<?=e(kv1_color('primary_color','#c8a96e'))?>}</style>
</head>
<body class="<?=function_exists('om_body_class') ? om_body_class('kv1-body') : 'kv1-body'?>">
<nav class="kv1-nav">
  <a href="<?=e(omurga_url())?>" class="kv1-logo"><?php $logo=trim((string)kv1_setting('logo','')); if($logo!==''): ?><img src="<?=e(image_url($logo))?>" alt="<?=e(kv1_logo_text())?>"><?php else: ?><?=e(kv1_logo_text())?> <span><?=e(kv1_logo_accent())?></span><?php endif; ?></a>
  <button class="kv1-menu-btn" type="button" onclick="document.body.classList.toggle('kv1-menu-open')">☰</button>
  <ul class="kv1-menu">
    <?php foreach(kv1_main_menu() as $m): ?><li><a href="<?=e($m['url'] ?? '#')?>"><?=e($m['title'] ?? '')?></a></li><?php endforeach; ?>
    <li><a href="#contact" class="kv1-nav-cta">İletişim</a></li>
  </ul>
</nav>
