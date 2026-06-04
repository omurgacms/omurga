<?php $style=in_array(($settings['style'] ?? 'solid'), ['solid','dashed','dotted'], true) ? $settings['style'] : 'solid'; ?>
<hr class="omg-core-block omg-divider" style="border-style:<?=e($style)?>">
