<?php if (!defined('OMURGA_ROOT')) { exit; } ?>
  <footer class="dt-footer" id="iletisim">
    <div class="dt-footer-top">
      <div>
        <div class="dt-footer-brand"><?=e(str_replace(' ', ' ', tv1_site_label()))?></div>
        <div class="dt-footer-desc"><?=e(tv1_setting('footer_desc',"1987'den bu yana kültürel miras, dayanışma ve toplumsal kalkınma için çalışıyoruz. Geleceği birlikte inşa ediyoruz."))?></div>
      </div>
      <div>
        <div class="dt-footer-head">Hızlı Bağlantılar</div>
        <ul class="dt-footer-links">
          <?php foreach(tv1_menu_items('footer') as $m): if(isset($m['active']) && !$m['active']) continue; ?><li><a href="<?=e($m['url'] ?? '#')?>"><?=e($m['title'] ?? '')?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div>
        <div class="dt-footer-head">İletişim</div>
        <ul class="dt-footer-links">
          <li><i class="ti ti-map-pin" style="font-size:13px;vertical-align:-1px;margin-right:4px"></i><?=e(tv1_setting('contact_address','Kızılay, Ankara'))?></li>
          <li><i class="ti ti-phone" style="font-size:13px;vertical-align:-1px;margin-right:4px"></i><?=e(tv1_setting('contact_phone','0312 xxx xx xx'))?></li>
          <li><i class="ti ti-mail" style="font-size:13px;vertical-align:-1px;margin-right:4px"></i><?=e(tv1_setting('contact_email','info@dernek.org.tr'))?></li>
          <li style="margin-top:8px;"><i class="ti ti-clock" style="font-size:13px;vertical-align:-1px;margin-right:4px"></i><?=e(tv1_setting('contact_hours','Hft. içi 09:00–18:00'))?></li>
        </ul>
      </div>
    </div>
    <div class="dt-footer-bottom">
      <span><?=e(tv1_setting('footer_text','© 2026 Ankara Kültür Derneği — Tüm hakları saklıdır.'))?></span>
      <div class="dt-footer-social">
        <a href="<?=e(tv1_setting('x_url','#'))?>" class="dt-social-btn"><i class="ti ti-brand-twitter"></i></a>
        <a href="<?=e(tv1_setting('facebook_url','#'))?>" class="dt-social-btn"><i class="ti ti-brand-facebook"></i></a>
        <a href="<?=e(tv1_setting('instagram_url','#'))?>" class="dt-social-btn"><i class="ti ti-brand-instagram"></i></a>
        <a href="<?=e(tv1_setting('youtube_url','#'))?>" class="dt-social-btn"><i class="ti ti-brand-youtube"></i></a>
      </div>
    </div>
  </footer>
</div>
</body>
</html>
