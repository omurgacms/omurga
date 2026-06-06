<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once dirname(__DIR__) . '/functions.php';
require_admin();
require_cap('themes.manage');
require_once OMURGA_ROOT . '/admin/_layout.php';
require __DIR__ . '/panel-content.php';
require_once OMURGA_ROOT . '/admin/_footer.php';
