<?php
require '_layout.php';
require_cap('blocks.manage');
$blocks=omurga_available_blocks();
$warnings=omurga_block_registry_warnings();
$groups=[];
foreach($blocks as $slug=>$def){ $groups[omurga_block_source_label($def)][]=$def; }
?>
<div class="toolbar"><div><h1>Bloklar</h1><p class="muted">Sistem, tema ve özel blokları tek ekranda gör. Özel bloklar <code>storage/blocks</code> klasöründen gelir.</p></div><a class="btn light" href="layout.php">Düzene Git</a></div>
<div class="layout-help card"><strong>Blok kaynakları:</strong> Sistem blokları Omurga çekirdeğinden, Tema blokları aktif temadan, Paket blokları aktif paketlerden, Özel bloklar <code>storage/blocks</code> klasöründen okunur. Gelişmiş bloklarda <code>block.json</code>, basit bloklarda tek <code>.php</code> veya <code>.omg</code> dosyası yeterlidir.</div>
<?php if($warnings): ?><div class="alert error"><strong>Blok uyarıları</strong><br><?=e(implode(' | ', array_unique($warnings)))?></div><?php endif; ?>
<?php foreach($groups as $source=>$items): ?>
<section class="card blocks-list-card"><h2><?=e($source)?> Blokları</h2><div class="blocks-table">
  <table><thead><tr><th>Blok</th><th>Slug</th><th>Kategori</th><th>Kullanım Alanı</th><th>Context</th><th>Ayar</th><th>Dosya</th></tr></thead><tbody>
  <?php foreach($items as $b): ?>
    <tr><td><strong><?=e($b['name'] ?? $b['slug'])?></strong><br><small><?=e($b['description'] ?? '')?></small></td><td><code><?=e($b['slug'])?></code></td><td><?=e($b['category'] ?? '')?></td><td><?=e(implode(', ', $b['usage'] ?? []))?></td><td><?=e(implode(', ', $b['allowed_contexts'] ?? []))?></td><td><?=count($b['settings_schema'] ?? $b['settings'] ?? [])?></td><td><small><?=e(isset($b['view']) ? str_replace(OMURGA_ROOT.'/', '', $b['view']) : (!empty($b['render_callback']) ? 'callback' : 'çekirdek'))?></small></td></tr>
  <?php endforeach; ?>
  </tbody></table></div></section>
<?php endforeach; ?>
<div class="card"><h2>Özel blok örneği</h2><pre><code>storage/blocks/ornek-blok/
  block.json
  view.php</code></pre><p class="muted">Gelişmiş blok için klasör + block.json kullan. Basit blok için <code>storage/blocks/ornek.php</code> yeterlidir.</p></div>
<?php require '_footer.php'; ?>
