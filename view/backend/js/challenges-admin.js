/**
 * Module Admin Défis - GaiaLumen Backend
 * Uniquement AJOUTER et AFFICHER les défis
 */

console.log('🏆 Admin Challenges (Mode Ajout/Affichage) chargé');

let adminChallenges = [];
let adminParticipants = [
  {
    id: 1,
    nom: 'Sophie Martin',
    email: 'sophie.martin@email.com',
    challenge_id: 1,
    challenge_titre: 'Défi Zéro Déchet',
    challenge_icon: '♻️',
    progression: 85,
    statut: 'actif',
    date_inscription: '2026-04-01'
  },
  {
    id: 2,
    nom: 'Karim Benali',
    email: 'karim.benali@email.com',
    challenge_id: 2,
    challenge_titre: 'Économie d\'Eau',
    challenge_icon: '💧',
    progression: 62,
    statut: 'actif',
    date_inscription: '2026-04-05'
  },
  {
    id: 3,
    nom: 'Léa Dubois',
    email: 'lea.dubois@email.com',
    challenge_id: 3,
    challenge_titre: 'Végan 30 Jours',
    challenge_icon: '🥗',
    progression: 28,
    statut: 'actif',
    date_inscription: '2026-04-10'
  }
];

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

// Écouter l'événement de chargement du module par le loader
document.addEventListener('adminModuleLoaded', (e) => {
  if (e.detail.moduleName === 'challenges') {
    console.log('📦 Module Challenges détecté, chargement des données...');
    loadAdminChallenges();
    renderParticipantsTable();
  }
});

// Au cas où le script est chargé après le module
if (document.getElementById('challenges')) {
  loadAdminChallenges();
}

// ── Chargement des données (AFFICHER) ────────────────────────
function loadAdminChallenges() {
  fetch('challenges/listChallenges.php')
    .then(response => response.json())
    .then(data => {
      adminChallenges = data;
      renderChallengesTable();
      updateDashboardStats();
    })
    .catch(err => {
      console.error('Erreur lors du chargement des défis admin:', err);
      renderChallengesTable();
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
    window.location.href = `challenges/deleteChallenge.php?id=${id}`;
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
  const statsDefis = document.querySelector('.quick-stats-mini .stat-mini:nth-child(1) .stat-mini-value');
  const statsParticipants = document.querySelector('.quick-stats-mini .stat-mini:nth-child(2) .stat-mini-value');
  
  if (statsDefis) statsDefis.innerText = adminChallenges.length;
  
  if (statsParticipants) {
    const totalParticipants = adminChallenges.reduce((sum, c) => sum + parseInt(c.participants_count || 0), 0);
    statsParticipants.innerText = totalParticipants.toLocaleString();
  }
}

// ═══════════════════════════════════════════════════════════
// RENDU DU TABLEAU PARTICIPANTS
// ═══════════════════════════════════════════════════════════

function renderParticipantsTable() {
  const tbody = document.getElementById('participants-tbody');
  if (!tbody) return;
  
  const filteredParticipants = adminParticipants; // À filtrer plus tard si besoin
  
  if (filteredParticipants.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
          <div style="font-size:3rem;margin-bottom:12px;">👤</div>
          <div>Aucun participant trouvé</div>
        </td>
      </tr>
    `;
    return;
  }
  
  tbody.innerHTML = filteredParticipants.map(p => {
    const avatar = p.nom.split(' ').map(n => n[0]).join('').toUpperCase();
    const progColor = p.progression > 70 ? 'high' : p.progression > 30 ? 'medium' : 'low';
    
    return `
      <tr class="table-row-animated">
        <td>
          <div class="participant-info">
            <div class="participant-avatar">${avatar}</div>
            <div class="participant-details">
              <div class="participant-name">${p.nom}</div>
              <div class="participant-email">${p.email}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 1.5rem;">${p.challenge_icon}</span>
            <span style="font-weight: 600;">${p.challenge_titre}</span>
          </div>
        </td>
        <td>
          <div style="display: flex; align-items: center; gap: 10px;">
            <div class="progress-bar-participant" style="flex:1; height:8px; background:rgba(91,62,150,0.1); border-radius:4px; overflow:hidden;">
              <div class="progress-fill-participant ${progColor}" style="width: ${p.progression}%; height:100%; transition:width 1s ease;"></div>
            </div>
            <span style="font-weight: 700; min-width:35px;">${p.progression}%</span>
          </div>
        </td>
        <td><span class="badge badge-active">Actif</span></td>
        <td>
          <div style="font-weight:600;">${formatDate(p.date_inscription)}</div>
        </td>
        <td>
          <div class="action-btns">
            <button class="btn-icon" onclick="editParticipant(${p.id})" title="Modifier" style="background: rgba(91, 62, 150, 0.2); color: var(--blue);">
              ✏️
            </button>
            <button class="btn-icon btn-danger" onclick="deleteParticipant(${p.id})" title="Supprimer" style="background: rgba(231, 76, 60, 0.15); color: #e74c3c;">
              🗑️
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function editParticipant(id) {
  console.log('Modifier le participant:', id);
  // À implémenter avec un modal ou formulaire
}

function deleteParticipant(id) {
  if (confirm('Voulez-vous vraiment retirer ce participant du défi ?')) {
    console.log('Supprimer le participant:', id);
    // Appel AJAX vers deleteParticipant.php
  }
}
