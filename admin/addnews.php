<?php
// Geriye uyumluluk için kısa yol: Yazı Ekle sayfası.
require_once dirname(__DIR__) . '/bootstrap.php';
$_GET['type'] = $_GET['type'] ?? primary_content_type();
require __DIR__ . '/post-edit.php';
