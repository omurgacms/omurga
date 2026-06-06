<?php if (!defined('OMURGA_ROOT')) { exit; } ?>
<footer class="kv1-footer">
  <a href="<?=e(omurga_url())?>" class="kv1-logo"><?=e(kv1_logo_text())?> <span><?=e(kv1_logo_accent())?></span></a>
  <p>© <?=date('Y')?> <?=e(kv1_logo_text())?>. <?=e(kv1_text('footer_text','Tüm hakları saklıdır.'))?></p>
  <nav>
    <ul>
      <?php foreach(kv1_footer_menu() as $m): ?><li><a href="<?=e($m['url'] ?? '#')?>"><?=e($m['title'] ?? '')?></a></li><?php endforeach; ?>
    </ul>
  </nav>
</footer>
<script>
document.addEventListener('click', function(e){
  var a=e.target.closest('.kv1-card-link');
  if(a && a.dataset.url){ location.href=a.dataset.url; }
});
</script>
</body>
</html>
