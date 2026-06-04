<?php
require_once __DIR__.'/_layout.php';
require_cap('themes.manage');
if(function_exists('omurga_security_can_edit_theme') && !omurga_security_can_edit_theme()){ echo '<div class="page-head"><div><h1>Tema Düzenleyici Kapalı</h1><p>Güvenlik Merkezi üzerinden Tema Düzenleyici kapatılmış.</p></div></div><div class="card"><a class="btn primary" href="security.php">Güvenlik Ayarlarına Git</a></div>'; require __DIR__.'/_footer.php'; exit; }
omurga_migrate();

$themesRoot = realpath(dirname(__DIR__).'/themes');
$themeBackupRoot = dirname(__DIR__).'/storage/backups/theme-editor';
@mkdir($themeBackupRoot, 0775, true);

function oe_slug(string $v): string { return preg_replace('/[^a-z0-9_-]/','', strtolower(trim($v))); }
function oe_rel(string $v): string {
    $v = str_replace('\\', '/', trim($v));
    $v = preg_replace('#/+#', '/', $v);
    $parts = [];
    foreach (explode('/', $v) as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') continue;
        $parts[] = preg_replace('/[^a-zA-Z0-9._\-]/', '', $part);
    }
    return implode('/', array_filter($parts, fn($x)=>$x!==''));
}
function oe_theme_dir(string $slug, string $themesRoot): string {
    $slug = oe_slug($slug);
    $path = realpath($themesRoot.'/'.$slug);
    if (!$path || strpos($path, $themesRoot)!==0 || !is_dir($path)) throw new RuntimeException('Tema klasörü bulunamadı.');
    return $path;
}
function oe_target(string $slug, string $rel, string $themesRoot, bool $mustExist=true): string {
    $base = oe_theme_dir($slug, $themesRoot);
    $rel = oe_rel($rel);
    if ($rel === '') throw new RuntimeException('Dosya seçilmedi.');
    $target = $base.'/'.$rel;
    if ($mustExist) {
        $real = realpath($target);
        if (!$real || strpos($real, $base)!==0) throw new RuntimeException('Dosya tema klasörü dışında olamaz.');
        return $real;
    }
    $parent = dirname($target);
    $parentReal = realpath($parent);
    if (!$parentReal || strpos($parentReal, $base)!==0) throw new RuntimeException('Hedef klasör tema dışında olamaz.');
    return $target;
}
function oe_new_path(string $slug, string $rel, string $themesRoot): string {
    $base = oe_theme_dir($slug, $themesRoot);
    $rel = oe_rel($rel);
    if ($rel === '') throw new RuntimeException('Hedef adı boş olamaz.');
    $target = $base.'/'.$rel;
    $parent = dirname($target);
    $parentReal = realpath($parent);
    if (!$parentReal || strpos($parentReal, $base) !== 0) {
        throw new RuntimeException('Hedef klasör tema dışında olamaz.');
    }
    return $target;
}
function oe_new_folder_path(string $slug, string $rel, string $themesRoot): string {
    $base = oe_theme_dir($slug, $themesRoot);
    $rel = oe_rel($rel);
    if ($rel === '') throw new RuntimeException('Klasör adı boş olamaz.');
    $target = $base.'/'.$rel;
    $parent = dirname($target);
    $parentReal = realpath($parent);
    if (!$parentReal || strpos($parentReal, $base) !== 0) {
        throw new RuntimeException('Hedef klasör tema dışında olamaz.');
    }
    return $target;
}
function oe_editable(string $file): bool {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if($ext==='php' && function_exists('omurga_security_can_edit_php_files') && !omurga_security_can_edit_php_files()) return false;
    return in_array($ext, ['omg','css','js','json','txt','md','html','htm','xml','svg','php'], true);
}
function oe_code_mode(string $file): string {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return match($ext){ 'css'=>'CSS', 'js'=>'JS', 'json'=>'JSON', 'php'=>'PHP', 'omg'=>'OMG', default=>strtoupper($ext ?: 'TEXT') };
}
function oe_tree(string $dir, string $base, int $depth=0): array {
    if ($depth > 6) return [];
    $items = @scandir($dir) ?: [];
    $out=[];
    foreach ($items as $item) {
        if ($item==='.' || $item==='..') continue;
        if (in_array($item, ['.git','node_modules','vendor'], true)) continue;
        $full=$dir.'/'.$item;
        $rel=ltrim(str_replace($base, '', $full), '/');
        $out[]=['name'=>$item,'rel'=>$rel,'dir'=>is_dir($full),'children'=>is_dir($full)?oe_tree($full,$base,$depth+1):[]];
    }
    usort($out, fn($a,$b)=>($b['dir']<=>$a['dir']) ?: strcasecmp($a['name'],$b['name']));
    return $out;
}
function oe_render_tree(array $items, string $selected, string $theme): void {
    echo '<ul class="theme-file-tree">';
    foreach($items as $it){
        $icon = $it['dir'] ? '📁' : '📄';
        echo '<li class="'.($it['dir']?'is-dir':'is-file').'">';
        if($it['dir']){
            echo '<span>'.$icon.' '.e($it['name']).'</span>';
            if($it['children']) oe_render_tree($it['children'],$selected,$theme);
        } else {
            $active = $selected===$it['rel'] ? ' active' : '';
            echo '<a class="'.$active.'" href="theme-editor.php?theme='.e($theme).'&file='.e($it['rel']).'">'.$icon.' '.e($it['name']).'</a>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
function oe_copy_dir(string $src, string $dst): void {
    @mkdir($dst,0775,true);
    foreach(scandir($src) ?: [] as $item){
        if($item==='.'||$item==='..') continue;
        $from=$src.'/'.$item; $to=$dst.'/'.$item;
        if(is_dir($from)) oe_copy_dir($from,$to); else copy($from,$to);
    }
}
function oe_starter_theme(string $dst, string $name, string $slug): void {
    @mkdir($dst.'/components',0775,true);
    @mkdir($dst.'/assets/css',0775,true);
    file_put_contents($dst.'/theme.json', json_encode([
        'name'=>$name,'slug'=>$slug,'version'=>'1.0.0','template_engine'=>'omg','description'=>'Omurga OMG Tema Düzenleyici ile oluşturuldu.',
        'regions'=>['home_main'=>'Ana İçerik','sidebar'=>'Sidebar','header_main'=>'Header','footer_main'=>'Footer']
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    file_put_contents($dst.'/header.omg', "<!doctype html>\n<html lang=\"{{ site.language }}\">\n<head>\n  {!! head !!}\n  <link rel=\"stylesheet\" href=\"{{ theme.url }}/assets/css/style.css\">\n</head>\n<body>\n<header class=\"site-header\"><div class=\"container\"><a class=\"logo\" href=\"{{ site.url }}\">{{ site.name }}</a></div></header>\n");
    file_put_contents($dst.'/footer.omg', "<footer class=\"site-footer\"><div class=\"container\">{{ site.name }}</div></footer>\n</body>\n</html>\n");
    file_put_contents($dst.'/components/post-card.omg', "<article class=\"content-card\">\n  @if(post.image)<a href=\"{{ post.url }}\"><img src=\"{{ post.image }}\" alt=\"{{ post.title }}\"></a>@endif\n  <h2><a href=\"{{ post.url }}\">{{ post.title }}</a></h2>\n  <p>{{ post.excerpt }}</p>\n</article>\n");
    file_put_contents($dst.'/home.omg', "@include('header')\n<main class=\"container\">\n  <omg:content />\n  @foreach(posts as post)\n    @include('components/post-card')\n  @endforeach\n</main>\n@include('footer')\n");
    file_put_contents($dst.'/single.omg', "@include('header')\n<main class=\"container full-story\"><h1>{{ post.title }}</h1><div class=\"meta\">{{ post.date }} · {{ post.category }}</div>{!! post.content !!}</main>\n@include('footer')\n");
    file_put_contents($dst.'/page.omg', "@include('header')\n<main class=\"container static-page\"><h1>{{ post.title }}</h1>{!! post.content !!}</main>\n@include('footer')\n");
    file_put_contents($dst.'/category.omg', "@include('header')\n<main class=\"container\"><h1>{{ category.name }}</h1>@foreach(posts as post)@include('components/post-card')@endforeach</main>\n@include('footer')\n");
    file_put_contents($dst.'/assets/css/style.css', "body{margin:0;font-family:Arial,sans-serif;background:#f5f7fb;color:#172033}.container{max-width:1180px;margin:auto;padding:18px}.site-header,.site-footer{background:#0f172a;color:white}.site-header a,.site-footer a{color:white}.content-card{background:white;border-radius:14px;padding:14px;margin-bottom:16px}.content-card img,.full-story img{max-width:100%;height:auto;border-radius:12px}.meta{color:#64748b;margin:8px 0 18px}\n");
}

$msg=''; $err='';
$theme = oe_slug($_REQUEST['theme'] ?? omurga_active_theme());
if(!$theme) $theme='omurga-kolay';
$file = oe_rel($_REQUEST['file'] ?? 'theme.json');

try { $themeDir = oe_theme_dir($theme,$themesRoot); } catch(Throwable $e) { $theme = omurga_active_theme(); $themeDir = oe_theme_dir($theme,$themesRoot); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action=$_POST['action'] ?? '';
    try{
        if($action==='save'){
            $file = oe_rel($_POST['file'] ?? '');
            $target=oe_target($theme,$file,$themesRoot,true);
            if(!is_file($target) || !oe_editable($target)) throw new RuntimeException('Bu dosya düzenlenemez.');
            if(strtolower(pathinfo($target,PATHINFO_EXTENSION))==='php' && (!function_exists('omurga_security_can_edit_php_files') || !omurga_security_can_edit_php_files())) throw new RuntimeException('PHP dosyası düzenleme Güvenlik Merkezi tarafından kapalı.');
            $backupDir=$themeBackupRoot.'/'.date('Ymd-His').'-'.$theme;
            @mkdir($backupDir,0775,true);
            copy($target, $backupDir.'/'.basename($target).'.bak');
            $content=(string)($_POST['content'] ?? '');
            if(strtolower(pathinfo($target,PATHINFO_EXTENSION))==='json'){
                json_decode($content,true);
                if(json_last_error()!==JSON_ERROR_NONE) throw new RuntimeException('JSON hatalı: '.json_last_error_msg());
            }
            file_put_contents($target,$content);
            log_activity('theme_editor.save','Tema dosyası kaydedildi: '.$theme.'/'.$file);
            $msg='Dosya kaydedildi.';
        }
        if($action==='new_file'){
            $rel=oe_rel($_POST['new_file'] ?? '');
            if($rel==='') throw new RuntimeException('Dosya adı boş olamaz.');
            $target=oe_new_path($theme,$rel,$themesRoot);
            if(file_exists($target)) throw new RuntimeException('Dosya zaten var.');
            if(!oe_editable($target)) throw new RuntimeException('Bu dosya türü oluşturulamaz.');
            file_put_contents($target, "");
            $file=$rel; $msg='Yeni dosya oluşturuldu.';
            log_activity('theme_editor.file_create','Tema dosyası oluşturuldu: '.$theme.'/'.$rel);
        }
        if($action==='new_folder'){
            $rel=oe_rel($_POST['new_folder'] ?? '');
            if($rel==='') throw new RuntimeException('Klasör adı boş olamaz.');
            $target=oe_new_folder_path($theme,$rel,$themesRoot);
            if(file_exists($target)) throw new RuntimeException('Klasör zaten var.');
            @mkdir($target,0775,true);
            $msg='Yeni klasör oluşturuldu.';
            log_activity('theme_editor.folder_create','Tema klasörü oluşturuldu: '.$theme.'/'.$rel);
        }
        if($action==='delete_file'){
            $file=oe_rel($_POST['file'] ?? '');
            $target=oe_target($theme,$file,$themesRoot,true);
            if(!is_file($target)) throw new RuntimeException('Silinecek dosya bulunamadı.');
            if(basename($target)==='theme.json') throw new RuntimeException('theme.json silinemez.');
            $backupDir=$themeBackupRoot.'/deleted-'.date('Ymd-His').'-'.$theme;
            @mkdir($backupDir,0775,true); copy($target,$backupDir.'/'.basename($target).'.bak');
            unlink($target); $file='theme.json'; $msg='Dosya silindi. Yedeği alındı.';
            log_activity('theme_editor.file_delete','Tema dosyası silindi: '.$theme.'/'.$file);
        }
        if($action==='create_theme'){
            $name=trim($_POST['theme_name'] ?? '');
            $slug=oe_slug($_POST['theme_slug'] ?? '');
            $mode=$_POST['starter'] ?? 'omg';
            if($name==='' || $slug==='') throw new RuntimeException('Tema adı ve slug zorunludur.');
            $dst=$themesRoot.'/'.$slug;
            if(file_exists($dst)) throw new RuntimeException('Bu slug ile tema zaten var.');
            if($mode==='copy') oe_copy_dir($themeDir,$dst); else oe_starter_theme($dst,$name,$slug);
            $theme=$slug; $file='theme.json'; $msg='Yeni tema oluşturuldu.';
            log_activity('theme_editor.theme_create','Yeni tema oluşturuldu: '.$slug);
        }
    }catch(Throwable $e){ omurga_write_error($e); $err=$e->getMessage(); }
    try { $themeDir=oe_theme_dir($theme,$themesRoot); } catch(Throwable $e) {}
}

$themes=omurga_list_themes();
$active=omurga_active_theme();
$content=''; $selectedPath=''; $mode='';
try{
    $selectedPath=oe_target($theme,$file,$themesRoot,true);
    if(is_file($selectedPath) && oe_editable($selectedPath)){
        $content=file_get_contents($selectedPath);
        $mode=oe_code_mode($selectedPath);
    }
}catch(Throwable $e){ $err=$err ?: $e->getMessage(); $file='theme.json'; }
$tree=oe_tree($themeDir,$themeDir);
?>
<h1>Tema Düzenleyici</h1>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<section class="card">
  <div class="toolbar"><div><h2>Tema seçimi</h2><p class="muted">Sadece <code>themes/</code> klasörü düzenlenir. Çekirdek, admin, config, uploads ve storage dosyalarına erişim verilmez.</p></div><a class="btn light" href="themes.php">Temalar</a></div>
  <form method="get" class="form-grid two">
    <label>Tema
      <select name="theme" onchange="this.form.submit()">
        <?php foreach($themes as $t): ?><option value="<?=e($t['slug'])?>" <?=$t['slug']===$theme?'selected':''?>><?=e($t['name'])?><?=($t['slug']===$active?' - Aktif':'')?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Dosya
      <input type="text" name="file" value="<?=e($file)?>" placeholder="home.omg">
    </label>
    <div><button class="btn primary">Aç</button></div>
  </form>
</section>

<section class="theme-editor-grid">
  <aside class="card theme-editor-tree">
    <h2>Dosyalar</h2>
    <?php oe_render_tree($tree,$file,$theme); ?>
    <hr>
    <form method="post" class="mini-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="new_file"><input type="hidden" name="theme" value="<?=e($theme)?>"><input name="new_file" placeholder="components/card.omg"><button class="btn light">Yeni dosya</button></form>
    <form method="post" class="mini-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="new_folder"><input type="hidden" name="theme" value="<?=e($theme)?>"><input name="new_folder" placeholder="parts"><button class="btn light">Yeni klasör</button></form>
  </aside>
  <main class="card theme-editor-main">
    <div class="toolbar"><div><h2><?=e($file)?></h2><p class="muted">Mod: <?=e($mode ?: 'Dosya seçilmedi')?> · Kaydetmeden önce otomatik yedek alınır.</p></div><span class="badge">Tema: <?=e($theme)?></span></div>
    <?php if($content!=='' || is_file($selectedPath)): ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="theme" value="<?=e($theme)?>"><input type="hidden" name="file" value="<?=e($file)?>">
      <textarea class="code-editor" name="content" spellcheck="false"><?=e($content)?></textarea>
      <div class="theme-editor-actions"><button class="btn primary">Kaydet</button><button class="btn primary" name="save_all" value="1">Tümünü Kaydet</button></div>
    </form>
    <?php if(basename($file)!=='theme.json'): ?>
    <form method="post" onsubmit="return confirm('Dosya silinsin mi? Otomatik yedek alınacak.');"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete_file"><input type="hidden" name="theme" value="<?=e($theme)?>"><input type="hidden" name="file" value="<?=e($file)?>"><button class="btn danger">Dosyayı Sil</button></form>
    <?php endif; ?>
    <?php else: ?><p class="muted">Düzenlenebilir bir dosya seç.</p><?php endif; ?>
  </main>
</section>

<section class="card">
  <h2>Yeni Tema Oluştur</h2>
  <form method="post" class="form-grid three"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create_theme">
    <label>Tema adı<input name="theme_name" placeholder="Omurga Örnek Teması"></label>
    <label>Tema slug<input name="theme_slug" placeholder="omurga-ornek-temasi"></label>
    <label>Başlangıç tipi<select name="starter"><option value="omg">OMG başlangıç teması</option><option value="copy">Seçili temayı kopyala</option></select></label>
    <div><button class="btn primary">Yeni Tema Oluştur</button></div>
  </form>
</section>

<section class="card">
  <h2>OMG etiket yardımcısı</h2>
  <div class="tag-help"><code>{{ site.name }}</code><code>{{ site.url }}</code><code>{{ theme.url }}</code><code>{{ post.title }}</code><code>{{ post.excerpt }}</code><code>{!! post.content !!}</code><code>@include('header')</code><code>@foreach(posts as post)...@endforeach</code><code>@if(post.image)...@endif</code><code>&lt;omg:content /&gt;</code></div>
</section>

<style>
.theme-editor-grid{display:grid;grid-template-columns:280px 1fr;gap:18px;align-items:start}.theme-editor-tree{position:sticky;top:78px}.theme-file-tree,.theme-file-tree ul{list-style:none;margin:0;padding-left:14px}.theme-file-tree>li{padding-left:0}.theme-file-tree li{margin:4px 0}.theme-file-tree a,.theme-file-tree span{display:block;padding:6px 8px;border-radius:8px;text-decoration:none;color:#334155}.theme-file-tree a.active,.theme-file-tree a:hover{background:#eef4ff;color:#0f4c81}.code-editor{width:100%;min-height:560px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.55;background:#0f172a;color:#e5e7eb;border:0;border-radius:14px;padding:18px;box-sizing:border-box;tab-size:2}.mini-form{display:flex;gap:6px;margin-top:8px}.mini-form input{min-width:0;flex:1}.theme-editor-actions{display:flex;gap:8px;margin-top:12px}.tag-help{display:flex;flex-wrap:wrap;gap:8px}.tag-help code{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:6px 10px}@media(max-width:900px){.theme-editor-grid{grid-template-columns:1fr}.theme-editor-tree{position:static}.code-editor{min-height:420px}}
</style>
<?php require __DIR__.'/_footer.php'; ?>
