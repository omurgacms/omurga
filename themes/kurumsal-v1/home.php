<?php if (!defined('OMURGA_ROOT')) { exit; } include omurga_theme_file('header.php');
$services = kv1_service_cards($services ?? kv1_posts('hizmetler',4));
$projects = kv1_project_cards($portfolio ?? kv1_posts('projeler',3));
?>
<section class="kv1-hero">
  <div class="kv1-hero-panel"></div>
  <div class="kv1-hero-hline"></div>
  <div class="kv1-hero-dot"></div>
  <div class="kv1-hero-content">
    <div class="kv1-tag"><?=e(kv1_text('hero_tag','Kurumsal Çözüm Ortağı'))?></div>
    <?php $heroTitle=kv1_text('hero_title','Markanızı güçlü bir dijital deneyime dönüştürüyoruz.'); $highlight=kv1_text('hero_highlight','dijital'); ?>
    <h1><?=str_replace(e($highlight), '<em>'.e($highlight).'</em>', e($heroTitle))?></h1>
    <p class="kv1-hero-sub"><?=e(kv1_text('hero_text','Web tasarımından kurumsal kimliğe, işletmenizi dijital dünyada daha profesyonel gösterecek çözümler üretiyoruz.'))?></p>
    <div class="kv1-btn-group">
      <a href="<?=e(kv1_text('hero_button_url','#contact'))?>" class="kv1-btn kv1-btn-primary"><?=e(kv1_text('hero_button_text','Teklif Al'))?> →</a>
      <a href="<?=e(kv1_text('secondary_button_url','#work'))?>" class="kv1-btn kv1-btn-outline"><?=e(kv1_text('secondary_button_text','Çalışmalarımız'))?></a>
    </div>
  </div>
  <div class="kv1-hero-stats">
    <div class="kv1-stat"><span class="kv1-stat-num"><?=e(kv1_text('stat_1_number','80+'))?></span><span class="kv1-stat-label"><?=e(kv1_text('stat_1_label','Tamamlanan Proje'))?></span></div>
    <div class="kv1-stat"><span class="kv1-stat-num"><?=e(kv1_text('stat_2_number','5★'))?></span><span class="kv1-stat-label"><?=e(kv1_text('stat_2_label','Müşteri Puanı'))?></span></div>
    <div class="kv1-stat"><span class="kv1-stat-num"><?=e(kv1_text('stat_3_number','3+'))?></span><span class="kv1-stat-label"><?=e(kv1_text('stat_3_label','Yıl Deneyim'))?></span></div>
  </div>
  <div class="kv1-scroll-hint"><span class="kv1-scroll-line"></span>Aşağı kaydır</div>
</section>

<section class="kv1-services" id="services">
  <div class="kv1-services-header">
    <div><div class="kv1-section-tag">Ne yapıyoruz</div><h2><?=nl2br(e(kv1_text('services_title','Sunduğumuz Hizmetler')))?></h2></div>
    <p><?=e(kv1_text('services_intro','İşlevsellik ile estetiği dengeli biçimde bir araya getiriyor, markanıza özel sürdürülebilir çözümler üretiyoruz.'))?></p>
  </div>
  <div class="kv1-services-grid">
    <?php foreach($services as $s): ?><article class="kv1-service-card <?=!empty($s['url'])?'kv1-card-link':''?>" <?=!empty($s['url'])?'data-url="'.e($s['url']).'"':''?>>
      <div class="kv1-service-icon"><?=e($s['icon'] ?? '◈')?></div><h3><?=e($s['title'] ?? '')?></h3><p><?=e($s['spot'] ?? '')?></p>
    </article><?php endforeach; ?>
  </div>
</section>

<section class="kv1-work" id="work">
  <div class="kv1-work-intro">
    <div><div class="kv1-section-tag">Portföy</div><h2><?=nl2br(e(kv1_text('portfolio_title','Seçilmiş Çalışmalar')))?></h2></div>
    <p><?=e(kv1_text('portfolio_intro','Her proje müşterinin vizyonunu anlamakla başlar. Ortaya çıkan sadece güzel bir tasarım değil, değer yaratan bir çözümdür.'))?></p>
  </div>
  <div class="kv1-portfolio-grid">
    <?php foreach(array_values($projects) as $i=>$p): ?><article class="kv1-port-item <?=!empty($p['url'])?'kv1-card-link':''?>" <?=!empty($p['url'])?'data-url="'.e($p['url']).'"':''?>>
      <div class="kv1-mock-img kv1-m<?=($i%3)+1?>" style="background-image:url('<?=e(kv1_image(($i%3)+1))?>')"><span class="kv1-mock-icon"><?=['◈','⬡','◎'][$i%3]?></span><span><?=e($p['title'] ?? '')?></span></div>
      <div class="kv1-port-overlay"></div><div class="kv1-port-info"><h4><?=e($p['title'] ?? '')?></h4><span><?=e($p['cat'] ?? 'Proje')?></span></div>
    </article><?php endforeach; ?>
  </div>
</section>

<?php if(kv1_bool('show_manager', true)): ?>
<section class="kv1-manager">
  <div class="kv1-manager-card"><div class="kv1-section-tag"><?=e(kv1_text('manager_title','Kurucudan Mesaj'))?></div><h2><?=e(kv1_text('manager_name','Omurga Kurumsal'))?></h2><p><?=e(kv1_text('manager_text','Her projede sade, güvenilir ve uzun ömürlü bir dijital yapı kurmayı hedefliyoruz.'))?></p></div>
</section>
<?php endif; ?>

<section class="kv1-process" id="process">
  <div class="kv1-section-tag">Nasıl çalışıyoruz</div><h2><?=e(kv1_text('process_title','Çalışma Sürecimiz'))?></h2>
  <div class="kv1-process-steps">
    <?php $steps=[['01','Keşif','Markanızı, hedef kitlenizi ve hedeflerinizi anlıyoruz.'],['02','Konsept','Araştırma bulgularını yaratıcı ve uygulanabilir bir plana dönüştürüyoruz.'],['03','Tasarım','Onaylanan konsepti detaylandırıp güçlü bir görünüme çeviriyoruz.'],['04','Teslimat','Projeyi kullanıma hazır biçimde teslim ediyor, süreç sonrasında destek oluyoruz.']]; foreach($steps as $stp): ?>
    <div class="kv1-step"><div class="kv1-step-num"><?=e($stp[0])?></div><h4><?=e($stp[1])?></h4><p><?=e($stp[2])?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="kv1-cta-section" id="contact">
  <div class="kv1-section-tag kv1-center">Başlayalım</div>
  <h2><?=nl2br(e(kv1_text('cta_title','Projenizi hayata geçirelim.')))?></h2>
  <p><?=e(kv1_text('cta_text','Fikriniz varsa, geri kalanını birlikte planlayalım. İlk görüşme ücretsiz ve bağlayıcı değildir.'))?></p>
  <a href="<?=e(kv1_text('cta_button_url','mailto:merhaba@example.com'))?>" class="kv1-btn kv1-btn-primary"><?=e(kv1_text('cta_button_text','Ücretsiz Görüşme Talep Et'))?> →</a>
  <div class="kv1-contact-mini"><span><?=e(kv1_text('contact_email','merhaba@example.com'))?></span><span><?=e(kv1_text('contact_phone','+90 555 000 00 00'))?></span></div>
</section>
<?php include omurga_theme_file('footer.php'); ?>
