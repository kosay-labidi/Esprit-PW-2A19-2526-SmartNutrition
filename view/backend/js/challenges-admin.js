/**
 * Module Admin Défis - GaiaLumen Backend
 * Uniquement AJOUTER et AFFICHER les défis
 */

console.log('🏆 Admin Challenges (Mode Ajout/Affichage) chargé');

let adminChallenges = [];
let adminParticipants = [];
let filteredParticipants = [];
let participantsPage = 1;
const PARTICIPANTS_PER_PAGE = 8;

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
      const isEditMode = submitBtn.getAttribute('data-mode') === 'edit';
      const originalHTML = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="btn-label">⌛ Envoi...</span>';

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
        submitBtn.innerHTML = originalHTML;
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
    if (submitBtn) {
      submitBtn.removeAttribute('data-mode');
      submitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 2 11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg><span class="btn-label">🚀 Publier le Défi</span>';
    }
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

  // Changer le titre et le bouton du formulaire (sans détruire l'icône SVG)
  document.getElementById('form-title').innerHTML = '<span>✏️</span> Modifier le Défi';
  const submitBtn2 = document.getElementById('form-submit-btn');
  if (submitBtn2) {
    submitBtn2.setAttribute('data-mode', 'edit');
    const btnText = submitBtn2.querySelector('.btn-label') || submitBtn2;
    submitBtn2.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg><span class="btn-label">💾 Enregistrer les modifications</span>';
  }
  
  // Scroll vers le formulaire (scroll le conteneur principal, pas la window)
  const mainContent = document.querySelector('.main-content');
  if (mainContent) {
    mainContent.scrollTo({ top: 0, behavior: 'smooth' });
  }
  // Scroll de secours vers l'élément du formulaire
  const formSection = document.getElementById('challenge-form');
  if (formSection) {
    setTimeout(() => formSection.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
  }

  // Mettre à jour l'aperçu
  updatePreview();
}

function deleteChallenge(id) {
  openAdmModal('⚠️ Voulez-vous vraiment supprimer ce défi ? Cette action est irréversible.', () => {
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
  });
}

// ═══════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getDaysLeft(dateFin) {
  const diff = new Date(dateFin) - new Date();
  const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
  if (days < 0) return `<span class="adm-days adm-days--over">Expiré</span>`;
  if (days === 0) return `<span class="adm-days adm-days--today">Aujourd'hui</span>`;
  if (days <= 7) return `<span class="adm-days adm-days--soon">${days}j</span>`;
  return `<span class="adm-days">${days}j</span>`;
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
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">
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

    const statusMap = {
      'actif': { label: '✅ Actif', cls: 'adm-badge--green' },
      'termine': { label: '📦 Terminé', cls: 'adm-badge--grey' },
      'futur': { label: '🔜 À venir', cls: 'adm-badge--blue' }
    };
    const s = statusMap[normalizedStatus] || { label: c.statut || '—', cls: '' };
    const target = parseInt(c.valeur_cible || 0, 10);
    const participantsCount = parseInt(c.participants_count || 0, 10);
    const pct = target > 0 ? Math.min(100, Math.round((participantsCount / target) * 100)) : 0;
    
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
          <div class="adm-prog-wrap">
            <div class="adm-prog-bar">
              <div class="adm-prog-fill" style="width: ${pct}%"></div>
            </div>
            <span class="adm-prog-val">${pct}%</span>
          </div>
        </td>
        <td><span class="adm-badge ${s.cls}">${s.label}</span></td>
        <td>${getDaysLeft(c.date_fin)}</td>
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

  filteredParticipants = adminParticipants.filter(p => {
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
  participantsPage = 1;
  renderParticipantsPage();
}

function getInitials(nom) {
  return (nom || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function getAvatarColor(nom) {
  const colors = ['#5B3E96', '#3A86C4', '#27ae60', '#e67e22', '#e74c3c', '#8e44ad'];
  let hash = 0;
  for (let c of (nom || '')) {
    hash = c.charCodeAt(0) + ((hash << 5) - hash);
  }
  return colors[Math.abs(hash) % colors.length];
}

function renderParticipantRows(rows) {
  const tbody = document.getElementById('participants-tbody');
  if (!tbody) return;

  tbody.innerHTML = rows.map(p => {
    const nom = String(p.nom || '');
    const email = String(p.email || '');
    const prog = clampInt(p.objectif, 0, 100);
    const challengeTarget = clampInt(p.challenge_target, 0, 100);
    const joinedDate = formatParticipantDate(p.date_inscription);
    const engBadge = p.engagement == 1
      ? `<span class="adm-engage-badge adm-engage-badge--on">🔥 Engagé</span>`
      : `<span class="adm-engage-badge adm-engage-badge--off">😴 Inactif</span>`;

    return `
      <tr class="participant-row-admin">
        <td>
          <div class="adm-participant-cell">
            <div class="adm-avatar" style="background:${getAvatarColor(nom)}">
              ${getInitials(nom)}
            </div>
            <div class="adm-participant-info">
              <span class="adm-participant-name">${escapeHtml(nom)}</span>
              <span class="adm-participant-email">${escapeHtml(email)}</span>
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
        <td><span class="participant-email">${escapeHtml(joinedDate)}</span></td>
        <td>${engBadge}</td>
        <td>
          <div class="table-actions">
            <button class="btn-icon" onclick="viewParticipant(${p.id})" title="Voir">👁️</button>
            <button class="btn-icon delete" onclick="deleteParticipant(${p.id}, ${p.id_challenge})" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function renderParticipantsPage() {
  const total = filteredParticipants.length;
  const totalPages = Math.max(1, Math.ceil(total / PARTICIPANTS_PER_PAGE));
  participantsPage = Math.min(Math.max(1, participantsPage), totalPages);

  const start = (participantsPage - 1) * PARTICIPANTS_PER_PAGE;
  const slice = filteredParticipants.slice(start, start + PARTICIPANTS_PER_PAGE);

  const info = document.getElementById('participants-pagination-info');
  if (info) {
    const from = total > 0 ? start + 1 : 0;
    const to = total > 0 ? Math.min(start + PARTICIPANTS_PER_PAGE, total) : 0;
    info.textContent = `Affichage de ${from} à ${to} sur ${total} participants`;
  }

  const btns = document.querySelectorAll('.adm-pagination .pagination-controls .adm-btn');
  if (btns[0]) btns[0].disabled = participantsPage <= 1;
  if (btns[1]) btns[1].disabled = participantsPage >= totalPages;

  renderParticipantRows(slice);
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
  openParticipantModal(p);
}

function openParticipantModal(p) {
  // Create or get modal
  let overlay = document.getElementById('adm-participant-modal');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'adm-participant-modal';
    overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99000;background:rgba(0,0,0,.72);backdrop-filter:blur(7px);align-items:center;justify-content:center;padding:20px;';
    overlay.innerHTML = `
      <div id="adm-pm-card" style="
        position:relative;width:520px;max-width:96vw;max-height:90vh;overflow-y:auto;
        background:linear-gradient(160deg,rgba(15,35,24,.98),rgba(10,26,16,.99));
        border:1px solid rgba(91,62,150,.38);border-radius:24px;
        box-shadow:0 0 0 1px rgba(255,255,255,.05),0 30px 70px rgba(0,0,0,.6);
        animation:admPmSlide .32s cubic-bezier(.34,1.56,.64,1);
      ">
        <style>
        @keyframes admPmSlide{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
        .adm-pm-section{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px 18px;margin-bottom:12px;}
        .adm-pm-row{display:flex;gap:16px;margin-bottom:10px;align-items:flex-start;}
        .adm-pm-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(168,184,160,.6);margin-bottom:3px;}
        .adm-pm-val{font-size:.9rem;color:#F2E8CF;font-weight:500;line-height:1.5;word-break:break-word;}
        .adm-pm-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700;}
        </style>

        <!-- Top edge glow -->
        <div style="position:absolute;top:0;left:22px;right:22px;height:2px;background:linear-gradient(90deg,transparent,#5B3E96 35%,#3A86C4 65%,transparent);border-radius:2px;opacity:.85;pointer-events:none;"></div>
        <!-- Corners -->
        <div style="position:absolute;top:-2px;left:-2px;width:16px;height:16px;border-top:2.5px solid #5B3E96;border-left:2.5px solid #5B3E96;border-radius:4px 0 0 0;pointer-events:none;"></div>
        <div style="position:absolute;top:-2px;right:-2px;width:16px;height:16px;border-top:2.5px solid #3A86C4;border-right:2.5px solid #3A86C4;border-radius:0 4px 0 0;pointer-events:none;"></div>
        <div style="position:absolute;bottom:-2px;right:-2px;width:16px;height:16px;border-bottom:2.5px solid #5B3E96;border-right:2.5px solid #5B3E96;border-radius:0 0 4px 0;pointer-events:none;"></div>
        <div style="position:absolute;bottom:-2px;left:-2px;width:16px;height:16px;border-bottom:2.5px solid #3A86C4;border-left:2.5px solid #3A86C4;border-radius:0 0 0 4px;pointer-events:none;"></div>

        <!-- Header -->
        <div style="padding:28px 24px 20px;background:linear-gradient(180deg,rgba(91,62,150,.12),transparent);border-bottom:1px solid rgba(91,62,150,.18);text-align:center;">
          <div style="width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,rgba(91,62,150,.3),rgba(58,134,196,.2));border:2.5px solid rgba(91,62,150,.4);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 14px;box-shadow:0 0 24px rgba(91,62,150,.25);">👤</div>
          <h3 id="adm-pm-name" style="font-family:'Cormorant Garamond',serif;font-size:1.5rem;font-weight:700;color:#F2E8CF;margin:0 0 5px;"></h3>
          <p id="adm-pm-email" style="font-size:.82rem;color:#a8b8a0;margin:0;"></p>
        </div>

        <!-- Body -->
        <div style="padding:20px 22px 8px;" id="adm-pm-body"></div>

        <!-- Footer -->
        <div style="padding:14px 22px 22px;display:flex;gap:10px;">
          <button onclick="document.getElementById('adm-participant-modal').style.display='none'" style="
            flex:1;padding:12px;border-radius:13px;border:1.5px solid rgba(91,62,150,.35);
            background:rgba(91,62,150,.1);color:#F2E8CF;font-size:.87rem;font-weight:600;
            cursor:pointer;transition:all .25s;font-family:'Lato',sans-serif;
          " onmouseover="this.style.background='rgba(91,62,150,.25)'" onmouseout="this.style.background='rgba(91,62,150,.1)'">
            Fermer
          </button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.style.display = 'none'; });
  }

  // Fill data
  const prog = Math.min(100, Math.max(0, parseInt(p.objectif) || 0));
  const target = Math.min(100, Math.max(0, parseInt(p.challenge_target || p.objectif_defi) || 0));
  const progColor = prog >= target ? '#2ecc71' : prog >= target * 0.6 ? '#f1c40f' : '#e74c3c';
  const engBg = p.engagement ? 'rgba(231,76,60,.15)' : 'rgba(168,184,160,.1)';
  const engColor = p.engagement ? '#e74c3c' : '#a8b8a0';
  const engText = p.engagement ? '🔥 Engagé' : '😴 Inactif';

  document.getElementById('adm-pm-name').textContent  = p.nom || '—';
  document.getElementById('adm-pm-email').textContent = p.email || '—';

  document.getElementById('adm-pm-body').innerHTML = `
    <!-- Statut & défi -->
    <div class="adm-pm-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div>
          <div class="adm-pm-label">Défi</div>
          <div class="adm-pm-val" style="font-weight:700;">${escapeHtml(p.challenge_titre || String(p.id_challenge) || '—')}</div>
        </div>
        <span class="adm-pm-badge" style="background:${engBg};border:1px solid ${engColor};color:${engColor};">${engText}</span>
      </div>
      <!-- Progress bar -->
      <div style="margin-bottom:4px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:.72rem;color:#a8b8a0;">Progression</span>
        <span style="font-size:.9rem;font-weight:700;color:${progColor};">${prog}%</span>
      </div>
      <div style="height:8px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden;margin-bottom:10px;">
        <div style="height:100%;width:${prog}%;background:linear-gradient(90deg,${progColor},${progColor}cc);border-radius:99px;transition:width .6s ease;"></div>
      </div>
      <div style="display:flex;gap:10px;">
        <div style="flex:1;background:rgba(91,62,150,.1);border:1px solid rgba(91,62,150,.22);border-radius:12px;padding:10px;text-align:center;">
          <div class="adm-pm-label">Objectif défi</div>
          <div class="adm-pm-val" style="font-size:1.1rem;font-weight:700;color:#3A86C4;">${target}%</div>
        </div>
        <div style="flex:1;background:rgba(91,62,150,.1);border:1px solid rgba(91,62,150,.22);border-radius:12px;padding:10px;text-align:center;">
          <div class="adm-pm-label">Date inscription</div>
          <div class="adm-pm-val" style="font-size:.85rem;">${escapeHtml(formatParticipantDate(p.date_inscription))}</div>
        </div>
      </div>
    </div>
    <!-- Motivation -->
    <div class="adm-pm-section">
      <div class="adm-pm-label" style="margin-bottom:7px;">💬 Motivation</div>
      <div class="adm-pm-val" style="font-style:italic;color:rgba(242,232,207,.7);line-height:1.6;">${escapeHtml(p.motivation || '—')}</div>
    </div>
    <!-- Action -->
    <div class="adm-pm-section">
      <div class="adm-pm-label" style="margin-bottom:7px;">⚡ Plan d'action</div>
      <div class="adm-pm-val" style="line-height:1.6;">${escapeHtml(p.action || '—')}</div>
    </div>
  `;

  overlay.style.display = 'flex';
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
        showToast('Participant supprimé', 'Le participant a été retiré du défi.', 'success');
        loadAdminParticipants();
      } else {
        showToast('Erreur', 'Impossible de supprimer le participant.', 'error');
      }
    })
    .catch(err => {
      console.error('Erreur suppression participant:', err);
      showToast('Erreur', 'Erreur de connexion au serveur.', 'error');
    });
}

function openAdmModal(msg, onConfirm) {
  const msgEl = document.getElementById('adm-modal-msg');
  const modalEl = document.getElementById('adm-confirm-modal');
  const confirmBtn = document.getElementById('adm-modal-confirm');
  if (!msgEl || !modalEl || !confirmBtn) {
    onConfirm();
    return;
  }
  msgEl.textContent = msg;
  modalEl.style.display = 'flex';
  confirmBtn.onclick = () => { closeAdmModal(); onConfirm(); };
}

function closeAdmModal() {
  const modalEl = document.getElementById('adm-confirm-modal');
  if (modalEl) modalEl.style.display = 'none';
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

window.renderParticipantsPage = renderParticipantsPage;
window.closeAdmModal = closeAdmModal;