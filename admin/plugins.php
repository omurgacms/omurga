<?php
require '_layout.php';
require_cap('plugins.manage');
?>
<div class="page-head"><div><h1>Paketler</h1><p>Omurga v4 ile eski <code>plugins/</code> sistemi pasif hale getirildi. Yeni geliştirmeler resmi <code>packages/</code> sistemiyle yapılır.</p></div></div>
<div class="card">
  <h2>Eski Eklenti Sistemi Pasif</h2>
  <p class="muted">Bu ekran geriye uyumluluk için korunur. Yeni paket yükleme, etkinleştirme ve yönetim işlemleri Paketler ekranından yapılmalıdır.</p>
  <a class="btn primary" href="packages.php">Paketler ekranına git</a>
</div>
<?php require '_footer.php'; ?>
