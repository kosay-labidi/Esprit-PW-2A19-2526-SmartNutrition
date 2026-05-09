/**
 * Module Défis Collaboratifs Complet - GaiaLumen
 * Gestion complète des défis avec steakers 3D, classement, formulaire, etc.
 */

console.log('🏆 Challenges Complete JS chargé');

// ═══════════════════════════════════════════════════════════
// DONNÉES
// ═══════════════════════════════════════════════════════════

let allChallenges = [];
let allParticipants = [];
let currentUser = {
  id: 1,
  nom: 'Utilisateur Test',
  pseudo: 'user_test',
  email: 'test@gaialumen.com',
  avatar: '👤'
};

// Données d'exemple des défis
const sampleChallenges = [
  {
    id: 1,
    titre: 'Défi Zéro Déchet',
    description: 'Réduisez vos déchets alimentaires de 50% en 30 jours',
    type: 'collectif',
    objectif: 'dechets',
    valeur_cible: 50,
    date_debut: '2026-04-01',
    date_fin: '2026-04-30',
    statut: 'actif',
    participants_count: 142,
    progression: 65,
    image: 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800',
    steaker: '🌱',
    steaker_nom: 'Feuille Verte',
    validation_mode: 'auto'
  },
  {
    id: 2,
    titre: 'Économie d\'Eau',
    description: 'Réduisez votre consommation d\'eau de 30% pendant 21 jours',
    type: 'individuel',
    objectif: 'eau',
    valeur_cible: 30,
    date_debut: '2026-04-10',
    date_fin: '2026-05-01',
    statut: 'actif',
    participants_count: 89,
    progression: 42,
    image: 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=800',
    steaker: '💧',
    steaker_nom: 'Goutte d\'Eau',
    validation_mode: 'manuel'
  },
  {
    id: 3,
    titre: 'Végan 30 Jours',
    description: 'Adoptez une alimentation 100% végétale pendant un mois',
    type: 'collectif',
    objectif: 'repas',
    valeur_cible: 90,
    date_debut: '2026-03-01',
    date_fin: '2026-03-31',
    statut: 'termine',
    participants_count: 234,
    progression: 100,
    image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800',
    steaker: '🥗',
    steaker_nom: 'Salade Verte',
    validation_mode: 'auto'
  },
  {
    id: 4,
    titre: 'Transport Vert',
    description: 'Utilisez uniquement des transports écologiques pendant 2 semaines',
    type: 'individuel',
    objectif: 'transport',
    valeur_cible: 14,
    date_debut: '2026-04-15',
    date_fin: '2026-04-29',
    statut: 'actif',
    participants_count: 67,
    progression: 28,
    image: 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=800',
    steaker: '🚲',
    steaker_nom: 'Vélo Vert',
    validation_mode: 'auto'
  },
  {
    id: 5,
    titre: 'Compostage Maison',
    description: 'Compostez 100% de vos déchets organiques pendant 60 jours',
    type: 'collectif',
    objectif: 'compost',
    valeur_cible: 100,
    date_debut: '2026-05-01',
    date_fin: '2026-06-30',
    statut: 'futur',
    participants_count: 45,
    progression: 0,
    image: 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800',
    steaker: '♻️',
    steaker_nom: 'Recyclage',
    validation_mode: 'manuel'
  },
  {
    id: 6,
    titre: 'Énergie Renouvelable',
    description: 'Réduisez votre consommation électrique de 40% en 45 jours',
    type: 'individuel',
    objectif: 'energie',
    valeur_cible: 40,
    date_debut: '2026-04-05',
    date_fin: '2026-05-20',
    statut: 'actif',
    participants_count: 112,
    progression: 55,
    image: 'https://images.unsplash.com/photo-1509391366360-2e959784a276?w=800',
    steaker: '⚡',
    steaker_nom: 'Éclair',
    validation_mode: 'auto'
  }
];

// Données classement
const sampleParticipants = [
  { id: 1, nom: 'Marie Dupont', pseudo: 'marie_eco', avatar: '👩', progression: 95, points: 1250, steaker_niveau: 'double' },
  { id: 2, nom: 'Jean Martin', pseudo: 'jean_green', avatar: '👨', progression: 85, points: 1120, steaker_niveau: 'gold' },
  { id: 3, nom: 'Sophie Bernard', pseudo: 'sophie_nature', avatar: '👩', progression: 75, points: 980, steaker_niveau: 'gold' },
  { id: 4, nom: 'Pierre Dubois', pseudo: 'pierre_eco', avatar: '👨', progression: 68, points: 850, steaker_niveau: 'orange' },
  { id: 5, nom: 'Emma Petit', pseudo: 'emma_green', avatar: '👩', progression: 62, points: 790, steaker_niveau: 'silver' },
  { id: 6, nom: 'Lucas Moreau', pseudo: 'lucas_nature', avatar: '👨', progression: 58, points: 720, steaker_niveau: 'silver' },
  { id: 7, nom: 'Chloé Laurent', pseudo: 'chloe_eco', avatar: '👩', progression: 52, points: 650, steaker_niveau: 'orange' },
  { id: 8, nom: 'Thomas Simon', pseudo: 'thomas_green', avatar: '👨', progression: 48, points: 580, steaker_niveau: 'orange' },
  { id: 9, nom: 'Léa Michel', pseudo: 'lea_nature', avatar: '👩', progression: 42, points: 510, steaker_niveau: 'orange' },
  { id: 10, nom: 'Hugo Lefebvre', pseudo: 'hugo_eco', avatar: '👨', progression: 38, points: 440, steaker_niveau: 'bronze' },
  { id: 11, nom: 'Camille Roux', pseudo: 'camille_green', avatar: '👩', progression: 35, points: 370, steaker_niveau: 'bronze' },
  { id: 12, nom: 'Nathan Garnier', pseudo: 'nathan_nature', avatar: '👨', progression: 30, points: 300, steaker_niveau: 'bronze' },
  { id: 13, nom: 'Manon Rousseau', pseudo: 'manon_eco', avatar: '👩', progression: 28, points: 250, steaker_niveau: 'bronze' },
  { id: 14, nom: 'Alexandre Blanc', pseudo: 'alex_green', avatar: '👨', progression: 25, points: 200, steaker_niveau: 'bronze' },
  { id: 15, nom: 'Julie Girard', pseudo: 'julie_nature', avatar: '👩', progression: 22, points: 150, steaker_niveau: 'bronze' }
];

// ═══════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════

function getSteakerLevel(progression) {
  if (progression >= 100) return 'double';
  if (progression >= 90) return 'gold';
  if (progression >= 60) return 'silver';
  if (progression >= 30) return 'bronze';
  return 'none';
}

function getSteakerClass(niveau) {
  const classes = {
    'double': 'steaker-double',
    'gold': 'steaker-gold',
    'silver': 'steaker-silver',
    'bronze': 'steaker-bronze',
    'none': 'steaker-locked'
  };
  return classes[niveau] || 'steaker-locked';
}

function getProgressColor(progression) {
  if (progression < 30) return 'red';
  if (progression < 70) return 'orange';
  return 'green';
}

function createSteakerHTML(icon, niveau, size = 'medium') {
  const steakerClass = getSteakerClass(niveau);
  
  if (niveau === 'double') {
    return `
      <div class="steaker-3d ${steakerClass} steaker-${size}">
        <div class="steaker-icon-main">${icon}</div>
        <div class="steaker-icon-orbit">${icon}</div>
        <div class="burst"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
      </div>
    `;
  }
  
  const particles = niveau === 'gold' ? 
    '<div class="particle"></div>'.repeat(4) : '';
  
  return `
    <div class="steaker-3d ${steakerClass} steaker-${size}">
      <div class="steaker-icon">${icon}</div>
      ${particles}
    </div>
  `;
}

// ═══════════════════════════════════════════════════════════
// CHARGEMENT DES DÉFIS
// ═══════════════════════════════════════════════════════════

function loadChallenges() {
  const grid = document.getElementById('challenges-grid');
  const loading = document.getElementById('challenges-loading');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !loading || !empty) return;
  
  loading.style.display = 'block';
  grid.style.display = 'none';
  empty.style.display = 'none';
  
  setTimeout(() => {
    allChallenges = sampleChallenges;
    allParticipants = sampleParticipants;
    filterChallenges();
    renderRanking();
    renderMyRank();
    loading.style.display = 'none';
  }, 800);
}

function filterChallenges() {
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const grid = document.getElementById('challenges-grid');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !empty) return;
  
  const search = searchInput?.value.toLowerCase() || '';
  const status = statusFilter?.value || '';
  
  const filtered = allChallenges.filter(c => {
    const matchSearch = c.titre.toLowerCase().includes(search) || 
                       c.description.toLowerCase().includes(search);
    const matchStatus = !status || c.statut === status;
    return matchSearch && matchStatus;
  });

  if (filtered.length === 0) {
    grid.style.display = 'none';
    empty.style.display = 'block';
  } else {
    empty.style.display = 'none';
    grid.style.display = 'grid';
    renderChallenges(filtered);
  }
}

function renderChallenges(challenges) {
  const grid = document.getElementById('challenges-grid');
  if (!grid) return;
  
  grid.innerHTML = challenges.map(c => {
    const dateDebut = new Date(c.date_debut);
    const dateFin = new Date(c.date_fin);
    const niveau = getSteakerLevel(c.progression);
    const progressColor = getProgressColor(c.progression);
    
    return `
      <div class="challenge-card" onclick="showChallengeDetail(${c.id})">
        ${c.image ? `<img src="${c.image}" alt="${c.titre}" class="challenge-image">` : ''}
        
        <div class="challenge-steaker">
          ${createSteakerHTML(c.steaker, niveau, 'small')}
        </div>
        
        <div class="challenge-badge ${c.statut}">
          ${c.statut === 'actif' ? '✅ Actif' : c.statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
        </div>
        
        <div class="challenge-content">
          <h3 class="challenge-title">${c.titre}</h3>
          <p class="challenge-description">${c.description}</p>
          
          <!-- Récompense 3D -->
          <div class="challenge-reward">
            <div class="reward-icon">
              ${createSteakerHTML(c.steaker, 'gold', 'small')}
            </div>
            <div class="reward-info">
              <div class="reward-label">Récompense à gagner</div>
              <div class="reward-name">Steaker 3D: ${c.steaker_nom}</div>
            </div>
          </div>

          <div class="challenge-stats">
            <div class="challenge-stat">
              <span>👥</span>
              <span>${c.participants_count} participants</span>
            </div>
            <div class="challenge-stat">
              <span>📅</span>
              <span>${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})} - ${dateFin.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})}</span>
            </div>
          </div>
          
          <div class="progress-wrapper">
            <div class="progress-header">
              <span class="progress-label">Progression</span>
              <span class="progress-value" style="color: var(--progress-${progressColor})">${c.progression}%</span>
            </div>
            <div class="progress-bar-container" data-progress="${c.progression}">
              <div class="progress-bar-fill ${progressColor}" style="width: ${c.progression}%"></div>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// CLASSEMENT
// ═══════════════════════════════════════════════════════════

let currentRankingType = 'global';

function renderMyRank() {
  const myRankCard = document.getElementById('my-ranking-card');
  if (!myRankCard) return;
  
  const sorted = [...allParticipants].sort((a, b) => b.progression - a.progression);
  const myIndex = sorted.findIndex(p => p.id === currentUser.id);
  const myData = sorted[myIndex];
  
  if (!myData) return;
  
  const rang = myIndex + 1;
  const niveau = getSteakerLevel(myData.progression);
  const progressColor = getProgressColor(myData.progression);
  
  myRankCard.innerHTML = `
    <div class="my-ranking-rank">#${rang}</div>
    <div class="my-ranking-details">
      <div class="my-ranking-name">${myData.avatar} ${myData.pseudo}</div>
      <div class="my-ranking-stats">
        <span>${myData.progression}%</span> • <span>${myData.points} pts</span>
      </div>
      <div class="ranking-progress-bar">
        <div class="ranking-progress-fill ${progressColor}" style="width: ${myData.progression}%"></div>
      </div>
    </div>
    <div class="ranking-steaker">
      ${createSteakerHTML('🌱', niveau, 'small')}
    </div>
  `;
}

function renderRanking(type = 'global') {
  const rankingList = document.getElementById('ranking-list');
  if (!rankingList) return;
  
  currentRankingType = type;
  
  // Simulation de filtrage par type
  let participants = [...allParticipants];
  if (type === 'friends') {
    participants = participants.filter(p => p.id % 2 === 0 || p.id === currentUser.id);
  }
  
  // Trier par progression
  const sorted = participants.sort((a, b) => b.progression - a.progression);
  
  rankingList.innerHTML = sorted.map((p, index) => {
    const rang = index + 1;
    const isTop3 = rang <= 3;
    const isCurrentUser = p.id === currentUser.id;
    const niveau = getSteakerLevel(p.progression);
    const progressColor = getProgressColor(p.progression);
    
    return `
      <div class="ranking-item ${isCurrentUser ? 'current-user' : ''}" onclick="showUserProfile(${p.id})">
        <div class="ranking-position ${isTop3 ? 'top3' : ''}">
          ${rang}
        </div>
        
        <div class="ranking-info">
          <div class="ranking-name">${p.avatar} ${p.pseudo}</div>
          <div class="ranking-stats">
            <span>${p.progression}%</span> • <span>${p.points} pts</span>
          </div>
          <div class="ranking-progress-bar">
            <div class="ranking-progress-fill ${progressColor}" style="width: ${p.progression}%"></div>
          </div>
        </div>
        
        <div class="ranking-steaker">
          ${createSteakerHTML('🌱', niveau, 'small')}
        </div>
      </div>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// MODAL DÉTAIL DÉFI
// ═══════════════════════════════════════════════════════════

function showChallengeDetail(challengeId) {
  const challenge = allChallenges.find(c => c.id === challengeId);
  const modal = document.getElementById('challenge-modal');
  const modalBody = document.getElementById('challenge-modal-body');
  
  if (!challenge || !modal || !modalBody) return;

  const dateDebut = new Date(challenge.date_debut);
  const dateFin = new Date(challenge.date_fin);
  const niveau = getSteakerLevel(challenge.progression);
  const progressColor = getProgressColor(challenge.progression);
  const isActif = challenge.statut === 'actif';
  const isTermine = challenge.statut === 'termine';
  const joursRestants = Math.ceil((dateFin - new Date()) / (1000 * 60 * 60 * 24));

  modalBody.innerHTML = `
    ${challenge.image ? `<img src="${challenge.image}" alt="${challenge.titre}" style="width:100%;height:300px;object-fit:cover;border-radius:24px 24px 0 0;">` : ''}
    
    <div style="padding:32px;">
      <div style="display:flex;align-items:center;gap:24px;margin-bottom:24px;">
        ${createSteakerHTML(challenge.steaker, niveau, 'large')}
        
        <div style="flex:1;">
          <h2 style="font-size:2rem;margin-bottom:8px;">${challenge.titre}</h2>
          <div class="challenge-badge ${challenge.statut}" style="position:static;display:inline-block;">
            ${challenge.statut === 'actif' ? '✅ Actif' : challenge.statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
          </div>
        </div>
      </div>
      
      <p style="color:var(--muted);font-size:1.05rem;line-height:1.8;margin-bottom:24px;">${challenge.description}</p>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px;padding:20px;background:rgba(91,62,150,.1);border-radius:16px;">
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">👥 Participants</div>
          <div style="font-weight:700;font-size:1.5rem;">${challenge.participants_count}</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">🎯 Objectif</div>
          <div style="font-weight:700;font-size:1.5rem;">${challenge.valeur_cible}%</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📊 Progression</div>
          <div style="font-weight:700;font-size:1.5rem;color:var(--progress-${progressColor})">${challenge.progression}%</div>
        </div>
        <div style="text-align:center;">
          <div style="font-size:0.8rem;color:var(--muted);margin-bottom:4px;">📅 ${isActif ? 'Jours restants' : 'Durée'}</div>
          <div style="font-weight:700;font-size:1.5rem;">${isActif ? joursRestants : Math.ceil((dateFin - dateDebut) / (1000 * 60 * 60 * 24))}j</div>
        </div>
      </div>

      <div class="progress-wrapper" style="margin-bottom:24px;">
        <div class="progress-header">
          <span class="progress-label">Progression globale</span>
          <span class="progress-value" style="color:var(--progress-${progressColor})">${challenge.progression}%</span>
        </div>
        <div class="progress-bar-container" data-progress="${challenge.progression}" style="height:16px;">
          <div class="progress-bar-fill ${progressColor}" style="width:${challenge.progression}%"></div>
        </div>
      </div>

      ${isActif ? `
        <button onclick="openParticipationForm(${challenge.id})" class="btn-primary" style="width:100%;padding:16px;font-size:1.1rem;">
          ✅ Participer à ce défi
        </button>
      ` : isTermine ? `
        <div style="padding:16px;background:rgba(149,165,166,.1);border:2px solid rgba(149,165,166,.3);border-radius:12px;text-align:center;color:var(--muted);">
          📦 Ce défi est terminé
        </div>
      ` : `
        <div style="padding:16px;background:rgba(52,152,219,.1);border:2px solid rgba(52,152,219,.3);border-radius:12px;text-align:center;color:#3498db;">
          🔜 Ce défi commence le ${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})}
        </div>
      `}
    </div>
  `;

  modal.classList.add('active');
}

function closeChallengeModal() {
  const modal = document.getElementById('challenge-modal');
  if (modal) modal.classList.remove('active');
}

// ═══════════════════════════════════════════════════════════
// FORMULAIRE DE PARTICIPATION
// ═══════════════════════════════════════════════════════════

let currentChallengeId = null;

function openParticipationForm(challengeId) {
  currentChallengeId = challengeId;
  const challenge = allChallenges.find(c => c.id === challengeId);
  const modal = document.getElementById('participation-modal');
  const title = document.getElementById('modal-challenge-title');
  
  if (!challenge || !modal || !title) return;
  
  title.textContent = `Défi: ${challenge.titre}`;
  closeChallengeModal();
  modal.classList.add('active');
  
  // Reset form
  document.getElementById('participation-form').reset();
  updateCharCount();
}

function closeParticipationModal() {
  const modal = document.getElementById('participation-modal');
  if (modal) modal.classList.remove('active');
  currentChallengeId = null;
}

function updateCharCount() {
  const textarea = document.getElementById('participant-objective');
  const charCount = document.querySelector('.char-count');
  if (textarea && charCount) {
    const length = textarea.value.length;
    charCount.textContent = `${length} / 50`;
    charCount.style.color = length >= 50 ? 'var(--success)' : 'var(--muted)';
  }
}

function handleParticipationSubmit(e) {
  e.preventDefault();
  
  const challenge = allChallenges.find(c => c.id === currentChallengeId);
  if (!challenge) return;
  
  const formData = {
    nom: document.getElementById('participant-name').value,
    objectif: document.getElementById('participant-objective').value,
    engagement: document.getElementById('participant-engagement').value,
    accept: document.getElementById('participant-accept').checked
  };
  
  // Validation
  if (formData.objectif.length < 50) {
    showToast('❌ L\'objectif doit contenir au moins 50 caractères', 'error');
    return;
  }
  
  if (!formData.accept) {
    showToast('❌ Vous devez accepter les conditions', 'error');
    return;
  }
  
  // Simuler l'inscription
  challenge.participants_count++;
  
  closeParticipationModal();
  
  showToast(`✅ Participation confirmée au défi "${challenge.titre}"!`, 'success');
  
  // Recharger les défis
  filterChallenges();
}

// ═══════════════════════════════════════════════════════════
// TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════════════════

function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.style.cssText = `
    position: fixed;
    top: 100px;
    right: 30px;
    z-index: 10000;
    background: ${type === 'success' ? 'linear-gradient(135deg, var(--violet), var(--blue))' : 'linear-gradient(135deg, #e74c3c, #c0392b)'};
    color: #fff;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
    max-width: 400px;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ═══════════════════════════════════════════════════════════
// NAVIGATION
// ═══════════════════════════════════════════════════════════

function showMyChallenge() {
  showToast('📊 Page "Mon Défi" en cours de développement...', 'success');
  // TODO: Implémenter la page Mon Défi
}

function showCollection() {
  showToast('🏆 Page "Collection" en cours de développement...', 'success');
  // TODO: Implémenter la page Collection
}

function showProfile() {
  showToast('👤 Page "Profil" en cours de développement...', 'success');
  // TODO: Implémenter la page Profil
}

function showUserProfile(userId) {
  const user = allParticipants.find(p => p.id === userId);
  const modal = document.getElementById('user-profile-modal');
  const modalBody = document.getElementById('user-profile-body');
  
  if (!user || !modal || !modalBody) return;

  // Simulation de collection de steakers pour l'utilisateur
  const userSteakers = ['🌱', '💧', '🥗', '🚲', '⚡', '♻️'].slice(0, Math.floor(user.progression / 15) + 1);

  modalBody.innerHTML = `
    <div class="profile-header">
      <div class="profile-avatar">${user.avatar}</div>
      <h2 class="profile-name">${user.nom}</h2>
      <div class="profile-pseudo">@${user.pseudo}</div>
    </div>
    
    <div class="profile-stats-grid">
      <div class="profile-stat-card">
        <div class="profile-stat-value">${user.progression}%</div>
        <div class="profile-stat-label">Progression</div>
      </div>
      <div class="profile-stat-card">
        <div class="profile-stat-value">${user.points}</div>
        <div class="profile-stat-label">Points</div>
      </div>
    </div>
    
    <div class="profile-collection">
      <h3 class="collection-title">🏆 Collection de Steakers</h3>
      <div class="collection-grid">
        ${userSteakers.map(s => `
          <div class="collection-item" title="Steaker débloqué">
            ${s}
          </div>
        `).join('')}
        ${Array(6 - userSteakers.length).fill(0).map(() => `
          <div class="collection-item" style="opacity: 0.2; filter: grayscale(1)" title="Verrouillé">
            ❓
          </div>
        `).join('')}
      </div>
    </div>

    <div style="padding: 24px; text-align: center;">
      <button class="btn-primary" style="width: 100%;" onclick="showToast('Message envoyé à ${user.pseudo}!', 'success')">
        💬 Envoyer un message
      </button>
    </div>
  `;

  modal.classList.add('active');
}

function closeUserProfileModal() {
  const modal = document.getElementById('user-profile-modal');
  if (modal) modal.classList.remove('active');
}

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

function initChallenges() {
  console.log('🎯 Initialisation du module Défis...');
  
  // Event listeners
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const refreshBtn = document.getElementById('challenge-refresh');
  const participationForm = document.getElementById('participation-form');
  const objectiveTextarea = document.getElementById('participant-objective');
  const toggleRanking = document.getElementById('toggle-ranking');
  
  if (searchInput) searchInput.addEventListener('input', filterChallenges);
  if (statusFilter) statusFilter.addEventListener('change', filterChallenges);
  if (refreshBtn) refreshBtn.addEventListener('click', loadChallenges);
  if (participationForm) participationForm.addEventListener('submit', handleParticipationSubmit);
  if (objectiveTextarea) objectiveTextarea.addEventListener('input', updateCharCount);
  
  // Gestion des onglets de classement
  const rankingTabs = document.querySelectorAll('.ranking-tab');
  rankingTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      rankingTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      renderRanking(tab.dataset.type);
    });
  });
  
  // Fermer modals en cliquant sur overlay
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeChallengeModal();
        closeParticipationModal();
        closeUserProfileModal();
      }
    });
  });
  
  // Charger les données
  loadChallenges();
}

// Charger au chargement du module
document.addEventListener('moduleLoaded', e => {
  if (e.detail.moduleName === 'challenges') {
    console.log('🎯 Module challenges détecté, initialisation...');
    setTimeout(() => initChallenges(), 100);
  }
});

// Si le module est déjà actif
const challengesSection = document.getElementById('challenges');
if (challengesSection && challengesSection.classList.contains('active')) {
  console.log('🎯 Section challenges active, initialisation immédiate...');
  initChallenges();
}

// Fallback
setTimeout(() => {
  const section = document.getElementById('challenges');
  if (section && section.classList.contains('active') && allChallenges.length === 0) {
    console.log('🎯 Chargement forcé (fallback)...');
    initChallenges();
  }
}, 500);

// Exposer les fonctions globalement
window.showChallengeDetail = showChallengeDetail;
window.closeChallengeModal = closeChallengeModal;
window.openParticipationForm = openParticipationForm;
window.closeParticipationModal = closeParticipationModal;
window.showMyChallenge = showMyChallenge;
window.showCollection = showCollection;
window.showProfile = showProfile;
window.showUserProfile = showUserProfile;
window.closeUserProfileModal = closeUserProfileModal;

console.log('✅ Challenges Complete JS prêt');
