<?php
require '_layout.php';
require_cap('layout.manage');
?>
<div class="toolbar"><h1>Bileşenler</h1></div>
<div class="card">
  <h2>Bileşenler Blok Merkezi ile birleştirildi</h2>
  <p class="muted">Omurga CMS içinde ayrı bir widget sistemi kullanılmıyor. Sidebar, header, footer ve sayfa alanları için Blok Merkezi, Sayfa Düzeni ve Üst / Alt Alanlar ekranlarını kullanın.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
    <a class="btn primary" href="blocks.php">Bloklara Git</a>
    <a class="btn" href="layout.php">Sayfa Düzenine Git</a>
    <a class="btn" href="layout-header-footer.php">Üst / Alt Alanlara Git</a>
    <a class="btn" href="index.php">Başlangıca Dön</a>
  </div>
</div>
<?php require '_footer.php'; ?>
