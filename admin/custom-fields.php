<?php
require '_layout.php';
require_cap('custom_fields.manage');
$groups = omurga_custom_field_groups();
$types = omurga_custom_field_types();
$cats = [];
try{ $cats=db()->query('SELECT id,name FROM '.table_name('categories').' ORDER BY name ASC')->fetchAll(); }catch(Throwable $e){}
function om_cf_find_index(array $groups, string $id): int { foreach($groups as $i=>$g){ if((string)($g['id']??'')===$id) return (int)$i; } return -1; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $action=$_POST['action'] ?? '';
  if($action==='save_group'){
    $gid=preg_replace('/[^a-z0-9_\-]/','', strtolower($_POST['group_id'] ?? '')) ?: ('group_'.time());
    $idx=om_cf_find_index($groups,$gid);
    $targets=[]; foreach((array)($_POST['targets'] ?? []) as $t){ if(in_array($t,['post','page'],true)) $targets[]=$t; }
    if(!$targets) $targets=['post'];
    $fields=[];
    foreach((array)($_POST['field_key'] ?? []) as $i=>$key){
      $key=omurga_custom_field_slug($key ?: ($_POST['field_label'][$i] ?? ''));
      if($key==='') continue;
      $ft=$_POST['field_type'][$i] ?? 'text'; if(!isset($types[$ft])) $ft='text';
      $fields[]=[
        'key'=>$key,
        'label'=>trim((string)($_POST['field_label'][$i] ?? $key)) ?: $key,
        'type'=>$ft,
        'default'=>trim((string)($_POST['field_default'][$i] ?? '')),
        'help'=>trim((string)($_POST['field_help'][$i] ?? '')),
        'options'=>trim((string)($_POST['field_options'][$i] ?? '')),
        'show_admin'=>!empty($_POST['field_show_admin'][$i]) ? 1 : 0,
        'show_frontend'=>!empty($_POST['field_show_frontend'][$i]) ? 1 : 0,
      ];
    }
    $group=[
      'id'=>$gid,
      'name'=>trim((string)($_POST['name'] ?? 'Özel Alan Grubu')) ?: 'Özel Alan Grubu',
      'active'=>!empty($_POST['active'])?1:0,
      'targets'=>$targets,
      'category_ids'=>array_values(array_filter(array_map('intval',(array)($_POST['category_ids'] ?? [])))),
      'fields'=>$fields,
    ];
    if($idx>=0) $groups[$idx]=$group; else $groups[]=$group;
    omurga_update_custom_field_groups($groups);
    echo '<div class="alert success">Özel alan grubu kaydedildi.</div>';
  } elseif($action==='delete_group'){
    $gid=(string)($_POST['group_id'] ?? '');
    $groups=array_values(array_filter($groups, fn($g)=>(string)($g['id']??'')!==$gid));
    omurga_update_custom_field_groups($groups);
    echo '<div class="alert success">Özel alan grubu silindi.</div>';
  }
}
$editId=(string)($_GET['edit'] ?? '');
$editIdx=om_cf_find_index($groups,$editId);
$edit=$editIdx>=0?$groups[$editIdx]:['id'=>'','name'=>'','active'=>1,'targets'=>['post'],'category_ids'=>[],'fields'=>[]];
?>
<div class="page-head"><div><h1>Özel Alanlar</h1><p>Yazı ve sayfalara kaynak, muhabir, video, etkinlik tarihi gibi ek alanlar ekle.</p></div></div>
<div class="grid two custom-fields-admin">
  <section class="card">
    <h2><?= $editIdx>=0 ? 'Alan Grubunu Düzenle' : 'Yeni Alan Grubu' ?></h2>
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="save_group">
      <input type="hidden" name="group_id" value="<?=e($edit['id'] ?: '')?>">
      <label>Grup Adı<input name="name" value="<?=e($edit['name'] ?? '')?>" placeholder="Haber Ek Alanları"></label>
      <label class="check-line"><input type="checkbox" name="active" value="1" <?=!isset($edit['active']) || (int)$edit['active']===1?'checked':''?>> Aktif</label>
      <div class="mini-grid two">
        <label class="check-line"><input type="checkbox" name="targets[]" value="post" <?=in_array('post',(array)($edit['targets']??[]),true)?'checked':''?>> Yazılarda göster</label>
        <label class="check-line"><input type="checkbox" name="targets[]" value="page" <?=in_array('page',(array)($edit['targets']??[]),true)?'checked':''?>> Sayfalarda göster</label>
      </div>
      <label>Kategori Kısıtı <small>Boşsa tüm yazı kategorilerinde görünür.</small><select name="category_ids[]" multiple size="5">
        <?php foreach($cats as $c): ?><option value="<?=$c['id']?>" <?=in_array((int)$c['id'],array_map('intval',(array)($edit['category_ids']??[])),true)?'selected':''?>><?=e($c['name'])?></option><?php endforeach; ?>
      </select></label>
      <h3>Alanlar</h3>
      <div id="cfFields">
        <?php $fields=$edit['fields'] ?: [['key'=>'kaynak','label'=>'Kaynak','type'=>'text','default'=>'','help'=>'','options'=>'','show_admin'=>1,'show_frontend'=>1]]; foreach($fields as $i=>$f): ?>
        <div class="cf-row">
          <input name="field_label[]" value="<?=e($f['label']??'')?>" placeholder="Etiket">
          <input name="field_key[]" value="<?=e($f['key']??'')?>" placeholder="anahtar">
          <select name="field_type[]"><?php foreach($types as $tk=>$tv): ?><option value="<?=e($tk)?>" <?=($f['type']??'text')===$tk?'selected':''?>><?=e($tv)?></option><?php endforeach; ?></select>
          <input name="field_default[]" value="<?=e($f['default']??'')?>" placeholder="Varsayılan">
          <input name="field_help[]" value="<?=e($f['help']??'')?>" placeholder="Yardım metni">
          <textarea name="field_options[]" placeholder="Seçenekler: anahtar=Etiket"><?=e(is_array($f['options']??'')?implode("\n",$f['options']):($f['options']??''))?></textarea>
          <input type="hidden" name="field_show_admin[<?= (int)$i ?>]" value="0"><label class="check-line cf-check"><input type="checkbox" name="field_show_admin[<?= (int)$i ?>]" value="1" <?=!isset($f['show_admin']) || (int)$f['show_admin']===1?'checked':''?>> Admin formunda</label>
          <input type="hidden" name="field_show_frontend[<?= (int)$i ?>]" value="0"><label class="check-line cf-check"><input type="checkbox" name="field_show_frontend[<?= (int)$i ?>]" value="1" <?=!empty($f['show_frontend'])?'checked':''?>> Muhabir formunda</label>
          <button type="button" class="btn light" onclick="this.closest('.cf-row').remove()">Sil</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn light" onclick="omAddCustomFieldRow()">Alan Ekle</button>
      <button class="btn primary">Kaydet</button>
    </form>
  </section>
  <section class="card">
    <h2>Alan Grupları</h2>
    <?php if(!$groups): ?><p class="muted">Henüz özel alan grubu yok.</p><?php endif; ?>
    <?php foreach($groups as $g): ?>
      <div class="om-list-row"><div><b><?=e($g['name']??'')?></b><small><?=e(implode(', ',(array)($g['targets']??[])))?> · <?=count($g['fields']??[])?> alan</small></div><div class="row-actions"><a class="btn light" href="custom-fields.php?edit=<?=e($g['id'])?>">Düzenle</a><form method="post" onsubmit="return confirm('Silinsin mi?')" style="display:inline"><?=csrf_field()?><input type="hidden" name="action" value="delete_group"><input type="hidden" name="group_id" value="<?=e($g['id'])?>"><button class="btn danger">Sil</button></form></div></div>
    <?php endforeach; ?>
    <hr><h3>OMG Kullanımı</h3><code>{{ field('kaynak') }}</code><br><code>@if(field('video_url')) ... @endif</code>
  </section>
</div>
<script>
function omAddCustomFieldRow(){
 var wrap=document.getElementById('cfFields');
 var div=document.createElement('div'); div.className='cf-row';
 var idx=wrap.querySelectorAll('.cf-row').length;
 div.innerHTML='<input name="field_label[]" placeholder="Etiket"><input name="field_key[]" placeholder="anahtar"><select name="field_type[]"><?php foreach($types as $tk=>$tv): ?><option value="<?=e($tk)?>"><?=e($tv)?></option><?php endforeach; ?></select><input name="field_default[]" placeholder="Varsayılan"><input name="field_help[]" placeholder="Yardım metni"><textarea name="field_options[]" placeholder="Seçenekler"></textarea><input type="hidden" name="field_show_admin['+idx+']" value="0"><label class="check-line cf-check"><input type="checkbox" name="field_show_admin['+idx+']" value="1" checked> Admin formunda</label><input type="hidden" name="field_show_frontend['+idx+']" value="0"><label class="check-line cf-check"><input type="checkbox" name="field_show_frontend['+idx+']" value="1"> Muhabir formunda</label><button type="button" class="btn light" onclick="this.closest(\'.cf-row\').remove()">Sil</button>';
 wrap.appendChild(div);
}
</script>
<?php require '_footer.php'; ?>
