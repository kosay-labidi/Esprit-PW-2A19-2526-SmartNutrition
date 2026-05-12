/* fo_repaslist.js */

/* ── Données PHP → JS (tous les aliments avec leurs valeurs nutritionnelles) */
const ALIMENTS_DATA = <?= $alimentsJson ?>;

const HISTORIQUE_REPAS = <?= json_encode(array_map(function($r) use ($repaDetails) {
    $id  = (int)$r['id_repas'];
    $tot = $repaDetails[$id]['totaux'] ?? [];
    $al  = $repaDetails[$id]['aliments'] ?? [];
    return ['id'=>$id,'nom'=>$r['nom_repas'],'date'=>$r['date_repas'],
            'calories'=>round($tot['total_calories']??0,1),
            'proteines'=>round($tot['total_proteines']??0,1),
            'lipides'=>round($tot['total_lipides']??0,1),
            'fibres'=>round($tot['total_fibres']??0,1),
            'sucre'=>round($tot['total_sucre']??0,1),
            'sodium'=>round($tot['total_sodium']??0,1),
            'co2'=>round($tot['total_co2']??0,2),
            'aliments'=>array_column($al,'nom')];
}, $mesRepas), JSON_UNESCAPED_UNICODE) ?>;

/* ── Curseur ──────────────────────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button,input').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
})();

/* ── Modal ─────────────────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/* ── Accordéon FONCTIONNALITÉ 3 ─────────────────────────────────
   Affiche/cache la description du repas au clic                  */
function toggleDetail(id) {
    const panel   = document.getElementById('detail_' + id);
    const chevron = document.getElementById('chevron_' + id);
    const isOpen  = panel.classList.contains('open');
    panel.classList.toggle('open', !isOpen);
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}

/* ════════════════════════════════════════════════════════════════
   SECTION A — CRUD : Gestion des sélections d'aliments
   ════════════════════════════════════════════════════════════════ */

const selected = {}; /* { id_aliment: nom } */

function toggleAliment(id, nom) {
    const item = document.getElementById('item_' + id);
    const chk  = document.getElementById('chk_' + id);
    if (selected[id]) {
        delete selected[id];
        item.classList.remove('selected');
        chk.checked = false;
    } else {
        selected[id] = nom;
        item.classList.add('selected');
        chk.checked = true;
    }
    updateQuantitesList();
    /* Déclenche la mise à jour des fonctionnalités innovantes */
    analyseTempsReel();
}

function syncToggle(id) {
    const chk  = document.getElementById('chk_' + id);
    const item = document.getElementById('item_' + id);
    const nom  = item.querySelector('p').textContent;
    if (chk.checked) { selected[id] = nom; item.classList.add('selected'); }
    else { delete selected[id]; item.classList.remove('selected'); }
    updateQuantitesList();
    analyseTempsReel();
}

/* Met à jour la liste des quantités */
function updateQuantitesList() {
    const zone = document.getElementById('selectionZone');
    const list = document.getElementById('quantitesList');
    const keys = Object.keys(selected);
    if (!keys.length) { zone.style.display = 'none'; return; }
    zone.style.display = 'block';
    list.innerHTML = keys.map(id => `
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:11px;color:var(--vert);font-weight:600;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${selected[id]}</span>
            <input type="number" name="quantites[${id}]" value="100"
                   onchange="validerQuantite(this,'qte_err_${id}'); analyseTempsReel();"
                   id="qte_${id}"
                   style="width:70px;padding:4px 8px;border-radius:8px;border:1.5px solid #e8e0d8;font-size:12px;text-align:center;">
            <span style="font-size:10px;color:#9ca3af;">g</span>
            <span id="qte_err_${id}" style="font-size:10px;color:#8a2020;display:none;font-weight:600;" title="Entre 1 et 2000 g">!</span>
        </div>
    `).join('');
}

/* Filtre la grille d'aliments dans le formulaire */
function filterAlimModal(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#alimGrid .alim-item').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* Filtre la consultation d'aliments */
function filterConsult() {
    const q = document.getElementById('searchAlim').value.toLowerCase();
    document.querySelectorAll('#consultList .consult-card').forEach(el => {
        el.style.display = el.dataset.nom.includes(q) ? '' : 'none';
    });
}

/* ════════════════════════════════════════════════════════════════
   SECTION B — FONCTIONNALITÉS INNOVANTES (JS)
   Toutes calculées côté client en temps réel dans le formulaire
   ════════════════════════════════════════════════════════════════ */

/**
 * Détermine le moment de la journée selon l'heure saisie
 */
function getMomentJournee(heure) {
    const h = parseInt(heure.split(':')[0]);
    if (h >= 6  && h < 10) return { key:'petit_dej', label:'☀️ Petit-déjeuner' };
    if (h >= 11 && h < 15) return { key:'dejeuner',  label:'🌤️ Déjeuner' };
    if (h >= 18 && h < 23) return { key:'diner',     label:'🌙 Dîner' };
    return { key:'collation', label:'🍎 Collation' };
}

/**
 * Détermine la saison selon le mois actuel
 */
function getSaison() {
    const m = new Date().getMonth() + 1;
    if ([3,4,5].includes(m))  return { key:'printemps', label:'🌸 Printemps' };
    if ([6,7,8].includes(m))  return { key:'ete',       label:'☀️ Été' };
    if ([9,10,11].includes(m))return { key:'automne',   label:'🍂 Automne' };
    return { key:'hiver', label:'❄️ Hiver' };
}

/**
 * FONCTIONNALITÉ 1 : Calcule le score écologique (JS)
 * Même logique que scoreEcologique() dans repas_helpers.php
 */
function calcScore(totaux) {
    if (totaux.poids <= 0) return 0;
    const co2ParKg   = totaux.co2 / (totaux.poids / 1000);
    const scoreCo2   = Math.max(0, Math.min(100, 100 - (co2ParKg / 6) * 100));
    const scoreFib   = Math.min(100, (totaux.fibres / 10) * 100);
    const ratioSucre = totaux.glucides > 0 ? totaux.sucre / totaux.glucides : 0;
    const scoreSucre = Math.max(0, Math.min(100, 100 - (ratioSucre * 100)));
    return Math.round(scoreCo2 * 0.60 + scoreFib * 0.20 + scoreSucre * 0.20);
}

/**
 * Retourne le label écologique selon le score
 */
function getLabelEco(score) {
    if (score >= 80) return { label:'Repas écologique',      emoji:'🌿', color:'#1a372f', bg:'#e8f0e9', bar:'#4caf50', desc:'Excellent choix pour la planète !' };
    if (score >= 60) return { label:'Repas acceptable',      emoji:'🌱', color:'#4a7a50', bg:'#f1f8e9', bar:'#8bc34a', desc:'Bon bilan, quelques ajustements possibles.' };
    if (score >= 40) return { label:'Repas à améliorer',     emoji:'⚠️', color:'#8a6510', bg:'#fff9e6', bar:'#ffc107', desc:'Impact modéré, des substitutions sont conseillées.' };
    if (score >= 20) return { label:'Repas polluant',         emoji:'🌫️', color:'#c07020', bg:'#fff3e0', bar:'#ff9800', desc:'Impact élevé, privilégiez des aliments locaux.' };
    return             { label:'Repas très polluant',        emoji:'🔴', color:'#8a2020', bg:'#faeaea', bar:'#f44336', desc:'Impact très fort. Revoyez la composition.' };
}

/**
 * FONCTIONNALITÉ 2 : Détecte les déséquilibres nutritionnels (JS)
 * Même logique que analyseNutritionnelle() dans repas_helpers.php
 */
function detecterAlertes(totaux, moment) {
    const alertes = [];
    const objets = {
        petit_dej : { calMax:600,  calMin:250, protMin:10, lipMax:20 },
        dejeuner  : { calMax:900,  calMin:400, protMin:20, lipMax:35 },
        diner     : { calMax:700,  calMin:300, protMin:15, lipMax:25 },
        collation : { calMax:300,  calMin:50,  protMin:5,  lipMax:15 },
    };
    const obj = objets[moment] || objets['dejeuner'];
    if (totaux.calories <= 0) return alertes;

    if (totaux.calories > obj.calMax)
        alertes.push({ type:'erreur',    emoji:'🔥', msg:`Trop calorique : ${Math.round(totaux.calories)} kcal (max ${obj.calMax} kcal).` });
    if (totaux.calories < obj.calMin && totaux.calories > 0)
        alertes.push({ type:'warning',   emoji:'⚡', msg:`Repas trop léger : ${Math.round(totaux.calories)} kcal.` });
    if (totaux.proteines < obj.protMin)
        alertes.push({ type:'erreur',    emoji:'💪', msg:`Manque de protéines : ${totaux.proteines.toFixed(1)}g (min ${obj.protMin}g).` });
    if (totaux.lipides > obj.lipMax)
        alertes.push({ type:'erreur',    emoji:'🧈', msg:`Trop de gras : ${totaux.lipides.toFixed(1)}g (max ${obj.lipMax}g).` });
    if (totaux.glucides > 0 && (totaux.sucre / totaux.glucides) > 0.5)
        alertes.push({ type:'erreur',    emoji:'🍬', msg:`Trop de sucre : ${totaux.sucre.toFixed(1)}g = ${Math.round(totaux.sucre/totaux.glucides*100)}% des glucides.` });
    if (totaux.calories > 200 && totaux.fibres < 5)
        alertes.push({ type:'warning',   emoji:'🥦', msg:`Manque de fibres : ${totaux.fibres.toFixed(1)}g. Ajoutez des légumes.` });
    if (totaux.sodium > 800)
        alertes.push({ type:'warning',   emoji:'🧂', msg:`Trop de sodium : ${Math.round(totaux.sodium)}mg.` });
    if (totaux.poids > 1200)
        alertes.push({ type:'gaspillage',emoji:'♻️', msg:`Quantité importante (${Math.round(totaux.poids)}g). Risque de gaspillage.` });

    return alertes;
}

/**
 * FONCTIONNALITÉ 2 : Génère les recommandations contextuelles (JS)
 * Même logique que recommandations() dans repas_helpers.php
 */
function genRecommandations(alertes, moment, saison) {
    const recs = [];
    const saisonLegumes = {
        printemps:'asperges, petits pois',
        ete:'tomates, courgettes, concombres',
        automne:'carottes, courges, poireaux',
        hiver:'brocoli, chou, navets'
    };

    alertes.forEach(a => {
        if (a.msg.includes('calorique'))   recs.push({ emoji:'🥗', texte:`Remplacez un aliment calorique par des légumes frais de ${saison.label.split(' ')[1]||'saison'}.` });
        if (a.msg.includes('léger'))       recs.push({ emoji:'🫘', texte:`Enrichissez avec des légumineuses ou des céréales complètes pour tenir jusqu'au prochain repas.` });
        if (a.msg.includes('protéines')) {
            const p = moment === 'petit_dej' ? '🥚 Ajoutez des œufs ou du fromage blanc.' :
                      moment === 'dejeuner'  ? '🐟 Privilégiez poisson ou légumineuses.' :
                                               '🍗 Une portion de viande maigre ou tofu.';
            recs.push({ emoji:'💪', texte: p });
        }
        if (a.msg.includes('gras'))        recs.push({ emoji:'🫒', texte:`Remplacez les graisses saturées par de l'huile d'olive. Évitez les fritures.` });
        if (a.msg.includes('sucre'))       recs.push({ emoji:'🍓', texte:`Substituez le sucre par des fruits frais de saison (${saisonLegumes[saison.key]||'fruits de saison'}).` });
        if (a.msg.includes('fibres'))      recs.push({ emoji:'🥦', texte:`Ajoutez des légumes de saison : ${saisonLegumes[saison.key]||'légumes frais'}.` });
        if (a.msg.includes('sodium'))      recs.push({ emoji:'🌿', texte:`Remplacez le sel par des herbes aromatiques fraîches.` });
        if (a.msg.includes('gaspillage'))  recs.push({ emoji:'📦', texte:`Réduisez les quantités. Un repas équilibré ≈ 400–800g selon le moment.` });
    });

    /* Conseil saisonnier permanent */
    const saisonConseils = {
        printemps: { emoji:'🌸', texte:'Au printemps, profitez des asperges, radis et petits pois de saison.' },
        ete:       { emoji:'☀️', texte:'En été, hydratez-vous avec concombres, tomates et pastèques.' },
        automne:   { emoji:'🍂', texte:'En automne, les courges et champignons sont nutritifs et de saison.' },
        hiver:     { emoji:'❄️', texte:'En hiver, les soupes de légumes racines réchauffent et renforcent l\'immunité.' },
    };
    recs.push(saisonConseils[saison.key]);

    return recs;
}

/**
 * ANALYSE EN TEMPS RÉEL — Mise à jour de l'interface innovante
 * Appelée à chaque sélection/désélection d'aliment ou changement
 * de quantité. Met à jour les 2 panneaux fonctionnalités.
 */
function analyseTempsReel() {
    const ids  = Object.keys(selected);
    const noSel= document.getElementById('noSelMsg');
    const ecoP = document.getElementById('ecoPanel');
    const alP  = document.getElementById('alertesPanel');
    const rcP  = document.getElementById('recosPanel');

    if (ids.length === 0) {
        /* Rien sélectionné : on remet l'état par défaut */
        noSel.style.display = 'block';
        document.getElementById('ecoScoreVal').textContent = '—';
        document.getElementById('ecoLabel').textContent    = 'Sélectionnez des aliments';
        document.getElementById('ecoDesc').textContent     = 'L\'indicateur se mettra à jour en temps réel';
        document.getElementById('ecoBarFill').style.width  = '0%';
        document.getElementById('ecoBarFill').style.background = '#9ca3af';
        document.getElementById('ecoCircle').style.color   = '#9ca3af';
        document.getElementById('ecoCircle').style.borderColor = '#d0c8be';
        document.getElementById('ecoCo2').textContent      = '0 kg';
        ecoP.style.background = '#f4ede4';
        ecoP.style.borderColor = '#e8e0d8';
        alP.style.display  = 'none';
        rcP.style.display  = 'none';
        return;
    }
    noSel.style.display = 'none';

    /* Calcul des totaux à partir des données PHP injectées en JS */
    const totaux = { calories:0, proteines:0, glucides:0, lipides:0,
                     fibres:0, sucre:0, sodium:0, co2:0, poids:0 };

    ids.forEach(id => {
        /* Récupère la quantité saisie (ou 100g par défaut) */
        const qteInput = document.querySelector(`input[name="quantites[${id}]"]`);
        const qte      = qteInput ? parseFloat(qteInput.value) || 100 : 100;
        const a        = ALIMENTS_DATA[id];
        if (!a) return;
        const f = qte / 100;
        totaux.calories  += (parseFloat(a.calories)  || 0) * f;
        totaux.proteines += (parseFloat(a.proteines) || 0) * f;
        totaux.glucides  += (parseFloat(a.glucides)  || 0) * f;
        totaux.lipides   += (parseFloat(a.lipides)   || 0) * f;
        totaux.fibres    += (parseFloat(a.fibres)    || 0) * f;
        totaux.sucre     += (parseFloat(a.sucre)     || 0) * f;
        totaux.sodium    += (parseFloat(a.sodium)    || 0) * f;
        totaux.co2       += (parseFloat(a.co2)       || 0) * f;
        totaux.poids     += qte;
    });

    /* Contexte temporel */
    const dateVal  = document.getElementById('f_date').value;
    const heure    = dateVal ? dateVal.split('T')[1] || '12:00' : getHeureLocale();
    const moment   = getMomentJournee(heure);
    const saison   = getSaison();

    /* ── FONCTIONNALITÉ 1 : Mise à jour score écologique ───── */
    const score = calcScore(totaux);
    const lbl   = getLabelEco(score);

    document.getElementById('ecoScoreVal').textContent = score;
    document.getElementById('ecoLabel').textContent    = `${lbl.emoji} ${lbl.label}`;
    document.getElementById('ecoDesc').textContent     = lbl.desc;
    document.getElementById('ecoBarFill').style.width  = score + '%';
    document.getElementById('ecoBarFill').style.background = lbl.bar;
    document.getElementById('ecoCircle').style.color       = lbl.color;
    document.getElementById('ecoCircle').style.borderColor = lbl.color;
    document.getElementById('ecoCo2').textContent  = totaux.co2.toFixed(2) + ' kg';
    document.getElementById('ecoCo2').style.color  = lbl.color;
    ecoP.style.background  = lbl.bg;
    ecoP.style.borderColor = lbl.color + '40';

    /* ── FONCTIONNALITÉ 2 : Alertes nutritionnelles ─────────── */
    const alertes = detecterAlertes(totaux, moment.key);
    const alList  = document.getElementById('alertesList');

    if (alertes.length > 0) {
        alP.style.display = 'block';
        alList.innerHTML = alertes.map(a => {
            const cssClass = a.type === 'erreur' ? 'alerte-erreur'
                           : a.type === 'gaspillage' ? 'alerte-gaspillage'
                           : 'alerte-warning';
            return `<div class="alerte-item ${cssClass}">
                        <span style="font-size:16px;flex-shrink:0;">${a.emoji}</span>
                        <span>${a.msg}</span>
                    </div>`;
        }).join('');
    } else {
        alP.style.display = 'none';
    }

    /* ── FONCTIONNALITÉ 2 : Recommandations contextuelles ────── */
    /* Recommandations IA via Gemini */
    var alimListIA = Object.keys(selected).map(function(id) {
        var inp = document.querySelector('input[name="quantites['+id+']"]');
        return {nom: selected[id], quantite: inp ? parseFloat(inp.value)||100 : 100};
    });
    genRecommandationsIA(totaux, alimListIA, moment, saison, score);
}

/* Récupère l'heure locale actuelle */
function getHeureLocale() {
    const now = new Date();
    return `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
}

/* Déclenche l'analyse quand la date change */
function onDateChange() {
    analyseTempsReel();
}

/* ── CRUD : Validation et soumission du formulaire ────────────── */
/* ────────────────────────────────────────────────────────────
   CONTRÔLE DE SAISIE — Validation JS (sans attributs HTML5)
   Règle : pas de required / min / max dans le HTML.
   Tout le contrôle se fait ici en JavaScript.
   ─────────────────────────────────────────────────────────── */

/**
 * validerQuantite() — Valide une quantité saisie (1 à 2000 g)
 * Appelée à chaque changement d'un champ quantité.
 * Affiche un indicateur rouge "!" si la valeur est invalide.
 */
function validerQuantite(input, errId) {
    const val = parseFloat(input.value);
    const err = document.getElementById(errId);
    const invalid = isNaN(val) || val < 1 || val > 2000;
    if (invalid) {
        input.style.borderColor = '#c09090';
        input.style.background  = '#fdf5f5';
        if (err) { err.style.display = 'inline'; err.title = 'Valeur entre 1 et 2000 g'; }
    } else {
        input.style.borderColor = '#e8e0d8';
        input.style.background  = 'white';
        if (err) err.style.display = 'none';
    }
    return !invalid;
}

/**
 * validateAndCreate() — Validation complète du formulaire
 * Vérifie : nom, date, sélection d'aliments, quantités.
 * Sans aucun attribut HTML5 (required, min, max).
 */
function validateAndCreate() {
    let valid = true;

    /* Utilitaire : marque/démarque un champ en erreur */
    function setErr(id, errId, show) {
        const el=document.getElementById(id), er=document.getElementById(errId);
        if(!el||!er) return;
        if(show){ el.classList.add('error'); er.classList.add('show'); valid=false; }
        else    { el.classList.remove('error'); er.classList.remove('show'); }
    }

    /* 1. Nom du repas : obligatoire, minimum 2 caractères */
    const nom = document.getElementById('f_nom').value.trim();
    setErr('f_nom', 'e_nom', nom.length < 2);

    /* 2. Date du repas : obligatoire */
    const date = document.getElementById('f_date').value;
    setErr('f_date', 'e_date', !date || date.trim() === '');

    /* 3. Aliments : au moins un sélectionné */
    const hasAlim = Object.keys(selected).length > 0;
    const eAlim   = document.getElementById('e_alim');
    if (!hasAlim) { eAlim.style.display = 'block'; valid = false; }
    else            eAlim.style.display = 'none';

    /* 4. Quantités : chaque aliment sélectionné doit avoir
          une quantité valide entre 1 et 2000 grammes.
          (validation purement JS, aucun attribut HTML5) */
    let qtesValides = true;
    Object.keys(selected).forEach(id => {
        const input = document.querySelector(`input[name="quantites[${id}]"]`);
        const errId = `qte_err_${id}`;
        if (input) {
            const ok = validerQuantite(input, errId);
            if (!ok) { qtesValides = false; valid = false; }
        }
    });

    if (!qtesValides) {
        /* Afficher un message global pour les quantités */
        const eAlim = document.getElementById('e_alim');
        eAlim.textContent = 'Vérifiez les quantités (entre 1 et 2000 g par aliment).';
        eAlim.style.display = 'block';
    }

    /* Soumission si tout est valide */
    if (valid) {
        document.getElementById('createRepasForm').submit();
    } else {
        const first = document.querySelector('.fi.error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/* ── Initialisation ──────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    /* Pré-remplir date/heure actuelle */
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const f = document.getElementById('f_date');
    if (f) f.value = now.toISOString().slice(0, 16);
});

/* === RECHERCHE === */
function appliquerFiltresRepas() {
    var q = document.getElementById("rechercheRepas").value.toLowerCase().trim();
    document.querySelectorAll("#repasContainer .repas-card").forEach(function(card) {
        card.style.display = (!q || card.dataset.nom.includes(q)) ? "" : "none";
    });
}

/* === TRI === */
function appliquerTriRepas() {
    var val = document.getElementById("triRepas").value;
    if (!val) return;
    var parts = val.split("-"), crit = parts[0], asc = parts[1] === "asc";
    function gv(el) {
        if (crit==="nom")   return el.dataset.nom  || "";
        if (crit==="date")  return el.dataset.date || "";
        if (crit==="cal")   return parseFloat(el.dataset.cal)   || 0;
        if (crit==="score") return parseFloat(el.dataset.score) || 0;
        if (crit==="nb")    return parseFloat(el.dataset.nb)    || 0;
        return "";
    }
    var box = document.getElementById("repasContainer");
    if (!box) return;
    var cards = Array.from(box.querySelectorAll(".repas-card"));
    cards.sort(function(a, b) {
        var va = gv(a), vb = gv(b);
        if (typeof va === "string") return asc ? va.localeCompare(vb) : vb.localeCompare(va);
        return asc ? va - vb : vb - va;
    });
    cards.forEach(function(el) { box.appendChild(el); });
}

/* === STATISTIQUES === */
var statsRadarInst = null, statsEvoInst = null, statsBarInst = null;

function toggleStats() {
    var panel = document.getElementById("statsPanel");
    if (!panel) return;
    var visible = panel.style.display === "block";
    panel.style.display = visible ? "none" : "block";
    if (!visible) calculerStats();
}

function calculerStats() {
    var crit = document.getElementById("statsCritere").value;
    var periode = document.getElementById("statsPeriode").value;
    var now = new Date();
    var all = Array.from(document.querySelectorAll("#repasContainer .repas-card"));
    var cards = all.filter(function(c) {
        if (periode === "tout") return true;
        var d = new Date(c.dataset.date), diff = (now - d) / 86400000;
        return periode === "semaine" ? diff <= 7 : diff <= 30;
    });
    if (cards.length === 0) {
        document.getElementById("statsMetriques").innerHTML = "<div style=\"grid-column:1/-1;text-align:center;padding:20px;color:#9ca3af;\">Aucun repas pour cette periode.</div>";
        return;
    }
    var labels=[], values=[], scores=[], dates=[], prots=[];
    cards.forEach(function(card) {
        var n = card.dataset.nom || "repas";
        labels.push(n.charAt(0).toUpperCase() + n.slice(1,14));
        scores.push(parseFloat(card.dataset.score) || 0);
        dates.push((card.dataset.date || "").slice(0,10));
        prots.push(parseFloat(card.dataset.proteines) || 0);
        var v = 0;
        if (crit==="calories")  v = parseFloat(card.dataset.cal)        || 0;
        if (crit==="score")     v = parseFloat(card.dataset.score)      || 0;
        if (crit==="nb")        v = parseFloat(card.dataset.nb)         || 0;
        if (crit==="proteines") v = parseFloat(card.dataset.proteines)  || 0;
        values.push(Math.round(v));
    });
    var total = values.reduce(function(a,b){return a+b;},0);
    var moy = Math.round(total / values.length);
    var max = Math.max.apply(null, values);
    var moySc = Math.round(scores.reduce(function(a,b){return a+b;},0) / scores.length);
    var u = {calories:"kcal",score:"/100",nb:"alim.",proteines:"g"}[crit] || "";
    var col = {calories:"#1a372f",score:"#4caf50",nb:"#a78bfa",proteines:"#60a5fa"}[crit] || "#1a372f";
    var dark = document.body.classList.contains('dark');
    var rows = [["Nb repas",cards.length,"",dark?"#e2e8f0":"#1a372f",dark?"#1e2a3a":"#e8f0e9"],["Moyenne",moy,u,dark?"#60a5fa":col,dark?"#1a2433":"#f9f6f2"],["Maximum",max,u,"#7c5cbf","#eeedfe"],["Score eco.",moySc,"/100",moySc>=60?"#1a372f":"#8a2020",moySc>=60?"#e8f0e9":"#faeaea"]];
    document.getElementById("statsMetriques").innerHTML = rows.map(function(r) {
        return "<div style=\"background:"+r[4]+";border-radius:12px;padding:14px;text-align:center;\">"
             + "<p style=\"font-size:22px;font-weight:700;color:"+r[3]+";font-family:'Cormorant Garamond',serif;margin:0;\">"+r[1]+"</p>"
             + "<p style=\"font-size:11px;color:#6b7280;margin:2px 0 0;\">"+r[0]+" <span style=\"opacity:.7;\">"+r[2]+"</span></p>"
             + "</div>";
    }).join("");
    var ptC = scores.map(function(s){return s>=60?"#4caf50":s>=40?"#EF9F27":"#E24B4A";});
    var bC  = scores.map(function(s){return s>=60?(dark?"rgba(96,165,250,.8)":"rgba(26,55,47,.8)"):s>=40?"rgba(239,159,39,.8)":(dark?"rgba(167,139,250,.8)":"rgba(226,75,74,.8)");});
    var mp  = Math.round(prots.reduce(function(a,b){return a+b;},0)/prots.length);
    var rd  = [Math.min(100,mp*3),Math.min(100,moySc),75,65,50];
    if (statsRadarInst) statsRadarInst.destroy();
    statsRadarInst = new Chart(document.getElementById("statsRadar").getContext("2d"),{type:"radar",data:{labels:["Prot.","Score eco.","Lipides","Glucides","Sodium"],datasets:[{data:rd,borderColor:dark?"#e2e8f0":"#1a372f",backgroundColor:dark?"rgba(226,232,240,0.1)":"rgba(26,55,47,0.15)",pointBackgroundColor:dark?"#e2e8f0":"#1a372f",pointRadius:4},{data:[70,70,60,70,50],borderColor:"rgba(160,160,160,0.5)",backgroundColor:"transparent",borderDash:[5,4],pointRadius:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{r:{min:0,max:100,ticks:{display:false},grid:{color:"rgba(180,180,180,0.2)"},pointLabels:{font:{size:11},color:"#6b7280"}}}}});
    document.getElementById("statsEvoLabel").textContent={calories:"Evolution calories",score:"Evolution score eco.",nb:"Evolution nb aliments",proteines:"Evolution proteines"}[crit]||"";
    if (statsEvoInst) statsEvoInst.destroy();
    statsEvoInst = new Chart(document.getElementById("statsEvo").getContext("2d"),{type:"line",data:{labels:dates,datasets:[{data:values,borderColor:dark?"#e2e8f0":"#1a372f",backgroundColor:dark?"rgba(226,232,240,0.06)":"rgba(26,55,47,0.08)",fill:true,tension:0.4,pointBackgroundColor:ptC,pointBorderColor:"#fff",pointBorderWidth:2,pointRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:"rgba(180,180,180,0.15)"},ticks:{font:{size:10},color:"#9ca3af"}},x:{grid:{display:false},ticks:{font:{size:10},color:"#9ca3af",maxRotation:30}}}}});
    document.getElementById("statsBarLabel").textContent={calories:"Calories par repas",score:"Score eco.",nb:"Nb aliments",proteines:"Proteines"}[crit]||"";
    if (statsBarInst) statsBarInst.destroy();
    statsBarInst = new Chart(document.getElementById("statsBar").getContext("2d"),{type:"bar",data:{labels:labels,datasets:[{data:values,backgroundColor:bC,borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:"rgba(180,180,180,0.15)"},ticks:{font:{size:10},color:"#9ca3af"}},x:{grid:{display:false},ticks:{font:{size:10},color:"#9ca3af",autoSkip:false,maxRotation:30}}}}});
}

/* === RECOMMANDATIONS IA === */
function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

async function genRecommandationsIA(totaux, alimList, moment, saison, score) {
    var rcP=document.getElementById("recosPanel"), rcList=document.getElementById("recosList"), ctxInfo=document.getElementById("contextInfo");
    rcP.style.display="block";
    rcList.innerHTML="<div style=\"display:flex;align-items:center;gap:10px;padding:12px 14px;background:#f0f7ff;border-radius:12px;color:#1a4a7a;font-size:12px;border:1px solid #b0d0f0;\">"
        +"<div style=\"width:16px;height:16px;border:2px solid #60a5fa;border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;\"></div>"
        +"<span>Gemini AI analyse votre repas...</span></div>"
        +"<style>@keyframes spin{to{transform:rotate(360deg)}}</style>";
    ctxInfo.textContent="Analyse IA . "+moment.label+" . "+saison.label;
    var payload={aliments:alimList,totaux:totaux,moment:moment.key,saison:saison.key,score_eco:score};
    try {
        var response=await fetch("../../../api/recommandation_ia.php",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload)});
        if (!response.ok) throw new Error("HTTP "+response.status);
        var data=await response.json();
        if (data.error) throw new Error(data.error);
        var html="";
        if (data.bilan) html+="<div style=\"padding:10px 14px;border-radius:12px;margin-bottom:8px;background:#e8f0e9;color:#1a372f;font-size:12px;font-weight:600;border:1px solid #1a372f30;\">"+escapeHtml(data.bilan)+"</div>";
        if (data.problemes&&data.problemes.length>0) data.problemes.forEach(function(p){html+="<div class=\"alerte-item alerte-erreur\"><span>&#9888;&#65039;</span><span>"+escapeHtml(p)+"</span></div>";});
        if (data.recommandations&&data.recommandations.length>0) data.recommandations.forEach(function(r){html+="<div class=\"reco-item\"><span style=\"font-size:15px;flex-shrink:0;\">"+(r.emoji||"&#128161;")+"</span><div><strong style=\"display:block;margin-bottom:2px;\">"+escapeHtml(r.titre||"")+"</strong>"+escapeHtml(r.texte||"")+"</div></div>";});
        if (data.conseil_saison&&data.conseil_saison.texte) html+="<div class=\"alerte-item alerte-warning\"><span>"+(data.conseil_saison.emoji||"&#127807;")+"</span><span>"+escapeHtml(data.conseil_saison.texte)+"</span></div>";
        if (data.note_eco) html+="<div class=\"reco-item\" style=\"background:#e8f0e9;color:#1a372f;border-color:#1a372f30;\"><span>&#127757;</span><span>"+escapeHtml(data.note_eco)+"</span></div>";
        html+="<p style=\"font-size:10px;color:#9ca3af;margin-top:6px;text-align:right;\">&#10024; Gemini 2.0 Flash</p>";
        rcList.innerHTML=html;
    } catch(err) {
        console.warn("IA indisponible:",err.message);
        rcList.innerHTML="<div class=\"alerte-item alerte-warning\"><span>&#9888;</span><span>Analyse IA indisponible. Verifiez votre cle API.</span></div>";
    }
}


/* ================================================================
   CULTURE DU JOUR — Visuel 3 (panneau lateral)
   ================================================================ */

var cultureCharge = false;
var cultureData   = null;

function toggleCulture() { showTab('culture'); }

function showTab(tab) {
    var vA = document.getElementById('voletAliments');
    var vC = document.getElementById('voletCulture');
    var tA = document.getElementById('tabAliments');
    var tC = document.getElementById('tabCulture');
    if (tab === 'culture') {
        vA.style.display = 'none';
        vC.style.display = 'block';
        var dark = document.body.classList.contains('dark');
        var actif = dark ? '#60a5fa' : 'var(--vert)';
        var inactifBg = dark ? 'var(--bg-card)' : 'white';
        var inactifCol = dark ? 'var(--text-muted)' : 'var(--vert)';
        tA.style.background = inactifBg;  tA.style.color = inactifCol; tA.style.borderColor = '#d0c8be';
        tC.style.background = actif; tC.style.color = 'white'; tC.style.borderColor = actif;
        if (!cultureCharge) chargerCulture();
    } else {
        vC.style.display = 'none';
        vA.style.display = 'block';
        var dark2 = document.body.classList.contains('dark');
        var actif2 = dark2 ? '#60a5fa' : 'var(--vert)';
        var inactifBg2 = dark2 ? 'var(--bg-card)' : 'white';
        var inactifCol2 = dark2 ? 'var(--text-muted)' : 'var(--vert)';
        tC.style.background = inactifBg2; tC.style.color = inactifCol2; tC.style.borderColor = '#d0c8be';
        tA.style.background = actif2; tA.style.color = 'white'; tA.style.borderColor = actif2;
    }
}

function chargerCulture() {
    document.getElementById('cultureLoading').style.display = 'block';
    document.getElementById('cultureContent').style.display = 'none';
    fetch('../../../api/culture_ia.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            cultureData = data; cultureCharge = true;
            afficherCulture(data);
            var labels = {'mythe':'Mythe','proverbe':'Proverbe','fait_historique':'Histoire','chiffre':'Chiffre','etude_scientifique':'Etude'};
            var lbl = document.getElementById('cultureTypeLabel');
            if (lbl) lbl.textContent = '- ' + (labels[data.type] || data.type);
        })
        .catch(function() { afficherCultureErreur(); });
}

function afficherCulture(d) {
    document.getElementById('cultureLoading').style.display = 'none';
    var typeConf = {
        'mythe':              {bg:'#FAEEDA',border:'#FAC775',tc:'#633806',icon:'fa-ghost'},
        'proverbe':           {bg:'#EAF3DE',border:'#97C459',tc:'#27500A',icon:'fa-quote-left'},
        'fait_historique':    {bg:'#E6F1FB',border:'#85B7EB',tc:'#0C447C',icon:'fa-landmark'},
        'chiffre':            {bg:'#EEEDFE',border:'#AFA9EC',tc:'#3C3489',icon:'fa-chart-bar'},
        'etude_scientifique': {bg:'#E1F5EE',border:'#5DCAA5',tc:'#085041',icon:'fa-microscope'},
    };
    var cf = typeConf[d.type] || typeConf['mythe'];
    var sentConf = {
        'inspirant': {bg:'#EAF3DE',tc:'#27500A',bar:'#639922'},
        'alarmant':  {bg:'#FCEBEB',tc:'#791F1F',bar:'#E24B4A'},
        'surprenant':{bg:'#FAEEDA',tc:'#633806',bar:'#EF9F27'},
        'neutre':    {bg:'#E6F1FB',tc:'#0C447C',bar:'#378ADD'},
    };
    var ton = (d.sentiment && d.sentiment.ton) ? d.sentiment.ton : 'neutre';
    var sc  = sentConf[ton] || sentConf['neutre'];
    var pct = (d.sentiment && d.sentiment.score) ? Math.min(100, Math.round(d.sentiment.score)) : 70;
    var typeLbl = {'mythe':'Mythe','proverbe':'Proverbe','fait_historique':'Fait historique','chiffre':'Chiffre surprenant','etude_scientifique':'Etude scientifique'}[d.type] || d.type;
    var tonLbl  = {'inspirant':'Inspirant','alarmant':'Alarmant','surprenant':'Surprenant','neutre':'Neutre'}[ton] || 'Neutre';

    var chiffreHTML = '';
    if (d.chiffre_cle && d.chiffre_cle !== 'null' && d.chiffre_cle !== null) {
        chiffreHTML = '<div style="text-align:center;padding:10px;background:'+cf.bg+';border-radius:10px;margin-bottom:10px;border:0.5px solid '+cf.border+';">'
                    + '<p style="font-size:26px;font-weight:700;color:'+cf.tc+';font-family:\'Cormorant Garamond\',serif;margin:0;">'+escH(d.chiffre_cle)+'</p>'
                    + '</div>';
    }

    var html =
        '<div style="background:'+cf.bg+';border-radius:12px 12px 0 0;padding:11px 13px;border:0.5px solid '+cf.border+';border-bottom:none;">'
      +   '<div style="display:flex;align-items:center;justify-content:space-between;">'
      +     '<div style="display:flex;align-items:center;gap:8px;">'
      +       '<div style="width:28px;height:28px;border-radius:50%;background:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
      +         '<i class="fas '+cf.icon+'" style="color:'+cf.tc+';font-size:12px;"></i>'
      +       '</div>'
      +       '<p style="font-size:12px;font-weight:700;color:'+cf.tc+';margin:0;">'+typeLbl+'</p>'
      +     '</div>'
      +     '<button onclick="chargerCulture()" style="background:none;border:0.5px solid '+cf.border+';cursor:pointer;font-size:11px;color:'+cf.tc+';padding:4px 8px;border-radius:6px;">'
      +       '<i class="fas fa-sync-alt" style="font-size:10px;"></i> Nouveau'
      +     '</button>'
      +   '</div>'
      + '</div>'
      + '<div style="background:white;border:0.5px solid '+cf.border+';border-top:none;border-radius:0 0 12px 12px;padding:12px 13px;margin-bottom:10px;">'
      +   chiffreHTML
      +   '<p style="font-size:13px;font-weight:700;color:var(--vert);margin:0 0 7px;line-height:1.4;">'+escH(d.titre)+'</p>'
      +   '<p style="font-size:11px;color:#6b7280;line-height:1.6;margin:0 0 8px;">'+escH(d.resume)+'</p>';

    if (d.detail && d.detail.length > 10) {
        html += '<button onclick="toggleDetailCulture(this)" data-detail="'+escA(d.detail)+'" data-source="'+escA(d.source||'')+'"'
             +  ' style="font-size:11px;padding:4px 10px;border:0.5px solid #d0c8be;border-radius:8px;background:transparent;color:var(--vert);cursor:pointer;display:inline-flex;align-items:center;gap:5px;">'
             +  '<i class="fas fa-book-open" style="font-size:10px;"></i> Lire la suite</button>'
             +  '<div class="detailExpand" style="display:none;margin-top:8px;padding:10px;background:var(--sable);border-radius:8px;font-size:11px;color:#6b7280;line-height:1.7;"></div>';
    }
    html += '</div>';

    html += '<div style="background:white;border:0.5px solid #ede8e0;border-radius:10px;padding:10px 12px;margin-bottom:8px;">'
          +   '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">'
          +     '<p style="font-size:10px;font-weight:700;color:var(--vert);margin:0;text-transform:uppercase;letter-spacing:.04em;">Analyse sentimentale</p>'
          +     '<span style="font-size:10px;padding:2px 8px;border-radius:99px;background:'+sc.bg+';color:'+sc.tc+';font-weight:600;">'+tonLbl+' '+pct+'%</span>'
          +   '</div>'
          +   '<div style="height:5px;border-radius:3px;background:#ede8e0;overflow:hidden;">'
          +     '<div style="width:'+pct+'%;height:100%;border-radius:3px;background:'+sc.bar+';"></div>'
          +   '</div>'
          + '</div>';

    if (d.defi) {
        html += '<div style="background:#EAF3DE;border-radius:10px;padding:10px 12px;display:flex;align-items:flex-start;gap:8px;">'
              +   '<i class="fas fa-trophy" style="color:#27500A;font-size:14px;flex-shrink:0;margin-top:2px;"></i>'
              +   '<div>'
              +     '<p style="font-size:11px;font-weight:700;color:#27500A;margin:0;">Defi du jour</p>'
              +     '<p style="font-size:11px;color:#3B6D11;margin:3px 0 0;line-height:1.5;">'+escH(d.defi)+'</p>'
              +   '</div>'
              + '</div>';
    }

    document.getElementById('cultureContent').innerHTML = html;
    document.getElementById('cultureContent').style.display = 'block';
}

function toggleDetailCulture(btn) {
    var detail = btn.nextElementSibling;
    var isOpen = detail.style.display === 'block';
    if (!isOpen) {
        var src = btn.dataset.source ? '<br><span style="font-size:10px;opacity:.7;">Source : ' + escH(btn.dataset.source) + '</span>' : '';
        detail.innerHTML = escH(btn.dataset.detail) + src;
    }
    detail.style.display = isOpen ? 'none' : 'block';
    btn.innerHTML = isOpen
        ? '<i class="fas fa-book-open" style="font-size:10px;"></i> Lire la suite'
        : '<i class="fas fa-chevron-up" style="font-size:10px;"></i> Reduire';
}

function afficherCultureErreur() {
    document.getElementById('cultureLoading').style.display = 'none';
    document.getElementById('cultureContent').innerHTML =
        '<div style="padding:14px;background:#faeaea;border-radius:10px;color:#8a2020;font-size:12px;text-align:center;">'
      + '<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>'
      + 'Culture IA indisponible. Verifiez votre cle API dans culture_ia.php'
      + '</div>';
    document.getElementById('cultureContent').style.display = 'block';
}

function escH(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escA(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}