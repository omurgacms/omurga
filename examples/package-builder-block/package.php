<?php
if(!defined('OMURGA_INIT')) { exit; }

omurga_register_builder_block('ornek-duyuru', [
    'name' => 'Örnek Duyuru',
    'description' => 'Builder API ile gelen örnek blok.',
    'category' => 'content',
    'allowed_contexts' => ['home','page','post'],
    'settings_schema' => [
        'title' => omurga_builder_field('text', 'Başlık', 'Örnek duyuru'),
        'text' => omurga_builder_field('textarea', 'Metin', 'Bu blok paket üzerinden eklendi.'),
    ],
    'render_callback' => function(array $block, array $context = []) {
        $s = $block['settings'] ?? [];
        return '<section class="omg-example-block"><h3>'.e($s['title'] ?? '').'</h3><p>'.e($s['text'] ?? '').'</p></section>';
    },
]);
