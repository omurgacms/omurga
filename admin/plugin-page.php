<?php
require '_layout.php';
require_cap('plugins.manage');
render_error_page(410, 'Eski eklenti sayfası pasif', 'Omurga v4 ile plugins/ sistemi pasif hale getirildi. Lütfen packages/ sistemi ve Paketler ekranını kullanın.');
