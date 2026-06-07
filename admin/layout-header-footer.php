<?php require '_layout.php'; verify_csrf(); require_cap('layout.manage');
$theme = omurga_active_theme();
$regions = omurga_theme_regions($theme);
$allRegions = omurga_theme_regions($theme);
$regions = [];
foreach($allRegions as $regionKey=>$regionLabel){
    if(in_array(omurga_region_kind((string)$regionKey), ['header','footer'], true)) $regions[$regionKey]=$regionLabel;
}
if(!$regions) $regions=['header'=>'Üst Alan','footer'=>'Alt Alan'];
$regionUsage = function_exists('omurga_theme_region_usage') ? omurga_theme_region_usage($theme) : [];
$inactiveRegions = [];
foreach($regions as $regionKey=>$regionLabel){
    if(empty($regionUsage[$regionKey])) $inactiveRegions[] = $regionLabel;
}
$layout = omurga_layout($theme);
$notice='';
function layout_find_remove(array &$layout, string $id, string $region): bool {
    if($region === '' || !isset($layout[$region]) || !is_array($layout[$region])) return false;
    foreach($layout[$region] as $i=>$b){
        if(($b['id'] ?? '')===$id){
            unset($layout[$region][$i]);
            $layout[$region]=array_values($layout[$region]);
            return true;
        }
    }
    return false;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? 'save';
    if($action==='reset'){
        $layout=omurga_default_layout($theme);
        omurga_update_layout($layout, $theme);
        $notice='Düzen varsayılan haline döndürüldü.';
    } elseif($action==='add'){
        $region=preg_replace('/[^a-z0-9_\-]/','',$_POST['region'] ?? '');
        $slug=omurga_normalize_block_slug((string)($_POST['block_slug'] ?? ''));
        $def=omurga_block_definition($slug);
        if($region && isset($regions[$region]) && $def && isset(omurga_available_blocks($region)[$slug])){
            $layout[$region] = $layout[$region] ?? [];
            $layout[$region][] = [
                'id'=>'blk_'.date('YmdHis').'_'.bin2hex(random_bytes(3)),
                'slug'=>$slug,
                'source'=>$def['source'] ?? 'core',
                'enabled'=>1,
                'sort'=>count($layout[$region])*10+10,
                'width'=>omurga_block_width_value(($_POST['width'] ?? 'auto') === 'auto' ? omurga_smart_width_for_new($layout[$region]) : ($_POST['width'] ?? '100')),
                'settings'=>omurga_block_defaults($slug),
            ];
            omurga_update_layout($layout, $theme);
            $notice='Blok düzene eklendi.';
        } else { $notice='Blok eklenemedi. Bu blok seçilen alanda kullanılamıyor olabilir.'; }
    } elseif($action==='remove'){
        $id=preg_replace('/[^a-zA-Z0-9_\-]/','',$_POST['id'] ?? '');
        $region=preg_replace('/[^a-z0-9_\-]/','',$_POST['region'] ?? '');
        if($id && isset($regions[$region]) && layout_find_remove($layout,$id,$region)){
            omurga_update_layout($layout, $theme);
            $notice='Blok '.($regions[$region] ?? $region).' alanından kaldırıldı.';
        } else {
            $notice='Blok kaldırılamadı. Seçilen alan ile blok eşleşmedi.';
        }
    } else {
        $new=[];
        foreach($_POST['blocks'] ?? [] as $region=>$items){
            $region=preg_replace('/[^a-z0-9_\-]/','',$region);
            if(!isset($regions[$region])) continue;
            foreach($items as $id=>$raw){
                $id=preg_replace('/[^a-zA-Z0-9_\-]/','',$id);
                $slug=omurga_normalize_block_slug((string)($raw['slug'] ?? ''));
                if(!$id || !$slug) continue;
                $def=omurga_block_definition($slug);
                $settings=[];
                if($def){
                    foreach(($def['settings'] ?? []) as $key=>$field){
                        $val=$raw['settings'][$key] ?? ($field['type'] === 'checkbox' ? '0' : ($field['default'] ?? ''));
                        if(($field['type'] ?? '')==='number') $val=(string)max(0,(int)$val);
                        if(($field['type'] ?? '')==='checkbox') $val=!empty($raw['settings'][$key])?'1':'0';
                        $settings[$key]=$val;
                    }
                } elseif(is_array($raw['settings'] ?? null)){
                    $settings=$raw['settings'];
                }
                $new[$region][]=[
                    'id'=>$id,
                    'slug'=>$slug,
                    'source'=>$def['source'] ?? 'missing',
                    'enabled'=>!empty($raw['enabled'])?1:0,
                    'sort'=>(int)($raw['sort'] ?? 100),
                    'width'=>omurga_block_width_value($raw['width'] ?? '100'),
                    'settings'=>$settings,
                ];
            }
            if (isset($new[$region]) && is_array($new[$region])) {
                usort($new[$region], fn($a,$b)=>(int)$a['sort'] <=>(int)$b['sort']);
            }
        }
        $layout=$new;
        omurga_update_layout($layout, $theme);
        $notice='Düzen kaydedildi.';
    }
}
$categories=[]; try{ $categories=db()->query('SELECT id,name FROM '.table_name('categories').' ORDER BY sort_order,name')->fetchAll(); }catch(Throwable $e){}
function render_setting_input(string $name, array $field, $value, array $categories): string {
    $type=$field['type'] ?? 'text'; $label=$field['label'] ?? $name; $html='<label><span>'.e($label).'</span>';
    if($type==='textarea') $html.='<textarea name="'.e($name).'" rows="3">'.e($value).'</textarea>';
    elseif($type==='number') $html.='<input type="number" name="'.e($name).'" value="'.e($value).'">';
    elseif($type==='checkbox') $html.='<input type="checkbox" name="'.e($name).'" value="1" '.(!empty($value)?'checked':'').'>';
    elseif($type==='color') $html.='<input type="color" name="'.e($name).'" value="'.e($value ?: '#f97316').'">';
    elseif($type==='category') { $html.='<select name="'.e($name).'"><option value="">Tüm kategoriler</option>'; foreach($categories as $c){ $sel=((string)$value===(string)$c['id'])?'selected':''; $html.='<option value="'.e($c['id']).'" '.$sel.'>'.e($c['name']).'</option>'; } $html.='</select>'; }
    elseif($type==='select') { $html.='<select name="'.e($name).'">'; foreach(($field['options'] ?? []) as $k=>$v){ $sel=((string)$value===(string)$k)?'selected':''; $html.='<option value="'.e($k).'" '.$sel.'>'.e($v).'</option>'; } $html.='</select>'; }
    else $html.='<input type="text" name="'.e($name).'" value="'.e($value).'">';
    return $html.'</label>';
}
function layout_hf_block_options_for_region(string $region): string {
    $groups=[];
    foreach(omurga_available_blocks($region) as $slug=>$def){
        $groups[omurga_block_source_label($def).' Bloklar'][$slug]=$def;
    }
    $html='';
    foreach($groups as $label=>$blocks){
        $items='';
        foreach($blocks as $slug=>$def){
            $items.='<option value="'.e($slug).'">'.e($def['name'] ?? $slug).'</option>';
        }
        if($items) $html.='<optgroup label="'.e($label).'">'.$items.'</optgroup>';
    }
    return $html;
}
?>
<div class="toolbar"><div><h1>Header / Footer Düzeni</h1><p class="muted"><a href="layout.php" class="btn light" style="margin-right:8px">Ana Sayfa Düzeni</a> Aktif tema: <b><?=e(omurga_theme_meta($theme)['name'] ?? $theme)?></b>. Alanlar ve tema blokları aktif temadan gelir.</p></div><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button name="action" value="reset" class="btn light" onclick="return confirm('Bu temanın düzeni varsayılana dönsün mü?')">Varsayılana Dön</button></form></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($inactiveRegions): ?>
  <div class="alert warning"><strong>Tema entegrasyonu:</strong> Aktif tema bu alanları ön yüzde çağırmıyor olabilir: <?=e(implode(', ', $inactiveRegions))?>. Header/Footer düzeni kaydedilir; görünmesi için tema dosyalarında ilgili region çağrısı bulunmalıdır.</div>
<?php endif; ?>
<div class="layout-help card"><strong>Header / Footer düzeni:</strong> Blogger’daki Gadget mantığının Omurga karşılığıdır. Üst alan, alt alan ve mobil alt alan bloklarını buradan yönet. Ana sayfa bloklarından ayrı tutulur.</div>
<form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="save">
<div class="layout-regions">
<?php foreach($regions as $region=>$label): $blocks=$layout[$region] ?? []; ?>
  <section class="layout-region card">
    <div class="layout-region-head"><div><h2><?=e($label)?></h2><small><?=e($region)?></small></div><button class="btn primary" type="submit">Düzeni Kaydet</button></div>
    <?php if(!$blocks): ?><p class="muted">Bu alanda blok yok.</p><?php endif; ?>
    <?php foreach(omurga_layout_rows($blocks) as $rowIndex=>$rowBlocks): ?><div class="layout-row"><div class="layout-row-title">Satır <?=e((string)($rowIndex+1))?></div><?php foreach($rowBlocks as $b): $id=$b['id'] ?? ('blk_'.md5(json_encode($b))); $slug=$b['slug'] ?? ''; $def=omurga_block_definition($slug); $missingBlock=!$def; $settings=$def ? array_merge(omurga_block_defaults($slug), $b['settings'] ?? []) : ($b['settings'] ?? []); $blockName=$def['name'] ?? ('Eksik blok: '.$slug); $blockSource=$def ? omurga_block_source_label($def).' Blok' : 'Eksik Blok'; ?>
      <div class="layout-block layout-preview-<?=e(omurga_block_width_value($b['width'] ?? '100'))?>"> 
        <div class="layout-block-top"><strong><?=e($blockName)?></strong><em><?=e($blockSource)?></em><span><?=e($slug)?></span><span class="width-pill"><?=e(omurga_block_widths()[omurga_block_width_value($b['width'] ?? '100')] ?? '%100')?></span></div>
        <input type="hidden" name="blocks[<?=e($region)?>][<?=e($id)?>][slug]" value="<?=e($slug)?>">
        <?php if($missingBlock): foreach($settings as $mk=>$mv): if(is_scalar($mv)): ?><input type="hidden" name="blocks[<?=e($region)?>][<?=e($id)?>][settings][<?=e((string)$mk)?>]" value="<?=e((string)$mv)?>"><?php endif; endforeach; endif; ?>
        <div class="layout-block-grid">
          <label><span>Göster</span><input type="checkbox" name="blocks[<?=e($region)?>][<?=e($id)?>][enabled]" value="1" <?=!empty($b['enabled'])?'checked':''?>></label>
          <label><span>Sıra</span><input type="number" name="blocks[<?=e($region)?>][<?=e($id)?>][sort]" value="<?=e($b['sort'] ?? 100)?>"></label>
          <label><span>Genişlik</span><select name="blocks[<?=e($region)?>][<?=e($id)?>][width]"><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>" <?=omurga_block_width_value($b['width'] ?? '100')===$wv?'selected':''?>><?=e($wl)?></option><?php endforeach; ?></select></label>
          <?php foreach(($def['settings'] ?? []) as $key=>$field): ?>
            <?=render_setting_input('blocks['.e($region).']['.e($id).'][settings]['.e($key).']', $field, $settings[$key] ?? ($field['default'] ?? ''), $categories)?>
          <?php endforeach; ?>
        </div>
        <button class="btn danger" form="remove-<?=e($id)?>" type="submit" onclick="return confirm('Bu blok kaldırılsın mı?')">Kaldır</button>
      </div>
    <?php endforeach; ?></div><?php endforeach; ?>
  </section>
<?php endforeach; ?>
</div><button class="btn primary sticky-save">Düzeni Kaydet</button></form>
<?php foreach($regions as $region=>$label): ?>
<form method="post" class="layout-add card"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="add"><input type="hidden" name="region" value="<?=e($region)?>"><strong><?=e($label)?> alanına blok ekle</strong><select name="block_slug">
  <?=layout_hf_block_options_for_region($region)?>
  </select><select name="width" title="Genişlik"><option value="auto">Otomatik / Kalan Alan</option><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>"><?=e($wl)?></option><?php endforeach; ?></select><button class="btn light">Blok Ekle</button></form>
<?php endforeach; ?>
<?php foreach($layout as $region=>$blocks){ foreach($blocks as $b){ $id=$b['id'] ?? ''; if($id): ?><form id="remove-<?=e($id)?>" method="post" style="display:none"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="region" value="<?=e($region)?>"><input type="hidden" name="id" value="<?=e($id)?>"></form><?php endif; }} ?>
<?php require '_footer.php'; ?>
