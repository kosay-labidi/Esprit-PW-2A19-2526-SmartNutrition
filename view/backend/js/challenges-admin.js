/**
 * Module Admin Défis - GaiaLumen Backend
 * Uniquement AJOUTER et AFFICHER les défis
 */

console.log('🏆 Admin Challenges (Mode Ajout/Affichage) chargé');

let adminChallenges = [];
let adminParticipants = [];

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

// Écouter l'événement de chargement du module par le loader
document.addEventListener('adminModuleLoaded', (e) => {
  if (e.detail.moduleName === 'challenges') {
    console.log('📦 Module Challenges détecté, chargement des données...');
    initChallengeForm();
    loadAdminChallenges();
    loadAdminParticipants();
  }
});

// Au cas où le script est chargé après le module
if (document.getElementById('challenges')) {
  initChallengeForm();
  loadAdminChallenges();
  loadAdminParticipants();
}

function initChallengeForm() {
  const form = document.getElementById('challenge-form');
  if (form) {
    form.onsubmit = function(e) {
      e.preventDefault();
      
      const formData = new FormData(form);
      const isUpdate = formData.get('id') !== '';
      const url = isUpdate ? 'challenges/updateChallenge.php?id=' + formData.get('id') : 'challenges/addChallenge.php';
      
      const submitBtn = document.getElementById('form-submit-btn');
      const originalText = submitBtn.innerText;
      submitBtn.disabled = true;
      submitBtn.innerText = '⌛ Envoi...';

      fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => {
        // Si c'est un ajout/modif classique, il redirige. On intercepte ça.
        if (response.redirected) {
          return { success: true };
        }
        return response.text().then(text => {
          try {
            return JSON.parse(text);
          } catch(e) {
            // Si ce n'est pas du JSON, on considère que c'est bon si le statut est OK
            return { success: response.ok };
          }
        });
      })
      .then(result => {
        if (result.success || result === true) {
          console.log('✅ Succès !');
          resetForm();
          loadAdminChallenges(); // Recharger le tableau
          
          // Notification visuelle (optionnelle)
          const msg = isUpdate ? 'Défi modifié avec succès !' : 'Défi créé avec succès !';
          alert(msg);
        } else {
          alert('❌ Erreur lors de l\'enregistrement : ' + (result.message || 'Erreur inconnue'));
        }
      })
      .catch(err => {
        console.error('Erreur AJAX:', err);
        alert('❌ Erreur de connexion au serveur');
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
      });
    };
  }
}

// ── Chargement des données (AFFICHER) ────────────────────────
function loadAdminChallenges() {
  fetch('challenges/listChallenges.php', {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => response.json())
    .then(data => {
      adminChallenges = data;
      renderChallengesTable();
      updateDashboardStats();
      renderParticipantsChallengeFilter();
    })
    .catch(err => {
      console.error('Erreur lors du chargement des défis admin:', err);
    });
}

function loadAdminParticipants() {
  fetch('challenges/showParticipant.php', {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => response.json())
    .then(result => {
      adminParticipants = Array.isArray(result.participants) ? result.participants : [];
      renderParticipantsTable();
      updateParticipantsStats();
      updateDashboardStats();
    })
    .catch(err => {
      console.error('Erreur lors du chargement des participants admin:', err);
      adminParticipants = [];
      renderParticipantsTable();
      updateParticipantsStats();
    });
}

// ═══════════════════════════════════════════════════════════
// FONCTIONS PRINCIPALES
// ═══════════════════════════════════════════════════════════

function resetForm() {
  const form = document.getElementById('challenge-form');
  if (form) {
    form.reset();
    const idField = document.getElementById('challenge-id');
    if (idField) idField.value = '';
    document.getElementById('form-title').innerText = '➕ Nouveau Défi';
    const submitBtn = document.getElementById('form-submit-btn');
    if (submitBtn) submitBtn.innerText = '✅ Créer';
    document.getElementById('description-count').innerText = '0/500';
  }
}

function updateCharCountAdmin(textarea, displayId, max) {
  const count = textarea.value.length;
  const display = document.getElementById(displayId);
  if (display) display.innerText = `${count}/${max}`;
}

// ═══════════════════════════════════════════════════════════
// ACTIONS CRUD
// ═══════════════════════════════════════════════════════════

function editChallenge(id) {
  const challenge = adminChallenges.find(c => c.id == id);
  if (!challenge) return;
  
  // Remplir le formulaire avec les données
  document.getElementById('challenge-id').value = challenge.id;
  document.getElementById('challenge-titre').value = challenge.titre;
  document.getElementById('challenge-description').value = challenge.description;
  document.getElementById('challenge-type').value = challenge.type;
  document.getElementById('challenge-objectif').value = challenge.objectif;
  document.getElementById('challenge-valeur').value = challenge.valeur_cible;
  document.getElementById('challenge-date-debut').value = challenge.date_debut;
  document.getElementById('challenge-date-fin').value = challenge.date_fin;
  document.getElementById('challenge-statut').value = challenge.statut;
  document.getElementById('challenge-streak-icon').value = challenge.streak_icon;
  document.getElementById('challenge-image').value = challenge.image;
  
  // Changer le titre et le bouton du formulaire
  document.getElementById('form-title').innerText = '✏️ Modifier le Défi';
  document.getElementById('form-submit-btn').innerText = '💾 Enregistrer';
  
  // Scroll vers le formulaire
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteChallenge(id) {
  if (confirm('⚠️ Voulez-vous vraiment supprimer ce défi ? Cette action est irréversible.')) {
    fetch(`challenges/deleteChallenge.php?id=${id}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (response.redirected || response.ok) {
        return { success: true };
      }
      return response.json();
    })
    .then(result => {
      if (result.success) {
        console.log('✅ Défi supprimé');
        loadAdminChallenges(); // Recharger le tableau
      } else {
        alert('❌ Erreur : ' + (result.message || 'Impossible de supprimer'));
      }
    })
    .catch(err => {
      console.error('Erreur suppression:', err);
      alert('❌ Erreur de connexion');
    });
  }
}

// ═══════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getJoursRestants(dateFin) {
  if (!dateFin) return '-';
  const fin = new Date(dateFin);
  const maintenant = new Date();
  const diff = Math.ceil((fin - maintenant) / (1000 * 60 * 60 * 24));
  
  if (diff < 0) return 'Terminé';
  if (diff === 0) return 'Aujourd\'hui';
  if (diff === 1) return 'Demain';
  return `Dans ${diff} jours`;
}

// ═══════════════════════════════════════════════════════════
// RENDU DU TABLEAU (AFFICHER)
// ═══════════════════════════════════════════════════════════

function renderChallengesTable() {
  const tbody = document.getElementById('challenges-tbody');
  if (!tbody) return;
  
  const filteredChallenges = filterChallengesList();
  
  if (filteredChallenges.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
          <div style="font-size:3rem;margin-bottom:12px;">🔍</div>
          <div>Aucun défi trouvé</div>
        </td>
      </tr>
    `;
    return;
  }
  
  tbody.innerHTML = filteredChallenges.map(c => {
    const status = c.statut || 'actif';
    const badgeClass = status === 'actif' ? 'badge-active' : 
                       status === 'termine' ? 'badge-completed' : 'badge-upcoming';
    const badgeText = status === 'actif' ? 'Actif' : 
                      status === 'termine' ? 'Terminé' : 'À venir';
    
    const descShort = c.description ? (c.description.substring(0, 50) + '...') : 'Pas de description';
    
    return `
      <tr class="table-row-animated">
        <td>
          <div class="challenge-cell">
            <span class="challenge-icon">${c.streak_icon || '🏆'}</span>
            <div>
              <div class="challenge-name">${c.titre || 'Sans titre'}</div>
              <div class="challenge-desc">${descShort}</div>
            </div>
          </div>
        </td>
        <td><span class="badge badge-type">${c.type === 'collectif' ? '👥 Collectif' : '👤 Individuel'}</span></td>
        <td>
          <div style="font-weight:700; color:var(--text); font-size:1.1rem;">${c.participants_count || 0}</div>
          <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px;">inscrits</div>
        </td>
        <td><span class="badge ${badgeClass}">${badgeText}</span></td>
        <td>
          <div style="font-weight:600; color:var(--text);">${getJoursRestants(c.date_fin)}</div>
          <div style="font-size:0.8rem; color:var(--muted);">${formatDate(c.date_fin)}</div>
        </td>
        <td>
          <div class="action-btns">
            <button class="btn-icon" onclick="editChallenge(${c.id})" title="Modifier" style="background: rgba(91, 62, 150, 0.2); color: var(--blue);">
              ✏️
            </button>
            <button class="btn-icon btn-danger" onclick="deleteChallenge(${c.id})" title="Supprimer" style="background: rgba(231, 76, 60, 0.15); color: #e74c3c;">
              🗑️
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
  
  // Mise à jour de l'info de pagination/affichage
  const pagInfo = document.querySelector('.pagination-info');
  if (pagInfo) {
    pagInfo.innerText = `Affichage de ${filteredChallenges.length} défi(s) depuis la base de données`;
    pagInfo.parentElement.style.display = filteredChallenges.length > 0 ? 'flex' : 'none';
  }
}

// ═══════════════════════════════════════════════════════════
// FILTRAGE ET RECHERCHE
// ═══════════════════════════════════════════════════════════

function filterChallengesList() {
  const searchInput = document.getElementById('search-input-admin');
  const statusFilter = document.getElementById('status-filter-admin');
  
  const search = searchInput?.value.toLowerCase() || '';
  const status = statusFilter?.value || '';
  
  return adminChallenges.filter(c => {
    const matchSearch = !search || 
      c.titre.toLowerCase().includes(search) || 
      c.description.toLowerCase().includes(search);
    
    const matchStatus = !status || c.statut === status;
    
    return matchSearch && matchStatus;
  });
}

function searchChallengesAdmin() {
  renderChallengesTable();
}

function filterChallengesAdmin() {
  renderChallengesTable();
}

function refreshTableAdmin() {
  loadAdminChallenges();
}

function updateDashboardStats() {
  const statChallenges = document.querySelector('.stat-mini:nth-child(1) .stat-mini-value');
  const statParticipants = document.querySelector('.stat-mini:nth-child(2) .stat-mini-value');
  const statCompletion = document.querySelector('.stat-mini:nth-child(3) .stat-mini-value');
  
  if (statChallenges) statChallenges.innerText = adminChallenges.length;
  if (statParticipants) statParticipants.innerText = adminParticipants.length;
  if (statCompletion) {
    const termines = adminChallenges.filter(c => c.statut === 'termine').length;
    const rate = adminChallenges.length > 0 ? Math.round((termines / adminChallenges.length) * 100) : 0;
    statCompletion.innerText = rate + '%';
  }
}

// ═══════════════════════════════════════════════════════════
// RENDU DU TABLEAU PARTICIPANTS
// ═══════════════════════════════════════════════════════════

function renderParticipantsChallengeFilter() {
  const select = document.getElementById('challenge-filter');
  if (!select) return;

  const previousValue = select.value;
  const options = ['<option value="">Tous les défis</option>'];

  adminChallenges.forEach(c => {
    const id = c.id;
    const titre = c.titre || `Challenge #${id}`;
    options.push(`<option value="${id}">${escapeHtml(titre)}</option>`);
  });

  select.innerHTML = options.join('');
  if (previousValue !== undefined) {
    select.value = previousValue;
  }
}

function renderParticipantsTable() {
  const tbody = document.getElementById('participants-tbody');
  if (!tbody) return;
  
  const searchValue = (document.getElementById('search-participants')?.value || '').toLowerCase();
  const challengeFilter = document.getElementById('challenge-filter')?.value || '';
  const progressFilter = document.getElementById('progress-filter')?.value || '';

  const filteredParticipants = adminParticipants.filter(p => {
    const nom = String(p.nom || '');
    const email = String(p.email || '');
    const challengeTitre = String(p.challenge_titre || '');

    if (searchValue) {
      const haystack = `${nom} ${email} ${challengeTitre}`.toLowerCase();
      if (!haystack.includes(searchValue)) return false;
    }

    if (challengeFilter) {
      if (String(p.id_challenge) !== String(challengeFilter)) return false;
    }

    const prog = clampInt(p.objectif, 0, 100);
    if (progressFilter === 'low' && !(prog >= 0 && prog <= 30)) return false;
    if (progressFilter === 'medium' && !(prog >= 31 && prog <= 70)) return false;
    if (progressFilter === 'high' && !(prog >= 71 && prog <= 100)) return false;

    return true;
  });
  
  if (filteredParticipants.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
          <div style="font-size:3rem;margin-bottom:12px;">👤</div>
          <div>Aucun participant trouvé</div>
        </td>
      </tr>
    `;
    updateParticipantsPagination(filteredParticipants.length);
    return;
  }
  
  tbody.innerHTML = filteredParticipants.map(p => {
    const nom = String(p.nom || '');
    const email = String(p.email || '');
    const avatar = nom ? nom.split(' ').filter(Boolean).map(n => n[0]).join('').slice(0, 2).toUpperCase() : '??';
    const challengeIcon = String(p.challenge_icon || '🏆');
    const challengeTitre = String(p.challenge_titre || (`Challenge #${p.id_challenge}`));

    const prog = clampInt(p.objectif, 0, 100);
    const progColor = prog > 70 ? 'high' : prog > 30 ? 'medium' : 'low';
    const objective = p.challenge_target !== null && p.challenge_target !== undefined && p.challenge_target !== '' ? `${p.challenge_target}%` : '-';
    const joined = formatDate(p.date_inscription);
    const isActive = clampInt(p.engagement, 0, 1) === 1;
    const statusLabel = isActive ? 'Actif' : 'Inactif';
    const statusClass = isActive ? 'badge-active' : 'badge-completed';
    
    return `
      <tr class="table-row-animated">
        <td>
          <div class="participant-info">
            <div class="participant-avatar">${avatar}</div>
            <div class="participant-details">
              <div class="participant-name">${escapeHtml(nom)}</div>
              <div class="participant-email">${escapeHtml(email)}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 1.5rem;">${escapeHtml(challengeIcon)}</span>
            <span style="font-weight: 600;">${escapeHtml(challengeTitre)}</span>
          </div>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 10px;">
            <div class="progress-bar-participant" style="flex:1; height:8px; background:rgba(91,62,150,0.1); border-radius:4px; overflow:hidden;">
              <div class="progress-fill-participant ${progColor}" style="width: ${prog}%; height:100%; transition:width 1s ease;"></div>
            </div>
            <span style="font-weight: 700; min-width:35px;">${prog}%</span>
          </div>
        </td>
        <td>${escapeHtml(objective)}</td>
        <td><div style="font-weight:600;">${escapeHtml(joined)}</div></td>
        <td><span class="badge ${statusClass}">${statusLabel}</span></td>
        <td>
          <div class="action-btns">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir détails">
              👁️
            </button>
            <button class="btn-icon btn-danger" onclick="deleteParticipant(${p.id}, ${p.id_challenge})" title="Supprimer">
              🗑️
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  updateParticipantsPagination(filteredParticipants.length);
}

function updateParticipantsPagination(visibleCount) {
  const total = visibleCount;
  const start = total > 0 ? 1 : 0;
  const end = total;
  const el = document.getElementById('participants-pagination-info');
  if (el) el.innerText = `Affichage de ${start} à ${end} sur ${total} participants`;
}

function updateParticipantsStats() {
  const total = adminParticipants.length;
  const active = adminParticipants.filter(p => clampInt(p.engagement, 0, 1) === 1).length;
  const engagement = total > 0 ? Math.round((active / total) * 100) : 0;

  const totalEl = document.getElementById('participants-total');
  const activeEl = document.getElementById('participants-active');
  const engEl = document.getElementById('participants-engagement');

  if (totalEl) totalEl.innerText = total.toLocaleString('fr-FR');
  if (activeEl) activeEl.innerText = active.toLocaleString('fr-FR');
  if (engEl) engEl.innerText = `${engagement}%`;
}

function searchParticipants() {
  renderParticipantsTable();
}

function filterParticipantsByChallenge() {
  renderParticipantsTable();
}

function filterParticipantsByProgress() {
  renderParticipantsTable();
}

function exportParticipants() {
  const rows = adminParticipants.map(p => ({
    id: p.id,
    id_challenge: p.id_challenge,
    challenge: p.challenge_titre || '',
    nom: p.nom || '',
    email: p.email || '',
    progression: clampInt(p.objectif, 0, 100),
    objectif_defi: p.challenge_target ?? '',
    engagement: p.engagement ?? '',
    notifications: p.notifications ?? '',
    date_inscription: p.date_inscription ?? ''
  }));

  const header = Object.keys(rows[0] || { id: '' });
  const csv = [
    header.join(','),
    ...rows.map(r => header.map(k => csvEscape(r[k])).join(','))
  ].join('\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'participants.csv';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(url);
}

function viewParticipant(id) {
  const p = adminParticipants.find(x => String(x.id) === String(id));
  if (!p) return;
  alert(`Participant: ${p.nom}\nEmail: ${p.email}\nDéfi: ${p.challenge_titre || p.id_challenge}\nMotivation: ${p.motivation || '-'}\nAction: ${p.action || '-'}`);
}

function deleteParticipant(id, idChallenge) {
  if (!confirm('Voulez-vous vraiment retirer ce participant du défi ?')) return;

  fetch(`challenges/deleteParticipant.php?id=${encodeURIComponent(id)}&id_challenge=${encodeURIComponent(idChallenge)}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(r => r.json())
    .then(result => {
      if (result && result.success) {
        loadAdminParticipants();
      } else {
        alert('❌ Erreur: Impossible de supprimer le participant');
      }
    })
    .catch(err => {
      console.error('Erreur suppression participant:', err);
      alert('❌ Erreur de connexion');
    });
}

function clampInt(value, min, max) {
  const n = parseInt(value, 10);
  if (Number.isNaN(n)) return min;
  return Math.min(max, Math.max(min, n));
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function csvEscape(value) {
  const s = String(value ?? '');
  if (s.includes('"') || s.includes(',') || s.includes('\n') || s.includes('\r')) {
    return `"${s.replace(/"/g, '""')}"`;
  }
  return s;
}
