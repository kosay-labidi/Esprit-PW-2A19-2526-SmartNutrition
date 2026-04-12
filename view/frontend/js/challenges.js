// Module Défis Collaboratifs GaiaLumen
(function(){ console.log('🚀 Module Défis chargé'); })();

// ═══════════════════════════════════════════════════════════
// DONNÉES
// ═══════════════════════════════════════════════════════════
const sampleChallenges = [];

const sampleRankings = {
  global: [
    {rang: 1, pseudo: 'EcoWarrior',  progression: 95, steaker: 'or', points: 15200, defis_completes: 18, collection: ['bronze','argent','or','double']},
    {rang: 2, pseudo: 'GreenHero',   progression: 87, steaker: 'or', points: 13800, defis_completes: 16, collection: ['bronze','argent','or']},
    {rang: 3, pseudo: 'NatureGirl',  progression: 76, steaker: 'argent', points: 11500, defis_completes: 14, collection: ['bronze','argent']},
    {rang: 4, pseudo: 'EcoFriend',   progression: 68, steaker: 'argent', points: 9800, defis_completes: 12, collection: ['bronze','argent']},
    {rang: 5, pseudo: 'GreenLife',   progression: 54, steaker: 'bronze', points: 7200, defis_completes: 9, collection: ['bronze']},
    {rang: 6, pseudo: 'PlantLover',  progression: 48, steaker: 'bronze', points: 6100, defis_completes: 8, collection: ['bronze']},
    {rang: 7, pseudo: 'EcoMaster',   progression: 42, steaker: 'bronze', points: 5400, defis_completes: 7, collection: ['bronze']},
    {rang: 8, pseudo: 'GreenQueen',  progression: 38, steaker: 'bronze', points: 4800, defis_completes: 6, collection: ['bronze']},
    {rang: 9, pseudo: 'NatureBoy',   progression: 35, steaker: 'bronze', points: 4200, defis_completes: 5, collection: ['bronze']},
    {rang: 10, pseudo: 'EcoStart',   progression: 32, steaker: 'bronze', points: 3800, defis_completes: 4, collection: ['bronze']},
    {rang: 11, pseudo: 'Vous',       progression: 28, steaker: 'aucun', points: 3200, defis_completes: 3, collection: ['bronze'], isUser: true}
  ],
  friends: [
    {rang: 1, pseudo: 'Lucas_Eco',   progression: 82, steaker: 'argent', points: 12400, defis_completes: 15, collection: ['bronze','argent','or']},
    {rang: 2, pseudo: 'Sarah_Green', progression: 75, steaker: 'argent', points: 10800, defis_completes: 13, collection: ['bronze','argent']},
    {rang: 3, pseudo: 'Vous',        progression: 68, steaker: 'argent', points: 9200, defis_completes: 11, collection: ['bronze','argent'], isUser: true},
    {rang: 4, pseudo: 'Tom_Nature',  progression: 45, steaker: 'bronze', points: 5800, defis_completes: 7, collection: ['bronze']},
    {rang: 5, pseudo: 'Julie_Bio',   progression: 30, steaker: 'bronze', points: 3600, defis_completes: 4, collection: ['bronze']}
  ]
};

// ═══════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════
function getSteakerIcon(niveau) {
  const icons = { bronze: '🥉', argent: '🥈', or: '🥇', double: '🏆', diamond: '💎', emerald: '🟢', aucun: '⚪' };
  return icons[niveau] || '⚪';
}

function getProgressColor(p) {
  return p < 30 ? '#e74c3c' : p < 70 ? '#f39c12' : '#27ae60';
}

function getSteakerFromProgression(p) {
  if (p >= 100) return 'double';
  if (p >= 90)  return 'or';
  if (p >= 60)  return 'argent';
  if (p >= 30)  return 'bronze';
  return 'aucun';
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function showToast(message, type = 'success') {
  const t = document.createElement('div');
  const bgColor = type === 'error' ? '#e74c3c' : type === 'info' ? '#3498db' : 'var(--violet)';
  const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'i';
  t.style.cssText = `position:fixed;top:100px;right:30px;z-index:10000;background:${bgColor};color:#fff;padding:16px 24px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.5);max-width:400px;`;
  t.innerHTML = `<b>${icon}</b> ${message}`;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ═══════════════════════════════════════════════════════════
// INIT PRINCIPALE
// ═══════════════════════════════════════════════════════════
function initChallenges() {
  if (window.challengesInitialized) return;
  window.challengesInitialized = true;

  const grid    = document.getElementById('challenges-grid');
  const loading = document.getElementById('challenges-loading');
  const rankingList = document.getElementById('ranking-list');
  const myRankCard  = document.getElementById('my-ranking-card');

  if (!grid || !loading) { window.challengesInitialized = false; return; }

  // ── Rendu des cartes ──────────────────────────────────────
  function renderChallenges(list) {
    if (!list || list.length === 0) {
      grid.innerHTML = '<div class="no-challenges">Aucun défi trouvé</div>';
      return;
    }
    grid.innerHTML = list.map(c => {
      // Si c'est un objet de la DB, adapter les clés si nécessaire
      const titre = c.titre || c.nom;
      const level = (c.participants_count || 0) > 100 ? 'double' : 'bronze';
      const streakDisplay = (c.streak_current || 0) > 0 
        ? `<div class="challenge-streak">
             <div class="streak-icon-badge">${c.streak_icon || '🏆'}</div>
             <div class="streak-info">
               <div class="streak-current">Série actuelle: ${c.streak_current} jours</div>
               <div class="streak-best">Meilleure série: ${c.streak_best || 0} jours</div>
             </div>
           </div>`
        : (c.streak_best || 0) > 0
        ? `<div class="challenge-streak">
             <div class="streak-icon-badge">${c.streak_icon || '🏆'}</div>
             <div class="streak-info">
               <div class="streak-best">Meilleure série: ${c.streak_best} jours</div>
             </div>
           </div>`
        : '';
      
      const image = c.image || 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800';
      const statut = c.statut || 'actif';
      
      return `
        <div class="challenge-card" data-challenge-id="${c.id}">
          <img src="${image}" alt="${titre}" class="challenge-image" onclick="window.viewChallengeDetail(${c.id})">
          <div class="challenge-badge ${statut}">${statut.toUpperCase()}</div>
          <div class="challenge-steaker">
            <div class="steaker-3d steaker-${level} steaker-small">
              <span class="steaker-icon">${getSteakerIcon(level)}</span>
            </div>
          </div>
          <div class="challenge-content">
            <h3 class="challenge-title" onclick="window.viewChallengeDetail(${c.id})">${titre}</h3>
            <p class="challenge-description">${c.description}</p>
            ${streakDisplay}
            <div class="challenge-stats">
              <div class="challenge-stat"><span>${c.participants_count || 0} participants</span></div>
              <div class="challenge-stat"><span>Objectif: -${c.valeur_cible}%</span></div>
            </div>
            <button class="btn-participate" onclick="event.stopPropagation(); window.showInlineParticipationForm(${c.id})">
              Participer
            </button>
            
            <!-- Inline form container -->
            <div class="inline-form-container" id="inline-form-${c.id}" style="display: none;">
              <!-- Le formulaire sera inséré ici dynamiquement -->
            </div>
          </div>
        </div>`;
    }).join('');
  }

  // ── Chargement des données ────────────────────────────────
   function loadChallenges() {
     loading.style.display = 'flex';
     fetch('../backend/challenges/listChallenges.php')
       .then(response => response.json())
       .then(data => {
         renderChallenges(data);
         loading.style.display = 'none';
       })
       .catch(err => {
         console.error('Erreur lors du chargement des défis:', err);
         renderChallenges([]);
         loading.style.display = 'none';
       });
   }

  // ── Classement ────────────────────────────────────────────
  function renderRanking(type = 'global') {
    if (!rankingList) return;
    rankingList.innerHTML = (sampleRankings[type] || []).map(r => `
      <div class="ranking-item ${r.isUser ? 'current-user' : ''}">
        <div class="ranking-position ${r.rang <= 3 ? 'top3' : ''}">${r.rang}</div>
        <div class="ranking-info">
          <div class="ranking-name">${r.pseudo}</div>
          <div class="ranking-progress-bar">
            <div class="ranking-progress-fill" style="width:${r.progression}%;background:${getProgressColor(r.progression)}"></div>
          </div>
        </div>
        <div class="ranking-steaker">${getSteakerIcon(r.steaker)}</div>
      </div>`).join('');
  }

  function renderMyRank() {
    if (!myRankCard) return;
    const me = sampleRankings.global.find(r => r.isUser);
    if (!me) return;
    myRankCard.innerHTML = `
      <div class="my-ranking-rank">#${me.rang}</div>
      <div class="my-ranking-details">
        <div class="my-ranking-name">VOUS</div>
        <div class="ranking-progress-bar">
          <div class="ranking-progress-fill" style="width:${me.progression}%;background:${getProgressColor(me.progression)}"></div>
        </div>
      </div>
      <div class="ranking-steaker">${getSteakerIcon(me.steaker)}</div>`;
  }

  // ── Onglets classement ────────────────────────────────────
  document.querySelectorAll('.ranking-tab').forEach(tab => {
    tab.onclick = e => {
      e.preventDefault();
      document.querySelectorAll('.ranking-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      renderRanking(tab.dataset.type);
    };
  });

  // ── Filtre recherche ──────────────────────────────────────
  const searchInput = document.getElementById('challenge-search');
  if (searchInput) {
    searchInput.oninput = () => {
      const val = searchInput.value.toLowerCase();
      renderChallenges(sampleChallenges.filter(c =>
        c.titre.toLowerCase().includes(val) || c.description.toLowerCase().includes(val)
      ));
    };
  }

  // ── Filtre statut ─────────────────────────────────────────
  const statusFilter = document.getElementById('challenge-status-filter');
  if (statusFilter) {
    statusFilter.onchange = () => {
      const val = statusFilter.value;
      renderChallenges(val ? sampleChallenges.filter(c => c.statut === val) : sampleChallenges);
    };
  }

  // ── Bouton refresh ────────────────────────────────────────
  const refreshBtn = document.getElementById('challenge-refresh');
  if (refreshBtn) {
    refreshBtn.onclick = () => {
      grid.style.display = 'none';
      loadChallenges();
      grid.style.display = 'grid';
    };
  }

  // ── Chargement initial ────────────────────────────────────
  renderRanking('global');
  renderMyRank();
  loadChallenges();
  grid.style.display = 'grid';
}

// ═══════════════════════════════════════════════════════════
// INLINE PARTICIPATION FORM FUNCTIONS
// ═══════════════════════════════════════════════════════════

// Helper: Close all inline forms
function hideAllInlineForms() {
  if (window.activeInlineForm) {
    window.hideInlineParticipationForm(window.activeInlineForm);
  }
}

// Helper: Check if user is already participating
function isUserParticipating(challengeId) {
  // Stub implementation - to be replaced with real user data check
  return false;
}

// Helper: Generate inline form HTML
function generateInlineFormHTML(challenge, steaker) {
  return `
    <div class="inline-participation-form">
      <div class="inline-form-header">
        <div class="inline-form-context">
          <h4 class="inline-form-title">${challenge.titre}</h4>
          <div class="inline-form-meta">
            <span>Objectif: -${challenge.valeur_cible}%</span>
            <span>${getSteakerIcon(steaker)}</span>
            <span>${challenge.streak_icon} Défi</span>
          </div>
        </div>
        <button class="inline-form-close" 
                onclick="window.hideInlineParticipationForm(${challenge.id})"
                title="Fermer">
          ×
        </button>
      </div>
      
      <form id="inline-participation-form-${challenge.id}" 
            onsubmit="window.handleInlineParticipationSubmit(event, ${challenge.id})">
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" name="email" class="form-input" placeholder="votre@email.com" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">Nom complet <span class="required">*</span></label>
            <input type="text" name="nom" class="form-input" placeholder="Ex: Jean Dupont" required>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">
            Objectif personnel (%) <span class="required">*</span>
          </label>
          <div class="slider-container">
            <input type="range" name="objectif" class="form-slider" min="1" max="100" value="50" 
                   oninput="document.getElementById('objectif-value-${challenge.id}').textContent = this.value + '%'" required>
            <div class="slider-value" id="objectif-value-${challenge.id}">50%</div>
          </div>
          <p class="form-help">Quel pourcentage de réduction visez-vous?</p>
        </div>
        
        <div class="form-group">
          <label class="form-label">
            Motivation <span class="required">*</span>
            <span class="char-count" id="inline-motivation-count-${challenge.id}">0/500</span>
          </label>
          <textarea
            name="motivation"
            class="form-textarea"
            placeholder="Pourquoi souhaitez-vous participer à ce défi?"
            maxlength="500"
            rows="3"
            required
            oninput="window.updateCharCount(this, 'inline-motivation-count-${challenge.id}')"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Première action concrète <span class="required">*</span></label>
          <textarea
            name="action"
            class="form-textarea"
            placeholder="Quelle sera votre première action pour ce défi?"
            rows="2"
            required
          ></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="engagement" required>
            <span class="checkbox-label">
              Je m'engage à participer activement à ce défi
            </span>
          </label>
        </div>
        
        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="notifications">
            <span class="checkbox-label">
              Recevoir des notifications de motivation
            </span>
          </label>
        </div>
        
        <div class="form-actions">
          <button type="button" 
                  class="btn-secondary btn-inline" 
                  onclick="window.hideInlineParticipationForm(${challenge.id})">
            Annuler
          </button>
          <button type="submit" class="btn-primary btn-inline">
            Confirmer
          </button>
        </div>
      </form>
    </div>
  `;
}

// Main function: Show inline participation form
window.showInlineParticipationForm = function(challengeId) {
  // 1. Close any currently open inline forms
  hideAllInlineForms();
  
  // 2. Retrieve challenge data
  const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
  if (!challenge) {
    showToast('Défi introuvable', 'error');
    return;
  }
  
  // 3. Validate challenge eligibility - status check
  if (challenge.statut !== 'actif') {
    showToast('Ce défi n\'est pas disponible pour la participation', 'info');
    return;
  }
  
  // 4. Get container and card elements
  const container = document.getElementById(`inline-form-${challengeId}`);
  const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
  
  if (!container || !card) return;
  
  // 5. Check if user is already participating
  if (isUserParticipating(challengeId)) {
    showToast('Vous participez déjà à ce défi', 'info');
    return;
  }
  
  // 6. Generate form HTML using helper function
  const steaker = getSteakerFromProgression(65);
  container.innerHTML = generateInlineFormHTML(challenge, steaker);
  
  // 7. Insert form into inline container and animate expansion with CSS transitions
  container.style.display = 'block';
  container.style.maxHeight = '0px';
  container.style.opacity = '0';
  
  // Force reflow for animation
  container.offsetHeight;
  
  // Animate expansion
  container.style.transition = 'max-height 0.3s ease-out, opacity 0.3s ease-out';
  container.style.maxHeight = '1200px';
  container.style.opacity = '1';
  
  // 8. Add form-expanded class to card
  card.classList.add('form-expanded');
  
  // 9. Scroll card into view if needed
  setTimeout(() => {
    const rect = card.getBoundingClientRect();
    if (rect.top < 0 || rect.bottom > window.innerHeight) {
      card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }, 100);
  
  // 10. Focus first input field
  setTimeout(() => {
    const firstInput = container.querySelector('input[name="nom"]');
    if (firstInput) firstInput.focus();
  }, 350);
  
  // 11. Track active form state
  window.activeInlineForm = challengeId;
};

// Function to hide inline participation form
window.hideInlineParticipationForm = function(challengeId) {
  const container = document.getElementById(`inline-form-${challengeId}`);
  const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
  
  if (!container || !card) return;
  
  // Animate form collapse with CSS transitions
  container.style.transition = 'max-height 0.3s ease-in, opacity 0.3s ease-in';
  container.style.maxHeight = '0px';
  container.style.opacity = '0';
  
  // Remove form-expanded class and clean up DOM after animation completes
  setTimeout(() => {
    container.style.display = 'none';
    card.classList.remove('form-expanded');
    
    // Clear active form state
    if (window.activeInlineForm === challengeId) {
      window.activeInlineForm = null;
    }
  }, 300);
};

// Function to handle inline participation form submission
window.handleInlineParticipationSubmit = function(event, challengeId) {
  event.preventDefault();
  
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // Disable submit button and show loading state
  submitBtn.disabled = true;
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = 'Envoi en cours...';
  
  // Extract form data using FormData API
  const formData = new FormData(form);
  const data = {
    nom: formData.get('nom'),
    email: formData.get('email'),
    objectif: parseInt(formData.get('objectif')),
    motivation: formData.get('motivation'),
    action: formData.get('action'),
    engagement: formData.get('engagement') === 'on',
    notifications: formData.get('notifications') === 'on'
  };
  
  // Call validation function
  if (!validateParticipationData(data)) {
    // Re-enable submit button on validation failure
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
    return;
  }
  
  // Simulate API call (to be replaced with real API call)
  // POST /api/challenges/:id/participate
  setTimeout(() => {
    // Simulate success response
    const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
    
    if (challenge) {
      // Update challenge participant count
      challenge.participants_count++;
      
      // Show success toast message with challenge icon
      showToast(`Félicitations ${data.nom}! Vous participez maintenant au défi "${challenge.titre}" ${challenge.streak_icon}`, 'success');
      
      // Close form on success
      window.hideInlineParticipationForm(challengeId);
      
      // Update challenge card participant count
      setTimeout(() => {
        updateChallengeCard(challengeId);
      }, 350);
    } else {
      // Show error toast if challenge not found
      showToast('Erreur: Défi introuvable', 'error');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  }, 800);
};

// Helper function to validate participation data
function validateParticipationData(data) {
  // Validate nom (min 2 characters)
  if (!data.nom || data.nom.trim().length < 2) {
    showToast('Le nom doit contenir au moins 2 caractères', 'error');
    return false;
  }
  
  // Validate email format
  if (!data.email || !isValidEmail(data.email)) {
    showToast('Email invalide', 'error');
    return false;
  }
  
  // Validate objectif (1-100 range)
  if (!data.objectif || data.objectif < 1 || data.objectif > 100) {
    showToast('L\'objectif doit être entre 1 et 100%', 'error');
    return false;
  }
  
  // Validate motivation (min 10 characters)
  if (!data.motivation || data.motivation.trim().length < 10) {
    showToast('La motivation doit contenir au moins 10 caractères', 'error');
    return false;
  }
  
  // Validate action (min 5 characters)
  if (!data.action || data.action.trim().length < 5) {
    showToast('L\'action doit contenir au moins 5 caractères', 'error');
    return false;
  }
  
  // Validate engagement checkbox
  if (!data.engagement) {
    showToast('Vous devez accepter l\'engagement', 'error');
    return false;
  }
  
  return true;
}

// Helper function to validate email format
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Helper function to update challenge card display
function updateChallengeCard(challengeId) {
  const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
  if (!challenge) return;
  
  const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
  if (!card) return;
  
  // Update participant count display
  const participantsSpan = card.querySelector('.challenge-stat span:last-child');
  if (participantsSpan && participantsSpan.textContent.includes('participants')) {
    participantsSpan.textContent = `${challenge.participants_count} participants`;
  }
}

// ═══════════════════════════════════════════════════════════
// FORMULAIRE DE PARTICIPATION (bouton "Participer")
// ═══════════════════════════════════════════════════════════
window.showParticipationForm = function(challengeId) {
  const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
  if (!challenge) return;

  const modal = document.getElementById('challenge-modal');
  const body  = document.getElementById('challenge-modal-body');
  if (!modal || !body) return;

  const steaker = getSteakerFromProgression(65);
  modal.classList.add('active');

  body.innerHTML = `
    <button onclick="window.closeChallengeModal()" class="modal-close" title="Fermer">×</button>

    <div class="participation-layout">

      <!-- ── Colonne gauche : infos du défi ── -->
      <div class="participation-challenge-info">
        <div class="challenge-info-header" style="background-image:url('${challenge.image}')">
          <div class="challenge-info-overlay">
            <h2>${challenge.titre}</h2>
            <span class="challenge-badge ${challenge.statut}">${challenge.statut.toUpperCase()}</span>
          </div>
        </div>

        <div class="challenge-info-content">
          <div class="info-section">
            <h3>Description</h3>
            <p>${challenge.description}</p>
          </div>
          <div class="info-section">
            <h3>Objectif</h3>
            <p>Réduire de <strong>${challenge.valeur_cible}%</strong></p>
          </div>
          <div class="info-section">
            <h3>Période</h3>
            <p>Du ${formatDate(challenge.date_debut)} au ${formatDate(challenge.date_fin)}</p>
          </div>
          <div class="info-section">
            <h3>Participants</h3>
            <p><strong>${challenge.participants_count}</strong> personnes participent déjà</p>
          </div>
          <div class="info-section">
            <h3>Récompense</h3>
            <div class="reward-showcase">
              <div class="steaker-3d steaker-${steaker} steaker-medium">
                <span class="steaker-icon">${getSteakerIcon(steaker)}</span>
              </div>
              <p>Gagnez ce steaker en complétant le défi!</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Colonne droite : formulaire ── -->
      <div class="participation-form-container">
        <div class="form-header">
          <h3>Rejoindre le défi</h3>
          <p>Remplissez ce formulaire pour participer</p>
        </div>

        <form id="participation-form-${challengeId}" onsubmit="window.handleParticipationSubmit(event, ${challengeId})">

          <div class="form-group">
            <label class="form-label">Nom complet <span class="required">*</span></label>
            <input type="text" name="nom" class="form-input" placeholder="Ex: Jean Dupont" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" name="email" class="form-input" placeholder="votre@email.com" required>
          </div>

          <div class="form-group">
            <label class="form-label">
              Objectif personnel (%) <span class="required">*</span>
            </label>
            <input type="number" name="objectif" class="form-input" min="1" max="100" placeholder="Ex: 60" required>
            <p class="form-help">Quel pourcentage de réduction visez-vous?</p>
          </div>

          <div class="form-group">
            <label class="form-label">
              Motivation <span class="required">*</span>
              <span class="char-count" id="motivation-count-${challengeId}">0/500</span>
            </label>
            <textarea
              name="motivation"
              class="form-textarea"
              placeholder="Pourquoi souhaitez-vous participer à ce défi?"
              maxlength="500"
              rows="4"
              required
              oninput="window.updateCharCount(this, 'motivation-count-${challengeId}')"
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Première action concrète <span class="required">*</span></label>
            <textarea
              name="action"
              class="form-textarea"
              placeholder="Quelle sera votre première action pour ce défi?"
              rows="3"
              required
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-checkbox">
              <input type="checkbox" name="engagement" required>
              <span class="checkbox-label">
                Je m'engage à participer activement à ce défi et à suivre ma progression régulièrement.
              </span>
            </label>
          </div>

          <div class="form-group">
            <label class="form-checkbox">
              <input type="checkbox" name="notifications">
              <span class="checkbox-label">
                Je souhaite recevoir des notifications pour rester motivé(e).
              </span>
            </label>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="window.closeChallengeModal()">
              Annuler
            </button>
            <button type="submit" class="btn-primary">
              Confirmer ma participation
            </button>
          </div>

        </form>
      </div>
    </div>`;
};

// Compteur de caractères
window.updateCharCount = function(textarea, counterId) {
  const el = document.getElementById(counterId);
  if (el) el.textContent = `${textarea.value.length}/500`;
};

// Soumission du formulaire
window.handleParticipationSubmit = function(event, challengeId) {
  event.preventDefault();
  const fd = new FormData(event.target);
  const nom = fd.get('nom');

  const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
  if (challenge) {
    challenge.participants_count++;
    showToast(`Félicitations ${nom}! Vous participez maintenant au défi "${challenge.titre}"`, 'success');
    window.closeChallengeModal();
    // Rafraîchir la grille
    setTimeout(() => {
      window.challengesInitialized = false;
      initChallenges();
    }, 400);
  }
};

// ═══════════════════════════════════════════════════════════
// AUTRES MODALS
// ═══════════════════════════════════════════════════════════
window.viewChallengeDetail = function(challengeId) {
  const challenge = sampleChallenges.find(c => c.id === parseInt(challengeId));
  if (!challenge) return;

  const modal = document.getElementById('challenge-modal');
  const body  = document.getElementById('challenge-modal-body');
  if (!modal || !body) return;

  const steaker = getSteakerFromProgression(65);
  modal.classList.add('active');

  body.innerHTML = `
    <button onclick="window.closeChallengeModal()" class="modal-close" title="Fermer">×</button>
    <div style="padding:32px;">
      <div style="position:relative;height:220px;background:url('${challenge.image}') center/cover;border-radius:16px;overflow:hidden;margin-bottom:24px;">
        <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.2),rgba(0,0,0,.7));display:flex;align-items:flex-end;padding:24px;">
          <h2 style="color:#fff;font-size:2rem;margin:0;">${challenge.titre}</h2>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;padding:20px;background:rgba(91,62,150,.1);border-radius:16px;">
        <div class="steaker-3d steaker-${steaker} steaker-large"><span class="steaker-icon">${getSteakerIcon(steaker)}</span></div>
        <div>
          <h4 style="margin:0 0 6px;">Récompense 3D</h4>
          <p style="margin:0;color:var(--muted);">Gagnez ce trophée en complétant le défi!</p>
        </div>
      </div>
      <p style="color:var(--muted);line-height:1.7;margin-bottom:24px;">${challenge.description}</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:32px;">
        <div style="background:var(--glass);padding:16px;border-radius:12px;text-align:center;">
          <div style="font-size:1.4rem;font-weight:800;color:var(--blue);">${challenge.participants_count}</div>
          <div style="font-size:0.8rem;color:var(--muted);">Participants</div>
        </div>
        <div style="background:var(--glass);padding:16px;border-radius:12px;text-align:center;">
          <div style="font-size:1.4rem;font-weight:800;color:var(--blue);">-${challenge.valeur_cible}%</div>
          <div style="font-size:0.8rem;color:var(--muted);">Objectif</div>
        </div>
      </div>
      <button class="btn-primary" style="width:100%;padding:14px;font-size:1.1rem;"
        onclick="window.closeChallengeModal(); setTimeout(()=>window.showParticipationForm(${challengeId}),200)">
        Participer à ce défi
      </button>
    </div>`;
};

// ═══════════════════════════════════════════════════════════
// FERMETURE MODALS
// ═══════════════════════════════════════════════════════════
window.closeChallengeModal    = () => document.getElementById('challenge-modal').classList.remove('active');
window.closeUserProfileModal  = () => document.getElementById('user-profile-modal').classList.remove('active');

// Fermer en cliquant sur l'overlay
document.addEventListener('click', e => {
  if (e.target.id === 'challenge-modal')    window.closeChallengeModal();
  if (e.target.id === 'user-profile-modal') window.closeUserProfileModal();
});

// ═══════════════════════════════════════════════════════════
// KEYBOARD NAVIGATION
// ═══════════════════════════════════════════════════════════
// Escape key handler for closing inline forms
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && window.activeInlineForm) {
    window.hideInlineParticipationForm(window.activeInlineForm);
  }
});

// ═══════════════════════════════════════════════════════════
// DÉMARRAGE
// ═══════════════════════════════════════════════════════════
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initChallenges);
} else {
  initChallenges();
}

document.addEventListener('moduleLoaded', e => {
  if (e.detail && e.detail.moduleName === 'challenges') {
    window.challengesInitialized = false;
    initChallenges();
  }
});
