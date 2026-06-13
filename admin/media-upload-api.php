<?php
require_once dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function om_media_api_json(array $payload, int $status=200): void { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
try{
    require_admin();
    if(!can('media.manage') && !can('media.upload') && current_user_role()!=='admin') throw new RuntimeException('Medya yükleme yetkin yok.');
    if($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('Geçersiz istek.');
    $csrf=(string)($_POST['_csrf'] ?? $_POST['csrf_token'] ?? '');
    if(!$csrf || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)){
        om_media_api_json(['ok'=>false,'message'=>'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.'], 419);
    }
    if(empty($_FILES['files']) && empty($_FILES['file'])) throw new RuntimeException('Dosya seçilmedi.');
    $files=$_FILES['files'] ?? $_FILES['file'];
    $names=is_array($files['name'] ?? null) ? $files['name'] : [($files['name'] ?? '')];
    $items=[]; $errors=[]; $skipped=0;
    $titleHint=trim((string)($_POST['title_hint'] ?? $_POST['media_title_hint'] ?? $_POST['post_title'] ?? ''));
    $altText=trim((string)($_POST['alt_text'] ?? ''));
    $createWebp=!empty($_POST['make_webp']);
    for($i=0;$i<count($names);$i++){
        try{
            $item=omurga_store_media_upload(omurga_normalize_upload_item($files,$i), [
                'title_hint'=>$titleHint,
                'alt_text'=>$altText,
                'create_webp'=>$createWebp,
                'max_size'=>64*1024*1024,
                'user_id'=>$_SESSION['omurga_user_id'] ?? null,
            ]);
            if($item) $items[]=$item;
        }catch(Throwable $e){ $skipped++; $errors[]=$e->getMessage(); omurga_write_error($e); }
    }
    if(!$items){
        $message=$errors ? implode(' | ', array_slice(array_unique($errors),0,3)) : 'Dosya yüklenemedi.';
        om_media_api_json(['ok'=>false,'items'=>[],'skipped'=>$skipped,'message'=>$message], 400);
    }
    om_media_api_json(['ok'=>true,'items'=>$items,'skipped'=>$skipped,'errors'=>$errors,'message'=>count($items).' dosya yüklendi'.($skipped?' · '.$skipped.' atlandı':'')]);
}catch(Throwable $e){
    omurga_write_error($e);
    om_media_api_json(['ok'=>false,'message'=>$e->getMessage()], 400);
}
