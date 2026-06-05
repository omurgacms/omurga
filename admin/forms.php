<?php
require '_layout.php';
verify_csrf();
require_cap('forms.manage');

omurga_ensure_forms_v2_tables();
$tables = omurga_forms_v2_tables();
$formsT = $tables['submissions'];
$defsT = $tables['definitions'];

$allowedStatuses = [
    'new' => 'Yeni',
    'reviewed' => 'İncelendi',
    'approved' => 'Onaylandı',
];
$allowedTypes = [
    '' => 'Tümü',
    'contact' => 'İletişim',
    'quote' => 'Teklif',
    'membership' => 'Üyelik',
    'event' => 'Etkinlik',
    'custom' => 'Özel',
];
$fieldTypes = [
    'text' => 'Metin',
    'email' => 'E-posta',
    'tel' => 'Telefon',
    'textarea' => 'Uzun Metin',
    'select' => 'Seçim',
    'checkbox' => 'Onay Kutusu',
    'number' => 'Sayı',
    'url' => 'URL',
];

$tab = ($_GET['tab'] ?? 'submissions') === 'builder' ? 'builder' : 'submissions';
$notice = '';
$error = '';

function om_admin_form_fields_from_post(): array {
    $keys = $_POST['field_key'] ?? [];
    $labels = $_POST['field_label'] ?? [];
    $types = $_POST['field_type'] ?? [];
    $required = $_POST['field_required'] ?? [];
    $placeholders = $_POST['field_placeholder'] ?? [];
    $options = $_POST['field_options'] ?? [];
    $out = [];
    foreach ((array)$labels as $i => $label) {
        $label = trim((string)$label);
        if ($label === '') continue;
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($keys[$i] ?? ''));
        if ($key === '') $key = slugify($label);
        $key = str_replace('-', '_', $key);
        $type = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($types[$i] ?? 'text')));
        if (!in_array($type, ['text','email','tel','textarea','select','checkbox','number','url'], true)) $type = 'text';
        $out[] = [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'required' => isset($required[$i]) ? 1 : 0,
            'placeholder' => trim((string)($placeholders[$i] ?? '')),
            'options' => trim((string)($options[$i] ?? '')),
        ];
    }
    return $out ?: omurga_default_form_fields();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'submission_delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Geçersiz başvuru seçildi.');
            db()->prepare("DELETE FROM $formsT WHERE id=?")->execute([$id]);
            $notice = 'Başvuru silindi.';
        } elseif ($action === 'submission_status') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Geçersiz başvuru seçildi.');
            $status = (string)($_POST['status'] ?? 'new');
            if (!isset($allowedStatuses[$status])) $status = 'new';
            db()->prepare("UPDATE $formsT SET status=? WHERE id=?")->execute([$status, $id]);
            $notice = 'Başvuru durumu güncellendi.';
        } elseif ($action === 'form_save') {
            $id = (int)($_POST['form_id'] ?? 0);
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') throw new RuntimeException('Form adı zorunludur.');
            $slug = slugify((string)($_POST['slug'] ?? '')) ?: slugify($title);
            $formType = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($_POST['form_type'] ?? 'custom')));
            if ($formType === '') $formType = 'custom';
            $status = ($_POST['status'] ?? 'active') === 'passive' ? 'passive' : 'active';
            $description = trim((string)($_POST['description'] ?? ''));
            $submitLabel = trim((string)($_POST['submit_label'] ?? 'Gönder')) ?: 'Gönder';
            $successMessage = trim((string)($_POST['success_message'] ?? 'Başvurunuz alındı. En kısa sürede dönüş yapılacaktır.'));
            $fields = json_encode(om_admin_form_fields_from_post(), JSON_UNESCAPED_UNICODE);

            if ($id > 0) {
                $stmt = db()->prepare("UPDATE $defsT SET title=?, slug=?, form_type=?, description=?, fields=?, status=?, submit_label=?, success_message=? WHERE id=?");
                $stmt->execute([$title, $slug, $formType, $description, $fields, $status, $submitLabel, $successMessage, $id]);
                $notice = 'Form güncellendi.';
            } else {
                $stmt = db()->prepare("INSERT INTO $defsT (title, slug, form_type, description, fields, status, submit_label, success_message) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$title, $slug, $formType, $description, $fields, $status, $submitLabel, $successMessage]);
                $notice = 'Yeni form oluşturuldu.';
            }
            $tab = 'builder';
        } elseif ($action === 'form_delete') {
            $id = (int)($_POST['form_id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Geçersiz form seçildi.');
            $count = (int)db()->query("SELECT COUNT(*) FROM $defsT")->fetchColumn();
            if ($count <= 1) throw new RuntimeException('En az bir form kalmalıdır.');
            db()->prepare("DELETE FROM $defsT WHERE id=?")->execute([$id]);
            $notice = 'Form silindi. Eski başvuru kayıtları korunur.';
            $tab = 'builder';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$type = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($_GET['type'] ?? '')));
$statusFilter = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($_GET['status'] ?? '')));
$q = trim((string)($_GET['q'] ?? ''));
if (!isset($allowedTypes[$type])) $type = '';
if ($statusFilter !== '' && !isset($allowedStatuses[$statusFilter])) $statusFilter = '';

$where = ['1=1'];
$params = [];
if ($type !== '') { $where[] = 'form_type=?'; $params[] = $type; }
if ($statusFilter !== '') { $where[] = 'status=?'; $params[] = $statusFilter; }
if ($q !== '') {
    $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ? OR message LIKE ? OR meta LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$rows = [];
try {
    $sql = "SELECT * FROM $formsT WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT 200";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = $error ?: 'Form başvuruları okunamadı: ' . $e->getMessage();
}

$definitions = [];
try { $definitions = db()->query("SELECT * FROM $defsT ORDER BY id DESC")->fetchAll(); }
catch (Throwable $e) { $error = $error ?: 'Form tanımları okunamadı: ' . $e->getMessage(); }

$editId = (int)($_GET['edit'] ?? 0);
$editForm = null;
if ($editId > 0) {
    foreach ($definitions as $d) if ((int)$d['id'] === $editId) $editForm = $d;
}
$editFields = $editForm ? omurga_form_fields_decode($editForm['fields'] ?? '') : omurga_default_form_fields();

function om_forms_query(array $extra = []): string {
    $base = array_merge($_GET, $extra);
    foreach ($base as $k => $v) if ($v === '' || $v === null) unset($base[$k]);
    return 'forms.php' . ($base ? '?' . http_build_query($base) : '');
}
function om_admin_submission_meta(array $r): string {
    $meta = json_decode((string)($r['meta'] ?? ''), true);
    if (!is_array($meta) || empty($meta['fields'])) return '';
    $labels = is_array($meta['labels'] ?? null) ? $meta['labels'] : [];
    $out = [];
    foreach ($meta['fields'] as $k => $v) {
        $label = $labels[$k] ?? $k;
        if ((string)$v === '') continue;
        $out[] = '<strong>'.e($label).':</strong> '.e((string)$v);
    }
    return implode('<br>', $out);
}
$title = 'Formlar';
?>
<div class="toolbar">
  <h1>Formlar</h1>
  <a class="btn light" href="forms.php?tab=submissions">Başvurular</a>
  <a class="btn light" href="forms.php?tab=builder">Form Oluşturucu</a>
</div>

<?php if ($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if ($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>

<?php if ($tab === 'builder'): ?>
<div class="card">
  <h2><?= $editForm ? 'Formu Düzenle' : 'Yeni Form Oluştur' ?></h2>
  <form method="post" class="form-grid" style="grid-template-columns:1fr 1fr">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="form_save">
    <input type="hidden" name="form_id" value="<?=e((string)($editForm['id'] ?? 0))?>">
    <label>Form Adı<input name="title" required value="<?=e($editForm['title'] ?? '')?>" placeholder="İletişim Formu"></label>
    <label>Slug<input name="slug" value="<?=e($editForm['slug'] ?? '')?>" placeholder="iletisim-formu"></label>
    <label>Form Türü
      <select name="form_type">
        <?php foreach ($allowedTypes as $k=>$v): if($k==='') continue; ?>
          <option value="<?=e($k)?>" <?=($editForm['form_type'] ?? 'contact')===$k?'selected':''?>><?=e($v)?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Durum
      <select name="status">
        <option value="active" <?=($editForm['status'] ?? 'active')==='active'?'selected':''?>>Aktif</option>
        <option value="passive" <?=($editForm['status'] ?? '')==='passive'?'selected':''?>>Pasif</option>
      </select>
    </label>
    <label>Buton Yazısı<input name="submit_label" value="<?=e($editForm['submit_label'] ?? 'Gönder')?>"></label>
    <label>Başarılı Mesajı<input name="success_message" value="<?=e($editForm['success_message'] ?? 'Başvurunuz alındı. En kısa sürede dönüş yapılacaktır.')?>"></label>
    <label style="grid-column:1/-1">Açıklama<textarea name="description" style="min-height:70px"><?=e($editForm['description'] ?? '')?></textarea></label>

    <div style="grid-column:1/-1">
      <h3>Alanlar</h3>
      <div id="om-form-fields">
        <?php foreach ($editFields as $i=>$f): ?>
          <div class="card" style="margin:10px 0; padding:12px">
            <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr 90px">
              <label>Alan Anahtarı<input name="field_key[<?=$i?>]" value="<?=e($f['key'])?>" placeholder="name"></label>
              <label>Etiket<input name="field_label[<?=$i?>]" value="<?=e($f['label'])?>" placeholder="Ad Soyad"></label>
              <label>Tip
                <select name="field_type[<?=$i?>]">
                  <?php foreach($fieldTypes as $tk=>$tv): ?><option value="<?=e($tk)?>" <?=$f['type']===$tk?'selected':''?>><?=e($tv)?></option><?php endforeach; ?>
                </select>
              </label>
              <label>Zorunlu<br><input type="checkbox" name="field_required[<?=$i?>]" value="1" <?=!empty($f['required'])?'checked':''?>></label>
              <label>Placeholder<input name="field_placeholder[<?=$i?>]" value="<?=e($f['placeholder'] ?? '')?>"></label>
              <label style="grid-column:span 3">Seçenekler <small class="muted">Select için her satıra bir seçenek</small><textarea name="field_options[<?=$i?>]" style="min-height:54px"><?=e($f['options'] ?? '')?></textarea></label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn light" onclick="omAddFormField()">+ Alan Ekle</button>
    </div>
    <div style="grid-column:1/-1"><button class="btn primary">Formu Kaydet</button></div>
  </form>
</div>

<div class="card">
  <h2>Kayıtlı Formlar</h2>
  <table class="table">
    <thead><tr><th>Form</th><th>Tür</th><th>Durum</th><th>Kısa Kod</th><th>İşlem</th></tr></thead>
    <tbody>
      <?php foreach($definitions as $d): ?>
      <tr>
        <td><strong><?=e($d['title'])?></strong><br><small class="muted"><?=e($d['description'] ?? '')?></small></td>
        <td><?=e($d['form_type'])?></td>
        <td><span class="badge <?=e($d['status'])?>"><?=e($d['status']==='active'?'Aktif':'Pasif')?></span></td>
        <td><code>[form id="<?=e((string)$d['id'])?>"]</code></td>
        <td>
          <a class="btn light" href="forms.php?tab=builder&edit=<?=e((string)$d['id'])?>">Düzenle</a>
          <form method="post" class="inline-form" style="display:inline">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="form_delete">
            <input type="hidden" name="form_id" value="<?=e((string)$d['id'])?>">
            <button class="btn danger" onclick="return confirm('Bu form silinsin mi? Eski başvurular korunur.')">Sil</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
function omAddFormField(){
  const wrap=document.getElementById('om-form-fields');
  const i=wrap.querySelectorAll('.card').length+1;
  const div=document.createElement('div');
  div.className='card'; div.style.cssText='margin:10px 0; padding:12px';
  div.innerHTML=`<div class="form-grid" style="grid-template-columns:1fr 1fr 1fr 90px">
    <label>Alan Anahtarı<input name="field_key[${i}]" placeholder="field_${i}"></label>
    <label>Etiket<input name="field_label[${i}]" placeholder="Yeni Alan"></label>
    <label>Tip<select name="field_type[${i}]"><option value="text">Metin</option><option value="email">E-posta</option><option value="tel">Telefon</option><option value="textarea">Uzun Metin</option><option value="select">Seçim</option><option value="checkbox">Onay Kutusu</option><option value="number">Sayı</option><option value="url">URL</option></select></label>
    <label>Zorunlu<br><input type="checkbox" name="field_required[${i}]" value="1"></label>
    <label>Placeholder<input name="field_placeholder[${i}]"></label>
    <label style="grid-column:span 3">Seçenekler <small class="muted">Select için her satıra bir seçenek</small><textarea name="field_options[${i}]" style="min-height:54px"></textarea></label>
  </div>`;
  wrap.appendChild(div);
}
</script>
<?php else: ?>
<div class="card">
  <div class="type-tabs">
    <?php foreach ($allowedTypes as $typeKey => $typeLabel): ?>
      <a class="<?=$type===$typeKey?'active':''?>" href="<?=e(om_forms_query(['type'=>$typeKey]))?>"><?=e($typeLabel)?></a>
    <?php endforeach; ?>
  </div>

  <form method="get" class="form-grid" style="grid-template-columns:2fr 1fr auto; align-items:end; margin:12px 0 16px">
    <input type="hidden" name="tab" value="submissions">
    <label>Arama<input name="q" value="<?=e($q)?>" placeholder="Ad, telefon, e-posta, mesaj veya özel alan ara"></label>
    <label>Durum
      <select name="status">
        <option value="">Tümü</option>
        <?php foreach ($allowedStatuses as $statusKey => $statusLabel): ?>
          <option value="<?=e($statusKey)?>" <?=$statusFilter===$statusKey?'selected':''?>><?=e($statusLabel)?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?=e($type)?>"><?php endif; ?>
    <button class="btn light">Filtrele</button>
  </form>

  <table class="table">
    <thead><tr><th>Ad Soyad</th><th>Telefon</th><th>E-posta</th><th>Tür</th><th>Durum</th><th>Detay</th><th>Tarih</th><th>İşlem</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?=e($r['name'] ?? '')?></strong></td>
          <td><?=e($r['phone'] ?? '')?></td>
          <td><?=e($r['email'] ?? '')?></td>
          <td><?=e($r['form_type'] ?? '')?></td>
          <td><span class="badge <?=e($r['status'] ?? 'new')?>"><?=e($allowedStatuses[$r['status'] ?? 'new'] ?? ($r['status'] ?? 'new'))?></span></td>
          <td><?=om_admin_submission_meta($r) ?: e(excerpt((string)($r['message'] ?? ''), 120))?></td>
          <td><?=e($r['created_at'] ?? '')?></td>
          <td>
            <form method="post" class="inline-form">
              <?=csrf_field()?>
              <input type="hidden" name="id" value="<?=e((string)($r['id'] ?? 0))?>">
              <select name="status" style="width:120px">
                <?php foreach ($allowedStatuses as $statusKey => $statusLabel): ?>
                  <option value="<?=e($statusKey)?>" <?=($r['status'] ?? 'new')===$statusKey?'selected':''?>><?=e($statusLabel)?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn light" name="action" value="submission_status">Kaydet</button>
              <button class="btn danger" name="action" value="submission_delete" onclick="return confirm('Bu başvuru silinsin mi?')">Sil</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$rows): ?><p class="muted">Henüz form başvurusu yok.</p><?php endif; ?>
</div>
<?php endif; ?>
<?php require '_footer.php'; ?>
