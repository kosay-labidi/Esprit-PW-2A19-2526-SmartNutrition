/* fo_alimentlist.js — Filtrage, recherche, vues */

let cv = 'grid'; /* vue courante */

/* Bascule grille ↔ liste */
function setView(v) {
    cv = v;
    document.getElementById('vg').classList.toggle('hide', v === 'list');
    document.getElementById('vl').classList.toggle('show', v === 'list');
    document.getElementById('bg').classList.toggle('on', v === 'grid');
    document.getElementById('bl').classList.toggle('on', v === 'list');
    applyFilters(); /* recalcule le compteur */
}

/* Active le bouton de filtre cliqué */
function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}


function applyFilters() {
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    let vg = 0, vl = 0;

    /* Filtre les cartes (vue grille) */
    document.querySelectorAll('#vg .card').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vg++;
    });

    /* Filtre les lignes (vue liste) */
    document.querySelectorAll('#lbody .lrow').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vl++;
    });

    document.getElementById('emptyG').style.display = vg === 0 ? 'block' : 'none';
    document.getElementById('emptyL').style.display = vl === 0 ? 'block' : 'none';
    const rc = document.getElementById('rc');
    if (rc) rc.textContent = (cv==='grid' ? vg : vl) + ' aliment(s)';
}