<?php
require '_layout.php';
verify_csrf();
require_cap('categories.manage');
$t = table_name('tags');
db()->exec("CREATE TABLE IF NOT EXISTS $t (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, slug VARCHAR(140) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { db()->exec("ALTER TABLE $t ADD COLUMN description TEXT NULL"); } catch (Throwable $e) {}
try { db()->exec("ALTER TABLE $t ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"); } catch (Throwable $e) {}
try { db()->exec("ALTER TABLE $t ADD COLUMN sort_order INT NOT NULL DEFAULT 0"); } catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id=(int)($_POST['id']??0); $name=trim((string)($_POST['name']??'')); $slug=trim((string)($_POST['slug']??'')) ?: slugify($name); $desc=trim((string)($_POST['description']??'')); $sort=(int)($_POST['sort_order']??0); $active=isset($_POST['is_active'])?1:0;
        if ($name === '') echo '<div class="alert error">Etiket adı boş olamaz.</div>';
        else {
            if ($id) db()->prepare("UPDATE $t SET name=?,slug=?,description=?,sort_order=?,is_active=? WHERE id=?")->execute([$name,$slug,$desc,$sort,$active,$id]);
            else db()->prepare("INSERT INTO $t (name,slug,description,sort_order,is_active) VALUES (?,?,?,?,?)")->execute([$name,$slug,$desc,$sort,$active]);
            echo '<div class="alert success">Etiket kaydedildi.</div>';
        }
    }
    if ($action === 'delete') { db()->prepare("DELETE FROM $t WHERE id=?")->execute([(int)($_POST['id']??0)]); echo '<div class="alert success">Etiket silindi.</div>'; }
}
$edit=null; if(isset($_GET['edit'])){ $st=db()->prepare("SELECT * FROM $t WHERE id=?"); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch(); }
$tags=db()->query("SELECT * FROM $t ORDER BY sort_order,name")->fetchAll();
?>

<div class="toolbar compact-page-head"><div><h1>Etiketler</h1><p class="muted">Yeni etiket formu üstte; liste altta geniş alanda görünür.</p></div></div>
<div class="card omg-edit-box omg-top-edit-box"><h2><?= $edit?'Etiket Düzenle':'Yeni Etiket Ekle' ?></h2><form method="post" class="omg-wide-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><div class="mini-grid two"><label>Ad<input name="name" required value="<?=e($edit['name']??'')?>"></label><label>Slug<input name="slug" value="<?=e($edit['slug']??'')?>"></label></div><label>Açıklama<textarea name="description" style="min-height:70px"><?=e($edit['description']??'')?></textarea></label><div class="mini-grid two"><label>Sıra<input type="number" name="sort_order" value="<?=e($edit['sort_order']??0)?>"></label><label class="check-line" style="align-self:end"><input type="checkbox" name="is_active" value="1" <?=((int)($edit['is_active']??1)===1)?'checked':''?>> Aktif</label></div><div class="form-actions"><button class="btn primary">Kaydet</button><?php if($edit): ?><a class="btn light" href="tags.php">Yeni etiket ekle</a><?php endif; ?></div></form></div>
<div class="card omg-list-card"><div class="omg-card-head"><h2>Etiket listesi</h2><span class="badge draft"><?=count($tags)?> etiket</span></div>
  <div class="omg-tree-list omg-tag-list">
    <?php if(!$tags): ?><div class="empty-state">Henüz etiket yok.</div><?php endif; ?>
    <?php foreach($tags as $tag): $ok=(int)($tag['is_active']??1)===1; ?>
      <div class="omg-tree-row">
        <div class="omg-tree-main"><span class="omg-tree-spacer"></span><span class="omg-dot <?=$ok?'ok':'bad'?>"></span><b>ID:<?=e($tag['id'])?> <?=e($tag['name'])?></b><small><?=e($tag['slug'])?></small><em><?=$ok?'Aktif':'Pasif'?></em></div>
        <div class="omg-tree-actions"><a class="btn light small" href="?edit=<?=$tag['id']?>">Düzenle</a><form method="post" class="inline-form" onsubmit="return confirm('Etiket silinsin mi?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$tag['id']?>"><button class="btn danger small">Sil</button></form></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require '_footer.php'; ?>
