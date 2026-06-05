<?php
require '_layout.php';
require_cap('blocks.manage');

$blocks = omurga_available_blocks();
$warnings = omurga_block_registry_warnings();
$sourceOrder = ['Çekirdek'=>0,'Tema'=>1,'Paket'=>2,'Özel'=>3,'Kayıtlı'=>4];
$sourceCounts = [];
$categoryCounts = [];
$groups = [];

foreach ($blocks as $slug => $def) {
    $def['slug'] = $def['slug'] ?? $slug;
    $source = omurga_block_source_label($def);
    $category = trim((string)($def['category'] ?? 'Genel')) ?: 'Genel';
    $def['_source_label'] = $source;
    $def['_category_label'] = $category;
    $sourceCounts[$source] = ($sourceCounts[$source] ?? 0) + 1;
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    $groups[$source][$category][] = $def;
}

uksort($groups, function($a, $b) use ($sourceOrder) {
    return ($sourceOrder[$a] ?? 99) <=> ($sourceOrder[$b] ?? 99) ?: strnatcasecmp($a, $b);
});
foreach ($groups as &$cats) {
    ksort($cats, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($cats as &$items) {
        usort($items, fn($a, $b) => strnatcasecmp((string)($a['name'] ?? $a['slug']), (string)($b['name'] ?? $b['slug'])));
    }
}
unset($cats, $items);

$totalBlocks = count($blocks);
$coreCount = $sourceCounts['Çekirdek'] ?? 0;
$themeCount = $sourceCounts['Tema'] ?? 0;
$packageCount = $sourceCounts['Paket'] ?? 0;
$customCount = $sourceCounts['Özel'] ?? 0;
?>
<style>
.block-center-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin:16px 0}.block-stat{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px}.block-stat strong{display:block;font-size:28px;line-height:1}.block-stat span{display:block;color:var(--muted);font-weight:700;margin-top:6px}.block-center-note{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center}.block-source-title{display:flex;align-items:center;gap:10px;justify-content:space-between;margin-bottom:12px}.block-source-title h2{margin:0}.block-category-title{display:flex;align-items:center;gap:8px;margin:16px 0 8px}.block-category-title h3{margin:0;font-size:16px}.source-chip,.status-chip{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0}.source-chip.core{background:#ecfdf5;color:#047857;border-color:#bbf7d0}.source-chip.theme{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}.source-chip.package{background:#fff7ed;color:#c2410c;border-color:#fed7aa}.source-chip.custom{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}.status-chip.active{background:#dcfce7;color:#166534;border-color:#bbf7d0}.block-mini{color:#64748b;font-size:12px}.block-table-wrap{overflow:auto;border:1px solid #e5e7eb;border-radius:14px}.block-table{width:100%;border-collapse:collapse;background:#fff}.block-table th,.block-table td{padding:12px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:top}.block-table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}.block-table tr:last-child td{border-bottom:0}.block-name strong{display:block}.block-name small{display:block;color:#64748b;margin-top:3px;max-width:420px}.block-doc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}.block-doc-grid code{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:2px 6px}@media(max-width:760px){.block-center-note{grid-template-columns:1fr}.block-table th{display:none}.block-table tr{display:block;border-bottom:1px solid #e5e7eb}.block-table td{display:flex;justify-content:space-between;gap:12px;border-bottom:0}.block-table td:before{content:attr(data-label);font-weight:800;color:#64748b}.block-table td.block-name{display:block}.block-table td.block-name:before{display:none}}
</style>

<div class="toolbar">
  <div>
    <h1>Blok Merkezi</h1>
    <p class="muted">Çekirdek, tema, paket ve özel blokları tek ekranda düzenli şekilde görüntüle.</p>
  </div>
  <a class="btn light" href="layout.php">Sayfa Tasarımcısına Git</a>
</div>

<div class="block-center-stats">
  <div class="block-stat"><strong><?=e((string)$totalBlocks)?></strong><span>Toplam blok</span></div>
  <div class="block-stat"><strong><?=e((string)$coreCount)?></strong><span>Çekirdek blok</span></div>
  <div class="block-stat"><strong><?=e((string)$themeCount)?></strong><span>Tema bloğu</span></div>
  <div class="block-stat"><strong><?=e((string)$packageCount)?></strong><span>Paket bloğu</span></div>
  <div class="block-stat"><strong><?=e((string)$customCount)?></strong><span>Özel blok</span></div>
</div>

<div class="layout-help card block-center-note">
  <div>
    <strong>Blok Merkezi ne işe yarar?</strong><br>
    <span class="muted">Buradan blok eklenmez; bloklar Sayfa Tasarımcısı içinde kullanılır. Bu ekran hangi bloğun nereden geldiğini, hangi kategoride olduğunu ve hangi alanlarda kullanılabileceğini gösterir.</span>
  </div>
  <span class="source-chip core">Varsayılan blokların kaynağı: Çekirdek</span>
</div>

<?php if($warnings): ?>
  <div class="alert error"><strong>Blok uyarıları</strong><br><?=e(implode(' | ', array_unique($warnings)))?></div>
<?php endif; ?>

<?php foreach($groups as $source => $categories): ?>
<section class="card blocks-list-card">
  <div class="block-source-title">
    <h2><?=e($source)?> Blokları</h2>
    <span class="source-chip <?=e($source==='Çekirdek'?'core':($source==='Tema'?'theme':($source==='Paket'?'package':($source==='Özel'?'custom':''))))?>"><?=e((string)($sourceCounts[$source] ?? 0))?> blok</span>
  </div>

  <?php foreach($categories as $category => $items): ?>
    <div class="block-category-title">
      <h3><?=e($category)?></h3>
      <span class="status-chip active"><?=count($items)?> aktif</span>
    </div>
    <div class="block-table-wrap">
      <table class="block-table">
        <thead><tr><th>Blok</th><th>Kaynak</th><th>Slug</th><th>Kullanım Alanı</th><th>Context</th><th>Ayar</th><th>Dosya / Render</th></tr></thead>
        <tbody>
        <?php foreach($items as $b):
          $fileLabel = isset($b['view']) ? str_replace(OMURGA_ROOT.'/', '', (string)$b['view']) : (!empty($b['render_callback']) ? 'callback' : 'çekirdek');
          $settingsCount = count($b['settings_schema'] ?? $b['settings'] ?? []);
          $usage = implode(', ', $b['usage'] ?? []);
          $contexts = implode(', ', $b['allowed_contexts'] ?? []);
          $srcClass = $source==='Çekirdek'?'core':($source==='Tema'?'theme':($source==='Paket'?'package':($source==='Özel'?'custom':'')));
        ?>
          <tr>
            <td class="block-name" data-label="Blok"><strong><?=e($b['name'] ?? $b['slug'])?></strong><small><?=e($b['description'] ?? '')?></small></td>
            <td data-label="Kaynak"><span class="source-chip <?=e($srcClass)?>"><?=e($source)?></span></td>
            <td data-label="Slug"><code><?=e($b['slug'])?></code></td>
            <td data-label="Kullanım"><span class="block-mini"><?=e($usage ?: '-')?></span></td>
            <td data-label="Context"><span class="block-mini"><?=e($contexts ?: '-')?></span></td>
            <td data-label="Ayar"><?=e((string)$settingsCount)?></td>
            <td data-label="Dosya"><small><?=e($fileLabel)?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>

<div class="card">
  <h2>Blok kaynak standardı</h2>
  <div class="block-doc-grid">
    <p><span class="source-chip core">Çekirdek</span><br><span class="muted">Omurga ile gelen varsayılan bloklar. Logo, Menü, Arama, Metin, Görsel, Buton, Son İçerikler gibi temel bloklar burada görünür.</span></p>
    <p><span class="source-chip theme">Tema</span><br><span class="muted">Aktif temanın <code>blocks/</code> klasöründen gelen tema özel blokları.</span></p>
    <p><span class="source-chip package">Paket</span><br><span class="muted">Aktif paketlerin eklediği bloklar. Haber, belediye veya servis blokları bu yapıya uygundur.</span></p>
    <p><span class="source-chip custom">Özel</span><br><span class="muted"><code>storage/blocks</code> klasöründen gelen siteye özel bloklar.</span></p>
  </div>
</div>
<?php require '_footer.php'; ?>
