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

document.addEventListener('click', (e) => {
  const sampleBtn = e.target.closest('#challenge-ai-sample');
  if (sampleBtn) {
    const promptEl = document.getElementById('challenge-ai-prompt');
    if (promptEl) {
      promptEl.value = getChallengeAISamplePrompt();
      promptEl.focus();
      setChallengeAIStatus('Exemple chargé. Cliquez sur Générer le défi.');
    }
    return;
  }

  const generateBtn = e.target.closest('#challenge-ai-generate');
  if (generateBtn) {
    e.preventDefault();
    generateChallengeWithAI();
    return;
  }

  const imageBtn = e.target.closest('#challenge-ai-image-generate');
  if (imageBtn) {
    e.preventDefault();
    generateChallengeImageWithAI();
  }
});

// ═══════════════════════════════════════════════════════════
// ÉTAT GLOBAL
// ═══════════════════════════════════════════════════════════
let adminChallenges        = [];
let adminParticipants      = [];
let adminCalendarDate      = new Date();
let adminCalendarSelected  = '';
let filteredParticipants   = [];
let selectedChallengeParticipants = [];
let challengesPage         = 1;
let participantsPage       = 1;
let editingParticipantId   = null;
let currentCoachParticipantId = null;
let previewImageLoadToken  = 0;
const CHALLENGES_PER_PAGE = 6;
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
  injectAISummaryModal();
  injectSortableScript();
  injectStatsSectionIfMissing();
  injectChallengeCalendarIfMissing();
  injectStatsUserSectionIfMissing();
  injectAdminInsightButtons();
  initChallengeAI();
}

function injectAISummaryModal() {
  if (document.getElementById('gl-ai-summary-modal')) return;
  const div = document.createElement('div');
  div.id = 'gl-ai-summary-modal';
  div.className = 'adm-modal-overlay';
  div.style.cssText = `
    display:none; position:fixed; inset:0; z-index:99999;
    background:rgba(0,0,0,0.75); backdrop-filter:blur(8px);
    align-items:center; justify-content:center; padding:20px;
    cursor: default;
  `;
  div.innerHTML = `
    <div class="adm-modal-container" style="
      background:#1e1e2e; border:1px solid #6366f1; border-radius:18px;
      width:700px; max-width:100%; max-height:90vh; overflow-y:auto;
      position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.5);
      cursor: auto;
    ">
      <div style="position:sticky; top:0; background:#1e1e2e; padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.1); z-index:10; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="color:#e2e8f0; margin:0; display:flex; align-items:center; gap:10px;">
            <span style="background:linear-gradient(135deg,#6366f1,#8b5cf6); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">✨ Résumé IA du Défi</span>
          </h3>
          <p id="ai-summary-challenge-title" style="color:#94a3b8; font-size:13px; margin:4px 0 0;"></p>
        </div>
        <button onclick="closeAISummaryModal()" style="background:none; border:none; color:#94a3b8; font-size:1.5rem; cursor:pointer; padding:5px; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">✕</button>
      </div>
      
      <div id="ai-summary-content" style="padding:24px;">
        <div class="adm-analytics-empty">Chargement de l'analyse...</div>
      </div>
      
      <div style="padding:16px 24px; border-top:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
        <span id="ai-summary-footer" style="color:rgba(148,163,184,0.5); font-size:11px;"></span>
        <button id="ai-summary-refresh-btn" class="adm-btn adm-btn--ghost adm-btn--sm" style="font-size:11px; cursor: pointer;">🔄 Rafraîchir l'analyse</button>
      </div>
    </div>
  `;
  document.body.appendChild(div);
  div.addEventListener('click', e => { if (e.target === div) closeAISummaryModal(); });
}

function closeAISummaryModal() {
  const m = document.getElementById('gl-ai-summary-modal');
  if (m) m.style.display = 'none';
}

async function showAISummaryToday(id) {
  const tabToday = document.getElementById('ai-summary-tab-today');
  const tabHistory = document.getElementById('ai-summary-tab-history');
  if (tabToday) tabToday.classList.add('active');
  if (tabHistory) tabHistory.classList.remove('active');
  showChallengeAISummary(id);
}

async function showAISummaryHistory(id) {
  const tabToday = document.getElementById('ai-summary-tab-today');
  const tabHistory = document.getElementById('ai-summary-tab-history');
  const content = document.getElementById('ai-summary-tab-content');
  if (tabToday) tabToday.classList.remove('active');
  if (tabHistory) tabHistory.classList.add('active');

  if (content) content.innerHTML = '<div style="text-align:center; padding:40px;"><div class="adm-table-loading">⌛ Récupération de l\'historique...</div></div>';

  try {
    const resp = await fetch('api/ai-challenge-summary.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ challenge_id: id, mode: 'history' })
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error);

    if (!data.history || data.history.length === 0) {
      content.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;">Aucun historique disponible pour ce défi.</div>';
      return;
    }

    content.innerHTML = `
      <div style="display:flex; flex-direction:column; gap:12px;">
        ${data.history.map(h => `
          <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:15px; border-radius:12px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
              <span style="color:#6366f1; font-weight:700; font-size:12px;">Score Santé: ${h.summary.score_sante}%</span>
              <span style="color:#94a3b8; font-size:11px;">${new Date(h.created_at).toLocaleString('fr-FR')}</span>
            </div>
            <p style="color:#e2e8f0; font-size:13px; margin:0 0 8px 0;">${escapeHtml(h.summary.synthese_participants)}</p>
            <div style="font-size:11px; color:#94a3b8;">${escapeHtml(h.summary.tendances_engagement)}</div>
          </div>
        `).join('')}
      </div>
    `;
  } catch (err) {
    if (content) content.innerHTML = `<div style="text-align:center; padding:40px; color:#ef4444;">⚠️ Erreur : ${escapeHtml(err.message)}</div>`;
  }
}

async function showChallengeAISummary(id, force = false) {
  const modal = document.getElementById('gl-ai-summary-modal');
  const content = document.getElementById('ai-summary-content');
  const titleEl = document.getElementById('ai-summary-challenge-title');
  const footer = document.getElementById('ai-summary-footer');
  const refreshBtn = document.getElementById('ai-summary-refresh-btn');
  
  const c = adminChallenges.find(x => String(x.id) === String(id));
  if (titleEl) titleEl.textContent = c ? `${c.streak_icon || '🏆'} ${c.titre}` : `Défi #${id}`;
  
  if (modal) modal.style.display = 'flex';
  if (content) content.innerHTML = '<div style="text-align:center; padding:40px;"><div class="adm-table-loading">⌛ L\'IA analyse le défi et ses participants...</div></div>';
  if (footer) footer.textContent = '';
  if (refreshBtn) refreshBtn.onclick = () => showChallengeAISummary(id, true);

  const formatAiValue = (val) => {
    if (!val) return '-';
    if (typeof val === 'string') return val;
    if (Array.isArray(val)) return val.join('\n');
    if (typeof val === 'object') return Object.values(val).join(' ');
    return String(val);
  };

  try {
    const resp = await fetch('api/ai-challenge-summary.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ challenge_id: id, force_refresh: force })
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || 'Erreur inconnue');

    const s = data.summary;
    const score = parseInt(s.score_sante || 0);
    const scoreColor = score >= 80 ? '#22c55e' : score >= 50 ? '#f59e0b' : '#ef4444';
    const scoreLabel = score >= 80 ? 'Excellent' : score >= 50 ? 'Moyen' : 'Critique';

    // Rendu enrichi selon le nouveau design
    content.innerHTML = `
      <div style="display:flex; gap:10px; margin-bottom:24px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:16px;">
        <button id="ai-summary-tab-today" class="adm-btn adm-btn--ghost adm-btn--sm active" style="border-radius:8px;" onclick="showAISummaryToday(${id})">Aujourd'hui</button>
        <button id="ai-summary-tab-history" class="adm-btn adm-btn--ghost adm-btn--sm" style="border-radius:8px;" onclick="showAISummaryHistory(${id})">Historique</button>
      </div>

      <div id="ai-summary-tab-content">
        <div style="display:grid; grid-template-columns: 1fr 220px; gap:20px;">
          <!-- Colonne Gauche : Synthèse & Tendances -->
          <div style="display:flex; flex-direction:column; gap:16px;">
            <div style="background:rgba(255,255,255,0.03); padding:16px; border-radius:14px; border:1px solid rgba(255,255,255,0.05);">
              <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span style="background:rgba(99,102,241,0.1); padding:6px; border-radius:8px;">👤</span>
                <h4 style="margin:0; font-size:12px; text-transform:uppercase; color:#94a3b8; letter-spacing:0.5px;">Synthèse Participants</h4>
              </div>
              <p style="color:#e2e8f0; font-size:13px; line-height:1.6; margin:0;">${escapeHtml(formatAiValue(s.synthese_participants))}</p>
            </div>

            <div style="background:rgba(255,255,255,0.03); padding:16px; border-radius:14px; border:1px solid rgba(255,255,255,0.05);">
              <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span style="background:rgba(34,197,94,0.1); padding:6px; border-radius:8px;">📊</span>
                <h4 style="margin:0; font-size:12px; text-transform:uppercase; color:#94a3b8; letter-spacing:0.5px;">Tendances & Engagement</h4>
              </div>
              <p style="color:#e2e8f0; font-size:13px; line-height:1.6; margin:0;">${escapeHtml(formatAiValue(s.tendances_engagement))}</p>
            </div>
          </div>

          <!-- Colonne Droite : Score & Mini-stats -->
          <div style="display:flex; flex-direction:column; gap:16px;">
            <div style="background:rgba(255,255,255,0.03); padding:20px; border-radius:18px; border:1px solid rgba(255,255,255,0.05); text-align:center; position:relative;">
              <div style="font-size:10px; text-transform:uppercase; color:#94a3b8; margin-bottom:15px;">Score de Santé</div>
              <div style="width:100px; height:100px; margin:0 auto; position:relative; display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 36 36" style="width:100%; height:100%; transform: rotate(-90deg);">
                  <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="3" />
                  <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="${scoreColor}" stroke-width="3" stroke-dasharray="${score}, 100" stroke-linecap="round" />
                </svg>
                <div style="position:absolute; font-size:28px; font-weight:800; color:#e2e8f0;">${score}</div>
              </div>
              <div style="margin-top:10px; font-size:11px; color:${scoreColor}; font-weight:600;">${scoreLabel}</div>
              <div style="display:flex; gap:3px; justify-content:center; margin-top:10px;">
                ${[1,2,3,4,5,6,7].map(i => `<div style="width:6px; height:12px; border-radius:2px; background:${i <= score/14 ? scoreColor : 'rgba(255,255,255,0.05)'}"></div>`).join('')}
              </div>
            </div>
          </div>
        </div>

        <!-- Ligne de Mini-KPIs -->
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin:20px 0;">
          <div style="background:rgba(255,255,255,0.02); padding:12px; border-radius:10px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
            <div style="font-size:18px; font-weight:700; color:#e2e8f0;">${c.participants_count || 0}</div>
            <div style="font-size:9px; color:#94a3b8; text-transform:uppercase;">Participants</div>
          </div>
          <div style="background:rgba(255,255,255,0.02); padding:12px; border-radius:10px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
            <div style="font-size:18px; font-weight:700; color:#22c55e;">${Math.round(c.avg_progress || 0)}%</div>
            <div style="font-size:9px; color:#94a3b8; text-transform:uppercase;">Progression moy.</div>
          </div>
          <div style="background:rgba(255,255,255,0.02); padding:12px; border-radius:10px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
            <div style="font-size:18px; font-weight:700; color:#f59e0b;">${Math.round(c.avg_engagement || 0)}%</div>
            <div style="font-size:9px; color:#94a3b8; text-transform:uppercase;">Engagement</div>
          </div>
        </div>

        <!-- Points de vigilance -->
        <div style="background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.2); padding:16px; border-radius:14px; margin-bottom:24px;">
          <div style="display:flex; align-items:center; gap:8px; color:#ef4444; font-size:12px; font-weight:700; margin-bottom:8px; text-transform:uppercase;">
            <span style="width:8px; height:8px; border-radius:50%; background:#ef4444;"></span>
            Points de vigilance
          </div>
          <p style="color:#fca5a5; font-size:13px; line-height:1.5; margin:0;">${escapeHtml(formatAiValue(s.points_vigilance))}</p>
        </div>

        <!-- Recommandations -->
        <div>
          <div style="display:flex; align-items:center; gap:8px; color:#f59e0b; font-size:12px; font-weight:700; margin-bottom:16px; text-transform:uppercase;">
            <span style="font-size:14px;">💡</span>
            Recommandations stratégiques
          </div>
          <div style="display:flex; flex-direction:column; gap:10px;">
            ${(Array.isArray(s.recommandations) ? s.recommandations : []).map(rec => `
              <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:12px 16px; border-radius:12px; display:flex; align-items:center; gap:12px;">
                <span style="width:6px; height:6px; border-radius:50%; background:#818cf8;"></span>
                <span style="flex:1; color:#e2e8f0; font-size:13px;">${escapeHtml(rec)}</span>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
    
    if (footer) footer.textContent = `Généré le ${data.generated_at} ${data.cached ? '(depuis le cache)' : '(nouvelle analyse)'}`;

  } catch (err) {
    content.innerHTML = `<div style="text-align:center; padding:40px; color:#ef4444;">⚠️ Erreur : ${escapeHtml(err.message)}</div>`;
  }
}

function injectAdminInsightButtons() {
  const hero = document.querySelector('#challenges .adm-hero-stats, #challenges .adm-hero-inner');
  if (!hero || document.getElementById('adm-calendar-toggle-btn')) return;

  const calendarBtn = document.createElement('button');
  calendarBtn.id = 'adm-calendar-toggle-btn';
  calendarBtn.type = 'button';
  calendarBtn.className = 'adm-btn adm-btn--ghost adm-btn--sm adm-stats-toggle-btn';
  calendarBtn.onclick = toggleChallengeCalendar;
  calendarBtn.innerHTML = '📅 Calendrier';
  hero.appendChild(calendarBtn);

  const statsUserBtn = document.createElement('button');
  statsUserBtn.id = 'adm-stats-user-toggle-btn';
  statsUserBtn.type = 'button';
  statsUserBtn.className = 'adm-btn adm-btn--ghost adm-btn--sm adm-stats-toggle-btn';
  statsUserBtn.onclick = toggleStatsUserPredictions;
  statsUserBtn.innerHTML = '🧠 StatsUser IA';
  hero.appendChild(statsUserBtn);
}

function initChallengeAI() {
  const box = document.getElementById('challenge-ai-box');
  const promptEl = document.getElementById('challenge-ai-prompt');
  const generateBtn = document.getElementById('challenge-ai-generate');
  if (!box || !promptEl || !generateBtn || box.dataset.bound === '1') return;
  box.dataset.bound = '1';
  setChallengeAIStatus('');
}

function getChallengeAISamplePrompt() {
  return 'Crée un défi collectif de 14 jours pour réduire les bouteilles plastiques au campus, avec une cible de 40%, un ton motivant et une catégorie déchets.';
}

function setChallengeAIStatus(text, type = '') {
  const status = document.getElementById('challenge-ai-status');
  if (!status) return;
  status.textContent = text || '';
  status.classList.remove('ok', 'error');
  if (type) status.classList.add(type);
}

function setChallengeImageAIStatus(text, type = '') {
  const status = document.getElementById('challenge-ai-image-status');
  if (!status) return;
  status.textContent = text || '';
  status.classList.remove('ok', 'error');
  if (type) status.classList.add(type);
}

function getSelectedChallengeTypeLabel() {
  const checked = document.querySelector('input[name="type"]:checked');
  if (!checked) return 'collectif';
  return checked.value === 'individuel' ? 'individuel' : 'collectif';
}

function getSelectedChallengeCategoryLabel() {
  const select = document.getElementById('challenge-objectif');
  if (!select) return '';
  const option = select.options[select.selectedIndex];
  return (option?.textContent || select.value || '').replace(/\s+/g, ' ').trim();
}

function collectChallengeImagePromptData() {
  return {
    titre: (document.getElementById('challenge-titre')?.value || '').trim(),
    description: (document.getElementById('challenge-description')?.value || '').trim(),
    type: getSelectedChallengeTypeLabel(),
    objectif: document.getElementById('challenge-objectif')?.value || '',
    objectif_label: getSelectedChallengeCategoryLabel(),
    valeur_cible: parseInt(document.getElementById('challenge-valeur')?.value || '0', 10) || 0,
    date_debut: document.getElementById('challenge-date-debut')?.value || '',
    date_fin: document.getElementById('challenge-date-fin')?.value || '',
    streak_icon: document.getElementById('challenge-streak-icon')?.value || '🏆',
  };
}

function validateChallengeImagePromptData(data) {
  if (!data.titre || data.titre.length < 3) return 'Remplissez au moins le titre du défi.';
  if (!data.description || data.description.length < 10) return 'Ajoutez une description pour guider l’image IA.';
  if (!data.objectif) return 'Choisissez une catégorie.';
  return '';
}

async function generateChallengeImageWithAI() {
  const btn = document.getElementById('challenge-ai-image-generate');
  const imageInput = document.getElementById('challenge-image');
  const data = collectChallengeImagePromptData();
  const error = validateChallengeImagePromptData(data);

  if (error) {
    setChallengeImageAIStatus(error, 'error');
    return;
  }

  const original = btn ? btn.textContent : '';
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Analyse...';
  }
  setChallengeImageAIStatus('Analyse du formulaire et création de l’image...');

  try {
    const resp = await fetch('api/ai-challenge-image.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(data),
    });
    const result = await resp.json().catch(() => ({}));
    if (!resp.ok || !result.ok || !result.image_url) {
      throw new Error(result.error || `HTTP ${resp.status || 'inconnu'}`);
    }

    if (imageInput) {
      imageInput.value = result.image_url;
      imageInput.dispatchEvent(new Event('input', { bubbles: true }));
      imageInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    updatePreview();
    setChallengeImageAIStatus('Image générée depuis les champs du défi.', 'ok');
    showToast('Image IA générée', 'Le champ Image URL a été rempli.', 'success');
  } catch (err) {
    console.error('Erreur image IA:', err);
    setChallengeImageAIStatus(err.message || 'Impossible de générer l’image.', 'error');
    showToast('Erreur image IA', err.message || 'Impossible de générer l’image.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = original || 'Image IA';
    }
  }
}

async function generateChallengeWithAI() {
  const promptEl = document.getElementById('challenge-ai-prompt');
  const btn = document.getElementById('challenge-ai-generate');
  const prompt = (promptEl?.value || '').trim();
  if (prompt.length < 8) {
    setChallengeAIStatus('Ajoutez une idée un peu plus précise.', 'error');
    return;
  }

  const original = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = 'Génération...';
  }
  setChallengeAIStatus('L’IA prépare le brouillon...');

  try {
    const resp = await fetch('api/ai-challenge-generator.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ prompt }),
    });
    const data = await resp.json().catch(() => ({}));
    console.log('Réponse IA défi:', { status: resp.status, ok: resp.ok, data });
    if (!resp.ok || !data.ok || !data.challenge) {
      const detail = typeof data.detail === 'string'
        ? data.detail
        : (data.detail ? JSON.stringify(data.detail) : '');
      const message = detail || data.error || `HTTP ${resp.status || 'inconnu'}`;
      throw new Error(message);
    }

    fillChallengeFormFromAI(data.challenge);
    setChallengeAIStatus('Brouillon généré. Vérifiez puis publiez le défi.', 'ok');
    showToast('Défi généré', 'Le formulaire a été rempli par l’IA.', 'success');
  } catch (err) {
    console.error('Erreur génération défi IA:', err);
    setChallengeAIStatus(err.message || 'Erreur IA', 'error');
    showToast('Erreur IA', err.message || 'Impossible de générer le défi', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  }
}

function fillChallengeFormFromAI(challenge) {
  const setVal = (id, value) => {
    const el = document.getElementById(id);
    if (!el || value === undefined || value === null) return;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  };

  setVal('challenge-titre', challenge.titre || '');
  setVal('challenge-description', challenge.description || '');
  setVal('challenge-objectif', challenge.objectif || 'dechets');
  setVal('challenge-valeur', challenge.valeur_cible || 50);
  setVal('challenge-date-debut', challenge.date_debut || '');
  setVal('challenge-date-fin', challenge.date_fin || '');
  setVal('challenge-statut', challenge.statut || 'actif');
  setVal('challenge-streak-icon', challenge.streak_icon || '🏆');
  setVal('challenge-image', challenge.image || '');

  document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.checked = radio.value === (challenge.type || 'collectif');
    radio.dispatchEvent(new Event('change', { bubbles: true }));
  });

  updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);
  clearErrors();
  updatePreview();
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
  const challenges = document.getElementById('challenges');
  if (!challenges) return;

  // Si la section existe déjà, on vérifie si l'onglet paiement est là
  let section = document.getElementById('gl-stats-section');
  if (section && document.querySelector('button[onclick*="payments"]')) return;

  // Sinon on (re)crée ou on met à jour
  if (!section) {
    section = document.createElement('div');
    section.id = 'gl-stats-section';
    section.className = 'adm-frame adm-frame--section adm-analytics-frame';
    section.style.display = 'none';
    challenges.insertBefore(section, challenges.firstChild);
  }

  section.innerHTML = `
    <div class="adm-corner adm-corner--tl"></div>
    <div class="adm-corner adm-corner--tr"></div>
    <div class="adm-corner adm-corner--br"></div>
    <div class="adm-top-edge adm-top-edge--accent"></div>

    <div class="adm-analytics-head">
      <div>
        <div class="adm-analytics-eyebrow">Analytique & Prédictions</div>
        <h3 class="adm-analytics-title">Statistiques Globales des Défis</h3>
      </div>
      <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="renderStatsCharts()">
        Actualiser les graphiques
      </button>
    </div>

    <!-- KPIs Row -->
    <div id="gl-stats-kpis" class="adm-calendar-ai-kpis" style="margin-bottom:24px;">
      <!-- Rempli dynamiquement -->
    </div>

    <!-- Tabs Navigation -->
    <div class="adm-tabs-nav" style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
      <button class="adm-btn adm-btn--ghost adm-btn--sm active" onclick="switchStatsTab(this, 'sigmoid')">Courbes S</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'participants')">Participants / défi</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'types')">Répartition types</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'views')">Vues & likes</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'payments')">Paiements</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'scatter')">Engagement vs risque</button>
      <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="switchStatsTab(this, 'insights')">Insights IA</button>
    </div>

    <!-- Tab Contents -->
    <div id="gl-stats-tab-content">
      <div id="tab-sigmoid" class="stats-tab-pane">
        <div class="adm-analytics-card">
          <h4>ADOPTION COLLECTIVE — PROGRESSION SIGMOÏDE PAR TYPE DE DÉFI SUR 30 JOURS</h4>
          <div style="height: 240px; position: relative;">
            <canvas id="chart-gl-sigmoid"></canvas>
          </div>
          <p style="font-size:10px; color:#94a3b8; margin-top:10px;">Le point d'inflexion (milieu de la courbe) indique à quel jour 50% des participants ont atteint leur objectif.</p>
        </div>
      </div>
      <div id="tab-participants" class="stats-tab-pane" style="display:none;">
        <div class="adm-analytics-card">
          <h4>PARTICIPANTS PAR DÉFI — RÉPARTITION PAR STATUT</h4>
          <div style="height: 350px; position: relative;">
            <canvas id="chart-gl-participants"></canvas>
          </div>
        </div>
      </div>
      <div id="tab-types" class="stats-tab-pane" style="display:none;">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
          <div class="adm-analytics-card">
            <h4>RÉPARTITION DES DÉFIS PAR TYPE</h4>
            <div style="height: 220px; position: relative;">
              <canvas id="chart-gl-types"></canvas>
            </div>
          </div>
          <div class="adm-analytics-card">
            <div id="gl-top3-lists">
              <!-- Rempli dynamiquement -->
            </div>
          </div>
        </div>
      </div>
      <div id="tab-views" class="stats-tab-pane" style="display:none;">
        <div class="adm-analytics-card">
          <h4>ÉVOLUTION VUES ET LIKES — DÉFIS TRIÉS PAR DATE DE DÉBUT</h4>
          <div style="height: 240px; position: relative;">
            <canvas id="chart-gl-views"></canvas>
          </div>
        </div>
      </div>
      <div id="tab-payments" class="stats-tab-pane" style="display:none;">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
          <div class="adm-analytics-card">
            <h4>REVENUS PAR MÉTHODE DE PAIEMENT</h4>
            <div style="height: 220px; position: relative;">
              <canvas id="chart-gl-payments-method"></canvas>
            </div>
          </div>
          <div class="adm-analytics-card">
            <h4>RÉPARTITION DES STATUTS DE PAIEMENT</h4>
            <div style="height: 220px; position: relative;">
              <canvas id="chart-gl-payments-status"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div id="tab-scatter" class="stats-tab-pane" style="display:none;">
        <div class="adm-analytics-card">
          <h4>ENGAGEMENT VS RISQUE — MATRICE DE PRIORISATION</h4>
          <div style="height: 240px; position: relative;">
            <canvas id="chart-gl-scatter"></canvas>
          </div>
          <p style="font-size:10px; color:#94a3b8; margin-top:10px;">X = Participants (Engagement), Y = Risque (nb_likes/participants). Les points en haut à gauche sont prioritaires.</p>
        </div>
      </div>
      <div id="tab-insights" class="stats-tab-pane" style="display:none;">
        <div class="adm-analytics-card">
          <h4>INSIGHTS AUTOMATIQUES — GÉNÉRÉS DEPUIS ADMINCHALLENGES</h4>
          <div id="gl-stats-insights-list" style="display:flex; flex-direction:column; gap:15px; margin-top:20px;">
            <!-- Rempli dynamiquement -->
          </div>
        </div>
      </div>
    </div>
  `;

  // Insérer au début de la section challenges
  challenges.insertBefore(section, challenges.firstChild);

  // Bouton toggle stats dans le hero
  const hero = challenges.querySelector('.adm-hero-stats, .adm-hero-inner');
  if (hero) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'adm-btn adm-btn--ghost adm-btn--sm adm-stats-toggle-btn';
    btn.onclick = toggleStats;
    btn.innerHTML = '📊 Voir les statistiques';
    hero.appendChild(btn);
  }
}

function switchStatsTab(btn, tabId) {
  btn.parentNode.querySelectorAll('.adm-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.stats-tab-pane').forEach(p => p.style.display = 'none');
  document.getElementById('tab-' + tabId).style.display = 'block';
  renderStatsCharts();
}

// ─── Rafraîchissement automatique ──────────────────────────────
let statsAutoRefreshInterval = null;

function startStatsAutoRefresh() {
  if (statsAutoRefreshInterval) clearInterval(statsAutoRefreshInterval);
  // Rafraîchissement toutes les 30 secondes pour plus de réactivité
  statsAutoRefreshInterval = setInterval(() => {
    const globalStats = document.getElementById('gl-stats-section');
    if (globalStats && globalStats.style.display !== 'none') {
      renderStatsCharts();
    }
    
    const userStats = document.getElementById('adm-stats-user-section');
    if (userStats && userStats.style.display !== 'none') {
      loadStatsUserPredictions();
    }
  }, 30000);
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
  startStatsAutoRefresh();
});

function renderStatsCharts() {
  const data = adminChallenges;
  if (!data || data.length === 0) return;

  // Charger les analytics avancées (incluant les paiements et recommandations)
  fetch('api/challenge-analytics-predictions.php?t=' + Date.now())
    .then(r => r.json())
    .then(analytics => {
      if (!analytics.ok) return;
      const global = analytics.global || {};
      const payStats = global.payment_stats || {};

      // 1. KPIs enrichis
      const totalVues = data.reduce((sum, c) => sum + parseInt(c.nb_vues || 0), 0);
      const totalPart = data.reduce((sum, c) => sum + parseInt(c.participants_count || 0), 0);
      const totalActifs = data.filter(c => c.statut === 'actif').length;
      const revenue = parseFloat(payStats.total_revenue || 0);

      const kpis = document.getElementById('gl-stats-kpis');
      if (kpis) {
        kpis.innerHTML = `
          <div><b>${data.length}</b><small>total défis (${totalActifs} actifs)</small></div>
          <div><b>${totalPart}</b><small>participants total</small></div>
          <div><b>${totalVues}</b><small>vues totales</small></div>
          <div><b style="color:#22c55e;">${revenue.toFixed(2)} DT</b><small>revenus encaissés</small></div>
        `;
      }

      // 2. Recommendations IA réelles depuis l'API
      const insightsList = document.getElementById('gl-stats-insights-list');
      if (insightsList && Array.isArray(analytics.recommendations)) {
        insightsList.innerHTML = analytics.recommendations.map(r => `
          <div style="border-left:4px solid ${r.priority === 'haute' ? '#ef4444' : '#3b82f6'}; padding:10px 15px; background:rgba(255,255,255,0.03); margin-bottom:10px;">
            <b style="color:${r.priority === 'haute' ? '#ef4444' : '#3b82f6'};">${escapeHtml(r.title)}</b><br>
            <span style="font-size:12px; color:#e2e8f0;">${escapeHtml(r.reason)}</span><br>
            <small style="color:#94a3b8;">Action : ${escapeHtml(r.action)}</small>
          </div>
        `).join('');
      }

      // 3. Graphiques de paiement
      renderPaymentCharts(payStats);
    })
    .catch(err => console.error('Erreur analytics stats:', err));

  // Les autres graphiques utilisent les données déjà en mémoire (adminChallenges)
  renderBasicStatsCharts(data);
}

function renderBasicStatsCharts(data) {
  // 2. Sigmoid Chart
  const ctxSigmoid = document.getElementById('chart-gl-sigmoid');
  if (ctxSigmoid) {
    const existing = Chart.getChart(ctxSigmoid);
    if (existing) existing.destroy();
    const types = [...new Set(data.map(c => c.type))];
    const days = Array.from({length: 31}, (_, i) => i);
    const sigmoid = (x, k, x0) => 100 / (1 + Math.exp(-k * (x - x0)));
    const colors = ['#3b82f6', '#22c55e', '#8b5cf6', '#f59e0b', '#ef4444'];
    const datasets = types.map((type, i) => {
      const typeData = data.filter(c => c.type === type);
      const avgPart = typeData.reduce((s, c) => s + parseInt(c.participants_count || 0), 0) / (typeData.length || 1);
      const k = 0.2 + (avgPart / 150); 
      const x0 = 22 - (avgPart / 4);
      return {
        label: type,
        data: days.map(d => sigmoid(d, k, x0)),
        borderColor: colors[i % colors.length],
        tension: 0.4,
        pointRadius: 0
      };
    });
    new Chart(ctxSigmoid, {
      type: 'line',
      data: { labels: days.map(d => 'J' + d), datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { min: 0, max: 100, ticks: { color: '#94a3b8', callback: v => v + '%' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          x: { ticks: { color: '#94a3b8', maxTicksLimit: 7 }, grid: { display: false } }
        },
        plugins: { legend: { labels: { color: '#94a3b8', usePointStyle: true } } }
      }
    });
  }

  // 3. Participants / défi Chart
  const ctxPart = document.getElementById('chart-gl-participants');
  if (ctxPart) {
    const existing = Chart.getChart(ctxPart);
    if (existing) existing.destroy();
    const sorted = [...data].sort((a, b) => b.participants_count - a.participants_count).slice(0, 15);
    const colors = { actif: '#3b82f6', termine: '#22c55e', en_attente: '#f59e0b' };
    new Chart(ctxPart, {
      type: 'bar',
      data: {
        labels: sorted.map(c => c.titre),
        datasets: [{
          label: 'Participants',
          data: sorted.map(c => c.participants_count),
          backgroundColor: sorted.map(c => colors[c.statut] || '#94a3b8'),
          borderRadius: 4
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          y: { ticks: { color: '#e2e8f0', font: { size: 10 } }, grid: { display: false } }
        },
        plugins: { legend: { display: false } }
      }
    });
  }

  // 4. Types Chart (Donut)
  const ctxTypes = document.getElementById('chart-gl-types');
  if (ctxTypes) {
    const existing = Chart.getChart(ctxTypes);
    if (existing) existing.destroy();
    const counts = data.reduce((acc, c) => { acc[c.type] = (acc[c.type] || 0) + 1; return acc; }, {});
    new Chart(ctxTypes, {
      type: 'doughnut',
      data: {
        labels: Object.keys(counts),
        datasets: [{
          data: Object.values(counts),
          backgroundColor: ['#3b82f6', '#22c55e', '#8b5cf6', '#f59e0b', '#ef4444'],
          borderWidth: 0
        }]
      },
      options: {
        cutout: '70%',
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', usePointStyle: true } } }
      }
    });
    const topPart = [...data].sort((a, b) => b.participants_count - a.participants_count).slice(0, 3);
    const topViews = [...data].sort((a, b) => b.nb_vues - a.nb_vues).slice(0, 3);
    const lists = document.getElementById('gl-top3-lists');
    if (lists) {
      lists.innerHTML = `
        <h4 style="margin-bottom:15px; font-size:12px; color:#94a3b8;">TOP 3 PAR PARTICIPANTS</h4>
        ${topPart.map((c, i) => `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px;"><span>${i+1}. ${c.titre}</span><b style="color:#3b82f6">${c.participants_count}</b></div>`).join('')}
        <h4 style="margin-top:25px; margin-bottom:15px; font-size:12px; color:#94a3b8;">TOP 3 LES PLUS VUS</h4>
        ${topViews.map((c, i) => `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:13px;"><span>${i+1}. ${c.titre}</span><b style="color:#8b5cf6">${c.nb_vues}</b></div>`).join('')}
      `;
    }
  }

  // 5. Views & Likes Chart
  const ctxViews = document.getElementById('chart-gl-views');
  if (ctxViews) {
    const existing = Chart.getChart(ctxViews);
    if (existing) existing.destroy();
    const sorted = [...data].sort((a, b) => new Date(a.date_debut) - new Date(b.date_debut)).slice(-12);
    new Chart(ctxViews, {
      type: 'line',
      data: {
        labels: sorted.map(c => c.titre.substring(0, 10) + '...'),
        datasets: [
          { label: 'Vues', data: sorted.map(c => c.nb_vues), borderColor: '#3b82f6', yAxisID: 'y', tension: 0.3 },
          { label: 'Likes', data: sorted.map(c => c.nb_likes), borderColor: '#ef4444', yAxisID: 'y1', tension: 0.3 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { type: 'linear', position: 'left', ticks: { color: '#3b82f6' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          y1: { type: 'linear', position: 'right', ticks: { color: '#ef4444' }, grid: { drawOnChartArea: false } },
          x: { ticks: { color: '#94a3b8', font: { size: 9 } } }
        },
        plugins: { legend: { labels: { color: '#94a3b8' } } }
      }
    });
  }

  // 6. Scatter Chart
  const ctxScatter = document.getElementById('chart-gl-scatter');
  if (ctxScatter) {
    const existing = Chart.getChart(ctxScatter);
    if (existing) existing.destroy();
    new Chart(ctxScatter, {
      type: 'scatter',
      data: {
        datasets: [{
          label: 'Défis',
          data: data.map(c => ({
            x: parseInt(c.participants_count || 0),
            y: (parseInt(c.nb_likes || 0) / (parseInt(c.participants_count || 1))) * 10
          })),
          backgroundColor: '#8b5cf6',
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { title: { display: true, text: 'Participants', color: '#94a3b8' }, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          y: { title: { display: true, text: 'Score Engagement/Risque', color: '#94a3b8' }, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
        },
        plugins: { tooltip: { callbacks: { label: (ctx) => data[ctx.dataIndex].titre } } }
      }
    });
  }

  // 7. Insights IA
  const insightsList = document.getElementById('gl-stats-insights-list');
  if (insightsList) {
    const mostPop = [...data].sort((a, b) => b.participants_count - a.participants_count)[0];
    const mostSeen = [...data].sort((a, b) => b.nb_vues - a.nb_vues)[0];
    const lowEng = data.filter(c => c.participants_count < 10);
    const inAttente = data.filter(c => c.statut === 'en_attente');

    insightsList.innerHTML = `
      <div style="border-left:4px solid #22c55e; padding:10px 15px; background:rgba(34,197,94,0.05);">
        <b style="color:#22c55e;">Le défi le plus populaire est "${mostPop.titre}" avec ${mostPop.participants_count} participants.</b><br>
        <small style="color:#94a3b8;">Action : le mettre en avant sur la page d'accueil pour booster les inscriptions.</small>
      </div>
      <div style="border-left:4px solid #3b82f6; padding:10px 15px; background:rgba(59,130,246,0.05);">
        <b style="color:#3b82f6;">"${mostSeen.titre}" cumule le plus de vues (${mostSeen.nb_vues}) — fort potentiel de conversion.</b><br>
        <small style="color:#94a3b8;">Action : ajouter un CTA d'inscription visible sur la page du défi.</small>
      </div>
      ${inAttente.length > 0 ? `
      <div style="border-left:4px solid #f59e0b; padding:10px 15px; background:rgba(245,158,11,0.05);">
        <b style="color:#f59e0b;">${inAttente.length} défi(s) en attente.</b><br>
        <small style="color:#94a3b8;">Action : valider ou relancer les défis en attente pour augmenter l'offre.</small>
      </div>` : ''}
      ${lowEng.length > 0 ? `
      <div style="border-left:4px solid #ef4444; padding:10px 15px; background:rgba(239,68,68,0.05);">
        <b style="color:#ef4444;">${lowEng.length} défi(s) ont moins de 10 participants.</b><br>
        <small style="color:#94a3b8;">Défis : ${lowEng.map(c => c.titre).join(', ')}.</small>
      </div>` : ''}
    `;
  }
}

function renderPaymentCharts(payStats) {
  const ctxMethod = document.getElementById('chart-gl-payments-method');
  if (ctxMethod && payStats.by_method) {
    const existing = Chart.getChart(ctxMethod);
    if (existing) existing.destroy();
    new Chart(ctxMethod, {
      type: 'bar',
      data: {
        labels: payStats.by_method.map(m => m.methode),
        datasets: [{
          label: 'Revenus (DT)',
          data: payStats.by_method.map(m => m.revenue),
          backgroundColor: '#22c55e',
          borderRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
          x: { ticks: { color: '#94a3b8' } }
        }
      }
    });
  }

  const ctxStatus = document.getElementById('chart-gl-payments-status');
  if (ctxStatus && payStats.by_status) {
    const existing = Chart.getChart(ctxStatus);
    if (existing) existing.destroy();
    const colors = { paye: '#22c55e', en_attente: '#f59e0b', echoue: '#ef4444', rembourse: '#6366f1' };
    new Chart(ctxStatus, {
      type: 'pie',
      data: {
        labels: payStats.by_status.map(s => s.statut),
        datasets: [{
          data: payStats.by_status.map(s => s.total),
          backgroundColor: payStats.by_status.map(s => colors[s.statut] || '#94a3b8'),
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', usePointStyle: true } } }
      }
    });
  }
}

function toggleStats() {
  const s = document.getElementById('gl-stats-section');
  if (!s) return;
  const willOpen = s.style.display === 'none';
  s.style.display = willOpen ? 'block' : 'none';
  if (willOpen) {
    setTimeout(renderStatsCharts, 100);
  }
}

function injectChallengeCalendarIfMissing() {
  if (document.getElementById('adm-challenge-calendar-section')) return;
  const challenges = document.getElementById('challenges');
  if (!challenges) return;

  const section = document.createElement('div');
  section.id = 'adm-challenge-calendar-section';
  section.className = 'adm-frame adm-frame--section adm-calendar-frame';
  section.style.display = 'none';
  section.innerHTML = `
    <div class="adm-corner adm-corner--tl"></div>
    <div class="adm-corner adm-corner--tr"></div>
    <div class="adm-corner adm-corner--br"></div>
    <div class="adm-top-edge adm-top-edge--accent"></div>

    <div class="adm-calendar-head">
      <div>
        <div class="adm-analytics-eyebrow">Planning intelligent</div>
        <h3 class="adm-analytics-title">Calendrier des défis</h3>
      </div>
      <div class="adm-calendar-actions">
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengeCalendarMonth(-1)">←</button>
        <strong id="adm-calendar-month">—</strong>
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengeCalendarMonth(1)">→</button>
      </div>
    </div>

    <div class="adm-calendar-layout">
      <div class="adm-calendar-grid-wrap">
        <div class="adm-calendar-weekdays">
          ${['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'].map(d => `<span>${d}</span>`).join('')}
        </div>
        <div id="adm-calendar-grid" class="adm-calendar-grid"></div>
      </div>
      <aside class="adm-calendar-detail">
        <div class="adm-calendar-detail__date" id="adm-calendar-selected-date">Sélectionnez une date</div>
        <div id="adm-calendar-challenges-list" class="adm-calendar-challenges-list">
          <div class="adm-analytics-empty">Cliquez sur une date contenant un défi.</div>
        </div>
        <div class="adm-calendar-ai">
          <div class="adm-calendar-ai__head">
            <span>AI</span>
            <strong>Analyse IA du jour</strong>
          </div>
          <div id="adm-calendar-ai-output" class="adm-calendar-ai__body">
            Les insights s'affichent après sélection d'une date.
          </div>
        </div>
      </aside>
    </div>
  `;

  const stats = document.getElementById('gl-stats-section');
  if (stats && stats.parentNode === challenges) {
    stats.insertAdjacentElement('afterend', section);
  } else {
    challenges.insertBefore(section, challenges.firstChild);
  }
}

function injectStatsUserSectionIfMissing() {
  if (document.getElementById('adm-stats-user-section')) return;
  const challenges = document.getElementById('challenges');
  if (!challenges) return;

  const section = document.createElement('div');
  section.id = 'adm-stats-user-section';
  section.className = 'adm-frame adm-frame--section adm-stats-user-frame';
  section.style.display = 'none';
  section.innerHTML = `
    <div class="adm-corner adm-corner--tl"></div>
    <div class="adm-corner adm-corner--tr"></div>
    <div class="adm-corner adm-corner--br"></div>
    <div class="adm-top-edge adm-top-edge--accent"></div>

    <div class="adm-calendar-head">
      <div>
        <div class="adm-analytics-eyebrow">Prédiction utilisateur</div>
        <h3 class="adm-analytics-title">statsUser · activité & défis</h3>
      </div>
      <div class="adm-calendar-actions">
        <input id="adm-stats-user-email" class="adm-search" type="email" placeholder="Filtrer par email utilisateur">
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="loadStatsUserPredictions()">Analyser</button>
      </div>
    </div>

    <div id="adm-stats-user-kpis" class="adm-calendar-ai-kpis">
      <div><b>—</b><small>utilisateurs</small></div>
      <div><b>—</b><small>engagement prévu</small></div>
      <div><b>—</b><small>risque moyen</small></div>
    </div>

    <!-- Charts Container -->
    <div class="adm-charts-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:24px;">
      <div class="adm-analytics-card" style="padding: 20px; overflow: hidden; height: fit-content;">
        <h4 style="font-size: 14px; margin-bottom: 15px; color: #e2e8f0; text-transform: uppercase; letter-spacing: 0.5px;">Adoption & Engagement (Sigmoïde)</h4>
        <div style="height: 200px; position: relative; width: 100%;">
          <canvas id="chart-sigmoid-progression"></canvas>
        </div>
      </div>
      <div class="adm-analytics-card" style="padding: 20px; overflow: hidden; height: fit-content;">
        <h4 style="font-size: 14px; margin-bottom: 15px; color: #e2e8f0; text-transform: uppercase; letter-spacing: 0.5px;">Profil Comportemental (Radar)</h4>
        <div style="height: 200px; position: relative; width: 100%;">
          <canvas id="chart-radar-profils"></canvas>
        </div>
      </div>
      <div class="adm-analytics-card" style="padding: 20px; overflow: hidden; height: fit-content;">
        <h4 style="font-size: 14px; margin-bottom: 15px; color: #e2e8f0; text-transform: uppercase; letter-spacing: 0.5px;">Analyse de Rétention (Survival)</h4>
        <div style="height: 200px; position: relative; width: 100%;">
          <canvas id="chart-survival-churn"></canvas>
        </div>
      </div>
    </div>

    <div class="adm-analytics-card">
      <h4>Vue enrichie des participants</h4>
      <table class="adm-analytics-table">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Indicateurs (Engagement / Complétion / Churn)</th>
            <th>Risque & Statut</th>
            <th>Action recommandée</th>
          </tr>
        </thead>
        <tbody id="adm-stats-user-tbody">
          <tr><td colspan="4" class="adm-analytics-empty">Cliquez sur Analyser.</td></tr>
        </tbody>
      </table>
    </div>
  `;

  const calendar = document.getElementById('adm-challenge-calendar-section');
  if (calendar && calendar.parentNode === challenges) {
    calendar.insertAdjacentElement('afterend', section);
  } else {
    challenges.insertBefore(section, challenges.firstChild);
  }
}

function toggleChallengeCalendar() {
  const s = document.getElementById('adm-challenge-calendar-section');
  if (!s) return;
  const willOpen = s.style.display === 'none';
  s.style.display = willOpen ? 'block' : 'none';
  if (willOpen) renderChallengeCalendar();
}

function toggleStatsUserPredictions() {
  const s = document.getElementById('adm-stats-user-section');
  if (!s) return;
  const willOpen = s.style.display === 'none';
  s.style.display = willOpen ? 'block' : 'none';
  if (willOpen) {
    injectStatsUserSectionIfMissing(); // S'assure que la section est bien injectée
    loadStatsUserPredictions();
  }
}

function scoreTone(score, inverse = false) {
  const n = parseFloat(score) || 0;
  const good = inverse ? n <= 35 : n >= 70;
  const warn = inverse ? n <= 65 : n >= 45;
  return good ? 'good' : warn ? 'warn' : 'bad';
}

function loadStatsUserPredictions() {
  const tbody = document.getElementById('adm-stats-user-tbody');
  const kpis = document.getElementById('adm-stats-user-kpis');
  const email = (document.getElementById('adm-stats-user-email')?.value || '').trim();
  if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="adm-analytics-empty">Analyse statsUser en cours...</td></tr>';

  const url = 'api/stats-user-predictions.php?limit=20&t=' + Date.now()
    + (email ? '&email=' + encodeURIComponent(email) : '');
  fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
  })
    .then(r => r.json())
    .then(data => {
      if (!data?.ok) throw new Error(data?.error || 'statsUser indisponible');
      renderStatsUserPredictions(data);
    })
    .catch(err => {
      if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="adm-analytics-empty">${escapeHtml(err.message || 'Erreur statsUser')}</td></tr>`;
      if (kpis) kpis.innerHTML = `
        <div><b>—</b><small>utilisateurs</small></div>
        <div><b>—</b><small>engagement prévu</small></div>
        <div><b>—</b><small>risque moyen</small></div>
      `;
    });
}

function renderStatsUserPredictions(data) {
  const tbody = document.getElementById('adm-stats-user-tbody');
  const kpis = document.getElementById('adm-stats-user-kpis');
  const g = data.global || {};

  // 1. KPIs
  if (kpis) {
    kpis.innerHTML = `
      <div><b>${parseInt(g.users_count || 0, 10)}</b><small>utilisateurs</small></div>
      <div><b>${parseFloat(g.avg_engagement_forecast || 0)}%</b><small>engagement prévu</small></div>
      <div><b>${parseFloat(g.avg_churn_risk || 0)}%</b><small>risque moyen</small></div>
    `;
  }

  const users = Array.isArray(data.statsUser) ? data.statsUser : [];
  if (!tbody) return;
  if (users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="adm-analytics-empty">Aucun historique utilisateur disponible.</td></tr>';
    return;
  }

  // 2. Enriched Table
  tbody.innerHTML = users.map(u => {
    const h = u.historical || {};
    const p = u.prediction || {};
    const riskLabel = (p.risk_label || 'faible').toLowerCase();
    const riskColor = riskLabel === 'critique' ? '#ef4444' : riskLabel === 'surveiller' ? '#f59e0b' : '#22c55e';
    
    return `
      <tr class="adm-stats-user-row">
        <td>
          <div class="adm-analytics-user" style="display:flex; align-items:center; gap:12px;">
            <div class="adm-avatar" style="background:${getAvatarColor(u.nom)}; width:36px; height:36px; font-size:12px;">${u.initials || 'U'}</div>
            <div style="min-width:0;">
              <b style="display:block; font-size:14px; color:#e2e8f0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(u.nom || 'Utilisateur')}</b>
              <small style="color:#94a3b8; font-size:11px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(u.email || '')}</small>
            </div>
          </div>
        </td>
        <td style="min-width:250px;">
          <div style="display:flex; flex-direction:column; gap:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <small style="width:70px; color:#94a3b8; font-size:10px;">Engagement</small>
              <div style="flex:1; height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                <div style="width:${p.engagement_forecast || 0}%; height:100%; background:#3b82f6; box-shadow:0 0 8px rgba(59,130,246,0.4);"></div>
              </div>
              <small style="width:30px; font-size:10px; color:#3b82f6; font-weight:700; text-align:right;">${p.engagement_forecast || 0}%</small>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <small style="width:70px; color:#94a3b8; font-size:10px;">Complétion</small>
              <div style="flex:1; height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                <div style="width:${p.completion_probability || 0}%; height:100%; background:#8b5cf6; box-shadow:0 0 8px rgba(139,92,246,0.4);"></div>
              </div>
              <small style="width:30px; font-size:10px; color:#8b5cf6; font-weight:700; text-align:right;">${p.completion_probability || 0}%</small>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
              <small style="width:70px; color:#94a3b8; font-size:10px;">Churn</small>
              <div style="flex:1; height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                <div style="width:${p.churn_risk || 0}%; height:100%; background:${riskColor}; box-shadow:0 0 8px ${riskColor}44;"></div>
              </div>
              <small style="width:30px; font-size:10px; color:${riskColor}; font-weight:700; text-align:right;">${p.churn_risk || 0}%</small>
            </div>
          </div>
        </td>
        <td>
          <div style="display:flex; flex-direction:column; gap:6px;">
            <span style="background:${riskColor}22; color:${riskColor}; padding:2px 8px; border-radius:12px; font-size:10px; font-weight:700; width:fit-content; text-transform:uppercase; border:1px solid ${riskColor}33;">
              ${riskLabel}
            </span>
            <small style="color:#94a3b8; font-size:10px; display:flex; align-items:center; gap:4px;">
              <span style="width:6px; height:6px; border-radius:50%; background:${h.days_inactive > 10 ? '#ef4444' : '#22c55e'}"></span>
              J+${h.days_inactive || 0} inactif
            </small>
          </div>
        </td>
        <td>
          <div style="font-size:12px; color:#e2e8f0; margin-bottom:4px;">${escapeHtml(p.recommended_action || '-')}</div>
          <div style="display:flex; align-items:center; gap:8px; opacity:0.6;">
             <span style="font-size:14px;">📅</span>
             <small style="color:#94a3b8; font-size:10px;">Prévu: ${p.predicted_challenges_next_30d || 0} défis / 30j</small>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  // 3. Charts
  renderSigmoidChart(data);
  renderRadarChart(users.slice(0, 3)); 
  renderSurvivalChart(users);
}

function renderSigmoidChart(data) {
  const ctx = document.getElementById('chart-sigmoid-progression');
  if (!ctx) return;
  const existing = Chart.getChart(ctx);
  if (existing) existing.destroy();

  const days = Array.from({length: 31}, (_, i) => i);
  const sigmoid = (x, k, x0) => 100 / (1 + Math.exp(-k * (x - x0)));

  const g = data.global || {};
  const datasets = [
    {
      label: 'Engagement',
      data: days.map(d => sigmoid(d, 0.35, 12) * (g.avg_engagement_forecast / 100 || 0.8)),
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59,130,246,0.15)',
      fill: true,
      tension: 0.4,
      pointRadius: 0,
      borderWidth: 3
    },
    {
      label: 'Complétion',
      data: days.map(d => sigmoid(d, 0.25, 18) * (g.avg_completion_probability / 100 || 0.6)),
      borderColor: '#8b5cf6',
      borderDash: [5, 5],
      tension: 0.4,
      pointRadius: 0,
      borderWidth: 2
    }
  ];

  new Chart(ctx, {
    type: 'line',
    data: { labels: days.map(d => 'J' + d), datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y: { 
          min: 0, max: 100, 
          ticks: { color: '#e2e8f0', font: { size: 10, weight: 'bold' }, callback: v => v + '%' }, 
          grid: { color: 'rgba(255,255,255,0.1)', borderDash: [2, 2] } 
        },
        x: { 
          ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 7 }, 
          grid: { display: false } 
        }
      },
      plugins: { 
        legend: { position: 'top', labels: { color: '#e2e8f0', usePointStyle: true, boxWidth: 6, font: { size: 11, weight: 'bold' } } },
        tooltip: {
          backgroundColor: '#1e293b',
          titleColor: '#f8fafc',
          bodyColor: '#cbd5e1',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 10,
          displayColors: true,
          callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%` }
        }
      }
    }
  });
}

function renderRadarChart(users) {
  const ctx = document.getElementById('chart-radar-profils');
  if (!ctx || users.length === 0) return;
  const existing = Chart.getChart(ctx);
  if (existing) existing.destroy();

  const labels = ['Engagement', 'Complétion', 'Récence', 'Qualité', 'Stabilité', 'Fréquence'];
  const colors = ['#3b82f6', '#ef4444', '#22c55e'];

  const datasets = users.map((u, i) => {
    const p = u.prediction || {};
    const h = u.historical || {};
    return {
      label: u.nom?.split(' ')[0] || 'U' + (i+1),
      data: [
        p.engagement_forecast || 0,
        p.completion_probability || 0,
        Math.max(0, 100 - (h.days_inactive * 5)),
        h.quality_signal || 0,
        Math.max(0, 100 - (p.churn_risk || 0)),
        Math.min(100, (h.participations_count || 0) * 15)
      ],
      borderColor: colors[i % colors.length],
      backgroundColor: colors[i % colors.length] + '22',
      pointBackgroundColor: colors[i % colors.length],
      pointBorderColor: '#fff',
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: colors[i % colors.length],
      borderWidth: 2.5,
      pointRadius: 3,
      pointHoverRadius: 5
    };
  });

  new Chart(ctx, {
    type: 'radar',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          angleLines: { color: 'rgba(255,255,255,0.15)' },
          grid: { color: 'rgba(255,255,255,0.15)' },
          pointLabels: { color: '#e2e8f0', font: { size: 11, weight: 'bold' } },
          ticks: { display: false },
          min: 0, max: 100
        }
      },
      plugins: { 
        legend: { position: 'bottom', labels: { color: '#94a3b8', usePointStyle: true, boxWidth: 6, font: { size: 10 } } },
        tooltip: {
          backgroundColor: '#1e293b',
          padding: 10,
          callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw}%` }
        }
      }
    }
  });
}

function renderSurvivalChart(users) {
  const ctx = document.getElementById('chart-survival-churn');
  if (!ctx) return;
  const existing = Chart.getChart(ctx);
  if (existing) existing.destroy();

  const days = [0, 3, 7, 10, 14, 21, 30];
  const total = users.length || 1;
  const survivalData = days.map(d => {
    const activeAtD = users.filter(u => (u.historical?.days_inactive || 0) <= d).length;
    return (activeAtD / total) * 100;
  });

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: days.map(d => 'J' + d),
      datasets: [
        {
          label: 'Actifs (%)',
          data: survivalData,
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34,197,94,0.2)',
          fill: true,
          tension: 0.3,
          pointRadius: 3,
          pointBackgroundColor: '#22c55e',
          borderWidth: 3
        },
        {
          label: 'Alerte Churn',
          data: days.map(() => 30),
          borderColor: '#ef4444',
          borderDash: [5, 5],
          pointRadius: 0,
          fill: false,
          borderWidth: 1.5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        y: { 
          min: 0, max: 100, 
          ticks: { color: '#e2e8f0', font: { size: 10, weight: 'bold' }, callback: v => v + '%' }, 
          grid: { color: 'rgba(255,255,255,0.1)', borderDash: [2, 2] } 
        },
        x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { display: false } }
      },
      plugins: { 
        legend: { position: 'top', labels: { color: '#e2e8f0', usePointStyle: true, boxWidth: 6, font: { size: 11, weight: 'bold' } } },
        tooltip: {
          backgroundColor: '#1e293b',
          padding: 10,
          callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%` }
        }
      }
    }
  });
}

function dateKey(date) {
  if (!date) return '';
  const d = date instanceof Date ? date : new Date(date);
  if (Number.isNaN(d.getTime())) return '';
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function parseDateKey(key) {
  const parts = String(key || '').split('-').map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
  return new Date(parts[0], parts[1] - 1, parts[2]);
}

function challengesForDate(key) {
  return adminChallenges.filter(c => {
    const start = dateKey(c.date_debut);
    const end = dateKey(c.date_fin);
    return start && end && key >= start && key <= end;
  });
}

function challengeBoundaryLabels(c, key) {
  const labels = [];
  if (dateKey(c.date_debut) === key) labels.push('Début');
  if (dateKey(c.date_fin) === key) labels.push('Fin');
  if (labels.length === 0) labels.push('En cours');
  return labels;
}

function renderChallengeCalendar() {
  const grid = document.getElementById('adm-calendar-grid');
  const monthEl = document.getElementById('adm-calendar-month');
  if (!grid || !monthEl) return;

  const y = adminCalendarDate.getFullYear();
  const m = adminCalendarDate.getMonth();
  monthEl.textContent = adminCalendarDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });

  const first = new Date(y, m, 1);
  const firstWeekday = (first.getDay() + 6) % 7;
  const start = new Date(y, m, 1 - firstWeekday);
  const todayKey = dateKey(new Date());
  let html = '';

  for (let i = 0; i < 42; i++) {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const key = dateKey(d);
    const list = challengesForDate(key);
    const inMonth = d.getMonth() === m;
    const selected = adminCalendarSelected === key;
    const dots = list.slice(0, 3).map(c => `<span title="${escapeHtml(c.titre || 'Défi')}"></span>`).join('');
    html += `
      <button type="button"
        class="adm-calendar-day ${inMonth ? '' : 'is-out'} ${key === todayKey ? 'is-today' : ''} ${selected ? 'is-selected' : ''} ${list.length ? 'has-challenges' : ''}"
        onclick="selectChallengeCalendarDate('${key}')">
        <b>${d.getDate()}</b>
        ${list.length ? `<small>${list.length}</small><div class="adm-calendar-dots">${dots}</div>` : ''}
      </button>
    `;
  }

  grid.innerHTML = html;
}

function changeChallengeCalendarMonth(delta) {
  adminCalendarDate = new Date(adminCalendarDate.getFullYear(), adminCalendarDate.getMonth() + delta, 1);
  renderChallengeCalendar();
}

function selectChallengeCalendarDate(key) {
  adminCalendarSelected = key;
  renderChallengeCalendar();
  renderCalendarDateDetails(key);
}

function renderCalendarDateDetails(key) {
  const dateEl = document.getElementById('adm-calendar-selected-date');
  const listEl = document.getElementById('adm-calendar-challenges-list');
  const aiEl = document.getElementById('adm-calendar-ai-output');
  if (!dateEl || !listEl || !aiEl) return;

  const d = parseDateKey(key);
  dateEl.textContent = d
    ? d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    : 'Date invalide';

  const list = challengesForDate(key);
  if (list.length === 0) {
    listEl.innerHTML = '<div class="adm-analytics-empty">Aucun défi planifié ce jour.</div>';
    aiEl.textContent = 'Aucune analyse nécessaire pour cette date.';
    return;
  }

  listEl.innerHTML = list.map(c => {
    const labels = challengeBoundaryLabels(c, key);
    return `
      <button type="button" class="adm-calendar-challenge" onclick="loadCalendarChallengeAI(${parseInt(c.id, 10)}, '${key}')">
        <span>${escapeHtml(c.streak_icon || '🏆')}</span>
        <div>
          <strong>${escapeHtml(c.titre || 'Défi')}</strong>
          <small>${labels.join(' · ')} · ${escapeHtml(c.statut || '-')} · ${parseInt(c.participants_count || 0, 10)} participant(s)</small>
        </div>
      </button>
    `;
  }).join('');

  aiEl.innerHTML = '<div class="adm-analytics-empty">Analyse de la journée en cours...</div>';
  loadCalendarDayAI(list, key);
}

function loadCalendarDayAI(list, key) {
  const aiEl = document.getElementById('adm-calendar-ai-output');
  if (!aiEl) return;
  const calls = list.slice(0, 4).map(c =>
    fetch(`api/challenge-analytics-predictions.php?challenge_id=${parseInt(c.id, 10)}&limit=5&t=${Date.now()}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
    })
      .then(r => r.json())
      .catch(() => null)
  );

  Promise.all(calls).then(results => {
    const insights = results
      .filter(r => r?.ok && Array.isArray(r.challenges) && r.challenges[0])
      .map(r => r.challenges[0]);
    aiEl.innerHTML = renderCalendarAIInsight(insights, list, key);
  });
}

function loadCalendarChallengeAI(challengeId, key) {
  const aiEl = document.getElementById('adm-calendar-ai-output');
  if (!aiEl) return;
  aiEl.innerHTML = '<div class="adm-analytics-empty">Analyse IA du défi...</div>';
  fetch(`api/challenge-analytics-predictions.php?challenge_id=${parseInt(challengeId, 10)}&limit=5&t=${Date.now()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
  })
    .then(r => r.json())
    .then(data => {
      if (!data?.ok || !data.challenges?.[0]) throw new Error(data?.error || 'Analyse indisponible');
      aiEl.innerHTML = renderCalendarAIInsight([data.challenges[0]], challengesForDate(key), key);
    })
    .catch(err => {
      aiEl.innerHTML = `<div class="adm-analytics-empty">${escapeHtml(err.message || 'Analyse indisponible')}</div>`;
    });
}

function renderCalendarAIInsight(insights, rawList, key) {
  if (!insights.length) {
    return '<div class="adm-analytics-empty">Aucune donnée analytique exploitable pour ces défis.</div>';
  }
  const avgSuccess = Math.round(insights.reduce((s, c) => s + (parseFloat(c.prediction?.success_probability) || 0), 0) / insights.length);
  const avgRisk = Math.round(insights.reduce((s, c) => s + (parseFloat(c.prediction?.risk_score) || 0), 0) / insights.length);
  const risky = insights.filter(c => (parseFloat(c.prediction?.risk_score) || 0) >= 45);
  const starts = rawList.filter(c => dateKey(c.date_debut) === key).length;
  const ends = rawList.filter(c => dateKey(c.date_fin) === key).length;
  const advice = risky.length
    ? `Priorité: surveiller ${risky.length} défi(s) à risque et envoyer une relance ciblée.`
    : avgSuccess >= 70
      ? 'Journée favorable: les signaux de réussite sont solides.'
      : 'Journée à accompagner: publier une astuce courte dans le chat peut améliorer l’engagement.';

  return `
    <div class="adm-calendar-ai-kpis">
      <div><b>${insights.length}</b><small>analysé(s)</small></div>
      <div><b>${avgSuccess}%</b><small>réussite prévue</small></div>
      <div><b>${avgRisk}%</b><small>risque moyen</small></div>
    </div>
    <p><strong>Lecture IA:</strong> ${escapeHtml(advice)}</p>
    <p>${starts} début(s), ${ends} fin(s), ${rawList.length} défi(s) actifs sur cette date.</p>
    <ul>
      ${insights.slice(0, 4).map(c => `
        <li>
          <strong>${escapeHtml(c.titre || 'Défi')}</strong>:
          réussite ${parseFloat(c.prediction?.success_probability || 0)}%,
          risque ${parseFloat(c.prediction?.risk_score || 0)}%,
          ${parseInt(c.participants_count || 0, 10)} participant(s).
        </li>
      `).join('')}
    </ul>
  `;
}

function toggleStats() {
  const s = document.getElementById('gl-stats-section');
  if (!s) return;
  const willOpen = s.style.display === 'none';
  s.style.display = willOpen ? 'block' : 'none';
  if (willOpen) loadAdvancedAnalytics();
}

// ─── Modal notifications ──────────────────────────────────────
function injectNotifModal() {
  if (document.getElementById('gl-notif-modal')) return;
  const div = document.createElement('div');
  div.id = 'gl-notif-modal';
  div.style.cssText = `
    display:none; position:fixed; inset:0; z-index:99998;
    background:rgba(0,0,0,0.75); backdrop-filter:blur(8px);
    align-items:center; justify-content:center;
  `;
  div.innerHTML = `
    <div id="gl-notif-container" style="
      background:#111122; border:1px solid rgba(99,102,241,0.3); border-radius:24px;
      padding:32px; width:540px; max-width:95vw; position:relative;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    ">
      <button onclick="closeNotifModal()" style="
        position:absolute;top:20px;right:24px;background:rgba(255,255,255,0.05);border:none;
        color:#94a3b8;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s;
      " onmouseover="this.style.background='rgba(239,68,68,0.1)';this.style.color='#ef4444'" onmouseout="this.style.background='rgba(255,255,255,0.05)';this.style.color='#94a3b8'">✕</button>
      
      <div id="gl-notif-form-view">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
          <div style="background:rgba(99,102,241,0.1); padding:12px; border-radius:12px;">
            <span style="font-size:24px;">📧</span>
          </div>
          <div>
            <h3 style="color:#e2e8f0;margin:0;font-size:18px;">Notifier les participants</h3>
            <p style="color:#94a3b8;font-size:13px;margin:4px 0 0;">
              Défi : <strong id="gl-notif-titre" style="color:#818cf8;"></strong>
            </p>
          </div>
        </div>

        <div style="background:rgba(34,197,94,0.05); border:1px solid rgba(34,197,94,0.2); border-radius:12px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
          <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:8px; height:8px; border-radius:50%; background:#22c55e;"></span>
            <span style="color:#a8b8a0; font-size:13px;">Participants avec notifications activées</span>
          </div>
          <span id="gl-notif-count" style="color:#22c55e; font-size:13px; font-weight:700;">3 recevront cet email</span>
        </div>

        <div style="margin-bottom:20px;">
          <label style="display:block;color:#94a3b8;font-size:11px;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;">Templates Rapides</label>
          <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <button onclick="applyNotifTemplate('rappel')" class="adm-btn adm-btn--ghost adm-btn--sm" style="font-size:11px; border-radius:20px; padding:4px 12px;">⏰ Rappel fin de défi</button>
            <button onclick="applyNotifTemplate('bravo')" class="adm-btn adm-btn--ghost adm-btn--sm" style="font-size:11px; border-radius:20px; padding:4px 12px;">🏆 Bravo pour l'effort</button>
            <button onclick="applyNotifTemplate('boost')" class="adm-btn adm-btn--ghost adm-btn--sm" style="font-size:11px; border-radius:20px; padding:4px 12px;">⚡ Boost motivation</button>
            <button onclick="applyNotifTemplate('recap')" class="adm-btn adm-btn--ghost adm-btn--sm" style="font-size:11px; border-radius:20px; padding:4px 12px;">📋 Récap hebdo</button>
          </div>
        </div>

        <input type="hidden" id="gl-notif-id">
        
        <div style="margin-bottom:20px;">
          <label style="display:block;color:#94a3b8;font-size:12px;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Sujet de l'email *</label>
          <input id="gl-notif-sujet" type="text" placeholder="Ex: Rappel — votre défi se termine bientôt !" style="
            width:100%;box-sizing:border-box;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.1);
            border-radius:12px;color:#e2e8f0;padding:12px 16px;font-size:14px;transition:border-color 0.2s;
          " onfocus="this.style.borderColor='#6366f1'">
        </div>
        
        <div style="margin-bottom:24px;">
          <label style="display:block;color:#94a3b8;font-size:12px;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Message *</label>
          <textarea id="gl-notif-message" rows="5" placeholder="Votre message aux participants..." style="
            width:100%;box-sizing:border-box;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.1);
            border-radius:12px;color:#e2e8f0;padding:12px 16px;font-size:14px;resize:none;transition:border-color 0.2s;
          " onfocus="this.style.borderColor='#6366f1'"></textarea>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;border-top:1px solid rgba(255,255,255,0.05);padding-top:24px;">
          <button onclick="closeNotifModal()" class="adm-btn adm-btn--ghost" style="padding:12px 24px;border-radius:12px;">Annuler</button>
          <button id="gl-notif-send" onclick="sendNotification()" class="adm-btn adm-btn--primary" style="padding:12px 32px;border-radius:12px;font-weight:700;">✉️ Envoyer</button>
        </div>
      </div>

      <div id="gl-notif-success-view" style="display:none; text-align:center; padding:20px 0;">
        <div style="width:80px; height:80px; background:rgba(34,197,94,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
          <span style="font-size:40px; color:#22c55e;">✓</span>
        </div>
        <h3 style="color:#e2e8f0; font-size:22px; margin:0 0 8px;">Emails envoyés avec succès !</h3>
        <p style="color:#94a3b8; font-size:15px; margin:0 0 32px;">Votre message a bien été distribué aux participants.</p>
        
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; margin-bottom:32px;">
          <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:16px; border-radius:16px;">
            <div id="gl-notif-res-sent" style="font-size:24px; font-weight:800; color:#3b82f6;">0</div>
            <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">Envoyés</div>
          </div>
          <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:16px; border-radius:16px;">
            <div id="gl-notif-res-failed" style="font-size:24px; font-weight:800; color:#ef4444;">0</div>
            <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">Échoués</div>
          </div>
          <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:16px; border-radius:16px;">
            <div id="gl-notif-res-total" style="font-size:24px; font-weight:800; color:#8b5cf6;">0</div>
            <div style="font-size:11px; color:#94a3b8; text-transform:uppercase; margin-top:4px;">Total</div>
          </div>
        </div>

        <button onclick="resetNotifModal()" class="adm-btn adm-btn--ghost" style="padding:12px 32px; border-radius:12px; display:flex; align-items:center; gap:8px; margin:0 auto;">
          <span>←</span> Nouveau message
        </button>
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

function loadAdvancedAnalytics() {
  const kpis = document.getElementById('adm-prediction-kpis');
  if (kpis) kpis.innerHTML = '<div class="adm-analytics-empty">Analyse en cours...</div>';

  fetch('api/challenge-analytics-predictions.php?limit=5&t=' + Date.now(), {
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
  })
    .then(r => r.json())
    .then(data => {
      if (!data?.ok) throw new Error(data?.error || 'Analyse indisponible');
      renderAdvancedAnalytics(data);
    })
    .catch(err => {
      console.warn('Analytics prédictives non disponibles:', err);
      renderAnalyticsError(err.message || 'Analyse indisponible');
    });
}

function renderAnalyticsError(message) {
  ['adm-prediction-kpis', 'adm-risk-tbody', 'adm-forecast-rank-tbody', 'adm-recommendations-list'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    const colspan = id.endsWith('tbody') ? ' colspan="3"' : '';
    el.innerHTML = id.endsWith('tbody')
      ? `<tr><td${colspan} class="adm-analytics-empty">${escapeHtml(message)}</td></tr>`
      : `<div class="adm-analytics-empty">${escapeHtml(message)}</div>`;
  });
}

function renderAdvancedAnalytics(data) {
  renderAnalyticsKpis(data.global || {});
  renderRiskChallenges(data.predictions?.at_risk_challenges || []);
  renderForecastRanking(data.predictions?.forecast_ranking || []);
  renderRecommendations(data.recommendations || []);
}

function renderAnalyticsKpis(global) {
  const wrap = document.getElementById('adm-prediction-kpis');
  if (!wrap) return;
  const items = [
    ['Réussite globale', `${global.global_success_rate ?? 0}%`, 'Historique'],
    ['Probabilité moyenne', `${global.avg_success_probability ?? 0}%`, 'Prédiction'],
    ['Engagement futur', `${global.avg_future_engagement ?? 0}%`, 'Prévision'],
    ['Participants uniques', global.unique_participants ?? 0, 'Comportement'],
  ];
  wrap.innerHTML = items.map(([label, value, hint]) => `
    <div class="adm-prediction-kpi">
      <b>${escapeHtml(value)}</b>
      <span>${escapeHtml(label)}</span>
      <small>${escapeHtml(hint)}</small>
    </div>
  `).join('');
}

function renderRiskChallenges(list) {
  const tbody = document.getElementById('adm-risk-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="3" class="adm-analytics-empty">Aucun défi à risque</td></tr>';
    return;
  }
  tbody.innerHTML = list.slice(0, 5).map(c => {
    const p = c.prediction || {};
    return `
      <tr>
        <td>${escapeHtml(c.streak_icon || '')} ${escapeHtml(c.titre || '')}</td>
        <td><span class="adm-score-badge adm-score-badge--${getScoreTone(p.success_probability)}">${p.success_probability ?? 0}%</span></td>
        <td><span class="adm-score-badge adm-score-badge--${getRiskTone(p.risk_score)}">${p.risk_score ?? 0}%</span></td>
      </tr>
    `;
  }).join('');
}

function renderForecastRanking(list) {
  const tbody = document.getElementById('adm-forecast-rank-tbody');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="3" class="adm-analytics-empty">Aucun participant</td></tr>';
    return;
  }
  tbody.innerHTML = list.slice(0, 5).map(p => `
    <tr>
      <td>#${parseInt(p.predicted_rank || 0, 10) || '-'}</td>
      <td>
        <div class="adm-analytics-user">
          <span>${escapeHtml(p.initials || getInitials(p.nom || 'P'))}</span>
          <div>
            <b>${escapeHtml(p.nom || 'Participant')}</b>
            <small>${escapeHtml(p.email || '')}</small>
          </div>
        </div>
      </td>
      <td><span class="adm-score-badge adm-score-badge--${getScoreTone(p.performance_score)}">${p.performance_score ?? 0}</span></td>
    </tr>
  `).join('');
}

function renderRecommendations(list) {
  const wrap = document.getElementById('adm-recommendations-list');
  if (!wrap) return;
  if (!list.length) {
    wrap.innerHTML = '<div class="adm-analytics-empty">Aucune recommandation critique</div>';
    return;
  }
  wrap.innerHTML = list.slice(0, 5).map(item => `
    <div class="adm-recommendation-item adm-recommendation-item--${escapeHtml(item.priority || 'moyenne')}">
      <div>
        <b>${escapeHtml(item.title || 'Recommandation')}</b>
        <p>${escapeHtml(item.reason || '')}</p>
        <small>${escapeHtml(item.action || '')}</small>
      </div>
      <span>${escapeHtml(item.priority || 'info')}</span>
    </div>
  `).join('');
}

function getScoreTone(score) {
  const n = parseFloat(score) || 0;
  if (n >= 70) return 'good';
  if (n >= 40) return 'warn';
  return 'bad';
}

function getRiskTone(score) {
  const n = parseFloat(score) || 0;
  if (n >= 70) return 'bad';
  if (n >= 40) return 'warn';
  return 'good';
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
  tbody.innerHTML = list.map((c, i) => {
    const rate = Math.round(c.completion_rate || 0);
    return `
      <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
        <td style="padding:10px 12px;color:#f59e0b;font-weight:700;">${['🥇','🥈','🥉'][i]}</td>
        <td style="padding:10px 12px;color:#e2e8f0;">
          ${escapeHtml(c.streak_icon||'')} ${escapeHtml(c.titre||'')}
          <div style="margin-top:6px;width:100px;">
            <div style="display:flex;justify-content:space-between;font-size:10px;color:#94a3b8;margin-bottom:3px;">
              <span>Complétion</span>
              <span>${rate}%</span>
            </div>
            <div style="height:4px;background:rgba(255,255,255,0.1);border-radius:2px;overflow:hidden;">
              <div style="width:${rate}%;height:100%;background:linear-gradient(90deg, #6366f1, #22c55e);border-radius:2px;"></div>
            </div>
          </div>
        </td>
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
    `;
  }).join('');
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

function resetNotifModal() {
  const formView = document.getElementById('gl-notif-form-view');
  const successView = document.getElementById('gl-notif-success-view');
  if (formView) formView.style.display = 'block';
  if (successView) successView.style.display = 'none';
  const sujet = document.getElementById('gl-notif-sujet');
  const message = document.getElementById('gl-notif-message');
  if (sujet) sujet.value = '';
  if (message) message.value = '';
}

function applyNotifTemplate(type) {
  const sujet = document.getElementById('gl-notif-sujet');
  const message = document.getElementById('gl-notif-message');
  const titre = document.getElementById('gl-notif-titre').textContent;

  const templates = {
    rappel: {
      sujet: `⏰ Rappel — Le défi "${titre}" se termine bientôt !`,
      message: `Bonjour à tous !\n\nPlus que quelques jours pour atteindre vos objectifs dans le défi "${titre}". Ne lâchez rien, la victoire est proche !\n\nL'équipe GaiaLumen.`
    },
    bravo: {
      sujet: `🏆 Félicitations pour vos progrès sur "${titre}" !`,
      message: `Bonjour les challengers !\n\nNous avons remarqué une superbe progression collective sur le défi "${titre}". Bravo pour votre engagement et votre persévérance !\n\nContinuez comme ça !`
    },
    boost: {
      sujet: `⚡ Un petit boost pour le défi "${titre}" ?`,
      message: `Bonjour !\n\nC'est le moment de passer à la vitesse supérieure pour le défi "${titre}". Une petite action aujourd'hui peut faire une grande différence demain.\n\nOn compte sur vous !`
    },
    recap: {
      sujet: `📋 Récapitulatif hebdomadaire — Défi "${titre}"`,
      message: `Bonjour à tous !\n\nVoici le point sur le défi "${titre}" cette semaine. La progression moyenne avance bien et l'engagement reste fort.\n\nVérifiez vos objectifs et complétez vos actions du jour !`
    }
  };

  const t = templates[type];
  if (t && sujet && message) {
    sujet.value = t.sujet;
    message.value = t.message;
  }
}

function openNotifModal(id, titre) {
  const modal = document.getElementById('gl-notif-modal');
  if (!modal) return;
  resetNotifModal();
  document.getElementById('gl-notif-id').value    = id;
  document.getElementById('gl-notif-titre').textContent = titre;
  
  // Chercher le défi pour le nombre de participants
  const c = adminChallenges.find(x => String(x.id) === String(id));
  const count = c ? (parseInt(c.participants_count) || 0) : 0;
  const countEl = document.getElementById('gl-notif-count');
  if (countEl) countEl.textContent = `${count} recevront cet email`;

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
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '⌛ Envoi...';

  fetch('api/challenge-notifier.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id_challenge: parseInt(id), sujet, message })
  })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = originalHTML;
      
      if (data.success) {
        // Passer à la vue succès
        const formView = document.getElementById('gl-notif-form-view');
        const successView = document.getElementById('gl-notif-success-view');
        if (formView) formView.style.display = 'none';
        if (successView) successView.style.display = 'block';
        
        const resSent = document.getElementById('gl-notif-res-sent');
        const resFailed = document.getElementById('gl-notif-res-failed');
        const resTotal = document.getElementById('gl-notif-res-total');
        
        if (resSent) resSent.textContent = data.sent || 0;
        if (resFailed) resFailed.textContent = data.failed || 0;
        if (resTotal) resTotal.textContent = data.total || 0;
        
        showToast('Notifications envoyées', `${data.sent} email(s) envoyé(s).`, 'success');
      } else {
        showToast('Erreur', data.error || 'Erreur lors de l\'envoi', 'error');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = originalHTML;
      showToast('Erreur serveur', err.message || 'Connexion impossible.', 'error');
    });
}

// ═══════════════════════════════════════════════════════════
// TOAST SYSTEM
// ═══════════════════════════════════════════════════════════

/**
 * Appelable partout dans le code.
 * showToast(title, message, type, duration)
 * showToast(title, message, 'success'|'error'|'warning'|'info', 4000)
 */
function showToast(title, message = '', type = 'success', duration = 4000) {
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
  const toastId = 'toast-' + Date.now();
  toast.id = toastId;
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
      ${message ? `<div style="color:#94a3b8;font-size:12px;line-height:1.4;">${message}</div>` : ''}
    </div>
    <button onclick="this.closest('[style]').remove()" style="
      background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;
      padding:0;margin-top:1px;flex-shrink:0;
    ">✕</button>
    <!-- Progress bar -->
    <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,0.05);">
      <div id="tpb-${toastId}" style="height:100%;background:${c.color};width:100%;
           transition:width ${duration}ms linear;border-radius:2px;"></div>
    </div>
  `;

  container.appendChild(toast);

  // Animer la barre de progression
  requestAnimationFrame(() => {
    const bar = document.getElementById(`tpb-${toastId}`);
    if (bar) bar.style.width = '0%';
  });

  // Auto-dismiss après la durée spécifiée
  setTimeout(() => {
    if (document.getElementById(toastId)) {
      toast.style.animation = 'glToastOut .3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }
  }, duration);
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
    renderChallengesPagination(0);
    return;
  }

  const totalPages = Math.max(1, Math.ceil(filtered.length / CHALLENGES_PER_PAGE));
  if (challengesPage > totalPages) challengesPage = totalPages;
  if (challengesPage < 1) challengesPage = 1;

  const startIndex = (challengesPage - 1) * CHALLENGES_PER_PAGE;
  const pageItems = filtered.slice(startIndex, startIndex + CHALLENGES_PER_PAGE);

  tbody.innerHTML = pageItems.map(c => {
    const participantsCount = parseInt(c.participants_count || 0);
    const target            = parseInt(c.valeur_cible       || 0);
    const pct = target > 0 ? Math.min(100, Math.round((participantsCount / target) * 100)) : 0;
    const nbVues  = parseInt(c.nb_vues  || 0);
    const nbLikes = parseInt(c.nb_likes || 0);
    const isPaid = parseInt(c.est_payant || 0, 10) === 1 && (parseFloat(c.prix || 0) || 0) > 0;
    const priceLabel = isPaid ? `${(parseFloat(c.prix || 0) || 0).toFixed(2).replace('.', ',')} DT` : 'Gratuit';

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

        <!-- Prix -->
        <td>
          <span class="type-badge" style="background:${isPaid ? 'rgba(245,158,11,.18)' : 'rgba(34,197,94,.14)'};color:${isPaid ? '#f59e0b' : '#22c55e'};">
            ${isPaid ? '💳 ' : '🎁 '}${priceLabel}
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
            <button class="btn-icon" onclick="showChallengeAISummary(${c.id})"     title="Résumé IA">✨</button>
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
  renderChallengesPagination(filtered.length);
}

function renderChallengesPagination(total) {
  const wrap = document.getElementById('challenges-pagination');
  const info = document.getElementById('challenges-pagination-info');
  const controls = document.getElementById('challenges-pagination-controls');
  if (!wrap || !info || !controls) return;

  if (total <= CHALLENGES_PER_PAGE) {
    wrap.style.display = total > 0 ? 'flex' : 'none';
    info.textContent = total > 0 ? `Affichage de 1 à ${total} sur ${total} défi${total > 1 ? 's' : ''}` : '';
    controls.innerHTML = '';
    return;
  }

  const totalPages = Math.ceil(total / CHALLENGES_PER_PAGE);
  const start = (challengesPage - 1) * CHALLENGES_PER_PAGE + 1;
  const end = Math.min(challengesPage * CHALLENGES_PER_PAGE, total);
  wrap.style.display = 'flex';
  info.textContent = `Affichage de ${start} à ${end} sur ${total} défis`;

  const pages = [];
  const first = Math.max(1, challengesPage - 2);
  const last = Math.min(totalPages, challengesPage + 2);
  for (let page = first; page <= last; page++) pages.push(page);

  controls.innerHTML = `
    <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengesPage(${challengesPage - 1})" ${challengesPage <= 1 ? 'disabled' : ''}>← Précédent</button>
    ${first > 1 ? `<button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengesPage(1)">1</button>${first > 2 ? '<span class="pagination-ellipsis">...</span>' : ''}` : ''}
    ${pages.map(page => `
      <button class="adm-btn adm-btn--ghost adm-btn--sm ${page === challengesPage ? 'active' : ''}" onclick="changeChallengesPage(${page})">${page}</button>
    `).join('')}
    ${last < totalPages ? `${last < totalPages - 1 ? '<span class="pagination-ellipsis">...</span>' : ''}<button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengesPage(${totalPages})">${totalPages}</button>` : ''}
    <button class="adm-btn adm-btn--ghost adm-btn--sm" onclick="changeChallengesPage(${challengesPage + 1})" ${challengesPage >= totalPages ? 'disabled' : ''}>Suivant →</button>
  `;
}

function changeChallengesPage(page) {
  challengesPage = page;
  renderChallengesTable();
  document.querySelector('.table-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
      renderChallengeCalendar();
      if (adminCalendarSelected) renderCalendarDateDetails(adminCalendarSelected);
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
  challengesPage = 1;
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
      case 'challenge-prix':
        const paid = document.getElementById('challenge-est-payant')?.checked;
        const price = parseFloat(value || '0');
        if (paid && (isNaN(price) || price <= 0)) { isValid = false; errorMsg = 'Prix supérieur à 0 requis'; } break;
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
  toggleChallengePrice();
  updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);
  clearErrors();
  updatePreview();
}

function toggleChallengePrice() {
  const paid = document.getElementById('challenge-est-payant')?.checked;
  const priceInput = document.getElementById('challenge-prix');
  if (!priceInput) return;
  priceInput.required = !!paid;
  priceInput.closest('.form-group').style.opacity = paid ? '1' : '.55';
  if (!paid) priceInput.value = '0';
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
  const paid     = document.getElementById('challenge-est-payant')?.checked;
  const price    = parseFloat(document.getElementById('challenge-prix')?.value || '0') || 0;

  const safe = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
  safe('preview-title',      titre);
  safe('preview-desc',       desc);
  safe('preview-type-label', (type === 'collectif' ? '👥 ' : '👤 ') + type.charAt(0).toUpperCase() + type.slice(1));
  safe('preview-category',   category.replace(/[^\w\s]/gi, '').trim());
  safe('preview-target',     target + '%');
  safe('preview-price',      paid && price > 0 ? price.toFixed(2).replace('.', ',') + ' DT' : 'Gratuit');
  safe('preview-icon',       icon);
  const el = document.getElementById('preview-date');
  if (el) el.innerText = dateFin !== '-' ? new Date(dateFin).toLocaleDateString('fr-FR', { day:'numeric', month:'short' }) : '-';

  const img = document.getElementById('preview-img-container');
  if (img) {
    const imageEl = document.getElementById('preview-generated-img');
    const previewIcon = document.getElementById('preview-icon');
    if (image && isValidUrl(image)) {
      const token = ++previewImageLoadToken;
      img.classList.remove('has-image');
      img.style.backgroundImage = 'none';
      if (previewIcon) previewIcon.style.opacity = '1';
      if (imageEl) {
        imageEl.onload = () => {
          if (token !== previewImageLoadToken) return;
          img.classList.add('has-image');
          if (previewIcon) previewIcon.style.opacity = '0';
        };
        imageEl.onerror = () => {
          if (token !== previewImageLoadToken) return;
          img.classList.remove('has-image');
          imageEl.removeAttribute('src');
          if (previewIcon) previewIcon.style.opacity = '1';
        };
        imageEl.src = image;
      } else {
        img.style.backgroundImage = `url('${image}')`;
        img.style.backgroundSize = 'cover';
        img.style.backgroundPosition = 'center';
        if (previewIcon) previewIcon.style.opacity = '0.3';
      }
    } else {
      previewImageLoadToken++;
      img.classList.remove('has-image');
      img.style.backgroundImage = 'none';
      if (imageEl) imageEl.removeAttribute('src');
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
  const paidInput = document.getElementById('challenge-est-payant');
  const priceInput = document.getElementById('challenge-prix');
  if (paidInput) paidInput.checked = parseInt(c.est_payant || 0, 10) === 1;
  if (priceInput) priceInput.value = parseFloat(c.prix || 0) || 0;
  toggleChallengePrice();
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

function searchChallengesAdmin()  { challengesPage = 1; renderChallengesTable(); }
function filterChallengesAdmin()  { challengesPage = 1; renderChallengesTable(); }
function refreshTableAdmin()      { loadAdminChallenges(); }

function updateDashboardStats() {
  // Fonction désactivée - Stats supprimées
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
  if (tbody)   tbody.innerHTML = `<tr><td colspan="8" class="adm-table-loading">⏳ Chargement…</td></tr>`;

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
      if (tbody)   tbody.innerHTML = `<tr><td colspan="8" style="color:#ef4444;text-align:center;padding:30px;">⚠️ ${escapeHtml(err.message)}</td></tr>`;
    })
    .finally(() => panel?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function renderPaymentBadge(p) {
  const status = (p.paiement_statut || '').toString();
  if (status === 'paye') {
    const method = (p.paiement_methode || 'payé').toString().replace('_', ' ');
    const amount = p.paiement_montant !== null && p.paiement_montant !== undefined
      ? `${(parseFloat(p.paiement_montant) || 0).toFixed(2).replace('.', ',')} DT`
      : '';
    return `
      <div>
        <span class="adm-engage-badge adm-engage-badge--on">💳 Payé</span>
        <div style="font-size:10px;color:#94a3b8;margin-top:4px;white-space:nowrap;">
          ${escapeHtml(method)}${amount ? ' · ' + amount : ''}
        </div>
      </div>`;
  }

  // Si le défi est payant mais pas de paiement trouvé
  if (p.challenge_est_payant == 1) {
    const price = p.challenge_prix ? `${parseFloat(p.challenge_prix).toFixed(2)} DT` : '';
    return `
      <div>
        <span class="adm-engage-badge" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2);">⏳ Attente</span>
        ${price ? `<div style="font-size:10px;color:#ef4444;margin-top:4px;">${price}</div>` : ''}
      </div>`;
  }

  return `<span class="adm-engage-badge adm-engage-badge--off">Gratuit / N.A.</span>`;
}

function renderSelectedChallengeParticipants() {
  const tbody = document.getElementById('challenge-participants-tbody');
  if (!tbody) return;
  if (!selectedChallengeParticipants.length) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:12px;">👤</div><div>Aucun participant</div>
    </td></tr>`;
    return;
  }
  tbody.innerHTML = selectedChallengeParticipants.map(p => {
    if (String(p.id) === String(editingParticipantId)) {
      return renderParticipantEditRow(p);
    }
    const nom = String(p.nom || '');
    const prog = clampInt(p.objectif, 0, 100);
    const target = clampInt(p.challenge_target, 0, 100);
    const engBadge = p.engagement == 1
      ? `<span class="adm-engage-badge adm-engage-badge--on">🔥 Engagé</span>`
      : `<span class="adm-engage-badge adm-engage-badge--off">😴 Inactif</span>`;
    const payBadge = renderPaymentBadge(p);

    // Calcul du score de risque ML (Phase 2)
    const progressGap = target - prog;
    const isAtRisk = progressGap > 20 || p.engagement == 0;
    const riskBadge = isAtRisk 
      ? `<span class="ml-risk-indicator" 
               onclick="showRiskExplanation(${p.id}, event)"
               title="Cliquez pour voir l'analyse détaillée" 
               style="display:inline-block; white-space:nowrap; background:#ff4d4d; color:white; padding:1px 6px; border-radius:6px; font-size:9px; font-weight:bold; margin-left:6px; border:1px solid #ffffff44; vertical-align:middle; cursor:pointer; transition:transform 0.2s;"
               onmouseover="this.style.transform='scale(1.1)'"
               onmouseout="this.style.transform='scale(1)'">⚠️ RISQUE</span>`
      : ``;

    return `
      <tr class="participant-row-admin">
        <td>
          <div class="adm-participant-cell" style="display:flex; align-items:center; gap:10px;">
            <div class="adm-avatar" style="background:${getAvatarColor(nom)}; flex-shrink:0;">${getInitials(nom)}</div>
            <div class="adm-participant-info" style="min-width:0;">
              <div style="display:flex; align-items:center; gap:4px;">
                <span class="adm-participant-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(nom)}</span>
                ${riskBadge}
              </div>
              <span class="adm-participant-email" style="display:block; font-size:11px; opacity:0.7;">${escapeHtml(p.email||'')}</span>
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
        <td>${payBadge}</td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon" onclick="openProgressCoach(${p.id})" title="Coach IA">🧠</button>
            <button class="btn-icon" onclick="editParticipant(${p.id})" title="Modifier">✏️</button>
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
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">
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
    if (String(p.id) === String(editingParticipantId)) {
      return renderParticipantEditRow(p);
    }
    const nom  = String(p.nom  || '');
    const prog = clampInt(p.objectif, 0, 100);
    const target = clampInt(p.challenge_target, 0, 100);
    const engBadge = p.engagement == 1
      ? `<span class="adm-engage-badge adm-engage-badge--on">🔥 Engagé</span>`
      : `<span class="adm-engage-badge adm-engage-badge--off">😴 Inactif</span>`;
    const payBadge = renderPaymentBadge(p);

    // Calcul du score de risque ML (Phase 2)
    const progressGap = target - prog;
    const isAtRisk = progressGap > 20 || p.engagement == 0;
    const riskBadge = isAtRisk 
      ? `<span class="ml-risk-indicator" 
               onclick="showRiskExplanation(${p.id}, event)"
               title="Cliquez pour voir l'analyse détaillée" 
               style="display:inline-block; white-space:nowrap; background:#ff4d4d; color:white; padding:1px 6px; border-radius:6px; font-size:9px; font-weight:bold; margin-left:6px; border:1px solid #ffffff44; vertical-align:middle; cursor:pointer; transition:transform 0.2s;"
               onmouseover="this.style.transform='scale(1.1)'"
               onmouseout="this.style.transform='scale(1)'">⚠️ RISQUE</span>`
      : ``;

    return `
      <tr class="participant-row-admin">
        <td>
          <div class="adm-participant-cell" style="display:flex; align-items:center; gap:10px;">
            <div class="adm-avatar" style="background:${getAvatarColor(nom)}; flex-shrink:0;">${getInitials(nom)}</div>
            <div class="adm-participant-info" style="min-width:0;">
              <div style="display:flex; align-items:center; gap:4px;">
                <span class="adm-participant-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(nom)}</span>
                ${riskBadge}
              </div>
              <span class="adm-participant-email" style="display:block; font-size:11px; opacity:0.7;">${escapeHtml(p.email||'')}</span>
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
        <td>${payBadge}</td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon" onclick="openProgressCoach(${p.id})" title="Coach IA">🧠</button>
            <button class="btn-icon" onclick="editParticipant(${p.id})" title="Modifier">✏️</button>
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

  const btns = document.querySelectorAll('.participants-section .adm-pagination .pagination-controls .adm-btn');
  if (btns[0]) btns[0].disabled = participantsPage <= 1;
  if (btns[1]) btns[1].disabled = participantsPage >= totalPages;

  renderParticipantRows(slice);
}

function changeParticipantsPage(delta) {
  const total      = filteredParticipants.length;
  const totalPages = Math.max(1, Math.ceil(total / PARTICIPANTS_PER_PAGE));
  const newPage = participantsPage + delta;
  if (newPage >= 1 && newPage <= totalPages) {
    participantsPage = newPage;
    renderParticipantsPage();
    document.querySelector('.participants-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
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

function editParticipant(id) {
  const participantId = parseInt(id, 10);
  if (!participantId) return;
  editingParticipantId = participantId;
  renderParticipantsTable();
  renderSelectedChallengeParticipants();
  setTimeout(() => {
    const form = document.getElementById(`participant-edit-form-${participantId}`);
    form?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    form?.querySelector('[name="nom"]')?.focus();
  }, 0);
}

function cancelParticipantEdit() {
  editingParticipantId = null;
  renderParticipantsTable();
  renderSelectedChallengeParticipants();
}

function renderParticipantEditRow(p) {
  const id = parseInt(p.id, 10);
  const challengeOptions = adminChallenges.map(c => {
    const selected = String(c.id) === String(p.id_challenge) ? 'selected' : '';
    return `<option value="${c.id}" ${selected}>${escapeHtml((c.streak_icon ? c.streak_icon + ' ' : '') + (c.titre || `#${c.id}`))}</option>`;
  }).join('');

  return `
    <tr class="participant-row-admin participant-row-admin--editing">
      <td colspan="8">
        <form id="participant-edit-form-${id}" class="adm-inline-edit-form" onsubmit="saveParticipantInline(event, ${id})"
              style="display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px;align-items:end;padding:18px;border:1px solid rgba(168,184,160,.22);border-radius:16px;background:rgba(255,255,255,.03);">
          <label style="grid-column:span 3;color:var(--text);font-size:12px;font-weight:700;">
            Nom
            <input class="adm-input" name="nom" value="${escapeHtml(p.nom || '')}" required style="width:100%;margin-top:6px;">
          </label>
          <label style="grid-column:span 3;color:var(--text);font-size:12px;font-weight:700;">
            Email
            <input class="adm-input" type="email" name="email" value="${escapeHtml(p.email || '')}" required style="width:100%;margin-top:6px;">
          </label>
          <label style="grid-column:span 3;color:var(--text);font-size:12px;font-weight:700;">
            Défi
            <select class="adm-select" name="id_challenge" required style="width:100%;margin-top:6px;">
              ${challengeOptions}
            </select>
          </label>
          <label style="grid-column:span 3;color:var(--text);font-size:12px;font-weight:700;">
            Progression
            <input class="adm-input" type="number" name="objectif" min="0" max="100" value="${clampInt(p.objectif, 0, 100)}" required style="width:100%;margin-top:6px;">
          </label>
          <label style="grid-column:span 6;color:var(--text);font-size:12px;font-weight:700;">
            Motivation
            <textarea class="adm-input" name="motivation" rows="2" required style="width:100%;margin-top:6px;resize:vertical;">${escapeHtml(p.motivation || '')}</textarea>
          </label>
          <label style="grid-column:span 6;color:var(--text);font-size:12px;font-weight:700;">
            Plan d'action
            <textarea class="adm-input" name="action" rows="2" required style="width:100%;margin-top:6px;resize:vertical;">${escapeHtml(p.action || '')}</textarea>
          </label>
          <label style="grid-column:span 3;display:flex;gap:8px;align-items:center;color:var(--text);font-size:12px;font-weight:700;">
            <input type="checkbox" name="engagement" value="1" ${parseInt(p.engagement, 10) === 1 ? 'checked' : ''}>
            Engagé
          </label>
          <label style="grid-column:span 3;display:flex;gap:8px;align-items:center;color:var(--text);font-size:12px;font-weight:700;">
            <input type="checkbox" name="notifications" value="1" ${parseInt(p.notifications, 10) === 1 ? 'checked' : ''}>
            Notifications
          </label>
          <div style="grid-column:span 6;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="cancelParticipantEdit()">Annuler</button>
            <button type="submit" class="adm-btn adm-btn--primary adm-btn--sm">Enregistrer</button>
          </div>
        </form>
      </td>
    </tr>`;
}

function saveParticipantInline(event, id) {
  event.preventDefault();
  const form = event.currentTarget;
  const btn = form.querySelector('button[type="submit"]');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Enregistrement...';
  }

  const data = new FormData(form);
  fetch(`challenges/updateParticipant.php?id=${id}`, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: data
  })
    .then(r => r.json())
    .then(result => {
      if (!result?.success) {
        throw new Error(result?.error || 'Modification impossible');
      }
      showToast('Participant modifié', 'La ligne a été mise à jour.', 'success');
      editingParticipantId = null;
      loadAdminParticipants();
      if (selectedChallengeParticipants.length) {
        const currentChallengeId = selectedChallengeParticipants[0]?.id_challenge || result.id_challenge || 0;
        if (currentChallengeId) showChallengeParticipants(currentChallengeId);
      }
    })
    .catch(err => {
      showToast('Erreur', err.message || 'Modification impossible.', 'error');
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Enregistrer';
      }
    });
}

function ensureProgressCoachModal() {
  let overlay = document.getElementById('adm-progress-coach-modal');
  if (overlay) return overlay;
  overlay = document.createElement('div');
  overlay.id = 'adm-progress-coach-modal';
  overlay.className = 'adm-coach-modal adm-modal-overlay';
  overlay.style.cssText = `
    display:none; position:fixed; inset:0; z-index:99999;
    background:rgba(0,0,0,0.75); backdrop-filter:blur(8px);
    align-items:center; justify-content:center; padding:20px;
    cursor: default;
  `;
  overlay.innerHTML = `
    <div class="adm-coach-card adm-modal-container" style="
      background:#1e1e2e; border:1px solid #6366f1; border-radius:18px;
      width:700px; max-width:100%; max-height:90vh; overflow-y:auto;
      position:relative; box-shadow:0 20px 50px rgba(0,0,0,0.5);
      cursor: auto;
    ">
      <button class="adm-coach-close" onclick="closeProgressCoach()" style="position:absolute; top:20px; right:20px; background:none; border:none; color:#94a3b8; font-size:1.5rem; cursor:pointer; padding:5px; transition: color 0.2s; z-index:11;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">×</button>
      <div class="adm-coach-head" style="position:sticky; top:0; background:#1e1e2e; padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.1); z-index:10; display:flex; align-items:center; gap:15px;">
        <span class="adm-coach-icon" style="font-size:2rem; background:linear-gradient(135deg,#6366f1,#8b5cf6); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">🧠</span>
        <div>
          <h3 style="color:#e2e8f0; margin:0;">Coach IA de progression</h3>
          <p id="adm-coach-subtitle" style="color:#94a3b8; font-size:13px; margin:4px 0 0;"></p>
        </div>
      </div>
      <div id="adm-coach-body" class="adm-coach-body" style="padding:24px;">
        <div class="adm-coach-loading">Analyse en cours...</div>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeProgressCoach();
  });
  return overlay;
}

function closeProgressCoach() {
  const overlay = document.getElementById('adm-progress-coach-modal');
  if (overlay) overlay.style.display = 'none';
}

function openProgressCoach(id) {
  const participantId = parseInt(id, 10);
  if (!participantId) return;
  currentCoachParticipantId = participantId;
  const modal = ensureProgressCoachModal();
  const body = document.getElementById('adm-coach-body');
  const subtitle = document.getElementById('adm-coach-subtitle');
  const participant = adminParticipants.find(p => String(p.id) === String(participantId));
  if (subtitle) subtitle.textContent = participant ? `${participant.nom || 'Participant'} · ${participant.challenge_titre || 'Défi'}` : 'Analyse du participant';
  if (body) body.innerHTML = '<div class="adm-coach-loading">Analyse en cours...</div>';
  modal.style.display = 'flex';

  fetch('api/ai-progress-coach.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ id_participant: participantId })
  })
    .then(r => r.json())
    .then(result => {
      if (!result?.success) throw new Error(result?.error || 'Analyse impossible');
      renderProgressCoach(result);
    })
    .catch(err => {
      if (body) body.innerHTML = `<div class="adm-coach-error">⚠️ ${escapeHtml(err.message || 'Analyse impossible')}</div>`;
    });
}

function renderProgressCoach(result) {
  const body = document.getElementById('adm-coach-body');
  if (!body) return;
  const a = result.analysis || {};
  const c = result.coach || {};
  const p = result.participant || {};
  const risk = clampInt(c.risque || a.risk_score, 0, 100);
  const tone = risk >= 65 ? 'danger' : risk >= 35 ? 'warning' : 'success';
  const plan = Array.isArray(c.plan_7_jours) ? c.plan_7_jours : [];
  
  const sentiment = c.sentiment || 'Neutre';
  const intention = c.intention || 'Inconnue';
  const status = c.statut || a.status || 'Inconnu';

  body.innerHTML = `
    <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:12px; margin-bottom:24px;">
      <div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:12px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:18px; font-weight:800; color:${tone === 'danger' ? '#ef4444' : tone === 'warning' ? '#f59e0b' : '#22c55e'};">${risk}%</div>
        <div style="font-size:9px; text-transform:uppercase; color:#94a3b8; margin-top:4px;">Risque</div>
      </div>
      <div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:12px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:13px; font-weight:700; color:#22c55e;">${escapeHtml(status)}</div>
        <div style="font-size:9px; text-transform:uppercase; color:#94a3b8; margin-top:4px;">Statut IA</div>
      </div>
      <div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:12px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:18px; font-weight:800; color:#3b82f6;">${clampInt(a.progress, 0, 100)}%</div>
        <div style="font-size:9px; text-transform:uppercase; color:#94a3b8; margin-top:4px;">Progression</div>
      </div>
      <div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:12px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:18px; font-weight:800; color:#94a3b8;">${clampInt(a.expected_progress, 0, 100)}%</div>
        <div style="font-size:9px; text-transform:uppercase; color:#94a3b8; margin-top:4px;">Attendu</div>
      </div>
      <div style="background:rgba(255,255,255,0.03); padding:12px; border-radius:12px; text-align:center; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:18px; font-weight:800; color:#f59e0b;">${parseInt(a.days_left || 0, 10)}</div>
        <div style="font-size:9px; text-transform:uppercase; color:#94a3b8; margin-top:4px;">Jours Rest.</div>
      </div>
    </div>

    <div style="margin-bottom:24px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span style="font-size:12px; color:#e2e8f0; font-weight:600;">Niveau de risque d'abandon</span>
        <span style="font-size:12px; font-weight:700; color:${tone === 'danger' ? '#ef4444' : tone === 'warning' ? '#f59e0b' : '#22c55e'};">${risk}% — ${risk >= 65 ? 'Critique' : risk >= 35 ? 'Moyen' : 'Faible'}</span>
      </div>
      <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:4px; overflow:hidden; display:flex;">
        <div style="width:${risk}%; height:100%; background:${tone === 'danger' ? '#ef4444' : tone === 'warning' ? '#f59e0b' : '#22c55e'};"></div>
      </div>
      <div style="display:flex; gap:10px; margin-top:10px;">
        <span style="font-size:9px; color:#22c55e; border:1px solid #22c55e; padding:2px 8px; border-radius:10px; background:rgba(34,197,94,0.1);">0-35% Faible</span>
        <span style="font-size:9px; color:#f59e0b; border:1px solid #f59e0b; padding:2px 8px; border-radius:10px; background:rgba(245,158,11,0.1);">35-65% Moyen</span>
        <span style="font-size:9px; color:#ef4444; border:1px solid #ef4444; padding:2px 8px; border-radius:10px; background:rgba(239,68,68,0.1);">65%+ Critique</span>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px;">
      <div style="background:rgba(255,255,255,0.03); padding:16px; border-radius:14px; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:10px; text-transform:uppercase; color:#94a3b8; margin-bottom:6px; letter-spacing:0.5px;">Sentiment Détecté</div>
        <div style="font-weight:700; color:#e2e8f0; display:flex; align-items:center; gap:8px;">
          <span style="width:8px; height:8px; border-radius:50%; background:${sentiment === 'Positif' ? '#22c55e' : sentiment === 'Neutre' ? '#94a3b8' : '#ef4444'}"></span>
          ${escapeHtml(sentiment)}
        </div>
      </div>
      <div style="background:rgba(255,255,255,0.03); padding:16px; border-radius:14px; border:1px solid rgba(255,255,255,0.05);">
        <div style="font-size:10px; text-transform:uppercase; color:#94a3b8; margin-bottom:6px; letter-spacing:0.5px;">Intention</div>
        <div style="font-weight:700; color:#e2e8f0; display:flex; align-items:center; gap:8px;">
          <span style="width:8px; height:8px; border-radius:50%; background:#6366f1"></span>
          ${escapeHtml(intention)}
        </div>
      </div>
    </div>

    <div style="background:rgba(99,102,241,0.05); border-left:4px solid #6366f1; padding:20px; border-radius:0 14px 14px 0; margin-bottom:24px;">
      <p style="color:#e2e8f0; font-size:14px; line-height:1.6; margin:0;">${escapeHtml(c.message || 'Bonjour, voici votre analyse de progression.')}</p>
      <div style="font-size:10px; color:#94a3b8; margin-top:12px;">groq · ${escapeHtml(result.coach?.model || 'llama-3.3-70b')}</div>
    </div>

    <div style="background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); padding:14px 18px; border-radius:12px; margin-bottom:24px;">
      <div style="color:#3b82f6; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
        <span>Conseil admin :</span>
        <span style="color:#e2e8f0; font-weight:400;">${escapeHtml(c.conseil_admin || 'Aucune intervention nécessaire.')}</span>
      </div>
    </div>

    <div style="margin-bottom:24px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h4 style="margin:0; font-size:15px; color:#e2e8f0;">Plan 7 jours</h4>
        <span style="font-size:11px; color:#94a3b8;">0 / 7 réalisés</span>
      </div>
      <div style="display:flex; flex-direction:column; gap:8px;">
        ${plan.map((item, i) => `
          <div style="background:rgba(255,255,255,0.03); padding:12px 16px; border-radius:10px; display:flex; align-items:center; gap:12px; border:1px solid rgba(255,255,255,0.05);">
            <span style="background:rgba(99,102,241,0.1); color:#818cf8; width:24px; height:24px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;">J${i + 1}</span>
            <div style="flex:1;">
              <div style="font-size:13px; color:#e2e8f0; font-weight:500;">${escapeHtml(item.action)}</div>
              <div style="font-size:11px; color:#94a3b8; margin-top:2px;">${escapeHtml(item.conseil)}</div>
            </div>
            <input type="checkbox" style="width:18px; height:18px; cursor:pointer; accent-color:#6366f1;">
          </div>
        `).join('')}
      </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px; margin-top:30px;">
      <button type="button" class="adm-btn adm-btn--ghost" style="width:100%; display:flex; align-items:center; justify-content:center; gap:10px; border-radius:12px; padding:12px;" onclick="openCoachChat(${p.id})">
        <span>💬 Chatter avec le Coach IA</span>
      </button>
      <div style="display:flex; gap:10px;">
        <button type="button" class="adm-btn adm-btn--ghost" style="flex:1;" onclick="closeProgressCoach()">Annuler</button>
        <button type="button" class="adm-btn adm-btn--primary" style="flex:2;" onclick="sendProgressCoachEmail()">Valider et envoyer par mail</button>
      </div>
    </div>
    
    <!-- Zone de Chat IA (Masquée par défaut) -->
    <div id="adm-coach-chat-zone" style="display:none; margin-top:20px; border-top:1px solid rgba(255,255,255,0.1); padding-top:20px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h4 style="margin:0; color:#e2e8f0; font-size:14px;">Chat en direct avec l'IA</h4>
        <button onclick="closeCoachChat()" style="background:none; border:none; color:#ef4444; font-size:11px; cursor:pointer;">✕ Fermer le chat</button>
      </div>
      <div id="adm-coach-chat-messages" style="height:200px; overflow-y:auto; background:rgba(0,0,0,0.2); border-radius:10px; padding:15px; margin-bottom:15px; display:flex; flex-direction:column; gap:10px;">
        <div style="background:rgba(99,102,241,0.1); padding:8px 12px; border-radius:12px 12px 12px 0; color:#e2e8f0; font-size:13px; align-self:flex-start; max-width:85%;">
          Bonjour ! Je suis votre coach IA. Comment puis-je vous aider aujourd'hui ?
        </div>
      </div>
      <div style="display:flex; gap:8px;">
        <input type="text" id="adm-coach-chat-input" placeholder="Posez une question au coach..." style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:10px; color:#fff; font-size:13px;">
        <button onclick="sendCoachChatMessage(${p.id})" class="adm-btn adm-btn--primary" style="padding:0 15px;">Envoyer</button>
      </div>
    </div>`;
}

function openCoachChat(id) {
  const zone = document.getElementById('adm-coach-chat-zone');
  if (zone) zone.style.display = 'block';
  const input = document.getElementById('adm-coach-chat-input');
  if (input) {
    input.focus();
    input.onkeydown = (e) => { if (e.key === 'Enter') sendCoachChatMessage(id); };
  }
}

function closeCoachChat() {
  const zone = document.getElementById('adm-coach-chat-zone');
  if (zone) zone.style.display = 'none';
}

async function sendCoachChatMessage(id) {
  const input = document.getElementById('adm-coach-chat-input');
  const messages = document.getElementById('adm-coach-chat-messages');
  const text = input?.value.trim();
  if (!text) return;

  // Ajouter le message utilisateur
  const userMsg = document.createElement('div');
  userMsg.style.cssText = 'background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:12px 12px 0 12px; color:#e2e8f0; font-size:13px; align-self:flex-end; max-width:85%;';
  userMsg.textContent = text;
  messages.appendChild(userMsg);
  input.value = '';
  messages.scrollTop = messages.scrollHeight;

  // Loader IA
  const loadingMsg = document.createElement('div');
  loadingMsg.style.cssText = 'color:#94a3b8; font-size:11px; align-self:flex-start; margin-left:5px;';
  loadingMsg.textContent = 'Le coach réfléchit...';
  messages.appendChild(loadingMsg);

  try {
    const resp = await fetch('api/ai-coach-chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ participant_id: id, message: text })
    });
    const data = await resp.json();
    loadingMsg.remove();

    if (!data.ok) throw new Error(data.error);

    const coachMsg = document.createElement('div');
    coachMsg.style.cssText = 'background:rgba(99,102,241,0.1); padding:8px 12px; border-radius:12px 12px 12px 0; color:#e2e8f0; font-size:13px; align-self:flex-start; max-width:85%;';
    coachMsg.textContent = data.reply;
    messages.appendChild(coachMsg);
    messages.scrollTop = messages.scrollHeight;
  } catch (err) {
    loadingMsg.textContent = '⚠️ Erreur: ' + err.message;
  }
}

function sendProgressCoachEmail() {
  if (!currentCoachParticipantId) return;
  if (!confirm('Valider ce plan Coach IA et l envoyer par mail au participant ?')) return;

  const body = document.getElementById('adm-coach-body');
  const actions = body?.querySelector('.adm-coach-actions');
  if (actions) {
    actions.innerHTML = '<div class="adm-coach-loading" style="width:100%;">Envoi du mail en cours...</div>';
  }

  fetch('api/ai-progress-coach.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ id_participant: currentCoachParticipantId, send_email: true })
  })
    .then(r => r.json())
    .then(result => {
      if (!result?.success) throw new Error(result?.error || 'Envoi impossible');
      if (!result.email?.sent) throw new Error(result.email?.error || 'Mail non envoye');
      showToast('Coach IA envoyé', 'Le plan a été envoyé par mail au participant.', 'success');
      closeProgressCoach();
    })
    .catch(err => {
      showToast('Erreur mail', err.message || 'Envoi impossible.', 'error');
      if (actions) {
        actions.innerHTML = `
          <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" onclick="closeProgressCoach()">Annuler</button>
          <button type="button" class="adm-btn adm-btn--primary adm-btn--sm" onclick="sendProgressCoachEmail()">Réessayer l'envoi</button>`;
      }
    });
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

function showRiskExplanation(participantId, event) {
  if (event) event.stopPropagation();
  
  showToast('Analyse ML', 'Récupération des données prédictives...', 'info');

  fetch('api/ml-predict-risk.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_participant: participantId })
  })
    .then(r => r.json())
    .then(data => {
      if (!data.success) throw new Error(data.error);
      
      const m = data.metrics;
      const p = data.prediction;
      
      const content = `
        <div style="text-align:left; margin-top:10px;">
          <div style="margin-bottom:8px; padding:8px; background:rgba(255,255,255,0.05); border-radius:6px;">
            <b style="color:#ff4d4d;">Probabilité de réussite : ${p.success_probability}%</b><br>
            <small>Niveau de risque : ${p.risk_level}</small>
          </div>
          <ul style="list-style:none; padding:0; font-size:12px; line-height:1.6;">
            <li>📉 <b>Retard :</b> ${Math.abs(m.gap)}% par rapport à l'attendu</li>
            <li>💬 <b>Activité Chat :</b> ${m.chat_activity_7d} messages (7 derniers jours)</li>
            <li>🎯 <b>Recommandation :</b> ${p.recommendation}</li>
          </ul>
        </div>
      `;
      
      showToast('Pourquoi ce risque ?', content, 'warning', 8000);
    })
    .catch(err => {
      showToast('Erreur ML', 'Impossible de récupérer les détails : ' + err.message, 'error');
    });
}

// ─── Exposer globalement ──────────────────────────────────────
window.loadAdminChallenges            = loadAdminChallenges;
window.showRiskExplanation            = showRiskExplanation;
window.loadAdminParticipants          = loadAdminParticipants;
window.loadStatistiques               = loadStatistiques;
window.loadAdvancedAnalytics          = loadAdvancedAnalytics;
window.changeChallengeCalendarMonth   = changeChallengeCalendarMonth;
window.selectChallengeCalendarDate    = selectChallengeCalendarDate;
window.loadCalendarChallengeAI        = loadCalendarChallengeAI;
window.toggleChallengeCalendar        = toggleChallengeCalendar;
window.toggleStatsUserPredictions     = toggleStatsUserPredictions;
window.loadStatsUserPredictions       = loadStatsUserPredictions;
window.renderChallengesTable          = renderChallengesTable;
window.changeChallengesPage           = changeChallengesPage;
window.renderParticipantsPage         = renderParticipantsPage;
window.closeAdmModal                  = closeAdmModal;
window.showChallengeParticipants      = showChallengeParticipants;
window.closeChallengeParticipantsPanel = closeChallengeParticipantsPanel;
window.editParticipant                = editParticipant;
window.cancelParticipantEdit          = cancelParticipantEdit;
window.saveParticipantInline          = saveParticipantInline;
window.openProgressCoach              = openProgressCoach;
window.closeProgressCoach             = closeProgressCoach;
window.sendProgressCoachEmail         = sendProgressCoachEmail;
window.updateStatutChallenge          = updateStatutChallenge;
window.toggleStatutDropdown           = toggleStatutDropdown;
window.exportCSV                      = exportCSV;
window.exportPDF                      = exportPDF;
window.saveOrder                      = saveOrder;
window.openNotifModal                 = openNotifModal;
window.closeNotifModal                = closeNotifModal;
window.applyNotifTemplate             = applyNotifTemplate;
window.resetNotifModal                = resetNotifModal;
window.sendNotification               = sendNotification;
window.showAISummaryToday             = showAISummaryToday;
window.showAISummaryHistory           = showAISummaryHistory;
window.openCoachChat                  = openCoachChat;
window.closeCoachChat                 = closeCoachChat;
window.sendCoachChatMessage           = sendCoachChatMessage;
window.toggleStats                    = toggleStats;
window.showToast                      = showToast;
window.adminChallenges                = () => adminChallenges;
window.adminParticipants              = () => adminParticipants;
