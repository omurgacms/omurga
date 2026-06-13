<?php require __DIR__.'/_layout.php'; require_cap('seo.view');
omurga_migrate();

function omurga_admin_test_badge(bool $ok): string { return $ok ? '<span class="badge ok">Tamam</span>' : '<span class="badge warn">Kontrol gerekli</span>'; }
function omurga_admin_table_exists(string $name): bool { try{ db()->query('SELECT 1 FROM '.table_name($name).' LIMIT 1'); return true; }catch(Throwable $e){ return false; } }
$storageChecks = [
  'storage/cache' => is_dir(OMURGA_ROOT.'/storage/cache') && is_writable(OMURGA_ROOT.'/storage/cache'),
  'storage/logs' => is_dir(OMURGA_ROOT.'/storage/logs') && is_writable(OMURGA_ROOT.'/storage/logs'),
  'uploads' => is_dir(OMURGA_ROOT.'/uploads') && is_writable(OMURGA_ROOT.'/uploads'),
];
$reserved = function_exists('omurga_reserved_root_slugs') ? omurga_reserved_root_slugs() : [];
$mustReserved = ['atom.xml','sitemap-tags.xml','sitemap-images.xml','sitemap-authors.xml','yazar'];
$missingReserved = array_values(array_diff($mustReserved, $reserved));
$endpoints = [
  'Sitemap Index' => ['url'=>omurga_url('sitemap.xml'), 'active'=>setting('seo_sitemap_enabled','1')==='1'],
  'Yazı Sitemap' => ['url'=>omurga_url('sitemap-posts.xml'), 'active'=>setting('seo_sitemap_enabled','1')==='1'],
  'Kategori Sitemap' => ['url'=>omurga_url('sitemap-categories.xml'), 'active'=>setting('seo_sitemap_enabled','1')==='1'],
  'Etiket Sitemap' => ['url'=>omurga_url('sitemap-tags.xml'), 'active'=>setting('seo_sitemap_tags_enabled','1')==='1'],
  'Görsel Sitemap' => ['url'=>omurga_url('sitemap-images.xml'), 'active'=>setting('seo_sitemap_images_enabled','1')==='1'],
  'Yazar Sitemap' => ['url'=>omurga_url('sitemap-authors.xml'), 'active'=>setting('seo_sitemap_authors_enabled','1')==='1'],
  'News Sitemap' => ['url'=>omurga_url('news-sitemap.xml'), 'active'=>setting('seo_news_sitemap_enabled','1')==='1'],
  'RSS Feed' => ['url'=>omurga_url('feed.xml'), 'active'=>setting('seo_feed_enabled','1')==='1'],
  'Atom Feed' => ['url'=>omurga_url('atom.xml'), 'active'=>setting('seo_atom_enabled','1')==='1'],
  'Google News RSS' => ['url'=>omurga_url('google-news.xml'), 'active'=>setting('seo_google_news_feed_enabled','1')==='1'],
  'Robots.txt' => ['url'=>omurga_url('robots.txt'), 'active'=>true],
];
$key = trim(setting('seo_indexnow_key',''));
$tableChecks = [
  'seo_index_queue' => omurga_admin_table_exists('seo_index_queue'),
  'seo_redirects' => omurga_admin_table_exists('seo_redirects'),
  'seo_404_logs' => omurga_admin_table_exists('seo_404_logs'),
];
?>
<div class="toolbar compact-head">
  <div><h1>SEO Canlı Kurulum Testi</h1><p>SEO endpointleri, yazılabilir klasörler, IndexNow anahtarı, yönlendirme/404 tabloları ve korunan slug kayıtları için hızlı kontrol.</p></div>
  <div><a class="btn light" href="seo.php">← SEO Merkezi</a></div>
</div>
<div class="card compact-panel">
  <div class="compact-panel-head"><h2>Genel Durum</h2></div>
  <div class="stat-grid compact-stats">
    <div class="stat"><b><?=e(OMURGA_VERSION)?></b><span>Omurga Sürümü</span></div>
    <div class="stat"><b><?=e(count(array_filter($storageChecks)))?>/<?=e(count($storageChecks))?></b><span>Yazılabilir Klasör</span></div>
    <div class="stat"><b><?=e(count(array_filter($tableChecks)))?>/<?=e(count($tableChecks))?></b><span>SEO Tablosu</span></div>
    <div class="stat"><b><?=empty($missingReserved)?'Tamam':'Eksik'?></b><span>Korunan Slug</span></div>
  </div>
</div>
<div class="card compact-panel"><div class="compact-panel-head"><h2>Endpoint Linkleri</h2></div>
  <div class="compact-table-wrap"><table class="table compact-table"><thead><tr><th>Endpoint</th><th>Durum</th><th>Adres</th></tr></thead><tbody>
  <?php foreach($endpoints as $name=>$row): ?><tr><td><?=e($name)?></td><td><?=omurga_admin_test_badge((bool)$row['active'])?></td><td><a target="_blank" href="<?=e($row['url'])?>"><?=e($row['url'])?></a></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<div class="grid two">
  <div class="card compact-panel"><div class="compact-panel-head"><h2>Dosya İzinleri</h2></div>
    <table class="table compact-table"><tbody><?php foreach($storageChecks as $path=>$ok): ?><tr><td><?=e($path)?></td><td><?=omurga_admin_test_badge($ok)?></td></tr><?php endforeach; ?></tbody></table>
    <p><small>Canlı hostingde klasörler genelde 755, dosyalar 644 olmalı. storage/cache, storage/logs ve uploads yazılabilir olmalı.</small></p>
  </div>
  <div class="card compact-panel"><div class="compact-panel-head"><h2>IndexNow / Tablolar</h2></div>
    <table class="table compact-table"><tbody>
      <tr><td>IndexNow Anahtarı</td><td><?=omurga_admin_test_badge($key!=='')?></td></tr>
      <?php if($key!==''): ?><tr><td>Anahtar Dosyası</td><td><a target="_blank" href="<?=e(omurga_url($key.'.txt'))?>"><?=e(omurga_url($key.'.txt'))?></a></td></tr><?php endif; ?>
      <?php foreach($tableChecks as $table=>$ok): ?><tr><td><?=e($table)?></td><td><?=omurga_admin_test_badge($ok)?></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<div class="card compact-panel"><div class="compact-panel-head"><h2>Korunan SEO Slugları</h2></div>
  <?php if($missingReserved): ?><div class="alert warning">Eksik korunan slug: <?=e(implode(', ', $missingReserved))?></div><?php else: ?><div class="alert success">Yeni SEO yolları korunan slug listesinde görünüyor.</div><?php endif; ?>
  <p><small>Bu kontrol; atom.xml, sitemap-images.xml, sitemap-authors.xml ve yazar gibi sistem yollarının içerik sluglarıyla çakışmasını engellemek içindir.</small></p>
</div>
<?php require __DIR__.'/_footer.php'; ?>
