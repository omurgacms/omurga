<?php
require '_layout.php';

$theme = omurga_active_theme();
if(function_exists('omurga_load_active_theme_functions')) omurga_load_active_theme_functions();
$pageId = (string)($_GET['page'] ?? '');
$page = function_exists('omurga_find_theme_admin_page') ? omurga_find_theme_admin_page($theme, $pageId) : null;
$cap = $page['cap'] ?? 'design.manage';
require_cap($cap);
$meta = omurga_theme_meta($theme);
?>
<div class="page-head"><div><h1><?=e($page['title'] ?? 'Tema Paneli')?></h1><p><?=e($page ? 'Aktif temanın kendi paneli.' : 'İstenen tema paneli bulunamadı.')?></p></div><a class="btn" href="design.php">Tema Ayarları</a></div>
<?php if(!$page): ?>
  <div class="alert error">Tema paneli bulunamadı veya aktif tema bu paneli tanımlamıyor.</div>
<?php else: ?>
  <?php
  try {
      $file = $page['file'] ?? null;
      if (!is_string($file) || $file === '') {
          echo '<div class="alert error">Tema panel dosyası geçersiz.</div>';
      } else {
          $real = realpath($file);
          $themeRoot = realpath(omurga_theme_dir($theme));
          $allowed = $themeRoot && $real && (str_starts_with($real, $themeRoot . DIRECTORY_SEPARATOR) || $real === $themeRoot);
          if (!$allowed || !$real || !is_file($real)) {
              echo '<div class="alert error">Tema panel dosyası güvenli değil veya bulunamadı.</div>';
          } else {
              include $real;
          }
      }
  } catch (Throwable $e) {
      if(function_exists('omurga_write_error')) omurga_write_error($e);
      echo '<div class="alert error">Tema paneli yüklenemedi.</div>';
  }
  ?>
<?php endif; ?>
<?php require '_footer.php'; ?>
