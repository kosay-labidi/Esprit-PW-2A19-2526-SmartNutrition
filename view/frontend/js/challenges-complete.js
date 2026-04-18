/**
 * Module Défis Collaboratifs Complet - GaiaLumen
 * Gestion complète des défis avec steakers 3D, classement, formulaire, etc.
 */

console.log('🏆 Challenges Complete JS chargé');

// ═══════════════════════════════════════════════════════════
// DONNÉES
// ═══════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════
// CONFIGURATION ET ENDPOINTS
// ═══════════════════════════════════════════════════════════

// Détection dynamique du chemin de base du backend
const getBackendPath = (file) => {
  const isModule = window.location.pathname.includes('/modules/');
  const base = isModule ? '../../backend' : '../backend';
  return `${base}/challenges/${file}`;
};

const CHALLENGES_ENDPOINT = getBackendPath('listChallenges.php?ajax=1');
const PARTICIPANTS_ENDPOINT = getBackendPath('listParticipants.php');
const ADD_PARTICIPANT_ENDPOINT = getBackendPath('addParticipant.php');

// État global du module
let allChallenges = [];
let allParticipants = [];
const currentUser = window.__USER__ || { id: 1, nom: 'Utilisateur', pseudo: 'user1' };

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

/**
 * Mappe les données du backend vers le format attendu par le frontend
 */
function mapChallengesData(data) {
  if (!Array.isArray(data)) return [];
  return data.map(c => ({
    ...c,
    id: parseInt(c.id, 10),
    titre: c.titre || 'Défi sans titre',
    description: c.description || 'Aucune description disponible',
    type: c.type || 'collectif',
    objectif: c.objectif || 'écologie',
    valeur_cible: parseInt(c.valeur_cible || 0, 10),
    participants_count: parseInt(c.participants_count || 0, 10),
    progression: parseInt(c.progression || 0, 10),
    statut: normalizeChallengeStatus(c.statut),
    steaker: c.streak_icon || c.steaker || '🏆',
    steaker_nom: c.steaker_nom || c.objectif || 'Défi',
    image: c.image || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800'
  }));
}

// ═══════════════════════════════════════════════════════════
// CHARGEMENT DES DÉFIS
// ═══════════════════════════════════════════════════════════

async function loadChallenges() {
  const grid = document.getElementById('challenges-grid');
  const loading = document.getElementById('challenges-loading');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !loading || !empty) return;
  
  loading.style.display = 'block';
  grid.style.display = 'none';
  empty.style.display = 'none';
  
  try {
    const response = await fetch(`${CHALLENGES_ENDPOINT}&t=${Date.now()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);

    const data = await response.json();
    if (!Array.isArray(data)) {
      console.error('Données reçues non valides:', data);
      allChallenges = [];
    } else {
      allChallenges = mapChallengesData(data);
    }
    
    filterChallenges();
    populateRankingSelect();
    await loadParticipantsForRanking();
  } catch (err) {
    console.error('Erreur lors du chargement des défis:', err);
    allChallenges = [];
    filterChallenges();
    populateRankingSelect();
    await loadParticipantsForRanking();
  } finally {
    loading.style.display = 'none';
  }
}

/**
 * Normalise le statut des défis pour correspondre aux classes CSS et filtres
 */
function normalizeChallengeStatus(value) {
  const raw = (value ?? '').toString().trim().toLowerCase();
  if (!raw) return 'actif';
  if (raw === 'active' || raw === 'en cours' || raw === 'en_cours' || raw === 'actif') return 'actif';
  if (raw === 'termine' || raw === 'terminé' || raw === 'terminée') return 'termine';
  if (raw === 'futur' || raw === 'a venir' || raw === 'à venir') return 'futur';
  return 'actif'; // Par défaut
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
    const titre = (c.titre || '').toLowerCase();
    const description = (c.description || '').toLowerCase();
    const matchSearch = titre.includes(search) || description.includes(search);

    let matchStatus = true;
    if (status === 'mine') {
      matchStatus = c.is_participating === true || c.is_participating === 1;
    } else if (status) {
      matchStatus = c.statut === status;
    }

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

  if (challenges.length === 0) {
    grid.style.display = 'none';
    document.getElementById('challenges-empty').style.display = 'block';
    return;
  }

  document.getElementById('challenges-empty').style.display = 'none';
  grid.style.display = 'grid';

  grid.innerHTML = challenges.map(c => {
    const dateDebut = new Date(c.date_debut);
    const dateFin = new Date(c.date_fin);
    const hasValidDates = !Number.isNaN(dateDebut.getTime()) && !Number.isNaN(dateFin.getTime());
    const niveau = getSteakerLevel(c.progression);
    const progressColor = getProgressColor(c.progression);
    const statut = normalizeChallengeStatus(c.statut);
    const canParticipate = statut === 'actif';
    const dateLabel = hasValidDates
      ? `${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})} - ${dateFin.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})}`
      : 'Dates non disponibles';
    
    return `
      <div class="challenge-card" data-challenge-id="${c.id}">
        <div class="challenge-card-main">
          ${c.image ? `<img src="${c.image}" alt="${c.titre}" class="challenge-image">` : ''}
          
          <div class="challenge-steaker">
            ${createSteakerHTML(c.steaker, niveau, 'small')}
          </div>
          
          <div class="challenge-badge ${statut}">
            ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
          </div>
          
          <div class="challenge-content">
            <h3 class="challenge-title">${c.titre}</h3>
            <p class="challenge-description">${c.description}</p>
            
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
                <span class="challenge-participants-count">${c.participants_count} participants</span>
              </div>
              <div class="challenge-stat">
                <span>📅</span>
                <span>${dateLabel}</span>
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

            <button
              class="btn-participate ${canParticipate ? '' : 'is-disabled'}"
              data-challenge-id="${c.id}"
              data-challenge-status="${statut}"
            >
              Participer
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// CLASSEMENT
// ═══════════════════════════════════════════════════════════

async function loadParticipantsForRanking(challengeId = null) {
  const loader = document.getElementById('ranking-loader');
  if (loader) loader.style.display = 'flex';

  try {
    const url = challengeId 
      ? `${PARTICIPANTS_ENDPOINT}?id_challenge=${challengeId}`
      : PARTICIPANTS_ENDPOINT;

    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!response.ok) throw new Error('Erreur réseau');
    
    const data = await response.json();
    
    if (data && Array.isArray(data)) {
      allParticipants = data.map(p => ({
        id: parseInt(p.id_user || p.id, 10),
        nom: p.nom || p.pseudo || 'Anonyme',
        pseudo: p.pseudo || 'anonyme',
        avatar: p.avatar || '👤',
        progression: Math.min(100, Math.round((parseInt(p.score || 0, 10) / (parseInt(p.objectif || 100, 10) || 100)) * 100)),
        points: parseInt(p.points || p.score || 0, 10),
        xp: parseInt(p.xp || 0, 10),
        level: parseInt(p.level || 1, 10)
      }));
    } else {
      allParticipants = sampleParticipants;
    }
  } catch (err) {
    console.error('Erreur classement:', err);
    allParticipants = sampleParticipants;
  } finally {
    if (loader) loader.style.display = 'none';
    renderPodium();
    renderRanking();
    renderMyRank();
  }
}

function populateRankingSelect() {
  const select = document.getElementById('ranking-challenge-filter');
  if (!select || select.dataset.bound === 'true') return;

  const activeChallenges = allChallenges.filter(c => c.statut === 'actif');
  
  let html = '<option value="">Global</option>';
  activeChallenges.forEach(c => {
    html += `<option value="${c.id}">${c.titre}</option>`;
  });
  
  select.innerHTML = html;
  select.addEventListener('change', (e) => {
    loadParticipantsForRanking(e.target.value);
  });
  select.dataset.bound = 'true';
}

function renderPodium() {
  const podium = document.getElementById('ranking-podium');
  if (!podium) return;

  const sorted = [...allParticipants].sort((a, b) => b.progression - a.progression);
  const top3 = sorted.slice(0, 3);

  if (top3.length === 0) {
    podium.innerHTML = '<p style="color:var(--muted);text-align:center;width:100%;">Aucun participant</p>';
    return;
  }

  // Ordre visuel: 2nd (gauche), 1er (centre), 3ème (droite)
  const displayOrder = [];
  if (top3[1]) displayOrder.push({ ...top3[1], rank: 2 });
  if (top3[0]) displayOrder.push({ ...top3[0], rank: 1 });
  if (top3[2]) displayOrder.push({ ...top3[2], rank: 3 });

  podium.innerHTML = displayOrder.map(p => {
    const medal = p.rank === 1 ? '🥇' : p.rank === 2 ? '🥈' : '🥉';
    return `
      <div class="podium-item podium-rank-${p.rank}" onclick="showUserProfile(${p.id})">
        <div class="podium-avatar-wrapper">
          <div class="podium-avatar">${p.avatar}</div>
          <div class="podium-medal">${medal}</div>
        </div>
        <div class="podium-column">
          <span>${p.progression}%</span>
        </div>
        <div class="podium-name">${p.pseudo}</div>
        <div class="podium-pts">${p.points} pts</div>
      </div>
    `;
  }).join('');
}

function updateMiniDashboard(userData) {
  const miniDashboard = document.getElementById('mini-dashboard');
  const streakValue = document.getElementById('mini-stat-streak');
  const pointsValue = document.getElementById('mini-stat-points');

  if (!miniDashboard || !userData) return;

  if (streakValue) streakValue.textContent = `${userData.streak || 0} jours`;
  if (pointsValue) pointsValue.textContent = (userData.points || 0).toLocaleString();

  miniDashboard.style.display = 'flex';
}

function renderMyRank() {
  const myRankCard = document.getElementById('my-ranking-card');
  if (!myRankCard) return;
  
  const sorted = [...allParticipants].sort((a, b) => b.progression - a.progression);
  const myIndex = sorted.findIndex(p => p.id === currentUser.id);
  
  if (myIndex === -1) {
    myRankCard.style.display = 'none';
    return;
  }
  
  myRankCard.style.display = 'flex';
  const myData = sorted[myIndex];
  const rang = myIndex + 1;
  const niveau = getSteakerLevel(myData.progression);
  const progressColor = getProgressColor(myData.progression);
  
  // Niveaux et XP réels ou simulés
  const level = myData.level || Math.floor(myData.points / 500) + 1;
  const xp = myData.xp || (myData.points % 500);
  const xpPercent = Math.min(100, (xp / 500) * 100);

  // Mise à jour du mini-dashboard
  updateMiniDashboard({
    streak: myData.streak || 5, // Simulation si non présent
    points: myData.points
  });

  myRankCard.innerHTML = `
    <div class="my-ranking-rank">#${rang}</div>
    <div class="my-ranking-details">
      <div class="my-ranking-name">${myData.avatar} ${myData.pseudo} <span class="badge-level">Lvl ${level}</span></div>
      <div class="my-ranking-stats">
        <span>${myData.progression}%</span> • <span>${myData.points} pts</span>
      </div>
      <div class="xp-bar-container" title="XP: ${xp}/500">
        <div class="xp-bar-fill" style="width: ${xpPercent}%"></div>
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

function renderRanking() {
  const rankingList = document.getElementById('ranking-list');
  if (!rankingList) return;
  
  const sorted = [...allParticipants].sort((a, b) => b.progression - a.progression);
  const others = sorted.slice(3); // À partir du rang 4

  if (others.length === 0 && sorted.length <= 3) {
    rankingList.innerHTML = '<p style="color:var(--muted);text-align:center;padding:20px;">Fin du classement</p>';
    return;
  }
  
  rankingList.innerHTML = others.map((p, index) => {
    const rang = index + 4;
    const isCurrentUser = p.id === currentUser.id;
    const niveau = getSteakerLevel(p.progression);
    const progressColor = getProgressColor(p.progression);
    
    return `
      <div class="ranking-item ${isCurrentUser ? 'current-user' : ''}" onclick="showUserProfile(${p.id})">
        <div class="ranking-position">
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
  const statut = normalizeChallengeStatus(challenge.statut);
  const isActif = statut === 'actif';
  const isTermine = statut === 'termine';
  const joursRestants = Math.ceil((dateFin - new Date()) / (1000 * 60 * 60 * 24));

  modalBody.innerHTML = `
    ${challenge.image ? `<img src="${challenge.image}" alt="${challenge.titre}" style="width:100%;height:300px;object-fit:cover;border-radius:24px 24px 0 0;">` : ''}
    
    <div style="padding:32px;">
      <div style="display:flex;align-items:center;gap:24px;margin-bottom:24px;">
        ${createSteakerHTML(challenge.steaker, niveau, 'large')}
        
        <div style="flex:1;">
          <h2 style="font-size:2rem;margin-bottom:8px;">${challenge.titre}</h2>
          <div class="challenge-badge ${statut}" style="position:static;display:inline-block;">
            ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
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
        <button onclick="openDrawer(${challenge.id}); closeChallengeModal();" class="btn-primary" style="width:100%;padding:16px;font-size:1.1rem;">
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
let activeCardId = null;

// Ouvrir le drawer
function openDrawer(challengeId) {
  const normalizedId = parseInt(challengeId, 10);
  const challenge = allChallenges.find(c => c.id === normalizedId);
  if (!challenge) return;

  // Retirer l'ancien highlight
  if (activeCardId) {
    const oldMain = document.querySelector(`.challenge-card[data-challenge-id="${activeCardId}"] .challenge-card-main`);
    if (oldMain) oldMain.classList.remove('card-active');
  }

  // Ajouter le highlight à la nouvelle carte
  activeCardId = normalizedId;
  const newMain = document.querySelector(`.challenge-card[data-challenge-id="${normalizedId}"] .challenge-card-main`);
  if (newMain) newMain.classList.add('card-active');

  const drawer = document.getElementById('participation-drawer');
  const summary = document.getElementById('drawer-challenge-summary');
  const formWrapper = document.getElementById('drawer-form-wrapper');

  if (!drawer || !summary || !formWrapper) return;

  currentChallengeId = normalizedId;

  // Remplir le résumé
  const niveau = getSteakerLevel(challenge.progression);
  const statut = normalizeChallengeStatus(challenge.statut);
  
  summary.style.backgroundImage = `url('${challenge.image || ''}')`;
  summary.innerHTML = `
    <div class="drawer-summary-overlay">
      <h3 class="drawer-summary-title">${challenge.titre}</h3>
      <div class="drawer-summary-meta">
        <span class="status-${statut}">${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}</span>
        <span>👥 ${challenge.participants_count} inscrits</span>
        <span>🏆 ${challenge.steaker_nom}</span>
      </div>
    </div>
  `;

  // Remplir le formulaire
  formWrapper.innerHTML = getParticipationFormHTML(challenge);

  // Afficher le drawer
  drawer.setAttribute('aria-hidden', 'false');
  drawer.classList.add('is-open');
  document.body.style.overflow = 'hidden';
  
  // Focus sur le premier champ
  setTimeout(() => {
    const firstField = formWrapper.querySelector('input[name="nom"]');
    if (firstField) firstField.focus();
  }, 400);
}

// Fermer le drawer
function closeDrawer() {
  const drawer = document.getElementById('participation-drawer');
  if (drawer) {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
  }
  document.body.style.overflow = '';

  // Retirer le highlight
  if (activeCardId) {
    const main = document.querySelector(`.challenge-card[data-challenge-id="${activeCardId}"] .challenge-card-main`);
    if (main) main.classList.remove('card-active');
    activeCardId = null;
  }
  
  currentChallengeId = null;
}

function getParticipationFormHTML(challenge) {
  const objectiveValue = Math.max(1, Math.min(100, parseInt(challenge.valeur_cible || 50, 10) || 50));
  const objectiveLabel = `${objectiveValue}%`;

  return `
    <div class="drawer-form-content">
      <div id="inline-feedback-${challenge.id}" class="inline-form-feedback" aria-live="polite"></div>

      <form id="inline-participation-form-${challenge.id}" onsubmit="window.handleParticipationSubmit(event, ${challenge.id})" novalidate>
        <div class="form-group">
          <label class="form-label">Nom complet <span class="required">*</span></label>
          <input type="text" name="nom" class="form-input" placeholder="Ex: Jean Dupont" required>
          <span class="error-msg" id="error-nom-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-input" placeholder="votre@email.com" required>
          <span class="error-msg" id="error-email-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">
            Objectif personnel (%) <span class="required">*</span>
            <span class="char-count" id="objectif-label-${challenge.id}">${objectiveLabel}</span>
          </label>
          <div class="slider-container">
            <input
              type="range"
              name="objectif"
              class="form-slider"
              min="1"
              max="100"
              value="${objectiveValue}"
              required
              oninput="window.updateCharCount(this, 'objectif-value-${challenge.id}')"
            >
            <div class="slider-value" id="objectif-value-${challenge.id}">${objectiveLabel}</div>
          </div>
          <p class="form-help">Définissez votre objectif personnel pour ce défi.</p>
          <span class="error-msg" id="error-objectif-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">
            Motivation <span class="required">*</span>
            <span class="char-count" id="motivation-count-${challenge.id}">0/500</span>
          </label>
          <textarea
            name="motivation"
            class="form-textarea"
            placeholder="Pourquoi souhaitez-vous participer à ce défi ?"
            maxlength="500"
            rows="4"
            required
            oninput="window.updateCharCount(this, 'motivation-count-${challenge.id}')"
          ></textarea>
          <span class="error-msg" id="error-motivation-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Première action concrète <span class="required">*</span></label>
          <textarea
            name="action"
            class="form-textarea"
            placeholder="Quelle sera votre première action pour commencer ?"
            rows="3"
            required
          ></textarea>
          <span class="error-msg" id="error-action-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="engagement" required>
            <span class="checkbox-label">Je m'engage à participer activement à ce défi.</span>
          </label>
        </div>

        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="notifications">
            <span class="checkbox-label">Recevoir des notifications de motivation.</span>
          </label>
        </div>

        <div class="form-actions" style="position: sticky; bottom: -24px; background: var(--surface); margin: 32px -24px -24px; padding: 20px 24px; border-top: 1px solid rgba(91,62,150,0.2);">
          <button type="button" class="btn-secondary" onclick="window.closeDrawer()" style="flex: 1;">Fermer</button>
          <button type="submit" class="btn-primary" style="flex: 2;">
            <span class="participation-submit-text">Confirmer ma participation</span>
          </button>
        </div>
      </form>
    </div>
  `;
}

function setInlineFormFeedback(challengeId, message = '', type = '') {
  const feedback = document.getElementById(`inline-feedback-${challengeId}`);
  if (!feedback) return;

  feedback.textContent = message;
  feedback.className = 'inline-form-feedback';

  if (!message) return;

  feedback.classList.add('is-visible');
  if (type) feedback.classList.add(`is-${type}`);
}

function clearInlineValidation(challengeId) {
  const form = document.getElementById(`inline-participation-form-${challengeId}`);
  if (!form) return;

  form.querySelectorAll('.error-msg').forEach(node => {
    node.textContent = '';
  });
  form.querySelectorAll('.invalid').forEach(field => {
    field.classList.remove('invalid');
  });
  setInlineFormFeedback(challengeId);
}

function showInlineFieldError(challengeId, fieldName, message) {
  const errorNode = document.getElementById(`error-${fieldName}-${challengeId}`);
  const form = document.getElementById(`inline-participation-form-${challengeId}`);
  const field = form?.querySelector(`[name="${fieldName}"]`);

  if (errorNode) errorNode.textContent = message;
  if (field) field.classList.add('invalid');
}

function updateCharCount(source, outputId) {
  if (typeof source === 'string') {
    const output = document.getElementById(source);
    if (output) output.textContent = outputId;
    return;
  }

  const field = source;
  const output = document.getElementById(outputId);
  if (!field || !output) return;

  if (field.type === 'range') {
    const value = `${field.value}%`;
    output.textContent = value;
    const labelId = outputId.replace('objectif-value-', 'objectif-label-');
    const label = document.getElementById(labelId);
    if (label) label.textContent = value;
    return;
  }

  const length = field.value.length;
  output.textContent = `${length}/500`;
  output.style.color = length >= 10 ? 'var(--success)' : 'var(--muted)';
}

function updateChallengeParticipationCount(challengeId, count) {
  const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
  const countNode = card?.querySelector('.challenge-participants-count');
  if (countNode) {
    countNode.textContent = `${count} participants`;
  }
}

function handleParticipationSubmit(event, challengeId = currentChallengeId) {
  event.preventDefault();

  const normalizedId = parseInt(challengeId, 10);
  const form = event.target;
  const challenge = allChallenges.find(c => c.id === normalizedId);
  const btnSubmit = form.querySelector('button[type="submit"]');
  const submitText = form.querySelector('.participation-submit-text');
  if (!challenge || !btnSubmit || !submitText) return;
  if (btnSubmit.disabled) return;

  clearInlineValidation(normalizedId);

  const formData = new FormData(form);
  const data = {
    id_challenge: normalizedId,
    nom: (formData.get('nom') || '').toString().trim(),
    email: (formData.get('email') || '').toString().trim(),
    objectif: parseInt((formData.get('objectif') || '0').toString(), 10),
    motivation: (formData.get('motivation') || '').toString().trim(),
    action: (formData.get('action') || '').toString().trim(),
    engagement: formData.get('engagement') === 'on' ? 1 : 0,
    notifications: formData.get('notifications') === 'on' ? 1 : 0
  };

  let hasErrors = false;

  if (data.nom.length < 2) {
    showInlineFieldError(normalizedId, 'nom', 'Le nom doit contenir au moins 2 caractères.');
    hasErrors = true;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
    showInlineFieldError(normalizedId, 'email', 'Veuillez saisir un email valide.');
    hasErrors = true;
  }
  if (!data.objectif || data.objectif < 1 || data.objectif > 100) {
    showInlineFieldError(normalizedId, 'objectif', 'L’objectif doit être compris entre 1 et 100%.');
    hasErrors = true;
  }
  if (data.motivation.length < 10) {
    showInlineFieldError(normalizedId, 'motivation', 'La motivation doit contenir au moins 10 caractères.');
    hasErrors = true;
  }
  if (data.action.length < 5) {
    showInlineFieldError(normalizedId, 'action', 'Décrivez une action concrète en au moins 5 caractères.');
    hasErrors = true;
  }
  if (!data.engagement) {
    setInlineFormFeedback(normalizedId, 'Vous devez confirmer votre engagement avant de participer.', 'error');
    hasErrors = true;
  }

  if (hasErrors) {
    showToast('Merci de corriger les champs indiqués.', 'error');
    return;
  }

  btnSubmit.disabled = true;
  submitText.textContent = 'Envoi...';

  fetch(ADD_PARTICIPANT_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(data)
  })
    .then(r => r.json())
    .then(result => {
      if (!result || !result.success) {
        setInlineFormFeedback(normalizedId, result?.message || 'Erreur lors de l’inscription.', 'error');
        showToast(result?.message || 'Erreur lors de l’inscription', 'error');
        btnSubmit.disabled = false;
        submitText.textContent = 'Confirmer ma participation';
        return;
      }

      challenge.participants_count = parseInt(challenge.participants_count || 0, 10) + 1;
      updateChallengeParticipationCount(normalizedId, challenge.participants_count);
      setInlineFormFeedback(normalizedId, `Participation confirmée pour "${challenge.titre}".`, 'success');
      showToast(`Participation confirmée au défi "${challenge.titre}"`, 'success');
      form.reset();
      const motivationField = form.querySelector('[name="motivation"]');
      const objectifField = form.querySelector('[name="objectif"]');
      if (motivationField) updateCharCount(motivationField, `motivation-count-${normalizedId}`);
      if (objectifField) updateCharCount(objectifField, `objectif-value-${normalizedId}`);

      setTimeout(() => {
        closeDrawer();
        filterChallenges();
      }, 900);
    })
    .catch(() => {
      setInlineFormFeedback(normalizedId, 'Une erreur réseau est survenue. Réessayez dans un instant.', 'error');
      showToast('Une erreur réseau est survenue', 'error');
      btnSubmit.disabled = false;
      submitText.textContent = 'Confirmer ma participation';
    });
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
  const section = document.getElementById('challenges');
  if (!section) return;

  console.log('🎯 Initialisation du module Défis...');
  
  // Event listeners
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const refreshBtn = document.getElementById('challenge-refresh');
  const grid = document.getElementById('challenges-grid');
  
  if (searchInput && searchInput.dataset.bound !== 'true') {
    searchInput.addEventListener('input', filterChallenges);
    searchInput.dataset.bound = 'true';
  }
  if (statusFilter && statusFilter.dataset.bound !== 'true') {
    statusFilter.addEventListener('change', filterChallenges);
    statusFilter.dataset.bound = 'true';
  }
  if (refreshBtn && refreshBtn.dataset.bound !== 'true') {
    refreshBtn.addEventListener('click', loadChallenges);
    refreshBtn.dataset.bound = 'true';
  }
  if (grid && grid.dataset.bound !== 'true') {
    grid.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-participate');
      if (btn) {
        e.preventDefault();

        const id = parseInt(btn.getAttribute('data-challenge-id') || '0', 10);
        if (!id) return;

        const statut = normalizeChallengeStatus(btn.getAttribute('data-challenge-status'));
        if (statut !== 'actif') {
          showToast(statut === 'termine' ? 'Ce défi est terminé' : 'Ce défi n\'est pas encore disponible', 'error');
          return;
        }

        openDrawer(id);
        return;
      }

      if (e.target.closest('.drawer-panel')) return;

      const card = e.target.closest('.challenge-card-main');
      if (!card) return;

      const challengeCard = card.closest('.challenge-card');
      const id = parseInt(challengeCard?.getAttribute('data-challenge-id') || '0', 10);
      if (!id) return;

      showChallengeDetail(id);
    });
    grid.dataset.bound = 'true';
  }
  
  // Fermer modals en cliquant sur overlay
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    if (overlay.dataset.bound === 'true') return;
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeChallengeModal();
        closeDrawer();
        closeUserProfileModal();
      }
    });
    overlay.dataset.bound = 'true';
  });

  // Tabs classement
  document.querySelectorAll('.ranking-tab').forEach(tab => {
    if (tab.dataset.bound === 'true') return;
    tab.addEventListener('click', () => {
      document.querySelectorAll('.ranking-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      loadParticipantsForRanking();
    });
    tab.dataset.bound = 'true';
  });

  // Fermer sur Escape
  if (window.__ESCAPE_BOUND__ !== true) {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeChallengeModal();
        closeDrawer();
        closeUserProfileModal();
      }
    });
    window.__ESCAPE_BOUND__ = true;
  }
  
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
window.initChallenges = initChallenges;
window.loadChallenges = loadChallenges;
window.showChallengeDetail = showChallengeDetail;
window.closeChallengeModal = closeChallengeModal;
window.openDrawer = openDrawer;
window.closeDrawer = closeDrawer;
window.updateCharCount = updateCharCount;
window.handleParticipationSubmit = handleParticipationSubmit;
window.showMyChallenge = showMyChallenge;
window.showCollection = showCollection;
window.showProfile = showProfile;
window.showUserProfile = showUserProfile;
window.closeUserProfileModal = closeUserProfileModal;

console.log('✅ Challenges Complete JS prêt');
