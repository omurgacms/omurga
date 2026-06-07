<?php require_once __DIR__.'/functions.php'; include __DIR__.'/header.php'; ?>
<main class="omh-main">
  <div class="omh-container">
    <section class="omh-home-hero omg-layout-row">
      <div class="omg-layout-item omg-width-70"><?=omh_render_theme_block('omhaber-hero-slider', ['limit'=>'5','title'=>'GÜNDEM'])?></div>
      <div class="omg-layout-item omg-width-30"><?=omh_render_theme_block('omhaber-small-headlines', ['limit'=>'3'])?></div>
    </section>
    <?=omh_render_theme_block('omhaber-category-strip')?>
    <section class="omh-home-content omg-layout-row">
      <div class="omg-layout-item omg-width-70">
        <?=omh_render_theme_block('omhaber-latest-news', ['limit'=>'5','title'=>'SON HABERLER'])?>
        <?=omh_render_theme_block('omhaber-video-news', ['limit'=>'4','title'=>'VİDEO HABER'])?>
      </div>
      <aside class="omg-layout-item omg-width-30">
        <?=omh_render_theme_block('omhaber-popular-news', ['limit'=>'5','title'=>'ÇOK OKUNANLAR'])?>
        <?=omh_render_theme_block('omhaber-sidebar-stack')?>
      </aside>
    </section>
    <?=omh_render_theme_block('omhaber-ad-slot', ['size'=>'970x90','text'=>'REKLAM ALANI • 970x90'])?>
    <?=omh_render_theme_block('omhaber-category-cards', ['title'=>'KATEGORİLER'])?>
  </div>
</main>
<?php include __DIR__.'/footer.php'; ?>
