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

// Vue active (grid | swipe)
let activeChallengesView = 'grid';
let filteredChallenges = [];

// Pagination
let currentPage = 1;
const itemsPerPage = 6;

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

function renderDaysLeftBadge(dateFin) {
  const days = Math.ceil((new Date(dateFin) - new Date()) / 86400000);
  if (days < 0) return `<span class="gl-days-badge gl-days-badge--over">Terminé</span>`;
  if (days === 0) return `<span class="gl-days-badge gl-days-badge--today">Dernier jour !</span>`;
  if (days <= 3) return `<span class="gl-days-badge gl-days-badge--urgent">${days}j restants</span>`;
  if (days <= 7) return `<span class="gl-days-badge gl-days-badge--soon">${days}j restants</span>`;
  return `<span class="gl-days-badge">${days}j restants</span>`;
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
    // Utiliser une résolution plus élevée pour la vue Swipe (image pleine largeur)
    image: c.image || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1400&auto=format&fit=crop&q=85'
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

    // Exposer l'état à la page (certaines features patchées y accèdent)
    window.allChallenges = allChallenges;
    
    filterChallenges();
    populateRankingSelect();
    await loadParticipantsForRanking();
  } catch (err) {
    console.error('Erreur lors du chargement des défis:', err);
    allChallenges = [];
    window.allChallenges = allChallenges;
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
  currentPage = 1; // Reset pagination
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const sortFilter = document.getElementById('challenge-sort-filter');
  const activeChip = document.querySelector('.gl-chip--active');
  const grid = document.getElementById('challenges-grid');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !empty) return;
  
  const search = searchInput?.value.toLowerCase() || '';
  const status = statusFilter?.value || '';
  const chipStatus = activeChip?.dataset.status || '';
  const sortType = sortFilter?.value || 'date_desc';
  
  let filtered = allChallenges.filter(c => {
    const titre = (c.titre || '').toLowerCase();
    const description = (c.description || '').toLowerCase();
    const matchSearch = titre.includes(search) || description.includes(search);
    
    // Priorité au chip de filtrage
    let matchStatus = true;
    if (chipStatus === 'liked') {
      matchStatus = !!c.is_liked;
    } else if (chipStatus) {
      matchStatus = c.statut === chipStatus;
    } else if (status) {
      matchStatus = c.statut === status;
    }
    
    return matchSearch && matchStatus;
  });

  // Tri des défis
  filtered.sort((a, b) => {
    if (sortType === 'participants_desc') {
      return (b.participants_count || 0) - (a.participants_count || 0);
    } else if (sortType === 'titre_asc') {
      return (a.titre || '').localeCompare(b.titre || '');
    } else {
      // date_desc par défaut
      return new Date(b.date_debut) - new Date(a.date_debut);
    }
  });

  filteredChallenges = filtered;
  window.filteredChallenges = filteredChallenges;

  const swipeViewEl = document.getElementById('swipe-view');
  const isSwipe = activeChallengesView === 'swipe' && !!swipeViewEl;

  if (filtered.length === 0) {
    grid.style.display = 'none';
    if (isSwipe) {
      empty.style.display = 'none';
      renderSwipeDeck([]);
    } else {
      empty.style.display = 'block';
    }
    return;
  }

  empty.style.display = 'none';

  if (isSwipe) {
    grid.style.display = 'none';
    renderSwipeDeck(filtered);
  } else {
    grid.style.display = 'grid';
    renderChallenges(filtered);
  }
}

function setChallengesView(view) {
  const grid = document.getElementById('challenges-grid');
  const swipeView = document.getElementById('swipe-view');
  const btnGrid = document.getElementById('view-grid');
  const btnList = document.getElementById('view-list');
  const btnSwipe = document.getElementById('view-swipe');

  if (!grid) return;
  activeChallengesView = view === 'swipe' ? 'swipe' : 'grid';

  if (activeChallengesView === 'swipe' && swipeView) {
    grid.style.display = 'none';
    swipeView.style.display = 'flex';
    btnSwipe?.classList.add('active');
    btnGrid?.classList.remove('active');
    btnList?.classList.remove('active');
    renderSwipeDeck(filteredChallenges.length ? filteredChallenges : allChallenges);
    return;
  }

  // Grille/Liste
  if (swipeView) swipeView.style.display = 'none';
  grid.style.display = 'grid';
  btnSwipe?.classList.remove('active');
}

function escapeHtml(str) {
  return (str ?? '').toString().replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[m]));
}

function buildSwipeCard(challenge) {
  const statut = normalizeChallengeStatus(challenge.statut);
  const dateFin = new Date(challenge.date_fin);
  const hasValidDate = !Number.isNaN(dateFin.getTime());
  const days = hasValidDate ? Math.ceil((dateFin - new Date()) / 86400000) : null;
  const daysLabel = days === null ? '' : (days < 0 ? 'Terminé' : `${Math.max(days, 0)}j`);
  const rewardName = escapeHtml(challenge.steaker_nom || challenge.objectif || 'Récompense');

  return `
    <article class="gl-swipe-card" data-challenge-id="${challenge.id}">
      <div class="gl-swipe-card__like-label">LIKE</div>
      <div class="gl-swipe-card__nope-label">NOPE</div>

      ${challenge.image
        ? `<img class="gl-swipe-card__img" src="${escapeHtml(challenge.image)}" alt="${escapeHtml(challenge.titre)}" loading="lazy" decoding="async">`
        : `<div class="gl-swipe-card__img-placeholder">🏞️</div>`
      }

      <span class="gl-swipe-card__status-badge ${statut}">
        ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
      </span>
      ${daysLabel ? `<span class="gl-swipe-card__days-badge">${daysLabel}</span>` : ''}

      <div class="gl-swipe-card__body">
        <div>
          <div class="gl-swipe-card__title">${escapeHtml(challenge.titre)}</div>
          <div class="gl-swipe-card__desc">${escapeHtml(challenge.description)}</div>

          <div class="gl-swipe-card__reward">
            <span class="gl-swipe-card__reward-icon">🏆</span>
            <div>
              <div class="gl-swipe-card__reward-label">Récompense</div>
              <div class="gl-swipe-card__reward-name">${rewardName}</div>
            </div>
          </div>

          <div class="gl-swipe-card__meta">
            <div class="gl-swipe-card__meta-item">👥 <span>${parseInt(challenge.participants_count || 0, 10)}</span></div>
            <div class="gl-swipe-card__meta-item">📈 <span>${parseInt(challenge.progression || 0, 10)}%</span></div>
          </div>
        </div>

        <div class="gl-swipe-card__progress">
          <div class="gl-swipe-card__progress-row">
            <span style="font-size:.72rem;color:var(--muted,#a8b8a0)">Progression</span>
            <span style="font-weight:800">${parseInt(challenge.progression || 0, 10)}%</span>
          </div>
          <div class="gl-swipe-card__progress-bar">
            <div class="gl-swipe-card__progress-fill" style="width:${Math.max(0, Math.min(100, parseInt(challenge.progression || 0, 10)))}%"></div>
          </div>
        </div>
      </div>
    </article>
  `;
}

function updateSwipeCounter(currentIndex, total) {
  const curEl = document.getElementById('swipe-current');
  const totEl = document.getElementById('swipe-total');
  if (totEl) totEl.textContent = String(total || 0);
  if (curEl) curEl.textContent = String(Math.min(total || 0, Math.max(1, currentIndex)));
}

function getTopSwipeCard() {
  const deck = document.getElementById('swipe-deck');
  if (!deck) return null;
  const cards = deck.querySelectorAll('.gl-swipe-card');
  return cards.length ? cards[0] : null;
}

function attachSwipeDrag(card) {
  if (!card || card.dataset.dragBound === 'true') return;
  card.dataset.dragBound = 'true';

  let startX = 0;
  let startY = 0;
  let dx = 0;
  let dy = 0;
  let dragging = false;

  const onMove = (e) => {
    if (!dragging) return;
    dx = (e.clientX - startX);
    dy = (e.clientY - startY);
    const rot = Math.max(-18, Math.min(18, dx / 18));
    card.style.transform = `translate(${dx}px, ${dy * 0.25}px) rotate(${rot}deg)`;
    const like = card.querySelector('.gl-swipe-card__like-label');
    const nope = card.querySelector('.gl-swipe-card__nope-label');
    if (like) like.style.opacity = dx > 30 ? String(Math.min(1, dx / 120)) : '0';
    if (nope) nope.style.opacity = dx < -30 ? String(Math.min(1, Math.abs(dx) / 120)) : '0';
  };

  const endDrag = () => {
    if (!dragging) return;
    dragging = false;
    card.classList.remove('is-dragging');
    card.releasePointerCapture?.(pointerId);

    const threshold = 120;
    if (dx > threshold) {
      swipeExit('right');
      return;
    }
    if (dx < -threshold) {
      swipeExit('left');
      return;
    }
    card.style.transition = 'transform 180ms ease';
    card.style.transform = '';
    const like = card.querySelector('.gl-swipe-card__like-label');
    const nope = card.querySelector('.gl-swipe-card__nope-label');
    if (like) like.style.opacity = '0';
    if (nope) nope.style.opacity = '0';
    setTimeout(() => { card.style.transition = ''; }, 220);
  };

  let pointerId = null;
  card.addEventListener('pointerdown', (e) => {
    // Ne permettre le drag que sur la carte du dessus
    if (card !== getTopSwipeCard()) return;
    pointerId = e.pointerId;
    dragging = true;
    startX = e.clientX;
    startY = e.clientY;
    dx = 0;
    dy = 0;
    card.classList.add('is-dragging');
    card.setPointerCapture?.(e.pointerId);
  });
  card.addEventListener('pointermove', onMove);
  card.addEventListener('pointerup', endDrag);
  card.addEventListener('pointercancel', endDrag);
}

function swipeExit(direction) {
  const deck = document.getElementById('swipe-deck');
  if (!deck) return;
  const card = getTopSwipeCard();
  if (!card) return;

  const id = parseInt(card.getAttribute('data-challenge-id') || '0', 10);
  const challenge = (filteredChallenges.length ? filteredChallenges : allChallenges).find(c => c.id === id);

  card.classList.remove('is-dragging');
  card.style.transition = '';
  card.classList.add(direction === 'right' ? 'leaving-right' : 'leaving-left');

  // Like: ouvrir détails (et participation depuis le drawer). Skip: juste passer.
  if (direction === 'right' && typeof window.showChallengeDetail === 'function' && id) {
    try { window.showChallengeDetail(id); } catch (_) {}
    if (challenge) showToast?.(`Défi "${challenge.titre}"`, 'success');
  }

  setTimeout(() => {
    card.remove();
    const remaining = deck.querySelectorAll('.gl-swipe-card').length;
    const total = parseInt(document.getElementById('swipe-total')?.textContent || '0', 10) || 0;
    const currentShown = total - remaining;
    updateSwipeCounter(currentShown + 1, total);

    const done = document.getElementById('swipe-done');
    if (done) done.style.display = remaining === 0 ? 'flex' : 'none';

    const next = getTopSwipeCard();
    if (next) attachSwipeDrag(next);
  }, 420);
}

function renderSwipeDeck(challenges) {
  const swipeView = document.getElementById('swipe-view');
  const deck = document.getElementById('swipe-deck');
  const done = document.getElementById('swipe-done');
  if (!swipeView || !deck || !done) return;

  const list = Array.isArray(challenges) ? challenges : [];
  deck.innerHTML = '';

  const maxCards = Math.min(20, list.length);
  for (let i = 0; i < maxCards; i++) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = buildSwipeCard(list[i]);
    const el = wrapper.firstElementChild;
    if (el) deck.appendChild(el);
  }

  updateSwipeCounter(1, maxCards);
  done.style.display = maxCards === 0 ? 'flex' : 'none';

  // Bind buttons once
  const btnSkip = document.getElementById('swipe-btn-skip');
  const btnLike = document.getElementById('swipe-btn-like');
  const btnInfo = document.getElementById('swipe-btn-info');
  const btnReset = document.getElementById('swipe-reset');

  if (btnSkip && btnSkip.dataset.bound !== 'true') {
    btnSkip.addEventListener('click', () => swipeExit('left'));
    btnSkip.dataset.bound = 'true';
  }
  if (btnLike && btnLike.dataset.bound !== 'true') {
    btnLike.addEventListener('click', () => swipeExit('right'));
    btnLike.dataset.bound = 'true';
  }
  if (btnInfo && btnInfo.dataset.bound !== 'true') {
    btnInfo.addEventListener('click', () => {
      const top = getTopSwipeCard();
      const id = parseInt(top?.getAttribute('data-challenge-id') || '0', 10);
      if (id && typeof window.showChallengeDetail === 'function') window.showChallengeDetail(id);
    });
    btnInfo.dataset.bound = 'true';
  }
  if (btnReset && btnReset.dataset.bound !== 'true') {
    btnReset.addEventListener('click', () => renderSwipeDeck(list));
    btnReset.dataset.bound = 'true';
  }

  const top = getTopSwipeCard();
  if (top) attachSwipeDrag(top);
}

function renderChallenges(challenges) {
  const grid = document.getElementById('challenges-grid');
  const pagination = document.getElementById('challenges-pagination');
  if (!grid) return;

  if (challenges.length === 0) {
    grid.style.display = 'none';
    if (pagination) pagination.style.display = 'none';
    document.getElementById('challenges-empty').style.display = 'block';
    return;
  }

  document.getElementById('challenges-empty').style.display = 'none';
  grid.style.display = 'grid';

  // Logique de pagination
  const totalPages = Math.ceil(challenges.length / itemsPerPage);
  if (currentPage > totalPages) currentPage = totalPages || 1;
  
  const start = (currentPage - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  const paginatedItems = challenges.slice(start, end);

  // Mise à jour de l'UI de pagination
  if (pagination) {
    pagination.style.display = totalPages > 1 ? 'flex' : 'none';
    const pageInfo = document.getElementById('page-info');
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
    
    const btnPrev = document.getElementById('prev-page');
    const btnNext = document.getElementById('next-page');
    if (btnPrev) {
      btnPrev.disabled = currentPage === 1;
      btnPrev.style.opacity = currentPage === 1 ? '0.4' : '1';
      btnPrev.style.pointerEvents = currentPage === 1 ? 'none' : 'auto';
    }
    if (btnNext) {
      btnNext.disabled = currentPage === totalPages;
      btnNext.style.opacity = currentPage === totalPages ? '0.4' : '1';
      btnNext.style.pointerEvents = currentPage === totalPages ? 'none' : 'auto';
    }
  }

  grid.innerHTML = paginatedItems.map(c => {
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
    const daysLeftBadge = hasValidDates ? renderDaysLeftBadge(c.date_fin) : '';
    
    return `
      <div class="challenge-card" data-challenge-id="${c.id}">
        <div class="challenge-card-main">
          ${c.image ? `
            <div class="challenge-image-wrap" style="position:relative;cursor:pointer;" onclick="showChallengeDetail(${c.id})">
              <img src="${c.image}" alt="${c.titre}" class="challenge-image">
              <div class="challenge-overlay-stats" style="position:absolute;top:10px;left:10px;display:flex;gap:5px;z-index:2;">
                <span class="stat-item" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);color:#fff;padding:2px 8px;border-radius:20px;font-size:0.7rem;display:flex;align-items:center;gap:4px;">
                  <i class="lni lni-eye"></i> ${c.nb_vues || 0}
                </span>
                <span class="stat-item like-count-${c.id}" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);color:#fff;padding:2px 8px;border-radius:20px;font-size:0.7rem;display:flex;align-items:center;gap:4px;">
                  <i class="lni lni-heart"></i> ${c.nb_likes || 0}
                </span>
              </div>
            </div>
          ` : ''}
          
          <div class="challenge-steaker">
            ${createSteakerHTML(c.steaker, niveau, 'small')}
          </div>
          
          <div class="challenge-badge ${statut}">
            ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
          </div>
          
          <div class="challenge-content">
            <div class="challenge-header-row" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <h3 class="challenge-title" style="margin:0;font-size:1.1rem;cursor:pointer;" onclick="showChallengeDetail(${c.id})">${c.titre}</h3>
              <button class="btn-like ${c.is_liked ? 'active' : ''}" 
                      style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.05);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s;"
                      onclick="event.stopPropagation(); window.toggleLike(${c.id}, this)">
                <i class="lni lni-heart${c.is_liked ? '-fill' : ''}"></i>
              </button>
            </div>
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
              <div class="challenge-stat">
                <span>⏳</span>
                <span>${daysLeftBadge}</span>
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
        points: parseInt(p.points || p.score || 0, 10)
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
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!podium) return;

  const sorted = [...allParticipants].sort((a, b) => {
    if (sortType === 'points') return b.points - a.points;
    return b.progression - a.progression;
  });
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

function renderMyRank() {
  const myRankCard = document.getElementById('my-ranking-card');
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!myRankCard) return;
  
  const sorted = [...allParticipants].sort((a, b) => {
    if (sortType === 'points') return b.points - a.points;
    return b.progression - a.progression;
  });
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

function renderRanking() {
  const rankingList = document.getElementById('ranking-list');
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!rankingList) return;
  
  const sorted = [...allParticipants].sort((a, b) => {
    if (sortType === 'points') return b.points - a.points;
    return b.progression - a.progression;
  });
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

  // Incrémenter les vues
  if (window.incrementVues) window.incrementVues(challengeId);

  const dateDebut = new Date(challenge.date_debut);
  const dateFin = new Date(challenge.date_fin);
  const niveau = getSteakerLevel(challenge.progression);
  const progressColor = getProgressColor(challenge.progression);
  const statut = normalizeChallengeStatus(challenge.statut);
  const isActif = statut === 'actif';
  const isTermine = statut === 'termine';
  const joursRestants = Math.ceil((dateFin - new Date()) / (1000 * 60 * 60 * 24));

  modalBody.innerHTML = `
    <div class="gl-ch-detail">
      ${challenge.image ? `
        <div class="gl-ch-detail__media" style="position:relative;">
          <img class="gl-ch-detail__img" src="${challenge.image}" alt="${challenge.titre}" loading="lazy" decoding="async">
          <div class="gl-ch-detail__mediaShade" aria-hidden="true" style="position:absolute;inset:0;background:linear-gradient(to bottom, transparent, rgba(0,0,0,0.8));"></div>
          <div style="position:absolute;bottom:20px;left:20px;right:20px;display:flex;justify-content:space-between;align-items:flex-end;z-index:2;">
            <h2 style="color:#fff;font-size:1.8rem;margin:0;font-family:'Cormorant Garamond',serif;">${challenge.titre}</h2>
            <button class="btn-like ${challenge.is_liked ? 'active' : ''}" 
                    style="width:42px;height:42px;border-radius:50%;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                    onclick="event.stopPropagation(); window.toggleLike(${challenge.id}, this)">
              <i class="lni lni-heart${challenge.is_liked ? '-fill' : ''}" style="font-size:1.3rem;"></i>
            </button>
          </div>
        </div>
        </div>
      ` : ''}

      <div class="gl-ch-detail__content">
        <div class="gl-ch-detail__header">
          <div class="gl-ch-detail__steaker">
            ${createSteakerHTML(challenge.steaker, niveau, 'large')}
          </div>
          <div class="gl-ch-detail__titleWrap">
            <h2 class="gl-ch-detail__title">${challenge.titre}</h2>
            <div class="challenge-badge ${statut} gl-ch-detail__badge">
              ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
            </div>
          </div>
        </div>

        <p class="gl-ch-detail__desc">${challenge.description}</p>

        <div class="gl-ch-detail__stats">
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">👥 Participants</div>
            <div class="gl-ch-stat__val">${challenge.participants_count}</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">🎯 Objectif</div>
            <div class="gl-ch-stat__val">${challenge.valeur_cible}%</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">📊 Progression</div>
            <div class="gl-ch-stat__val gl-ch-stat__val--${progressColor}">${challenge.progression}%</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">📅 ${isActif ? 'Jours restants' : 'Durée'}</div>
            <div class="gl-ch-stat__val">${isActif ? joursRestants : Math.ceil((dateFin - dateDebut) / (1000 * 60 * 60 * 24))}j</div>
          </div>
        </div>

        <div class="progress-wrapper gl-ch-detail__progress">
          <div class="progress-header">
            <span class="progress-label">Progression globale</span>
            <span class="progress-value" style="color:var(--progress-${progressColor})">${challenge.progression}%</span>
          </div>
          <div class="progress-bar-container" data-progress="${challenge.progression}" style="height:14px;">
            <div class="progress-bar-fill ${progressColor}" style="width:${challenge.progression}%"></div>
          </div>
        </div>

        <div class="gl-ch-detail__cta">
          ${isActif ? `
            <button onclick="openDrawer(${challenge.id}); closeChallengeModal();" class="btn-primary gl-ch-detail__btn">
              ✅ Participer à ce défi
            </button>
          ` : isTermine ? `
            <div class="gl-ch-detail__notice gl-ch-detail__notice--muted">
              📦 Ce défi est terminé
            </div>
          ` : `
            <div class="gl-ch-detail__notice gl-ch-detail__notice--info">
              🔜 Ce défi commence le ${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})}
            </div>
          `}
        </div>
      </div>
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
      
      showToast(
        `Félicitations !`,
        `Votre participation au défi "${challenge.titre}" a été enregistrée.`,
        'success',
        challenge.streak_icon || '🏆'
      );
      window.addNotification(`Vous avez rejoint le défi "${challenge.titre}" !`, challenge.streak_icon || '🏆');

      markChallengeJoined(normalizedId);
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

function markChallengeJoined(challengeId) {
  const btn = document.querySelector(`.btn-participate[data-challenge-id="${challengeId}"]`);
  if (!btn) return;
  btn.disabled = true;
  btn.innerHTML = '✅ Inscrit !';
  btn.classList.add('gl-btn--joined');
}

function handleJoinChallenge(btn, challengeId) {
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="gl-spinner"></span> Inscription...`;
  try {
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = originalHTML;
      openDrawer(challengeId);
    }, 350);
  } catch (e) {
    btn.disabled = false;
    btn.innerHTML = originalHTML;
  }
}

// ═══════════════════════════════════════════════════════════
// TOAST & NOTIFICATION SYSTEM
// ═══════════════════════════════════════════════════════════
window.notifications = [];

window.addNotification = function(text, icon = '🔔') {
  const notif = {
    id: Date.now(),
    text,
    icon,
    time: 'À l\'instant',
    unread: true
  };
  window.notifications.unshift(notif);
  updateNotifUI();
};

window.clearNotifications = function() {
  window.notifications = [];
  updateNotifUI();
};

function updateNotifUI() {
  const panel = document.getElementById('gl-notif-panel');
  const list = document.getElementById('gl-notif-list');
  const badge = document.getElementById('gl-notif-count');
  
  if (!list || !badge) return;

  const unreadCount = window.notifications.filter(n => n.unread).length;
  badge.textContent = unreadCount;
  badge.style.display = unreadCount > 0 ? 'flex' : 'none';

  if (window.notifications.length === 0) {
    list.innerHTML = '<div class="gl-notif-empty">Aucune nouvelle notification</div>';
    return;
  }

  list.innerHTML = window.notifications.map(n => `
    <div class="gl-notif-item ${n.unread ? 'gl-notif-item--unread' : ''}" onclick="this.classList.remove('gl-notif-item--unread')">
      <div class="gl-notif-item__icon">${n.icon}</div>
      <div class="gl-notif-item__content">
        <div class="gl-notif-item__text">${n.text}</div>
        <div class="gl-notif-item__time">${n.time}</div>
      </div>
    </div>
  `).join('');
}

function showToast(title, message = '', type = 'success', customIcon = null) {
  const cfg = {
    success: { color: '#22c55e', icon: 'lni lni-checkmark-circle' },
    error:   { color: '#ef4444', icon: 'lni lni-close' },
    warning: { color: '#f59e0b', icon: 'lni lni-warning' },
    info:    { color: '#3b82f6', icon: 'lni lni-bubble' },
  };
  const c = cfg[type] || cfg.info;
  const iconHtml = customIcon 
    ? `<span style="font-size:1.4rem;flex-shrink:0;">${customIcon}</span>`
    : `<span style="font-size:1.2rem;flex-shrink:0;margin-top:1px;color:${c.color};"><i class="${c.icon}"></i></span>`;

  let container = document.getElementById('gl-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'gl-toast-container';
    container.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;`;
    document.body.appendChild(container);
  }

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
    ${iconHtml}
    <div style="flex:1;">
      <div style="font-weight:700;margin-bottom:2px;">${title}</div>
      ${message ? `<div style="color:#94a3b8;font-size:12px;line-height:1.4;">${message}</div>` : ''}
    </div>
    <button onclick="this.closest('[style]').remove()" style="background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;padding:0;margin-top:1px;flex-shrink:0;">✕</button>
  `;

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'glToastOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
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
// MÉTIER : VUES & LIKES
// ═══════════════════════════════════════════════════════════

window.incrementVues = function(challengeId) {
  const endpoint = getBackendPath('listChallenges.php?action=incrementVues');
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id: challengeId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const c = allChallenges.find(ch => ch.id === parseInt(challengeId));
      if (c) {
        c.nb_vues = (parseInt(c.nb_vues) || 0) + 1;
        const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
        if (card) {
          const vueEl = card.querySelector('.lni-eye')?.parentElement;
          if (vueEl) vueEl.innerHTML = `<i class="lni lni-eye"></i> ${c.nb_vues}`;
        }
      }
    }
  })
  .catch(err => console.warn('Erreur incrementVues:', err));
};

window.toggleLike = function(challengeId, btn) {
  const icon = btn.querySelector('i');
  const endpoint = getBackendPath('listChallenges.php?action=toggleLike');
  
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id_challenge: challengeId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.liked !== undefined) {
      const c = allChallenges.find(ch => ch.id === parseInt(challengeId));
      if (c) {
        c.is_liked = data.liked;
        c.nb_likes = data.count;
      }
      btn.classList.toggle('active', data.liked);
      if (icon) icon.className = `lni lni-heart${data.liked ? '-fill' : ''}`;
      document.querySelectorAll(`.like-count-${challengeId}`).forEach(el => {
        el.innerHTML = `<i class="lni lni-heart"></i> ${data.count}`;
      });
      
      if (window.showToast) {
        showToast(
          data.liked ? 'Coup de cœur !' : 'Favoris mis à jour',
          data.liked ? `Défi "${c.titre}" ajouté à vos favoris` : `Défi "${c.titre}" retiré de vos favoris`,
          'info',
          data.liked ? '❤️' : '💔'
        );
      }
      window.addNotification(
        data.liked ? `Vous avez aimé le défi "${c.titre}"` : `Vous avez retiré votre like du défi "${c.titre}"`,
        data.liked ? '❤️' : '💔'
      );
    }
  })
  .catch(err => {
    console.error('Erreur toggleLike:', err);
    if (window.showToast) window.showToast('Erreur lors du like. Êtes-vous connecté ?', 'error');
  });
};

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

function setupNotifEvents() {
  const trigger = document.getElementById('gl-notif-trigger');
  const panel = document.getElementById('gl-notif-panel');
  
  if (trigger && panel) {
    trigger.onclick = (e) => {
      e.stopPropagation();
      panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
      if (panel.style.display === 'block') {
        window.notifications.forEach(n => n.unread = false);
        updateNotifUI();
      }
    };
    
    document.addEventListener('click', (e) => {
      if (!panel.contains(e.target) && e.target !== trigger) {
        panel.style.display = 'none';
      }
    });
  }
}

function initChallenges() {
  const section = document.getElementById('challenges');
  if (!section) return;

  console.log('🎯 Initialisation du module Défis...');
  setupNotifEvents();
  
  // Event listeners
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const sortFilter = document.getElementById('challenge-sort-filter');
  const rankSortFilter = document.getElementById('ranking-sort-filter');
  const refreshBtn = document.getElementById('challenge-refresh');
  const grid = document.getElementById('challenges-grid');
  
  const btnPrev = document.getElementById('prev-page');
  const btnNext = document.getElementById('next-page');

  if (btnPrev && btnPrev.dataset.bound !== 'true') {
    btnPrev.onclick = () => {
      if (currentPage > 1) {
        currentPage--;
        renderChallenges(filteredChallenges.length ? filteredChallenges : allChallenges);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
    btnPrev.dataset.bound = 'true';
  }
  if (btnNext && btnNext.dataset.bound !== 'true') {
    btnNext.onclick = () => {
      const list = filteredChallenges.length ? filteredChallenges : allChallenges;
      const totalPages = Math.ceil(list.length / itemsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderChallenges(list);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
    btnNext.dataset.bound = 'true';
  }

  if (searchInput && searchInput.dataset.bound !== 'true') {
    searchInput.addEventListener('input', filterChallenges);
    searchInput.dataset.bound = 'true';
  }
  if (statusFilter && statusFilter.dataset.bound !== 'true') {
    statusFilter.addEventListener('change', filterChallenges);
    statusFilter.dataset.bound = 'true';
  }
  if (sortFilter && sortFilter.dataset.bound !== 'true') {
    sortFilter.addEventListener('change', filterChallenges);
    sortFilter.dataset.bound = 'true';
  }
  if (rankSortFilter && rankSortFilter.dataset.bound !== 'true') {
    rankSortFilter.addEventListener('change', () => {
      renderPodium();
      renderRanking();
      renderMyRank();
    });
    rankSortFilter.dataset.bound = 'true';
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

        handleJoinChallenge(btn, id);
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

  // Quand on repasse en grille/liste, masquer la vue swipe
  const btnGrid = document.getElementById('view-grid');
  if (btnGrid && btnGrid.dataset.boundSwipe !== 'true') {
    btnGrid.addEventListener('click', () => setChallengesView('grid'));
    btnGrid.dataset.boundSwipe = 'true';
  }

  // Vue swipe (Tinder) + fallback
  const btnSwipe = document.getElementById('view-swipe');
  if (btnSwipe && btnSwipe.dataset.bound !== 'true') {
    btnSwipe.addEventListener('click', () => {
      setChallengesView('swipe');
      filterChallenges();
    });
    btnSwipe.dataset.bound = 'true';
  }

  // Filtre Chips
  document.querySelectorAll('.gl-chip').forEach(chip => {
    if (chip.dataset.bound === 'true') return;
    chip.addEventListener('click', () => {
      document.querySelectorAll('.gl-chip').forEach(c => c.classList.remove('gl-chip--active'));
      chip.classList.add('gl-chip--active');
      filterChallenges();
    });
    chip.dataset.bound = 'true';
  });
  
  // Fermer modals en cliquant sur overlay
  document.querySelectorAll('.modal-overlay, .gl-modal-overlay').forEach(overlay => {
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
window.setChallengesView = setChallengesView;
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
