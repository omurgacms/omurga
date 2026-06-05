<?php
$limit=max(1,min(24,(int)($settings['limit'] ?? 6)));
$view=in_array(($settings['view'] ?? 'card'), ['card','list'], true) ? $settings['view'] : 'card';
$title=trim((string)($settings['title'] ?? ''));
if($title===''){
    $profile=function_exists('site_type') ? site_type() : 'bos';
    $title=match($profile){
        'haber'=>om_t('blocks.latest_content.news','Son Haberler'),
        'topluluk'=>om_t('blocks.latest_content.community','Son Duyurular'),
        default=>om_t('blocks.latest_content.posts','Son Yazilar'),
    };
}
$rows=[];
try{
    $postsT=table_name('posts'); $catsT=table_name('categories');
    $rows=db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit")->fetchAll();
}catch(Throwable $e){ omurga_write_error($e); }
?>
<section class="omg-core-block omg-content-block omg-latest-content omg-view-<?=e($view)?>">
  <h2><?=e($title)?></h2>
  <?php if(!$rows): ?><p class="omg-block-empty"><?=e(om_t('blocks.no_content','Icerik bulunamadi.'))?></p><?php else: ?>
  <div class="omg-content-items">
    <?php foreach($rows as $item): ?>
      <article class="omg-content-item">
        <?php if($view==='card'): ?><?php if(!empty($item['featured_image'])): ?><img src="<?=e(image_url($item['featured_image']))?>" alt="<?=e($item['title'] ?? '')?>"><?php endif; ?><?php endif; ?>
        <div>
          <?php if(!empty($item['category_name'])): ?><small><?=e($item['category_name'])?></small><?php endif; ?>
          <h3><a href="<?=e(post_url($item))?>"><?=e($item['title'] ?? '')?></a></h3>
          <?php if($view==='card'): ?><p><?=e(excerpt(($item['spot'] ?? '') ?: ($item['content'] ?? ''), 120))?></p><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
