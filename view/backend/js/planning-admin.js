// planning-admin.js — VERSION CORRIGEE
// Corrections :
//   1. data.filter is not a function : result.data peut etre undefined si PHP retourne une erreur
//      → normalisation defensive de result.data avant tout usage
//   2. Protection sur updateStats() et renderPlanningTable() si data n'est pas un tableau

console.log('📅 Planning Admin JS charge');

let planningAllData     = [];
let planningFiltre      = 'all';
let planningInitialized = false;

function initPlanningModule() {
    const tbody = document.getElementById('planningTableBody');
    if (!tbody) { console.warn('⚠️ #planningTableBody non trouve'); return; }
    if (planningInitialized) { loadPlanningData(); return; }
    planningInitialized = true;
    loadPlanningData();
}

function loadPlanningData() {
    const tbody = document.getElementById('planningTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:50px">
        <div style="margin:0 auto 12px;width:36px;height:36px;border:3px solid rgba(91,62,150,.2);
             border-top-color:#5B3E96;border-radius:50%;animation:spin 0.8s linear infinite"></div>
        Chargement...
    </td></tr>`;

    fetch('planning/listDemandeplanning.php?json=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => {
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) throw new Error('Réponse non-JSON. Vérifiez listDemandeplanning.php');
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(result => {
        // CORRECTION : data.filter is not a function
        // result.data peut être undefined, null, ou non-tableau si le PHP renvoie une erreur SQL
        // On normalise défensivement AVANT tout .filter() ou .map()
        if (!result.success) {
            throw new Error(result.error || result.message || 'Erreur serveur (success=false)');
        }

        // Normalisation : garantit que planningAllData est toujours un Array
        const raw = result.data;
        if (!Array.isArray(raw)) {
            console.warn('⚠️ result.data n\'est pas un tableau :', raw);
            planningAllData = [];
        } else {
            planningAllData = raw;
        }

        updateStats(planningAllData);
        renderPlanningTable();
    })
    .catch(err => {
        console.error('❌ loadPlanningData :', err);
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:50px;color:#e74c3c">
            ❌ ${err.message}<br>
            <small style="color:var(--muted);display:block;margin:8px 0">Vérifiez la console et listDemandeplanning.php</small>
            <button onclick="loadPlanningData()" style="margin-top:12px;padding:8px 18px;
                background:#5B3E96;color:#fff;border:none;border-radius:8px;cursor:pointer">
                Réessayer
            </button>
        </td></tr>`;
    });
}

function updateStats(data) {
    // CORRECTION : garde-fou si data n'est pas un tableau
    if (!Array.isArray(data)) data = [];

    const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setEl('stat-total',     data.length);
    setEl('stat-quotidien', data.filter(d => d.type_budget === 'quotidien').length);
    setEl('stat-hebdo',     data.filter(d => d.type_budget === 'hebdomadaire').length);
    const totalJours = data.reduce((s, d) => {
        return s + (d.type_duree === 'semaines' ? (parseInt(d.duree) || 0) * 7 : (parseInt(d.duree) || 0));
    }, 0);
    setEl('stat-jours', totalJours);
}

function renderPlanningTable() {
    const tbody = document.getElementById('planningTableBody');
    if (!tbody) return;

    // CORRECTION : garde-fou si planningAllData a ete corrompu
    if (!Array.isArray(planningAllData)) planningAllData = [];

    const search = ((document.getElementById('planningSearchInput') || {}).value || '').toLowerCase();
    const data   = planningAllData.filter(d => {
        if (planningFiltre !== 'all' && d.statut !== planningFiltre) return false;
        if (!search) return true;
        return (d.id + ' ' + d.id_utilisateur + ' ' + d.calories + ' ' + (d.statut || '')).toLowerCase().includes(search);
    });

    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted)">Aucune demande</td></tr>`;
        return;
    }

    const badges = { en_attente: '⏳ En attente', approuve: '✅ Approuvé', rejete: '❌ Rejeté' };
    const bcls   = { en_attente: 'badge-en_attente', approuve: 'badge-approuve', rejete: 'badge-rejete' };

    tbody.innerHTML = data.map(d => {
        const statut  = d.statut || 'en_attente';
        const date    = d.date_demande ? new Date(d.date_demande).toLocaleDateString('fr-FR') : '—';
        const nbLignes = parseInt(d.nb_lignes_planning) || 0;
        const planning = nbLignes > 0
            ? `<span style="color:#2ecc71;font-weight:700">${nbLignes} lignes</span>`
            : '<span style="color:var(--muted)">—</span>';

        const isActive = drawerCurrentId === d.id;
        let actions = `<div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center">
            <button class="btn-icon-sm${isActive?' active-voir':''}" onclick="voirDetailPlanning(${d.id})" title="Voir détail">👁️</button>`;
        if (statut === 'en_attente') {
            actions += `<button class="btn-statut-sm btn-approuver" onclick="changerStatutSPA(${d.id},'approuve',this)">✅</button>
                        <button class="btn-statut-sm btn-rejeter"   onclick="changerStatutSPA(${d.id},'rejete',this)">❌</button>`;
        } else if (statut === 'approuve') {
            actions += `<button class="btn-statut-sm btn-regen"   onclick="regenSPA(${d.id},this)">🔄</button>
                        <button class="btn-statut-sm btn-rejeter"  onclick="changerStatutSPA(${d.id},'rejete',this)">❌</button>`;
        } else if (statut === 'rejete') {
            actions += `<button class="btn-statut-sm btn-remettre" onclick="changerStatutSPA(${d.id},'en_attente',this)">↩️</button>`;
        }
        actions += `<button class="btn-icon-sm btn-danger-sm" onclick="supprimerSPA(${d.id},this)">🗑️</button></div>`;

        return `<tr>
            <td><strong>#${d.id}</strong></td>
            <td>👤 ${d.id_utilisateur}</td>
            <td>${parseInt(d.calories).toLocaleString('fr')} kcal</td>
            <td>${parseFloat(d.budget).toFixed(2)} € <small style="color:var(--muted)">${d.type_budget}</small></td>
            <td>${d.duree} <small style="color:var(--muted)">${d.type_duree}</small></td>
            <td><span class="badge-statut ${bcls[statut] || ''}">${badges[statut] || statut}</span></td>
            <td>${planning}</td>
            <td style="color:var(--muted);font-size:.8rem">${date}</td>
            <td>${actions}</td>
        </tr>`;
    }).join('');
}

// ── Actions AJAX ─────────────────────────────────────────────────────────

window.changerStatutSPA = function(id, val, btn) {
    const labels = { approuve: 'approuver', rejete: 'rejeter', en_attente: 'remettre en attente' };
    if (!confirm(`Confirmer : ${labels[val] || val} la demande #${id} ?`)) return;
    const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳';
    fetch(`planning/listDemandeplanning.php?json=1&action=statut&id=${id}&val=${val}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) throw new Error(res.error || res.message || 'Erreur');
        showPlanningToast(res.message || 'Statut mis à jour', 'ok');
        const d = planningAllData.find(x => x.id == id);
        if (d) {
            d.statut = val;
            if (res.nb_lignes) d.nb_lignes_planning = res.nb_lignes;
        }
        renderPlanningTable();
    })
    .catch(err => {
        showPlanningToast('Erreur : ' + err.message, 'err');
        btn.disabled = false;
        btn.textContent = orig;
    });
};

window.regenSPA = function(id, btn) {
    if (!confirm(`Régénérer le planning #${id} ?`)) return;
    const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳';
    fetch(`planning/listDemandeplanning.php?json=1&action=generer&id=${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) throw new Error(res.error || 'Erreur');
        showPlanningToast(res.message, 'ok');
        const d = planningAllData.find(x => x.id == id);
        if (d) d.nb_lignes_planning = res.nb_lignes;
        renderPlanningTable();
    })
    .catch(err => {
        showPlanningToast('Erreur : ' + err.message, 'err');
        btn.disabled = false;
        btn.textContent = orig;
    });
};

window.supprimerSPA = function(id, btn) {
    if (!confirm(`Supprimer la demande #${id} et tout son planning ?`)) return;
    btn.disabled = true;
    fetch(`planning/listDemandeplanning.php?json=1&action=delete&id=${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) throw new Error('Erreur suppression');
        showPlanningToast('Demande #' + id + ' supprimée', 'ok');
        planningAllData = planningAllData.filter(d => d.id != id);
        updateStats(planningAllData);
        if (drawerCurrentId == id) closeDrawer();
        renderPlanningTable();
    })
    .catch(err => {
        showPlanningToast('Erreur : ' + err.message, 'err');
        btn.disabled = false;
    });
};

// ══════════════════════════════════════════════════════════════
// SIDE DRAWER — Voir détail sans quitter la page
// ══════════════════════════════════════════════════════════════
let drawerCurrentId = null;

window.voirDetailPlanning = function(id) {
    // Toggle : si déjà ouvert sur le même ID → fermer
    if (drawerCurrentId === id) { closeDrawer(); return; }
    drawerCurrentId = id;
    renderPlanningTable(); // highlight le bouton 👁️

    const overlay = document.getElementById('planningDrawerOverlay');
    const drawer  = document.getElementById('planningDrawer');
    const body    = document.getElementById('drawerBody');
    const title   = document.getElementById('drawerTitle');
    const sub     = document.getElementById('drawerSubtitle');

    // Retirer ancienne barre d'actions si présente
    const oldBar = drawer.querySelector('.drawer-actions-bar');
    if (oldBar) oldBar.remove();

    overlay.classList.add('open');
    drawer.classList.add('open');
    title.textContent = `Demande #${id}`;
    sub.textContent   = 'Chargement...';
    body.innerHTML    = `<div class="drawer-loading">
        <div class="drawer-spinner"></div><p>Chargement des données...</p>
    </div>`;

    fetch(`planning/showDemandeplanning.php?id=${id}&json=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(res => {
        if (!res.success) throw new Error(res.error || 'Erreur serveur');
        _renderDrawerContent(res, drawer, body, sub);
    })
    .catch(err => {
        body.innerHTML = `<div style="text-align:center;padding:50px;color:#e74c3c">
            ❌ ${err.message}<br>
            <button onclick="voirDetailPlanning(${id})" style="margin-top:16px;padding:8px 18px;
                background:#5B3E96;color:#fff;border:none;border-radius:8px;cursor:pointer">Réessayer</button>
        </div>`;
    });
};

window.closeDrawer = function() {
    document.getElementById('planningDrawerOverlay').classList.remove('open');
    document.getElementById('planningDrawer').classList.remove('open');
    const bar = document.getElementById('planningDrawer').querySelector('.drawer-actions-bar');
    if (bar) bar.remove();
    drawerCurrentId = null;
    renderPlanningTable();
};

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && drawerCurrentId !== null) closeDrawer();
});

function _renderDrawerContent(res, drawer, body, sub) {
    const d  = res.demande;
    const ss = res.sportsommeil;
    const pl = res.planning;
    const st = res.stats;

    const badges = { en_attente:'⏳ En attente', approuve:'✅ Approuvé', rejete:'❌ Rejeté' };
    const bcls   = { en_attente:'badge-en_attente', approuve:'badge-approuve', rejete:'badge-rejete' };
    const statut = d.statut || 'en_attente';
    const date   = d.date_demande ? new Date(d.date_demande).toLocaleDateString('fr-FR') : '—';

    sub.innerHTML = `<span class="badge-statut ${bcls[statut]}">${badges[statut]||statut}</span> &nbsp;·&nbsp; ${date}`;
    document.getElementById('drawerTitle').textContent = `Demande #${d.id}`;

    // ── Bloc 1 : Infos demande ──
    const bloc1 = `<div class="drawer-block">
        <div class="drawer-block-hd">📋 Informations de la demande</div>
        <div class="drawer-block-bd">
            <div class="dw-grid">
                <div class="dw-item"><span class="dw-lbl">👤 Utilisateur</span><span class="dw-val">#${d.id_utilisateur}</span></div>
                <div class="dw-item"><span class="dw-lbl">📅 Date demande</span><span class="dw-val">${date}</span></div>
                <div class="dw-item"><span class="dw-lbl">🔥 Calories</span><span class="dw-val">${parseInt(d.calories).toLocaleString('fr')} kcal/jour</span></div>
                <div class="dw-item"><span class="dw-lbl">💰 Budget</span><span class="dw-val">${parseFloat(d.budget).toFixed(2)} € <small style="color:var(--muted);font-weight:400">${d.type_budget}</small></span></div>
                <div class="dw-item"><span class="dw-lbl">⏱️ Durée</span><span class="dw-val">${d.duree} ${d.type_duree}</span></div>
                <div class="dw-item"><span class="dw-lbl">📊 Statut</span><span class="dw-val"><span class="badge-statut ${bcls[statut]}">${badges[statut]||statut}</span></span></div>
            </div>
        </div>
    </div>`;

    // ── Bloc 2 : Sport & Sommeil ──
    let bloc2 = '';
    if (ss) {
        const mpj = Math.round((ss.duree_sport_hebdo||0)/7);
        bloc2 = `<div class="drawer-block">
            <div class="drawer-block-hd">🏃 Sport & Sommeil</div>
            <div class="drawer-block-bd">
                <div class="dw-grid">
                    <div class="dw-item"><span class="dw-lbl">🏋️ Activité</span><span class="dw-val">${ss.activite_sportive||'—'}</span></div>
                    <div class="dw-item"><span class="dw-lbl">⏱️ Durée/semaine</span><span class="dw-val">${ss.duree_sport_hebdo||0} min <small style="color:var(--muted);">(≈${mpj} min/j)</small></span></div>
                    <div class="dw-item"><span class="dw-lbl">🌙 Coucher</span><span class="dw-val">${(ss.heure_coucher||'—').substring(0,5)}</span></div>
                    <div class="dw-item"><span class="dw-lbl">☀️ Réveil</span><span class="dw-val">${(ss.heure_reveil||'—').substring(0,5)}</span></div>
                    <div class="dw-item" style="grid-column:1/-1"><span class="dw-lbl">😴 Qualité sommeil</span><span class="dw-val">${ss.qualite_sommeil||'—'}</span></div>
                </div>
            </div>
        </div>`;
    } else {
        bloc2 = `<div class="drawer-block"><div class="drawer-block-bd">
            <div style="padding:10px;background:rgba(243,156,18,.1);border:1px solid rgba(243,156,18,.3);border-radius:10px;color:#f39c12;font-size:.85rem;">
                ⚠️ Sport & Sommeil non rempli — planning impossible à générer.
            </div></div></div>`;
    }

    // ── Bloc 3 : Stats ──
    const hasPlanning = pl && pl.length > 0;
    let bloc3 = '';
    if (hasPlanning) {
        bloc3 = `<div class="drawer-block">
            <div class="drawer-block-hd">📊 Résumé du planning</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;padding:14px 16px;">
                <div class="dw-pill">📆 <strong>${st.nb_jours}</strong> jour(s)</div>
                <div class="dw-pill">🍽️ <strong>${st.nb_repas}</strong> repas</div>
                <div class="dw-pill">🏃 <strong>${st.nb_sport}</strong> sport</div>
                <div class="dw-pill">🌙 <strong>${st.nb_sommeil}</strong> nuit(s)</div>
                <div class="dw-pill">📋 <strong>${res.nb_lignes}</strong> lignes</div>
            </div>
        </div>`;
    }

    // ── Bloc 4 : Tableau planning ──
    let bloc4 = '';
    if (hasPlanning) {
        const headers = pl.map(j =>
            `<th class="dw-th-jour"><span style="font-weight:700;display:block;font-size:.78rem">${j.jourFr}</span><span style="font-size:.68rem;color:var(--muted)">${j.dateAff}</span></th>`
        ).join('');
        const mkRow = (cls, ico, lbl, getter) => `<tr class="${cls}">
            <td class="dw-td-type"><span style="display:block;font-size:.9rem">${ico}</span>${lbl}</td>
            ${pl.map(j => { const v = getter(j); return `<td>${v||'<span style="color:var(--muted)">—</span>'}</td>`; }).join('')}
        </tr>`;

        bloc4 = `<div class="drawer-block">
            <div class="drawer-block-hd">📅 Planning complet</div>
            <div style="overflow-x:auto;padding-bottom:4px">
                <table class="dw-planning-table">
                    <thead><tr><th class="dw-th-type">Activité</th>${headers}</tr></thead>
                    <tbody>
                        ${mkRow('row-repas','🍳','Petit-déj', j=>(j.repas||[])[0])}
                        ${mkRow('row-repas','🍽️','Déjeuner',  j=>(j.repas||[])[1])}
                        ${mkRow('row-repas','🌮','Dîner',     j=>(j.repas||[])[2])}
                        ${mkRow('row-sport','🏃','Sport',     j=>(j.sport||[])[0])}
                        ${mkRow('row-sommeil','🌙','Sommeil',  j=>(j.sommeil||[])[0])}
                    </tbody>
                </table>
            </div>
        </div>`;
    } else {
        bloc4 = `<div style="text-align:center;padding:30px 20px;border:2px dashed rgba(91,62,150,.2);border-radius:12px;color:var(--muted);">
            <div style="font-size:2rem;margin-bottom:10px;opacity:.5">📅</div>
            <h4 style="margin:0 0 8px">Aucun planning généré</h4>
            <p style="font-size:.83rem;margin:0">${ss ? 'Approuvez pour générer le planning.' : 'Complétez d\'abord l\'étape Sport & Sommeil.'}</p>
        </div>`;
    }

    body.innerHTML = bloc1 + bloc2 + bloc3 + bloc4;

    // Barre d'actions en bas du drawer
    const actBar = document.createElement('div');
    actBar.className = 'drawer-actions-bar';
    let actBtns = '';
    if (statut === 'en_attente') {
        actBtns = `<button class="dw-btn dw-btn-ok"  onclick="drawerChangerStatut(${d.id},'approuve')">✅ Approuver</button>
                   <button class="dw-btn dw-btn-err" onclick="drawerChangerStatut(${d.id},'rejete')">❌ Rejeter</button>`;
    } else if (statut === 'approuve') {
        actBtns = `<button class="dw-btn dw-btn-blue" onclick="drawerRegen(${d.id})">🔄 Régénérer</button>
                   <button class="dw-btn dw-btn-err"  onclick="drawerChangerStatut(${d.id},'rejete')">❌ Rejeter</button>`;
    } else if (statut === 'rejete') {
        actBtns = `<button class="dw-btn dw-btn-warn" onclick="drawerChangerStatut(${d.id},'en_attente')">↩️ Remettre en attente</button>`;
    }
    if (actBtns) { actBar.innerHTML = actBtns; drawer.appendChild(actBar); }
}

window.drawerChangerStatut = function(id, val) {
    const labels = { approuve:'approuver', rejete:'rejeter', en_attente:'remettre en attente' };
    if (!confirm(`Confirmer : ${labels[val]||val} la demande #${id} ?`)) return;
    fetch(`planning/listDemandeplanning.php?json=1&action=statut&id=${id}&val=${val}`, {
        headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(r=>r.json()).then(res=>{
        if (!res.success) throw new Error(res.error||'Erreur');
        showPlanningToast(res.message||'Statut mis à jour','ok');
        const d = planningAllData.find(x=>x.id==id);
        if (d) { d.statut=val; if(res.nb_lignes) d.nb_lignes_planning=res.nb_lignes; }
        renderPlanningTable();
        voirDetailPlanning(id); // rafraîchit le drawer
    }).catch(err=>showPlanningToast('Erreur : '+err.message,'err'));
};

window.drawerRegen = function(id) {
    if (!confirm(`Régénérer le planning #${id} ?`)) return;
    fetch(`planning/listDemandeplanning.php?json=1&action=generer&id=${id}`, {
        headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(r=>r.json()).then(res=>{
        if (!res.success) throw new Error(res.error||'Erreur');
        showPlanningToast(res.message,'ok');
        const d = planningAllData.find(x=>x.id==id);
        if (d) d.nb_lignes_planning=res.nb_lignes;
        renderPlanningTable();
        voirDetailPlanning(id);
    }).catch(err=>showPlanningToast('Erreur : '+err.message,'err'));
};

function showPlanningToast(msg, type) {
    let t = document.getElementById('planningToastSPA');
    if (!t) {
        t = document.createElement('div');
        t.id = 'planningToastSPA';
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;font-weight:600;font-size:.88rem;z-index:9999;transition:opacity .3s';
        document.body.appendChild(t);
    }
    t.textContent    = msg;
    t.style.display  = 'block';
    t.style.opacity  = '1';
    t.style.background = type === 'ok' ? '#1a4731' : '#4a1515';
    t.style.border     = type === 'ok' ? '1px solid #2ecc71' : '1px solid #e74c3c';
    t.style.color      = type === 'ok' ? '#2ecc71' : '#e74c3c';
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.style.display = 'none', 350); }, 3200);
}

// ── Filtres ───────────────────────────────────────────────────────────────
window.filterPlanningTable = function() { renderPlanningTable(); };
window.loadPlanningData    = loadPlanningData;
window.setPlanningFilter   = function(f, el) {
    planningFiltre = f;
    document.querySelectorAll('.planning-chip').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    renderPlanningTable();
};

// ── Auto-init ─────────────────────────────────────────────────────────────
document.addEventListener('adminModuleLoaded', e => {
    if (e.detail && e.detail.moduleName === 'planning') setTimeout(initPlanningModule, 80);
});
new MutationObserver(() => {
    if (!planningInitialized && document.getElementById('planningTableBody')) initPlanningModule();
}).observe(document.querySelector('.main-content') || document.body, { childList: true, subtree: true });
window.addEventListener('load', () => {
    setTimeout(initPlanningModule, 300);
    setTimeout(initPlanningModule, 900);
});
console.log('✅ planning-admin.js initialise');