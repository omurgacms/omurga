<?php
/* OmHaber tema yardımcıları. Sadece tema içinde çalışır; çekirdeğe kalıcı kayıt/ekleme yapmaz. */
if (!function_exists('omh_theme_url')) {
function omh_theme_url(string $path=''): string { return function_exists('omurga_theme_url') ? omurga_theme_url($path, 'omhaber') : $path; }
function omh_s(string $key, $default='') { return function_exists('theme_setting') ? theme_setting($key, $default, 'omhaber') : $default; }
function omh_bool(string $key, bool $default=false): bool { return function_exists('theme_setting_bool') ? theme_setting_bool($key, $default, 'omhaber') : $default; }
function omh_e($text): string { return function_exists('e') ? e((string)$text) : htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
function omh_url(string $path=''): string { return function_exists('omurga_url') ? omurga_url($path) : '/'.ltrim($path,'/'); }
function omh_post_url(array $post): string { return function_exists('post_url') ? post_url($post) : '#'; }
function omh_category_url(array $cat): string { return function_exists('category_url') ? category_url($cat) : '#'; }
function omh_excerpt($text, int $len=140): string { return function_exists('excerpt') ? excerpt((string)$text, $len) : mb_substr(strip_tags((string)$text),0,$len); }
function omh_date(array $post): string { $d=$post['published_at'] ?? $post['created_at'] ?? ''; return $d ? date('d.m.Y H:i', strtotime($d)) : date('d.m.Y H:i'); }
function omh_img(array $post, string $size='large'): string {
    $img = trim((string)($post['featured_image'] ?? ''));
    if ($img && function_exists('image_url')) return image_url($img);
    return omh_theme_url('assets/img/placeholder.svg');
}
function omh_title(): string { return function_exists('setting') ? setting('site_name','OmHaber') : 'OmHaber'; }
function omh_posts(array $args=[]): array {
    $limit=max(1,(int)($args['limit'] ?? 6));
    if (!function_exists('db') || !function_exists('table_name')) return [];
    $postsT=table_name('posts'); $catsT=table_name('categories');
    try{
        if(!empty($args['category_id'])){
            $st=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.category_id=? ORDER BY p.published_at DESC,p.id DESC LIMIT $limit");
            $st->execute([(int)$args['category_id']]); return $st->fetchAll();
        }
        if(!empty($args['category_slug'])){
            $st=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND c.slug=? ORDER BY p.published_at DESC,p.id DESC LIMIT $limit");
            $st->execute([(string)$args['category_slug']]); return $st->fetchAll();
        }
        return db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' ORDER BY p.published_at DESC,p.id DESC LIMIT $limit")->fetchAll();
    }catch(Throwable $e){ if(function_exists('omurga_write_error')) omurga_write_error($e); return []; }
}
function omh_block_posts(array $settings=[], int $fallback=6): array {
    $limit=max(1,(int)($settings['limit'] ?? $fallback));
    $args=['limit'=>$limit];
    if(!empty($settings['category_id'])) $args['category_id']=(int)$settings['category_id'];
    if(!empty($settings['category_slug'])) $args['category_slug']=(string)$settings['category_slug'];
    return omh_posts($args);
}
function omh_categories(int $limit=8): array {
    if (!function_exists('db') || !function_exists('table_name')) return [];
    try { return db()->query('SELECT * FROM '.table_name('categories').' ORDER BY sort_order ASC, name ASC LIMIT '.(int)$limit)->fetchAll(); }
    catch(Throwable $e){ return []; }
}
function omh_menu_items(): array {
    if(function_exists('menu_items')){ $items=menu_items('main'); if($items) return $items; }
    $cats=omh_categories(9); $out=[]; foreach($cats as $c){ $out[]=['title'=>$c['name'] ?? '', 'url'=>omh_category_url($c)]; }
    if(!$out){ foreach(['Gündem','Siyaset','Ekonomi','Spor','Teknoloji','Yaşam'] as $t){ $out[]=['title'=>$t,'url'=>'#']; } }
    return $out;
}
function omh_render_region_safe(string $region, array $context=[]): string { return function_exists('omurga_render_region') ? omurga_render_region($region,$context) : ''; }
function omh_render_theme_block(string $slug, array $settings=[]): string {
    $file=__DIR__.'/blocks/'.$slug.'/view.php';
    if(!is_file($file)) return '';
    $block=['slug'=>$slug,'enabled'=>1,'settings'=>$settings];
    $posts=[];
    ob_start(); include $file; return (string)ob_get_clean();
}
function omh_require_functions(): void { /* compatibility placeholder */ }
}
