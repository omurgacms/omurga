<?php
require_once dirname(__DIR__).'/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
try{
    if(!can('media.manage') && !can('media.upload') && current_user_role()!=='admin') throw new RuntimeException('Medya yükleme yetkin yok.');
    if($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('Geçersiz istek.');
    verify_csrf();
    if(empty($_FILES['files'])) throw new RuntimeException('Dosya seçilmedi.');
    $files=$_FILES['files'];
    $names=is_array($files['name']) ? $files['name'] : [$files['name']];
    $items=[]; $skipped=0; $createWebp=!empty($_POST['make_webp']);
    for($i=0;$i<count($names);$i++){
        $one=[
            'name'=>$names[$i],
            'type'=>is_array($files['type']) ? ($files['type'][$i] ?? '') : ($files['type'] ?? ''),
            'tmp_name'=>is_array($files['tmp_name']) ? ($files['tmp_name'][$i] ?? '') : ($files['tmp_name'] ?? ''),
            'error'=>is_array($files['error']) ? ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) : ($files['error'] ?? UPLOAD_ERR_NO_FILE),
            'size'=>is_array($files['size']) ? ($files['size'][$i] ?? 0) : ($files['size'] ?? 0),
        ];
        if(($one['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){ $skipped++; continue; }
        $tmp=$one['tmp_name']; $mime=mime_content_type($tmp) ?: '';
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','application/pdf'=>'pdf','video/mp4'=>'mp4'];
        if(!isset($allowed[$mime])){ $skipped++; continue; }
        if(($one['size']??0)>64*1024*1024){ $skipped++; continue; }
        $relDir=omurga_media_rel_dir(); $dir=OMURGA_ROOT.'/'.$relDir; if(!is_dir($dir)) mkdir($dir,0775,true);
        $name=omurga_prepare_upload_name((string)$one['name'], trim($_POST['title_hint'] ?? $_POST['alt_text'] ?? ''));
        $originalPath=$relDir.'/'.$name; $target=$dir.'/'.$name;
        if(!move_uploaded_file($tmp,$target)){ $skipped++; continue; }
        if(str_starts_with($mime,'image/')) omurga_resize_image_if_needed($target,$mime,(int)setting('media_max_width','1600'),(int)setting('media_jpeg_quality','86'));
        $finalPath=$originalPath;
        if($createWebp && in_array($mime,['image/jpeg','image/png'],true)){
            $webp=create_webp_copy($target,$mime,(int)setting('webp_quality','82'));
            if($webp) $finalPath=$relDir.'/'.basename($webp);
        }
        $alt=trim($_POST['alt_text'] ?? '');
        insert_media_record($finalPath, $alt, $_SESSION['omurga_user_id'] ?? null, $originalPath===$finalPath?null:$originalPath);
        $items[]=['src'=>$finalPath,'thumb'=>image_url($finalPath),'alt'=>$alt ?: basename($finalPath),'name'=>basename($finalPath),'mime'=>mime_content_type(OMURGA_ROOT.'/'.$finalPath) ?: $mime];
    }
    echo json_encode(['ok'=>true,'items'=>$items,'skipped'=>$skipped,'message'=>count($items).' dosya yüklendi'.($skipped?' · '.$skipped.' atlandı':'')], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
