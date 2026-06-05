<?php
require '_layout.php';
require_cap('users.manage');
$theme=omurga_active_theme();
$meta=omurga_theme_meta($theme);
$templates=omurga_theme_templates($theme);
$groupLabels=['page'=>'Sayfa Şablonları','single'=>'Yazı Detay Şablonları'];
?>
<div class="toolbar"><div><h1>Şablonlar</h1><p class="muted">Aktif tema: <b><?=e($meta['name'] ?? $theme)?></b>. Sabit sayfa ve yazı tasarımları temadan gelir.</p></div><a class="btn light" href="themes.php">Temalara Dön</a></div>
<div class="layout-help card"><strong>Şablon mantığı:</strong> Tema genel görünümü belirler, Düzen blok yerleşimini belirler, Şablon ise tek bir sayfa veya yazının nasıl görüneceğini belirler. İçerik düzenleme ekranında sağdaki <b>Tasarım Şablonu</b> kutusundan seçim yapılır.</div>
<div class="template-groups">
<?php foreach($groupLabels as $group=>$label): ?>
  <section class="card template-group">
    <h2><?=e($label)?></h2>
    <p class="muted"><?= $group==='page' ? 'Hakkımızda, iletişim, hizmet ve özel sayfalar için kullanılır.' : 'Yazı ve diğer içerik detayları için kullanılır.' ?></p>
    <div class="template-grid">
      <?php foreach(($templates[$group] ?? []) as $key=>$tpl): ?>
        <article class="template-card <?=!empty($tpl['exists'])?'':'missing'?>">
          <div class="template-badge"><?=!empty($tpl['exists'])?'Hazır':'Dosya Yok'?></div>
          <h3><?=e($tpl['name'] ?? $key)?></h3>
          <small><?=e($key)?> · <?=e($tpl['file'] ?? '')?></small>
          <?php if(!empty($tpl['description'])): ?><p><?=e($tpl['description'])?></p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>
<div class="card"><h2>Tema geliştirici notu</h2><p>Şablonlar tema klasöründeki <code>theme.json</code> içinde tanımlanır. Dosyalar genellikle <code>templates/</code> klasörü altında tutulur.</p><pre>{
  "templates": {
    "page": {
      "fullwidth": {"name":"Tam Genişlik", "file":"templates/page-fullwidth.php"}
    },
    "single": {
      "wide": {"name":"Geniş Görselli", "file":"templates/single-wide.php"}
    }
  }
}</pre></div>
<?php require '_footer.php'; ?>
