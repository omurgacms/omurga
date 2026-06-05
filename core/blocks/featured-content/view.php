<?php
$limit=max(1,min(12,(int)($settings['limit'] ?? 4)));
$title=trim((string)($settings['title'] ?? '')) ?: om_t('blocks.featured_content','One Cikan Icerikler');
$rows=[];
try{
    $postsT=table_name('posts'); $catsT=table_name('categories'); $metaT=table_name('post_meta');
    $sql="SELECT DISTINCT p.*, c.name category_name, c.slug category_slug FROM $postsT p INNER JOIN $metaT pm ON pm.post_id=p.id LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND pm.meta_key IN ('featured','is_featured','legacy_is_featured') AND pm.meta_value IN ('1','true','yes','on') ORDER BY p.sort_order ASC,COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit";
    $rows=db()->query($sql)->fetchAll();
    if(!$rows && !empty($settings['category_id'])){
        $st=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.category_id=? ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit");
        $st->execute([(int)$settings['category_id']]);
        $rows=$st->fetchAll();
    }
    if(!$rows){
        $rows=db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit")->fetchAll();
    }
}catch(Throwable $e){ omurga_write_error($e); }
?>
<section class="omg-core-block omg-content-block omg-featured-content">
  <h2><?=e($title)?></h2>
  <?php if(!$rows): ?><p class="omg-block-empty"><?=e(om_t('blocks.no_content','Icerik bulunamadi.'))?></p><?php else: ?>
  <div class="omg-featured-grid">
    <?php foreach($rows as $item): ?><article class="omg-content-item"><?php if(!empty($item['featured_image'])): ?><img src="<?=e(image_url($item['featured_image']))?>" alt="<?=e($item['title'] ?? '')?>"><?php endif; ?><div><h3><a href="<?=e(post_url($item))?>"><?=e($item['title'] ?? '')?></a></h3><p><?=e(excerpt(($item['spot'] ?? '') ?: ($item['content'] ?? ''), 120))?></p></div></article><?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
