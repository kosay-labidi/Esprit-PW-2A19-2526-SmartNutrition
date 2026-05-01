/**
 * challenges-admin.js — GaiaLumen v5.0 FINAL
 * ════════════════════════════════════════════════════════
 *  ✅ CRUD complet (add, edit, delete, list)
 *  ✅ Statistiques dashboard avec count-up
 *  ✅ Statut → Accepté / Refusé (dropdown inline)
 *  ✅ Export CSV et PDF
 *  ✅ Drag & Drop (SortableJS)
 *  ✅ Timer compte à rebours
 *  ✅ Notifications email (modal)
 *  ✅ nb_vues auto + toggle likes
 *  ✅ Toast system global
 *  ✅ Toutes les fonctions existantes préservées
 * ════════════════════════════════════════════════════════
 */

console.log('🏆 challenges-admin.js v5.0 FINAL chargé');

// ═══════════════════════════════════════════════════════════
// ÉTAT GLOBAL
// ═══════════════════════════════════════════════════════════
let adminChallenges        = [];
let adminParticipants      = [];
let filteredParticipants   = [];
let selectedChallengeParticipants = [];
let participantsPage       = 1;
const PARTICIPANTS_PER_PAGE = 8;

// SortableJS instance (drag & drop)
let sortableInstance = null;

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

document.addEventListener('adminModuleLoaded', (e) => {
  if (e.detail.moduleName === 'challenges') {
    setTimeout(() => {
      if (document.getElementById('challenges')) {
        initChallengeForm();
        loadAdminChallenges();
        loadAdminParticipants();
        loadStatistiques();
        setupRippleEffect();
        injectExtraUI();
      }
    }, 50);
  }
});

if (document.getElementById('challenges')) {
  initChallengeForm();
  loadAdminChallenges();
  loadAdminParticipants();
  loadStatistiques();
  setupRippleEffect();
  injectExtraUI();
}

// ─── Injecter les éléments UI dynamiques ─────────────────────
function injectExtraUI() {
  injectToastContainer();
  injectNotifModal();
  injectStatsSectionIfMissing();
  injectSortableScript();
}

// ─── Charger SortableJS via CDN ──────────────────────────────
function injectSortableScript() {
  if (window.Sortable) { initDragDrop(); return; }
  const s = document.createElement('script');
  s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
  s.onload = initDragDrop;
  document.head.appendChild(s);
}

// ─── Toast container ─────────────────────────────────────────
function injectToastContainer() {
  if (document.getElementById('gl-toast-container')) return;
  const d = document.createElement('div');
  d.id = 'gl-toast-container';
  d.style.cssText = `
    position:fixed; bottom:24px; right:24px; z-index:99999;
    display:flex; flex-direction:column; gap:10px; pointer-events:none;
  `;
  document.body.appendChild(d);
}

// ─── Section statistiques ─────────────────────────────────────
function injectStatsSectionIfMissing() {
  if (document.getElementById('gl-stats-section')) return;
  const challenges = document.getElementById('challenges');
  if (!challenges) return;

  const section = document.createElement('div');
  section.id = 'gl-stats-section';
  section.style.cssText = `
    background:#1e1e2e; border:1px solid rgba(99,102,241,0.3); border-radius:16px;
    padding:24px; margin:16px 0; display:none;
  `;
  section.innerHTML = `
    <h3 style="color:#e2e8f0;margin:0 0 20px;font-size:1.1rem;">
      📊 Tableau de bord statistiques
    </h3>

    <!-- Cartes de stats -->
    <div id="gl-stat-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:24px;">
      ${['total_challenges:🏆:Total','challenges_actifs:✅:Actifs','challenges_termines:📦:Terminés',
         'total_participants:👥:Participants','total_vues:👁:Vues','total_likes:❤️:Likes']
        .map(x => {
          const [key, icon, label] = x.split(':');
          return `<div style="background:#2d2d44;border-radius:12px;padding:16px;text-align:center;border-left:3px solid #6366f1;">
            <div style="font-size:1.8rem;" id="stat-${key}">—</div>
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin-top:4px;">${icon} ${label}</div>
          </div>`;
        }).join('')}
    </div>

    <!-- Top 3 défis -->
    <div style="margin-bottom:20px;">
      <h4 style="color:#94a3b8;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 12px;">
        🥇 Top 3 défis populaires
      </h4>
      <table style="width:100%;border-collapse:collapse;" id="gl-top3-table">
        <thead>
          <tr style="background:rgba(99,102,241,0.15);">
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">#</th>
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Défi</th>
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Participants</th>
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Statut</th>
          </tr>
        </thead>
        <tbody id="gl-top3-tbody">
          <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">Chargement...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Top 5 participants -->
    <div>
      <h4 style="color:#94a3b8;font-size:.85rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 12px;">
        🔥 Top 5 participants engagés
      </h4>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:rgba(99,102,241,0.15);">
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Participant</th>
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Défi</th>
            <th style="padding:8px 12px;text-align:left;color:#818cf8;font-size:12px;">Engagement</th>
          </tr>
        </thead>
        <tbody id="gl-top5-tbody">
          <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:20px;">Chargement...</td></tr>
        </tbody>
      </table>
    </div>
  `;

  // Insérer au début de la section challenges
  challenges.insertBefore(section, challenges.firstChild);

  // Bouton toggle stats dans le hero
  const hero = challenges.querySelector('.adm-hero-stats, .adm-hero-inner');
  if (hero) {
    const btn = document.createElement('button');
    btn.onclick = toggleStats;
    btn.style.cssText = `
      background:rgba(99,102,241,0.2); color:#818cf8; border:1px solid rgba(99,102,241,0.4);
      padding:8px 16px; border-radius:8px; cursor:pointer; font-size:13px; margin-top:10px;
    `;
    btn.innerHTML = '📊 Voir les statistiques';
    hero.appendChild(btn);
  }
}

function toggleStats() {
  const s = document.getElementById('gl-stats-section');
  if (!s) return;
  s.style.display = s.style.display === 'none' ? 'block' : 'none';
}

// ─── Modal notifications ──────────────────────────────────────
function injectNotifModal() {
  if (document.getElementById('gl-notif-modal')) return;
  const div = document.createElement('div');
  div.id = 'gl-notif-modal';
  div.style.cssText = `
    display:none; position:fixed; inset:0; z-index:99998;
    background:rgba(0,0,0,0.7); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
  `;
  div.innerHTML = `
    <div style="
      background:#1e1e2e; border:1px solid #6366f1; border-radius:16px;
      padding:28px; width:500px; max-width:95vw; position:relative;
    ">
      <button onclick="closeNotifModal()" style="
        position:absolute;top:12px;right:16px;background:none;border:none;
        color:#94a3b8;font-size:1.3rem;cursor:pointer;
      ">✕</button>
      <h3 style="color:#e2e8f0;margin:0 0 6px;">📧 Notifier les participants</h3>
      <p style="color:#94a3b8;font-size:13px;margin:0 0 20px;">
        Défi : <strong id="gl-notif-titre" style="color:#818cf8;"></strong>
      </p>
      <input type="hidden" id="gl-notif-id">
      <div style="margin-bottom:14px;">
        <label style="display:block;color:#94a3b8;font-size:12px;margin-bottom:6px;">Sujet de l'email *</label>
        <input id="gl-notif-sujet" type="text" placeholder="Ex: Rappel — votre défi se termine bientôt !" style="
          width:100%;box-sizing:border-box;background:#2d2d44;border:1px solid rgba(99,102,241,0.4);
          border-radius:8px;color:#e2e8f0;padding:10px 14px;font-size:14px;
        ">
      </div>
      <div style="margin-bottom:20px;">
        <label style="display:block;color:#94a3b8;font-size:12px;margin-bottom:6px;">Message *</label>
        <textarea id="gl-notif-message" rows="5" placeholder="Votre message aux participants..." style="
          width:100%;box-sizing:border-box;background:#2d2d44;border:1px solid rgba(99,102,241,0.4);
          border-radius:8px;color:#e2e8f0;padding:10px 14px;font-size:14px;resize:vertical;
        "></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeNotifModal()" style="
          background:none;border:1px solid rgba(255,255,255,0.15);color:#94a3b8;
          padding:10px 20px;border-radius:8px;cursor:pointer;
        ">Annuler</button>
        <button id="gl-notif-send" onclick="sendNotification()" style="
          background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;
          padding:10px 24px;border-radius:8px;cursor:pointer;font-weight:600;
        ">✉️ Envoyer</button>
      </div>
    </div>
  `;
  document.body.appendChild(div);
  div.addEventListener('click', e => { if (e.target === div) closeNotifModal(); });
}

// ═══════════════════════════════════════════════════════════
// STATISTIQUES
// ═══════════════════════════════════════════════════════════

function loadStatistiques() {
  fetch('challenges/listChallenges.php?action=stats&t=' + Date.now(), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(data => {
      renderStatCards(data);
      renderTop3(data.top3_challenges  || []);
      renderTop5(data.top5_participants || []);
      // Mettre à jour les pills du hero
      updateHeroStats(data);
    })
    .catch(err => console.warn('Stats non disponibles:', err));
}

function updateHeroStats(data) {
  const totalHero = document.getElementById('stat-total-hero');
  const actifHero = document.getElementById('stat-actif-hero');
  const partHero  = document.getElementById('stat-part-hero');
  if (totalHero) countUp(totalHero, data.total_challenges || 0);
  if (actifHero) countUp(actifHero,  data.challenges_actifs || 0);
  if (partHero)  countUp(partHero,   data.total_participants || 0);
}

function renderStatCards(data) {
  const keys = {
    'total_challenges': data.total_challenges   || 0,
    'challenges_actifs': data.challenges_actifs  || 0,
    'challenges_termines': data.challenges_termines || 0,
    'total_participants': data.total_participants || 0,
    'total_vues':         data.total_vues        || 0,
    'total_likes':        data.total_likes       || 0,
  };
  for (const [key, val] of Object.entries(keys)) {
    const el = document.getElementById(`stat-${key}`);
    if (el) countUp(el, val);
  }
}

function renderTop3(list) {
  const tbody = document.getElementById('gl-top3-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:16px;">Aucune donnée</td></tr>`;
    return;
  }
  const colors = { actif:'#22c55e', termine:'#6b7280', en_attente:'#f59e0b', accepte:'#3b82f6', refuse:'#ef4444' };
  tbody.innerHTML = list.map((c, i) => `
    <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
      <td style="padding:10px 12px;color:#f59e0b;font-weight:700;">${['🥇','🥈','🥉'][i]}</td>
      <td style="padding:10px 12px;color:#e2e8f0;">${escapeHtml(c.streak_icon||'')} ${escapeHtml(c.titre||'')}</td>
      <td style="padding:10px 12px;">
        <span style="background:rgba(99,102,241,0.2);color:#818cf8;padding:3px 10px;border-radius:20px;font-size:12px;">
          👥 ${c.nb_participants}
        </span>
      </td>
      <td style="padding:10px 12px;">
        <span style="background:${colors[c.statut]||'#94a3b8'}22;color:${colors[c.statut]||'#94a3b8'};
                      padding:3px 10px;border-radius:20px;font-size:12px;">
          ${c.statut}
        </span>
      </td>
    </tr>
  `).join('');
}

function renderTop5(list) {
  const tbody = document.getElementById('gl-top5-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="3" style="color:#94a3b8;text-align:center;padding:16px;">Aucune donnée</td></tr>`;
    return;
  }
  tbody.innerHTML = list.map(p => {
    const eng = Math.min(100, Math.max(0, parseInt(p.engagement) || 0));
    const color = eng >= 80 ? '#22c55e' : eng >= 50 ? '#f59e0b' : '#ef4444';
    return `
      <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
        <td style="padding:10px 12px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:${getAvatarColor(p.nom)};
                        display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;">
              ${getInitials(p.nom)}
            </div>
            <div>
              <div style="color:#e2e8f0;font-size:13px;">${escapeHtml(p.nom||'')}</div>
              <div style="color:#94a3b8;font-size:11px;">${escapeHtml(p.email||'')}</div>
            </div>
          </div>
        </td>
        <td style="padding:10px 12px;color:#94a3b8;font-size:13px;">
          ${escapeHtml(p.challenge_icon||'🏆')} ${escapeHtml(p.challenge_titre||'')}
        </td>
        <td style="padding:10px 12px;">
          <div style="display:flex;align-items:center;gap:8px;">
            <div style="flex:1;height:6px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden;">
              <div style="width:${eng}%;height:100%;background:${color};border-radius:99px;"></div>
            </div>
            <span style="color:${color};font-size:12px;font-weight:700;min-width:32px;">${eng}%</span>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ─── Animation count-up ──────────────────────────────────────
function countUp(el, target, duration = 800) {
  const start = parseInt(el.textContent) || 0;
  const range = target - start;
  if (range === 0) { el.textContent = target; return; }
  const startTime = performance.now();
  const step = (now) => {
    const t = Math.min((now - startTime) / duration, 1);
    const ease = 1 - Math.pow(1 - t, 3); // easeOutCubic
    el.textContent = Math.round(start + range * ease).toLocaleString('fr-FR');
    if (t < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

// ═══════════════════════════════════════════════════════════
// STATUT → ACCEPTÉ / REFUSÉ (dropdown inline)
// ═══════════════════════════════════════════════════════════

/**
 * Génère le HTML d'un badge statut cliquable avec dropdown
 */
function renderStatutBadge(id, statut) {
  const cfg = {
    actif:      { label: '✅ Actif',       color: '#22c55e' },
    termine:    { label: '📦 Terminé',     color: '#6b7280' },
    en_attente: { label: '⏳ En attente',  color: '#f59e0b' },
    accepte:    { label: '✅ Accepté',      color: '#3b82f6' },
    refuse:     { label: '❌ Refusé',       color: '#ef4444' },
  };
  const s = cfg[statut] || { label: statut, color: '#94a3b8' };

  const options = Object.entries(cfg)
    .filter(([k]) => k !== statut)
    .map(([k, v]) => `
      <div onclick="updateStatutChallenge(${id},'${k}',this)" style="
        padding:8px 14px; cursor:pointer; color:#e2e8f0; font-size:13px;
        display:flex; align-items:center; gap:8px; border-radius:6px; transition:background .15s;
      " onmouseover="this.style.background='rgba(99,102,241,0.2)'"
         onmouseout="this.style.background='none'">
        <span style="width:8px;height:8px;border-radius:50%;background:${v.color};display:inline-block;"></span>
        ${v.label}
      </div>
    `).join('');

  return `
    <div class="gl-statut-wrap" style="position:relative;display:inline-block;">
      <span class="gl-statut-badge" data-id="${id}" data-statut="${statut}"
        style="background:${s.color}22;color:${s.color};border:1px solid ${s.color}55;
               padding:4px 12px;border-radius:20px;font-size:12px;cursor:pointer;
               display:inline-flex;align-items:center;gap:5px;user-select:none;"
        onclick="toggleStatutDropdown(this)">
        ${s.label} <span style="font-size:10px;opacity:0.7;">▾</span>
      </span>
      <div class="gl-statut-dropdown" style="
        display:none;position:absolute;top:calc(100% + 6px);left:0;
        background:#1e1e2e;border:1px solid rgba(99,102,241,0.4);border-radius:10px;
        padding:6px;min-width:160px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.5);
        animation:dropIn .15s ease;
      ">
        ${options}
      </div>
    </div>
    <style>
      @keyframes dropIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
    </style>
  `;
}

function toggleStatutDropdown(badge) {
  // Fermer tous les autres dropdowns
  document.querySelectorAll('.gl-statut-dropdown').forEach(d => {
    if (d !== badge.nextElementSibling) d.style.display = 'none';
  });
  const dd = badge.nextElementSibling;
  dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

// Fermer les dropdowns si clic ailleurs
document.addEventListener('click', (e) => {
  if (!e.target.closest('.gl-statut-wrap')) {
    document.querySelectorAll('.gl-statut-dropdown').forEach(d => d.style.display = 'none');
  }
});

function updateStatutChallenge(id, newStatut, clickedEl) {
  // Fermer le dropdown
  const dd = clickedEl?.closest('.gl-statut-dropdown');
  if (dd) dd.style.display = 'none';

  fetch('challenges/listChallenges.php?action=updateStatut', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id, statut: newStatut })
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Mettre à jour dans adminChallenges
        const c = adminChallenges.find(x => String(x.id) === String(id));
        if (c) c.statut = newStatut;

        // Remplacer le badge dans le DOM sans recharger tout le tableau
        const wrap = document.querySelector(`.gl-statut-badge[data-id="${id}"]`)?.closest('.gl-statut-wrap');
        if (wrap) {
          const tmp = document.createElement('div');
          tmp.innerHTML = renderStatutBadge(id, newStatut);
          wrap.replaceWith(tmp.firstElementChild);
        }
        showToast('Statut mis à jour', `Le défi est maintenant : ${newStatut}`, 'success');
      } else {
        showToast('Erreur', 'Impossible de changer le statut', 'error');
      }
    })
    .catch(() => showToast('Erreur serveur', 'Connexion impossible', 'error'));
}

// ═══════════════════════════════════════════════════════════
// EXPORT CSV / PDF
// ═══════════════════════════════════════════════════════════

function exportCSV() {
  showToast('Export CSV', 'Téléchargement du fichier CSV...', 'info');
  window.location.href = 'challenges/listChallenges.php?action=exportCSV&t=' + Date.now();
}

function exportPDF() {
  showToast('Export PDF', 'Génération du rapport PDF...', 'info');
  window.open('challenges/listChallenges.php?action=exportPDF&t=' + Date.now(), '_blank');
}

// ═══════════════════════════════════════════════════════════
// DRAG & DROP (SortableJS)
// ═══════════════════════════════════════════════════════════

function initDragDrop() {
  const checkInterval = setInterval(() => {
    const tbody = document.getElementById('challenges-tbody');
    if (!tbody || !window.Sortable) return;
    clearInterval(checkInterval);

    if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null; }

    sortableInstance = new Sortable(tbody, {
      handle: '.gl-drag-handle',
      animation: 200,
      ghostClass: 'gl-sortable-ghost',
      chosenClass: 'gl-sortable-chosen',
      onEnd: function() {
        const btn = document.getElementById('btn-save-order');
        if (btn) btn.style.display = 'inline-flex';
      }
    });

    // Injecter le style ghost/chosen une seule fois
    if (!document.getElementById('gl-drag-styles')) {
      const style = document.createElement('style');
      style.id = 'gl-drag-styles';
      style.textContent = `
        .gl-drag-handle { cursor:grab; color:#6366f1; padding:0 6px; font-size:16px; }
        .gl-drag-handle:hover { color:#818cf8; }
        .gl-sortable-ghost  { opacity:.4; background:rgba(99,102,241,0.15) !important; }
        .gl-sortable-chosen { box-shadow:0 6px 20px rgba(99,102,241,0.4); transform:scale(1.01); }
      `;
      document.head.appendChild(style);
    }
  }, 500);
}

function saveOrder() {
  const rows = document.querySelectorAll('#challenges-tbody tr[data-id]');
  if (!rows.length) return;

  const ordreData = Array.from(rows).map((row, index) => ({
    id:    parseInt(row.dataset.id),
    ordre: index
  }));

  const btn = document.getElementById('btn-save-order');
  if (btn) { btn.disabled = true; btn.innerHTML = '⌛ Sauvegarde...'; }

  fetch('challenges/listChallenges.php?action=updateOrdre', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify(ordreData)
  })
    .then(r => r.json())
    .then(data => {
      if (btn) { btn.disabled = false; btn.style.display = 'none'; btn.innerHTML = '💾 Sauvegarder l\'ordre'; }
      if (data.success) {
        showToast('Ordre sauvegardé', 'L\'ordre des défis a été mis à jour.', 'success');
        // Mettre à jour l'ordre dans adminChallenges
        rows.forEach((row, i) => {
          const c = adminChallenges.find(x => String(x.id) === row.dataset.id);
          if (c) c.ordre = i;
        });
      }
    })
    .catch(() => {
      if (btn) { btn.disabled = false; btn.innerHTML = '💾 Sauvegarder l\'ordre'; }
      showToast('Erreur', 'Impossible de sauvegarder l\'ordre.', 'error');
    });
}

// ═══════════════════════════════════════════════════════════
// TIMER COMPTE À REBOURS
// ═══════════════════════════════════════════════════════════

const _timerIntervals = {};

function startTimers() {
  document.querySelectorAll('.gl-timer[data-end]').forEach(timer => {
    const key = timer.dataset.end;
    if (_timerIntervals[key]) return; // déjà démarré

    function tick() {
      const diff = new Date(key) - new Date();
      if (diff <= 0) {
        timer.innerHTML = '<span style="color:#6b7280;font-size:11px;">⏰ Terminé</span>';
        clearInterval(_timerIntervals[key]);
        return;
      }
      const jj = Math.floor(diff / 86400000);
      const hh = Math.floor((diff % 86400000) / 3600000);
      const mm = Math.floor((diff % 3600000)  / 60000);
      const ss = Math.floor((diff % 60000)    / 1000);
      const p  = (n) => String(n).padStart(2, '0');

      timer.innerHTML = `
        <span style="font-size:10px;color:#94a3b8;display:flex;align-items:center;gap:3px;
                      font-family:'Courier New',monospace;${diff < 86400000 ? 'color:#ef4444;' : ''}">
          ${jj > 0 ? `<b style="color:inherit">${jj}j</b>` : ''}
          <b style="color:inherit">${p(hh)}h</b>
          <b style="color:inherit">${p(mm)}m</b>
          <b style="color:inherit">${p(ss)}s</b>
        </span>`;
    }
    tick();
    _timerIntervals[key] = setInterval(tick, 1000);
  });
}

// ═══════════════════════════════════════════════════════════
// NOTIFICATIONS EMAIL
// ═══════════════════════════════════════════════════════════

function openNotifModal(id, titre) {
  const modal = document.getElementById('gl-notif-modal');
  if (!modal) return;
  document.getElementById('gl-notif-id').value    = id;
  document.getElementById('gl-notif-titre').textContent = titre;
  document.getElementById('gl-notif-sujet').value  = '';
  document.getElementById('gl-notif-message').value = '';
  modal.style.display = 'flex';
}

function closeNotifModal() {
  const modal = document.getElementById('gl-notif-modal');
  if (modal) modal.style.display = 'none';
}

function sendNotification() {
  const id      = document.getElementById('gl-notif-id').value;
  const sujet   = document.getElementById('gl-notif-sujet').value.trim();
  const message = document.getElementById('gl-notif-message').value.trim();

  if (!sujet || !message) {
    showToast('Champs manquants', 'Veuillez remplir le sujet et le message.', 'warning');
    return;
  }

  const btn = document.getElementById('gl-notif-send');
  btn.disabled = true;
  btn.innerHTML = '⌛ Envoi en cours...';

  fetch('challenges/listChallenges.php?action=notifier', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id_challenge: parseInt(id), sujet, message })
  })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '✉️ Envoyer';
      closeNotifModal();
      if (data.success) {
        if (data.failed > 0 && data.sent === 0) {
          showToast(
            '⚠️ Échec de l\'envoi',
            `Aucun email n'a pu être envoyé. Vérifiez la configuration du serveur mail (XAMPP).`,
            'warning'
          );
        } else {
          showToast(
            '📧 Notifications envoyées',
            `${data.sent} email(s) envoyé(s) sur ${data.total} participants. ${data.failed} échec(s).`,
            data.failed > 0 ? 'warning' : 'success'
          );
        }
      } else {
        showToast('Erreur', data.error || 'Erreur lors de l\'envoi', 'error');
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '✉️ Envoyer';
      showToast('Erreur serveur', 'Connexion impossible.', 'error');
    });
}

// ═══════════════════════════════════════════════════════════
// TOAST SYSTEM
// ═══════════════════════════════════════════════════════════

/**
 * Appelable partout dans le code.
 * showToast(title, message, type)
 * showToast(title, message, 'success'|'error'|'warning'|'info')
 */
function showToast(title, message = '', type = 'success', customIcon = null) {
  const cfg = {
    success: { color: '#22c55e', icon: '✅' },
    error:   { color: '#ef4444', icon: '❌' },
    warning: { color: '#f59e0b', icon: '⚠️' },
    info:    { color: '#3b82f6', icon: 'ℹ️' },
  };
  const c = cfg[type] || cfg.info;

  const container = document.getElementById('gl-toast-container');
  if (!container) { console.log(`[Toast ${type}] ${title}: ${message}`); return; }

  const toast = document.createElement('div');
  toast.style.cssText = `
    background:#1e1e2e; color:#e2e8f0; padding:14px 18px;
    border-radius:12px; border-left:4px solid ${c.color};
    box-shadow:0 4px 20px rgba(0,0,0,0.5); font-size:13px;
    display:flex; align-items:flex-start; gap:12px; min-width:280px; max-width:360px;
    pointer-events:all; position:relative; overflow:hidden;
    animation:glToastIn .3s cubic-bezier(.34,1.56,.64,1);
  `;
  toast.innerHTML = `
    <style>
      @keyframes glToastIn  { from{opacity:0;transform:translateX(100%)} to{opacity:1;transform:translateX(0)} }
      @keyframes glToastOut { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(100%)} }
    </style>
    <span style="font-size:1.2rem;flex-shrink:0;margin-top:1px;">${c.icon}</span>
    <div style="flex:1;">
      <div style="font-weight:700;margin-bottom:2px;">${escapeHtml(title)}</div>
      ${message ? `<div style="color:#94a3b8;font-size:12px;line-height:1.4;">${escapeHtml(message)}</div>` : ''}
    </div>
    <button onclick="this.closest('[style]').remove()" style="
      background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;
      padding:0;margin-top:1px;flex-shrink:0;
    ">✕</button>
    <!-- Progress bar -->
    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,0.05);">
      <div id="tpb-${Date.now()}" style="height:100%;background:${c.color};width:100%;
           transition:width 4s linear;border-radius:2px;"></div>
    </div>
  `;

  container.appendChild(toast);

  // Animer la barre de progression
  requestAnimationFrame(() => {
    const bar = toast.querySelector('[id^="tpb-"]');
    if (bar) bar.style.width = '0%';
  });

  // Auto-dismiss après 4s
  setTimeout(() => {
    toast.style.animation = 'glToastOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// ═══════════════════════════════════════════════════════════
// RENDU DU TABLEAU — VERSION ÉTENDUE
// ═══════════════════════════════════════════════════════════

function renderChallengesTable() {
  const tbody = document.getElementById('challenges-tbody');
  if (!tbody) return;

  const searchValue  = (document.getElementById('search-input-admin')?.value  || '').toLowerCase();
  const statusFilter =  document.getElementById('status-filter-admin')?.value  || '';

  const filtered = adminChallenges.filter(c => {
    if (!c) return false;
    const titre       = (c.titre       || '').toLowerCase();
    const description = (c.description || '').toLowerCase();
    const statut      = (c.statut      || '').toLowerCase();

    if (searchValue && !titre.includes(searchValue) && !description.includes(searchValue)) return false;

    if (statusFilter) {
      let cs = statut;
      if (['en cours','en_cours','actif'].includes(cs))       cs = 'actif';
      if (['terminé','termine'].includes(cs))                 cs = 'termine';
      if (['a venir','futur','à venir'].includes(cs))         cs = 'futur';
      if (cs !== statusFilter) return false;
    }
    return true;
  });

  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:10px;">🏆</div>
      <div>${adminChallenges.length === 0 ? 'Aucun défi en base' : 'Aucun défi trouvé'}</div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(c => {
    const participantsCount = parseInt(c.participants_count || 0);
    const target            = parseInt(c.valeur_cible       || 0);
    const pct = target > 0 ? Math.min(100, Math.round((participantsCount / target) * 100)) : 0;
    const nbVues  = parseInt(c.nb_vues  || 0);
    const nbLikes = parseInt(c.nb_likes || 0);

    return `
      <tr class="challenge-row-admin" data-id="${c.id}">
        <!-- Drag handle -->
        <td class="gl-drag-handle" title="Glisser pour réordonner" style="width:28px;">⠿</td>

        <!-- Titre + dates -->
        <td>
          <div class="challenge-cell">
            <span class="challenge-icon-mini">${c.streak_icon || '🏆'}</span>
            <div class="challenge-info">
              <div class="challenge-title">${escapeHtml(c.titre || 'Sans titre')}</div>
              <div class="challenge-dates">${formatDate(c.date_debut)} → ${formatDate(c.date_fin)}</div>
            </div>
          </div>
        </td>

        <!-- Type -->
        <td>
          <span class="type-badge ${(c.type||'').toLowerCase() === 'collectif' ? 'type-collectif' : 'type-individuel'}">
            ${(c.type||'').toLowerCase() === 'collectif' ? '👥 Collectif' : '👤 Individuel'}
          </span>
        </td>

        <!-- Participants + barre -->
        <td>
          <div class="adm-prog-wrap">
            <div class="adm-prog-bar">
              <div class="adm-prog-fill" style="width:${pct}%"></div>
            </div>
            <span class="adm-prog-val">${participantsCount} / ${target}</span>
          </div>
        </td>

        <!-- Vues & Likes -->
        <td style="font-size:12px;color:#94a3b8;white-space:nowrap;">
          👁 ${nbVues} &nbsp; ❤️ ${nbLikes}
        </td>

        <!-- Statut dropdown -->
        <td>${renderStatutBadge(c.id, c.statut)}</td>

        <!-- Timer -->
        <td><div class="gl-timer" data-end="${c.date_fin}T23:59:59"></div></td>

        <!-- Actions -->
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="showChallengeParticipants(${c.id})"   title="Participants">👥</button>
            <button class="btn-icon" onclick="openNotifModal(${c.id}, '${escapeHtml(c.titre||'').replace(/'/g,"&#39;")}')" title="Notifier">📧</button>
            <button class="btn-icon edit"   onclick="editChallenge(${c.id})"         title="Modifier">✏️</button>
            <button class="btn-icon delete" onclick="deleteChallenge(${c.id})"       title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  // Démarrer les timers après rendu
  requestAnimationFrame(startTimers);
}

// ═══════════════════════════════════════════════════════════
// CHARGEMENT DES DONNÉES
// ═══════════════════════════════════════════════════════════

function loadAdminChallenges() {
  fetch('challenges/listChallenges.php?ajax=1&t=' + Date.now(), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const ct = r.headers.get('content-type');
      if (!ct?.includes('application/json')) throw new Error('Réponse non-JSON');
      return r.json();
    })
    .then(data => {
      adminChallenges = Array.isArray(data) ? data : [];
      sortAdminChallenges(); // Appliquer le tri par défaut
      updateDashboardStats();
      renderParticipantsChallengeFilter();
      initDragDrop();
    })
    .catch(err => {
      console.error('Erreur chargement défis:', err);
      const tbody = document.getElementById('challenges-tbody');
      if (tbody) tbody.innerHTML = `
        <tr><td colspan="8" style="text-align:center;padding:40px;color:#ef4444;">
          <div style="font-size:2rem;margin-bottom:10px;">⚠️</div>
          <div>${err.message}</div>
          <button onclick="loadAdminChallenges()" style="margin-top:10px;padding:6px 16px;
            background:#ef4444;color:#fff;border:none;border-radius:6px;cursor:pointer;">
            Réessayer
          </button>
        </td></tr>`;
    });
}

function loadAdminParticipants() {
  fetch('challenges/showParticipant.php?ajax=1&t=' + Date.now(), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(result => {
      adminParticipants = (result?.participants && Array.isArray(result.participants))
        ? result.participants : [];
      sortAdminParticipants(); // Appliquer le tri par défaut
      updateParticipantsStats();
      updateDashboardStats();
    })
    .catch(err => {
      console.error('Erreur chargement participants:', err);
      adminParticipants = [];
      renderParticipantsTable();
    });
}

// ─── Tris admin ──────────────────────────────────────────────
function sortAdminChallenges() {
  const sortType = document.getElementById('sort-challenges-admin')?.value || 'date_desc';
  adminChallenges.sort((a, b) => {
    switch (sortType) {
      case 'participants_desc': return (parseInt(b.participants_count) || 0) - (parseInt(a.participants_count) || 0);
      case 'vues_desc': return (parseInt(b.nb_vues) || 0) - (parseInt(a.nb_vues) || 0);
      case 'likes_desc': return (parseInt(b.nb_likes) || 0) - (parseInt(a.nb_likes) || 0);
      case 'titre_asc': return (a.titre || '').localeCompare(b.titre || '');
      default: return new Date(b.date_debut) - new Date(a.date_debut);
    }
  });
  renderChallengesTable();
}

function sortAdminParticipants() {
  const sortType = document.getElementById('sort-participants-admin')?.value || 'date_desc';
  adminParticipants.sort((a, b) => {
    switch (sortType) {
      case 'progression_desc': return (parseInt(b.engagement) || 0) - (parseInt(a.engagement) || 0);
      case 'nom_asc': return (a.nom || '').localeCompare(b.nom || '');
      default: return new Date(b.date_inscription) - new Date(a.date_inscription);
    }
  });
  renderParticipantsTable();
}

// ═══════════════════════════════════════════════════════════
// TOUTES LES FONCTIONS EXISTANTES PRÉSERVÉES
// ═══════════════════════════════════════════════════════════

function setupRippleEffect() {
  document.addEventListener('mousedown', function(e) {
    const target = e.target.closest('.btn-primary, .btn-secondary, .btn-icon, .visual-option');
    if (!target) return;
    const ripple = document.createElement('span');
    ripple.classList.add('ripple');
    target.appendChild(ripple);
    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
    ripple.style.top  = (e.clientY - rect.top  - size / 2) + 'px';
    setTimeout(() => ripple.remove(), 600);
  });
}

function initChallengeForm() {
  const form = document.getElementById('challenge-form');
  if (!form) return;
  setupRealTimeValidation(form);
  updatePreview();

  form.onsubmit = function(e) {
    e.preventDefault();
    clearErrors();
    if (!validateChallengeForm()) return;

    const formData  = new FormData(form);
    const challengeId = formData.get('id');
    const isUpdate  = challengeId && challengeId !== '';
    const url       = isUpdate
      ? 'challenges/updateChallenge.php?id=' + challengeId
      : 'challenges/addChallenge.php';

    const submitBtn = document.getElementById('form-submit-btn');
    const origHTML  = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="btn-label">⌛ Envoi...</span>';

    fetch(url, {
      method: 'POST', body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.redirected ? { success: true } : r.text().then(t => {
        try { return JSON.parse(t); } catch { return { success: r.ok }; }
      }))
      .then(result => {
        if (result.success || result === true) {
          resetForm();
          loadAdminChallenges();
          loadStatistiques();
          showToast(
            isUpdate ? 'Défi modifié' : 'Défi créé',
            isUpdate ? 'Le défi a été mis à jour.' : 'Le nouveau défi est maintenant en ligne.',
            'success'
          );
        } else {
          showToast('Erreur', result.message || 'Erreur lors de l\'enregistrement', 'error');
        }
      })
      .catch(() => showToast('Erreur serveur', 'Connexion impossible', 'error'))
      .finally(() => { submitBtn.disabled = false; submitBtn.innerHTML = origHTML; });
  };
}

function setupRealTimeValidation(form) {
  form.querySelectorAll('input, textarea, select').forEach(input => {
    ['input', 'change'].forEach(ev => input.addEventListener(ev, () => {
      validateField(input);
      if (input.id === 'challenge-date-debut') validateField(document.getElementById('challenge-date-fin'));
      if (input.id === 'challenge-date-fin')   validateField(document.getElementById('challenge-date-debut'));
    }));
  });
}

function validateField(input) {
  if (!input) return true;
  const id    = input.id;
  const value = (input.value || '').trim();
  let isValid = true, errorMsg = '';

  input.classList.remove('invalid');
  const errorSpan = document.getElementById(`error-${id.replace('challenge-', '')}`);
  if (errorSpan) errorSpan.innerText = '';

  if (input.required && !value) { isValid = false; errorMsg = 'Ce champ est obligatoire'; }
  else if (value) {
    switch (id) {
      case 'challenge-titre':
        if (value.length < 3) { isValid = false; errorMsg = 'Au moins 3 caractères'; } break;
      case 'challenge-description':
        if (value.length < 10) { isValid = false; errorMsg = 'Au moins 10 caractères'; } break;
      case 'challenge-valeur':
        const v = parseInt(value);
        if (isNaN(v) || v < 1 || v > 100) { isValid = false; errorMsg = 'Entre 1 et 100%'; } break;
      case 'challenge-date-debut':
        const ef = document.getElementById('challenge-date-fin')?.value;
        if (ef && new Date(value) > new Date(ef)) { isValid = false; errorMsg = 'Avant la date de fin'; } break;
      case 'challenge-date-fin':
        const ds = document.getElementById('challenge-date-debut')?.value;
        if (ds && new Date(value) < new Date(ds)) { isValid = false; errorMsg = 'Après la date de début'; } break;
      case 'challenge-image':
        if (value && !isValidUrl(value)) { isValid = false; errorMsg = 'URL invalide (http/https)'; } break;
    }
  }

  if (!isValid) { input.classList.add('invalid'); if (errorSpan) errorSpan.innerText = errorMsg; }
  return isValid;
}

function validateChallengeForm() {
  const form = document.getElementById('challenge-form');
  let valid = true;
  form.querySelectorAll('input[required], textarea[required], select[required], #challenge-image')
      .forEach(input => { if (!validateField(input)) valid = false; });
  return valid;
}

function clearErrors() {
  document.querySelectorAll('.form-input, .form-textarea, .form-select').forEach(i => i.classList.remove('invalid'));
  document.querySelectorAll('.error-msg').forEach(s => s.innerText = '');
}

function showError(fieldSuffix, msg) {
  const el = document.getElementById(`challenge-${fieldSuffix}`);
  const sp = document.getElementById(`error-${fieldSuffix}`);
  if (el) el.classList.add('invalid');
  if (sp) sp.innerText = msg;
}

function isValidUrl(str) {
  try { const u = new URL(str); return u.protocol === 'http:' || u.protocol === 'https:'; }
  catch { return false; }
}

function resetForm() {
  const form = document.getElementById('challenge-form');
  if (!form) return;
  form.reset();
  const idField = document.getElementById('challenge-id');
  if (idField) idField.value = '';
  document.getElementById('form-title').innerHTML = '<span>➕</span> Nouveau Défi';
  const btn = document.getElementById('form-submit-btn');
  if (btn) {
    btn.removeAttribute('data-mode');
    btn.innerHTML = '<span class="btn-label">🚀 Publier le Défi</span>';
  }
  updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);
  clearErrors();
  updatePreview();
}

function updateCharCountAdmin(textarea, displayId, max) {
  if (!textarea) return;
  const count    = textarea.value.length;
  const display  = document.getElementById(displayId);
  const progress = document.getElementById('char-count-progress');
  if (display)  display.innerText = `${count}/${max}`;
  if (progress) {
    const pct = (count / max) * 100;
    progress.style.width = pct + '%';
    progress.style.background = pct > 90 ? '#f44336' : pct > 70 ? '#ff9800' : '#A8B8A0';
  }
}

function updatePreview() {
  const titre    = document.getElementById('challenge-titre')?.value  || 'Titre du défi';
  const desc     = document.getElementById('challenge-description')?.value || 'Description…';
  const type     = document.querySelector('input[name="type"]:checked')?.value || 'collectif';
  const catSel   = document.getElementById('challenge-objectif');
  const category = catSel?.options[catSel.selectedIndex]?.text || 'Catégorie';
  const target   = document.getElementById('challenge-valeur')?.value   || '50';
  const dateFin  = document.getElementById('challenge-date-fin')?.value || '-';
  const icon     = document.getElementById('challenge-streak-icon')?.value || '♻️';
  const image    = document.getElementById('challenge-image')?.value;

  const safe = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
  safe('preview-title',      titre);
  safe('preview-desc',       desc);
  safe('preview-type-label', (type === 'collectif' ? '👥 ' : '👤 ') + type.charAt(0).toUpperCase() + type.slice(1));
  safe('preview-category',   category.replace(/[^\w\s]/gi, '').trim());
  safe('preview-target',     target + '%');
  safe('preview-icon',       icon);
  const el = document.getElementById('preview-date');
  if (el) el.innerText = dateFin !== '-' ? new Date(dateFin).toLocaleDateString('fr-FR', { day:'numeric', month:'short' }) : '-';

  const img = document.getElementById('preview-img-container');
  if (img) {
    if (image && isValidUrl(image)) {
      img.style.backgroundImage = `url('${image}')`;
      img.style.backgroundSize = 'cover';
      img.style.backgroundPosition = 'center';
      const previewIcon = document.getElementById('preview-icon');
      if (previewIcon) previewIcon.style.opacity = '0.3';
    } else {
      img.style.backgroundImage = 'none';
      const previewIcon = document.getElementById('preview-icon');
      if (previewIcon) previewIcon.style.opacity = '1';
    }
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('fr-FR', { day:'numeric', month:'short', year:'numeric' });
}

function getDaysLeft(dateFin) {
  const diff = new Date(dateFin) - new Date();
  const days = Math.ceil(diff / 86400000);
  if (days < 0)  return `<span class="adm-days adm-days--over">Expiré</span>`;
  if (days === 0) return `<span class="adm-days adm-days--today">Aujourd'hui</span>`;
  if (days <= 7)  return `<span class="adm-days adm-days--soon">${days}j</span>`;
  return `<span class="adm-days">${days}j</span>`;
}

function editChallenge(id) {
  const c = adminChallenges.find(x => String(x.id) === String(id));
  if (!c) return;
  document.getElementById('challenge-id').value          = c.id;
  document.getElementById('challenge-titre').value       = c.titre;
  document.getElementById('challenge-description').value = c.description;
  document.querySelectorAll('input[name="type"]').forEach(r => r.checked = r.value === c.type);
  document.getElementById('challenge-objectif').value    = c.objectif;
  document.getElementById('challenge-valeur').value      = c.valeur_cible;
  document.getElementById('challenge-date-debut').value  = c.date_debut;
  document.getElementById('challenge-date-fin').value    = c.date_fin;
  document.getElementById('challenge-statut').value      = c.statut;
  document.getElementById('challenge-streak-icon').value = c.streak_icon;
  document.getElementById('challenge-image').value       = c.image;
  updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);
  document.getElementById('form-title').innerHTML = '<span>✏️</span> Modifier le Défi';
  const btn = document.getElementById('form-submit-btn');
  if (btn) {
    btn.setAttribute('data-mode', 'edit');
    btn.innerHTML = '<span class="btn-label">💾 Enregistrer les modifications</span>';
  }
  const mc = document.querySelector('.main-content');
  if (mc) mc.scrollTo({ top: 0, behavior: 'smooth' });
  document.getElementById('challenge-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  updatePreview();
}

function deleteChallenge(id) {
  openAdmModal('⚠️ Voulez-vous vraiment supprimer ce défi ? Cette action est irréversible.', () => {
    fetch(`challenges/deleteChallenge.php?id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.redirected || r.ok ? { success: true } : r.json())
      .then(result => {
        if (result.success) {
          showToast('Défi supprimé', 'Le défi a été retiré de la plateforme.', 'success');
          loadAdminChallenges();
          loadStatistiques();
        } else {
          showToast('Erreur', result.message || 'Impossible de supprimer', 'error');
        }
      })
      .catch(() => showToast('Erreur', 'Erreur lors de la suppression', 'error'));
  });
}

function searchChallengesAdmin()  { renderChallengesTable(); }
function filterChallengesAdmin()  { renderChallengesTable(); }
function refreshTableAdmin()      { loadAdminChallenges(); }

function updateDashboardStats() {
  const s1 = document.querySelector('.metric-card:nth-child(1) .metric-value');
  const s2 = document.querySelector('.metric-card:nth-child(2) .metric-value');
  const s3 = document.querySelector('.metric-card:nth-child(3) .metric-value');
  if (s1) s1.innerText = adminChallenges.length;
  if (s2) s2.innerText = adminParticipants.length;
  if (s3) {
    const t = adminChallenges.length;
    const termines = adminChallenges.filter(c => c.statut === 'termine').length;
    s3.innerText = t > 0 ? Math.round((termines / t) * 100) + '%' : '0%';
  }
}

function showChallengeParticipants(challengeId) {
  const panel  = document.getElementById('challenge-participants-panel');
  const tbody  = document.getElementById('challenge-participants-tbody');
  const titleEl = document.getElementById('challenge-participants-title');
  const countEl = document.getElementById('challenge-participants-count');
  const c = adminChallenges.find(x => String(x.id) === String(challengeId));

  if (titleEl) titleEl.textContent = c?.titre || `Défi #${challengeId}`;
  if (countEl) countEl.textContent = '…';
  if (panel)   panel.style.display = '';
  if (tbody)   tbody.innerHTML = `<tr><td colspan="7" class="adm-table-loading">⏳ Chargement…</td></tr>`;

  // Incrémenter nb_vues
  fetch('challenges/listChallenges.php?action=incrementVues', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id: challengeId })
  }).catch(() => {});

  fetch(`challenges/showParticipant.php?ajax=1&id_challenge=${challengeId}&t=${Date.now()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(result => {
      selectedChallengeParticipants = result?.participants && Array.isArray(result.participants)
        ? result.participants : [];
      if (countEl) countEl.textContent = String(selectedChallengeParticipants.length);
      renderSelectedChallengeParticipants();
    })
    .catch(err => {
      if (countEl) countEl.textContent = '0';
      if (tbody)   tbody.innerHTML = `<tr><td colspan="7" style="color:#ef4444;text-align:center;padding:30px;">⚠️ ${escapeHtml(err.message)}</td></tr>`;
    })
    .finally(() => panel?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function renderSelectedChallengeParticipants() {
  const tbody = document.getElementById('challenge-participants-tbody');
  if (!tbody) return;
  if (!selectedChallengeParticipants.length) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:12px;">👤</div><div>Aucun participant</div>
    </td></tr>`;
    return;
  }
  tbody.innerHTML = selectedChallengeParticipants.map(p => {
    const nom = String(p.nom || '');
    const prog = clampInt(p.objectif, 0, 100);
    const target = clampInt(p.challenge_target, 0, 100);
    const engBadge = p.engagement == 1
      ? `<span class="adm-engage-badge adm-engage-badge--on">🔥 Engagé</span>`
      : `<span class="adm-engage-badge adm-engage-badge--off">😴 Inactif</span>`;
    return `
      <tr class="participant-row-admin">
        <td>
          <div class="adm-participant-cell">
            <div class="adm-avatar" style="background:${getAvatarColor(nom)}">${getInitials(nom)}</div>
            <div class="adm-participant-info">
              <span class="adm-participant-name">${escapeHtml(nom)}</span>
              <span class="adm-participant-email">${escapeHtml(p.email||'')}</span>
            </div>
          </div>
        </td>
        <td><div class="challenge-tag">
          <span class="tag-icon">${p.challenge_icon||'🏆'}</span>
          <span class="tag-text">${escapeHtml(p.challenge_titre||'')}</span>
        </div></td>
        <td>
          <div class="progress-wrapper-large">
            <div class="progress-header-flex"><span class="progress-value-text">${prog}%</span></div>
            <div class="progress-bar-large">
              <div class="progress-fill-large" style="width:${prog}%;background:linear-gradient(90deg,#f44336,#FFC107,#4CAF50);background-size:100px 100%;"></div>
            </div>
          </div>
        </td>
        <td><div class="challenge-tag"><span class="tag-icon">🎯</span><span class="tag-text">${target}%</span></div></td>
        <td><span class="participant-email">${escapeHtml(formatParticipantDate(p.date_inscription))}</span></td>
        <td>${engBadge}</td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon delete" onclick="deleteParticipant(${p.id},${p.id_challenge})" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

function closeChallengeParticipantsPanel() {
  const panel = document.getElementById('challenge-participants-panel');
  if (panel) panel.style.display = 'none';
  selectedChallengeParticipants = [];
}

function renderParticipantsChallengeFilter() {
  const select = document.getElementById('challenge-filter');
  if (!select) return;
  const prev = select.value;
  select.innerHTML = ['<option value="">Tous les défis</option>',
    ...adminChallenges.map(c => `<option value="${c.id}">${escapeHtml(c.titre||`#${c.id}`)}</option>`)
  ].join('');
  if (prev) select.value = prev;
}

function renderParticipantsTable() {
  const tbody = document.getElementById('participants-tbody');
  if (!tbody) return;
  const search   = (document.getElementById('search-participants')?.value || '').toLowerCase();
  const chalFilt = document.getElementById('challenge-filter')?.value   || '';
  const progFilt = document.getElementById('progress-filter')?.value    || '';

  filteredParticipants = adminParticipants.filter(p => {
    const hay = `${p.nom||''} ${p.email||''} ${p.challenge_titre||''}`.toLowerCase();
    if (search && !hay.includes(search)) return false;
    if (chalFilt && String(p.id_challenge) !== chalFilt) return false;
    const prog = clampInt(p.objectif, 0, 100);
    if (progFilt === 'low'    && !(prog <= 30))          return false;
    if (progFilt === 'medium' && !(prog >= 31 && prog <= 70)) return false;
    if (progFilt === 'high'   && !(prog >= 71))          return false;
    return true;
  });

  if (!filteredParticipants.length) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:12px;">👤</div><div>Aucun participant trouvé</div>
    </td></tr>`;
    updateParticipantsPagination(0, 0);
    return;
  }
  participantsPage = 1;
  renderParticipantsPage();
}

function renderParticipantRows(rows) {
  const tbody = document.getElementById('participants-tbody');
  if (!tbody) return;
  tbody.innerHTML = rows.map(p => {
    const nom  = String(p.nom  || '');
    const prog = clampInt(p.objectif, 0, 100);
    const target = clampInt(p.challenge_target, 0, 100);
    const engBadge = p.engagement == 1
      ? `<span class="adm-engage-badge adm-engage-badge--on">🔥 Engagé</span>`
      : `<span class="adm-engage-badge adm-engage-badge--off">😴 Inactif</span>`;
    return `
      <tr class="participant-row-admin">
        <td>
          <div class="adm-participant-cell">
            <div class="adm-avatar" style="background:${getAvatarColor(nom)}">${getInitials(nom)}</div>
            <div class="adm-participant-info">
              <span class="adm-participant-name">${escapeHtml(nom)}</span>
              <span class="adm-participant-email">${escapeHtml(p.email||'')}</span>
            </div>
          </div>
        </td>
        <td><div class="challenge-tag">
          <span class="tag-icon">${p.challenge_icon||'🏆'}</span>
          <span class="tag-text">${escapeHtml(p.challenge_titre||'')}</span>
        </div></td>
        <td>
          <div class="progress-wrapper-large">
            <div class="progress-header-flex"><span class="progress-value-text">${prog}%</span></div>
            <div class="progress-bar-large">
              <div class="progress-fill-large" style="width:${prog}%;background:linear-gradient(90deg,#f44336,#FFC107,#4CAF50);background-size:100px 100%;"></div>
            </div>
          </div>
        </td>
        <td><div class="challenge-tag"><span class="tag-icon">🎯</span><span class="tag-text">${target}%</span></div></td>
        <td><span class="participant-email">${escapeHtml(formatParticipantDate(p.date_inscription))}</span></td>
        <td>${engBadge}</td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon delete" onclick="deleteParticipant(${p.id},${p.id_challenge})" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>`;
  }).join('');
}

function renderParticipantsPage() {
  const total      = filteredParticipants.length;
  const totalPages = Math.max(1, Math.ceil(total / PARTICIPANTS_PER_PAGE));
  participantsPage = Math.min(Math.max(1, participantsPage), totalPages);
  const start = (participantsPage - 1) * PARTICIPANTS_PER_PAGE;
  const slice = filteredParticipants.slice(start, start + PARTICIPANTS_PER_PAGE);

  const info = document.getElementById('participants-pagination-info');
  if (info) info.textContent = `Affichage de ${total > 0 ? start + 1 : 0} à ${Math.min(start + PARTICIPANTS_PER_PAGE, total)} sur ${total} participants`;

  const btns = document.querySelectorAll('.adm-pagination .pagination-controls .adm-btn');
  if (btns[0]) btns[0].disabled = participantsPage <= 1;
  if (btns[1]) btns[1].disabled = participantsPage >= totalPages;

  renderParticipantRows(slice);
}

function updateParticipantsPagination(visible, total = visible) {
  const el = document.getElementById('participants-pagination-info');
  if (el) el.innerText = `Affichage de ${total > 0 ? 1 : 0} à ${visible} sur ${total} participants`;
}

function updateParticipantsStats() {
  const total  = adminParticipants.length;
  const active = adminParticipants.filter(p => clampInt(p.engagement, 0, 1) === 1).length;
  const eng    = total > 0 ? Math.round((active / total) * 100) : 0;
  const elT = document.getElementById('participants-total');
  const elA = document.getElementById('participants-active');
  const elE = document.getElementById('participants-engagement');
  if (elT) elT.innerText = total.toLocaleString('fr-FR');
  if (elA) elA.innerText = active.toLocaleString('fr-FR');
  if (elE) elE.innerText = eng + '%';
}

function searchParticipants()          { renderParticipantsTable(); }
function filterParticipantsByChallenge(){ renderParticipantsTable(); }
function filterParticipantsByProgress() { renderParticipantsTable(); }

function exportParticipants() {
  const rows = adminParticipants.map(p => ({
    id: p.id, challenge: p.challenge_titre||'', nom: p.nom||'', email: p.email||'',
    progression: clampInt(p.objectif,0,100), engagement: p.engagement??'',
    date_inscription: p.date_inscription??''
  }));
  const header = Object.keys(rows[0] || { id:'' });
  const csv = [header.join(','), ...rows.map(r => header.map(k => csvEscape(r[k])).join(','))].join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type:'text/csv;charset=utf-8;' }));
  a.download = 'participants.csv';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(a.href);
}

function viewParticipant(id) {
  const p = adminParticipants.find(x => String(x.id) === String(id));
  if (p) openParticipantModal(p);
}

function openParticipantModal(p) {
  let overlay = document.getElementById('adm-participant-modal');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'adm-participant-modal';
    overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99000;background:rgba(0,0,0,.72);backdrop-filter:blur(7px);align-items:center;justify-content:center;padding:20px;';
    overlay.innerHTML = `<div id="adm-pm-card" style="position:relative;width:520px;max-width:96vw;max-height:90vh;overflow-y:auto;background:linear-gradient(160deg,rgba(15,35,24,.98),rgba(10,26,16,.99));border:1px solid rgba(91,62,150,.38);border-radius:24px;box-shadow:0 30px 70px rgba(0,0,0,.6);">
      <div style="padding:28px 24px 20px;background:linear-gradient(180deg,rgba(91,62,150,.12),transparent);border-bottom:1px solid rgba(91,62,150,.18);text-align:center;">
        <div style="width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,rgba(91,62,150,.3),rgba(58,134,196,.2));border:2.5px solid rgba(91,62,150,.4);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 14px;">👤</div>
        <h3 id="adm-pm-name" style="font-size:1.5rem;font-weight:700;color:#F2E8CF;margin:0 0 5px;"></h3>
        <p id="adm-pm-email" style="font-size:.82rem;color:#a8b8a0;margin:0;"></p>
      </div>
      <div style="padding:20px 22px 8px;" id="adm-pm-body"></div>
      <div style="padding:14px 22px 22px;">
        <button onclick="document.getElementById('adm-participant-modal').style.display='none'" style="width:100%;padding:12px;border-radius:13px;border:1.5px solid rgba(91,62,150,.35);background:rgba(91,62,150,.1);color:#F2E8CF;font-size:.87rem;cursor:pointer;">Fermer</button>
      </div>
    </div>`;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.style.display = 'none'; });
  }
  const prog = clampInt(p.objectif, 0, 100);
  const target = clampInt(p.challenge_target || p.objectif_defi, 0, 100);
  const progColor = prog >= target ? '#2ecc71' : prog >= target * 0.6 ? '#f1c40f' : '#e74c3c';
  document.getElementById('adm-pm-name').textContent  = p.nom   || '—';
  document.getElementById('adm-pm-email').textContent = p.email || '—';
  document.getElementById('adm-pm-body').innerHTML = `
    <div style="background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px 18px;margin-bottom:12px;">
      <div style="font-size:.68rem;color:rgba(168,184,160,.6);margin-bottom:4px;">DÉFI</div>
      <div style="font-size:.9rem;color:#F2E8CF;font-weight:600;margin-bottom:12px;">${escapeHtml(p.challenge_titre||String(p.id_challenge)||'—')}</div>
      <div style="height:8px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;margin-bottom:8px;">
        <div style="height:100%;width:${prog}%;background:${progColor};border-radius:99px;"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;">
        <span>Progression : <b style="color:${progColor}">${prog}%</b></span>
        <span>Objectif : ${target}%</span>
      </div>
    </div>
    <div style="background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px 18px;margin-bottom:12px;">
      <div style="font-size:.68rem;color:rgba(168,184,160,.6);margin-bottom:6px;">💬 MOTIVATION</div>
      <div style="color:rgba(242,232,207,.7);font-style:italic;line-height:1.6;">${escapeHtml(p.motivation||'—')}</div>
    </div>
    <div style="background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px 18px;">
      <div style="font-size:.68rem;color:rgba(168,184,160,.6);margin-bottom:6px;">⚡ PLAN D'ACTION</div>
      <div style="color:#F2E8CF;line-height:1.6;">${escapeHtml(p.action||'—')}</div>
    </div>`;
  overlay.style.display = 'flex';
}

function deleteParticipant(id, idChallenge) {
  if (!confirm('Retirer ce participant du défi ?')) return;
  fetch(`challenges/deleteParticipant.php?id=${id}&id_challenge=${idChallenge}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(result => {
      if (result?.success) {
        showToast('Participant supprimé', 'Retiré du défi avec succès.', 'success');
        loadAdminParticipants();
      } else {
        showToast('Erreur', 'Impossible de supprimer.', 'error');
      }
    })
    .catch(() => showToast('Erreur serveur', 'Connexion impossible.', 'error'));
}

function openAdmModal(msg, onConfirm) {
  const msgEl = document.getElementById('adm-modal-msg');
  const modal = document.getElementById('adm-confirm-modal');
  const btn   = document.getElementById('adm-modal-confirm');
  if (!msgEl || !modal || !btn) { onConfirm(); return; }
  msgEl.textContent = msg;
  modal.style.display = 'flex';
  btn.onclick = () => { closeAdmModal(); onConfirm(); };
}
function closeAdmModal() {
  const modal = document.getElementById('adm-confirm-modal');
  if (modal) modal.style.display = 'none';
}

function formatParticipantDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  return isNaN(d.getTime()) ? String(value)
    : d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
}

function getInitials(nom) {
  return (nom || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function getAvatarColor(nom) {
  const colors = ['#5B3E96','#3A86C4','#27ae60','#e67e22','#e74c3c','#8e44ad'];
  let hash = 0;
  for (const c of (nom || '')) hash = c.charCodeAt(0) + ((hash << 5) - hash);
  return colors[Math.abs(hash) % colors.length];
}

function clampInt(value, min, max) {
  const n = parseInt(value, 10);
  return isNaN(n) ? min : Math.min(max, Math.max(min, n));
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function csvEscape(value) {
  const s = String(value ?? '');
  return (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r'))
    ? `"${s.replace(/"/g, '""')}"` : s;
}

// ─── Exposer globalement ──────────────────────────────────────
window.loadAdminChallenges            = loadAdminChallenges;
window.loadAdminParticipants          = loadAdminParticipants;
window.loadStatistiques               = loadStatistiques;
window.renderChallengesTable          = renderChallengesTable;
window.renderParticipantsPage         = renderParticipantsPage;
window.closeAdmModal                  = closeAdmModal;
window.showChallengeParticipants      = showChallengeParticipants;
window.closeChallengeParticipantsPanel = closeChallengeParticipantsPanel;
window.updateStatutChallenge          = updateStatutChallenge;
window.toggleStatutDropdown           = toggleStatutDropdown;
window.exportCSV                      = exportCSV;
window.exportPDF                      = exportPDF;
window.saveOrder                      = saveOrder;
window.openNotifModal                 = openNotifModal;
window.closeNotifModal                = closeNotifModal;
window.sendNotification               = sendNotification;
window.toggleStats                    = toggleStats;
window.showToast                      = showToast;
window.adminChallenges                = () => adminChallenges;
window.adminParticipants              = () => adminParticipants;
