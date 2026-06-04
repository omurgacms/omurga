<?php
require '_layout.php';
verify_csrf();
require_cap('categories.manage');

$t = table_name('categories');

function omurga_ensure_category_columns(string $table): void {
    try { db()->exec("ALTER TABLE $table ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL"); } catch (Throwable $e) {}
    try { db()->exec("ALTER TABLE $table ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"); } catch (Throwable $e) {}
    try { db()->exec("ALTER TABLE $table ADD COLUMN color VARCHAR(20) NULL DEFAULT NULL"); } catch (Throwable $e) {}
}

function omurga_admin_category_tree(array $items, int $parent = 0, int $level = 0): string {
    $html = '';
    foreach ($items as $item) {
        if ((int)($item['parent_id'] ?? 0) !== $parent) continue;
        $id = (int)$item['id'];
        $children = omurga_admin_category_tree($items, $id, $level + 1);
        $hasChildren = trim($children) !== '';
        $status = ((int)($item['is_active'] ?? 1) === 1) ? 'Aktif' : 'Pasif';
        $statusClass = ((int)($item['is_active'] ?? 1) === 1) ? 'ok' : 'bad';
        $indent = max(0, $level) * 18;
        $html .= '<div class="omg-tree-row" data-level="'.$level.'" data-parent="'.$parent.'" data-id="'.$id.'">';
        $html .= '<div class="omg-tree-main" style="padding-left:'.$indent.'px">';
        $html .= $hasChildren ? '<button type="button" class="omg-tree-toggle" aria-label="Alt kategorileri aç/kapat">▾</button>' : '<span class="omg-tree-spacer"></span>';
        $html .= '<span class="omg-dot '.$statusClass.'"></span>';
        $html .= '<b>ID:'.$id.' '.e($item['name']).'</b>';
        $html .= '<small>'.e($item['slug']).'</small>';
        $html .= '<em>'.$status.'</em>';
        $html .= '</div>';
        $html .= '<div class="omg-tree-actions">';
        $html .= '<a class="btn light small" href="?edit='.$id.'">Düzenle</a>';
        $html .= '<form method="post" class="inline-form" onsubmit="return confirm(\'Kategori silinsin mi?\')"><input type="hidden" name="_csrf" value="'.csrf_token().'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.$id.'"><button class="btn danger small">Sil</button></form>';
        $html .= '</div></div>';
        if ($hasChildren) $html .= '<div class="omg-tree-children">'.$children.'</div>';
    }
    return $html;
}

omurga_ensure_category_columns($t);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? '')) ?: slugify($name);
        $desc = trim((string)($_POST['description'] ?? ''));
        $sort = (int)($_POST['sort_order'] ?? 0);
        $parent = (int)($_POST['parent_id'] ?? 0);
        $color = trim((string)($_POST['color'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($parent === $id) $parent = 0;
        if ($name === '') {
            echo '<div class="alert error">Kategori adı boş olamaz.</div>';
        } else {
            if ($id) {
                db()->prepare("UPDATE $t SET name=?,slug=?,description=?,sort_order=?,parent_id=?,color=?,is_active=? WHERE id=?")->execute([$name,$slug,$desc,$sort,$parent ?: null,$color ?: null,$active,$id]);
            } else {
                db()->prepare("INSERT INTO $t (name,slug,description,sort_order,parent_id,color,is_active) VALUES (?,?,?,?,?,?,?)")->execute([$name,$slug,$desc,$sort,$parent ?: null,$color ?: null,$active]);
            }
            echo '<div class="alert success">Kategori kaydedildi.</div>';
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE $t SET parent_id=NULL WHERE parent_id=?")->execute([$id]);
        db()->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);
        echo '<div class="alert success">Kategori silindi.</div>';
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare("SELECT * FROM $t WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$cats = db()->query("SELECT * FROM $t ORDER BY COALESCE(parent_id,0), sort_order, name")->fetchAll();
?>

<div class="toolbar compact-page-head">
  <div>
    <h1>Kategoriler</h1>
    <p class="muted">Yeni kategori formu üstte; liste altta geniş alanda görünür. Kategori isimleri artık sıkışmaz.</p>
  </div>
</div>

<div class="card omg-edit-box omg-top-edit-box">
  <h2><?= $edit ? 'Kategori Düzenle' : 'Yeni Kategori Ekle' ?></h2>
  <form method="post" class="omg-wide-form">
    <input type="hidden" name="_csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?=e($edit['id'] ?? 0)?>">
    <div class="mini-grid two"><label>Ad<input name="name" required value="<?=e($edit['name'] ?? '')?>"></label><label>Slug<input name="slug" value="<?=e($edit['slug'] ?? '')?>"></label></div>
    <div class="mini-grid three"><label>Üst kategori<select name="parent_id"><option value="0">Ana kategori</option><?php foreach($cats as $c): if($edit && (int)$edit['id']===(int)$c['id']) continue; ?><option value="<?=$c['id']?>" <?=((int)($edit['parent_id'] ?? 0)===(int)$c['id'])?'selected':''?>><?=e($c['name'])?></option><?php endforeach; ?></select></label><label>Sıra<input type="number" name="sort_order" value="<?=e($edit['sort_order'] ?? 0)?>"></label><label>Renk<input name="color" placeholder="#f97316" value="<?=e($edit['color'] ?? '')?>"></label></div>
    <label>Açıklama<textarea name="description" style="min-height:70px"><?=e($edit['description'] ?? '')?></textarea></label>
    <div class="form-actions"><label class="check-line"><input type="checkbox" name="is_active" value="1" <?=((int)($edit['is_active'] ?? 1)===1)?'checked':''?>> Aktif</label><button class="btn primary">Kaydet</button><?php if($edit): ?><a class="btn light" href="categories.php">Yeni kategori ekle</a><?php endif; ?></div>
  </form>
</div>

<div class="card omg-list-card">
  <div class="omg-card-head"><h2>Kategori listesi</h2><div><button class="btn light small" type="button" data-tree-action="expand">Tümünü genişlet</button> <button class="btn light small" type="button" data-tree-action="collapse">Tümünü daralt</button></div></div>
  <div class="omg-tree-list">
    <?= $cats ? omurga_admin_category_tree($cats) : '<div class="empty-state">Henüz kategori yok.</div>' ?>
  </div>
</div>
<script>
(function(){
  document.addEventListener('click', function(e){
    var t=e.target;
    if(t.matches('.omg-tree-toggle')){
      var row=t.closest('.omg-tree-row'); var next=row ? row.nextElementSibling : null;
      if(next && next.classList.contains('omg-tree-children')){ next.classList.toggle('is-collapsed'); t.textContent=next.classList.contains('is-collapsed')?'▸':'▾'; }
    }
    if(t.matches('[data-tree-action="collapse"]')){ document.querySelectorAll('.omg-tree-children').forEach(function(x){x.classList.add('is-collapsed')}); document.querySelectorAll('.omg-tree-toggle').forEach(function(x){x.textContent='▸'}); }
    if(t.matches('[data-tree-action="expand"]')){ document.querySelectorAll('.omg-tree-children').forEach(function(x){x.classList.remove('is-collapsed')}); document.querySelectorAll('.omg-tree-toggle').forEach(function(x){x.textContent='▾'}); }
  });
})();
</script>
<?php require '_footer.php'; ?>
