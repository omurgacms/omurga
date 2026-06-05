<?php
if(!defined('OMURGA_INIT')) exit;

Omurga::addBlock('ornek-paket-blok', [
    'name'=>'Örnek Paket Blok',
    'category'=>'custom',
    'source'=>'package',
    'settings'=>[
        'title'=>['type'=>'text','label'=>'Başlık','default'=>'Omurga Paket API çalışıyor']
    ],
    'render_callback'=>function($settings){
        return '<div class="omurga-box"><strong>'.e($settings['title'] ?? '').'</strong></div>';
    }
]);
