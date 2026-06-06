<?php include __DIR__.'/header.php'; $news=tv1_posts(5,'haberler'); if(!$news) $news=tv1_posts(5); ?>
  <div class="dt-hero">
    <div class="dt-hero-badge"><i class="ti ti-award" style="font-size:12px"></i><?=e(tv1_setting('hero_badge','37 Yıllık Köklü Deneyim'))?></div>
    <h1 class="dt-hero-title"><?=e(tv1_setting('hero_title','Birlikte Güçlü, Birlikte Değerliyiz'))?></h1>
    <p class="dt-hero-desc"><?=e(tv1_setting('hero_desc','Kültürel miras, dayanışma ve toplumsal gelişim için bir arada oluyoruz. Siz de aramıza katılın.'))?></p>
    <div class="dt-hero-btns">
      <a href="<?=e(tv1_setting('hero_primary_url','#uyelik'))?>" class="dt-btn-primary"><i class="ti ti-user-plus" style="font-size:15px"></i><?=e(tv1_setting('hero_primary_text','Üyelik Başvurusu'))?></a>
      <a href="<?=e(tv1_setting('hero_secondary_url','#etkinlikler'))?>" class="dt-btn-ghost"><?=e(tv1_setting('hero_secondary_text','Etkinlikleri Keşfet'))?></a>
    </div>
    <div class="dt-hero-stats">
      <div><div class="dt-stat-num"><?=e(tv1_setting('stat_1_num','2.400+'))?></div><div class="dt-stat-lbl"><?=e(tv1_setting('stat_1_label','Aktif Üye'))?></div></div>
      <div><div class="dt-stat-num"><?=e(tv1_setting('stat_2_num','180'))?></div><div class="dt-stat-lbl"><?=e(tv1_setting('stat_2_label','Yıllık Etkinlik'))?></div></div>
      <div><div class="dt-stat-num"><?=e(tv1_setting('stat_3_num','37'))?></div><div class="dt-stat-lbl"><?=e(tv1_setting('stat_3_label','Kuruluş Yılı'))?></div></div>
    </div>
  </div>
  <?php if(tv1_setting('ticker_enabled','1')==='1'): ?><div class="dt-ticker"><span class="dt-ticker-label"><?=e(tv1_setting('ticker_label','Duyuru'))?></span><span class="dt-ticker-text"><i class="ti ti-speakerphone" style="font-size:13px;vertical-align:-2px;margin-right:4px"></i><?=e(tv1_setting('ticker_text',"Genel Kurul Toplantısı 15 Temmuz 2026 tarihinde saat 14:00'te gerçekleşecektir. Tüm üyelerimizin katılımı beklenmektedir."))?></span></div><?php endif; ?>
  <div class="dt-section" id="etkinlikler">
    <div class="dt-section-header"><div class="dt-section-title"><span>Yaklaşan</span>Etkinlikler</div><a href="<?=e(function_exists('omurga_url')?omurga_url('etkinlikler'):'#etkinlikler')?>" class="dt-see-all">Tümünü Gör <i class="ti ti-arrow-right" style="font-size:13px"></i></a></div>
    <div class="dt-events">
      <?php foreach(tv1_event_settings() as $ev): [$cls,$icon,$date,$tag,$title,$place]=$ev; $parts=preg_split('/\s+/',trim($date),2); ?>
      <div class="dt-event-card"><div class="dt-event-img <?=e($cls)?>"><span><?=e($icon)?></span><div class="dt-event-date-badge"><?=e($parts[0] ?? '')?><br><?=e($parts[1] ?? '')?></div></div><div class="dt-event-body"><div class="dt-event-tag"><?=e($tag)?></div><div class="dt-event-title"><?=e($title)?></div><div class="dt-event-meta"><i class="ti ti-map-pin" style="font-size:12px"></i><?=e($place)?></div></div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="dt-divider"></div>
  <div class="dt-section dt-two-col" id="haberler">
    <div>
      <div class="dt-section-header"><div class="dt-section-title"><span>Son</span>Haberler</div><a href="<?=e(function_exists('omurga_url')?omurga_url('haberler'):'#haberler')?>" class="dt-see-all">Tümü <i class="ti ti-arrow-right" style="font-size:13px"></i></a></div>
      <div class="dt-news-list">
        <?php if($news): foreach($news as $i=>$p): ?><div class="dt-news-item"><div class="dt-news-num"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></div><div><div class="dt-news-cat"><?=e($p['category_name'] ?? 'Duyuru')?></div><div class="dt-news-title"><a href="<?=e(tv1_post_url($p))?>"><?=e($p['title'] ?? '')?></a></div><div class="dt-news-date"><i class="ti ti-calendar" style="font-size:12px;vertical-align:-1px"></i> <?=e(tv1_date($p['published_at'] ?? $p['created_at'] ?? ''))?></div></div></div><?php endforeach; else: ?>
        <?php foreach([['Duyuru','2026 Bütçesi Oybirliğiyle Kabul Edildi','2 Haziran 2026'],['Proje','Gençlik Bursu Başvuruları Açıldı','28 Mayıs 2026'],['İşbirliği','Belediye ile Kültür Protokolü İmzalandı','18 Mayıs 2026']] as $i=>$n): ?><div class="dt-news-item"><div class="dt-news-num"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></div><div><div class="dt-news-cat"><?=e($n[0])?></div><div class="dt-news-title"><?=e($n[1])?></div><div class="dt-news-date"><i class="ti ti-calendar" style="font-size:12px;vertical-align:-1px"></i> <?=e($n[2])?></div></div></div><?php endforeach; endif; ?>
      </div>
    </div>
    <div>
      <div class="dt-section-header"><div class="dt-section-title"><span>Seçilmiş</span><?=e(tv1_setting('board_title','Yönetim Kurulu'))?></div></div>
      <div class="dt-board"><?php foreach(tv1_board_members() as $m): ?><div class="dt-board-card"><div class="dt-avatar <?=e($m[0])?>"><?=e($m[1])?></div><div class="dt-board-name"><?=e($m[2])?></div><div class="dt-board-role"><?=e($m[3])?></div></div><?php endforeach; ?></div>
    </div>
  </div>
  <div class="dt-divider"></div>
  <?php if(tv1_setting('membership_enabled','1')==='1'): ?><div class="dt-section-alt" id="uyelik">
    <div class="dt-section-header"><div class="dt-section-title"><span>Aramıza Katılın</span>Üyelik Planları</div></div>
    <div class="dt-membership">
      <div class="dt-mem-card"><div class="dt-mem-header"><div class="dt-mem-tier">Standart Üyelik</div><div class="dt-mem-price">₺600 <span>/ yıl</span></div></div><ul class="dt-mem-features"><li><span class="dt-check">✓</span> Tüm etkinliklere katılım</li><li><span class="dt-check">✓</span> Dernek bültenine erişim</li><li><span class="dt-check">✓</span> Oy kullanma hakkı</li><li><span class="dt-check">✓</span> Üye kartı</li></ul><a href="<?=e(tv1_setting('nav_cta_url','#uyelik'))?>" class="dt-mem-btn outline">Başvur</a></div>
      <div class="dt-mem-card"><div class="dt-mem-header gold"><div class="dt-mem-tier">Onur Üyeliği</div><div class="dt-mem-price">₺1.500 <span>/ yıl</span></div></div><ul class="dt-mem-features"><li><span class="dt-check">✓</span> Standart üyelik avantajları</li><li><span class="dt-check">✓</span> Özel etkinliklere öncelikli davet</li><li><span class="dt-check">✓</span> Yönetim toplantılarına gözlemci katılım</li><li><span class="dt-check">✓</span> İsimli plaket ve teşekkür sertifikası</li></ul><a href="<?=e(tv1_setting('nav_cta_url','#uyelik'))?>" class="dt-mem-btn">Onur Üyesi Ol</a></div>
    </div>
  </div><?php endif; ?>
<?php include __DIR__.'/footer.php'; ?>
