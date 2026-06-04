<?php require '_layout.php';
$postsT=table_name('posts'); $catsT=table_name('categories'); $formsT=table_name('forms'); $st=site_type();
$count=function($where='1=1') use($postsT){ return (int)db()->query("SELECT COUNT(*) FROM $postsT WHERE $where")->fetchColumn(); };
$type=primary_content_type();
$latest=db()->query("SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();
$formCount=0; try{$formCount=(int)db()->query("SELECT COUNT(*) FROM $formsT")->fetchColumn();}catch(Throwable $e){}
$quick = 'post-edit.php?type='.primary_content_type();
$today = date('d F Y, l');
?>
<div class="dle-page-head"><div><h1>Ana Sayfa</h1><p><span>⌂</span> Ana Sayfa / Kontrol Paneli</p></div><div class="today">Bugün: <?=e($today)?></div></div>
<div class="dle-stat-row">
  <div class="dle-stat"><span class="sicon blue">▤</span><div><b>Yayındaki <?=e(content_label_plural())?></b><strong><?=$count("status='published'")?></strong><small>Tüm yayınlar</small></div><i>→</i></div>
  <div class="dle-stat"><span class="sicon orange">✎</span><div><b>Taslaklar</b><strong><?=$count("status='draft'")?></strong><small>Taslak içerikler</small></div><i>→</i></div>
  <div class="dle-stat"><span class="sicon green">☆</span><div><b>Medya İçerikleri</b><strong><?=$count("(video_url IS NOT NULL AND video_url<>'') OR (gallery_images IS NOT NULL AND gallery_images<>'')")?></strong><small>Video/Galeri</small></div><i>→</i></div>
  <div class="dle-stat"><span class="sicon purple">▣</span><div><b>Form Başvuruları</b><strong><?=$formCount?></strong><small>Yeni başvurular</small></div><i>→</i></div>
</div>
<div class="dle-panel"><div class="dle-panel-title">Site bölümlerine hızlı erişim</div><div class="dle-quick-grid">
  <a class="dle-quick primary-quick" href="<?=e($quick)?>"><span class="qicon add">✚</span><div><b><?=e(content_quick_add_label())?></b><small>Yeni içerik oluştur, yayın durumunu seç ve görselleri ekle.</small></div></a>
  <a class="dle-quick" href="users.php"><span class="qicon user">♙</span><div><b>Kullanıcı Düzenleme</b><small>Kayıtlı kullanıcıların yönetimi, profillerinin düzenlenmesi ve hesapların engellenmesi</small></div></a>
  <a class="dle-quick" href="settings.php"><span class="qicon gear">⚙</span><div><b>Sistem Ayarları</b><small>Genel site parametreleri, e-posta, güvenlik seçenekleri ve diğer ayarlar</small></div></a>
  <a class="dle-quick" href="ads.php"><span class="qicon ad">AD</span><div><b>Reklam Alanları</b><small>Sitede yayımlanan reklam alanlarının eklenmesi ve yönetimi</small></div></a>
  <a class="dle-quick" href="pages.php"><span class="qicon doc">▤</span><div><b>Statik Sayfalar</b><small>Hakkımızda, iletişim gibi statik sayfaların oluşturulması ve düzenlenmesi</small></div></a>
  <a class="dle-quick" href="media.php"><span class="qicon media">▧</span><div><b>Medya Yönetimi</b><small>Görsel, video ve dosyaların yüklenmesi, yönetimi ve düzenlenmesi</small></div></a>
  <a class="dle-quick" href="seo.php"><span class="qicon seo">↗</span><div><b>SEO Araçları</b><small>Site haritası, yönlendirmeler, meta ayarları ve SEO araçları</small></div></a>
  <a class="dle-quick" href="backups.php"><span class="qicon backup">▰</span><div><b>Yedekleme</b><small>Veritabanı ve dosya yedekleri oluşturma ve geri yükleme</small></div></a>
  <a class="dle-quick" href="system.php"><span class="qicon update">↻</span><div><b>Güncellemeler</b><small>Omurga çekirdeği, eklentiler ve tema güncelleme kontrolü</small></div></a>
</div></div>
<div class="dle-two-col">
  <div class="dle-panel"><div class="dle-panel-title">Genel site istatistikleri</div><div class="stats-table-wrap"><table class="stats-table">
    <tr><td>Site çalışma modu:</td><td>Açık</td></tr><tr><td>Toplam içerik sayısı:</td><td><?=$count('1=1')?></td></tr><tr><td>Yayındaki içerikler:</td><td><?=$count("status='published'")?></td></tr><tr><td>Taslak içerikler:</td><td><?=$count("status='draft'")?></td></tr><tr><td>Toplam form başvurusu:</td><td><?=$formCount?></td></tr><tr><td>Kayıtlı kullanıcı sayısı:</td><td><?= (int)db()->query('SELECT COUNT(*) FROM '.table_name('users'))->fetchColumn() ?></td></tr><tr><td>Omurga sürümü:</td><td><?=OMURGA_VERSION?></td></tr><tr><td>Disk kullanımı:</td><td>Kontrol ediliyor</td></tr>
  </table><div class="donut"><span></span><b>Omurga</b><small>Yayın yönetimi</small></div></div></div>
  <div class="dle-panel"><div class="dle-panel-title flex-title"><span>Son Eklenen İçerikler</span><a href="posts.php">Tümünü Gör</a></div><div class="recent-list"><?php foreach($latest as $p): ?><a href="post-edit.php?id=<?=$p['id']?>"><span class="file-ico">▤</span><div><b><?=e($p['title'])?></b><small><?=e(type_label($p['type']))?> · <?=e($p['category_name'] ?? '-')?></small></div><em><?=e(substr($p['created_at'],0,10))?></em></a><?php endforeach; ?></div></div>
</div>
<?php require '_footer.php'; ?>
