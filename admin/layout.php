<?php require '_layout.php'; verify_csrf(); require_cap('layout.manage');
$theme = omurga_active_theme();
$regions = omurga_theme_regions($theme);
foreach(['header','footer','mobile_bottom'] as $hf){ unset($regions[$hf]); }
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
    } elseif($action==='home-sidebar-toggle'){
        update_setting(omurga_sidebar_setting_key($theme, 'home'), !empty($_POST['home_sidebar_enabled']) ? '1' : '0');
        $notice=!empty($_POST['home_sidebar_enabled']) ? 'Anasayfa sidebar açıldı.' : 'Anasayfa sidebar kapatıldı.';
    } elseif($action==='global-sidebar-toggle'){
        update_setting(omurga_sidebar_setting_key($theme, 'global'), !empty($_POST['global_sidebar_enabled']) ? '1' : '0');
        $notice=!empty($_POST['global_sidebar_enabled']) ? 'Genel sidebar açıldı.' : 'Genel sidebar kapatıldı.';
    } elseif($action==='add'){
        $region=preg_replace('/[^a-z0-9_\-]/','',$_POST['region'] ?? '');
        $slug=omurga_normalize_block_slug((string)($_POST['block_slug'] ?? ''));
        $def=omurga_block_definition($slug);
        if($region && isset($regions[$region]) && $def){
            if(!isset(omurga_available_blocks($region)[$slug])){
                $notice='Blok bu alanda kullanılamıyor.';
            } else {
                $layout[$region] = $layout[$region] ?? [];
                $layout[$region][] = [
                    'id'=>'blk_'.date('YmdHis').'_'.bin2hex(random_bytes(3)),
                    'slug'=>$slug,
                    'source'=>$def['source'] ?? 'core',
                    'enabled'=>1,
                    'sort'=>count($layout[$region])*10+10,
                    'width'=>omurga_block_width_value(($_POST['width'] ?? 'auto') === 'auto' ? omurga_smart_width_for_new($layout[$region]) : ($_POST['width'] ?? '100')),
                    'width_tablet'=>'',
                    'width_mobile'=>'',
                    'hide_mobile'=>0,
                    'mobile_scroll'=>0,
                    'settings'=>omurga_block_defaults($slug),
                ];
                omurga_update_layout($layout, $theme);
                $notice='Blok düzene eklendi.';
            }
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
                        $val=$raw['settings'][$key] ?? (($field['type'] ?? '') === 'checkbox' ? '0' : ($field['default'] ?? ''));
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
                    'width_tablet'=>!empty($raw['width_tablet']) ? omurga_block_width_value($raw['width_tablet']) : '',
                    'width_mobile'=>!empty($raw['width_mobile']) ? omurga_block_width_value($raw['width_mobile']) : '',
                    'hide_mobile'=>!empty($raw['hide_mobile'])?1:0,
                    'mobile_scroll'=>!empty($raw['mobile_scroll'])?1:0,
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
function layout_block_options_for_region(string $region, string $theme): string {
    $html='';
    $groups=[];
    foreach(omurga_available_blocks($region) as $slug=>$def){
        $groups[omurga_block_source_label($def).' Bloklar'][$slug]=$def;
    }
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
<div class="toolbar compact-page-head"><div><h1>Düzen Alanı</h1><p class="muted">Aktif tema: <b><?=e(omurga_theme_meta($theme)['name'] ?? $theme)?></b>. Blokları ekle, genişliğini seç, ayarlarını aç/kapat.</p></div><div class="layout-top-actions"><button type="button" class="btn light layout-mobile-inspector-btn" id="layoutMobileInspectorBtn">Blok Ayarları</button><a href="layout-header-footer.php" class="btn light">Header / Footer</a><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button name="action" value="reset" class="btn light" onclick="return confirm('Bu temanın düzeni varsayılana dönsün mü?')">Varsayılana Dön</button></form></div></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php $firstRegion = array_key_first($regions); ?>
<div class="layout-region-tabs card" role="tablist" aria-label="Düzen alanları">
  <?php foreach($regions as $region=>$label): $count=count($layout[$region] ?? []); ?>
    <button type="button" class="layout-tab <?= $region===$firstRegion ? 'active' : '' ?>" data-target="<?=e($region)?>">
      <strong><?=e($label)?></strong><span><?=e((string)$count)?> blok</span>
    </button>
  <?php endforeach; ?>
</div>
<div class="layout-device-preview card" aria-label="Cihaz önizleme">
  <strong>Önizleme</strong>
  <button type="button" class="active" data-preview-device="desktop">Masaüstü</button>
  <button type="button" data-preview-device="tablet">Tablet</button>
  <button type="button" data-preview-device="mobile">Mobil</button>
</div>
<div class="layout-workbench">
  <div class="layout-work-main">
    <form method="post" id="layoutSaveForm"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="save">
      <div class="layout-regions">
      <?php foreach($regions as $region=>$label): $blocks=$layout[$region] ?? []; ?>
        <section class="layout-region card designer-region <?= $region===$firstRegion ? 'active-region' : '' ?>" id="region-<?=e($region)?>" data-region="<?=e($region)?>" data-region-label="<?=e($label)?>">
          <div class="layout-region-head"><div><h2><?=e($label)?></h2><small><?=e($region)?> · <?=count($blocks)?> blok</small></div><button class="btn primary" type="submit">Kaydet</button></div>
          <?php if(!$blocks): ?><div class="layout-region-empty">Bu alanda blok yok. Sağdaki panelden bu alana blok ekleyebilirsin.</div><?php endif; ?>
          <?php foreach(omurga_layout_rows($blocks) as $rowIndex=>$rowBlocks): $rowId='row_'.$region.'_'.($rowIndex+1); ?><div class="layout-row" data-layout-row data-row-id="<?=e($rowId)?>" data-section-id="<?=e($region)?>"><div class="layout-row-title"><span>Satır <?=e((string)($rowIndex+1))?></span><em><?=e(count($rowBlocks))?> kolon</em></div><?php foreach($rowBlocks as $colIndex=>$b): $id=$b['id'] ?? ('blk_'.md5(json_encode($b))); $slug=$b['slug'] ?? ''; $def=omurga_block_definition($slug); $missingBlock=!$def; $settings=$def ? array_merge(omurga_block_defaults($slug), $b['settings'] ?? []) : ($b['settings'] ?? []); $blockName=$def['name'] ?? ('Eksik blok: '.$slug); $blockSource=$def ? omurga_block_source_label($def).' Blok' : 'Eksik Blok'; $colId='col_'.$region.'_'.($rowIndex+1).'_'.($colIndex+1); $tabletWidth=(string)($b['width_tablet'] ?? ''); $mobileWidth=(string)($b['width_mobile'] ?? ''); ?>
            <div class="layout-block layout-preview-<?=e(omurga_block_width_value($b['width'] ?? '100'))?>" data-layout-block data-component-id="<?=e($id)?>" data-column-id="<?=e($colId)?>" data-row-id="<?=e($rowId)?>" data-section-id="<?=e($region)?>" data-block-id="<?=e($id)?>" data-block-region="<?=e($region)?>" data-block-region-label="<?=e($label)?>" data-block-title="<?=e($def['name'] ?? $slug)?>" data-block-slug="<?=e($slug)?>" tabindex="0"> 
              <div class="layout-block-top"><strong><?=e($blockName)?></strong><em><?=e($blockSource)?></em><span><?=e($slug)?></span><span class="width-pill"><?=e(omurga_block_widths()[omurga_block_width_value($b['width'] ?? '100')] ?? '%100')?></span></div>
              <input type="hidden" name="blocks[<?=e($region)?>][<?=e($id)?>][slug]" value="<?=e($slug)?>">
              <?php if($missingBlock): foreach($settings as $mk=>$mv): if(is_scalar($mv)): ?><input type="hidden" name="blocks[<?=e($region)?>][<?=e($id)?>][settings][<?=e((string)$mk)?>]" value="<?=e((string)$mv)?>"><?php endif; endforeach; endif; ?>
              <div class="layout-block-quick">
                <label class="quick-enabled inspector-data"><input type="checkbox" name="blocks[<?=e($region)?>][<?=e($id)?>][enabled]" value="1" <?=!empty($b['enabled'])?'checked':''?>> Göster</label>
                <label class="sort-field inspector-data"><span>Sıra</span><input type="number" name="blocks[<?=e($region)?>][<?=e($id)?>][sort]" value="<?=e($b['sort'] ?? 100)?>"></label>
                <label class="inspector-style"><span>Genişlik</span><select name="blocks[<?=e($region)?>][<?=e($id)?>][width]"><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>" <?=omurga_block_width_value($b['width'] ?? '100')===$wv?'selected':''?>><?=e($wl)?></option><?php endforeach; ?></select></label>
                <label class="inspector-style"><span>Tablet</span><select name="blocks[<?=e($region)?>][<?=e($id)?>][width_tablet]"><option value="" <?=$tabletWidth===''?'selected':''?>>Akıllı</option><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>" <?=$tabletWidth!=='' && omurga_block_width_value($tabletWidth)===$wv?'selected':''?>><?=e($wl)?></option><?php endforeach; ?></select></label>
                <label class="inspector-style"><span>Mobil</span><select name="blocks[<?=e($region)?>][<?=e($id)?>][width_mobile]"><option value="" <?=$mobileWidth===''?'selected':''?>>%100 / Akıllı</option><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>" <?=$mobileWidth!=='' && omurga_block_width_value($mobileWidth)===$wv?'selected':''?>><?=e($wl)?></option><?php endforeach; ?></select></label>
                <label class="quick-enabled inspector-style"><input type="checkbox" name="blocks[<?=e($region)?>][<?=e($id)?>][hide_mobile]" value="1" <?=!empty($b['hide_mobile'])?'checked':''?>> Mobilde gizle</label>
                <label class="quick-enabled inspector-style"><input type="checkbox" name="blocks[<?=e($region)?>][<?=e($id)?>][mobile_scroll]" value="1" <?=!empty($b['mobile_scroll'])?'checked':''?>> Mobilde yatay kaydır</label>
                <span class="quick-spacer"></span>
                <button class="btn danger" form="remove-<?=e($id)?>" type="submit" onclick="return confirm('Bu blok <?=e($label)?> alanından silinecek. Devam edilsin mi?')">Kaldır</button>
                <?php if(!empty($def['settings'])): ?><details class="layout-block-settings inspector-data"><summary>Blok ayarları</summary><div class="layout-block-grid">
                  <?php foreach(($def['settings'] ?? []) as $key=>$field): ?>
                    <?=render_setting_input('blocks['.e($region).']['.e($id).'][settings]['.e($key).']', $field, $settings[$key] ?? ($field['default'] ?? ''), $categories)?>
                  <?php endforeach; ?>
                </div></details><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?></div><?php endforeach; ?>
        </section>
      <?php endforeach; ?>
      </div>
      <div class="layout-save-bar"><span>Genişlik değişince satır önizlemesi canlı güncellenir. Kaydetmeden çıkarsan değişiklikler uygulanmaz.</span><button class="btn primary">Düzeni Kaydet</button></div>
    </form>
  </div>
  <div class="layout-inspector-overlay" id="layoutInspectorOverlay"></div>
  <aside class="layout-side-panel layout-inspector-panel" id="layoutInspector" aria-label="Blok ayarlari">
    <div class="card layout-inspector-card">
      <div class="layout-inspector-head">
        <div>
          <h2 id="layoutInspectorTitle">Blok Ayarları</h2>
          <small id="layoutInspectorMeta">Canvas alanından bir blok seç.</small>
        </div>
        <button type="button" class="btn light layout-inspector-close" id="layoutInspectorClose">Kapat</button>
      </div>
      <div class="layout-inspector-empty" id="layoutInspectorEmpty">Sol canvas alanındaki bir blok kartına tıklayın. Ayarlar burada içerik ve görünüm sekmeleriyle açılır.</div>
      <div class="layout-inspector-live" id="layoutInspectorLive" hidden>
        <div class="layout-inspector-tabs" role="tablist" aria-label="Blok ayar sekmeleri">
          <button type="button" class="active" data-inspector-tab="data">İçerik / Veri</button>
          <button type="button" data-inspector-tab="style">Görünüm / Stil</button>
        </div>
        <div class="layout-inspector-pane active" data-inspector-pane="data"><div id="layoutInspectorData" class="layout-inspector-fields"></div></div>
        <div class="layout-inspector-pane" data-inspector-pane="style"><div id="layoutInspectorStyle" class="layout-inspector-fields"></div></div>
        <div class="layout-inspector-actions" id="layoutInspectorActions"></div>
      </div>
    </div>
    <form method="post" class="card layout-add-panel"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="add"><h2>Blok Ekle</h2><p class="muted">Önce alanı seç, sonra bloğu ekle.</p><div class="form-grid"><label><span>Alan</span><select name="region" id="layout-add-region"><?php foreach($regions as $region=>$label): ?><option value="<?=e($region)?>"><?=e($label)?></option><?php endforeach; ?></select></label><?php foreach($regions as $region=>$label): ?><label class="layout-block-select" data-region="<?=e($region)?>" style="display:<?=array_key_first($regions)===$region?'grid':'none'?>"><span>Blok</span><select name="block_slug" <?=array_key_first($regions)===$region?'':'disabled'?>><?=layout_block_options_for_region($region,$theme)?></select></label><?php endforeach; ?><label><span>Genişlik</span><select name="width"><option value="auto">Otomatik / Kalan Alan</option><?php foreach(omurga_block_widths() as $wv=>$wl): ?><option value="<?=e($wv)?>"><?=e($wl)?></option><?php endforeach; ?></select></label><button class="btn primary">Blok Ekle</button></div></form>
    <div class="card layout-info-panel"><h2>Sidebar</h2><form method="post" class="sidebar-toggle-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="home-sidebar-toggle"><label class="switch-line"><input type="checkbox" name="home_sidebar_enabled" value="1" <?=omurga_sidebar_enabled($theme,'home')?'checked':''?>> <span>Anasayfa sidebar</span></label><button class="btn light">Uygula</button></form><form method="post" class="sidebar-toggle-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="global-sidebar-toggle"><label class="switch-line"><input type="checkbox" name="global_sidebar_enabled" value="1" <?=omurga_sidebar_enabled($theme,'global')?'checked':''?>> <span>Genel sidebar</span></label><button class="btn light">Uygula</button></form></div>
    <div class="card layout-info-panel"><h2>Kullanım</h2><ul class="layout-mini-help"><li><b>%50 + %50</b> aynı satıra gelir.</li><li><b>%70 + %30</b> aynı satıra gelir.</li><li><b>%50 + %70</b> yeni satıra geçer.</li><li>Blok ayarları sadece ihtiyaç olunca açılır.</li></ul></div>
  </aside>
</div>
<?php foreach($layout as $region=>$blocks){ foreach($blocks as $b){ $id=$b['id'] ?? ''; if($id): ?><form id="remove-<?=e($id)?>" method="post" style="display:none"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="region" value="<?=e($region)?>"><input type="hidden" name="id" value="<?=e($id)?>"></form><?php endif; }} ?>
<script>
(function(){
  var tabs=document.querySelectorAll('.layout-tab');
  var panels=document.querySelectorAll('.designer-region');
  function showRegion(key){
    tabs.forEach(function(t){t.classList.toggle('active', t.getAttribute('data-target')===key);});
    panels.forEach(function(p){p.classList.toggle('active-region', p.getAttribute('data-region')===key);});
    var addRegion=document.getElementById('layout-add-region');
    if(addRegion){ addRegion.value=key; addRegion.dispatchEvent(new Event('change')); }
    document.dispatchEvent(new CustomEvent('omurga:layout-region-change', {detail:{region:key}}));
  }
  tabs.forEach(function(t){ t.addEventListener('click', function(){ showRegion(t.getAttribute('data-target')); }); });
})();
(function(){
  var region=document.getElementById('layout-add-region');
  if(!region) return;
  function refresh(){
    document.querySelectorAll('.layout-block-select').forEach(function(w){
      var on=w.getAttribute('data-region')===region.value;
      w.style.display=on?'grid':'none';
      var s=w.querySelector('select'); if(s) s.disabled=!on;
    });
  }
  region.addEventListener('change', refresh); refresh();
})();
(function(){
  var main=document.querySelector('.layout-work-main');
  var buttons=document.querySelectorAll('[data-preview-device]');
  if(!main || !buttons.length) return;
  buttons.forEach(function(btn){
    btn.addEventListener('click', function(){
      var device=btn.getAttribute('data-preview-device') || 'desktop';
      buttons.forEach(function(item){ item.classList.toggle('active', item===btn); });
      main.classList.toggle('preview-tablet', device==='tablet');
      main.classList.toggle('preview-mobile', device==='mobile');
    });
  });
})();
(function(){
  var inspector=document.getElementById('layoutInspector');
  var overlay=document.getElementById('layoutInspectorOverlay');
  var mobileBtn=document.getElementById('layoutMobileInspectorBtn');
  var closeBtn=document.getElementById('layoutInspectorClose');
  var title=document.getElementById('layoutInspectorTitle');
  var meta=document.getElementById('layoutInspectorMeta');
  var empty=document.getElementById('layoutInspectorEmpty');
  var live=document.getElementById('layoutInspectorLive');
  var dataBox=document.getElementById('layoutInspectorData');
  var styleBox=document.getElementById('layoutInspectorStyle');
  var actionsBox=document.getElementById('layoutInspectorActions');
  var moved=[];
  var selectedRegion='';
  var selectedBlockId='';
  if(!inspector || !dataBox || !styleBox || !actionsBox) return;

  function isMobile(){
    return window.matchMedia && window.matchMedia('(max-width: 1150px)').matches;
  }
  function openDrawer(){
    inspector.classList.add('open');
    if(overlay) overlay.classList.add('open');
  }
  function closeDrawer(){
    inspector.classList.remove('open');
    if(overlay) overlay.classList.remove('open');
  }
  function resetInspector(region){
    returnMoved();
    selectedRegion=region || selectedRegion || '';
    selectedBlockId='';
    document.querySelectorAll('[data-layout-block]').forEach(function(item){ item.classList.remove('is-selected'); });
    dataBox.innerHTML='';
    styleBox.innerHTML='';
    actionsBox.innerHTML='';
    if(title) title.textContent='Blok Ayarları';
    var active=document.querySelector('.designer-region.active-region');
    var label=(active && active.getAttribute('data-region-label')) || selectedRegion || 'aktif alan';
    if(meta) meta.textContent=label + ' alanından bir blok seçin.';
    if(empty){ empty.hidden=false; empty.textContent='Bu alandan bir blok seçin. Önceki alanın blokları burada gösterilmez.'; }
    if(live) live.hidden=true;
  }
  function returnMoved(){
    moved.forEach(function(item){
      if(item.marker && item.marker.parentNode){
        item.marker.parentNode.insertBefore(item.node, item.marker);
        item.marker.parentNode.removeChild(item.marker);
      }
    });
    moved=[];
  }
  function ensureFormOwner(root){
    root.querySelectorAll('input,select,textarea').forEach(function(el){
      el.setAttribute('form','layoutSaveForm');
    });
    root.querySelectorAll('button').forEach(function(el){
      if(!el.getAttribute('form')) el.setAttribute('form','layoutSaveForm');
    });
  }
  function moveNode(node,target){
    var marker=document.createComment('layout-inspector-origin');
    node.parentNode.insertBefore(marker,node);
    target.appendChild(node);
    if(node.tagName === 'DETAILS') node.open=true;
    ensureFormOwner(node);
    moved.push({node:node,marker:marker});
  }
  function activateTab(name){
    inspector.querySelectorAll('[data-inspector-tab]').forEach(function(btn){
      btn.classList.toggle('active', btn.getAttribute('data-inspector-tab')===name);
    });
    inspector.querySelectorAll('[data-inspector-pane]').forEach(function(pane){
      pane.classList.toggle('active', pane.getAttribute('data-inspector-pane')===name);
    });
  }
  function selectBlock(block, autoOpen){
    if(!block) return;
    var active=document.querySelector('.designer-region.active-region');
    var activeRegion=active ? active.getAttribute('data-region') : '';
    var blockRegion=block.getAttribute('data-block-region') || '';
    if(activeRegion && blockRegion && activeRegion !== blockRegion){
      resetInspector(activeRegion);
      return;
    }
    returnMoved();
    selectedRegion=blockRegion || activeRegion;
    selectedBlockId=block.getAttribute('data-block-id') || '';
    document.querySelectorAll('[data-layout-block]').forEach(function(item){
      item.classList.toggle('is-selected', item===block);
    });
    dataBox.innerHTML='';
    styleBox.innerHTML='';
    actionsBox.innerHTML='';

    var region=block.closest('[data-region]');
    title.textContent=block.getAttribute('data-block-title') || 'Blok Ayarları';
    meta.textContent=(block.getAttribute('data-block-slug') || 'blok') + ' · ' + (block.getAttribute('data-block-region-label') || (region ? region.getAttribute('data-region') : ''));
    if(empty) empty.hidden=true;
    if(live) live.hidden=false;
    activateTab('data');

    var quick=block.querySelector('.layout-block-quick');
    if(quick){
      Array.from(quick.children).forEach(function(node){
        if(node.classList && node.classList.contains('quick-spacer')) return;
        if(node.classList && node.classList.contains('inspector-style')) {
          moveNode(node, styleBox);
        } else if(node.matches && node.matches('button.btn.danger')) {
          moveNode(node, actionsBox);
        } else {
          moveNode(node, dataBox);
        }
      });
    }
    if(autoOpen && isMobile()) openDrawer();
  }

  document.querySelectorAll('[data-layout-block]').forEach(function(block){
    block.addEventListener('click', function(event){
      if(event.target.closest('input,select,textarea,button,a,summary')) return;
      selectBlock(block,true);
    });
    block.addEventListener('keydown', function(event){
      if(event.key === 'Enter' || event.key === ' '){
        event.preventDefault();
        selectBlock(block,true);
      }
    });
  });
  inspector.querySelectorAll('[data-inspector-tab]').forEach(function(btn){
    btn.addEventListener('click', function(){ activateTab(btn.getAttribute('data-inspector-tab')); });
  });
  if(overlay) overlay.addEventListener('click', closeDrawer);
  if(closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if(mobileBtn) mobileBtn.addEventListener('click', function(){
    var selected=document.querySelector('[data-layout-block].is-selected') || document.querySelector('.designer-region.active-region [data-layout-block]') || document.querySelector('[data-layout-block]');
    if(selected) selectBlock(selected,false);
    else resetInspector((document.querySelector('.designer-region.active-region') || {}).dataset ? document.querySelector('.designer-region.active-region').dataset.region : '');
    openDrawer();
  });
  document.addEventListener('omurga:layout-region-change', function(event){
    resetInspector(event.detail ? event.detail.region : '');
    closeDrawer();
  });

  resetInspector((document.querySelector('.designer-region.active-region') || {}).dataset ? document.querySelector('.designer-region.active-region').dataset.region : '');
})();
</script>
<?php require '_footer.php'; ?>
