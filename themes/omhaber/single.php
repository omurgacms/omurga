<?php require_once __DIR__.'/functions.php'; include __DIR__.'/header.php'; $p=$post ?? []; ?>
<main class="omh-main omh-detail-main"><div class="omh-container omh-two-col">
  <article class="omh-article">
    <div class="omh-article-head">
      <span class="omh-badge"><?=omh_e($p['category_name'] ?? 'Haber')?></span>
      <h1><?=omh_e($p['title'] ?? 'Başlık')?></h1>
      <div class="omh-meta"><?=omh_e(omh_date($p))?><?=!empty($p['author_name'])?' • '.omh_e($p['author_name']):''?></div>
    </div>
    <img class="omh-article-img" src="<?=omh_e(omh_img($p))?>" alt="<?=omh_e($p['title'] ?? '')?>">
    <div class="omh-article-content"><?=function_exists('omurga_render_shortcodes') ? omurga_render_shortcodes($p['content'] ?? '') : ($p['content'] ?? '')?></div>
    <?=omh_render_region_safe('post_inside', ['post'=>$p])?>
    <?php $related=omh_posts(['category_id'=>$p['category_id'] ?? 0,'limit'=>4]); if($related): ?><section class="omh-related"><h2>Benzer Haberler</h2><div class="omh-card-grid"><?php foreach($related as $r): if(($r['id']??0)==($p['id']??-1)) continue; ?><a class="omh-mini-card" href="<?=omh_e(omh_post_url($r))?>"><img src="<?=omh_e(omh_img($r))?>" alt=""><strong><?=omh_e($r['title'])?></strong></a><?php endforeach; ?></div></section><?php endif; ?>
  </article>
  <aside class="omh-sidebar"><?=omh_render_region_safe('sidebar', ['post'=>$p])?></aside>
</div></main>
<?php include __DIR__.'/footer.php'; ?>
