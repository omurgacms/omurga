<?php
$title=trim((string)($settings['title'] ?? '')) ?: om_t('blocks.contact_info','Iletisim');
$phone=trim((string)($settings['phone'] ?? ''));
$email=trim((string)($settings['email'] ?? ''));
$address=trim((string)($settings['address'] ?? ''));
$map=trim((string)($settings['map_url'] ?? ''));
$whatsapp=trim((string)($settings['whatsapp_url'] ?? ''));
if($phone==='' && $email==='' && $address==='' && $map==='' && $whatsapp==='') return;
?>
<section class="omg-core-block omg-contact-info">
  <h2><?=e($title)?></h2>
  <ul>
    <?php if($phone!==''): ?><li><strong>Tel</strong><a href="tel:<?=e(preg_replace('/[^0-9+]/','',$phone))?>"><?=e($phone)?></a></li><?php endif; ?>
    <?php if($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)): ?><li><strong>E-posta</strong><a href="mailto:<?=e($email)?>"><?=e($email)?></a></li><?php endif; ?>
    <?php if($address!==''): ?><li><strong>Adres</strong><span><?=nl2br(e($address))?></span></li><?php endif; ?>
    <?php if($map!==''): ?><li><a href="<?=e($map)?>" target="_blank" rel="noopener"><?=e(om_t('blocks.map','Harita'))?></a></li><?php endif; ?>
    <?php if($whatsapp!==''): ?><li><a href="<?=e($whatsapp)?>" target="_blank" rel="noopener">WhatsApp</a></li><?php endif; ?>
  </ul>
</section>
