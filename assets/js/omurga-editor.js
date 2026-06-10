(function(){
  const source = document.getElementById('contentEditor');
  const editor = document.getElementById('omVisualEditor');
  const htmlBox = document.getElementById('omHtmlEditorBox');
  const htmlEditor = document.getElementById('omHtmlEditor');
  const modal = document.getElementById('omMediaModal');
  const wordCount = document.getElementById('wordCount');
  let mediaTargetInput = null;
  let mediaMode = 'editor';
  let galleryTargetTextarea = null;
  let mediaPage = 1;
  function mediaEndpoint(){ return modal ? (modal.dataset.endpoint || 'media-picker-api.php') : 'media-picker-api.php'; }
  function mediaUploadEndpoint(){ return modal ? (modal.dataset.uploadEndpoint || 'media-upload-api.php') : 'media-upload-api.php'; }

  function esc(s){ return String(s||'').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
  function normalizeImageSrc(src){
    src = String(src||'').trim();
    if(!src) return '';
    if(/^https?:\/\//i.test(src) || src.startsWith('/') || src.startsWith('../')) return src;
    return '../' + src.replace(/^\/+/, '');
  }
  function normalizeStoragePath(src){
    src = String(src||'').trim();
    if(!src) return '';
    if(src.startsWith('../')) src = src.replace(/^\.\.\//,'');
    return src.replace(/^\/+/, '');
  }
  function countWords(html){
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    const txt = (tmp.textContent || '').trim();
    return txt ? txt.split(/\s+/).length : 0;
  }
  function updateWordCount(){ if(wordCount) wordCount.textContent = countWords(editor ? editor.innerHTML : (source ? source.value : '')); }
  window.omurgaEditorRefreshWordCount = updateWordCount;
  function sync(){
    if(!source) return;
    if(htmlBox && htmlBox.style.display !== 'none' && htmlEditor) source.value = htmlEditor.value;
    else if(editor) source.value = editor.innerHTML;
  }
  function focusEditor(){ if(editor) editor.focus(); }
  function exec(cmd, value=null){ focusEditor(); document.execCommand(cmd, false, value); sync(); updateWordCount(); }
  function insertHtml(html){ focusEditor(); document.execCommand('insertHTML', false, html); sync(); updateWordCount(); }

  function openMedia(mode='editor', targetInput=null){
    mediaMode = mode || 'editor';
    mediaTargetInput = targetInput || null;
    galleryTargetTextarea = (mediaMode === 'gallery') ? targetInput : null;
    if(modal){
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden','false');
      modal.dataset.mode = mediaMode;
      mediaPage = 1;
      loadMedia(false);
    } else {
      const url = prompt(mediaMode === 'gallery' ? 'Görsel URL/yolu veya URL listesi:' : 'Görsel URL veya yolu:', '');
      if(!url) return;
      if(mediaMode === 'gallery' && galleryTargetTextarea) appendGalleryImages(url);
      else if(mediaMode === 'input' && mediaTargetInput) setInputImage(url);
      else insertImage(url, '');
    }
  }
  window.omurgaOpenMedia = openMedia;
  function closeMedia(){
    if(modal){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); }
    mediaTargetInput = null;
    galleryTargetTextarea = null;
    mediaMode = 'editor';
  }
  function insertImage(src, alt){
    src = normalizeImageSrc(src); if(!src) return;
    insertHtml('<figure class="om-content-image"><img src="'+esc(src)+'" alt="'+esc(alt||'')+'"><figcaption></figcaption></figure><p><br></p>');
    closeMedia();
  }
  function setInputImage(src){
    if(!mediaTargetInput) return;
    const clean = normalizeStoragePath(src);
    mediaTargetInput.value = clean;
    mediaTargetInput.dispatchEvent(new Event('input', {bubbles:true}));
    const previewSel = mediaTargetInput.dataset.preview;
    if(previewSel){
      const img = document.querySelector(previewSel);
      if(img){ img.src = normalizeImageSrc(clean); img.style.display='block'; }
    }
    closeMedia();
  }
  function galleryItemsFromText(text){
    return String(text||'').split(/[\r\n,]+/).map(v=>v.trim()).filter(Boolean);
  }
  function renderGalleryPreview(){
    document.querySelectorAll('[data-gallery-preview]').forEach(box=>{
      const selector = box.dataset.galleryPreview;
      const ta = selector ? document.querySelector(selector) : null;
      if(!ta) return;
      const items = galleryItemsFromText(ta.value);
      if(!items.length){ box.innerHTML = '<span class="gallery-preview-empty">Galeriye henüz görsel eklenmedi.</span>'; return; }
      box.innerHTML = items.map(src => '<figure><img src="'+esc(normalizeImageSrc(src))+'" alt=""><figcaption>'+esc(normalizeStoragePath(src))+'</figcaption></figure>').join('');
    });
  }
  function appendGalleryImages(values){
    if(!galleryTargetTextarea) return;
    const existing = galleryItemsFromText(galleryTargetTextarea.value);
    const add = Array.isArray(values) ? values : galleryItemsFromText(values);
    add.forEach(v=>{
      const clean = normalizeStoragePath(v);
      if(clean && !existing.includes(clean)) existing.push(clean);
    });
    galleryTargetTextarea.value = existing.join('\n');
    galleryTargetTextarea.dispatchEvent(new Event('input', {bubbles:true}));
    renderGalleryPreview();
  }
  function chooseMedia(src, alt){
    if(mediaMode === 'gallery' && galleryTargetTextarea){ appendGalleryImages([src]); return; }
    if(mediaMode === 'input' && mediaTargetInput) return setInputImage(src);
    return insertImage(src, alt);
  }

  if(source && editor){
    editor.innerHTML = source.value || '';
    updateWordCount();
    editor.addEventListener('input', ()=>{ sync(); updateWordCount(); });
    editor.addEventListener('paste', (e)=>{
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
      sync(); updateWordCount();
    });

    document.querySelectorAll('.om-editor-toolbar button').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const cmd=btn.dataset.cmd, fmt=btn.dataset.format, action=btn.dataset.action;
        if(cmd) return exec(cmd);
        if(fmt) return exec('formatBlock', fmt);
        if(action==='quote') return insertHtml('<blockquote>Alıntı metni...</blockquote><p><br></p>');
        if(action==='link'){
          const url=prompt('Link adresi:','https://');
          if(url) exec('createLink', url);
          return;
        }
        if(action==='media') return openMedia('editor');
        if(action==='video'){
          const url=prompt('YouTube/video bağlantısı:','');
          if(url) insertHtml('<p>[video url="'+esc(url)+'"]</p>');
          return;
        }
        if(action==='ad') return insertHtml('<p>[reklam]</p>');
        if(action==='gallery') return insertHtml('<p>[galeri]</p>');
        if(action==='readmore') return insertHtml('<p><!--more--></p>');
        if(action==='html'){
          if(!htmlBox || !htmlEditor) return;
          const showing = htmlBox.style.display !== 'none';
          if(showing){ editor.innerHTML = htmlEditor.value; htmlBox.style.display='none'; editor.style.display='block'; sync(); updateWordCount(); }
          else { sync(); htmlEditor.value = source.value; htmlBox.style.display='block'; editor.style.display='none'; htmlEditor.focus(); }
        }
      });
    });

    const form = source.closest('form');
    if(form) form.addEventListener('submit', sync);
  }

  function bindMediaItems(root){
    (root || document).querySelectorAll('.om-media-item').forEach(item=>{
      if(item.dataset.bound==='1') return;
      item.dataset.bound='1';
      item.addEventListener('click', ()=> chooseMedia(item.dataset.src, item.dataset.alt));
    });
  }
  function renderMediaItems(items, append){
    const grid = document.getElementById('omMediaGrid');
    if(!grid) return;
    if(!append) grid.innerHTML = '';
    if(!items || !items.length){ if(!append) grid.innerHTML='<div class="om-media-empty">Sonuç bulunamadı.</div>'; return; }
    items.forEach(m=>{
      const btn=document.createElement('button');
      btn.type='button'; btn.className='om-media-item';
      btn.dataset.id=m.id||''; btn.dataset.src=m.src||''; btn.dataset.thumb=m.thumb||''; btn.dataset.alt=m.alt||m.name||'';
      btn.innerHTML='<img src="'+esc(m.thumb||normalizeImageSrc(m.src||''))+'" alt="'+esc(m.alt||m.name||'')+'"><span>'+esc(m.name||m.src||'')+'</span>';
      grid.appendChild(btn);
    });
    bindMediaItems(grid);
  }
  function loadMedia(append){
    if(!modal || !window.fetch) return;
    const q=document.getElementById('omMediaSearch');
    const t=document.getElementById('omMediaType');
    const url=mediaEndpoint()+'?page='+encodeURIComponent(mediaPage)+'&q='+encodeURIComponent(q?q.value:'')+'&type='+encodeURIComponent(t?t.value:'');
    fetch(url,{credentials:'same-origin'}).then(async r=>{
      const text=await r.text();
      let data=null;
      try{ data=JSON.parse(text); }catch(e){ throw new Error((text||'Medya listesi alınamadı.').slice(0,180)); }
      if(!r.ok || !data || !data.ok) throw new Error((data && data.message) || 'Medya listesi alınamadı.');
      renderMediaItems(data.items, append);
    }).catch(err=>{
      const grid=document.getElementById('omMediaGrid');
      if(grid && !append) grid.innerHTML='<div class="om-media-empty">'+esc(err.message || 'Medya listesi alınamadı.')+'</div>';
    });
  }
  function uploadMediaFiles(files){
    if(!files || !files.length || !window.fetch) return;
    const status=document.getElementById('omMediaUploadStatus');
    const alt=document.getElementById('omMediaUploadAlt');
    const webp=document.getElementById('omMediaUploadWebp');
    const csrf=modal ? (modal.querySelector('input[name="_csrf"]') || modal.querySelector('input[name="csrf_token"]')) : null;
    const fd=new FormData();
    Array.from(files).forEach(file=>fd.append('files[]', file));
    if(alt) fd.append('alt_text', alt.value || '');
    if(webp && webp.checked) fd.append('make_webp', '1');
    if(csrf) fd.append(csrf.name, csrf.value);
    if(status) status.textContent='Yükleniyor...';
    fetch(mediaUploadEndpoint(), {method:'POST', body:fd, credentials:'same-origin'}).then(async r=>{
      const text=await r.text();
      let data=null;
      try{ data=JSON.parse(text); }catch(e){ throw new Error((text||'Yükleme başarısız.').slice(0,180)); }
      if(!r.ok && data && data.message) throw new Error(data.message);
      return data;
    }).then(data=>{
      if(!data || !data.ok) throw new Error((data && data.message) || 'Yükleme başarısız.');
      renderMediaItems(data.items || [], false);
      if(status) status.textContent=data.message || 'Yüklendi.';
      if(data.items && data.items.length){
        const first=data.items[0];
        if(mediaMode === 'gallery' && galleryTargetTextarea) appendGalleryImages(data.items.map(x=>x.src));
        else chooseMedia(first.src, first.alt || first.name || '');
      }
    }).catch(err=>{ if(status) status.textContent=err.message || 'Yükleme başarısız.'; });
  }
  bindMediaItems(document);
  document.querySelectorAll('[data-om-media-target]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const selector = btn.dataset.omMediaTarget;
      const input = selector ? document.querySelector(selector) : null;
      openMedia('input', input);
    });
  });
  document.querySelectorAll('[data-om-media-gallery]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const selector = btn.dataset.omMediaGallery;
      const textarea = selector ? document.querySelector(selector) : null;
      openMedia('gallery', textarea);
    });
  });

  const mediaQuickUpload = document.getElementById('omMediaQuickUpload');
  if(mediaQuickUpload) mediaQuickUpload.addEventListener('change', function(){ uploadMediaFiles(this.files); this.value=''; });
  const mediaSearch = document.getElementById('omMediaSearch');
  const mediaType = document.getElementById('omMediaType');
  const mediaMore = document.getElementById('omMediaLoadMore');
  let mediaSearchTimer=null;
  function reloadMedia(){ mediaPage=1; loadMedia(false); }
  if(mediaSearch) mediaSearch.addEventListener('input', ()=>{ clearTimeout(mediaSearchTimer); mediaSearchTimer=setTimeout(reloadMedia, 250); });
  if(mediaType) mediaType.addEventListener('change', reloadMedia);
  if(mediaMore) mediaMore.addEventListener('click', ()=>{ mediaPage += 1; loadMedia(true); });

  const closeBtn = document.querySelector('.om-media-close');
  if(closeBtn) closeBtn.addEventListener('click', closeMedia);
  if(modal) modal.addEventListener('click', e=>{ if(e.target===modal) closeMedia(); });
  const urlBtn = document.getElementById('omInsertUrlImage');
  const urlInput = document.getElementById('omImageUrl');
  if(urlBtn && urlInput) urlBtn.addEventListener('click', ()=>{
    if(mediaMode === 'gallery' && galleryTargetTextarea){ appendGalleryImages(urlInput.value); urlInput.value=''; return; }
    if(mediaMode === 'input' && mediaTargetInput) setInputImage(urlInput.value);
    else insertImage(urlInput.value, '');
  });
  const addGalleryUrls = document.getElementById('omAddGalleryUrls');
  const galleryInput = document.getElementById('galleryImagesInput');
  if(addGalleryUrls && galleryInput) addGalleryUrls.addEventListener('click', ()=>{
    const urls = prompt('Birden fazla görsel URL/yolu ekle. Her satıra bir tane yazabilirsin:', '');
    if(urls){ galleryTargetTextarea = galleryInput; appendGalleryImages(urls); galleryTargetTextarea = null; }
  });
  const clearGalleryPreview = document.getElementById('omClearGalleryPreview');
  if(clearGalleryPreview) clearGalleryPreview.addEventListener('click', renderGalleryPreview);
  if(galleryInput){ galleryInput.addEventListener('input', renderGalleryPreview); renderGalleryPreview(); }
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeMedia(); });
})();

(function(){
  const card = document.querySelector('.editor-card');
  const classic = document.querySelector('.om-editor-wrap[data-om-editor]');
  const typeInput = document.getElementById('editorTypeInput');
  const blocksInput = document.getElementById('contentBlocksInput');
  if(!card || !classic || !typeInput || !blocksInput) return;

  function esc(s){ return String(s||'').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
  function cleanType(type){ return ['text','image','quote','video'].includes(type) ? type : 'text'; }
  function readBlocks(){
    try {
      const data = JSON.parse(blocksInput.value || '[]');
      return Array.isArray(data) && data.length ? data : [{type:'text', value:'', caption:''}];
    } catch(e) {
      return [{type:'text', value:'', caption:''}];
    }
  }

  function normalizeMediaSrc(src){
    src = String(src||'').trim();
    if(!src) return '';
    if(src.startsWith('../')) src = src.replace(/^\.\.\//,'');
    if(/^https?:\/\//i.test(src) || src.startsWith('/')) return src;
    return '../' + src.replace(/^\/+/, '');
  }
  function blockTextToHtml(text){
    return String(text||'').split(/\n{2,}/).map(v=>v.trim()).filter(Boolean).map(p=>'<p>'+esc(p).replace(/\n/g,'<br>')+'</p>').join('');
  }
  function blocksToHtml(blocks){
    return (blocks||[]).map(block => {
      const type = cleanType(block.type || 'text');
      const value = String(block.value || '').trim();
      const caption = String(block.caption || '').trim();
      if(!value && !caption) return '';
      if(type === 'text') return blockTextToHtml(value);
      if(type === 'image'){
        if(!value) return '';
        const figcap = caption ? '<figcaption>'+esc(caption)+'</figcaption>' : '';
        return '<figure class="om-block-image"><img src="'+esc(normalizeMediaSrc(value))+'" alt="'+esc(caption)+'">'+figcap+'</figure>';
      }
      if(type === 'quote') return '<blockquote class="om-block-quote">'+esc(value).replace(/\n/g,'<br>')+(caption ? '<cite>'+esc(caption)+'</cite>' : '')+'</blockquote>';
      if(type === 'video') return value ? '<p>[video url="'+esc(value)+'"]</p>' : '';
      return '';
    }).join('');
  }
  function htmlToText(html){
    const tmp=document.createElement('div');
    tmp.innerHTML=html || '';
    tmp.querySelectorAll('br').forEach(br=>br.replaceWith('\n'));
    tmp.querySelectorAll('p,div,blockquote,figure,h1,h2,h3,h4,h5,h6,li').forEach(el=>{ el.appendChild(document.createTextNode('\n\n')); });
    return (tmp.textContent || '').replace(/\n{3,}/g,'\n\n').trim();
  }
  function htmlToBlocks(html){
    const tmp=document.createElement('div');
    tmp.innerHTML=html || '';
    const out=[];
    Array.from(tmp.childNodes).forEach(node=>{
      if(node.nodeType===3){ const t=node.textContent.trim(); if(t) out.push({type:'text', value:t, caption:''}); return; }
      if(node.nodeType!==1) return;
      const tag=node.tagName.toLowerCase();
      if(tag==='figure'){
        const img=node.querySelector('img');
        if(img){ out.push({type:'image', value:(img.getAttribute('src')||'').replace(/^\.\.\//,''), caption:(img.getAttribute('alt') || (node.querySelector('figcaption')?node.querySelector('figcaption').textContent:'') || '').trim()}); return; }
      }
      if(tag==='img'){ out.push({type:'image', value:(node.getAttribute('src')||'').replace(/^\.\.\//,''), caption:(node.getAttribute('alt')||'').trim()}); return; }
      if(tag==='blockquote'){ const cite=node.querySelector('cite'); const cap=cite?cite.textContent.trim():''; if(cite) cite.remove(); out.push({type:'quote', value:htmlToText(node.innerHTML), caption:cap}); return; }
      const text=htmlToText(node.outerHTML); if(text) out.push({type:'text', value:text, caption:''});
    });
    if(!out.length){ const text=htmlToText(html); if(text) out.push({type:'text', value:text, caption:''}); }
    return out.length ? out : [{type:'text', value:'', caption:''}];
  }
  function syncBlocksToClassic(){
    serialize();
    const source=document.getElementById('contentEditor');
    const visual=document.getElementById('omVisualEditor');
    const htmlEd=document.getElementById('omHtmlEditor');
    const html=blocksToHtml(readBlocks());
    if(source) source.value=html;
    if(visual) visual.innerHTML=html;
    if(htmlEd) htmlEd.value=html;
    if(typeof window.omurgaEditorRefreshWordCount === 'function') window.omurgaEditorRefreshWordCount();
  }
  function syncClassicToBlocks(){
    const source=document.getElementById('contentEditor');
    const visual=document.getElementById('omVisualEditor');
    const htmlEd=document.getElementById('omHtmlEditor');
    let html = source ? source.value : '';
    const htmlBox=document.getElementById('omHtmlEditorBox');
    if(htmlBox && htmlBox.style.display !== 'none' && htmlEd) html = htmlEd.value;
    else if(visual) html = visual.innerHTML;
    blocksInput.value = JSON.stringify(htmlToBlocks(html));
    render(readBlocks());
  }

  const tabs = document.createElement('div');
  tabs.className = 'om-editor-mode-tabs';
  tabs.setAttribute('role','tablist');
  tabs.setAttribute('aria-label','Editör tipi');
  tabs.innerHTML = '<button type="button" data-editor-mode="blocks">Blok Editör</button><button type="button" data-editor-mode="classic">Klasik Editör</button>';

  const panel = document.createElement('div');
  panel.className = 'om-block-editor-panel';
  panel.setAttribute('data-block-editor','');
  panel.innerHTML = '<div class="om-editor-top"><strong>Blok Editör</strong><span>Metin, görsel, alıntı ve video bloklarını sıralayarak içerik oluştur.</span></div><div id="omBlockEditor" class="om-block-editor-list"></div><div class="om-block-add-zone"><button type="button" data-add-block="text">+ Metin</button><button type="button" data-add-block="image">+ Görsel</button><button type="button" data-add-block="quote">+ Alıntı</button><button type="button" data-add-block="video">+ Video</button></div><div class="om-editor-status"><span>Bloklar yukarı/aşağı taşınır, silinir ve sırasıyla kaydedilir.</span></div>';
  classic.parentNode.insertBefore(tabs, classic);
  classic.parentNode.insertBefore(panel, classic);

  const list = panel.querySelector('#omBlockEditor');

  function blockTemplate(block){
    const type = cleanType(block.type || 'text');
    const value = esc(block.value || '');
    const caption = esc(block.caption || '');
    let field = '';
    if(type === 'text') field = '<label><span>Metin</span><textarea rows="5" data-block-value placeholder="Yazmaya başlayın...">'+value+'</textarea></label>';
    if(type === 'image') field = '<label><span>Görsel</span><div class="om-block-image-field"><input data-block-value value="'+value+'" placeholder="Bilgisayardan yükle veya medyadan seç"><button type="button" class="btn light" data-block-media>Görsel Seç</button></div></label><label><span>Alt metin / başlık</span><input data-block-caption value="'+caption+'" placeholder="Görsel açıklaması"></label>';
    if(type === 'quote') field = '<label><span>Alıntı</span><textarea rows="3" data-block-value placeholder="Vurgulamak istediğiniz sözü yazın...">'+value+'</textarea></label><label><span>Kaynak</span><input data-block-caption value="'+caption+'" placeholder="Opsiyonel kaynak"></label>';
    if(type === 'video') field = '<label><span>Video bağlantısı</span><input data-block-value value="'+value+'" placeholder="YouTube, Vimeo veya MP4 URL"></label>';
    const label = {text:'Metin', image:'Görsel', quote:'Alıntı', video:'Video'}[type] || 'Blok';
    return '<div class="om-block-editor-item" data-type="'+type+'"><div class="om-block-head"><strong>'+label+'</strong><div class="om-block-controls"><button type="button" data-block-up>↑</button><button type="button" data-block-down>↓</button><button type="button" data-block-remove>Sil</button></div></div>'+field+'</div>';
  }

  function render(blocks){
    list.innerHTML = (blocks && blocks.length ? blocks : [{type:'text', value:'', caption:''}]).map(blockTemplate).join('');
  }

  function serialize(){
    const rows = [];
    list.querySelectorAll('.om-block-editor-item').forEach(item => {
      const type = cleanType(item.dataset.type || 'text');
      const valueEl = item.querySelector('[data-block-value]');
      const captionEl = item.querySelector('[data-block-caption]');
      const value = valueEl ? valueEl.value.trim() : '';
      const caption = captionEl ? captionEl.value.trim() : '';
      if(value || caption) rows.push({type, value, caption});
    });
    blocksInput.value = JSON.stringify(rows.length ? rows : [{type:'text', value:'', caption:''}]);
  }

  function setMode(mode){
    mode = mode === 'classic' ? 'classic' : 'blocks';
    const previous = typeInput.value === 'classic' ? 'classic' : 'blocks';
    if(previous !== mode){
      if(mode === 'classic') syncBlocksToClassic();
      else syncClassicToBlocks();
    }
    typeInput.value = mode;
    card.classList.toggle('editor-mode-blocks', mode === 'blocks');
    card.classList.toggle('editor-mode-classic', mode === 'classic');
    tabs.querySelectorAll('[data-editor-mode]').forEach(btn => btn.classList.toggle('active', btn.dataset.editorMode === mode));
  }

  tabs.addEventListener('click', e => {
    const btn = e.target.closest('[data-editor-mode]');
    if(btn) setMode(btn.dataset.editorMode);
  });
  panel.addEventListener('click', e => {
    const add = e.target.closest('[data-add-block]');
    if(add){ serialize(); const data = readBlocks(); data.push({type:cleanType(add.dataset.addBlock), value:'', caption:''}); render(data); return; }
    const item = e.target.closest('.om-block-editor-item');
    if(!item) return;
    if(e.target.closest('[data-block-media]')){
      const input = item.querySelector('[data-block-value]');
      if(window.omurgaOpenMedia && input) window.omurgaOpenMedia('input', input);
      return;
    }
    if(e.target.closest('[data-block-remove]')){ item.remove(); if(!list.children.length) render([{type:'text', value:'', caption:''}]); serialize(); return; }
    if(e.target.closest('[data-block-up]') && item.previousElementSibling){ list.insertBefore(item, item.previousElementSibling); serialize(); return; }
    if(e.target.closest('[data-block-down]') && item.nextElementSibling){ list.insertBefore(item.nextElementSibling, item); serialize(); }
  });
  panel.addEventListener('input', serialize);
  const form = card.closest('form');
  if(form) form.addEventListener('submit', () => {
    if(typeInput.value === 'blocks') { serialize(); syncBlocksToClassic(); typeInput.value = 'blocks'; }
    else { syncClassicToBlocks(); typeInput.value = 'classic'; }
  });

  render(readBlocks());
  setMode(typeInput.value || 'blocks');
})();
