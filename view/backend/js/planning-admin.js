// planning-admin.js — VERSION CORRIGEE
// Corrections :
//   1. data.filter is not a function : result.data peut etre undefined si PHP retourne une erreur
//      → normalisation defensive de result.data avant tout usage
//   2. Protection sur updateStats() et renderPlanningTable() si data n'est pas un tableau

console.log('📅 Planning Admin JS charge');

let planningAllData     = [];
let planningFiltre      = 'all';
let planningInitialized = false;
let planningSort        = { col: 'id', dir: 'desc' };  // tri actif par défaut : ID décroissant

// ── Anomalies IA — état global ────────────────────────────────────────────
let _anomaliesDetectees = [];   // résultats du dernier scan
const GROQ_API_KEY = 'CLEAPI';
const GROQ_MODEL   = 'llama3-8b-8192';

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

/* ══════════════════════════════════════════════════════════════════════════
   PLANNING STATS — Moteur d'animation professionnel
══════════════════════════════════════════════════════════════════════════ */

/**
 * Anime un compteur numérique de 0 vers la valeur cible.
 * @param {string} id       – ID de l'élément DOM
 * @param {number} target   – valeur finale
 * @param {number} duration – durée ms (défaut 900)
 * @param {string} [suffix] – suffixe optionnel (ex: "%")
 */
function animateCounter(id, target, duration = 900, suffix = '') {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('pstats-skeleton');
    const start = performance.now();
    const from  = parseFloat(el.dataset.current || 0) || 0;
    el.dataset.current = target;

    function step(now) {
        const p  = Math.min((now - start) / duration, 1);
        // easeOutExpo
        const ep = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
        const v  = from + (target - from) * ep;
        el.textContent = (Number.isInteger(target) ? Math.round(v) : v.toFixed(1)) + suffix;
        if (p < 1) requestAnimationFrame(step);
        else {
            el.textContent = target + suffix;
            el.classList.remove('pstats-skeleton');
            el.classList.add('updating');
            setTimeout(() => el.classList.remove('updating'), 400);
        }
    }
    requestAnimationFrame(step);
}

/**
 * Met à jour le mini anneau SVG de progression dans chaque KPI card.
 */
function animateRing(id, pct) {
    const el = document.getElementById(id);
    if (!el) return;
    const circ = 2 * Math.PI * 15.5; // r=15.5 → ≈97.4
    const fill = (pct / 100) * circ;
    // délai pour permettre le rendu initial
    setTimeout(() => {
        el.style.transition = 'stroke-dasharray 1.2s cubic-bezier(.4,0,.2,1)';
        el.setAttribute('stroke-dasharray', `${fill} ${circ}`);
    }, 200);
}

/**
 * Met à jour le donut chart de répartition des statuts.
 */
function animateDonut(approuve, attente, rejete, total) {
    const circ = 2 * Math.PI * 48; // r=48 → ≈301.6
    let offset = 0;

    function setArc(id, count) {
        const el = document.getElementById(id);
        if (!el) return;
        const fill = total > 0 ? (count / total) * circ : 0;
        el.style.strokeDashoffset = offset;
        el.setAttribute('stroke-dasharray', '0 ' + circ); // reset
        setTimeout(() => {
            el.setAttribute('stroke-dasharray', `${fill} ${circ - fill}`);
            el.style.strokeDashoffset = -offset;
        }, 300);
        offset += fill;
    }

    setArc('donut-approuve', approuve);
    setArc('donut-attente',  attente);
    setArc('donut-rejete',   rejete);

    // Centre
    const centerEl = document.getElementById('donut-center-val');
    if (centerEl) { centerEl.textContent = total; }
    const badgeEl = document.getElementById('donut-total-badge');
    if (badgeEl) { badgeEl.textContent = total + ' total'; }

    // Légende
    const setLeg = (id, n) => { const e = document.getElementById(id); if(e) e.textContent = n; };
    setLeg('legend-approuve', approuve);
    setLeg('legend-attente',  attente);
    setLeg('legend-rejete',   rejete);
}

/**
 * Anime les barres de budget (quotidien / hebdo).
 */
function animateBars(quotidien, hebdo) {
    const total = quotidien + hebdo || 1;
    const pctQ  = (quotidien / total) * 100;
    const pctH  = (hebdo    / total) * 100;

    setTimeout(() => {
        const bq = document.getElementById('bar-quotidien');
        const bh = document.getElementById('bar-hebdo');
        if (bq) bq.style.width = Math.max(pctQ, 4) + '%';
        if (bh) bh.style.width = Math.max(pctH, 4) + '%';
        const tq = document.getElementById('bar-quotidien-tip');
        const th = document.getElementById('bar-hebdo-tip');
        if (tq) tq.textContent = quotidien + ' demande' + (quotidien > 1 ? 's' : '');
        if (th) th.textContent = hebdo     + ' demande' + (hebdo     > 1 ? 's' : '');
    }, 400);

    const vq = document.getElementById('bar-quotidien-val');
    const vh = document.getElementById('bar-hebdo-val');
    if (vq) vq.textContent = quotidien;
    if (vh) vh.textContent = hebdo;
}

/**
 * Génère et anime les mini barres de calories.
 */
function animateMiniCalBars(data) {
    const container = document.getElementById('pstats-cal-bars');
    if (!container) return;

    // Grouper par semaine (approximation : tranches de 7 dem.)
    const buckets = [];
    const step = Math.max(1, Math.ceil(data.length / 7));
    for (let i = 0; i < data.length; i += step) {
        const slice = data.slice(i, i + step);
        const avg   = slice.reduce((s, d) => s + (parseInt(d.calories) || 0), 0) / (slice.length || 1);
        buckets.push(Math.round(avg));
    }
    if (buckets.length === 0) buckets.push(0);

    const maxV = Math.max(...buckets, 1);
    container.innerHTML = buckets.map((v, idx) => {
        const pct = (v / maxV) * 100;
        return `<div class="pstats-mini-bar-col">
            <div class="pstats-mini-bar-fill" id="mcb-${idx}" style="height:0;max-height:40px" title="${v.toLocaleString('fr')} kcal"></div>
            <div class="pstats-mini-bar-lbl">S${idx + 1}</div>
        </div>`;
    }).join('');

    // Anime après rendu
    setTimeout(() => {
        buckets.forEach((v, idx) => {
            const el = document.getElementById(`mcb-${idx}`);
            if (el) el.style.height = Math.max((v / maxV) * 40, 3) + 'px';
        });
    }, 500 + buckets.length * 30);
}

/**
 * Anime l'aiguille et l'arc du gauge d'approbation.
 */
function animateGauge(pct) {
    const arcEl    = document.getElementById('gauge-arc');
    const needleEl = document.getElementById('gauge-needle');
    const valEl    = document.getElementById('gauge-val');

    const arcLen = 283; // longueur approx de l'arc demi-cercle r=90

    setTimeout(() => {
        if (arcEl) {
            const fill = (pct / 100) * arcLen;
            arcEl.setAttribute('stroke-dasharray', `${fill} ${arcLen}`);
        }
        if (needleEl) {
            // angle : 0% = -90deg (gauche) → 100% = +90deg (droite)
            const deg = -90 + (pct / 100) * 180;
            needleEl.style.transform = `rotate(${deg}deg)`;
        }
        if (valEl) valEl.textContent = Math.round(pct) + '%';
    }, 350);
}

/**
 * Fonction principale appelée après chargement des données.
 */
function updateStats(data) {
    if (!Array.isArray(data)) data = [];

    // ── Calculs ──────────────────────────────────────────────────────────
    const total     = data.length;
    const approuve  = data.filter(d => d.statut === 'approuve').length;
    const attente   = data.filter(d => d.statut === 'en_attente').length;
    const rejete    = data.filter(d => d.statut === 'rejete').length;
    const quotidien = data.filter(d => d.type_budget === 'quotidien').length;
    const hebdo     = data.filter(d => d.type_budget === 'hebdomadaire').length;

    const totalJours = data.reduce((s, d) => {
        return s + (d.type_duree === 'semaines' ? (parseInt(d.duree) || 0) * 7 : (parseInt(d.duree) || 0));
    }, 0);

    const calAvg    = total > 0 ? Math.round(data.reduce((s, d) => s + (parseInt(d.calories) || 0), 0) / total) : 0;
    const budgetAvg = total > 0 ? (data.reduce((s, d) => s + (parseFloat(d.budget) || 0), 0) / total).toFixed(1) : '0.0';
    const dureeAvg  = total > 0 ? Math.round(totalJours / total) : 0;
    const tauxApprob = total > 0 ? (approuve / total) * 100 : 0;

    // ── KPI counters ─────────────────────────────────────────────────────
    animateCounter('stat-total',      total);
    animateCounter('stat-approuve',   approuve);
    animateCounter('stat-en-attente', attente);
    animateCounter('stat-jours',      totalJours);

    // Maintient la compatibilité avec les IDs legacy si présents dans d'autres modules
    const legacyQ = document.getElementById('stat-quotidien');
    const legacyH = document.getElementById('stat-hebdo');
    if (legacyQ) legacyQ.textContent = quotidien;
    if (legacyH) legacyH.textContent = hebdo;

    // ── Rings (progress circles sur les KPI) ─────────────────────────────
    const maxJours = Math.max(totalJours, 365);
    animateRing('ring-total',    Math.min((total    / 50)  * 100, 100));
    animateRing('ring-approuve', total > 0 ? (approuve / total) * 100 : 0);
    animateRing('ring-attente',  total > 0 ? (attente  / total) * 100 : 0);
    animateRing('ring-jours',    Math.min((totalJours / maxJours) * 100, 100));

    // ── Donut chart ───────────────────────────────────────────────────────
    animateDonut(approuve, attente, rejete, total);

    // ── Bar chart budget ──────────────────────────────────────────────────
    animateBars(quotidien, hebdo);

    // ── Mini bars calories ────────────────────────────────────────────────
    animateMiniCalBars(data);

    // ── Gauge taux d'approbation ──────────────────────────────────────────
    animateGauge(tauxApprob);

    // ── Stats secondaires ─────────────────────────────────────────────────
    const setSec = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
    setSec('sec-cal-avg',    calAvg.toLocaleString('fr'));
    setSec('sec-budget-avg', budgetAvg);
    setSec('sec-duree-avg',  dureeAvg);
}

// ══════════════════════════════════════════════════════════════════════════
// 🔍 DÉTECTION D'ANOMALIES IA — Règles métier locales
// ══════════════════════════════════════════════════════════════════════════

function detecterAnomaliesLocales(data) {
    const anomalies = [];
    data.forEach(d => {
        const id         = d.id;
        const cal        = parseInt(d.calories)  || 0;
        const budget     = parseFloat(d.budget)  || 0;
        const dureeRaw   = parseInt(d.duree)     || 0;
        const dureeJ     = d.type_duree === 'semaines' ? dureeRaw * 7 : dureeRaw;
        const typeBudget = d.type_budget || '';

        if (cal > 0 && cal < 800)
            anomalies.push({ id, icon:'🥵', message:'Calories dangereusement basses',
                details:`${cal.toLocaleString('fr')} kcal/j — minimum recommandé : 800 kcal` });

        if (cal > 5000)
            anomalies.push({ id, icon:'🔥', message:'Apport calorique excessif',
                details:`${cal.toLocaleString('fr')} kcal/j — dépasse 5 000 kcal` });

        if (budget <= 0)
            anomalies.push({ id, icon:'🚫', message:'Budget nul ou invalide',
                details:`Budget = ${budget} €` });

        if (cal >= 1500 && budget > 0) {
            if (typeBudget === 'quotidien' && budget < 2)
                anomalies.push({ id, icon:'💸', message:'Budget quotidien insuffisant pour ces calories',
                    details:`${cal.toLocaleString('fr')} kcal/j avec ${budget.toFixed(2)} €/j` });
            if (typeBudget === 'hebdomadaire' && budget < 10)
                anomalies.push({ id, icon:'💸', message:'Budget hebdomadaire insuffisant vs calories',
                    details:`${cal.toLocaleString('fr')} kcal/j avec ${budget.toFixed(2)} €/sem` });
        }

        if (dureeJ > 0 && dureeJ < 3 && cal > 3000)
            anomalies.push({ id, icon:'⏱️', message:'Durée très courte avec calories élevées',
                details:`${dureeJ} jour(s) pour ${cal.toLocaleString('fr')} kcal/j` });

        if (dureeRaw <= 0)
            anomalies.push({ id, icon:'📅', message:'Durée invalide ou manquante',
                details:`Durée = ${d.duree || '—'} ${d.type_duree || ''}` });
    });
    return anomalies;
}

/**
 * Appel Groq API pour analyse sémantique d'une sélection de demandes suspectes.
 * Retourne les anomalies enrichies avec explication IA.
 */
async function analyserAvecGroq(suspects) {
    if (!suspects.length) return [];

    const lignes = suspects.map(d =>
        `ID#${d.id}: ${d.calories} kcal/j | budget ${d.budget}€ ${d.type_budget} | durée ${d.duree} ${d.type_duree}`
    ).join('\n');

    const prompt = `Tu es un expert en nutrition et planification alimentaire.
Analyse ces demandes de planning nutritionnel et identifie les incohérences ou anomalies.
Pour chaque anomalie, réponds UNIQUEMENT en JSON valide, tableau d'objets avec les champs:
id (number), icon (emoji), message (string court), details (string explicatif).

Demandes à analyser:
${lignes}

Réponds uniquement avec le tableau JSON, sans texte avant ou après.`;

    const resp = await fetch('https://api.groq.com/openai/v1/chat/completions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${GROQ_API_KEY}`
        },
        body: JSON.stringify({
            model: GROQ_MODEL,
            max_tokens: 1024,
            temperature: 0.2,
            messages: [{ role: 'user', content: prompt }]
        })
    });

    if (!resp.ok) throw new Error(`Groq API HTTP ${resp.status}`);
    const data = await resp.json();
    const text = data.choices?.[0]?.message?.content || '[]';

    // Nettoyer la réponse (enlever éventuels backticks markdown)
    const clean = text.replace(/```json|```/gi, '').trim();
    const parsed = JSON.parse(clean);
    return Array.isArray(parsed) ? parsed : [];
}

/**
 * Marque les lignes de la table avec le badge ⚠️ Anomalie IA.
 * Appelée AVANT renderPlanningTable pour que la déclaration soit disponible.
 */
function marquerLignesAnomalies(anomalies) {
    const byId = {};
    anomalies.forEach(a => {
        if (!byId[a.id]) byId[a.id] = [];
        byId[a.id].push(a.message);
    });

    const tbody = document.getElementById('planningTableBody');
    if (!tbody) return;

    tbody.querySelectorAll('tr').forEach(tr => {
        const firstTd = tr.querySelector('td:first-child');
        if (!firstTd) return;
        const match = firstTd.textContent.match(/#(\d+)/);
        if (!match) return;
        const id = parseInt(match[1]);
        if (!byId[id]) return;

        const old = firstTd.querySelector('.badge-anomalie');
        if (old) old.remove();

        const badge = document.createElement('span');
        badge.className = 'badge-anomalie';
        badge.title = byId[id].join('\n');
        badge.innerHTML = '⚠️ Anomalie IA';
        firstTd.appendChild(badge);
    });
}

/**
 * Affiche le panel résultats anomalies au-dessus de la table.
 */
function afficherAnomaliesPanel(anomalies, total) {
    const panel = document.getElementById('anomaliesPanel');
    if (!panel) return;

    if (!anomalies.length) {
        panel.style.display = 'none';
        panel.innerHTML = '';
        return;
    }

    const idsUniq = [...new Set(anomalies.map(a => a.id))];
    const itemsHTML = anomalies.map(a => `
        <div class="anomaly-item" onclick="voirDetailPlanning(${a.id})" title="Ouvrir demande #${a.id}">
            <span class="anomaly-item-ico">${a.icon || '⚠️'}</span>
            <div class="anomaly-item-body">
                <div class="anomaly-item-id">Demande #${a.id}</div>
                <div class="anomaly-item-msg">${a.message}</div>
                <div class="anomaly-item-vals">${a.details || ''}</div>
            </div>
            <span class="anomaly-item-arrow">›</span>
        </div>`).join('');

    panel.innerHTML = `
        <div class="anomaly-panel-hd">
            <div class="anomaly-panel-title"><span>⚠️</span> Anomalies IA détectées</div>
            <button class="anomaly-panel-close" onclick="fermerAnomaliesPanel()">✕ Fermer</button>
        </div>
        <div class="anomaly-summary">
            <div class="anomaly-stat"><strong>${anomalies.length}</strong><span>anomalie${anomalies.length>1?'s':''}</span></div>
            <div class="anomaly-stat"><strong>${idsUniq.length}</strong><span>demande${idsUniq.length>1?'s':''} concernée${idsUniq.length>1?'s':''}</span></div>
            <div class="anomaly-stat"><strong>${total}</strong><span>analysées</span></div>
            <div class="anomaly-stat"><strong>${Math.round(idsUniq.length/total*100)}%</strong><span>taux anomalie</span></div>
        </div>
        <div class="anomaly-list">${itemsHTML}</div>`;

    panel.style.display = 'block';
}

window.fermerAnomaliesPanel = function() {
    const panel = document.getElementById('anomaliesPanel');
    if (panel) { panel.style.display='none'; panel.innerHTML=''; }
    _anomaliesDetectees = [];
    document.querySelectorAll('.badge-anomalie').forEach(b => b.remove());
};

/**
 * Point d'entrée principal — animation Siri + Groq API + affichage.
 */
window.lancerScanAnomalies = async function() {
    const data = planningAllData;
    if (!Array.isArray(data) || data.length === 0) {
        showPlanningToast('Aucune donnée à analyser — chargez d\'abord les demandes', 'err');
        return;
    }

    const overlay   = document.getElementById('siriScanOverlay');
    const statusEl  = document.getElementById('siriStatusText');
    const subEl     = document.getElementById('siriSubText');
    const fillEl    = document.getElementById('siriProgressFill');
    const counterEl = document.getElementById('siriCounter');
    const btn       = document.getElementById('btnScanAnomalies');

    if (!overlay) { console.error('❌ #siriScanOverlay introuvable dans le DOM'); return; }

    // ── 1. Afficher l'overlay Siri ────────────────────────────────────────
    if (btn) { btn.disabled = true; btn.style.opacity = '.5'; }
    overlay.classList.add('visible');

    const setStatus = (s, sub, pct) => {
        if (statusEl)  statusEl.textContent  = s;
        if (subEl)     subEl.textContent      = sub;
        if (fillEl)    fillEl.style.width     = pct + '%';
        if (counterEl) counterEl.textContent  = `${pct}% — ${data.length} demandes`;
    };

    setStatus('INITIALISATION…', 'Démarrage du scanner IA…', 0);
    await new Promise(r => setTimeout(r, 400));

    // ── 2. Phase locale (règles métier) ───────────────────────────────────
    setStatus('ANALYSE EN COURS', 'Vérification des règles métier…', 15);
    await new Promise(r => setTimeout(r, 500));

    const anomaliesLocales = detecterAnomaliesLocales(data);
    setStatus('ANALYSE EN COURS', `${anomaliesLocales.length} anomalie(s) locale(s) trouvée(s)…`, 40);
    await new Promise(r => setTimeout(r, 500));

    // ── 3. Phase Groq IA ──────────────────────────────────────────────────
    setStatus('IA EN COURS', 'Envoi vers Groq LLaMA 3…', 55);
    await new Promise(r => setTimeout(r, 300));

    let anomaliesGroq = [];
    try {
        // On envoie les 10 premières demandes en attente max (évite dépasser les tokens)
        const enAttente = data.filter(d => d.statut === 'en_attente').slice(0, 10);
        const cibles    = enAttente.length > 0 ? enAttente : data.slice(0, 10);

        setStatus('IA EN COURS', `Groq analyse ${cibles.length} demandes…`, 65);
        anomaliesGroq = await analyserAvecGroq(cibles);
        setStatus('IA EN COURS', `Groq : ${anomaliesGroq.length} anomalie(s) détectée(s)`, 80);
        await new Promise(r => setTimeout(r, 500));
    } catch (err) {
        console.warn('⚠️ Groq API indisponible, fallback local uniquement :', err.message);
        setStatus('IA EN COURS', 'Groq indisponible — résultats locaux uniquement', 80);
        await new Promise(r => setTimeout(r, 600));
    }

    // ── 4. Fusion & déduplication ─────────────────────────────────────────
    setStatus('FINALISATION…', 'Compilation du rapport…', 90);
    await new Promise(r => setTimeout(r, 400));

    // Fusionner sans doublon (même id + même message)
    const seen = new Set();
    const toutes = [...anomaliesLocales, ...anomaliesGroq].filter(a => {
        const k = `${a.id}|${a.message}`;
        if (seen.has(k)) return false;
        seen.add(k);
        return true;
    });

    fillEl.style.width = '100%';
    await new Promise(r => setTimeout(r, 300));

    if (toutes.length > 0) {
        statusEl.textContent = `⚠️ ${toutes.length} ANOMALIE${toutes.length > 1 ? 'S' : ''} TROUVÉE${toutes.length > 1 ? 'S' : ''}`;
    } else {
        statusEl.textContent = '✅ AUCUNE ANOMALIE';
    }
    subEl.textContent     = toutes.length > 0 ? 'Rapport disponible ci-dessous.' : 'Toutes les demandes sont cohérentes.';
    counterEl.textContent = `Analyse terminée — ${data.length} demandes traitées`;
    await new Promise(r => setTimeout(r, 1100));

    // ── 5. Fermer l'overlay ───────────────────────────────────────────────
    overlay.style.transition = 'opacity .5s ease';
    overlay.style.opacity    = '0';
    setTimeout(() => {
        overlay.classList.remove('visible');
        overlay.style.opacity    = '';
        overlay.style.transition = '';
    }, 500);

    // ── 6. Afficher les résultats ─────────────────────────────────────────
    _anomaliesDetectees = toutes;
    afficherAnomaliesPanel(toutes, data.length);
    marquerLignesAnomalies(toutes);

    if (btn) { btn.disabled = false; btn.style.opacity = ''; }

    showPlanningToast(
        toutes.length > 0
            ? `⚠️ ${toutes.length} anomalie(s) détectée(s)`
            : '✅ Aucune anomalie détectée',
        toutes.length > 0 ? 'err' : 'ok'
    );
};

// ══════════════════════════════════════════════════════════════════════════

function renderPlanningTable() {
    const tbody = document.getElementById('planningTableBody');
    if (!tbody) return;

    // CORRECTION : garde-fou si planningAllData a ete corrompu
    if (!Array.isArray(planningAllData)) planningAllData = [];

    const searchInput = document.getElementById('planningSearchInput');
    const searchFieldEl = document.getElementById('planningSearchField');
    const searchVal   = searchInput  ? searchInput.value.trim()  : '';
    const searchField = searchFieldEl ? searchFieldEl.value       : 'id';

    // ── Filtrage statut + recherche ciblée sur le champ choisi ──────────
    let data = planningAllData.filter(d => {
        // filtre statut
        if (planningFiltre !== 'all' && d.statut !== planningFiltre) return false;
        // pas de recherche → tout passe
        if (!searchVal) return true;

        // Valeur brute du champ, toujours traitée comme string pour .includes()
        let fieldVal;
        switch (searchField) {
            case 'id':
                fieldVal = String(d.id ?? '');
                break;
            case 'budget':
                // On retire les espaces/virgules pour robustesse, on compare les chiffres bruts
                fieldVal = String(d.budget ?? '').replace(',', '.');
                break;
            case 'calories':
                fieldVal = String(d.calories ?? '');
                break;
            default:
                fieldVal = '';
        }
        return fieldVal.toLowerCase().includes(searchVal.toLowerCase());
    });

    // ── Synchroniser planningSort.col depuis le select avant de trier ────
    const sortFieldEl = document.getElementById('planningSortField');
    if (sortFieldEl && sortFieldEl.value) {
        planningSort.col = sortFieldEl.value;
    }

    // ── Tri ──────────────────────────────────────────────────────────────
    const col = planningSort.col;
    const dir = planningSort.dir;

    data = data.slice().sort((a, b) => {
        let va, vb;
        switch (col) {
            case 'id':
                va = parseInt(a.id, 10)  || 0;
                vb = parseInt(b.id, 10)  || 0;
                break;
            case 'calories':
                va = parseFloat(a.calories) || 0;
                vb = parseFloat(b.calories) || 0;
                break;
            case 'budget':
                va = parseFloat(a.budget) || 0;
                vb = parseFloat(b.budget) || 0;
                break;
            case 'duree': {
                const toJ = x => x.type_duree === 'semaines'
                    ? (parseInt(x.duree, 10) || 0) * 7
                    : (parseInt(x.duree, 10) || 0);
                va = toJ(a); vb = toJ(b);
                break;
            }
            case 'statut': {
                const ord = { en_attente: 0, approuve: 1, rejete: 2 };
                va = ord[a.statut] ?? 9;
                vb = ord[b.statut] ?? 9;
                break;
            }
            case 'date':
                va = a.date_demande ? new Date(a.date_demande).getTime() : 0;
                vb = b.date_demande ? new Date(b.date_demande).getTime() : 0;
                break;
            default:
                va = 0; vb = 0;
        }
        if (va === vb) return 0;
        return dir === 'asc' ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
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

    // ── Ré-appliquer les badges anomalie si un scan a déjà eu lieu ────────
    if (_anomaliesDetectees.length > 0) {
        marquerLignesAnomalies(_anomaliesDetectees);
    }
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
    const hasPlanning = (pl && pl.length > 0) || (res.nb_lignes && res.nb_lignes > 0);
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
        const hasSS = !!(ss && ss.activite_sportive);
        const hasPlanningSS = hasPlanning;
        actBtns = `<button class="dw-btn dw-btn-ai" id="drawerAiBtn-${d.id}" onclick="drawerGenererIA(${d.id})" ${!hasSS ? 'disabled title="Sport &amp; Sommeil non rempli"' : ''}>✨ Générer par IA</button>
                   <button class="dw-btn dw-btn-blue" onclick="drawerRegen(${d.id})">🔄 Régénérer</button>
                   <button class="dw-btn dw-btn-trad" id="drawerTradBtn-${d.id}" onclick="drawerOuvrirTraduction(${d.id})" ${!hasPlanning ? 'disabled title="Générez d\'abord le planning IA"' : ''}>🌐 Traduire</button>
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

// ══════════════════════════════════════════════════════════════════════════
// ✨ GÉNÉRATION IA — Bouton drawer demande approuvée
// ══════════════════════════════════════════════════════════════════════════
window.drawerGenererIA = function(id) {
    const btn  = document.getElementById(`drawerAiBtn-${id}`);
    const body = document.getElementById('drawerBody');
    if (!body) return;

    if (!confirm(`Générer le planning IA pour la demande #${id} ?\n\nCela peut prendre 10–20 secondes.`)) return;

    // Bouton en chargement
    if (btn) { btn.disabled = true; btn.innerHTML = `<span class="ai-btn-spinner"></span> Génération…`; }

    // Panneau de progression
    let progressEl = document.getElementById(`ai-progress-${id}`);
    if (progressEl) progressEl.remove();
    progressEl = document.createElement('div');
    progressEl.id = `ai-progress-${id}`;
    progressEl.className = 'ai-progress-panel';
    progressEl.innerHTML = `
        <div class="ai-progress-inner">
            <div class="ai-prog-ico">🤖</div>
            <div class="ai-prog-text">
                <strong>Intelligence Artificielle au travail…</strong>
                <span id="ai-prog-step-${id}">Connexion à Groq LLaMA 3 70B…</span>
            </div>
        </div>
        <div class="ai-prog-bar-wrap"><div class="ai-prog-bar" id="ai-prog-bar-${id}" style="width:0"></div></div>`;
    body.insertAdjacentElement('afterbegin', progressEl);

    // Progression simulée
    const steps = [
        [12,'Analyse du profil utilisateur…'],
        [28,'Construction du prompt nutritionnel…'],
        [45,'Appel API Groq LLaMA 3 70B…'],
        [62,'Génération du planning jour par jour…'],
        [78,'Traitement de la réponse IA…'],
        [90,'Sauvegarde en base de données…'],
    ];
    let si = 0;
    const tick = setInterval(() => {
        if (si >= steps.length) return;
        const barEl  = document.getElementById(`ai-prog-bar-${id}`);
        const stepEl = document.getElementById(`ai-prog-step-${id}`);
        if (barEl)  barEl.style.width  = steps[si][0] + '%';
        if (stepEl) stepEl.textContent = steps[si][1];
        si++;
    }, 1700);

    fetch(`planning/ai_generate.php?action=generer&id=${id}`, {
        headers:{ 'X-Requested-With':'XMLHttpRequest' }
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(res => {
        clearInterval(tick);
        if (!res.success) throw new Error(res.error || 'Erreur IA');
        const barEl = document.getElementById(`ai-prog-bar-${id}`);
        if (barEl) barEl.style.width = '100%';
        setTimeout(() => {
            _renderAIResult(progressEl, res, id);
        }, 400);
        const row = planningAllData.find(x => x.id == id);
        if (row) row.nb_lignes_planning = res.nb_lignes;
        renderPlanningTable();
        showPlanningToast(res.message || '✅ Planning IA généré !', 'ok');
        if (btn) { btn.disabled = false; btn.innerHTML = '✨ Régénérer IA'; }
    })
    .catch(err => {
        clearInterval(tick);
        if (progressEl) progressEl.outerHTML = `
            <div class="ai-error-panel">
                <span style="font-size:1.5rem">❌</span>
                <div style="flex:1">
                    <strong style="display:block;color:#e74c3c;font-size:.87rem">Erreur de génération</strong>
                    <span style="font-size:.76rem;color:var(--muted)">${err.message}</span>
                </div>
                <button onclick="drawerGenererIA(${id})" class="dw-btn dw-btn-ai">Réessayer</button>
            </div>`;
        showPlanningToast('Erreur IA : ' + err.message, 'err');
        if (btn) { btn.disabled = false; btn.innerHTML = '✨ Générer par IA'; }
    });
};

function _renderAIResult(container, res, id) {
    const jours = res.planning?.jours || [];
    const bilan = res.planning?.bilan || '';
    if (!jours.length) { container.remove(); return; }

    const thJours = jours.map(j =>
        `<th class="ai-th-jour"><span style="font-weight:800">Jour ${j.jour}</span></th>`
    ).join('');

    const mkRow = (cls, ico, lbl, fn) => `<tr class="${cls}">
        <td class="ai-td-type"><span>${ico}</span>${lbl}</td>
        ${jours.map(j => { const v = fn(j); return `<td>${v || '<span style="opacity:.35">—</span>'}</td>`; }).join('')}
    </tr>`;

    const repasRow = (k, ico, lbl) => mkRow('ai-row-repas', ico, lbl, j => j.repas?.[k] || '');

    const html = `<div class="ai-result-panel">
        <div class="ai-result-hd">
            <span class="ai-result-badge">✨ Planning IA — ${jours.length} jours · ${res.nb_lignes} activités</span>
            <button onclick="voirDetailPlanning(${id})" class="ai-result-refresh">🔄 Recharger</button>
        </div>
        ${bilan ? `<div class="ai-bilan"><span>📊</span><p>${bilan}</p></div>` : ''}
        <div class="ai-table-scroll">
            <table class="ai-planning-table">
                <thead><tr><th class="ai-th-act">Activité</th>${thJours}</tr></thead>
                <tbody>
                    ${repasRow('petit_dejeuner','🌅','Petit-déj')}
                    ${repasRow('dejeuner','🍽️','Déjeuner')}
                    ${repasRow('diner','🌙','Dîner')}
                    ${repasRow('collation','🍎','Collation')}
                    ${mkRow('ai-row-sport','🏃','Sport', j => {
                        const s = j.sport; if (!s?.activite) return '';
                        return `<strong>${s.activite}</strong><br><small>${s.duree_min||0} min</small>${s.description ? `<br><small style="color:var(--muted)">${s.description}</small>` : ''}`;
                    })}
                    ${mkRow('ai-row-sommeil','🌙','Sommeil', j => {
                        const s = j.sommeil; if (!s) return '';
                        return `<small>🛌 ${s.heure_coucher||''}</small><br><small>☀️ ${s.heure_reveil||''}</small>${s.conseil ? `<br><small style="color:var(--muted)">${s.conseil}</small>` : ''}`;
                    })}
                    ${mkRow('ai-row-cal','🔥','Calories', j =>
                        j.calories_estimees ? `<strong>${parseInt(j.calories_estimees).toLocaleString('fr')} kcal</strong>` : ''
                    )}
                    ${mkRow('ai-row-conseil','💡','Conseil', j => j.conseil_jour ? `<em>${j.conseil_jour}</em>` : '')}
                </tbody>
            </table>
        </div>
    </div>`;

    container.outerHTML = html;
}

// ══════════════════════════════════════════════════════════════════════════
// 🌐 TRADUCTION IA — Sélecteur de langue + appel API
// ══════════════════════════════════════════════════════════════════════════
const LANGUES_DISPO = [
    { code: 'ar', label: '🇸🇦 Arabe',     dir: 'rtl' },
    { code: 'en', label: '🇬🇧 Anglais',   dir: 'ltr' },
    { code: 'es', label: '🇪🇸 Espagnol',  dir: 'ltr' },
    { code: 'de', label: '🇩🇪 Allemand',  dir: 'ltr' },
    { code: 'it', label: '🇮🇹 Italien',   dir: 'ltr' },
    { code: 'pt', label: '🇧🇷 Portugais', dir: 'ltr' },
];

window.drawerOuvrirTraduction = function(id) {
    const old = document.getElementById(`trad-picker-${id}`);
    if (old) { old.remove(); return; }

    const body = document.getElementById('drawerBody');
    if (!body) return;

    const picker = document.createElement('div');
    picker.id = `trad-picker-${id}`;
    picker.className = 'trad-picker-panel';
    picker.innerHTML = `
        <div class="trad-picker-hd">
            <span>🌐 Choisissez la langue de traduction</span>
            <button onclick="document.getElementById('trad-picker-${id}').remove()" class="trad-close-btn">✕</button>
        </div>
        <div class="trad-picker-langs">
            ${LANGUES_DISPO.map(l => `
                <button class="trad-lang-btn" onclick="drawerTraduire(${id},'${l.code}','${l.label}','${l.dir}')">
                    ${l.label}
                </button>`).join('')}
        </div>`;
    body.insertAdjacentElement('afterbegin', picker);
};

window.drawerTraduire = function(id, code, label, dir) {
    const body = document.getElementById('drawerBody');
    if (!body) return;

    const picker = document.getElementById(`trad-picker-${id}`);
    if (picker) picker.remove();

    const oldTrad = document.getElementById(`trad-result-${id}`);
    if (oldTrad) oldTrad.remove();

    const loadEl = document.createElement('div');
    loadEl.id = `trad-result-${id}`;
    loadEl.className = 'trad-loading-panel';
    loadEl.innerHTML = `
        <div class="trad-load-inner">
            <span class="trad-spinner"></span>
            <span>Traduction en cours vers <strong>${label}</strong>…</span>
        </div>`;
    body.insertAdjacentElement('afterbegin', loadEl);

    fetch(`planning/ai_translate.php?id=${id}&langue=${code}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(res => {
        if (!res.success) throw new Error(res.error || 'Erreur traduction');

        const parDate = {};
        res.lignes.forEach(l => {
            if (!parDate[l.date]) parDate[l.date] = [];
            parDate[l.date].push(l);
        });

        const isRTL = dir === 'rtl';
        let html = `<div id="trad-result-${id}" class="trad-result-panel" dir="${dir}">
            <div class="trad-result-hd">
                <span class="trad-result-badge">🌐 Traduction ${label} — ${res.nb} lignes</span>
                <div style="display:flex;gap:8px">
                    <button onclick="drawerOuvrirTraduction(${id})" class="trad-change-btn">🔄 Autre langue</button>
                    <button onclick="document.getElementById('trad-result-${id}').remove()" class="trad-close-btn">✕</button>
                </div>
            </div>`;

        Object.entries(parDate).forEach(([date, lignes]) => {
            const dateAff = new Date(date).toLocaleDateString('fr-FR', {weekday:'long', day:'numeric', month:'long'});
            html += `<div class="trad-day-block">
                <div class="trad-day-hd">📅 ${dateAff}</div>
                <div class="trad-day-body">`;
            lignes.forEach(l => {
                const typeIco = { repas:'🍽️', sport:'🏃', sommeil:'🌙', conseil:'💡' }[l.type_activite] || '📋';
                html += `<div class="trad-ligne">
                    <span class="trad-type-badge">${typeIco} ${l.type_activite}</span>
                    <span class="trad-desc" style="direction:${dir};text-align:${isRTL?'right':'left'}">${l.description}</span>
                </div>`;
            });
            html += `</div></div>`;
        });

        html += `</div>`;
        loadEl.outerHTML = html;
        showPlanningToast(`✅ Planning traduit en ${label}`, 'ok');
    })
    .catch(err => {
        loadEl.outerHTML = `<div id="trad-result-${id}" class="trad-error-panel">
            ❌ Erreur : ${err.message}
            <button onclick="drawerOuvrirTraduction(${id})" class="dw-btn dw-btn-ai" style="margin-left:12px">Réessayer</button>
        </div>`;
        showPlanningToast('Erreur traduction : ' + err.message, 'err');
    });
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

// ── EXPORT CSV ────────────────────────────────────────────────────────────
window.exportPlanningCSV = function() {
    // Respect le filtre statut actif
    const statut = planningFiltre !== 'all' ? planningFiltre : '';
    const url = 'planning/listDemandeplanning.php?action=export_csv'
              + (statut ? '&statut=' + encodeURIComponent(statut) : '');
    window.location.href = url;
};
window.setPlanningFilter   = function(f, el) {
    planningFiltre = f;
    document.querySelectorAll('.planning-chip').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    renderPlanningTable();
};

// ── Tri via bouton dédié ──────────────────────────────────────────────────
window.applySortPlanning = function() {
    // Le select a déjà changé de valeur — renderPlanningTable() le relira
    renderPlanningTable();
};

window.toggleSortDir = function() {
    planningSort.dir = planningSort.dir === 'asc' ? 'desc' : 'asc';
    _updateSortDirBtn();
    renderPlanningTable();
};

function _updateSortDirBtn() {
    const btn = document.getElementById('planningSortDirBtn');
    if (!btn) return;
    if (planningSort.dir === 'asc') {
        btn.textContent = '↑ ASC';
        btn.style.color = '#2ecc71';
    } else {
        btn.textContent = '↓ DESC';
        btn.style.color = 'var(--violet, #a78bfa)';
    }
}

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