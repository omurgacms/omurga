<?php require_once dirname(__DIR__, 2).'/functions.php'; $settings=$settings ?? ($block['settings'] ?? []); ?>
<div class="omh-ad omh-ad-<?=omh_e(str_replace('x','-', $settings['size'] ?? '970x90'))?>"><?php if(!empty($settings['html'])): ?><?=$settings['html']?><?php else: ?><span><?=omh_e($settings['text'] ?? 'REKLAM ALANI')?></span><?php endif; ?></div>
