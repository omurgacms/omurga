<?php
require '_layout.php';
require_cap('layout.manage');
?>
<div class="toolbar"><h1>Bileşenler</h1></div>
<div class="card">
  <h2>Bu ekran şimdilik kullanımdan kaldırıldı</h2>
  <p class="muted">Widget / Sidebar yönetimi bu sürümde aktif kullanılmıyor. Omurga CMS içinde sayfa ve tema yerleşimleri için Bloklar, Düzen ve Tema Ayarları ekranlarını kullanın.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
    <a class="btn primary" href="blocks.php">Bloklara Git</a>
    <a class="btn" href="layout.php">Düzen Ekranına Git</a>
    <a class="btn" href="index.php">Başlangıca Dön</a>
  </div>
</div>
<?php require '_footer.php'; ?>
