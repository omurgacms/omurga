<?php
$limit=max(1,min(24,(int)($settings['limit'] ?? 6)));
$title=trim((string)($settings['title'] ?? '')) ?: om_t('blocks.popular_content','Populer Icerikler');
$rows=[];
try{
    $postsT=table_name('posts'); $catsT=table_name('categories'); $commentsT=table_name('comments');
    $sortBy=(string)($settings['sort_by'] ?? 'comments');
    $viewCol='';
    if($sortBy==='views'){
        foreach(['views','view_count','hit_count'] as $candidate){
            $st=db()->prepare("SHOW COLUMNS FROM $postsT LIKE ?");
            $st->execute([$candidate]);
            if($st->fetch()){ $viewCol=$candidate; break; }
        }
    }
    if($viewCol){
        $rows=db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.$viewCol DESC,COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit")->fetchAll();
    } else {
        $rows=db()->query("SELECT p.*, c.name category_name, c.slug category_slug, COUNT(cm.id) comment_count FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id LEFT JOIN $commentsT cm ON cm.post_id=p.id AND cm.status='approved' WHERE p.status='published' GROUP BY p.id ORDER BY comment_count DESC,COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT $limit")->fetchAll();
    }
}catch(Throwable $e){ omurga_write_error($e); }
?>
<section class="omg-core-block omg-content-block omg-popular-content">
  <h2><?=e($title)?></h2>
  <?php if(!$rows): ?><p class="omg-block-empty"><?=e(om_t('blocks.no_content','Icerik bulunamadi.'))?></p><?php else: ?>
  <ol class="omg-ranked-list">
    <?php foreach($rows as $i=>$item): ?><li><span><?=e((string)($i+1))?></span><a href="<?=e(post_url($item))?>"><?=e($item['title'] ?? '')?></a></li><?php endforeach; ?>
  </ol>
  <?php endif; ?>
</section>
