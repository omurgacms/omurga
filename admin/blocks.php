<?php
require '_layout.php';
require_cap('blocks.manage');

$blocks = omurga_available_blocks();
$warnings = omurga_block_registry_warnings();
$sourceOrder = ['Çekirdek'=>0,'Tema'=>1,'Paket'=>2,'Özel'=>3,'Kayıtlı'=>4];
$sourceCounts = [];
$categoryCounts = [];
$items = [];

foreach ($blocks as $slug => $def) {
    $def['slug'] = $def['slug'] ?? $slug;
    $source = omurga_block_source_label($def);
    $category = trim((string)($def['category'] ?? 'Genel')) ?: 'Genel';
    $def['_source_label'] = $source;
    $def['_category_label'] = $category;
    $sourceCounts[$source] = ($sourceCounts[$source] ?? 0) + 1;
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    $items[] = $def;
}

usort($items, function($a, $b) use ($sourceOrder) {
    $sa = $a['_source_label'] ?? 'Kayıtlı';
    $sb = $b['_source_label'] ?? 'Kayıtlı';
    $ca = $a['_category_label'] ?? 'Genel';
    $cb = $b['_category_label'] ?? 'Genel';
    $sourceCompare = (($sourceOrder[$sa] ?? 99) <=> ($sourceOrder[$sb] ?? 99));
    if ($sourceCompare !== 0) return $sourceCompare;
    $catCompare = strnatcasecmp($ca, $cb);
    if ($catCompare !== 0) return $catCompare;
    return strnatcasecmp((string)($a['name'] ?? $a['slug']), (string)($b['name'] ?? $b['slug']));
});

ksort($categoryCounts, SORT_NATURAL | SORT_FLAG_CASE);
$totalBlocks = count($items);
$coreCount = $sourceCounts['Çekirdek'] ?? 0;
$themeCount = $sourceCounts['Tema'] ?? 0;
$packageCount = $sourceCounts['Paket'] ?? 0;
$customCount = $sourceCounts['Özel'] ?? 0;
?>
<style>
.block-compact-wrap{display:grid;gap:12px}.block-compact-toolbar{position:sticky;top:74px;z-index:20;background:rgba(243,245,248,.96);backdrop-filter:blur(10px);border:1px solid #e5e7eb;border-radius:18px;padding:12px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.block-compact-top{display:grid;grid-template-columns:minmax(220px,1fr) auto;gap:10px;align-items:center}.block-search{width:100%;border:1px solid #e5e7eb;border-radius:12px;padding:11px 13px;background:#fff;font-weight:700}.block-filter-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.filter-chip{border:1px solid #e5e7eb;background:#fff;color:#334155;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:900;cursor:pointer}.filter-chip.is-active{background:#111827;color:#fff;border-color:#111827}.block-mini-stats{display:flex;gap:8px;flex-wrap:wrap}.block-mini-stat{background:#fff;border:1px solid #e5e7eb;border-radius:13px;padding:8px 10px;min-width:92px}.block-mini-stat strong{display:block;font-size:18px;line-height:1}.block-mini-stat span{display:block;color:#64748b;font-size:11px;font-weight:800;margin-top:2px}.block-grid-compact{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:10px}.block-card-compact{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:12px;box-shadow:0 6px 16px rgba(15,23,42,.045);min-width:0}.block-card-compact[hidden]{display:none!important}.block-card-head{display:flex;justify-content:space-between;gap:8px;align-items:flex-start}.block-card-title{min-width:0}.block-card-title strong{display:block;font-size:14px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.block-card-title small{display:block;color:#64748b;font-size:11px;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.source-chip,.status-chip{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;white-space:nowrap}.source-chip.core{background:#ecfdf5;color:#047857;border-color:#bbf7d0}.source-chip.theme{background:#fff7ed;color:#ea580c;border-color:#fed7aa}.source-chip.package{background:#fff7ed;color:#c2410c;border-color:#fed7aa}.source-chip.custom{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}.block-card-meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:9px}.block-card-meta code{font-size:11px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:4px 6px;max-width:100%;overflow:hidden;text-overflow:ellipsis}.block-card-desc{color:#475569;font-size:12px;line-height:1.45;margin:9px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:34px}.block-details{margin-top:8px}.block-details summary{cursor:pointer;color:#475569;font-size:12px;font-weight:900}.block-detail-grid{display:grid;gap:5px;margin-top:8px;color:#64748b;font-size:11px}.block-detail-grid div{display:flex;justify-content:space-between;gap:10px;border-top:1px dashed #e5e7eb;padding-top:5px}.block-empty-result{display:none;background:#fff;border:1px dashed #cbd5e1;border-radius:16px;padding:24px;text-align:center;color:#64748b}.block-empty-result.is-visible{display:block}.block-doc-compact{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}.block-doc-compact p{margin:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:12px}.block-doc-compact code{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:2px 6px}@media(max-width:760px){.block-compact-toolbar{top:70px;border-radius:14px}.block-compact-top{grid-template-columns:1fr}.block-mini-stat{flex:1}.block-grid-compact{grid-template-columns:1fr}.block-filter-row{max-height:112px;overflow:auto}}
</style>

<div class="toolbar compact-toolbar-page">
  <div>
    <h1>Blok Merkezi</h1>
    <p class="muted">Blokları hızlı ara, kaynak/kategoriye göre filtrele ve Sayfa Tasarımcısında kullan.</p>
  </div>
  <a class="btn light" href="layout.php">Sayfa Tasarımcısına Git</a>
</div>

<?php if($warnings): ?>
  <div class="alert error"><strong>Blok uyarıları</strong><br><?=e(implode(' | ', array_unique($warnings)))?></div>
<?php endif; ?>

<div class="block-compact-wrap" id="blockCompactApp">
  <div class="block-compact-toolbar">
    <div class="block-compact-top">
      <input class="block-search" type="search" id="blockSearch" placeholder="Blok ara: logo, menü, haber, görsel..." autocomplete="off">
      <div class="block-mini-stats">
        <div class="block-mini-stat"><strong><?=e((string)$totalBlocks)?></strong><span>Toplam</span></div>
        <div class="block-mini-stat"><strong><?=e((string)$coreCount)?></strong><span>Çekirdek</span></div>
        <div class="block-mini-stat"><strong><?=e((string)$themeCount)?></strong><span>Tema</span></div>
        <div class="block-mini-stat"><strong><?=e((string)$packageCount)?></strong><span>Paket</span></div>
      </div>
    </div>
    <div class="block-filter-row" aria-label="Kaynak filtreleri">
      <button type="button" class="filter-chip is-active" data-filter-type="source" data-filter-value="">Tümü</button>
      <?php foreach($sourceCounts as $source => $count): ?>
        <button type="button" class="filter-chip" data-filter-type="source" data-filter-value="<?=e(mb_strtolower($source,'UTF-8'))?>"><?=e($source)?> · <?=e((string)$count)?></button>
      <?php endforeach; ?>
    </div>
    <div class="block-filter-row" aria-label="Kategori filtreleri">
      <button type="button" class="filter-chip is-active" data-filter-type="category" data-filter-value="">Tüm kategoriler</button>
      <?php foreach($categoryCounts as $category => $count): ?>
        <button type="button" class="filter-chip" data-filter-type="category" data-filter-value="<?=e(mb_strtolower($category,'UTF-8'))?>"><?=e($category)?> · <?=e((string)$count)?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="block-grid-compact" id="blockGridCompact">
    <?php foreach($items as $b):
      $source = $b['_source_label'] ?? 'Kayıtlı';
      $category = $b['_category_label'] ?? 'Genel';
      $fileLabel = isset($b['view']) ? str_replace(OMURGA_ROOT.'/', '', (string)$b['view']) : (!empty($b['render_callback']) ? 'callback' : 'çekirdek');
      $settingsCount = count($b['settings_schema'] ?? $b['settings'] ?? []);
      $usage = implode(', ', $b['usage'] ?? []);
      $contexts = implode(', ', $b['allowed_contexts'] ?? []);
      $srcClass = $source==='Çekirdek'?'core':($source==='Tema'?'theme':($source==='Paket'?'package':($source==='Özel'?'custom':'')));
      $searchText = mb_strtolower(trim(($b['name'] ?? '').' '.($b['slug'] ?? '').' '.($b['description'] ?? '').' '.$source.' '.$category.' '.$usage.' '.$contexts),'UTF-8');
    ?>
      <article class="block-card-compact" data-source="<?=e(mb_strtolower($source,'UTF-8'))?>" data-category="<?=e(mb_strtolower($category,'UTF-8'))?>" data-search="<?=e($searchText)?>">
        <div class="block-card-head">
          <div class="block-card-title">
            <strong title="<?=e($b['name'] ?? $b['slug'])?>"><?=e($b['name'] ?? $b['slug'])?></strong>
            <small><?=e($category)?></small>
          </div>
          <span class="source-chip <?=e($srcClass)?>"><?=e($source)?></span>
        </div>
        <div class="block-card-meta">
          <code><?=e($b['slug'])?></code>
          <span class="status-chip"><?=e((string)$settingsCount)?> ayar</span>
        </div>
        <p class="block-card-desc"><?=e($b['description'] ?? 'Açıklama yok.')?></p>
        <details class="block-details">
          <summary>Detayları göster</summary>
          <div class="block-detail-grid">
            <div><span>Kullanım</span><b><?=e($usage ?: '-')?></b></div>
            <div><span>Context</span><b><?=e($contexts ?: '-')?></b></div>
            <div><span>Dosya</span><b><?=e($fileLabel)?></b></div>
          </div>
        </details>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="block-empty-result" id="blockEmptyResult">Aramana uygun blok bulunamadı.</div>
</div>

<div class="card compact-card">
  <h2>Blok kaynak standardı</h2>
  <div class="block-doc-compact">
    <p><span class="source-chip core">Çekirdek</span><br><span class="muted">Omurga ile gelen varsayılan bloklar.</span></p>
    <p><span class="source-chip theme">Tema</span><br><span class="muted">Aktif temanın <code>blocks/</code> klasöründen gelen bloklar.</span></p>
    <p><span class="source-chip package">Paket</span><br><span class="muted">Aktif paketlerin eklediği bloklar.</span></p>
    <p><span class="source-chip custom">Özel</span><br><span class="muted"><code>storage/blocks</code> klasöründen gelen siteye özel bloklar.</span></p>
  </div>
</div>

<script>
(function(){
  var app = document.getElementById('blockCompactApp');
  if(!app) return;
  var search = document.getElementById('blockSearch');
  var cards = Array.prototype.slice.call(document.querySelectorAll('.block-card-compact'));
  var empty = document.getElementById('blockEmptyResult');
  var state = {source:'', category:'', q:''};
  function norm(v){return (v || '').toString().toLocaleLowerCase('tr-TR').trim();}
  function apply(){
    state.q = norm(search ? search.value : '');
    var shown = 0;
    cards.forEach(function(card){
      var okSource = !state.source || card.getAttribute('data-source') === state.source;
      var okCategory = !state.category || card.getAttribute('data-category') === state.category;
      var okSearch = !state.q || norm(card.getAttribute('data-search')).indexOf(state.q) !== -1;
      var show = okSource && okCategory && okSearch;
      card.hidden = !show;
      if(show) shown++;
    });
    if(empty) empty.classList.toggle('is-visible', shown === 0);
  }
  document.querySelectorAll('.filter-chip').forEach(function(btn){
    btn.addEventListener('click', function(){
      var type = btn.getAttribute('data-filter-type');
      var value = btn.getAttribute('data-filter-value') || '';
      state[type] = value;
      document.querySelectorAll('.filter-chip[data-filter-type="'+type+'"]').forEach(function(b){b.classList.toggle('is-active', b === btn);});
      apply();
    });
  });
  if(search) search.addEventListener('input', apply);
  apply();
})();
</script>
<?php require '_footer.php'; ?>
