document.addEventListener('click', function(e){
  var t=e.target.closest('.dt-mobile-toggle');
  if(t){ var m=document.querySelector('.dt-menu-wrap'); if(m) m.classList.toggle('is-open'); }
});
