<?php
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_login();
if(!can('themes.manage') && !can('plugins.manage') && current_user_role()!=='admin'){ http_response_code(403); exit('Yetkisiz erişim'); }
omurga_migrate();
require_once OMURGA_ROOT . '/admin/_layout.php';
echo function_exists('kv1_admin_panel_content') ? kv1_admin_panel_content() : '<div class="alert error">Kurumsal V1 paneli yüklenemedi.</div>';
require_once OMURGA_ROOT . '/admin/_footer.php';
