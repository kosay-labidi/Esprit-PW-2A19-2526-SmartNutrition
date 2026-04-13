/**
 * Module Admin Défis - GaiaLumen Backend
 * Gestion complète des défis collaboratifs côté administration
 */

console.log('🏆 Admin Challenges JS chargé');

// ═══════════════════════════════════════════════════════════
// FONCTION PRINCIPALE MODAL
// ═══════════════════════════════════════════════════════════

function addChallenge() {
  console.log('🔓 addChallenge() appelée');
  const modal = document.getElementById('challenge-form-modal');
  console.log('Modal trouvé:', modal);
  
  if (modal) {
    modal.classList.add('active');
    console.log('✅ Classe "active" ajoutée');
    console.log('Classes actuelles:', modal.className);
    
    // Reset form
    const form = document.getElementById('challenge-form');
    if (form) {
      form.reset();
      console.log('✅ Formulaire réinitialisé');
    }
  } else {
    console.error('❌ Modal non trouvé!');
  }
}

// ═══════════════════════════════════════════════════════════
// DONNÉES SIMULÉES
// ═══════════════════════════════════════════════════════════

let adminChallenges = [
  {
    id: 1,
    titre: '30 Jours Végétarien',
    description: 'Adoptez une alimentation végétarienne pendant 30 jours',
    type: 'collectif',
    objectif: 'repas',
    valeur_cible: 30,
    date_debut: '2026-04-01',
    date_fin: '2026-04-30',
    statut: 'actif',
    participants_count: 342,
    progression: 78,
    steaker: '🥗',
    steaker_nom: 'Salade Verte',
    image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800'
  },
  {
    id: 2,
    titre: 'Zéro Déchet',
    description: 'Réduisez vos déchets de 50% en 7 jours',
    type: 'individuel',
    objectif: 'dechets',
    valeur_cible: 50,
    date_debut: '2026-04-08',
    date_fin: '2026-04-15',
    statut: 'actif',
    participants_count: 189,
    progression: 92,
    steaker: '♻️',
    steaker_nom: 'Recyclage',
    image: 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800'
  },
  {
    id: 3,
    titre: 'Local & Bio',
    description: 'Consommez uniquement des produits locaux pendant 14 jours',
    type: 'collectif',
    objectif: 'co2',
    valeur_cible: 40,
    date_debut: '2026-04-05',
    date_fin: '2026-04-19',
    statut: 'actif',
    participants_count: 267,
    progression: 45,
    steaker: '🌱',
    steaker_nom: 'Feuille Verte',
    image: 'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=800'
  },
  {
    id: 4,
    titre: 'Économie d\'Eau',
    description: 'Réduisez votre consommation d\'eau de 30%',
    type: 'individuel',
    objectif: 'eau',
    valeur_cible: 30,
    date_debut: '2026-03-15',
    date_fin: '2026-04-05',
    statut: 'termine',
    participants_count: 156,
    progression: 100,
    steaker: '💧',
    steaker_nom: 'Goutte d\'Eau',
    image: 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=800'
  },
  {
    id: 5,
    titre: 'Transport Vert',
    description: 'Utilisez uniquement des transports écologiques',
    type: 'collectif',
    objectif: 'transport',
    valeur_cible: 14,
    date_debut: '2026-05-01',
    date_fin: '2026-05-15',
    statut: 'futur',
    participants_count: 0,
    progression: 0,
    steaker: '🚲',
    steaker_nom: 'Vélo Vert',
    image: 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=800'
  }
];

let adminStats = {
  defis_actifs: 28,
  participants_totaux: 1847,
  steakers_gagnes: 342,
  taux_completion: 82,
  bronze: 124,
  argent: 89,
  or: 67,
  double: 62
};

// ═══════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getJoursRestants(dateFin) {
  const fin = new Date(dateFin);
  const maintenant = new Date();
  const diff = Math.ceil((fin - maintenant) / (1000 * 60 * 60 * 24));
  
  if (diff < 0) return 'Terminé';
  if (diff === 0) return 'Aujourd\'hui';
  if (diff === 1) return 'Demain';
  return `Dans ${diff} jours`;
}

function getProgressColor(progression) {
  if (progression < 30) return 'red';
  if (progression < 70) return 'orange';
  return 'green';
}

function getSteakerLevel(progression) {
  if (progression >= 100) return 'double';
  if (progression >= 90) return 'gold';
  if (progression >= 60) return 'silver';
  if (progression >= 30) return 'bronze';
  return 'none';
}

function showToast(title, message, type = 'success') {
  const toast = document.createElement('div');
  const bgColor = type === 'error' ? '#e74c3c' : type === 'info' ? '#3498db' : '#27ae60';
  const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
  
  toast.style.cssText = `
    position: fixed;
    top: 100px;
    right: 30px;
    z-index: 10000;
    background: ${bgColor};
    color: #fff;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    max-width: 400px;
    animation: slideInRight 0.3s ease;
  `;
  
  toast.innerHTML = `
    <div style="font-weight:700;margin-bottom:4px;">${icon} ${title}</div>
    <div style="font-size:0.9rem;opacity:0.9;">${message}</div>
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ═══════════════════════════════════════════════════════════
// RENDU DES DÉFIS
// ═══════════════════════════════════════════════════════════

function renderChallengesTable() {
  const tbody = document.querySelector('#challenges table tbody');
  if (!tbody) return;
  
  const filteredChallenges = filterChallengesList();
  
  if (filteredChallenges.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
          <div style="font-size:3rem;margin-bottom:12px;">🔍</div>
          <div>Aucun défi trouvé</div>
        </td>
      </tr>
    `;
    return;
  }
  
  tbody.innerHTML = filteredChallenges.map(c => {
    const progressColor = getProgressColor(c.progression);
    const badgeClass = c.statut === 'actif' ? 'badge-active' : 
                       c.statut === 'termine' ? 'badge-completed' : 'badge-upcoming';
    const badgeText = c.statut === 'actif' ? 'Actif' : 
                      c.statut === 'termine' ? 'Terminé' : 'À venir';
    
    return `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:12px;">
            <div style="font-size:1.5rem;">${c.steaker}</div>
            <div>
              <div style="font-weight:600;margin-bottom:4px;">${c.titre}</div>
              <div style="font-size:0.8rem;color:var(--muted);">${c.type === 'collectif' ? '👥 Collectif' : '👤 Individuel'}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight:600;">${c.participants_count}</div>
          <div style="font-size:0.8rem;color:var(--muted);">participants</div>
        </td>
        <td>
          <div style="font-weight:600;">-${c.valeur_cible}%</div>
          <div style="font-size:0.8rem;color:var(--muted);">${c.objectif}</div>
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;">
            <div style="flex:1;height:8px;background:rgba(91,62,150,.2);border-radius:4px;overflow:hidden;">
              <div style="height:100%;width:${c.progression}%;background:linear-gradient(90deg,var(--violet),var(--blue));transition:width 1s ease;"></div>
            </div>
            <span style="font-size:.85rem;font-weight:600;color:var(--text);min-width:40px;">${c.progression}%</span>
          </div>
        </td>
        <td><span class="badge ${badgeClass}">${badgeText}</span></td>
        <td>
          <div style="font-weight:600;">${getJoursRestants(c.date_fin)}</div>
          <div style="font-size:0.8rem;color:var(--muted);">${formatDate(c.date_fin)}</div>
        </td>
        <td>
          <div class="action-btns">
            <button class="action-btn action-btn-edit" onclick="viewChallenge(${c.id})">👁️ Voir</button>
            <button class="action-btn action-btn-edit" onclick="editChallenge(${c.id})">✏️ Modifier</button>
            <button class="action-btn action-btn-delete" onclick="deleteChallenge(${c.id})">🗑️ Supprimer</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// FILTRAGE
// ═══════════════════════════════════════════════════════════

let currentFilter = 'all';

function filterChallengesList() {
  const searchInput = document.getElementById('challengeSearchInput');
  const statusFilter = document.getElementById('challengeStatusFilter');
  const typeFilter = document.getElementById('challengeTypeFilter');
  const objectifFilter = document.getElementById('challengeObjectifFilter');
  
  const search = searchInput?.value.toLowerCase() || '';
  const status = statusFilter?.value || '';
  const type = typeFilter?.value || '';
  const objectif = objectifFilter?.value || '';
  
  return adminChallenges.filter(c => {
    // Filtre chip
    if (currentFilter !== 'all') {
      if (currentFilter === 'active' && c.statut !== 'actif') return false;
      if (currentFilter === 'completed' && c.statut !== 'termine') return false;
      if (currentFilter === 'upcoming' && c.statut !== 'futur') return false;
    }
    
    // Filtre recherche
    const matchSearch = !search || 
      c.titre.toLowerCase().includes(search) || 
      c.description.toLowerCase().includes(search);
    
    // Filtres select
    const matchStatus = !status || c.statut === status;
    const matchType = !type || c.type === type;
    const matchObjectif = !objectif || c.objectif === objectif;
    
    return matchSearch && matchStatus && matchType && matchObjectif;
  });
}

function filterChallengesByChip(chip, type) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  chip.classList.add('active');
  currentFilter = type;
  renderChallengesTable();
}

function searchChallenges() {
  renderChallengesTable();
}

function filterChallenges() {
  renderChallengesTable();
}

// ═══════════════════════════════════════════════════════════
// ACTIONS CRUD
// ═══════════════════════════════════════════════════════════

function refreshChallenges() {
  showToast('Actualisation', 'Défis actualisés avec succès', 'success');
  renderChallengesTable();
  updateStats();
}

function addChallenge() {
  const modal = document.getElementById('challenge-form-modal');
  if (modal) {
    modal.classList.add('active');
    document.getElementById('challenge-form').reset();
  }
}

function viewChallenge(id) {
  const challenge = adminChallenges.find(c => c.id === id);
  if (!challenge) return;
  
  const modal = document.getElementById('challenge-detail-modal');
  const body = document.getElementById('challenge-detail-body');
  
  if (!modal || !body) return;
  
  const niveau = getSteakerLevel(challenge.progression);
  const progressColor = getProgressColor(challenge.progression);
  const badgeClass = challenge.statut === 'actif' ? 'badge-active' : 
                     challenge.statut === 'termine' ? 'badge-completed' : 'badge-upcoming';
  const badgeText = challenge.statut === 'actif' ? 'Actif' : 
                    challenge.statut === 'termine' ? 'Terminé' : 'À venir';
  
  body.innerHTML = `
    <div style="padding:32px;">
      <div style="display:flex;align-items:center;gap:24px;margin-bottom:24px;">
        <div class="steaker-3d steaker-${niveau}" style="width:100px;height:100px;">
          <span class="steaker-icon" style="font-size:4rem;">${challenge.steaker}</span>
        </div>
        <div style="flex:1;">
          <h2 style="font-size:2rem;margin-bottom:8px;">${challenge.titre}</h2>
          <span class="badge ${badgeClass}">${badgeText}</span>
          <div style="margin-top:8px;color:var(--muted);">
            ${challenge.type === 'collectif' ? '👥 Défi Collectif' : '👤 Défi Individuel'}
          </div>
        </div>
      </div>
      
      <p style="color:var(--muted);font-size:1.05rem;line-height:1.8;margin-bottom:24px;">
        ${challenge.description}
      </p>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;padding:20px;background:rgba(91,62,150,.1);border-radius:16px;">
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">👥 Participants</div>
          <div style="font-weight:700;font-size:1.5rem;">${challenge.participants_count}</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">🎯 Objectif</div>
          <div style="font-weight:700;font-size:1.5rem;">-${challenge.valeur_cible}%</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📊 Progression</div>
          <div style="font-weight:700;font-size:1.5rem;color:var(--${progressColor})">${challenge.progression}%</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📅 Jours restants</div>
          <div style="font-weight:700;font-size:1.5rem;">${getJoursRestants(challenge.date_fin)}</div>
        </div>
      </div>

      <div style="margin-bottom:24px;">
        <h3 style="margin-bottom:12px;font-size:1.1rem;">📊 Progression globale</h3>
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="flex:1;height:16px;background:rgba(91,62,150,.2);border-radius:8px;overflow:hidden;">
            <div style="height:100%;width:${challenge.progression}%;background:linear-gradient(90deg,var(--violet),var(--blue));transition:width 1s ease;"></div>
          </div>
          <span style="font-weight:700;font-size:1.2rem;">${challenge.progression}%</span>
        </div>
      </div>

      <div style="padding:16px;background:rgba(91,62,150,.08);border-radius:12px;margin-bottom:24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.9rem;">
          <div><strong>Date début:</strong> ${formatDate(challenge.date_debut)}</div>
          <div><strong>Date fin:</strong> ${formatDate(challenge.date_fin)}</div>
          <div><strong>Type:</strong> ${challenge.type === 'collectif' ? 'Collectif' : 'Individuel'}</div>
          <div><strong>Objectif:</strong> ${challenge.objectif}</div>
        </div>
      </div>

      <div style="display:flex;gap:12px;">
        <button onclick="editChallenge(${challenge.id})" class="btn btn-primary" style="flex:1;">
          ✏️ Modifier
        </button>
        <button onclick="deleteChallenge(${challenge.id})" class="btn btn-secondary" style="flex:1;">
          🗑️ Supprimer
        </button>
      </div>
    </div>
  `;
  
  modal.classList.add('active');
}

function editChallenge(id) {
  const challenge = adminChallenges.find(c => c.id === id);
  if (!challenge) return;
  
  closeChallengeDetailModal();
  
  const modal = document.getElementById('challenge-form-modal');
  const form = document.getElementById('challenge-form');
  
  if (!modal || !form) return;
  
  // Remplir le formulaire
  form.titre.value = challenge.titre;
  form.description.value = challenge.description;
  form.type.value = challenge.type;
  form.objectif.value = challenge.objectif;
  form.valeur_cible.value = challenge.valeur_cible;
  form.steaker.value = challenge.steaker;
  form.date_debut.value = challenge.date_debut;
  form.date_fin.value = challenge.date_fin;
  form.image.value = challenge.image || '';
  
  // Changer le titre et le bouton
  modal.querySelector('h2').textContent = '✏️ Modifier le Défi';
  modal.querySelector('button[type="submit"]').textContent = '✅ Enregistrer';
  
  // Stocker l'ID pour la mise à jour
  form.dataset.editId = id;
  
  modal.classList.add('active');
}

async function deleteChallenge(id) {
  const challenge = adminChallenges.find(c => c.id === id);
  if (!challenge) return;
  
  if (confirm(`Êtes-vous sûr de vouloir supprimer le défi "${challenge.titre}" ?\n\nCette action est irréversible.`)) {
    try {
      const response = await fetch(`../../backend/challenges.php?id=${id}`, {
        method: 'DELETE'
      });
      
      if (!response.ok) throw new Error('Erreur lors de la suppression');
      
      adminChallenges = adminChallenges.filter(c => c.id !== id);
      showToast('Suppression', `Défi "${challenge.titre}" supprimé avec succès`, 'success');
      closeChallengeDetailModal();
      renderChallengesTable();
      updateStats();
    } catch (error) {
      console.error('Erreur:', error);
      showToast('Erreur', 'Impossible de supprimer le défi', 'error');
    }
  }
}

async function handleChallengeSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData);
  
  const editId = form.dataset.editId;
  
  try {
    if (editId) {
      // Mise à jour via API
      const response = await fetch('../../backend/challenges.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: parseInt(editId),
          ...data,
          valeur_cible: parseInt(data.valeur_cible)
        })
      });
      
      if (!response.ok) throw new Error('Erreur lors de la modification');
      
      // Mettre à jour localement
      const index = adminChallenges.findIndex(c => c.id === parseInt(editId));
      if (index !== -1) {
        adminChallenges[index] = {
          ...adminChallenges[index],
          ...data,
          valeur_cible: parseInt(data.valeur_cible)
        };
      }
      
      showToast('Modification', `Défi "${data.titre}" modifié avec succès`, 'success');
      delete form.dataset.editId;
    } else {
      // Création via API
      const response = await fetch('../../backend/challenges.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...data,
          valeur_cible: parseInt(data.valeur_cible)
        })
      });
      
      if (!response.ok) throw new Error('Erreur lors de la création');
      
      const newChallenge = await response.json();
      adminChallenges.push(newChallenge);
      
      showToast('Création', `Défi "${data.titre}" créé avec succès`, 'success');
    }
    
    closeChallengeFormModal();
    renderChallengesTable();
    updateStats();
  } catch (error) {
    console.error('Erreur:', error);
    showToast('Erreur', 'Impossible de sauvegarder le défi', 'error');
  }
}

// ═══════════════════════════════════════════════════════════
// MODALS
// ═══════════════════════════════════════════════════════════

function closeChallengeFormModal() {
  const modal = document.getElementById('challenge-form-modal');
  if (modal) {
    modal.classList.remove('active');
    // Reset
    modal.querySelector('h2').textContent = '➕ Nouveau Défi';
    modal.querySelector('button[type="submit"]').textContent = '✅ Créer le défi';
    delete document.getElementById('challenge-form').dataset.editId;
  }
}

function closeChallengeDetailModal() {
  const modal = document.getElementById('challenge-detail-modal');
  if (modal) modal.classList.remove('active');
}

// ═══════════════════════════════════════════════════════════
// STATISTIQUES
// ═══════════════════════════════════════════════════════════

function updateStats() {
  // Calculer les stats à partir des défis
  adminStats.defis_actifs = adminChallenges.filter(c => c.statut === 'actif').length;
  adminStats.participants_totaux = adminChallenges.reduce((sum, c) => sum + c.participants_count, 0);
  
  // Mettre à jour l'affichage (si nécessaire)
  console.log('Stats mises à jour:', adminStats);
}

function exportData(type) {
  const data = JSON.stringify(adminChallenges, null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `challenges-export-${new Date().toISOString().split('T')[0]}.json`;
  a.click();
  URL.revokeObjectURL(url);
  showToast('Export', 'Données exportées avec succès', 'success');
}

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

async function loadChallengesFromAPI() {
  try {
    const response = await fetch('../../backend/challenges.php');
    if (response.ok) {
      const data = await response.json();
      if (Array.isArray(data) && data.length > 0) {
        adminChallenges = data;
        console.log('✅ Défis chargés depuis l\'API:', data.length);
      }
    }
  } catch (error) {
    console.warn('⚠️ Impossible de charger les défis depuis l\'API, utilisation des données locales');
  }
}

async function initChallengesAdmin() {
  console.log('🎯 Initialisation Admin Défis...');
  
  // Charger les défis depuis l'API
  await loadChallengesFromAPI();
  
  // Rendre le tableau
  renderChallengesTable();
  
  // Event listeners
  const searchInput = document.getElementById('challengeSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', searchChallenges);
  }
  
  const statusFilter = document.getElementById('challengeStatusFilter');
  if (statusFilter) {
    statusFilter.addEventListener('change', filterChallenges);
  }
  
  const typeFilter = document.getElementById('challengeTypeFilter');
  if (typeFilter) {
    typeFilter.addEventListener('change', filterChallenges);
  }
  
  const objectifFilter = document.getElementById('challengeObjectifFilter');
  if (objectifFilter) {
    objectifFilter.addEventListener('change', filterChallenges);
  }
  
  const challengeForm = document.getElementById('challenge-form');
  if (challengeForm) {
    challengeForm.addEventListener('submit', handleChallengeSubmit);
  }
  
  // Fermer modals en cliquant sur overlay
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeChallengeFormModal();
        closeChallengeDetailModal();
      }
    });
  });
  
  console.log('✅ Admin Défis initialisé');
}

// Charger au chargement du module
document.addEventListener('DOMContentLoaded', () => {
  const challengesSection = document.getElementById('challenges');
  if (challengesSection) {
    initChallengesAdmin();
  }
});

// Exposer les fonctions globalement
window.refreshChallenges = refreshChallenges;
window.addChallenge = addChallenge;
window.viewChallenge = viewChallenge;
window.editChallenge = editChallenge;
window.deleteChallenge = deleteChallenge;
window.filterChallengesByChip = filterChallengesByChip;
window.searchChallenges = searchChallenges;
window.filterChallenges = filterChallenges;
window.exportData = exportData;
window.closeChallengeFormModal = closeChallengeFormModal;
window.closeChallengeDetailModal = closeChallengeDetailModal;
window.handleChallengeSubmit = handleChallengeSubmit;

console.log('✅ Admin Challenges JS prêt');


// ═══════════════════════════════════════════════════════════
// FONCTIONS FORMULAIRE AMÉLIORÉ
// ═══════════════════════════════════════════════════════════

// Mise à jour du compteur de caractères
function updateCharCount(textareaId, counterId, maxLength) {
  const textarea = document.getElementById(textareaId);
  const counter = document.getElementById(counterId);
  
  if (textarea && counter) {
    const length = textarea.value.length;
    counter.textContent = `${length}/${maxLength}`;
    
    if (length >= maxLength) {
      counter.style.color = 'var(--admin-danger)';
    } else if (length >= maxLength * 0.8) {
      counter.style.color = 'var(--admin-warning)';
    } else {
      counter.style.color = 'var(--muted)';
    }
  }
}

// Synchroniser slider et input
function updateSlider(value) {
  const slider = document.getElementById('valeur-slider');
  if (slider) slider.value = value;
}

function updateValueInput(value) {
  const input = document.getElementById('challenge-valeur');
  if (input) input.value = value;
}

// Informations sur le type de défi
function updateTypeInfo() {
  const typeSelect = document.getElementById('challenge-type');
  const typeInfo = document.getElementById('type-info');
  
  if (!typeSelect || !typeInfo) return;
  
  const infos = {
    'collectif': '👥 Tous les participants travaillent ensemble vers un objectif commun',
    'individuel': '👤 Chaque participant progresse individuellement avec son propre objectif'
  };
  
  typeInfo.textContent = infos[typeSelect.value] || '';
  typeInfo.style.color = 'var(--admin-info)';
}

// Informations sur l'objectif
function updateObjectifInfo() {
  const objectifSelect = document.getElementById('challenge-objectif');
  const objectifInfo = document.getElementById('objectif-info');
  
  if (!objectifSelect || !objectifInfo) return;
  
  const infos = {
    'dechets': '♻️ Réduction des déchets alimentaires et ménagers',
    'eau': '💧 Économie de la consommation d\'eau quotidienne',
    'repas': '🥗 Alimentation durable et végétale',
    'transport': '🚲 Mobilité douce et transports écologiques',
    'energie': '⚡ Réduction de la consommation énergétique',
    'co2': '🌍 Diminution de l\'empreinte carbone'
  };
  
  objectifInfo.textContent = infos[objectifSelect.value] || '';
  objectifInfo.style.color = 'var(--admin-info)';
}

// Calculer et afficher la date de fin
function updateDateFin() {
  const dateDebutInput = document.getElementById('challenge-date-debut');
  const dateFinInput = document.getElementById('challenge-date-fin');
  const dureeSelect = document.getElementById('challenge-duree');
  const datePreview = document.getElementById('date-preview');
  const datePreviewText = document.getElementById('date-preview-text');
  
  if (!dateDebutInput || !dateFinInput || !dureeSelect) return;
  
  const dateDebut = new Date(dateDebutInput.value);
  const duree = parseInt(dureeSelect.value);
  
  if (dateDebut && duree && duree !== 'custom') {
    const dateFin = new Date(dateDebut);
    dateFin.setDate(dateFin.getDate() + duree);
    
    // Formater la date pour l'input
    const dateFinStr = dateFin.toISOString().split('T')[0];
    dateFinInput.value = dateFinStr;
    
    // Afficher l'aperçu
    if (datePreview && datePreviewText) {
      datePreview.style.display = 'flex';
      const options = { day: 'numeric', month: 'long', year: 'numeric' };
      const debutFormatted = dateDebut.toLocaleDateString('fr-FR', options);
      const finFormatted = dateFin.toLocaleDateString('fr-FR', options);
      datePreviewText.textContent = `Du ${debutFormatted} au ${finFormatted} (${duree} jours)`;
    }
  } else if (duree === 'custom') {
    // Mode personnalisé - calculer la durée
    if (dateDebut && dateFinInput.value) {
      const dateFin = new Date(dateFinInput.value);
      const diffTime = Math.abs(dateFin - dateDebut);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (datePreview && datePreviewText) {
        datePreview.style.display = 'flex';
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        const debutFormatted = dateDebut.toLocaleDateString('fr-FR', options);
        const finFormatted = dateFin.toLocaleDateString('fr-FR', options);
        datePreviewText.textContent = `Du ${debutFormatted} au ${finFormatted} (${diffDays} jours)`;
      }
    }
  }
}

// Prévisualiser l'image
function previewImage(url) {
  const preview = document.getElementById('image-preview');
  const img = document.getElementById('preview-img');
  
  if (!preview || !img) return;
  
  if (url && url.trim() !== '') {
    img.src = url;
    img.onerror = () => {
      preview.style.display = 'none';
      showToast('Erreur', 'Impossible de charger l\'image', 'error');
    };
    img.onload = () => {
      preview.style.display = 'block';
    };
  } else {
    preview.style.display = 'none';
  }
}

// Prévisualiser le défi avant création
function previewChallenge() {
  const form = document.getElementById('challenge-form');
  const formData = new FormData(form);
  const data = Object.fromEntries(formData);
  
  // Validation basique
  if (!data.titre || !data.description) {
    showToast('Validation', 'Veuillez remplir les champs obligatoires', 'error');
    return;
  }
  
  // Créer un aperçu
  const modal = document.getElementById('challenge-detail-modal');
  const body = document.getElementById('challenge-detail-body');
  
  if (!modal || !body) return;
  
  const progressColor = 'green';
  
  body.innerHTML = `
    <div style="padding:32px;">
      <div style="background:rgba(52,152,219,.1);padding:16px;border-radius:12px;margin-bottom:24px;border:2px dashed var(--admin-info);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
          <span style="font-size:1.5rem;">👁️</span>
          <strong style="color:var(--admin-info);">MODE APERÇU</strong>
        </div>
        <div style="font-size:0.9rem;color:var(--muted);">
          Ceci est un aperçu de votre défi. Les données ne sont pas encore enregistrées.
        </div>
      </div>
      
      <div style="display:flex;align-items:center;gap:24px;margin-bottom:24px;">
        <div class="steaker-3d steaker-gold" style="width:100px;height:100px;">
          <span class="steaker-icon" style="font-size:4rem;">${data.steaker || '🌱'}</span>
        </div>
        <div style="flex:1;">
          <h2 style="font-size:2rem;margin-bottom:8px;">${data.titre}</h2>
          <span class="badge badge-upcoming">Aperçu</span>
          <div style="margin-top:8px;color:var(--muted);">
            ${data.type === 'collectif' ? '👥 Défi Collectif' : '👤 Défi Individuel'}
          </div>
        </div>
      </div>
      
      ${data.image ? `<img src="${data.image}" style="width:100%;height:250px;object-fit:cover;border-radius:16px;margin-bottom:24px;" onerror="this.style.display='none'">` : ''}
      
      <p style="color:var(--muted);font-size:1.05rem;line-height:1.8;margin-bottom:24px;">
        ${data.description}
      </p>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;padding:20px;background:rgba(91,62,150,.1);border-radius:16px;">
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">🎯 Objectif</div>
          <div style="font-weight:700;font-size:1.5rem;">-${data.valeur_cible}%</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📅 Durée</div>
          <div style="font-weight:700;font-size:1.5rem;">${data.duree || '30'} jours</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📋 Catégorie</div>
          <div style="font-weight:700;font-size:1.5rem;">${data.objectif || '...'}</div>
        </div>
      </div>

      <div style="padding:16px;background:rgba(91,62,150,.08);border-radius:12px;margin-bottom:24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.9rem;">
          <div><strong>Date début:</strong> ${data.date_debut ? new Date(data.date_debut).toLocaleDateString('fr-FR') : 'Non définie'}</div>
          <div><strong>Date fin:</strong> ${data.date_fin ? new Date(data.date_fin).toLocaleDateString('fr-FR') : 'Non définie'}</div>
          <div><strong>Notifications:</strong> ${data.notifications ? '✅ Activées' : '❌ Désactivées'}</div>
          <div><strong>Classement:</strong> ${data.classement_public ? '✅ Public' : '❌ Privé'}</div>
        </div>
      </div>

      <div style="display:flex;gap:12px;">
        <button onclick="closeChallengeDetailModal()" class="btn btn-secondary" style="flex:1;">
          ← Retour au formulaire
        </button>
        <button onclick="closeChallengeDetailModal(); document.getElementById('challenge-form').requestSubmit();" class="btn btn-primary" style="flex:1;">
          ✅ Confirmer et créer
        </button>
      </div>
    </div>
  `;
  
  closeChallengeFormModal();
  modal.classList.add('active');
}

// Réinitialiser le formulaire
function resetChallengeForm() {
  const form = document.getElementById('challenge-form');
  if (form) {
    form.reset();
    
    // Réinitialiser les éléments personnalisés
    const datePreview = document.getElementById('date-preview');
    if (datePreview) datePreview.style.display = 'none';
    
    const imagePreview = document.getElementById('image-preview');
    if (imagePreview) imagePreview.style.display = 'none';
    
    const descriptionCount = document.getElementById('description-count');
    if (descriptionCount) descriptionCount.textContent = '0/500';
    
    const typeInfo = document.getElementById('type-info');
    if (typeInfo) typeInfo.textContent = '';
    
    const objectifInfo = document.getElementById('objectif-info');
    if (objectifInfo) objectifInfo.textContent = '';
    
    // Réinitialiser le slider
    updateSlider(50);
    
    // Supprimer l'ID d'édition
    delete form.dataset.editId;
  }
}

// Ouvrir le formulaire en mode création
function openCreateForm() {
  const modal = document.getElementById('challenge-form-modal');
  const title = document.getElementById('form-modal-title');
  const subtitle = modal.querySelector('.modal-subtitle');
  const submitBtn = document.getElementById('form-submit-btn');
  
  if (modal) {
    resetChallengeForm();
    
    if (title) title.textContent = '➕ Nouveau Défi';
    if (subtitle) subtitle.textContent = 'Créez un nouveau défi collaboratif pour vos utilisateurs';
    if (submitBtn) submitBtn.textContent = '✅ Créer le défi';
    
    // Définir la date de début par défaut (aujourd'hui)
    const dateDebutInput = document.getElementById('challenge-date-debut');
    if (dateDebutInput) {
      const today = new Date().toISOString().split('T')[0];
      dateDebutInput.value = today;
      dateDebutInput.min = today;
      updateDateFin();
    }
    
    modal.classList.add('active');
  }
}

// Ouvrir le formulaire en mode édition
function openEditForm(challengeId) {
  const challenge = adminChallenges.find(c => c.id === challengeId);
  if (!challenge) return;
  
  const modal = document.getElementById('challenge-form-modal');
  const title = document.getElementById('form-modal-title');
  const subtitle = modal.querySelector('.modal-subtitle');
  const submitBtn = document.getElementById('form-submit-btn');
  const form = document.getElementById('challenge-form');
  
  if (!modal || !form) return;
  
  // Changer les textes
  if (title) title.textContent = '✏️ Modifier le Défi';
  if (subtitle) subtitle.textContent = `Modifiez les informations du défi "${challenge.titre}"`;
  if (submitBtn) submitBtn.textContent = '✅ Enregistrer les modifications';
  
  // Remplir le formulaire
  form.titre.value = challenge.titre;
  form.description.value = challenge.description;
  form.type.value = challenge.type;
  form.objectif.value = challenge.objectif;
  form.valeur_cible.value = challenge.valeur_cible;
  form.date_debut.value = challenge.date_debut;
  form.date_fin.value = challenge.date_fin;
  form.image.value = challenge.image || '';
  
  // Sélectionner le steaker
  const steakerRadio = form.querySelector(`input[name="steaker"][value="${challenge.steaker}"]`);
  if (steakerRadio) steakerRadio.checked = true;
  
  // Calculer la durée
  const dateDebut = new Date(challenge.date_debut);
  const dateFin = new Date(challenge.date_fin);
  const duree = Math.ceil((dateFin - dateDebut) / (1000 * 60 * 60 * 24));
  
  const dureeSelect = form.duree;
  const dureeOptions = [7, 14, 21, 30, 60, 90];
  if (dureeOptions.includes(duree)) {
    dureeSelect.value = duree;
  } else {
    dureeSelect.value = 'custom';
  }
  
  // Mettre à jour les compteurs et aperçus
  updateCharCount('challenge-description', 'description-count', 500);
  updateSlider(challenge.valeur_cible);
  updateTypeInfo();
  updateObjectifInfo();
  updateDateFin();
  
  if (challenge.image) {
    previewImage(challenge.image);
  }
  
  // Stocker l'ID pour la mise à jour
  form.dataset.editId = challengeId;
  
  modal.classList.add('active');
}

// Surcharger les fonctions existantes
const originalAddChallenge = window.addChallenge;
window.addChallenge = function() {
  openCreateForm();
};

const originalEditChallenge = window.editChallenge;
window.editChallenge = function(id) {
  closeChallengeDetailModal();
  openEditForm(id);
};

// Exposer les nouvelles fonctions
window.updateCharCount = updateCharCount;
window.updateSlider = updateSlider;
window.updateValueInput = updateValueInput;
window.updateTypeInfo = updateTypeInfo;
window.updateObjectifInfo = updateObjectifInfo;
window.updateDateFin = updateDateFin;
window.previewImage = previewImage;
window.previewChallenge = previewChallenge;
window.resetChallengeForm = resetChallengeForm;
window.openCreateForm = openCreateForm;
window.openEditForm = openEditForm;

console.log('✅ Formulaire amélioré chargé');
