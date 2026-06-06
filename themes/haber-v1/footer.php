  <footer class="footer">
    <a href="<?=e(omurga_url())?>" class="f-logo"><?=e(hv1_logo_text())?></a>
    <div class="f-links"><?php foreach(menu_items('footer') as $m): ?><a class="f-link" href="<?=e($m['url'])?>"><?=e($m['title'])?></a><?php endforeach; ?></div>
    <?php $ft=trim((string)hv1_setting('footer_text','')); ?><div class="f-copy"><?= $ft!=='' ? e($ft) : ('© '.date('Y').' '.e(setting('site_name','Haber V1'))) ?></div>
  </footer>
</div>
</body>
</html>
