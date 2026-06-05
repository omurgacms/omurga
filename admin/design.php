<?php require '_layout.php'; verify_csrf(); require_cap('users.manage');
$theme = omurga_active_theme();
$meta = omurga_theme_meta($theme);
$defs = omurga_theme_settings_definitions($theme);
$values = omurga_theme_settings_values($theme);
$notice = '';
$error = '';

function render_theme_setting_field(string $key, array $field, $value): string {
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? $key;
    $help = $field['help'] ?? '';
    $name = 'theme_settings[' . $key . ']';
    $html = '<div class="theme-setting-field theme-field-' . e($type) . '"><label><span>' . e($label) . '</span>';
    if ($type === 'textarea') {
        $html .= '<textarea name="' . e($name) . '" rows="4">' . e($value) . '</textarea>';
    } elseif ($type === 'number') {
        $min = isset($field['min']) ? ' min="' . e($field['min']) . '"' : '';
        $max = isset($field['max']) ? ' max="' . e($field['max']) . '"' : '';
        $html .= '<input type="number" name="' . e($name) . '" value="' . e($value) . '"' . $min . $max . '>';
    } elseif ($type === 'checkbox') {
        $html .= '<input type="checkbox" name="' . e($name) . '" value="1" ' . (!empty($value) ? 'checked' : '') . '>';
    } elseif ($type === 'color') {
        $html .= '<input type="color" name="' . e($name) . '" value="' . e($value ?: ($field['default'] ?? '#f97316')) . '">';
    } elseif ($type === 'select') {
        $html .= '<select name="' . e($name) . '">';
        foreach (($field['options'] ?? []) as $k => $v) {
            $html .= '<option value="' . e($k) . '" ' . (((string)$value === (string)$k) ? 'selected' : '') . '>' . e($v) . '</option>';
        }
        $html .= '</select>';
    } elseif ($type === 'image') {
        if ($value) $html .= '<div class="theme-image-preview"><img src="' . e(image_url($value)) . '" alt=""></div>';
        $html .= '<input type="text" name="' . e($name) . '" value="' . e($value) . '" placeholder="uploads/... veya https://...">';
        $html .= '<input type="file" name="theme_image_' . e($key) . '" accept="image/*">';
    } elseif ($type === 'url') {
        $html .= '<input type="url" name="' . e($name) . '" value="' . e($value) . '" placeholder="https://...">';
    } else {
        $html .= '<input type="text" name="' . e($name) . '" value="' . e($value) . '">';
    }
    $html .= '</label>';
    if ($help) $html .= '<small>' . e($help) . '</small>';
    return $html . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset-theme-settings') {
        update_theme_settings([], $theme);
        $values = omurga_theme_settings_values($theme);
        $notice = 'Tema ayarları varsayılan değerlere döndürüldü.';
    } else {
        try {
            $posted = $_POST['theme_settings'] ?? [];
            $new = [];
            foreach ($defs as $key => $field) {
                $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$key);
                if ($safeKey === '') continue;
                $value = $posted[$safeKey] ?? (($field['type'] ?? '') === 'checkbox' ? '' : ($field['default'] ?? ''));
                if (($field['type'] ?? '') === 'image' && !empty($_FILES['theme_image_' . $safeKey]['name'])) {
                    $saved = save_uploaded_file('theme_image_' . $safeKey, true);
                    if ($saved) {
                        insert_media_record($saved, $field['label'] ?? $safeKey, (int)($_SESSION['omurga_user_id'] ?? 0));
                        $value = $saved;
                    }
                }
                $new[$safeKey] = omurga_normalize_theme_setting_value($field, $value);
            }
            update_theme_settings($new, $theme);
            $values = omurga_theme_settings_values($theme);
            $notice = 'Tema ayarları kaydedildi.';
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<div class="toolbar"><div><h1>Tema Ayarları</h1><p class="muted">Aktif tema: <b><?=e($meta['name'] ?? $theme)?></b>. Bu ekran ayarları aktif temanın <code>theme.json</code> dosyasındaki <code>settings</code> alanından otomatik çeker.</p></div><form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><button name="action" value="reset-theme-settings" class="btn light" onclick="return confirm('Bu temanın ayarları varsayılana dönsün mü?')">Varsayılana Dön</button></form></div>
<?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<div class="card theme-settings-help"><strong>Nasıl çalışır?</strong><p>Tema yapımcısı yeni ayarı <code>theme.json</code> içine ekler. Omurga çekirdeğine dokunmadan bu ekranda otomatik görünür. Kullanıcının seçtiği değerler tema dosyasına değil veritabanına kaydedilir; tema güncellense bile ayarlar korunur.</p></div>
<?php if(!$defs): ?>
  <div class="card"><h2>Bu tema için özel ayar yok</h2><p class="muted">Tema yapımcısı <code>theme.json</code> içine <code>settings</code> alanı eklerse ayarlar burada listelenir.</p></div>
<?php else: ?>
<form method="post" enctype="multipart/form-data" class="card theme-settings-form"><input type="hidden" name="_csrf" value="<?=csrf_token()?>">
  <div class="theme-settings-grid">
  <?php foreach($defs as $key=>$field): $safeKey=preg_replace('/[^a-zA-Z0-9_\-]/','',(string)$key); if($safeKey==='') continue; ?>
    <?=render_theme_setting_field($safeKey, $field, $values[$safeKey] ?? ($field['default'] ?? ''))?>
  <?php endforeach; ?>
  </div>
  <button class="btn primary">Tema Ayarlarını Kaydet</button>
</form>
<div class="card"><h2>Tema geliştirici kullanımı</h2><pre><?=e("theme_setting('header_type', 'classic')\ntheme_setting('primary_color', '#f97316')\ntheme_setting_bool('show_topbar', true)")?></pre></div>
<?php endif; ?>
<?php require '_footer.php'; ?>
