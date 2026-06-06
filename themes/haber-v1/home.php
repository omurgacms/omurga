<?php include __DIR__.'/header.php';
$heroCat=hv1_setting('hero_category','gundem'); $hero=hv1_featured(5,$heroCat); if(!$hero) $hero=hv1_posts(5,$heroCat); if(!$hero) $hero=hv1_posts(5);
$featured=hv1_posts(3,'','one-cikan') ?: hv1_posts(3);
$local=hv1_posts(5,hv1_setting('local_category','yerel')); $eco=hv1_posts(3,hv1_setting('economy_category','ekonomi')); $sport=hv1_posts(3,hv1_setting('sport_category','spor')); $tech=hv1_posts(5,hv1_setting('tech_category','teknoloji')); $latest=hv1_posts(9);
?>
  <main class="main-area">
    <section class="hero-wrap">
      <?php $h=$hero[0] ?? null; if($h): ?><article class="hero hv1-card-link" data-url="<?=e(post_url($h))?>">
        <div class="hero-img-bg news-img-bg" style="background-image:url('<?=e(hv1_img($h['featured_image'] ?? '',1))?>')"></div>
        <div class="hero-shade"></div>
        <div class="hero-content">
          <span class="hero-cat"><?=e($h['category_name'] ?? 'GÜNDEM')?></span>
          <h1 class="hero-title"><a href="<?=e(post_url($h))?>"><?=e($h['title'])?></a></h1>
          <p class="hero-desc"><?=e(excerpt($h['spot'] ?: ($h['content'] ?? ''),150))?></p>
          <div class="hero-meta"><?=e($h['author_name'] ?? 'Editör')?> · <?=e(hv1_time_ago($h['published_at'] ?? ''))?></div>
        </div>
      </article><?php endif; ?>
      <div class="hero-side"><?php foreach(array_slice($hero,1,4) as $i=>$p): ?><article class="mini-hero hv1-card-link" data-url="<?=e(post_url($p))?>"><div class="mini-img news-img-bg" style="background-image:url('<?=e(hv1_img($p['featured_image'] ?? '',$i+2))?>')"></div><div><span><?=e($p['category_name'] ?? 'Haber')?></span><h3><a href="<?=e(post_url($p))?>"><?=e($p['title'] ?? '')?></a></h3><small><?=e(hv1_time_ago($p['published_at'] ?? ''))?></small></div></article><?php endforeach; ?></div>
    </section>

    <?php if(hv1_setting('show_markets','1')==='1'): ?><section class="mkt-strip"><span class="mkt-lbl">PİYASALAR</span><div class="mi"><span>DOLAR</span><b><?=e(hv1_setting('market_dollar','32,41'))?></b><em>▼ %0,3</em></div><div class="mi"><span>EURO</span><b><?=e(hv1_setting('market_euro','35,17'))?></b><em class="up">▲ %0,1</em></div><div class="mi"><span>ALTIN</span><b><?=e(hv1_setting('market_gold','3.241'))?></b><em class="up">▲ %0,8</em></div><div class="mi"><span>BIST</span><b><?=e(hv1_setting('market_bist','9.842'))?></b><em class="up">▲ %1,2</em></div></section><?php endif; ?>

    <?=hv1_ad_slot('home_after_hero','Manşet Altı Reklam')?>

    <section class="content-layout">
      <div class="content-main">
        <div class="sec-row"><span class="sec-label">ÖNE ÇIKANLAR</span><div class="sec-line"></div></div>
        <div class="news3"><?php foreach($featured as $i=>$p) hv1_card($p,'n3',$i+5); ?></div>
        <?php $sections=[['YEREL',$local],['EKONOMİ',$eco],['SPOR',$sport],['TEKNOLOJİ',$tech]]; foreach($sections as $sec): if(!$sec[1]) continue; ?><div class="sec-row"><span class="sec-label"><?=e($sec[0])?></span><div class="sec-line"></div><span class="sec-more">Tümü →</span></div><div class="news3"><?php foreach(array_slice($sec[1],0,3) as $i=>$p) hv1_card($p,'n3',$i+8); ?></div><?php endforeach; ?>
        <div class="sec-row"><span class="sec-label">SON HABERLER</span><div class="sec-line"></div></div><div class="news3"><?php foreach($latest as $i=>$p) hv1_card($p,'n3',$i+1); ?></div>
        <?php if(hv1_setting('show_authors','1')==='1'): ?><div class="sec-row" id="yazarlar"><span class="sec-label">YAZARLAR</span><div class="sec-line"></div></div><div class="op-grid"><?php foreach(hv1_author_cards() as $i=>$a): ?><div class="op-card"><div class="op-av av<?=($i+1)?>"><?=e($a['initials'])?></div><div><div class="op-name"><?=e($a['name'])?></div><div class="op-title"><?=e($a['title'])?></div></div></div><?php endforeach; ?></div><?php endif; ?>
      </div>
      <?php if(hv1_setting('show_sidebar','1')==='1') hv1_sidebar(); ?>
    </section>
  </main>
<?php include __DIR__.'/footer.php'; ?>
