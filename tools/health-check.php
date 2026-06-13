<?php
$root = dirname(__DIR__);
require_once $root.'/bootstrap.php';
$checks = [];
$add = function($name,$ok,$value='') use (&$checks){ $checks[]=['name'=>$name,'ok'=>(bool)$ok,'value'=>$value]; };
$add('PHP 8.0+', version_compare(PHP_VERSION,'8.0.0','>='), PHP_VERSION);
$add('PDO MySQL', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql')?'active':'missing');
$add('ZipArchive', class_exists('ZipArchive'), class_exists('ZipArchive')?'active':'missing');
$add('GD WebP', function_exists('imagewebp'), function_exists('imagewebp')?'active':'missing');
foreach(['storage/cache','storage/logs','uploads'] as $dir){ $p=$root.'/'.$dir; if(!is_dir($p)) @mkdir($p,0775,true); $add($dir.' writable', is_writable($p), is_writable($p)?'writable':'not writable'); }
try{ db()->query('SELECT 1'); $add('Database connection', true, 'ok'); } catch(Throwable $e){ $add('Database connection', false, $e->getMessage()); }
$add('Migrations applied', function_exists('omurga_migrations_all_applied') && omurga_migrations_all_applied(), '');
foreach($checks as $c){ echo ($c['ok']?'[OK] ':'[!!] ').$c['name'].($c['value']!==''?' - '.$c['value']:'').PHP_EOL; }
$failed = count(array_filter($checks, fn($c)=>!$c['ok']));
exit($failed>0?1:0);
