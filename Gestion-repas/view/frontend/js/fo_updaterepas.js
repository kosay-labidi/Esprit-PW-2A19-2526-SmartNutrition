/* fo_updaterepas.js */

/* Données des aliments actuellement sélectionnés (depuis PHP) */
const uSelected = {
<?php foreach ($alimActuels as $al): ?>
    <?= $al['id_aliment'] ?>: '<?= addslashes($al['nom']) ?>',
<?php endforeach; ?>
};

function uToggle(id, nom) {
    const item = document.getElementById('uitem_'+id);
    const chk  = document.getElementById('uchk_'+id);
    if (uSelected[id]) {
        delete uSelected[id];
        item.classList.remove('selected');
        chk.checked = false;
        const row = document.getElementById('uqrow_'+id);
        if (row) row.remove();
    } else {
        uSelected[id] = nom;
        item.classList.add('selected');
        chk.checked = true;
        addQteRow(id, nom, 100);
    }
}
function uSyncToggle(id) {
    const chk  = document.getElementById('uchk_'+id);
    const nom  = document.getElementById('uitem_'+id).querySelector('p').textContent;
    if (chk.checked) { uSelected[id]=nom; document.getElementById('uitem_'+id).classList.add('selected'); addQteRow(id,nom,100); }
    else { delete uSelected[id]; document.getElementById('uitem_'+id).classList.remove('selected'); const r=document.getElementById('uqrow_'+id); if(r)r.remove(); }
}
function addQteRow(id, nom, qte) {
    if (document.getElementById('uqrow_'+id)) return;
    const div = document.createElement('div');
    div.id = 'uqrow_'+id;
    div.style.cssText = 'display:flex;align-items:center;gap:10px;';
    div.innerHTML = `<span style="font-size:12px;color:var(--vert);font-weight:600;flex:1;">${nom}</span>
        <input type="number" name="quantites[${id}]" value="${qte}" min="1" max="2000"
               style="width:80px;padding:5px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;">
        <span style="font-size:11px;color:#9ca3af;">g</span>`;
    document.getElementById('uQuantitesList').appendChild(div);
}