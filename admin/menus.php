<?php require '_layout.php'; verify_csrf(); require_cap('menus.manage');
$locations = omurga_menu_locations();
$loc = omurga_normalize_menu_location($_GET['loc'] ?? 'main');
$msg = '';

function admin_menu_candidates(): array {
    $out = ['pages'=>[], 'categories'=>[], 'tags'=>[]];
    try {
        $posts = table_name('posts');
        $st = db()->query("SELECT id,title,slug,type FROM $posts WHERE type='page' AND status IN ('published','draft') ORDER BY title ASC LIMIT 300");
        foreach ($st->fetchAll() as $p) {
            $out['pages'][] = ['title'=>$p['title'], 'url'=>omurga_url($p['slug']), 'type'=>'page'];
        }
    } catch (Throwable $e) {}
    try {
        $cats = table_name('categories');
        $st = db()->query("SELECT id,name,slug FROM $cats ORDER BY sort_order ASC,name ASC LIMIT 300");
        foreach ($st->fetchAll() as $c) {
            $out['categories'][] = ['title'=>$c['name'], 'url'=>category_url($c), 'type'=>'category'];
        }
    } catch (Throwable $e) {}
    try {
        $tags = table_name('tags');
        $st = db()->query("SELECT id,name,slug FROM $tags ORDER BY name ASC LIMIT 300");
        foreach ($st->fetchAll() as $t) {
            $out['tags'][] = ['title'=>$t['name'], 'url'=>omurga_url('etiket/'.$t['slug']), 'type'=>'tag'];
        }
    } catch (Throwable $e) {}
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loc = omurga_normalize_menu_location($_POST['location'] ?? $loc);
    if (($_POST['action'] ?? '') === 'reset') {
        update_setting_json(menu_setting_key($loc), default_menu_items($loc));
        $msg = 'Menü varsayılan öğelere döndü.';
    } else {
        $raw = $_POST['items'] ?? [];
        $tmp = [];
        $oldIds = [];
        $order = 1;
        foreach ($raw as $i => $it) {
            $title = trim((string)($it['title'] ?? ''));
            $url = trim((string)($it['url'] ?? ''));
            if ($title === '' || $url === '') continue;
            $oldId = (int)($it['id'] ?? 0);
            if ($oldId <= 0 || isset($oldIds[$oldId])) $oldId = 100000 + $order;
            $oldIds[$oldId] = true;
            $tmp[] = [
                '_old_id' => $oldId,
                '_old_parent' => max(0, (int)($it['parent'] ?? 0)),
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
        $map = [];
        foreach ($tmp as $item) $map[(int)$item['_old_id']] = (int)$item['id'];
        foreach ($tmp as &$item) {
            $oldParent = (int)$item['_old_parent'];
            $item['parent'] = ($oldParent && isset($map[$oldParent])) ? $map[$oldParent] : 0;
            unset($item['_old_id'], $item['_old_parent']);
        }
        update_setting_json(menu_setting_key($loc), $tmp);
        $msg = 'Menü kaydedildi.';
    }
}

$items = menu_items($loc, false);
if (!$items) $items = [];
for ($i = count($items); $i < max(10, count($items) + 4); $i++) {
    $items[] = ['id'=>0, 'title'=>'', 'url'=>'', 'type'=>'custom', 'target'=>'_self', 'active'=>1, 'parent'=>0, 'sort'=>($i+1)*10];
}
$candidates = admin_menu_candidates();
?>
<div class="toolbar omg-menu-toolbar">
  <div>
    <h1>Menü Yönetimi</h1>
    <p class="muted">Menü öğelerini ekle, sırala, alt menü yap ve tek ekrandan kaydet.</p>
  </div>
  <form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="location" value="<?=e($loc)?>"><button name="action" value="reset" class="btn light" onclick="return confirm('Bu menü varsayılana dönsün mü?')">Varsayılana Dön</button></form>
</div>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>

<div class="card omg-menu-locations">
  <?php foreach($locations as $key=>$label): ?>
    <a class="omg-loc-tab <?=$key===$loc?'active':''?>" href="menus.php?loc=<?=e($key)?>"><?=e($label)?></a>
  <?php endforeach; ?>
</div>

<div class="omg-menu-manager easy">
  <div class="card omg-menu-editor">
    <div class="omg-card-head">
      <div>
        <h2><?=e($locations[$loc])?></h2>
        <p class="muted">Sıralama için ↑ ↓, alt menü için “Alt menü yap” kullan. Boş satırlar kaydedilmez.</p>
      </div>
      <div class="omg-menu-actions">
        <button type="button" class="btn light" onclick="omgAddEmptyMenuRow()">+ Boş Satır</button>
        <button type="button" class="btn light" onclick="omgToggleAdvanced()">Gelişmiş</button>
      </div>
    </div>
    <form method="post" id="omgMenuForm">
      <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
      <input type="hidden" name="location" value="<?=e($loc)?>">
      <div class="omg-menu-list" id="omgMenuList">
        <?php foreach($items as $i=>$it):
          $filled = trim((string)($it['title'] ?? '')) !== '' || trim((string)($it['url'] ?? '')) !== '';
          $parent = (int)($it['parent'] ?? 0);
        ?>
          <div class="omg-menu-row <?=$filled?'filled':'empty'?> <?=$parent?'child':''?>" data-index="<?=$i?>" data-id="<?=e($it['id'] ?? 0)?>">
            <input type="hidden" class="omg-menu-id" name="items[<?=$i?>][id]" value="<?=e($it['id'] ?? 0)?>">
            <input type="hidden" class="omg-menu-parent" name="items[<?=$i?>][parent]" value="<?=e($parent)?>">
            <input type="hidden" class="omg-menu-type" name="items[<?=$i?>][type]" value="<?=e($it['type'] ?? 'custom')?>">
            <div class="omg-menu-move">
              <button type="button" title="Yukarı taşı" onclick="omgMoveRow(this,-1)">↑</button>
              <button type="button" title="Aşağı taşı" onclick="omgMoveRow(this,1)">↓</button>
            </div>
            <div class="omg-menu-main-fields">
              <input class="omg-menu-title" name="items[<?=$i?>][title]" value="<?=e($it['title'] ?? '')?>" placeholder="Menü başlığı">
              <input class="omg-menu-url" name="items[<?=$i?>][url]" value="<?=e($it['url'] ?? '')?>" placeholder="/iletisim veya https://...">
            </div>
            <div class="omg-menu-mini">
              <label title="Aktif"><input type="checkbox" name="items[<?=$i?>][active]" value="1" <?=!empty($it['active'])?'checked':''?>> Aktif</label>
              <label title="Yeni sekmede aç"><input type="checkbox" name="items[<?=$i?>][target]" value="_blank" <?=(($it['target']??'_self')==='_blank'?'checked':'')?>> Yeni sekme</label>
            </div>
            <div class="omg-menu-row-actions">
              <button type="button" class="btn tiny light" onclick="omgMakeChild(this)">Alt menü yap</button>
              <button type="button" class="btn tiny light" onclick="omgMakeRoot(this)">Ana menü</button>
              <button type="button" class="btn tiny danger" onclick="omgClearMenuRow(this)">Temizle</button>
            </div>
            <div class="omg-menu-advanced">
              <label>Tip
                <select onchange="this.closest('.omg-menu-row').querySelector('.omg-menu-type').value=this.value">
                  <?php foreach(['custom'=>'Özel','page'=>'Sayfa','category'=>'Kategori','tag'=>'Etiket'] as $k=>$v): ?>
                    <option value="<?=e($k)?>" <?=(($it['type']??'custom')===$k?'selected':'')?>><?=e($v)?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Parent
                <input type="number" value="<?=e($parent)?>" oninput="this.closest('.omg-menu-row').querySelector('.omg-menu-parent').value=this.value">
              </label>
              <small class="muted">Sıra artık ekrandaki dizilişe göre otomatik kaydedilir.</small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="omg-sticky-save"><button class="btn primary">Menüyü Kaydet</button><span class="muted">OMG tema içinde menü bloğu / helper ile kullanılır</span></div>
    </form>
  </div>

  <div class="card omg-menu-palette">
    <h2>Hazır Bağlantılar</h2>
    <p class="muted">Ara, tıkla, menüye ekle.</p>
    <input type="search" class="omg-menu-search" placeholder="Sayfa, kategori, etiket ara..." oninput="omgFilterCandidates(this.value)">
    <?php $groups=['pages'=>'Statik Sayfalar','categories'=>'Kategoriler','tags'=>'Etiketler']; foreach($groups as $g=>$title): ?>
      <details class="omg-pick-group" open>
        <summary><?=e($title)?> <span><?=count($candidates[$g])?></span></summary>
        <?php if(!$candidates[$g]): ?><p class="muted empty-note">Henüz kayıt yok.</p><?php endif; ?>
        <div class="omg-pick-list">
          <?php foreach($candidates[$g] as $c): ?>
            <button type="button" data-title="<?=e($c['title'])?>" data-url="<?=e($c['url'])?>" data-type="<?=e($c['type'])?>" onclick="omgAddMenuCandidate(this)"><b><?=e($c['title'])?></b><small><?=e($c['url'])?></small></button>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
</div>

<script>
function omgToggleAdvanced(){ document.body.classList.toggle('omg-menu-show-advanced'); localStorage.setItem('omgMenuAdvanced', document.body.classList.contains('omg-menu-show-advanced')?'1':'0'); }
if(localStorage.getItem('omgMenuAdvanced')==='1'){ document.body.classList.add('omg-menu-show-advanced'); }
function omgRows(){ return [...document.querySelectorAll('#omgMenuList .omg-menu-row')]; }
function omgRefreshRows(){
  omgRows().forEach((row,i)=>{
    row.dataset.index=i;
    row.querySelectorAll('[name]').forEach(el=>{ el.name = el.name.replace(/items\[\d+\]/, 'items['+i+']'); });
    const title=row.querySelector('.omg-menu-title').value.trim();
    const url=row.querySelector('.omg-menu-url').value.trim();
    row.classList.toggle('empty', !title && !url);
    row.classList.toggle('filled', !!(title || url));
    row.classList.toggle('child', parseInt(row.querySelector('.omg-menu-parent').value||'0',10)>0);
  });
}
function omgMoveRow(btn,dir){
  const row=btn.closest('.omg-menu-row'); const list=document.getElementById('omgMenuList');
  if(dir<0 && row.previousElementSibling) list.insertBefore(row,row.previousElementSibling);
  if(dir>0 && row.nextElementSibling) list.insertBefore(row.nextElementSibling,row);
  omgRefreshRows();
}
function omgAddEmptyMenuRow(){
  const list=document.getElementById('omgMenuList'); const i=omgRows().length;
  const div=document.createElement('div'); div.className='omg-menu-row empty'; div.dataset.index=i; div.dataset.id='0';
  div.innerHTML=`<input type="hidden" class="omg-menu-id" name="items[${i}][id]" value="0"><input type="hidden" class="omg-menu-parent" name="items[${i}][parent]" value="0"><input type="hidden" class="omg-menu-type" name="items[${i}][type]" value="custom"><div class="omg-menu-move"><button type="button" title="Yukarı taşı" onclick="omgMoveRow(this,-1)">↑</button><button type="button" title="Aşağı taşı" onclick="omgMoveRow(this,1)">↓</button></div><div class="omg-menu-main-fields"><input class="omg-menu-title" name="items[${i}][title]" placeholder="Menü başlığı"><input class="omg-menu-url" name="items[${i}][url]" placeholder="/iletisim veya https://..."></div><div class="omg-menu-mini"><label><input type="checkbox" name="items[${i}][active]" value="1" checked> Aktif</label><label><input type="checkbox" name="items[${i}][target]" value="_blank"> Yeni sekme</label></div><div class="omg-menu-row-actions"><button type="button" class="btn tiny light" onclick="omgMakeChild(this)">Alt menü yap</button><button type="button" class="btn tiny light" onclick="omgMakeRoot(this)">Ana menü</button><button type="button" class="btn tiny danger" onclick="omgClearMenuRow(this)">Temizle</button></div><div class="omg-menu-advanced"><label>Tip<select onchange="this.closest('.omg-menu-row').querySelector('.omg-menu-type').value=this.value"><option value="custom">Özel</option><option value="page">Sayfa</option><option value="category">Kategori</option><option value="tag">Etiket</option></select></label><label>Parent<input type="number" value="0" oninput="this.closest('.omg-menu-row').querySelector('.omg-menu-parent').value=this.value"></label><small class="muted">Sıra otomatik.</small></div>`;
  list.appendChild(div); div.scrollIntoView({behavior:'smooth',block:'center'}); omgRefreshRows();
  return div;
}
function omgAddMenuCandidate(btn){
  let row=omgRows().find(r=>!r.querySelector('.omg-menu-title').value.trim() && !r.querySelector('.omg-menu-url').value.trim());
  if(!row) row=omgAddEmptyMenuRow();
  row.querySelector('.omg-menu-title').value=btn.dataset.title||'';
  row.querySelector('.omg-menu-url').value=btn.dataset.url||'';
  row.querySelector('.omg-menu-type').value=btn.dataset.type||'custom';
  const active=row.querySelector('input[name$="[active]"]'); if(active) active.checked=true;
  row.classList.add('just-added'); row.scrollIntoView({behavior:'smooth',block:'center'}); setTimeout(()=>row.classList.remove('just-added'),900);
  omgRefreshRows();
}
function omgMakeChild(btn){
  const row=btn.closest('.omg-menu-row'); const prev=row.previousElementSibling;
  if(!prev){ alert('Alt menü yapmak için üstünde bir ana menü olmalı.'); return; }
  const prevId=parseInt(prev.querySelector('.omg-menu-id').value||prev.dataset.id||'0',10);
  if(prevId<=0){ alert('Yeni eklenen satırı alt menü yapmak için önce menüyü kaydet.'); return; }
  row.querySelector('.omg-menu-parent').value=prevId; omgRefreshRows();
}
function omgMakeRoot(btn){ btn.closest('.omg-menu-row').querySelector('.omg-menu-parent').value=0; omgRefreshRows(); }
function omgClearMenuRow(btn){
  const row=btn.closest('.omg-menu-row');
  row.querySelector('.omg-menu-title').value=''; row.querySelector('.omg-menu-url').value=''; row.querySelector('.omg-menu-parent').value='0'; row.querySelector('.omg-menu-type').value='custom';
  omgRefreshRows();
}
function omgFilterCandidates(q){
  q=(q||'').toLowerCase().trim();
  document.querySelectorAll('.omg-pick-list button').forEach(b=>{
    const text=(b.dataset.title+' '+b.dataset.url).toLowerCase();
    b.style.display=(!q || text.includes(q))?'block':'none';
  });
}
document.addEventListener('input', e=>{ if(e.target.closest('#omgMenuList')) omgRefreshRows(); });
</script>
<?php require '_footer.php'; ?>
