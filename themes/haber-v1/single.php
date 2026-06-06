<?php include __DIR__.'/header.php'; $p=$post ?? null; ?>
<main class="article-wrap">
  <article class="article-card">
    <?php if($p): ?><small><?=e($p['category_name'] ?? '')?> · <?=e(!empty($p['published_at'])?date('d.m.Y H:i',strtotime($p['published_at'])):'')?></small><h1><?=e($p['title'])?></h1><div class="article-spot"><?=e($p['spot'] ?? '')?></div><div class="article-meta"><?=e($p['author_name'] ?? 'Editör')?> · <?=e(hv1_time_ago($p['published_at'] ?? ''))?></div><?php if(!empty($p['featured_image'])): ?><img class="article-cover" src="<?=e(image_url($p['featured_image']))?>" alt="<?=e($p['title'])?>"><?php endif; ?><div class="article-content"><?=omurga_render_post_content($p)?></div><?=hv1_ad_slot('article','Yazı İçi Reklam')?><?php $rel=hv1_posts(4,$p['category_slug'] ?? ''); if($rel): ?><div class="sec-row"><span class="sec-label">İLGİLİ HABERLER</span><div class="sec-line"></div></div><div class="related-grid"><?php foreach($rel as $i=>$r){ if((int)$r['id']===(int)$p['id']) continue; hv1_card($r,'n3',$i+1); } ?></div><?php endif; ?><?php if(function_exists("om_comments_list") && $p) echo om_comments_list((int)$p["id"]); ?><?php if(function_exists("om_comment_form") && $p) echo om_comment_form((int)$p["id"]); ?><?php endif; ?>
  </article>
  <?php if(hv1_setting('show_sidebar','1')==='1') hv1_sidebar(); ?>
</main>
<?php include __DIR__.'/footer.php'; ?>
