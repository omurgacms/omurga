<?php
require '_layout.php';
require_cap('plugins.manage');

$plugin = $_GET['plugin'] ?? '';
$pageId = $_GET['page'] ?? '';
$page = function_exists('omurga_find_plugin_admin_page') ? omurga_find_plugin_admin_page((string)$plugin, (string)$pageId) : null;
?>
<div class="page-head"><div><h1><?=e($page['title'] ?? 'Paket Sayfası')?></h1><p><?=e($page ? 'Paket yönetim sayfası.' : 'İstenen paket sayfası bulunamadı.')?></p></div><a class="btn" href="packages.php">Paketler</a></div>
<?php if(!$page): ?>
  <div class="alert error">Paket sayfası bulunamadı veya paket aktif değil.</div>
<?php else: ?>
  <?php
  try {
      $file = $page['file'] ?? null;
      if (is_callable($file)) {
          echo (string)call_user_func($file, $page);
      } elseif (is_string($file)) {
          if (!preg_match('~^(?:[A-Za-z]:)?[\\/]~', $file)) {
              $file = OMURGA_ROOT . '/' . ltrim($file, '/');
          }
          $real = realpath($file);
          $allowedRoots = [realpath(OMURGA_ROOT . '/packages')];
          $allowed = false;
          foreach ($allowedRoots as $root) {
              if ($root && $real && str_starts_with($real, $root . DIRECTORY_SEPARATOR)) { $allowed = true; break; }
          }
          if (!$allowed || !$real || !is_file($real)) {
              echo '<div class="alert error">Paket yönetim dosyası güvenli değil veya bulunamadı.</div>';
          } else {
              include $real;
          }
      } else {
          echo '<div class="alert error">Paket yönetim sayfası geçersiz.</div>';
      }
  } catch (Throwable $e) {
      omurga_write_error($e);
      echo '<div class="alert error">Paket sayfası yüklenemedi.</div>';
  }
  ?>
<?php endif; ?>
<?php require '_footer.php'; ?>
