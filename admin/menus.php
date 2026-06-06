<?php
require '_layout.php';
verify_csrf();

// WordPress benzeri menü yöneticisi: konum seç, hazır öğe ekle, özel bağlantı ekle,
// sürükle-bırak sırala, sağa/sola kaydırarak alt menü yap ve kaydet.
if (!can('menus.manage') && !can('settings.manage') && !can('system.manage')) {
    render_error_page(403, 'Yetkisiz Erişim', 'Menü yönetimi için yetkiniz yok.');
}

$msg = '';
$err = '';

if (!function_exists('omurga_menu_locations')) {
    function omurga_menu_locations(): array { return ['main'=>'Ana Menü','mobile'=>'Mobil Menü','footer'=>'Footer Menü','top'=>'Üst Menü']; }
}
if (!function_exists('omurga_normalize_menu_location')) {
    function omurga_normalize_menu_location(string $location='main'): string {
        $location = preg_replace('/[^a-z0-9_\-]/', '', strtolower($location ?: 'main'));
        return array_key_exists($location, omurga_menu_locations()) ? $location : 'main';
    }
}
if (!function_exists('menu_setting_key')) {
    function menu_setting_key(string $location='main'): string { return 'menu_'.omurga_normalize_menu_location($location); }
}
if (!function_exists('default_menu_items')) {
    function default_menu_items(string $location='main'): array {
        $base = function_exists('omurga_url') ? omurga_url() : '/';
        return [
            ['id'=>1,'title'=>'Anasayfa','url'=>$base,'type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>10],
            ['id'=>2,'title'=>'İçerikler','url'=>rtrim($base,'/').'#icerikler','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>20],
            ['id'=>3,'title'=>'İletişim','url'=>rtrim($base,'/').'#form','type'=>'custom','target'=>'_self','active'=>1,'parent'=>0,'sort'=>30],
        ];
    }
}
if (!function_exists('menu_items')) {
    function menu_items(string $location='main', bool $activeOnly=true): array {
        $items = function_exists('setting_json') ? setting_json(menu_setting_key($location), []) : [];
        if (!$items) $items = default_menu_items($location);
        return is_array($items) ? $items : [];
    }
}

function omurga_admin_menu_candidates(): array {
    $out = ['pages'=>[], 'categories'=>[], 'tags'=>[]];
    try {
        if (function_exists('db') && function_exists('table_name')) {
            $posts = table_name('posts');
            $st = db()->query("SELECT id,title,slug,type FROM $posts WHERE type='page' AND status IN ('published','draft') ORDER BY title ASC LIMIT 300");
            foreach ($st->fetchAll() as $p) {
                $slug = (string)($p['slug'] ?? '');
                $url = function_exists('omurga_url') ? omurga_url($slug) : '/'.$slug;
                $out['pages'][] = ['title'=>(string)($p['title'] ?? ''), 'url'=>$url, 'type'=>'page'];
            }
        }
    } catch (Throwable $e) { if (function_exists('log_error')) log_error($e); }
    try {
        if (function_exists('db') && function_exists('table_name')) {
            $cats = table_name('categories');
            $st = db()->query("SELECT id,name,slug FROM $cats ORDER BY sort_order ASC,name ASC LIMIT 300");
            foreach ($st->fetchAll() as $c) {
                $url = function_exists('category_url') ? category_url($c) : (function_exists('omurga_url') ? omurga_url('kategori/'.($c['slug'] ?? '')) : '/kategori/'.($c['slug'] ?? ''));
                $out['categories'][] = ['title'=>(string)($c['name'] ?? ''), 'url'=>$url, 'type'=>'category'];
            }
        }
    } catch (Throwable $e) { if (function_exists('log_error')) log_error($e); }
    try {
        if (function_exists('db') && function_exists('table_name')) {
            $tags = table_name('tags');
            $st = db()->query("SELECT id,name,slug FROM $tags ORDER BY name ASC LIMIT 300");
            foreach ($st->fetchAll() as $t) {
                $url = function_exists('omurga_url') ? omurga_url('etiket/'.($t['slug'] ?? '')) : '/etiket/'.($t['slug'] ?? '');
                $out['tags'][] = ['title'=>(string)($t['name'] ?? ''), 'url'=>$url, 'type'=>'tag'];
            }
        }
    } catch (Throwable $e) { if (function_exists('log_error')) log_error($e); }
    return $out;
}

$locations = omurga_menu_locations();
$loc = omurga_normalize_menu_location($_GET['loc'] ?? $_POST['location'] ?? 'main');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $loc = omurga_normalize_menu_location($_POST['location'] ?? $loc);
        $action = (string)($_POST['action'] ?? 'save');
        if ($action === 'reset') {
            update_setting_json(menu_setting_key($loc), default_menu_items($loc));
            $msg = 'Menü varsayılan öğelere döndü.';
        } else {
            $raw = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
            $tmp = [];
            $oldIds = [];
            $order = 1;
            foreach ($raw as $it) {
                $title = trim((string)($it['title'] ?? ''));
                $url = trim((string)($it['url'] ?? ''));
                if ($title === '' || $url === '') continue;
                $oldId = (int)($it['id'] ?? 0);
                if ($oldId <= 0 || isset($oldIds[$oldId])) $oldId = 100000 + $order;
                $oldIds[$oldId] = true;
                $depth = max(0, min(1, (int)($it['depth'] ?? 0)));
                $tmp[] = [
                    '_old_id' => $oldId,
                    '_depth' => $depth,
                    'id' => $order,
                    'title' => $title,
                    'url' => $url,
                    'type' => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($it['type'] ?? 'custom'))) ?: 'custom',
                    'target' => (($it['target'] ?? '_self') === '_blank') ? '_blank' : '_self',
                    'active' => !empty($it['active']) ? 1 : 0,
                    'parent' => 0,
                    'sort' => $order * 10,
                ];
                $order++;
            }
            $lastRootId = 0;
            foreach ($tmp as &$item) {
                if ((int)$item['_depth'] > 0 && $lastRootId > 0) {
                    $item['parent'] = $lastRootId;
                } else {
                    $item['parent'] = 0;
                    $lastRootId = (int)$item['id'];
                }
                unset($item['_old_id'], $item['_depth']);
            }
            unset($item);
            update_setting_json(menu_setting_key($loc), $tmp ?: default_menu_items($loc));
            $msg = 'Menü kaydedildi.';
        }
    }
} catch (Throwable $e) {
    $err = 'Menü kaydedilirken hata oluştu. Lütfen sistem loglarını kontrol edin.';
    if (function_exists('log_error')) log_error($e);
}

try {
    $items = menu_items($loc, false);
    if (!$items) $items = default_menu_items($loc);
} catch (Throwable $e) {
    $items = default_menu_items($loc);
    $err = $err ?: 'Menü verileri okunamadı, varsayılan menü gösteriliyor.';
    if (function_exists('log_error')) log_error($e);
}

$itemsById = [];
foreach ($items as $it) $itemsById[(int)($it['id'] ?? 0)] = $it;
$flat = [];
foreach ($items as $it) {
    if ((int)($it['parent'] ?? 0) === 0) {
        $flat[] = $it + ['_depth'=>0];
        foreach ($items as $child) {
            if ((int)($child['parent'] ?? 0) === (int)($it['id'] ?? 0)) $flat[] = $child + ['_depth'=>1];
        }
    }
}
foreach ($items as $it) {
    $pid = (int)($it['parent'] ?? 0);
    if ($pid && !isset($itemsById[$pid])) $flat[] = $it + ['_depth'=>0];
}
$items = $flat ?: default_menu_items($loc);
$candidates = omurga_admin_menu_candidates();
?>
<style>
.omg-wp-menu-wrap{display:grid;grid-template-columns:330px minmax(0,1fr);gap:18px;align-items:start}.omg-wp-menu-box{border:1px solid var(--border,#e5e7eb);border-radius:14px;background:#fff;overflow:hidden;margin-bottom:14px}.omg-wp-menu-box summary{cursor:pointer;padding:14px 16px;font-weight:800;background:#f8fafc;border-bottom:1px solid #e5e7eb}.omg-wp-menu-box-body{padding:14px 16px}.omg-wp-menu-box .check-list{max-height:260px;overflow:auto;display:grid;gap:8px}.omg-wp-menu-box label{display:flex;gap:8px;align-items:center}.omg-menu-locations{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.omg-loc-tab{padding:10px 13px;border:1px solid #e5e7eb;border-radius:999px;text-decoration:none;color:#334155;background:#fff}.omg-loc-tab.active{background:#0f172a;color:#fff;border-color:#0f172a}.omg-menu-structure{display:grid;gap:10px}.omg-menu-item-card{border:1px solid #dbe2ea;border-radius:12px;background:#fff;box-shadow:0 1px 0 rgba(15,23,42,.03)}.omg-menu-item-card.child{margin-left:34px}.omg-menu-item-head{display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f8fafc;border-radius:12px 12px 0 0}.omg-menu-handle{cursor:grab;color:#64748b;font-weight:800}.omg-menu-title-preview{font-weight:800;flex:1}.omg-menu-type-badge{font-size:12px;background:#e2e8f0;padding:3px 8px;border-radius:999px;color:#475569}.omg-menu-item-body{padding:14px;display:grid;gap:12px}.omg-menu-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.omg-menu-grid label,.omg-custom-grid label{display:grid;gap:6px;font-weight:700;color:#334155}.omg-menu-grid input,.omg-custom-grid input,.omg-custom-grid select{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px}.omg-menu-actions{display:flex;gap:7px;flex-wrap:wrap}.btn.tiny{padding:6px 9px;font-size:12px}.omg-sticky-save{position:sticky;bottom:0;background:rgba(255,255,255,.95);border-top:1px solid #e5e7eb;padding:14px;margin:16px -18px -18px}.omg-menu-empty{padding:28px;text-align:center;border:1px dashed #cbd5e1;border-radius:14px;color:#64748b}.omg-menu-search{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;margin-bottom:10px}.omg-menu-help{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:14px;padding:12px 14px;margin-bottom:16px}@media(max-width:900px){.omg-wp-menu-wrap{grid-template-columns:1fr}.omg-menu-grid{grid-template-columns:1fr}.omg-menu-item-card.child{margin-left:18px}}
</style>

<div class="toolbar">
  <div>
    <h1>Menü Yönetimi</h1>
    <p class="muted">WordPress mantığında menü oluştur: sayfa/kategori/etiket ekle, özel bağlantı oluştur, sırala ve alt menü yap.</p>
  </div>
  <form method="post" onsubmit="return confirm('Bu menü varsayılana dönsün mü?')">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="location" value="<?=e($loc)?>">
    <button name="action" value="reset" class="btn light">Varsayılana Dön</button>
  </form>
</div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert danger"><?=e($err)?></div><?php endif; ?>

<div class="omg-menu-locations">
  <?php foreach($locations as $key=>$label): ?>
    <a class="omg-loc-tab <?=$key===$loc?'active':''?>" href="menus.php?loc=<?=e($key)?>"><?=e($label)?></a>
  <?php endforeach; ?>
</div>

<div class="omg-menu-help">Bu ekranda düzenlediğin menü seçili konuma kaydedilir. Tema içindeki Header/Footer/Mobil menü blokları bu konumları kullanır.</div>

<div class="omg-wp-menu-wrap">
  <aside>
    <details class="omg-wp-menu-box" open>
      <summary>Özel Bağlantılar</summary>
      <div class="omg-wp-menu-box-body omg-custom-grid">
        <label>Bağlantı Metni <input id="omgCustomTitle" placeholder="Örn: İletişim"></label>
        <label>URL <input id="omgCustomUrl" placeholder="/iletisim veya https://..."></label>
        <button type="button" class="btn primary" onclick="omgAddCustomLink()">Menüye Ekle</button>
      </div>
    </details>
    <?php $groups=['pages'=>'Sayfalar','categories'=>'Kategoriler','tags'=>'Etiketler']; foreach($groups as $g=>$title): ?>
      <details class="omg-wp-menu-box" open>
        <summary><?=e($title)?> <span class="muted">(<?=count($candidates[$g] ?? [])?>)</span></summary>
        <div class="omg-wp-menu-box-body">
          <input type="search" class="omg-menu-search" placeholder="Ara..." oninput="omgFilterMenuSource(this)">
          <?php if(empty($candidates[$g])): ?><p class="muted">Henüz kayıt yok.</p><?php endif; ?>
          <div class="check-list">
            <?php foreach(($candidates[$g] ?? []) as $idx=>$c): $cid=$g.'_'.$idx; ?>
              <label class="omg-source-row" data-search="<?=e(mb_strtolower(($c['title'] ?? '').' '.($c['url'] ?? ''), 'UTF-8'))?>">
                <input type="checkbox" data-title="<?=e($c['title'])?>" data-url="<?=e($c['url'])?>" data-type="<?=e($c['type'])?>">
                <span><?=e($c['title'])?><br><small class="muted"><?=e($c['url'])?></small></span>
              </label>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn light" onclick="omgAddCheckedFromBox(this)">Seçilenleri Menüye Ekle</button>
        </div>
      </details>
    <?php endforeach; ?>
  </aside>

  <section class="card">
    <div class="toolbar" style="padding:0;margin-bottom:12px">
      <div><h2><?=e($locations[$loc] ?? 'Menü')?></h2><p class="muted">Öğeleri sürükle-bırak veya ↑ ↓ ile sırala. Sağa kaydırınca üstteki öğenin alt menüsü olur.</p></div>
      <button type="button" class="btn light" onclick="omgAddEmptyItem()">+ Boş Öğe</button>
    </div>
    <form method="post" id="omgMenuForm">
      <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="location" value="<?=e($loc)?>">
      <div class="omg-menu-structure" id="omgMenuStructure">
        <?php if(empty($items)): ?><div class="omg-menu-empty">Henüz menü öğesi yok. Soldan bağlantı ekle.</div><?php endif; ?>
        <?php foreach($items as $i=>$it):
          $type = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($it['type'] ?? 'custom'))) ?: 'custom';
          $depth = (int)($it['_depth'] ?? 0);
        ?>
          <div class="omg-menu-item-card <?=$depth?'child':''?>" draggable="true" data-depth="<?=$depth?>">
            <div class="omg-menu-item-head">
              <span class="omg-menu-handle">☰</span>
              <span class="omg-menu-title-preview"><?=e($it['title'] ?? 'Menü Öğesi')?></span>
              <span class="omg-menu-type-badge"><?=e($type)?></span>
              <button type="button" class="btn tiny light" onclick="omgToggleItem(this)">Aç/Kapat</button>
            </div>
            <div class="omg-menu-item-body" style="display:none">
              <input type="hidden" class="omg-id" name="items[<?=$i?>][id]" value="<?=e($it['id'] ?? 0)?>">
              <input type="hidden" class="omg-depth" name="items[<?=$i?>][depth]" value="<?=$depth?>">
              <input type="hidden" class="omg-type" name="items[<?=$i?>][type]" value="<?=e($type)?>">
              <div class="omg-menu-grid">
                <label>Menü Başlığı <input class="omg-title" name="items[<?=$i?>][title]" value="<?=e($it['title'] ?? '')?>" oninput="omgRefreshMenu()"></label>
                <label>URL <input class="omg-url" name="items[<?=$i?>][url]" value="<?=e($it['url'] ?? '')?>"></label>
              </div>
              <div class="omg-menu-actions">
                <label><input type="checkbox" name="items[<?=$i?>][active]" value="1" <?=!empty($it['active'])?'checked':''?>> Aktif</label>
                <label><input type="checkbox" name="items[<?=$i?>][target]" value="_blank" <?=(($it['target']??'_self')==='_blank'?'checked':'')?>> Yeni sekme</label>
              </div>
              <div class="omg-menu-actions">
                <button type="button" class="btn tiny light" onclick="omgMove(this,-1)">↑ Yukarı</button>
                <button type="button" class="btn tiny light" onclick="omgMove(this,1)">↓ Aşağı</button>
                <button type="button" class="btn tiny light" onclick="omgIndent(this)">→ Alt menü</button>
                <button type="button" class="btn tiny light" onclick="omgOutdent(this)">← Ana menü</button>
                <button type="button" class="btn tiny danger" onclick="omgRemoveItem(this)">Kaldır</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="omg-sticky-save"><button class="btn primary" name="action" value="save">Menüyü Kaydet</button></div>
    </form>
  </section>
</div>

<script>
function omgCards(){return Array.prototype.slice.call(document.querySelectorAll('#omgMenuStructure .omg-menu-item-card'));}
function omgToggleItem(btn){var b=btn.closest('.omg-menu-item-card').querySelector('.omg-menu-item-body'); b.style.display=(b.style.display==='none'||!b.style.display)?'grid':'none';}
function omgTpl(title,url,type){var i=omgCards().length; var div=document.createElement('div'); div.className='omg-menu-item-card'; div.draggable=true; div.dataset.depth='0'; div.innerHTML='<div class="omg-menu-item-head"><span class="omg-menu-handle">☰</span><span class="omg-menu-title-preview"></span><span class="omg-menu-type-badge"></span><button type="button" class="btn tiny light" onclick="omgToggleItem(this)">Aç/Kapat</button></div><div class="omg-menu-item-body"><input type="hidden" class="omg-id" name="items['+i+'][id]" value="0"><input type="hidden" class="omg-depth" name="items['+i+'][depth]" value="0"><input type="hidden" class="omg-type" name="items['+i+'][type]" value="'+(type||'custom')+'"><div class="omg-menu-grid"><label>Menü Başlığı <input class="omg-title" name="items['+i+'][title]" value=""></label><label>URL <input class="omg-url" name="items['+i+'][url]" value=""></label></div><div class="omg-menu-actions"><label><input type="checkbox" name="items['+i+'][active]" value="1" checked> Aktif</label><label><input type="checkbox" name="items['+i+'][target]" value="_blank"> Yeni sekme</label></div><div class="omg-menu-actions"><button type="button" class="btn tiny light" onclick="omgMove(this,-1)">↑ Yukarı</button><button type="button" class="btn tiny light" onclick="omgMove(this,1)">↓ Aşağı</button><button type="button" class="btn tiny light" onclick="omgIndent(this)">→ Alt menü</button><button type="button" class="btn tiny light" onclick="omgOutdent(this)">← Ana menü</button><button type="button" class="btn tiny danger" onclick="omgRemoveItem(this)">Kaldır</button></div></div>'; div.querySelector('.omg-title').value=title||''; div.querySelector('.omg-url').value=url||''; document.getElementById('omgMenuStructure').appendChild(div); omgBindDrag(div); omgRefreshMenu(); return div;}
function omgAddCustomLink(){var t=document.getElementById('omgCustomTitle').value.trim(); var u=document.getElementById('omgCustomUrl').value.trim(); if(!t||!u){alert('Bağlantı metni ve URL gir.'); return;} var row=omgTpl(t,u,'custom'); row.scrollIntoView({behavior:'smooth',block:'center'}); document.getElementById('omgCustomTitle').value=''; document.getElementById('omgCustomUrl').value='';}
function omgAddEmptyItem(){var row=omgTpl('','','custom'); row.querySelector('.omg-menu-item-body').style.display='grid'; row.scrollIntoView({behavior:'smooth',block:'center'});}
function omgAddCheckedFromBox(btn){var box=btn.closest('.omg-wp-menu-box'); box.querySelectorAll('input[type="checkbox"]:checked').forEach(function(c){omgTpl(c.dataset.title,c.dataset.url,c.dataset.type); c.checked=false;});}
function omgFilterMenuSource(inp){var q=(inp.value||'').toLowerCase(); inp.closest('.omg-wp-menu-box-body').querySelectorAll('.omg-source-row').forEach(function(r){r.style.display=(!q||r.dataset.search.indexOf(q)!==-1)?'flex':'none';});}
function omgMove(btn,dir){var card=btn.closest('.omg-menu-item-card'); var list=document.getElementById('omgMenuStructure'); if(dir<0&&card.previousElementSibling)list.insertBefore(card,card.previousElementSibling); if(dir>0&&card.nextElementSibling)list.insertBefore(card.nextElementSibling,card); omgRefreshMenu();}
function omgIndent(btn){var card=btn.closest('.omg-menu-item-card'); if(!card.previousElementSibling){alert('Alt menü yapmak için üstünde bir öğe olmalı.'); return;} card.dataset.depth='1'; card.classList.add('child'); omgRefreshMenu();}
function omgOutdent(btn){var card=btn.closest('.omg-menu-item-card'); card.dataset.depth='0'; card.classList.remove('child'); omgRefreshMenu();}
function omgRemoveItem(btn){if(confirm('Bu menü öğesi kaldırılsın mı?')){btn.closest('.omg-menu-item-card').remove(); omgRefreshMenu();}}
function omgRefreshMenu(){omgCards().forEach(function(card,i){card.querySelectorAll('[name]').forEach(function(el){el.name=el.name.replace(/items\[\d+\]/,'items['+i+']');}); var t=card.querySelector('.omg-title'); var title=t?t.value.trim():''; card.querySelector('.omg-menu-title-preview').textContent=title||'Menü Öğesi'; card.querySelector('.omg-menu-type-badge').textContent=card.querySelector('.omg-type').value||'custom'; var d=parseInt(card.dataset.depth||'0',10); if(i===0)d=0; card.dataset.depth=d; card.classList.toggle('child',d>0); card.querySelector('.omg-depth').value=d;});}
var dragEl=null; function omgBindDrag(card){card.addEventListener('dragstart',function(){dragEl=card; card.style.opacity='.55';}); card.addEventListener('dragend',function(){dragEl=null; card.style.opacity=''; omgRefreshMenu();}); card.addEventListener('dragover',function(e){e.preventDefault(); if(!dragEl||dragEl===card)return; var rect=card.getBoundingClientRect(); var after=(e.clientY-rect.top)>rect.height/2; card.parentNode.insertBefore(dragEl, after?card.nextSibling:card);});}
omgCards().forEach(omgBindDrag); document.addEventListener('input',function(e){if(e.target.closest('#omgMenuStructure'))omgRefreshMenu();});
</script>
<?php require '_footer.php'; ?>
