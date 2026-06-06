<?php if (!defined('OMURGA_ROOT')) { exit; } ?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=e($title ?? setting('site_name', tv1_site_label()))?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link rel="stylesheet" href="<?=e(tv1_asset('assets/css/style.css'))?>">
  <style>:root{--tv1-primary:<?=e(tv1_color('primary_color','#0d2240'))?>;--tv1-accent:<?=e(tv1_color('accent_color','#c9a84c'))?>}</style>
</head>
<body class="<?=function_exists('om_body_class') ? om_body_class('theme-topluluk-v1') : 'theme-topluluk-v1'?>">
<div class="wrapper">
  <nav class="dt-nav">
    <a class="dt-logo" href="<?=e(omurga_url())?>" style="text-decoration:none">
      <div class="dt-logo-icon"><i class="ti ti-building-community"></i></div>
      <div class="dt-logo-text"><?=e(tv1_site_label())?><span><?=e(tv1_setting('site_subtitle','Est. 1987'))?></span></div>
    </a>
    <button class="dt-mobile-toggle" type="button" aria-label="Menü" onclick="document.querySelector('.dt-menu-wrap').classList.toggle('is-open')"><i class="ti ti-menu-2"></i></button>
    <div class="dt-menu-wrap">
      <ul class="dt-nav-links">
        <?php foreach(tv1_menu_items('main') as $i=>$m): if(isset($m['active']) && !$m['active']) continue; ?>
          <li class="<?=$i===0?'active':''?>"><a href="<?=e($m['url'] ?? '#')?>"><?=e($m['title'] ?? '')?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="dt-nav-cta-link" href="<?=e(tv1_setting('nav_cta_url','#uyelik'))?>"><?=e(tv1_setting('nav_cta_text','Üye Ol'))?></a>
    </div>
  </nav>
