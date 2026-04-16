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
  console.log('🔔 Event adminModuleLoaded reçu pour:', e.detail.moduleName);
  if (e.detail.moduleName === 'challenges') {
    console.log('📦 Module Challenges détecté, initialisation forcée...');
    
    // Un petit délai pour s'assurer que le DOM est injecté
    setTimeout(() => {
      if (document.getElementById('challenges')) {
        console.log('✅ Element #challenges présent dans le DOM');
        initChallengeForm();
        loadAdminChallenges();
        loadAdminParticipants();
        setupRippleEffect();
      } else {
        console.error('❌ Element #challenges NON TROUVÉ après adminModuleLoaded');
      }
    }, 50);
  }
});

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

    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';

    setTimeout(() => ripple.remove(), 600);
  });
}

// Au cas où le script est chargé après le module
if (document.getElementById('challenges')) {
  initChallengeForm();
  loadAdminChallenges();
  loadAdminParticipants();
}

function initChallengeForm() {
  const form = document.getElementById('challenge-form');
  if (form) {
    // Ajouter la validation en temps réel
    setupRealTimeValidation(form);
    
    // Initialiser l'aperçu
    updatePreview();

    form.onsubmit = function(e) {
      e.preventDefault();
      
      // Nettoyer les erreurs précédentes
      clearErrors();

      // Valider le formulaire
      if (!validateChallengeForm()) {
        console.warn('⚠️ Validation échouée');
        return;
      }

      const formData = new FormData(form);
      const challengeId = formData.get('id');
      const isUpdate = challengeId && challengeId !== '';
      const url = isUpdate ? 'challenges/updateChallenge.php?id=' + challengeId : 'challenges/addChallenge.php';
      
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
          
          const title = isUpdate ? 'Défi modifié' : 'Défi créé';
          const msg = isUpdate ? 'Le défi a été mis à jour avec succès.' : 'Le nouveau défi est maintenant en ligne.';
          showToast(title, msg, 'success');
        } else {
          showToast('Erreur', result.message || 'Erreur lors de l\'enregistrement', 'error');
        }
      })
      .catch(err => {
        console.error('Erreur AJAX:', err);
        showToast('Erreur serveur', 'Impossible de se connecter au serveur', 'error');
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
      });
    };
  }
}

// ── Validation du formulaire ───────────────────────────────

function setupRealTimeValidation(form) {
  const inputs = form.querySelectorAll('input, textarea, select');
  inputs.forEach(input => {
    input.addEventListener('input', () => {
      validateField(input);
      
      // Re-valider l'autre date si l'une change
      if (input.id === 'challenge-date-debut') {
        validateField(document.getElementById('challenge-date-fin'));
      } else if (input.id === 'challenge-date-fin') {
        validateField(document.getElementById('challenge-date-debut'));
      }
    });
    input.addEventListener('change', () => {
      validateField(input);
      
      // Même chose pour le changement (pour les navigateurs qui ne tirent pas input sur date)
      if (input.id === 'challenge-date-debut') {
        validateField(document.getElementById('challenge-date-fin'));
      } else if (input.id === 'challenge-date-fin') {
        validateField(document.getElementById('challenge-date-debut'));
      }
    });
  });
}

function validateField(input) {
  if (!input) return true;
  const id = input.id;
  const value = input.value ? input.value.trim() : '';
  let isValid = true;
  let errorMsg = '';

  // Reset state
  input.classList.remove('invalid');
  const errorSpan = document.getElementById(`error-${id.replace('challenge-', '')}`);
  if (errorSpan) errorSpan.innerText = '';

  // Specific rules
  if (input.required && !value) {
    isValid = false;
    errorMsg = 'Ce champ est obligatoire';
  } else if (value) {
    switch (id) {
      case 'challenge-titre':
        if (value.length < 3) {
          isValid = false;
          errorMsg = 'Le titre doit faire au moins 3 caractères';
        }
        break;
      case 'challenge-description':
        if (value.length < 10) {
          isValid = false;
          errorMsg = 'La description doit faire au moins 10 caractères';
        }
        break;
      case 'challenge-valeur':
        const val = parseInt(value);
        if (isNaN(val) || val < 1 || val > 100) {
          isValid = false;
          errorMsg = 'L\'objectif doit être entre 1 et 100%';
        }
        break;
      case 'challenge-date-debut':
        const endVal = document.getElementById('challenge-date-fin').value;
        const startDate = new Date(value);
        const endDateFromStart = new Date(endVal);
        if (value && endVal && !isNaN(startDate) && !isNaN(endDateFromStart)) {
          if (startDate.getTime() > endDateFromStart.getTime()) {
            isValid = false;
            errorMsg = 'La date de début ne peut pas être après la date de fin';
          }
        }
        break;
      case 'challenge-date-fin':
        const startVal = document.getElementById('challenge-date-debut').value;
        const endDate = new Date(value);
        const startDateFromEnd = new Date(startVal);
        if (value && startVal && !isNaN(endDate) && !isNaN(startDateFromEnd)) {
          if (endDate.getTime() < startDateFromEnd.getTime()) {
            isValid = false;
            errorMsg = 'La date de fin ne peut pas être avant la date de début';
          }
        }
        break;
      case 'challenge-image':
        if (value && !isValidUrl(value)) {
          isValid = false;
          errorMsg = 'Veuillez entrer une URL valide (http/https)';
        }
        break;
    }
  }

  if (!isValid) {
    input.classList.add('invalid');
    if (errorSpan) errorSpan.innerText = errorMsg;
  }

  return isValid;
}

function validateChallengeForm() {
  const form = document.getElementById('challenge-form');
  const inputs = form.querySelectorAll('input[required], textarea[required], select[required], #challenge-image');
  let isFormValid = true;

  inputs.forEach(input => {
    if (!validateField(input)) {
      isFormValid = false;
    }
  });

  return isFormValid;
}

function showError(fieldSuffix, message) {
  const input = document.getElementById(`challenge-${fieldSuffix}`);
  const errorSpan = document.getElementById(`error-${fieldSuffix}`);
  if (input) input.classList.add('invalid');
  if (errorSpan) errorSpan.innerText = message;
}

function clearErrors() {
  const inputs = document.querySelectorAll('.form-input, .form-textarea, .form-select');
  inputs.forEach(input => input.classList.remove('invalid'));
  
  const errorSpans = document.querySelectorAll('.error-msg');
  errorSpans.forEach(span => span.innerText = '');
}

function isValidUrl(string) {
  try {
    const url = new URL(string);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch (_) {
    return false;
  }
}

// ── Chargement des données (AFFICHER) ────────────────────────
function loadAdminChallenges() {
  console.log('🔍 Tentative de chargement des défis...');
  fetch('challenges/listChallenges.php?ajax=1&t=' + Date.now(), {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => {
      console.log('📡 Réponse reçue de listChallenges.php:', response.status);
      const contentType = response.headers.get("content-type");
      if (!response.ok) throw new Error('Erreur HTTP: ' + response.status);
      if (!contentType || !contentType.includes("application/json")) {
        return response.text().then(text => {
          console.error('❌ Réponse non-JSON reçue:', text.substring(0, 200));
          throw new Error('Réponse serveur invalide (pas du JSON)');
        });
      }
      return response.json();
    })
    .then(data => {
      console.log('📦 Données des défis reçues:', data);
      adminChallenges = Array.isArray(data) ? data : [];
      renderChallengesTable();
      updateDashboardStats();
      renderParticipantsChallengeFilter();
    })
    .catch(err => {
      console.error('❌ Erreur lors du chargement des défis admin:', err);
      const tbody = document.getElementById('challenges-tbody');
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:#f44336;">
          <div style="font-size:2rem;margin-bottom:10px;">⚠️</div>
          <div>Erreur de chargement: ${err.message}</div>
          <button onclick="loadAdminChallenges()" style="margin-top:10px;padding:5px 15px;background:#f44336;color:white;border:none;border-radius:5px;cursor:pointer;">Réessayer</button>
        </td></tr>`;
      }
    });
}

function loadAdminParticipants() {
  console.log('🔍 Tentative de chargement des participants...');
  fetch('challenges/showParticipant.php?ajax=1&t=' + Date.now(), {
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => {
      console.log('📡 Réponse reçue de showParticipant.php:', response.status);
      const contentType = response.headers.get("content-type");
      if (!response.ok) throw new Error('Erreur HTTP: ' + response.status);
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error('Réponse serveur invalide (pas du JSON)');
      }
      return response.json();
    })
    .then(result => {
      console.log('📦 Données des participants reçues:', result);
      adminParticipants = (result && Array.isArray(result.participants)) ? result.participants : [];
      renderParticipantsTable();
      updateParticipantsStats();
      updateDashboardStats();
    })
    .catch(err => {
      console.error('❌ Erreur lors du chargement des participants admin:', err);
      const tbody = document.getElementById('participants-tbody');
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#f44336;">
          <div style="font-size:2rem;margin-bottom:10px;">⚠️</div>
          <div>Erreur de chargement: ${err.message}</div>
        </td></tr>`;
      }
      adminParticipants = [];
      renderParticipantsTable();
      updateParticipantsStats();
    });
}

// Exposer globalement pour le débogage console
window.loadAdminChallenges = loadAdminChallenges;
window.loadAdminParticipants = loadAdminParticipants;
window.renderChallengesTable = renderChallengesTable;
window.adminChallenges = () => adminChallenges;
window.adminParticipants = () => adminParticipants;

// ═══════════════════════════════════════════════════════════
// FONCTIONS PRINCIPALES
// ═══════════════════════════════════════════════════════════

function resetForm() {
  const form = document.getElementById('challenge-form');
  if (form) {
    form.reset();
    const idField = document.getElementById('challenge-id');
    if (idField) idField.value = '';
    document.getElementById('form-title').innerHTML = '<span>➕</span> Nouveau Défi';
    const submitBtn = document.getElementById('form-submit-btn');
    if (submitBtn) submitBtn.innerText = '🚀 Publier le Défi';
    updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);
    clearErrors();
    updatePreview();
  }
}

function updateCharCountAdmin(textarea, displayId, max) {
  if (!textarea) return;
  const count = textarea.value.length;
  const display = document.getElementById(displayId);
  const progress = document.getElementById('char-count-progress');
  
  if (display) display.innerText = `${count}/${max}`;
  
  if (progress) {
    const percentage = (count / max) * 100;
    progress.style.width = `${percentage}%`;
    
    // Color coding
    if (percentage > 90) progress.style.background = '#f44336';
    else if (percentage > 70) progress.style.background = '#ff9800';
    else progress.style.background = '#A8B8A0';
  }
}

function updatePreview() {
  const titre = document.getElementById('challenge-titre')?.value || 'Titre du défi';
  const desc = document.getElementById('challenge-description')?.value || 'La description apparaîtra ici...';
  const type = document.querySelector('input[name="type"]:checked')?.value || 'collectif';
  const categorySelect = document.getElementById('challenge-objectif');
  const category = categorySelect?.options[categorySelect.selectedIndex]?.text || 'Catégorie';
  const target = document.getElementById('challenge-valeur')?.value || '50';
  const dateFin = document.getElementById('challenge-date-fin')?.value || '-';
  const icon = document.getElementById('challenge-streak-icon')?.value || '♻️';
  const image = document.getElementById('challenge-image')?.value;

  // Update Preview Card
  const previewTitle = document.getElementById('preview-title');
  if (previewTitle) previewTitle.innerText = titre;
  
  const previewDesc = document.getElementById('preview-desc');
  if (previewDesc) previewDesc.innerText = desc;
  
  const previewTypeLabel = document.getElementById('preview-type-label');
  if (previewTypeLabel) previewTypeLabel.innerText = (type === 'collectif' ? '👥' : '👤') + ' ' + type.charAt(0).toUpperCase() + type.slice(1);
  
  const previewCategory = document.getElementById('preview-category');
  if (previewCategory) previewCategory.innerText = category.replace(/[^\w\s]/gi, '').trim(); // Remove emoji from text
  
  const previewTarget = document.getElementById('preview-target');
  if (previewTarget) previewTarget.innerText = target + '%';
  
  const previewDate = document.getElementById('preview-date');
  if (previewDate) previewDate.innerText = dateFin !== '-' ? new Date(dateFin).toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'}) : '-';
  
  const previewIcon = document.getElementById('preview-icon');
  if (previewIcon) previewIcon.innerText = icon;

  const imgContainer = document.getElementById('preview-img-container');
  if (imgContainer) {
    if (image && isValidUrl(image)) {
      imgContainer.style.backgroundImage = `url('${image}')`;
      imgContainer.style.backgroundSize = 'cover';
      imgContainer.style.backgroundPosition = 'center';
      if (previewIcon) previewIcon.style.opacity = '0.3';
    } else {
      imgContainer.style.backgroundImage = 'none';
      if (previewIcon) previewIcon.style.opacity = '1';
    }
  }
}

function isValidUrl(string) {
  try {
    const url = new URL(string);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch (_) {
    return false;  
  }
}

// ═══════════════════════════════════════════════════════════
// ACTIONS CRUD
// ═══════════════════════════════════════════════════════════

function editChallenge(id) {
  const challenge = adminChallenges.find(c => String(c.id) === String(id));
  if (!challenge) return;
  
  // Remplir le formulaire avec les données
  document.getElementById('challenge-id').value = challenge.id;
  document.getElementById('challenge-titre').value = challenge.titre;
  document.getElementById('challenge-description').value = challenge.description;
  
  // Radio buttons for type
  const typeRadios = document.querySelectorAll('input[name="type"]');
  typeRadios.forEach(radio => {
    radio.checked = radio.value === challenge.type;
  });

  document.getElementById('challenge-objectif').value = challenge.objectif;
  document.getElementById('challenge-valeur').value = challenge.valeur_cible;
  document.getElementById('challenge-date-debut').value = challenge.date_debut;
  document.getElementById('challenge-date-fin').value = challenge.date_fin;
  document.getElementById('challenge-statut').value = challenge.statut;
  document.getElementById('challenge-streak-icon').value = challenge.streak_icon;
  document.getElementById('challenge-image').value = challenge.image;
  
  // Mettre à jour le compteur de caractères
  updateCharCountAdmin(document.getElementById('challenge-description'), 'description-count', 500);

  // Changer le titre et le bouton du formulaire
  document.getElementById('form-title').innerHTML = '<span>✏️</span> Modifier le Défi';
  document.getElementById('form-submit-btn').innerText = '💾 Enregistrer les modifications';
  
  // Scroll vers le formulaire
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Mettre à jour l'aperçu
  updatePreview();
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
        showToast('Défi supprimé', 'Le défi a été retiré de la plateforme.', 'success');
        loadAdminChallenges(); // Recharger le tableau
      } else {
        showToast('Erreur', result.message || 'Impossible de supprimer ce défi.', 'error');
      }
    })
    .catch(err => {
      console.error('Erreur suppression:', err);
      showToast('Erreur', 'Une erreur est survenue lors de la suppression.', 'error');
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
  if (!tbody) {
    console.warn('⚠️ Element #challenges-tbody non trouvé');
    return;
  }

  console.log('📊 Rendu du tableau avec', adminChallenges.length, 'défis');

  const searchValue = (document.getElementById('search-input-admin')?.value || '').toLowerCase();
  const statusFilter = document.getElementById('status-filter-admin')?.value || '';

  const filtered = adminChallenges.filter(c => {
    if (!c) return false;
    
    // Add safety checks for null/undefined values
    const titre = (c.titre || '').toLowerCase();
    const description = (c.description || '').toLowerCase();
    const statut = (c.statut || '').toLowerCase();

    if (searchValue && !titre.includes(searchValue) && !description.includes(searchValue)) return false;
    
    // Harmonize status filtering
    if (statusFilter) {
      let challengeStatus = statut;
      if (challengeStatus === 'en cours' || challengeStatus === 'en_cours' || challengeStatus === 'actif') challengeStatus = 'actif';
      if (challengeStatus === 'terminé' || challengeStatus === 'termine') challengeStatus = 'termine';
      if (challengeStatus === 'a venir' || challengeStatus === 'futur' || challengeStatus === 'à venir') challengeStatus = 'futur';
      
      if (challengeStatus !== statusFilter) return false;
    }
    
    return true;
  });

  if (filtered.length === 0) {
    console.log('ℹ️ Aucun défi ne correspond aux critères');
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:10px;">🏆</div>
      <div>${adminChallenges.length === 0 ? 'Aucun défi dans la base de données' : 'Aucun défi trouvé pour ces critères'}</div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(c => {
    const rawStatus = (c.statut || '').toLowerCase();
    let normalizedStatus = 'futur';
    let statusLabel = c.statut || 'À venir';

    if (rawStatus === 'actif' || rawStatus === 'en cours' || rawStatus === 'en_cours') {
      normalizedStatus = 'actif';
      statusLabel = '✅ Actif';
    } else if (rawStatus === 'termine' || rawStatus === 'terminé') {
      normalizedStatus = 'termine';
      statusLabel = '📦 Terminé';
    } else {
      normalizedStatus = 'futur';
      statusLabel = '🔜 À venir';
    }

    const statusClass = `badge-status-${normalizedStatus}`;
    const progress = 0; 
    
    return `
      <tr class="challenge-row-admin" data-id="${c.id}">
        <td>
          <div class="challenge-cell">
            <span class="challenge-icon-mini">${c.streak_icon || '🏆'}</span>
            <div class="challenge-info">
              <div class="challenge-title">${escapeHtml(c.titre || 'Sans titre')}</div>
              <div class="challenge-dates">${formatDate(c.date_debut)} - ${formatDate(c.date_fin)}</div>
            </div>
          </div>
        </td>
        <td>
          <span class="type-badge ${(c.type || '').toLowerCase() === 'collectif' ? 'type-collectif' : 'type-individuel'}">
            ${(c.type || '').toLowerCase() === 'collectif' ? '👥 Collectif' : '👤 Individuel'}
          </span>
        </td>
        <td class="target-cell">${c.valeur_cible || 0}%</td>
        <td>
          <div class="progress-container-mini">
            <div class="progress-bar-mini">
              <div class="progress-fill-mini" style="width: ${progress}%; background: ${getProgressColor(progress)};"></div>
            </div>
            <span class="progress-text-mini">${Math.round(progress)}%</span>
          </div>
        </td>
        <td>
          <span class="badge-status ${statusClass}">
            <span class="status-dot"></span>
            ${statusLabel}
          </span>
        </td>
        <td>
          <div class="table-actions">
            <button class="btn-icon edit" onclick="editChallenge(${c.id})" title="Modifier">✏️</button>
            <button class="btn-icon delete" onclick="deleteChallenge(${c.id})" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function getProgressColor(prog) {
  if (prog >= 80) return '#4CAF50';
  if (prog >= 40) return '#FFC107';
  return '#f44336';
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
  const statChallenges = document.querySelector('.metric-card:nth-child(1) .metric-value');
  const statParticipants = document.querySelector('.metric-card:nth-child(2) .metric-value');
  const statCompletion = document.querySelector('.metric-card:nth-child(3) .metric-value');
  
  if (statChallenges) statChallenges.innerText = adminChallenges.length;
  if (statParticipants) statParticipants.innerText = adminParticipants.length;
  if (statCompletion) {
    const termines = adminChallenges.filter(c => c.statut === 'termine').length;
    const rate = adminChallenges.length > 0 ? Math.round((termines / adminChallenges.length) * 100) : 0;
    if (statCompletion) statCompletion.innerText = rate + '%';
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
    updateParticipantsPagination(0, 0);
    return;
  }
  
  tbody.innerHTML = filteredParticipants.map(p => {
    const nom = String(p.nom || '');
    const email = String(p.email || '');
    const initials = nom ? nom.split(' ').filter(Boolean).map(n => n[0]).join('').slice(0, 2).toUpperCase() : '??';
    const avatarColor = getAvatarColor(nom);
    
    const prog = clampInt(p.objectif, 0, 100);
    const progColor = getProgressColor(prog);
    const challengeTarget = clampInt(p.challenge_target, 0, 100);
    const joinedDate = formatParticipantDate(p.date_inscription);
    const statusLabel = p.engagement > 0 ? '🔥 Actif' : '💤 Passif';
    
    return `
      <tr class="participant-row-admin">
        <td>
          <div class="participant-cell">
            <div class="avatar-circle" style="background: ${avatarColor}">${initials}</div>
            <div class="participant-info">
              <div class="participant-name">${escapeHtml(nom)}</div>
              <div class="participant-email">${escapeHtml(email)}</div>
            </div>
          </div>
        </td>
        <td>
          <div class="challenge-tag">
            <span class="tag-icon">${p.challenge_icon || '🏆'}</span>
            <span class="tag-text">${escapeHtml(p.challenge_titre || 'Défi')}</span>
          </div>
        </td>
        <td>
          <div class="progress-wrapper-large">
            <div class="progress-header-flex">
              <span class="progress-dot-status" style="background: ${progColor}"></span>
              <span class="progress-value-text">${prog}%</span>
            </div>
            <div class="progress-bar-large">
              <div class="progress-fill-large" style="width: ${prog}%; background: linear-gradient(90deg, #f44336, #FFC107, #4CAF50); background-size: 100px 100%;"></div>
            </div>
          </div>
        </td>
        <td>
          <div class="challenge-tag">
            <span class="tag-icon">🎯</span>
            <span class="tag-text">${challengeTarget}%</span>
          </div>
        </td>
        <td>
          <span class="participant-email">${escapeHtml(joinedDate)}</span>
        </td>
        <td>
          <span class="engagement-badge ${p.engagement > 0 ? 'engaged' : 'passive'}">
            ${statusLabel}
          </span>
        </td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon delete" onclick="deleteParticipant(${p.id}, ${p.id_challenge})" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  updateParticipantsPagination(filteredParticipants.length, filteredParticipants.length);
}

function getAvatarColor(name) {
  const colors = ['#A8B8A0', '#E8DCC4', '#4CAF50', '#8D6E63', '#607D8B'];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
}

function updateParticipantsPagination(visibleCount, totalCount = visibleCount) {
  const total = totalCount;
  const start = total > 0 ? 1 : 0;
  const end = visibleCount;
  const el = document.getElementById('participants-pagination-info');
  if (el) el.innerText = `Affichage de ${start} à ${end} sur ${total} participants`;
}

function formatParticipantDate(value) {
  if (!value) return '-';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
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
