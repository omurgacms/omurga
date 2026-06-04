<?php
require_once dirname(__DIR__).'/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
try{
    if(!can('media.select') && !can('media.manage') && current_user_role()!=='admin') throw new RuntimeException('Yetkisiz işlem.');
    $q=trim((string)($_GET['q'] ?? ''));
    $type=trim((string)($_GET['type'] ?? ''));
    $page=max(1,(int)($_GET['page'] ?? 1));
    $limit=60; $offset=($page-1)*$limit;
    $where=[]; $params=[];
    if($q!==''){
        $where[]='(file_name LIKE ? OR original_name LIKE ? OR alt_text LIKE ? OR title LIKE ?)';
        $like='%'.$q.'%'; $params=array_merge($params,[$like,$like,$like,$like]);
    }
    if($type==='image') $where[]="mime_type LIKE 'image/%'";
    elseif($type==='video') $where[]="mime_type LIKE 'video/%'";
    elseif($type==='file') $where[]="mime_type NOT LIKE 'image/%' AND mime_type NOT LIKE 'video/%'";
    $sql='SELECT id,file_path,file_name,alt_text,mime_type,width,height,created_at FROM '.table_name('media');
    if($where) $sql.=' WHERE '.implode(' AND ',$where);
    $sql.=' ORDER BY created_at DESC LIMIT '.$limit.' OFFSET '.$offset;
    $st=db()->prepare($sql); $st->execute($params);
    $items=[];
    foreach($st->fetchAll() as $m){
        $items[]=['id'=>(int)$m['id'],'src'=>$m['file_path'],'thumb'=>image_url($m['file_path']),'alt'=>($m['alt_text'] ?: $m['file_name']),'name'=>$m['file_name'],'mime'=>$m['mime_type'] ?? ''];
    }
    echo json_encode(['ok'=>true,'items'=>$items,'page'=>$page], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
