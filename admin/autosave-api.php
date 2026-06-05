<?php
require_once dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try{
    require_admin();
    require_cap('posts.view');
    verify_csrf();
    if($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('Geçersiz istek.');
    if(setting('autosave_enabled','1')!=='1') throw new RuntimeException('Otomatik kaydetme kapalı.');
    $postId=(int)($_POST['post_id'] ?? 0);
    $draftKey=(string)($_POST['autosave_draft_key'] ?? '');
    $type=preg_replace('/[^a-zA-Z0-9_\-]/','', (string)($_POST['type'] ?? 'post')) ?: 'post';
    $payload=$_POST;
    unset($payload['_csrf']);
    // Çok büyük/gereksiz alanları temizlemeden tüm editör durumunu saklıyoruz.
    omurga_save_autosave($postId, $draftKey, $type, $payload);
    echo json_encode(['ok'=>true,'message'=>'Otomatik kaydedildi','time'=>date('H:i:s')], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
