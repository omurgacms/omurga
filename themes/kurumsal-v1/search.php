<?php if (!defined('OMURGA_ROOT')) { exit; } include omurga_theme_file('header.php'); $items=$posts ?? []; ?>
<main class="kv1-page-wrap">
  <section class="kv1-page-card">
    <div class="kv1-section-tag">Arama</div>
    <h1>Arama Sonuçları</h1>
    <form class="kv1-search" action="<?=e(omurga_url('search'))?>"><input name="q" value="<?=e($query ?? ($_GET['q'] ?? ''))?>" placeholder="Aranacak kelime"><button>Ara</button></form>
    <div class="kv1-list-grid">
      <?php foreach($items as $i=>$p): ?><article class="kv1-list-card"><div class="kv1-list-img" style="background-image:url('<?=e(!empty($p['featured_image']) ? image_url($p['featured_image']) : kv1_image(($i%3)+1))?>')"></div><h3><a href="<?=e(post_url($p))?>"><?=e($p['title'] ?? '')?></a></h3><p><?=e(($p['spot'] ?? '') ?: kv1_excerpt($p['content'] ?? '',110))?></p></article><?php endforeach; ?>
      <?php if(!$items): ?><p class="kv1-muted">Sonuç bulunamadı.</p><?php endif; ?>
    </div>
  </section>
</main>
<?php include omurga_theme_file('footer.php'); ?>
