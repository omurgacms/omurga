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
    $t=table_name('media');
    try{ $cols=db()->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_COLUMN); }catch(Throwable $e){ $cols=[]; }
    $pathCol=in_array('file_path',$cols,true) ? 'file_path' : (in_array('path',$cols,true) ? 'path' : null);
    $nameCol=in_array('file_name',$cols,true) ? 'file_name' : (in_array('filename',$cols,true) ? 'filename' : null);
    if(!$pathCol || !$nameCol) throw new RuntimeException('Medya tablosu beklenen alanları içermiyor.');
    $mimeCol=in_array('mime_type',$cols,true) ? 'mime_type' : (in_array('mime',$cols,true) ? 'mime' : null);
    $selectMime=$mimeCol ? "$mimeCol AS mime_value" : "'' AS mime_value";
    $altSelect=in_array('alt_text',$cols,true) ? 'alt_text' : "'' AS alt_text";
    $where=[]; $params=[];
    if($q!==''){
        $parts=["$nameCol LIKE ?","$pathCol LIKE ?"]; $params[]='%'.$q.'%'; $params[]='%'.$q.'%';
        foreach(['original_filename','original_name','alt_text','title','title_text'] as $c){ if(in_array($c,$cols,true)){ $parts[]="$c LIKE ?"; $params[]='%'.$q.'%'; } }
        $where[]='('.implode(' OR ', $parts).')';
    }
    if($mimeCol){
        if($type==='image') $where[]="$mimeCol LIKE 'image/%'";
        elseif($type==='video') $where[]="$mimeCol LIKE 'video/%'";
        elseif($type==='file') $where[]="($mimeCol NOT LIKE 'image/%' AND $mimeCol NOT LIKE 'video/%')";
    }
    $sql="SELECT id,$pathCol AS file_path,$nameCol AS file_name,$altSelect,$selectMime FROM $t";
    if($where) $sql.=' WHERE '.implode(' AND ',$where);
    $sql.=' ORDER BY created_at DESC LIMIT '.$limit.' OFFSET '.$offset;
    $st=db()->prepare($sql); $st->execute($params);
    $items=[];
    foreach($st->fetchAll() as $m){
        $items[]=['id'=>(int)$m['id'],'src'=>$m['file_path'],'thumb'=>image_url($m['file_path']),'alt'=>($m['alt_text'] ?: $m['file_name']),'name'=>$m['file_name'],'mime'=>$m['mime_value'] ?? ''];
    }
    echo json_encode(['ok'=>true,'items'=>$items,'page'=>$page], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
