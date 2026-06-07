<?php require_once __DIR__.'/functions.php'; ?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=omh_e($title ?? (function_exists('setting') ? setting('site_name','OmHaber') : 'OmHaber'))?></title>
  <link rel="stylesheet" href="<?=omh_e(omh_theme_url('assets/css/style.css'))?>">
  <link rel="stylesheet" href="<?=omh_e(omh_theme_url('assets/css/responsive.css'))?>">
  <style>:root{--omh-primary:<?=omh_e(omh_s('primary_color','#f97316'))?>;--omh-dark:<?=omh_e(omh_s('dark_color','#0f172a'))?>}</style>
</head>
<body class="omh-body omh-width-<?=omh_e(omh_s('site_width','wide'))?>">
<div class="omh-site-shell">
<?=omh_render_theme_block('omhaber-topbar')?>
<?=omh_render_theme_block('omhaber-header')?>
<?=omh_render_theme_block('omhaber-breaking-news', ['limit'=>'5','title'=>'SON DAKİKA'])?>
