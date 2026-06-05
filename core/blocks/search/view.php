<?php $title=trim((string)($settings['title'] ?? '')); ?>
<section class="omg-core-block omg-search-block">
  <?php if($title!==''): ?><h2><?=e($title)?></h2><?php endif; ?>
  <form method="get" action="<?=e(omurga_url('search'))?>">
    <label class="sr-only" for="omg-search-<?=e($block['id'] ?? 'block')?>"><?=e(om_t('blocks.search','Ara'))?></label>
    <input id="omg-search-<?=e($block['id'] ?? 'block')?>" type="search" name="q" placeholder="<?=e(om_t('blocks.search_placeholder','Aranacak kelime'))?>" value="<?=e($_GET['q'] ?? '')?>">
    <button type="submit"><?=e(om_t('blocks.search','Ara'))?></button>
  </form>
</section>
