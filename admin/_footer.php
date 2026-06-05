</div></main></div><script>
(function(){
  var body=document.body;
  document.addEventListener('click',function(e){
    if(window.matchMedia('(max-width:900px)').matches && body.classList.contains('nav-open')&&!e.target.closest('.sidebar, .dle-sidebar')&&!e.target.closest('.menu-toggle')) body.classList.remove('nav-open');
  });
  document.querySelectorAll('.sidebar a, .dle-sidebar a').forEach(function(a){a.addEventListener('click',function(){ if(window.matchMedia('(max-width:900px)').matches){body.classList.remove('nav-open');} });});
})();

(function(){
  function widthValue(block){
    var sel = block.querySelector('select[name$="[width]"]');
    var v = sel ? String(sel.value || '100') : '100';
    return ['100','75','70','67','66','50','33','30','25'].indexOf(v) >= 0 ? v : '100';
  }
  function widthInt(v){ return parseInt(v,10) || 100; }
  function applyWidthClass(block, v){
    block.className = block.className.replace(/\blayout-preview-(100|75|70|67|66|50|33|30|25)\b/g,'').replace(/\s+/g,' ').trim();
    block.classList.add('layout-preview-' + v);
    var pill = block.querySelector('.width-pill');
    if(pill) pill.textContent = '%' + v;
  }
  function makeRow(index, sum){
    var row = document.createElement('div');
    row.className = 'layout-row ' + (sum >= 100 ? 'is-full' : 'is-partial');
    var title = document.createElement('div');
    title.className = 'layout-row-title';
    title.innerHTML = '<span>Satır ' + index + '</span><span class="row-status">Toplam %' + sum + ' · Kalan %' + Math.max(0,100-sum) + '</span>';
    row.appendChild(title);
    return row;
  }
  function refreshRegion(region){
    if(!region) return;
    var blocks = Array.prototype.slice.call(region.querySelectorAll('.layout-row .layout-block, .layout-region > .layout-block'));
    if(!blocks.length) return;
    var rows = Array.prototype.slice.call(region.querySelectorAll('.layout-row'));
    rows.forEach(function(r){ r.parentNode.removeChild(r); });
    var rowIndex = 1, sum = 0, current = makeRow(rowIndex, 0);
    blocks.forEach(function(block){
      var v = widthValue(block), w = widthInt(v);
      applyWidthClass(block, v);
      if(current.children.length > 1 && (sum + w) > 100){
        current.className = 'layout-row ' + (sum >= 100 ? 'is-full' : 'is-partial');
        current.querySelector('.row-status').textContent = 'Toplam %' + sum + ' · Kalan %' + Math.max(0,100-sum);
        region.appendChild(current);
        rowIndex++; sum = 0; current = makeRow(rowIndex, 0);
      }
      current.appendChild(block);
      sum += w;
      block.classList.add('row-moved');
      setTimeout(function(){ block.classList.remove('row-moved'); }, 450);
    });
    current.className = 'layout-row ' + (sum >= 100 ? 'is-full' : 'is-partial');
    current.querySelector('.row-status').textContent = 'Toplam %' + sum + ' · Kalan %' + Math.max(0,100-sum);
    region.appendChild(current);
    var note = region.querySelector('.layout-region-live-note');
    if(!note){
      note = document.createElement('div');
      note.className = 'layout-region-live-note';
      note.textContent = 'Genişlik değiştirince bloklar aynı anda canlı olarak satıra yerleşir. Toplam %100’ü geçerse yeni satıra geçer.';
      region.appendChild(note);
    }
  }
  function refreshAll(){ document.querySelectorAll('.layout-region').forEach(refreshRegion); }
  document.addEventListener('change', function(e){
    if(e.target && e.target.matches('select[name$="[width]"]')) refreshRegion(e.target.closest('.layout-region'));
  });
  document.addEventListener('DOMContentLoaded', refreshAll);
  if(document.readyState !== 'loading') refreshAll();
})();

</script></body></html>