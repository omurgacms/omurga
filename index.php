<?php
require_once __DIR__ . '/bootstrap.php';
if(function_exists('omurga_frontend_maintenance_lock_active') && omurga_frontend_maintenance_lock_active()){
    http_response_code(503);
    echo '<!doctype html><meta charset="utf-8"><title>Omurga CMS</title><main style="font-family:Arial,sans-serif;max-width:720px;margin:80px auto;padding:24px"><h1>Omurga CMS</h1><p>'.e(omurga_frontend_maintenance_lock_message()).'</p></main>';
    exit;
}
$postsT=table_name('posts'); $catsT=table_name('categories'); $formsT=table_name('forms');
$siteName=setting('site_name','Omurga'); $desc=setting('site_description','Web sitelerinin güçlü yayın altyapısı'); $st=site_type(); $slug=current_path();
if($slug==='api' || str_starts_with($slug,'api/')){ omurga_api_dispatch(); }

function omurga_posts_by_category_slug(string $categorySlug, int $limit=6): array {
    $postsT=table_name('posts'); $catsT=table_name('categories');
    $stmt=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' AND c.slug=? ORDER BY p.sort_order ASC, p.published_at DESC, p.id DESC LIMIT ".max(1,$limit));
    $stmt->execute([$categorySlug]);
    return $stmt->fetchAll();
}
function omurga_page_by_slug(string $pageSlug): ?array {
    $postsT=table_name('posts');
    $stmt=db()->prepare("SELECT p.* FROM $postsT p WHERE p.status='published' AND p.type='page' AND p.slug=? LIMIT 1");
    $stmt->execute([$pageSlug]);
    $page=$stmt->fetch();
    return $page ?: null;
}


function omurga_feed_posts(?string $categorySlug=null, int $limit=50, bool $newsOnly=false): array {
    $postsT=table_name('posts'); $catsT=table_name('categories'); $usersT=table_name('users');
    $limit=max(1,min(1000,$limit));
    $where="p.status='published' AND p.type<>'page'";
    $params=[];
    if($newsOnly){ $where .= " AND p.type IN ('news','post') AND p.published_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)"; }
    if($categorySlug!==null && $categorySlug!==''){
        $where .= " AND c.slug=?"; $params[]=$categorySlug;
    }
    $sql="SELECT p.*, c.name category_name, c.slug category_slug, u.name author_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id LEFT JOIN $usersT u ON u.id=p.author_id WHERE $where ORDER BY p.published_at DESC, p.id DESC LIMIT $limit";
    $stmt=db()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
}
function omurga_xml_date($value): string { $ts=strtotime((string)$value); return date(DATE_RSS, $ts ?: time()); }
function omurga_atom_date($value): string { $ts=strtotime((string)$value); return date('c', $ts ?: time()); }
function omurga_render_rss_feed(array $posts, string $title, string $link, string $description): void {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">' . "\n<channel>\n";
    echo '<title>'.e($title).'</title>' . "\n";
    echo '<link>'.e($link).'</link>' . "\n";
    echo '<description>'.e($description).'</description>' . "\n";
    echo '<language>'.e(setting('site_language','tr-TR')).'</language>' . "\n";
    echo '<atom:link href="'.e($link).'" rel="self" type="application/rss+xml" />' . "\n";
    foreach($posts as $post){
        $url=post_url($post); $date=$post['published_at'] ?: $post['created_at']; $img=image_url($post['featured_image'] ?? '');
        echo "<item>\n";
        echo '<title>'.e($post['title'] ?? '').'</title>' . "\n";
        echo '<link>'.e($url).'</link>' . "\n";
        echo '<guid isPermaLink="true">'.e($url).'</guid>' . "\n";
        echo '<pubDate>'.e(omurga_xml_date($date)).'</pubDate>' . "\n";
        if(!empty($post['author_name'])) echo '<author>'.e($post['author_name']).'</author>' . "\n";
        if(!empty($post['category_name'])) echo '<category>'.e($post['category_name']).'</category>' . "\n";
        echo '<description><![CDATA['.excerpt((string)($post['spot'] ?: $post['content'] ?? ''), 280).']]></description>' . "\n";
        if($img!=='') echo '<media:content url="'.e($img).'" medium="image" />' . "\n";
        echo "</item>\n";
    }
    echo "</channel>\n</rss>"; exit;
}
function omurga_render_google_news_rss(array $posts): void {
    $title=setting('site_name','Omurga').' - Google News';
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">' . "\n<channel>\n";
    echo '<title>'.e($title).'</title>' . "\n";
    echo '<link>'.e(omurga_url()).'</link>' . "\n";
    echo '<description>'.e(setting('site_description','Son yayınlar')).'</description>' . "\n";
    echo '<language>tr-TR</language>' . "\n";
    echo '<atom:link href="'.e(omurga_url('google-news.xml')).'" rel="self" type="application/rss+xml" />' . "\n";
    foreach($posts as $post){
        $url=post_url($post); $date=$post['published_at'] ?: $post['created_at']; $img=image_url($post['featured_image'] ?? '');
        echo "<item>\n";
        echo '<title>'.e($post['title'] ?? '').'</title>' . "\n";
        echo '<link>'.e($url).'</link>' . "\n";
        echo '<guid isPermaLink="true">'.e($url).'</guid>' . "\n";
        echo '<pubDate>'.e(omurga_xml_date($date)).'</pubDate>' . "\n";
        if(!empty($post['author_name'])) echo '<author>'.e($post['author_name']).'</author>' . "\n";
        if(!empty($post['category_name'])) echo '<category>'.e($post['category_name']).'</category>' . "\n";
        echo '<description><![CDATA['.excerpt((string)($post['spot'] ?: $post['content'] ?? ''), 320).']]></description>' . "\n";
        if($img!=='') echo '<media:content url="'.e($img).'" medium="image" />' . "\n";
        echo "</item>\n";
    }
    echo "</channel>\n</rss>"; exit;
}

function omurga_render_atom_feed(array $posts, string $title, string $link, string $description): void {
    header('Content-Type: application/atom+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "
";
    echo '<feed xmlns="http://www.w3.org/2005/Atom">' . "
";
    echo '<title>'.e($title).'</title>' . "
";
    echo '<link href="'.e($link).'" rel="self" />' . "
";
    echo '<link href="'.e(omurga_url()).'" />' . "
";
    echo '<id>'.e(omurga_url()).'</id>' . "
";
    echo '<updated>'.e(omurga_atom_date($posts[0]['updated_at'] ?? $posts[0]['published_at'] ?? 'now')).'</updated>' . "
";
    echo '<subtitle>'.e($description).'</subtitle>' . "
";
    foreach($posts as $post){ $url=post_url($post); $date=$post['updated_at'] ?: ($post['published_at'] ?: $post['created_at']); echo '<entry>' . "
"; echo '<title>'.e($post['title'] ?? '').'</title>' . "
"; echo '<link href="'.e($url).'" />' . "
"; echo '<id>'.e($url).'</id>' . "
"; echo '<updated>'.e(omurga_atom_date($date)).'</updated>' . "
"; if(!empty($post['author_name'])) echo '<author><name>'.e($post['author_name']).'</name></author>' . "
"; echo '<summary><![CDATA['.excerpt((string)($post['spot'] ?: $post['content'] ?? ''), 280).']]></summary>' . "
"; echo '</entry>' . "
"; }
    echo '</feed>'; exit;
}

function omurga_tpl_page_vars(array $vars=[]): array { global $title,$meta,$canonical,$ogImage,$siteName,$desc,$formNotice; return array_merge(['title'=>$title ?? $siteName, 'meta'=>$meta ?? $desc, 'canonical'=>$canonical ?? omurga_url(), 'ogImage'=>$ogImage ?? '', 'seo_title'=>$title ?? $siteName, 'seo_description'=>$meta ?? $desc, 'canonical_url'=>$canonical ?? omurga_url(), 'og_image'=>$ogImage ?? '', 'head'=>omurga_seo_head(['title'=>$title ?? $siteName, 'meta'=>$meta ?? $desc, 'canonical'=>$canonical ?? omurga_url(), 'og_image'=>$ogImage ?? '']), 'formNotice'=>$formNotice ?? ''], $vars); }
omurga_migrate();
if(setting('maintenance_mode','0')==='1' && !is_admin_logged_in() && !in_array($slug, ['robots.txt','sitemap.xml','sitemap-posts.xml','sitemap-pages.xml','sitemap-categories.xml','sitemap-tags.xml','sitemap-images.xml','sitemap-authors.xml','news-sitemap.xml','feed.xml','rss.xml','atom.xml','google-news.xml'], true)){
    http_response_code(503);
    $title='Bakım Modu'; $meta=setting('maintenance_message','Sitemiz kısa süreli bakımda.');
    if(omurga_render_theme_omg('maintenance.omg', omurga_tpl_page_vars())) exit; include omurga_theme_file('maintenance.php'); exit;
}
$formNotice='';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['omurga_form'] ?? '')==='1'){
    $formNotice=omurga_handle_form_submission();
}
om_handle_comment_submission();
if(in_array($slug, ['hesabim','user-center'], true)){
    $title=om_t('user_center.title','Hesabım').' - '.$siteName;
    $meta=om_t('user_center.description','Kullanıcı profili, yazılar ve bildirimler.');
    $canonical=omurga_url('hesabim');
    $content=omurga_render_block(['slug'=>'user-center','enabled'=>1,'width'=>'100','settings'=>[]], ['route'=>'user-center']);
    $page=['id'=>0,'title'=>om_t('user_center.title','Hesabım'),'slug'=>'hesabim','spot'=>'','content'=>$content,'featured_image'=>'','type'=>'page','status'=>'published','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
    if(omurga_render_theme_omg('page.omg', omurga_tpl_page_vars(['post'=>$page,'page'=>$page]))) exit;
    include omurga_theme_file('page.php','static.php');
    exit;
}
if($slug==='robots.txt'){ header('Content-Type: text/plain; charset=utf-8'); echo robots_txt_content(); exit; }

if($slug==='feed.xml' || $slug==='rss.xml'){
    if(setting('seo_feed_enabled','1')!=='1'){ http_response_code(404); exit; }
    $limit=(int)setting('seo_feed_limit','50');
    omurga_render_rss_feed(omurga_feed_posts(null,$limit,false), setting('site_name','Omurga'), omurga_url($slug), setting('site_description','Son yayınlar'));
}
if($slug==='atom.xml') {
    if(setting('seo_atom_enabled','1')!=='1'){ http_response_code(404); exit; }
    $limit=(int)setting('seo_feed_limit','50');
    omurga_render_atom_feed(omurga_feed_posts(null,$limit,false), setting('site_name','Omurga'), omurga_url('atom.xml'), setting('site_description','Son yayınlar'));
}
if($slug==='google-news.xml'){
    if(setting('seo_google_news_feed_enabled','1')!=='1'){ http_response_code(404); exit; }
    $limit=(int)setting('seo_google_news_feed_limit','100');
    omurga_render_google_news_rss(omurga_feed_posts(null,$limit,true));
}
$key=trim(setting('seo_indexnow_key',''));
if($key!=='' && $slug===$key.'.txt'){
    header('Content-Type: text/plain; charset=utf-8'); echo $key; exit;
}
if(function_exists('omurga_seo_apply_redirect') && omurga_seo_apply_redirect($slug)) exit;

if(in_array($slug, ['sitemap.xml','sitemap-posts.xml','sitemap-pages.xml','sitemap-categories.xml','sitemap-tags.xml','sitemap-images.xml','sitemap-authors.xml','news-sitemap.xml'], true)){
    if(setting('seo_sitemap_enabled','1')!=='1' && $slug!=='news-sitemap.xml'){ http_response_code(404); exit; }
    if($slug==='news-sitemap.xml' && setting('seo_news_sitemap_enabled','1')!=='1'){ http_response_code(404); exit; }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "
";
    if($slug==='sitemap.xml'){
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
        foreach(['sitemap-posts.xml','sitemap-pages.xml','sitemap-categories.xml','sitemap-tags.xml','sitemap-images.xml','sitemap-authors.xml','news-sitemap.xml'] as $map){
            if($map==='news-sitemap.xml' && setting('seo_news_sitemap_enabled','1')!=='1') continue;
            if($map==='sitemap-tags.xml' && setting('seo_sitemap_tags_enabled','1')!=='1') continue;
            if($map==='sitemap-images.xml' && setting('seo_sitemap_images_enabled','1')!=='1') continue;
            if($map==='sitemap-authors.xml' && setting('seo_sitemap_authors_enabled','1')!=='1') continue;
            echo '<sitemap><loc>'.e(omurga_url($map)).'</loc></sitemap>' . "
";
        }
        echo '</sitemapindex>'; exit;
    }
    if($slug==='sitemap-categories.xml'){
        $cats=db()->query("SELECT slug, created_at FROM $catsT ORDER BY id ASC")->fetchAll();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
        echo '<url><loc>'.e(omurga_url()).'</loc><priority>1.0</priority></url>' . "
";
        foreach($cats as $c){ echo '<url><loc>'.e(omurga_url('kategori/'.$c['slug'])).'</loc><priority>0.7</priority></url>' . "
"; }
        echo '</urlset>'; exit;
    }
    if($slug==='sitemap-tags.xml'){
        if(setting('seo_sitemap_tags_enabled','1')!=='1'){ http_response_code(404); exit; }
        $tagsT=table_name('tags');
        try{ $tags=db()->query("SELECT slug FROM $tagsT ORDER BY id ASC LIMIT 1000")->fetchAll(); }catch(Throwable $e){ $tags=[]; }
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
        foreach($tags as $t){ echo '<url><loc>'.e(omurga_url('etiket/'.$t['slug'])).'</loc><priority>0.5</priority></url>' . "
"; }
        echo '</urlset>'; exit;
    }
    if($slug==='sitemap-authors.xml'){
        if(setting('seo_sitemap_authors_enabled','1')!=='1'){ http_response_code(404); exit; }
        $usersT=table_name('users');
        try{ $authors=db()->query("SELECT DISTINCT u.id,u.name,COALESCE(u.username, CONCAT('yazar-',u.id)) username FROM $usersT u INNER JOIN $postsT p ON p.author_id=u.id WHERE p.status='published' ORDER BY u.id ASC LIMIT 1000")->fetchAll(); }catch(Throwable $e){ $authors=[]; }
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
        foreach($authors as $a){ echo '<url><loc>'.e(omurga_url('yazar/'.($a['username'] ?: $a['id']))).'</loc><priority>0.5</priority></url>' . "
"; }
        echo '</urlset>'; exit;
    }
    if($slug==='sitemap-images.xml'){
        if(setting('seo_sitemap_images_enabled','1')!=='1'){ http_response_code(404); exit; }
        $posts=db()->query("SELECT p.slug,p.title,p.featured_image,p.updated_at,p.created_at FROM $postsT p WHERE p.status='published' AND p.type<>'page' AND p.featured_image<>'' ORDER BY p.id DESC LIMIT 2000")->fetchAll();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "
";
        foreach($posts as $p){ $date=substr($p['updated_at'] ?: $p['created_at'],0,10); echo '<url><loc>'.e(post_url($p)).'</loc><lastmod>'.e($date).'</lastmod><image:image><image:loc>'.e(image_url($p['featured_image'])).'</image:loc><image:title>'.e($p['title']).'</image:title></image:image></url>' . "
"; }
        echo '</urlset>'; exit;
    }
    if($slug==='news-sitemap.xml'){
        $posts=db()->query("SELECT p.slug,p.title,p.published_at,p.created_at FROM $postsT p WHERE p.status='published' AND p.type IN ('news','post') AND p.published_at >= DATE_SUB(NOW(), INTERVAL 2 DAY) ORDER BY p.published_at DESC, p.id DESC LIMIT 1000")->fetchAll();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "
";
        foreach($posts as $p){ $date=date('c', strtotime($p['published_at'] ?: $p['created_at'])); echo '<url><loc>'.e(post_url($p)).'</loc><news:news><news:publication><news:name>'.e(setting('site_name','Omurga')).'</news:name><news:language>tr</news:language></news:publication><news:publication_date>'.e($date).'</news:publication_date><news:title>'.e($p['title']).'</news:title></news:news></url>' . "
"; }
        echo '</urlset>'; exit;
    }
    $typeWhere=$slug==='sitemap-pages.xml' ? "AND p.type='page'" : "AND p.type<>'page'";
    $posts=db()->query("SELECT p.slug, p.updated_at, p.created_at FROM $postsT p WHERE p.status='published' $typeWhere ORDER BY p.id DESC LIMIT 2000")->fetchAll();
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";
    foreach($posts as $p){ $date=substr($p['updated_at'] ?: $p['created_at'],0,10); $loc=($slug==='sitemap-pages.xml') ? page_url($p) : post_url($p); echo '<url><loc>'.e($loc).'</loc><lastmod>'.e($date).'</lastmod><priority>0.8</priority></url>' . "
"; }
    echo '</urlset>'; exit;
}
if($slug==='' || $slug==='index.php'){
    $title=$siteName; $meta=$desc; $canonical=omurga_url(); $ogImage=image_url(default_social_image());
    if($st==='haber'){
                $latest=db()->query("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' ORDER BY p.published_at DESC, p.id DESC LIMIT 18")->fetchAll();
        $featured=[];
        $popular=array_slice($latest,0,6);
        $catsForHome=db()->query("SELECT c.* FROM $catsT c WHERE EXISTS (SELECT 1 FROM $postsT p WHERE p.category_id=c.id AND p.status='published' AND p.type<>'page') ORDER BY c.sort_order ASC, c.id ASC LIMIT 6")->fetchAll();
        $categorySections=[];
        foreach($catsForHome as $cat){
            $cs=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' AND p.category_id=? ORDER BY p.published_at DESC, p.id DESC LIMIT 5");
            $cs->execute([$cat['id']]);
            $items=$cs->fetchAll();
            if($items) $categorySections[]=['category'=>$cat,'posts'=>$items];
        }
        if(omurga_render_theme_omg('home.omg', omurga_tpl_page_vars(['content_tpl'=>'home','posts'=>$latest ?? [], 'latest'=>$latest ?? [], 'categorySections'=>$categorySections ?? []]))) exit; include omurga_theme_file('home.php'); exit;
    }
    if($st==='kurumsal'){
        $services=omurga_posts_by_category_slug('hizmetler', 8);
        $hero=$services[0] ?? omurga_page_by_slug('hakkimizda');
        $portfolio=omurga_posts_by_category_slug('projeler', 6);
        $pages=db()->query("SELECT p.* FROM $postsT p WHERE p.status='published' AND p.type='page' ORDER BY p.sort_order,p.id DESC LIMIT 4")->fetchAll();
        if(omurga_render_theme_omg('home.omg', omurga_tpl_page_vars(['content_tpl'=>'home','posts'=>$services ?? [], 'services'=>$services ?? [], 'portfolio'=>$portfolio ?? [], 'pages'=>$pages ?? []]))) exit; include omurga_theme_file('home-kurumsal.php','home.php'); exit;
    }
    if($st==='topluluk'){
        $announcements=omurga_posts_by_category_slug('duyurular', 6);
        $events=omurga_posts_by_category_slug('etkinlikler', 6);
        $projects=omurga_posts_by_category_slug('projeler', 6);
        $boardPage=omurga_page_by_slug('yonetim-kurulu');
        if(omurga_render_theme_omg('home.omg', omurga_tpl_page_vars(['content_tpl'=>'home','posts'=>$announcements ?? [], 'announcements'=>$announcements ?? [], 'events'=>$events ?? [], 'projects'=>$projects ?? [], 'boardPage'=>$boardPage ?? null]))) exit; include omurga_theme_file('home-topluluk.php','home.php'); exit;
    }
    $latest=db()->query("SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' ORDER BY p.published_at DESC, p.id DESC LIMIT 12")->fetchAll(); if(omurga_render_theme_omg('home.omg', omurga_tpl_page_vars(['content_tpl'=>'home','posts'=>$latest ?? [], 'latest'=>$latest ?? [], 'categorySections'=>$categorySections ?? []]))) exit; include omurga_theme_file('home.php'); exit;
}
if($slug==='search'){
    $q=trim((string)($_GET['q'] ?? ''));
    $posts=[];
    if($q!==''){
        $like='%'.$q.'%';
        $stmt=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND (p.title LIKE ? OR p.spot LIKE ? OR p.content LIKE ?) ORDER BY COALESCE(p.published_at,p.created_at) DESC,p.id DESC LIMIT 40");
        $stmt->execute([$like,$like,$like]);
        $posts=$stmt->fetchAll();
    }
    $title=om_t('blocks.search','Ara').' - '.$siteName; $meta=om_t('blocks.search_placeholder','Aranacak kelime'); $canonical=omurga_url('search');
    if(omurga_render_theme_omg('search.omg', omurga_tpl_page_vars(['query'=>$q,'posts'=>$posts]))) exit;
    include omurga_theme_file('header.php');
    echo '<section class="card single-wrap"><h1>'.e(om_t('blocks.search','Ara')).'</h1><form method="get" action="'.e(omurga_url('search')).'" class="omg-search-block"><input type="search" name="q" value="'.e($q).'" placeholder="'.e(om_t('blocks.search_placeholder','Aranacak kelime')).'"><button type="submit">'.e(om_t('blocks.search','Ara')).'</button></form>';
    if($q==='') echo '<p class="muted">'.e(om_t('blocks.search_placeholder','Aranacak kelime')).'</p>';
    elseif(!$posts) echo '<p class="omg-block-empty">'.e(om_t('blocks.no_content','Icerik bulunamadi.')).'</p>';
    else { echo '<div class="omg-content-items omg-view-list">'; foreach($posts as $item){ echo '<article class="omg-content-item"><div><small>'.e($item['category_name'] ?? '').'</small><h3><a href="'.e(post_url($item)).'">'.e($item['title'] ?? '').'</a></h3><p>'.e(excerpt(($item['spot'] ?? '') ?: ($item['content'] ?? ''), 140)).'</p></div></article>'; } echo '</div>'; }
    echo '</section>';
    include omurga_theme_file('footer.php');
    exit;
}
$base = content_url_base();
if(function_exists('str_starts_with') ? str_starts_with($slug,$base.'/') : substr($slug,0,strlen($base)+1)===$base.'/'){
    $postSlug = trim(substr($slug,strlen($base)+1),'/');
    if($postSlug !== ''){
        $statusWhere = is_admin_logged_in() ? "p.slug=?" : "p.slug=? AND p.status='published'";
        $stmt=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug, u.name author_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id LEFT JOIN ".table_name('users')." u ON u.id=p.author_id WHERE $statusWhere AND p.type<>'page' LIMIT 1");
        $stmt->execute([$postSlug]);
        $post=$stmt->fetch();
        if($post){
            $post['content']=omurga_render_post_content($post);
            $title=omurga_seo_title($post); $meta=omurga_seo_description($post); $canonical=omurga_canonical_url($post); $ogImage=image_url($post['social_image'] ?: $post['featured_image'] ?: default_social_image());
            $related=[];
            if(!empty($post['category_id'])){ $rs=db()->prepare("SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.category_id=? AND p.id<>? ORDER BY p.published_at DESC LIMIT 4"); $rs->execute([$post['category_id'],$post['id']]); $related=$rs->fetchAll(); }
            if(is_admin_logged_in() && ($post['status'] ?? '')!=='published') echo '<div style="position:fixed;z-index:99999;left:16px;bottom:16px;background:#111827;color:#fff;padding:10px 14px;border-radius:999px;font:600 13px Arial">Önizleme: '.e(omurga_public_status_label($post)).'</div>';
            if(omurga_render_theme_omg('single.omg', omurga_tpl_page_vars(['post'=>$post, 'related'=>$related]))) exit; $templateFile=omurga_post_template_file($post); include $templateFile; exit;
        }
    }
}

if((function_exists('str_starts_with') ? str_starts_with($slug,'kategori/') : substr($slug,0,9)==='kategori/') && str_ends_with($slug, '/feed.xml')){
    if(setting('seo_category_feed_enabled','1')!=='1'){ http_response_code(404); exit; }
    $catSlug=trim(substr($slug,9),'/'); $catSlug=preg_replace('#/feed\.xml$#','',$catSlug);
    $stmt=db()->prepare("SELECT * FROM $catsT WHERE slug=? LIMIT 1"); $stmt->execute([$catSlug]); $category=$stmt->fetch();
    if(!$category){ http_response_code(404); exit; }
    $limit=(int)setting('seo_feed_limit','50');
    omurga_render_rss_feed(omurga_feed_posts($catSlug,$limit,false), setting('site_name','Omurga').' - '.$category['name'], omurga_url('kategori/'.$catSlug.'/feed.xml'), ($category['description'] ?: $category['name'].' kategorisi son yayınlar'));
}

if(function_exists('str_starts_with') ? str_starts_with($slug,'kategori/') : substr($slug,0,9)==='kategori/'){
    $catSlug=trim(substr($slug,9),'/'); $stmt=db()->prepare("SELECT * FROM $catsT WHERE slug=? LIMIT 1"); $stmt->execute([$catSlug]); $category=$stmt->fetch();
    if($category){ $stmt=db()->prepare("SELECT p.*, c.name category_name FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' AND p.category_id=? ORDER BY p.published_at DESC, p.id DESC LIMIT 24"); $stmt->execute([$category['id']]); $posts=$stmt->fetchAll(); $title=$category['name'].' - '.$siteName; $meta=$category['description'] ?: $category['name'].' kategorisindeki son içerikler'; $canonical=category_url($category); if(omurga_render_theme_omg('category.omg', omurga_tpl_page_vars(['category'=>$category, 'posts'=>$posts]))) exit; include omurga_theme_file('category.php'); exit; }
}
if((function_exists('str_starts_with') ? str_starts_with($slug,'etiket/') : substr($slug,0,7)==='etiket/')){
    $tagSlug=trim(substr($slug,7),'/'); $tagsT=table_name('tags'); $ptT=table_name('post_tags');
    try{ $stmt=db()->prepare("SELECT * FROM $tagsT WHERE slug=? LIMIT 1"); $stmt->execute([$tagSlug]); $tag=$stmt->fetch(); }catch(Throwable $e){ $tag=null; }
    if($tag){ $stmt=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p INNER JOIN $ptT pt ON pt.post_id=p.id LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' AND pt.tag_id=? ORDER BY p.published_at DESC, p.id DESC LIMIT 24"); $stmt->execute([$tag['id']]); $posts=$stmt->fetchAll(); $title=$tag['name'].' - '.$siteName; $meta=$tag['name'].' etiketiyle yayınlanan son içerikler'; $canonical=omurga_url('etiket/'.$tag['slug']); if(omurga_render_theme_omg('tag.omg', omurga_tpl_page_vars(['tag'=>$tag,'posts'=>$posts]))) exit; include omurga_theme_file('category.php','home.php'); exit; }
}
if((function_exists('str_starts_with') ? str_starts_with($slug,'yazar/') : substr($slug,0,6)==='yazar/')){
    $authorSlug=trim(substr($slug,6),'/'); $usersT=table_name('users');
    $stmt=db()->prepare("SELECT * FROM $usersT WHERE username=? OR id=? LIMIT 1"); $stmt->execute([$authorSlug, ctype_digit($authorSlug)?(int)$authorSlug:0]); $author=$stmt->fetch();
    if($author){ $stmt=db()->prepare("SELECT p.*, c.name category_name, c.slug category_slug FROM $postsT p LEFT JOIN $catsT c ON c.id=p.category_id WHERE p.status='published' AND p.type<>'page' AND p.author_id=? ORDER BY p.published_at DESC, p.id DESC LIMIT 24"); $stmt->execute([(int)$author['id']]); $posts=$stmt->fetchAll(); $title=($author['name'] ?? 'Yazar').' - '.$siteName; $meta=($author['name'] ?? 'Yazar').' tarafından yayınlanan içerikler'; $canonical=omurga_url('yazar/'.($author['username'] ?: $author['id'])); if(omurga_render_theme_omg('author.omg', omurga_tpl_page_vars(['author'=>$author,'posts'=>$posts]))) exit; include omurga_theme_file('category.php','home.php'); exit; }
}
// Sabit sayfalar kökten çalışır: /hakkimizda, /iletisim
$stmt=db()->prepare("SELECT p.*, u.name author_name FROM $postsT p LEFT JOIN ".table_name('users')." u ON u.id=p.author_id WHERE p.slug=? AND p.type='page' AND ".(is_admin_logged_in()?"1=1":"p.status='published'")." LIMIT 1");
$stmt->execute([$slug]); $page=$stmt->fetch();
if($page){
    $page['content']=omurga_render_post_content($page);
    $title=omurga_seo_title($page); $meta=omurga_seo_description($page); $canonical=page_url($page); $ogImage=image_url($page['social_image'] ?: $page['featured_image'] ?: default_social_image());
    if(is_admin_logged_in() && ($page['status'] ?? '')!=='published') echo '<div style="position:fixed;z-index:99999;left:16px;bottom:16px;background:#111827;color:#fff;padding:10px 14px;border-radius:999px;font:600 13px Arial">Sabit sayfa önizleme: '.e(omurga_public_status_label($page)).'</div>';
    $post=$page;
    if(omurga_render_theme_omg('page.omg', omurga_tpl_page_vars(['post'=>$page, 'page'=>$page]))) exit; include omurga_theme_file('page.php','static.php'); exit;
}
if(function_exists('omurga_seo_log_404')) omurga_seo_log_404($slug);
render_error_page(404, 'Sayfa Bulunamadı', 'Aradığınız sayfa bulunamadı.');
