/* bo_repaslist_init.js */

/* Curseur */
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
})();

/* Recherche (hors CRUD — filtre affichage uniquement) */
function applySearch() {
    const q   = document.getElementById('sq').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.trow');
    let vis = 0;
    rows.forEach(r => {
        const show = !q || r.dataset.nom.includes(q);
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    document.getElementById('noResult').style.display = vis === 0 ? 'block' : 'none';
    document.getElementById('rowCount').textContent = vis + ' repas';
}