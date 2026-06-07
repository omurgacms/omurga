<?php
$omPostEditBufferLevel = ob_get_level();
ob_start();
require '_layout.php';
verify_csrf();
require_cap('posts.view');
$postsT = table_name('posts');
$catsT = table_name('categories');
$id = (int)($_GET['id'] ?? 0);
$incomingType = defined('OMURGA_PAGE_EDITOR') ? 'page' : ($_GET['type'] ?? primary_content_type());
$post = [
  'title'=>'','slug'=>'','spot'=>'','content'=>'','type'=>$incomingType,'status'=>'draft','category_id'=>'',
  'featured_image'=>'','video_url'=>'','gallery_images'=>'','social_image'=>'','sort_order'=>100,
  'seo_title'=>'','meta_description'=>'','focus_keyword'=>'','social_title'=>'','social_description'=>'','canonical_url'=>'','seo_noindex'=>0,'comments_enabled'=>om_default_comments_enabled()?1:0,'design_template'=>'default','published_at'=>'','editor_type'=>'','content_blocks'=>''
];
if($id){
  $stmt=db()->prepare("SELECT * FROM $postsT WHERE id=?"); $stmt->execute([$id]); $post=array_merge($post, $stmt->fetch() ?: []);
  if(defined('OMURGA_PAGE_EDITOR') && ($post['type'] ?? '') !== 'page'){ render_error_page(400,'Yanlış İçerik Türü','Bu ekran sadece sayfalar içindir.'); }
  if(current_user_role()==='author' && (int)($post['author_id']??0)!==(int)($_SESSION['omurga_user_id']??0)){ render_error_page(403,'Yetkisiz Erişim','Bu içeriği düzenleme yetkiniz yok.'); }
}
$tagLine = $id ? implode(', ', tag_names_for_post($id)) : '';
$autosaveDraftKey = preg_replace('/[^a-zA-Z0-9_\-]/','', (string)($_POST['autosave_draft_key'] ?? ($_GET['draft_key'] ?? '')));
if($autosaveDraftKey === ''){ $autosaveDraftKey = bin2hex(random_bytes(8)); }
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $title = trim($_POST['title'] ?? '');
    if($title==='') throw new RuntimeException('Başlık alanı boş bırakılamaz.');
    $slug = omurga_unique_slug(slugify(trim($_POST['slug'] ?? '') ?: $title), $id);
    $isStaticPageSave = defined('OMURGA_PAGE_EDITOR') || (($post['type'] ?? '') === 'page');
    $featured = trim($_POST['featured_image'] ?? '');
    $social = '';
    if(($up=save_uploaded_file('featured_upload'))) { $featured=$up; insert_media_record($up, $title, $_SESSION['omurga_user_id']??null); }
    $status=omurga_normalize_post_status($_POST['status'] ?? 'draft');
    $type = $isStaticPageSave ? 'page' : (($post['type'] ?? '') && ($post['type'] ?? '') !== 'page' ? $post['type'] : primary_content_type());
    $publishedInput = trim($_POST['published_at'] ?? '');
    $designTemplate = preg_replace('/[^a-z0-9_\-]/','', strtolower(trim($_POST['design_template'] ?? 'default'))) ?: 'default';
    $videoUrl = trim($_POST['video_url'] ?? '');
    $galleryImages = omurga_normalize_gallery_input($_POST['gallery_images'] ?? '');
    $editorType = omurga_normalize_editor_type($_POST['editor_type'] ?? ($id ? ($post['editor_type'] ?? 'classic') : 'blocks'));
    $contentBlocks = [];
    $contentBlocksJson = '';
    $contentHtml = (string)($_POST['content'] ?? '');
    if($editorType==='blocks'){
      $contentBlocks = omurga_decode_content_blocks($_POST['content_blocks'] ?? '[]');
      $contentBlocksJson = omurga_content_blocks_to_json($contentBlocks);
      $contentHtml = omurga_content_blocks_to_html($contentBlocks);
    }
    $isStaticPageSave = ($type === 'page');
    $categoryIds = $isStaticPageSave ? [] : (array)($_POST['category_ids'] ?? []);
    $categoryId = $isStaticPageSave ? null : (array_values(array_filter(array_map('intval',$categoryIds)))[0] ?? null);
    $publishedAt = null;
    if($status==='published') $publishedAt = $publishedInput ? date('Y-m-d H:i:s', strtotime($publishedInput)) : (($post['published_at']??'') ?: date('Y-m-d H:i:s'));
    $canonicalUrl = trim($_POST['canonical_url'] ?? '');
    $seoNoindex = !empty($_POST['seo_noindex']) ? 1 : 0;
    $commentsEnabled = $isStaticPageSave ? 0 : (!empty($_POST['comments_enabled']) ? 1 : 0);
    $newPostForRevision=[
      'title'=>$title,'slug'=>$slug,'spot'=>trim($_POST['spot']??''),'content'=>$contentHtml,'editor_type'=>$editorType,'content_blocks'=>$contentBlocksJson,'type'=>$type,'status'=>$status,'category_id'=>$categoryId,'featured_image'=>$featured,'video_url'=>$videoUrl,'gallery_images'=>$galleryImages,'social_image'=>$social,'sort_order'=>(int)($_POST['sort_order']??100),'seo_title'=>trim($_POST['seo_title']??''),'meta_description'=>trim($_POST['meta_description']??''),'focus_keyword'=>trim($_POST['focus_keyword']??''),'social_title'=>trim($_POST['social_title']??''),'social_description'=>trim($_POST['social_description']??''),'canonical_url'=>$canonicalUrl,'seo_noindex'=>$seoNoindex,'comments_enabled'=>$commentsEnabled,'design_template'=>$designTemplate,'published_at'=>$publishedAt
    ];
    omurga_do_action('omurga_before_post_save', $id, $newPostForRevision);
    $data=[$title,$slug,trim($_POST['spot']??''),$contentHtml, $editorType, $contentBlocksJson, $type, $status, $categoryId, $featured, $videoUrl, $galleryImages, $social, (int)($_POST['sort_order']??100), trim($_POST['seo_title']??''), trim($_POST['meta_description']??''), trim($_POST['focus_keyword']??''), trim($_POST['social_title']??''), trim($_POST['social_description']??''), $canonicalUrl, $seoNoindex, $commentsEnabled, $designTemplate, $publishedAt];
    if($id){
      omurga_create_post_revision($id, $post, $newPostForRevision, 'update');
      $sql="UPDATE $postsT SET title=?,slug=?,spot=?,content=?,editor_type=?,content_blocks=?,type=?,status=?,category_id=?,featured_image=?,video_url=?,gallery_images=?,social_image=?,sort_order=?,seo_title=?,meta_description=?,focus_keyword=?,social_title=?,social_description=?,canonical_url=?,seo_noindex=?,comments_enabled=?,design_template=?,published_at=? WHERE id=?";
      $data[]=$id; db()->prepare($sql)->execute($data); omurga_sync_post_categories($id, $isStaticPageSave ? [] : $categoryIds); sync_post_tags($id, $isStaticPageSave ? '' : ($_POST['tags'] ?? '')); if(!$isStaticPageSave) omurga_save_post_meta_values($id, $_POST['block_meta'] ?? []); omurga_save_custom_field_values($id, $type, array_merge($post,['category_id'=>$categoryId]), $_POST['custom_fields'] ?? []); if(!$isStaticPageSave){ omurga_set_post_meta($id, '_omurga_sidebar_enabled', isset($_POST['sidebar_enabled'])?'1':'0'); omurga_set_post_meta($id, '_omurga_sidebar_region', $_POST['sidebar_region']??'sidebar'); } else { omurga_set_post_meta($id, '_omurga_sidebar_enabled', isset($_POST['sidebar_enabled'])?'1':'0'); omurga_set_post_meta($id, '_omurga_sidebar_region', $_POST['sidebar_region']??'sidebar'); } omurga_do_action('omurga_after_post_save', $id, $newPostForRevision); if($status==='published') omurga_do_action('omurga_after_post_publish', $id, $newPostForRevision); log_activity('post.update','İçerik güncellendi: '.$title); omurga_delete_autosave($id, $autosaveDraftKey); echo '<div class="alert success">İçerik başarıyla güncellendi.</div>';
    } else {
      $sql="INSERT INTO $postsT (title,slug,spot,content,editor_type,content_blocks,type,status,category_id,featured_image,video_url,gallery_images,social_image,sort_order,seo_title,meta_description,focus_keyword,social_title,social_description,canonical_url,seo_noindex,comments_enabled,design_template,published_at,author_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      $data[]=$_SESSION['omurga_user_id']??null; db()->prepare($sql)->execute($data); $id=(int)db()->lastInsertId(); omurga_sync_post_categories($id, $isStaticPageSave ? [] : $categoryIds); sync_post_tags($id, $isStaticPageSave ? '' : ($_POST['tags'] ?? '')); if(!$isStaticPageSave) omurga_save_post_meta_values($id, $_POST['block_meta'] ?? []); omurga_save_custom_field_values($id, $type, array_merge($post,['category_id'=>$categoryId]), $_POST['custom_fields'] ?? []); omurga_set_post_meta($id, '_omurga_sidebar_enabled', isset($_POST['sidebar_enabled'])?'1':'0'); omurga_set_post_meta($id, '_omurga_sidebar_region', $_POST['sidebar_region']??'sidebar'); omurga_do_action('omurga_after_post_save', $id, $newPostForRevision); if($status==='published') omurga_do_action('omurga_after_post_publish', $id, $newPostForRevision); log_activity('post.create','İçerik eklendi: '.$title); omurga_delete_autosave(0, $autosaveDraftKey); while(ob_get_level() > $omPostEditBufferLevel){ ob_end_clean(); } redirect('admin/'.($isStaticPageSave ? 'page-edit.php' : 'post-edit.php').'?id='.$id.'&saved=1');
    }
    $stmt=db()->prepare("SELECT * FROM $postsT WHERE id=?"); $stmt->execute([$id]); $post=array_merge($post, $stmt->fetch() ?: []); $tagLine=implode(', ', tag_names_for_post($id));
  } catch(Throwable $e){ echo '<div class="alert error">'.e($e->getMessage()).'</div>'; }
}
if(!empty($_GET['saved'])) echo '<div class="alert success">İçerik başarıyla kaydedildi.</div>';
$cats=db()->query("SELECT * FROM $catsT ORDER BY sort_order,name")->fetchAll();
$selectedCategoryIds = $id ? omurga_post_category_ids($id, (int)($post['category_id'] ?? 0)) : [];
$sidebarRegions = omurga_theme_sidebar_regions();
$currentSidebarRegion = $id ? omurga_post_sidebar_region($post, array_key_first($sidebarRegions) ?: 'sidebar') : (array_key_first($sidebarRegions) ?: 'sidebar');
$currentSidebarEnabled = $id ? omurga_post_sidebar_enabled($post) : true;
$mediaItems=[];
try{ $mediaItems=db()->query("SELECT id,file_path,file_name,alt_text,width,height FROM ".table_name('media')." ORDER BY created_at DESC LIMIT 80")->fetchAll(); }catch(Throwable $e){ $mediaItems=[]; }
$labels=type_labels();
$isStaticPage = (($post['type'] ?? '') === 'page');
if(($post['type'] ?? '') !== 'page') unset($labels['page']);
$currentLabel=type_label($post['type']);
if(!$isStaticPage && ($currentLabel==='İçerik' || $currentLabel==='Haber')) $currentLabel = 'Yazı';
$editorType = $id ? omurga_normalize_editor_type($post['editor_type'] ?? 'classic') : 'blocks';
$editorBlocks = omurga_decode_content_blocks($post['content_blocks'] ?? '');
if($editorType==='blocks' && !$editorBlocks) $editorBlocks=omurga_default_content_blocks((string)($post['content'] ?? ''));
$editorBlocksJson = omurga_content_blocks_to_json($editorBlocks);
$templateOptions = omurga_templates_for_type($post['type'] ?: primary_content_type());
$currentTemplate = $post['design_template'] ?: 'default';
$wordCount = str_word_count(strip_tags((string)$post['content']));
$previewUrl = $id ? post_url($post) : '';
$blockMetaDefs = omurga_block_post_meta_definitions();
$blockMetaValues = $id ? omurga_get_post_meta_values($id) : [];
$galleryText = omurga_gallery_to_text($post['gallery_images'] ?? '');
$latestAutosave = omurga_get_autosave((int)$id, $autosaveDraftKey);
$autosavePayload = [];
if($latestAutosave && !empty($latestAutosave['payload'])){ $autosavePayload = json_decode((string)$latestAutosave['payload'], true) ?: []; }
$autosavePayloadB64 = $autosavePayload ? base64_encode(json_encode($autosavePayload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) : '';
$autosaveInterval = max(15, min(300, (int)setting('autosave_interval_seconds','30')));
?>
<div class="toolbar editor-head">
  <div><h1><?= $isStaticPage ? ($id?'Sayfa Düzenle':'Yeni Sayfa') : ((!$id && ($post['type']??'')==='news')?'Yazı Ekle':($id?e($currentLabel).' Düzenle':'Yeni '.e($currentLabel))) ?></h1><p><?= $isStaticPage ? 'Sabit sayfa: kategori ve etiket kullanılmaz, menüden veya doğrudan linkle ulaşılır.' : 'Sade içerik girişi. Vitrin/tasarım alanları çekirdekte değildir; gerekirse tema, blok veya paket alanlarından gelir.' ?></p></div>
  <div class="toolbar-actions"><button type="button" class="btn light" id="omFocusModeBtn">Odaklanma Modu</button><button type="button" class="btn light" id="omEditorPreviewBtn">Editör Önizleme</button><a class="btn light" href="<?= $isStaticPage ? 'pages.php' : 'posts.php?type='.e($post['type']) ?>">Listeye Dön</a><?php if($id): ?><a class="btn dark" target="_blank" href="<?=e($previewUrl)?>">Site Önizleme</a><?php endif; ?></div>
</div>
<form method="post" enctype="multipart/form-data" class="editor-layout" id="omContentEditForm"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="post_id" value="<?=e($id)?>"><input type="hidden" name="autosave_draft_key" value="<?=e($autosaveDraftKey)?>">
  <div class="om-focus-savebar"><strong>Temiz Yazım Modu</strong><button type="button" class="btn light" id="omFocusExitBtn">Normal Moda Dön</button><button class="btn primary"><?=can('posts.publish')?'Kaydet / Yayınla':'İncelemeye Gönder'?></button></div>
  <div class="autosave-strip" style="display:flex;gap:10px;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;margin-bottom:12px">
    <div><strong>Otomatik kayıt</strong> <small id="omAutosaveStatus">Her <?=e($autosaveInterval)?> saniyede taslak saklanır.</small></div>
    <?php if($autosavePayloadB64): ?><button type="button" class="btn light" id="omRestoreAutosaveBtn" data-payload="<?=e($autosavePayloadB64)?>">Son otomatik kaydı geri yükle</button><?php endif; ?>
  </div>
  <section class="editor-maincol">
    <div class="card editor-card editor-mode-<?=e($editorType)?>">
      <input type="hidden" name="type" value="<?=e($isStaticPage ? 'page' : ($post['type'] ?: primary_content_type()))?>">
      <input type="hidden" id="editorTypeInput" name="editor_type" value="<?=e($editorType)?>">
      <textarea id="contentBlocksInput" name="content_blocks" hidden><?=e($editorBlocksJson)?></textarea>
      <label class="om-title-row">Başlık<input id="titleInput" class="title-input" name="title" required value="<?=e($post['title'])?>" placeholder="<?= $isStaticPage ? 'Sayfa başlığını yaz...' : 'Yazı başlığını yaz...' ?>"></label>
      <div class="om-edit-meta-row">
        <label>Kalıcı bağlantı / Slug<input id="slugInput" name="slug" value="<?=e($post['slug'])?>" placeholder="Boş bırakılırsa başlıktan oluşur"><small><?= $isStaticPage ? 'Sayfa adresi kökten çalışır: /sayfa-adi' : 'Yazı adresi içerik tabanıyla çalışır: /yazi/yazi-adi' ?></small></label>
        <label>Spot / Kısa Açıklama<textarea name="spot" class="spot-input" maxlength="320" placeholder="Kısa özet yaz..."><?=e(trim((string)$post['spot']))?></textarea></label>
      </div>
      <div class="om-editor-wrap dle-square-editor" data-om-editor><div class="om-editor-top"><strong>İçerik</strong><span>Metin, görsel ve bloklarla içerik oluştur</span></div><div class="om-editor-toolbar" aria-label="Omurga editör araçları"><button type="button" data-cmd="bold"><b>B</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-format="h2">Ara Başlık</button><button type="button" data-format="h3">Alt Başlık</button><button type="button" data-cmd="insertUnorderedList">Liste</button><button type="button" data-cmd="insertOrderedList">Numaralı</button><button type="button" data-action="quote">Alıntı</button><button type="button" data-action="link">Link</button><button type="button" data-action="media">Görsel</button><button type="button" data-action="video">Video</button><button type="button" data-action="ad">Reklam</button><button type="button" data-action="gallery">Galeri</button><button type="button" data-action="readmore">Devamını Oku</button><button type="button" data-action="html">HTML</button></div><textarea id="contentEditor" name="content" class="content-editor om-editor-source" hidden><?=e($post['content'])?></textarea><div id="omVisualEditor" class="om-visual-editor" contenteditable="true" data-placeholder="İçerik metnini yaz..."></div><div id="omHtmlEditorBox" class="om-html-editor-box" style="display:none"><textarea id="omHtmlEditor" spellcheck="false"></textarea></div><div class="om-editor-status"><span>Kelime: <b id="wordCount"><?=$wordCount?></b></span><span>SEO açıklaması: <b id="metaCount"><?=mb_strlen((string)$post['meta_description'],'UTF-8')?></b>/160</span><span>Kaydetmeden önce içerik otomatik senkronize edilir.</span></div></div>
    </div>
    <div class="card seo-card"><h2>Video ve Galeri</h2><label>Video URL<input name="video_url" value="<?=e($post['video_url'] ?? '')?>" placeholder="YouTube, Vimeo veya MP4 bağlantısı"></label><label>Galeri Görselleri<textarea id="galleryImagesInput" name="gallery_images" placeholder="Her satıra bir görsel yolu veya URL yaz. Birden fazla URL için alt alta yazabilirsin."><?=e($galleryText)?></textarea><small>Video ve galeri Omurga çekirdeğinde standart içerik alanıdır.</small></label><div class="gallery-picker-actions"><button type="button" class="btn light" data-om-media-gallery="#galleryImagesInput">Medyadan seç</button><button type="button" class="btn light" id="omAddGalleryUrls">Birden fazla URL ekle</button><button type="button" class="btn light" id="omClearGalleryPreview">Önizlemeyi yenile</button></div><div id="galleryPreview" class="gallery-preview" data-gallery-preview="#galleryImagesInput"></div><small>Galeri için medyadan birden fazla görsel seçebilir veya URL/yol listesini alt alta girebilirsin.</small></div>
    <div class="card seo-card"><h2>SEO</h2><div class="mini-grid two"><label>SEO Başlığı<input name="seo_title" value="<?=e($post['seo_title'])?>" placeholder="Boşsa başlık kullanılır"></label><label>Odak Kelime<input name="focus_keyword" value="<?=e($post['focus_keyword'])?>"></label></div><label>Meta Açıklama<input id="metaDescription" name="meta_description" value="<?=e($post['meta_description'])?>" maxlength="255" placeholder="Google açıklaması"></label><div class="seo-preview"><b id="seoPreviewTitle"><?=e($post['seo_title'] ?: $post['title'] ?: 'Başlık önizlemesi')?></b><small><?=e($previewUrl ?: omurga_url('ornek-yazi'))?></small><p id="seoPreviewDesc"><?=e($post['meta_description'] ?: $post['spot'] ?: 'Meta açıklaması burada görünecek.')?></p></div><div class="mini-grid two"><label>Sosyal Medya Başlığı<input name="social_title" value="<?=e($post['social_title'])?>"></label><label>Sosyal Medya Açıklaması<input name="social_description" value="<?=e($post['social_description'])?>" maxlength="255"></label></div><div class="mini-grid two"><label>Canonical URL<input name="canonical_url" value="<?=e($post['canonical_url'] ?? '')?>" placeholder="Boşsa kendi bağlantısı kullanılır"></label><label class="check-line" style="align-self:end"><input type="checkbox" name="seo_noindex" value="1" <?=!empty($post['seo_noindex'])?'checked':''?>> Google’da gösterme (noindex)</label></div><small>Not: Bu kutu işaretlenirse içerik noindex olur. Normal içeriklerde boş bırak.</small></div>
    <?=omurga_render_admin_boxes('post-edit', ['post'=>$post,'id'=>$id])?>
  </section>
  <aside class="editor-sidecol">
    <div class="side-box publish-box"><h3>Yayın</h3><label>Durum<select name="status"><?php foreach(omurga_status_options_for_current_user($post['status'] ?? 'draft') as $sk=>$sv): ?><option value="<?=e($sk)?>" <?=($post['status']??'draft')===$sk?'selected':''?>><?=e($sv)?></option><?php endforeach; ?></select></label><label>Yayın Tarihi<input type="datetime-local" name="published_at" value="<?=e(omurga_datetime_local($post['published_at']??''))?>"></label><label>Sıralama<input type="number" name="sort_order" value="<?=e($post['sort_order'])?>"></label><button class="btn primary save-main" style="width:100%;justify-content:center"><?=can('posts.publish')?'Kaydet / Yayınla':'İncelemeye Gönder'?></button><button type="button" class="btn light" id="omEditorPreviewSideBtn" style="width:100%;justify-content:center;margin-top:8px">Editör Önizleme</button><?php if($id): ?><a class="btn light" style="width:100%;justify-content:center;margin-top:8px" target="_blank" href="<?=e($previewUrl)?>">Site Önizleme</a><?php endif; ?></div>
    <?php if($id): $revCount=count(omurga_recent_revisions($id, 50)); ?><div class="side-box"><h3>Revizyonlar</h3><p style="margin:0 0 10px;color:#64748b">Bu içerik için <?=e($revCount)?> kayıtlı revizyon var.</p><a class="btn light" style="width:100%;justify-content:center" href="revisions.php?post_id=<?=e($id)?>">Revizyonları Gör</a></div><?php endif; ?>
    <div class="side-box"><h3>Tasarım Şablonu</h3><label>Şablon<select name="design_template"><?php foreach($templateOptions as $tk=>$tpl): ?><option value="<?=e($tk)?>" <?=$currentTemplate===$tk?'selected':''?>><?=e($tpl['name'] ?? $tk)?><?=empty($tpl['exists'])?' (dosya yok)':''?></option><?php endforeach; ?></select></label><small>Şablonlar aktif temadan gelir.</small><a class="btn light" style="width:100%;justify-content:center;margin-top:8px" href="templates.php">Şablonları Gör</a></div>
    <?php if(!$isStaticPage): ?><div class="side-box"><h3>Yorumlar</h3><label class="check-line"><input type="checkbox" name="comments_enabled" value="1" <?=om_post_comments_enabled($post)?'checked':''?>> <?=e(om_t('comments.allow_comments','Yorumlara izin ver'))?></label><small><?=e(om_t('comments.waiting_approval','Yeni yorumlar onay bekler.'))?></small></div><?php endif; ?>
    <div class="side-box"><h3>Sidebar</h3><?php if(count($sidebarRegions)>1): ?><label>Sidebar seç<select name="sidebar_region"><?php foreach($sidebarRegions as $rk=>$rl): ?><option value="<?=e($rk)?>" <?=$currentSidebarRegion===$rk?'selected':''?>><?=e($rl)?></option><?php endforeach; ?></select></label><label class="check-line"><input type="checkbox" name="sidebar_enabled" value="1" <?=$currentSidebarEnabled?'checked':''?>> Bu içerikte sidebar göster</label><small>Temada birden fazla sidebar varsa buradan hangi yan alanın kullanılacağı seçilir.</small><?php else: ?><input type="hidden" name="sidebar_region" value="<?=e($currentSidebarRegion)?>"><label class="check-line"><input type="checkbox" name="sidebar_enabled" value="1" <?=$currentSidebarEnabled?'checked':''?>> Sidebar göster</label><small>Bu temada tek sidebar olduğu için sadece göster/gizle seçeneği gösterilir.</small><?php endif; ?></div>
    <?php if(!$isStaticPage): ?><div class="side-box"><h3>Kategoriler ve Etiketler</h3><label>Kategoriler<select name="category_ids[]" multiple size="7"><?php foreach($cats as $c): ?><option value="<?=$c['id']?>" <?=in_array((int)$c['id'], $selectedCategoryIds, true)?'selected':''?>><?=e($c['name'])?></option><?php endforeach; ?></select><small>Birden fazla kategori seçmek için basılı tutarak seçebilirsin. İlk seçilen kategori uyumluluk için ana kategori sayılır.</small></label><label>Etiketler<input name="tags" value="<?=e($tagLine)?>" placeholder="gundem, etkinlik, duyuru"><small>Virgül ile ayır.</small></label></div><?php else: ?><div class="side-box static-page-note"><h3>Sayfa</h3><p>Sayfalar kategori, etiket, yorum ve dinamik içerik akışlarından bağımsızdır. Hakkımızda, İletişim, KVKK gibi sabit içerikler için kullanılır; menüden veya doğrudan linkle ulaşılır.</p></div><?php endif; ?>
    <?=omurga_render_custom_fields_admin($isStaticPage ? 'page' : 'post', $post)?>
    <div class="side-box image-side"><h3>Öne Çıkan Görsel</h3>
      <label>Görsel yükle<input type="file" name="featured_upload" accept="image/*"></label>
      <div class="image-picker-row"><input id="featuredImageInput" name="featured_image" value="<?=e($post['featured_image'])?>" placeholder="uploads/ornek.webp" data-preview="#featuredImagePreview"><button type="button" class="btn light" data-om-media-target="#featuredImageInput">Medyadan seç</button></div>
      <img id="featuredImagePreview" class="thumb-preview" src="<?=e($post['featured_image'] ? image_url($post['featured_image']) : '')?>" style="<?=$post['featured_image']?'':'display:none'?>">
      <small>Sosyal medya görseli ayrı alan olarak kaldırıldı. Paylaşım görseli gerekiyorsa SEO sistemi öne çıkan görseli veya varsayılan OG görselini kullanır.</small>
    </div>
    <?php if(!$isStaticPage && $blockMetaDefs): ?><div class="side-box block-meta-box"><h3>Tema / Blok Alanları</h3><small>Tema, blok veya paket özel alan tanımlarsa burada görünür.</small><?php foreach($blockMetaDefs as $mk=>$mf): $type=$mf['type'] ?? 'text'; $val=$blockMetaValues[$mk] ?? ($mf['default'] ?? ($type==='checkbox'?'0':'')); ?><?php if($type==='checkbox'): ?><label class="check-line"><input type="checkbox" name="block_meta[<?=e($mk)?>]" value="1" <?=!empty($val)&&$val!=='0'?'checked':''?>> <?=e($mf['label'] ?? $mk)?></label><small><?=e($mf['block_name'] ?? '')?></small><?php elseif($type==='select'): ?><label><?=e($mf['label'] ?? $mk)?><select name="block_meta[<?=e($mk)?>]"><?php foreach(($mf['options'] ?? []) as $ok=>$ol): ?><option value="<?=e($ok)?>" <?=(string)$val===(string)$ok?'selected':''?>><?=e($ol)?></option><?php endforeach; ?></select><small><?=e($mf['block_name'] ?? '')?></small></label><?php elseif($type==='number'): ?><label><?=e($mf['label'] ?? $mk)?><input type="number" name="block_meta[<?=e($mk)?>]" value="<?=e($val)?>"><small><?=e($mf['block_name'] ?? '')?></small></label><?php else: ?><label><?=e($mf['label'] ?? $mk)?><input name="block_meta[<?=e($mk)?>]" value="<?=e($val)?>"><small><?=e($mf['block_name'] ?? '')?></small></label><?php endif; ?><?php endforeach; ?></div><?php endif; ?>
  </aside>
</form>


<div class="om-preview-modal" id="omEditorPreviewModal" aria-hidden="true">
  <div class="om-preview-backdrop" data-close-preview></div>
  <div class="om-preview-panel" role="dialog" aria-modal="true" aria-labelledby="omPreviewTitleLabel">
    <div class="om-preview-head">
      <div>
        <small>Kaydetmeden önce görünüm</small>
        <h2 id="omPreviewTitleLabel">Editör Önizlemesi</h2>
      </div>
      <button type="button" class="btn light" data-close-preview>Kapat</button>
    </div>
    <div class="om-preview-body">
      <img id="omPreviewImage" class="om-preview-image" alt="Öne çıkan görsel önizlemesi" style="display:none">
      <h1 id="omPreviewTitle">Başlık önizlemesi</h1>
      <p id="omPreviewSpot" class="om-preview-spot"></p>
      <div id="omPreviewContent" class="om-preview-content"></div>
    </div>
  </div>
</div>
<style>
.om-preview-modal{position:fixed;inset:0;z-index:9999;display:none}.om-preview-modal.open{display:block}.om-preview-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px)}.om-preview-panel{position:relative;margin:5vh auto;background:#fff;border-radius:20px;box-shadow:0 24px 80px rgba(15,23,42,.25);width:min(940px,calc(100vw - 28px));max-height:90vh;overflow:auto}.om-preview-head{position:sticky;top:0;background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;z-index:1}.om-preview-head h2{margin:2px 0 0;font-size:20px}.om-preview-head small{color:#64748b;font-weight:700}.om-preview-body{padding:22px;max-width:820px;margin:0 auto}.om-preview-image{width:100%;max-height:360px;object-fit:cover;border-radius:16px;margin-bottom:18px;background:#f1f5f9}.om-preview-body h1{font-size:clamp(28px,4vw,44px);line-height:1.1;margin:0 0 12px;color:#0f172a}.om-preview-spot{font-size:18px;color:#64748b;margin:0 0 20px}.om-preview-content{font-size:17px;line-height:1.75;color:#1f2937}.om-preview-content img{max-width:100%;height:auto;border-radius:12px}.om-preview-content iframe{max-width:100%}@media(max-width:640px){.om-preview-panel{margin:0;width:100%;height:100%;max-height:none;border-radius:0}.om-preview-body{padding:18px}.om-preview-head{padding:12px 14px}}
</style>

<?php require __DIR__.'/_media_picker.php'; ?>
<script src="../assets/js/omurga-editor.js?v=1.0.8-beta"></script>
<script>(function(){const title=document.getElementById('titleInput'), slug=document.getElementById('slugInput'), meta=document.getElementById('metaDescription'), metaCount=document.getElementById('metaCount'), seoTitle=document.getElementById('seoPreviewTitle'), seoDesc=document.getElementById('seoPreviewDesc'); const tr={'ş':'s','Ş':'s','ı':'i','İ':'i','ğ':'g','Ğ':'g','ü':'u','Ü':'u','ö':'o','Ö':'o','ç':'c','Ç':'c'}; function slugify(s){return (s||'').replace(/[şŞıİğĞüÜöÖçÇ]/g,m=>tr[m]||m).toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'')} if(title&&slug){title.addEventListener('input',()=>{ if(!slug.dataset.touched && !slug.value) slug.placeholder=slugify(title.value)||'otomatik-olusturulur'; if(seoTitle) seoTitle.textContent=title.value||'Başlık önizlemesi';}); slug.addEventListener('input',()=>slug.dataset.touched='1');} if(meta){meta.addEventListener('input',()=>{ if(metaCount) metaCount.textContent=meta.value.length; if(seoDesc) seoDesc.textContent=meta.value||'Meta açıklaması burada görünecek.';});}})();</script>


<script>(function(){
  var form=document.getElementById('omContentEditForm');
  var modal=document.getElementById('omEditorPreviewModal');
  if(!form||!modal) return;
  function syncEditor(){
    try{
      var visual=document.getElementById('omVisualEditor');
      var source=document.getElementById('contentEditor');
      if(visual&&source) source.value=visual.innerHTML;
      if(window.OmurgaEditor && typeof window.OmurgaEditor.sync==='function') window.OmurgaEditor.sync();
    }catch(e){}
  }
  function val(sel){ var el=form.querySelector(sel); return el ? (el.value||'') : ''; }
  function imageUrl(path){
    path=(path||'').trim();
    if(!path) return '';
    if(/^https?:\/\//i.test(path) || path.indexOf('../')===0 || path.indexOf('/')===0) return path;
    return '../'+path.replace(/^\/+/, '');
  }
  function openPreview(){
    syncEditor();
    var title=val('[name="title"]').trim() || 'Başlık önizlemesi';
    var spot=val('[name="spot"]').trim();
    var content=val('[name="content"]');
    var img=val('[name="featured_image"]');
    modal.querySelector('#omPreviewTitle').textContent=title;
    var spotEl=modal.querySelector('#omPreviewSpot');
    spotEl.textContent=spot;
    spotEl.style.display=spot?'block':'none';
    var imgEl=modal.querySelector('#omPreviewImage');
    if(img){ imgEl.src=imageUrl(img); imgEl.style.display='block'; } else { imgEl.removeAttribute('src'); imgEl.style.display='none'; }
    modal.querySelector('#omPreviewContent').innerHTML=content || '<p style="color:#94a3b8">Henüz içerik yazılmadı.</p>';
    modal.classList.add('open');
    modal.setAttribute('aria-hidden','false');
  }
  function closePreview(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
  document.getElementById('omEditorPreviewBtn')?.addEventListener('click',openPreview);
  document.getElementById('omEditorPreviewSideBtn')?.addEventListener('click',openPreview);
  modal.querySelectorAll('[data-close-preview]').forEach(function(el){el.addEventListener('click',closePreview);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open')) closePreview();});
})();</script>

<script>(function(){
  var form=document.getElementById('omContentEditForm');
  if(!form) return;
  var status=document.getElementById('omAutosaveStatus');
  var interval=<?=$autosaveInterval?>*1000;
  var busy=false, changed=false, lastPayload='';
  function syncVisualEditor(){
    try{
      var visual=document.getElementById('omVisualEditor');
      var source=document.getElementById('contentEditor');
      if(visual&&source) source.value=visual.innerHTML;
      if(window.OmurgaEditor && typeof window.OmurgaEditor.sync==='function') window.OmurgaEditor.sync();
    }catch(e){}
  }
  function serialize(){
    syncVisualEditor();
    var fd=new FormData(form);
    fd.delete('featured_upload');
    return fd;
  }
  function markChanged(){ changed=true; }
  form.addEventListener('input', markChanged, true);
  form.addEventListener('change', markChanged, true);
  function autosave(force){
    if(busy || (!force && !changed)) return;
    var fd=serialize();
    fd.append('post_id', form.querySelector('[name="post_id"]')?.value || '0');
    var compare=[];
    fd.forEach(function(v,k){ if(typeof v==='string') compare.push(k+'='+v); });
    var payload=compare.join('&');
    if(!force && payload===lastPayload) return;
    busy=true;
    if(status) status.textContent='Otomatik kaydediliyor...';
    fetch('autosave-api.php',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(j){
        if(j.ok){ changed=false; lastPayload=payload; if(status) status.textContent='Son otomatik kayıt: '+(j.time||'şimdi'); }
        else if(status){ status.textContent='Otomatik kayıt başarısız: '+(j.message||'hata'); }
      })
      .catch(function(){ if(status) status.textContent='Otomatik kayıt bağlantı hatası verdi.'; })
      .finally(function(){ busy=false; });
  }
  setInterval(function(){autosave(false);}, interval);
  window.addEventListener('beforeunload', function(){ autosave(true); });
  var restore=document.getElementById('omRestoreAutosaveBtn');
  if(restore){ restore.addEventListener('click', function(){
    try{
      var data=JSON.parse(atob(this.getAttribute('data-payload')||''));
      Object.keys(data).forEach(function(k){
        var els=form.querySelectorAll('[name="'+CSS.escape(k)+'"]');
        if(!els.length) return;
        els.forEach(function(el){
          if(el.type==='checkbox') el.checked=!!data[k] && data[k]!=='0';
          else if(el.type==='radio') el.checked=(el.value==data[k]);
          else el.value=Array.isArray(data[k]) ? data[k].join(',') : data[k];
        });
      });
      var visual=document.getElementById('omVisualEditor'), source=document.getElementById('contentEditor');
      if(visual && source) visual.innerHTML=source.value||'';
      changed=true;
      if(status) status.textContent='Otomatik kayıt geri yüklendi. Kalıcı olması için Kaydet’e bas.';
    }catch(e){ if(status) status.textContent='Otomatik kayıt geri yüklenemedi.'; }
  }); }
})();</script>

<script>(function(){var key='omurga_focus_mode';var open=document.getElementById('omFocusModeBtn');var close=document.getElementById('omFocusExitBtn');function setFocus(on){document.body.classList.toggle('om-focus-mode',!!on);try{localStorage.setItem(key,on?'1':'0');}catch(e){}}if(open)open.addEventListener('click',function(){setFocus(!document.body.classList.contains('om-focus-mode'));});if(close)close.addEventListener('click',function(){setFocus(false);});try{if(localStorage.getItem(key)==='1')setFocus(true);}catch(e){}})();</script>
<?php require '_footer.php'; ?>
