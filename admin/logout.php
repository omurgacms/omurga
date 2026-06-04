<?php
require_once dirname(__DIR__) . '/bootstrap.php';
if(is_admin_logged_in()) log_activity('auth.logout','Panelden çıkış yapıldı.');
session_destroy();
redirect('admin/login.php');
