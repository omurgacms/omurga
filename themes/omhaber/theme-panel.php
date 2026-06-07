<?php
if(!defined('OMURGA_ROOT')) { exit; }
if(!function_exists('can') || !(can('design.manage') || current_user_role()==='admin')) {
    echo '<div class="alert error">Bu paneli görüntüleme yetkiniz yok.</div>';
    return;
}

$themeSlug = 'omhaber';
$defs = function_exists('omurga_theme_settings_definitions') ? omurga_theme_settings_definitions($themeSlug) : [];
$values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values($themeSlug) : [];
$notice = '';
$error = '';

if(!function_exists('omh_panel_field')){
    function omh_panel_field(string $key, array $field, $value): string {
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? $key;
        $help = $field['help'] ?? '';
        $name = 'theme_settings['.$key.']';
        $html = '<label class="omh-panel-field omh-field-'.e($type).'"><span>'.e($label).'</span>';
        if($type==='textarea'){
            $html .= '<textarea name="'.e($name).'" rows="4">'.e($value).'</textarea>';
        } elseif($type==='checkbox'){
            $html .= '<input type="hidden" name="'.e($name).'" value="0"><label class="omh-switch"><input type="checkbox" name="'.e($name).'" value="1" '.(!empty($value)?'checked':'').'><b></b></label>';
        } elseif($type==='color'){
            $html .= '<input type="color" name="'.e($name).'" value="'.e($value ?: ($field['default'] ?? '#f97316')).'">';
        } elseif($type==='select'){
            $html .= '<select name="'.e($name).'">';
            foreach(($field['options'] ?? []) as $k=>$v){ $html .= '<option value="'.e($k).'" '.(((string)$value===(string)$k)?'selected':'').'>'.e($v).'</option>'; }
            $html .= '</select>';
        } elseif($type==='number'){
            $html .= '<input type="number" name="'.e($name).'" value="'.e($value).'">';
        } elseif($type==='url'){
            $html .= '<input type="url" name="'.e($name).'" value="'.e($value).'" placeholder="https://...">';
        } else {
            $html .= '<input type="text" name="'.e($name).'" value="'.e($value).'">';
        }
        if($help) $html .= '<small>'.e($help).'</small>';
        return $html.'</label>';
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if(function_exists('verify_csrf')) verify_csrf();
        $posted = $_POST['theme_settings'] ?? [];
        $new = [];
        foreach($defs as $key=>$field){
            $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/','',(string)$key);
            if($safeKey==='') continue;
            $value = $posted[$safeKey] ?? (($field['type'] ?? '')==='checkbox' ? '0' : ($field['default'] ?? ''));
            $new[$safeKey] = function_exists('omurga_normalize_theme_setting_value') ? omurga_normalize_theme_setting_value($field, $value) : trim((string)$value);
        }
        if(function_exists('update_theme_settings')) update_theme_settings($new, $themeSlug);
        $values = function_exists('omurga_theme_settings_values') ? omurga_theme_settings_values($themeSlug) : $new;
        $notice = 'OmHaber ayarları kaydedildi.';
    }catch(Throwable $e){ $error = $e->getMessage(); }
}

$groups = [
    'Genel' => ['primary_color','dark_color','logo_text','site_width'],
    'Header' => ['topbar_text','live_url'],
    'Sosyal Medya' => ['facebook_url','x_url','instagram_url','youtube_url'],
    'Footer' => ['footer_desc','contact_text'],
    'Mobil' => ['show_mobile_nav'],
];
?>
<style>
.omh-panel{display:grid;gap:18px}.omh-hero{background:linear-gradient(135deg,#0f172a,#111827);color:#fff;border-radius:18px;padding:22px;display:flex;justify-content:space-between;gap:16px;align-items:center}.omh-hero h2{margin:0;font-size:24px}.omh-hero p{margin:6px 0 0;color:#cbd5e1}.omh-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.omh-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;box-shadow:0 10px 30px rgba(15,23,42,.06)}.omh-card h3{margin:0 0 12px}.omh-panel-field{display:block;margin:0 0 13px}.omh-panel-field span{display:block;font-weight:700;margin-bottom:6px}.omh-panel-field input:not([type=color]),.omh-panel-field textarea,.omh-panel-field select{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 12px}.omh-panel-field small{display:block;color:#64748b;margin-top:5px}.omh-actions{position:sticky;bottom:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:12px;display:flex;justify-content:flex-end;box-shadow:0 -6px 20px rgba(15,23,42,.06)}.omh-switch input{display:none}.omh-switch b{display:inline-block;width:46px;height:24px;border-radius:999px;background:#cbd5e1;position:relative;vertical-align:middle}.omh-switch b:before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;left:3px;top:3px;transition:.2s}.omh-switch input:checked+b{background:#f97316}.omh-switch input:checked+b:before{left:25px}@media(max-width:900px){.omh-grid{grid-template-columns:1fr}.omh-hero{display:block}}
</style>
<div class="omh-panel">
  <div class="omh-hero"><div><h2>OmHaber Paneli</h2><p>Bu panel OmHaber temasının içinden gelir. Çekirdeğe OmHaber özel sayfası gömmez.</p></div><a class="btn light" href="themes.php">Temalara Git</a></div>
  <?php if($notice): ?><div class="alert success"><?=e($notice)?></div><?php endif; ?>
  <?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
  <?php if(!$defs): ?>
    <div class="card">OmHaber ayar tanımı bulunamadı.</div>
  <?php else: ?>
  <form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
    <div class="omh-grid">
      <?php foreach($groups as $title=>$keys): ?>
        <div class="omh-card"><h3><?=e($title)?></h3>
          <?php foreach($keys as $key): if(!isset($defs[$key])) continue; echo omh_panel_field($key, $defs[$key], $values[$key] ?? ($defs[$key]['default'] ?? '')); endforeach; ?>
        </div>
      <?php endforeach; ?>
      <div class="omh-card"><h3>Diğer Ayarlar</h3>
        <?php foreach($defs as $key=>$field): $listed=false; foreach($groups as $ks){ if(in_array($key,$ks,true)){ $listed=true; break; } } if($listed) continue; echo omh_panel_field($key, $field, $values[$key] ?? ($field['default'] ?? '')); endforeach; ?>
      </div>
    </div>
    <div class="omh-actions"><button class="btn primary">OmHaber Ayarlarını Kaydet</button></div>
  </form>
  <?php endif; ?>
</div>
