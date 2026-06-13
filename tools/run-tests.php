<?php
$root = dirname(__DIR__);
require_once $root.'/bootstrap.php';
$tests=[];
$add=function($name,$fn) use (&$tests){ $tests[]=['name'=>$name,'fn'=>$fn]; };
$add('database', fn()=> (bool)db()->query('SELECT 1'));
$add('settings table', fn()=> (bool)db()->query('SELECT 1 FROM '.table_name('settings').' LIMIT 1'));
$add('users table', fn()=> (bool)db()->query('SELECT 1 FROM '.table_name('users').' LIMIT 1'));
$add('cache writable', function(){ $f=OMURGA_ROOT.'/storage/cache/.cli-test-'.bin2hex(random_bytes(3)); $ok=@file_put_contents($f,'ok')!==false; if($ok) @unlink($f); return $ok; });
$add('uploads writable', function(){ $f=OMURGA_ROOT.'/uploads/.cli-test-'.bin2hex(random_bytes(3)); $ok=@file_put_contents($f,'ok')!==false; if($ok) @unlink($f); return $ok; });
$add('csrf token', fn()=> function_exists('csrf_token') && csrf_token() !== '');
$add('core guard', fn()=> function_exists('omurga_core_guard_enabled') && omurga_core_guard_enabled());
$failed=0;
foreach($tests as $t){ try{ $ok=(bool)$t['fn'](); }catch(Throwable $e){ $ok=false; echo '[ERR] '.$t['name'].' - '.$e->getMessage().PHP_EOL; } echo ($ok?'[OK] ':'[!!] ').$t['name'].PHP_EOL; if(!$ok)$failed++; }
echo 'Result: '.(count($tests)-$failed).'/'.count($tests).' passed'.PHP_EOL;
exit($failed>0?1:0);
