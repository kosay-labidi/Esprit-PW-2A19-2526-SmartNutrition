// Admin Module Loader - Charge les modules admin dynamiquement
console.log('🛡️ Admin Module Loader initialisé');

// Configuration des modules admin
const adminModules = {
  dashboard: 'modules/dashboard-admin.html',
  users: 'modules/users-admin.html',
  planning: 'modules/planning-admin.html',
  events: 'modules/events-admin.html',
  meals: 'modules/meals-admin.html',
  health: 'modules/health-admin.html',
  challenges: 'modules/challenges-admin.html',
  activity: 'modules/activity-admin.html',
  export: 'modules/export-admin.html'
};

// Cache des modules chargés
const adminModuleCache = {};

// Fonction pour charger un module admin (avec option de forcer le rechargement)
async function loadAdminModule(moduleName, forceReload = false) {
  console.log(`📥 Chargement du module admin: ${moduleName}`);
  
  // Vérifier si le module est en cache (sauf si forceReload)
  if (adminModuleCache[moduleName] && !forceReload) {
    console.log(`✅ Module ${moduleName} chargé depuis le cache`);
    return adminModuleCache[moduleName];
  }
  
  // Charger le module depuis le fichier
  const modulePath = adminModules[moduleName];
  if (!modulePath) {
    console.error(`❌ Module admin ${moduleName} non trouvé`);
    return null;
  }
  
  try {
    // Ajouter un timestamp pour éviter le cache du navigateur
    const timestamp = new Date().getTime();
    const response = await fetch(`${modulePath}?t=${timestamp}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const html = await response.text();
    
    // Mettre en cache
    adminModuleCache[moduleName] = html;
    console.log(`✅ Module admin ${moduleName} chargé avec succès`);
    
    return html;
  } catch (error) {
    console.error(`❌ Erreur lors du chargement du module admin ${moduleName}:`, error);
    return null;
  }
}

// Fonction pour recharger un module admin (vider le cache et recharger)
async function reloadAdminModule(moduleName) {
  console.log(`🔄 Rechargement du module admin: ${moduleName}`);
  
  // Supprimer du cache
  delete adminModuleCache[moduleName];
  
  // Supprimer la section existante
  const existingSection = document.getElementById(moduleName);
  if (existingSection) {
    existingSection.remove();
  }
  
  // Recharger le module
  await showAdminModule(moduleName);
}

// Fonction pour injecter les styles d'un module admin
function injectAdminModuleStyles(moduleName, container) {
  const styles = container.querySelectorAll('style, link[rel="stylesheet"]');
  const styleId = `style-admin-${moduleName}`;
  
  // Supprimer les anciens styles s'ils existent (pour forcer la mise à jour)
  const existingStyle = document.getElementById(styleId);
  if (existingStyle) {
    existingStyle.remove();
  }

  // Ajouter les nouveaux styles
  styles.forEach((style, index) => {
    const clonedStyle = style.cloneNode(true);
    clonedStyle.id = index === 0 ? styleId : `${styleId}-${index}`;
    document.head.appendChild(clonedStyle);
  });
}

// Fonction pour afficher un module admin
async function showAdminModule(moduleName) {
  // Détection du protocole file:// (CORS bloqué par les navigateurs)
  if (window.location.protocol === 'file:') {
    const mainContent = document.querySelector('.main-content');
    if (mainContent && mainContent.innerHTML.trim() === '') {
      mainContent.innerHTML = `
        <div style="padding:40px;text-align:center;background:rgba(231,76,60,.1);border:1px solid #e74c3c;border-radius:18px;margin-top:20px;">
          <h2 style="color:#e74c3c;margin-bottom:15px;">⚠️ Accès Restreint (CORS)</h2>
          <p style="margin-bottom:20px;line-height:1.6;">Le navigateur bloque le chargement des modules car vous ouvrez le fichier directement (file://).<br>
          Pour que le dashboard fonctionne, vous devez passer par votre serveur local (XAMPP).</p>
          <div style="background:rgba(0,0,0,.2);padding:15px;border-radius:10px;font-family:monospace;margin-bottom:20px;color:var(--text);">
            http://localhost/templater/views/backend/admin.html
          </div>
          <p style="font-size:.9rem;color:var(--muted);">Ouvrez cette URL dans votre navigateur après avoir démarré Apache dans XAMPP.</p>
        </div>
      `;
    }
    console.error('❌ Fetch bloqué par le protocole file://. Utilisez un serveur local (localhost).');
    return;
  }

  // Sauvegarder le module actif
  localStorage.setItem('activeAdminModule', moduleName);
  
  // Cacher toutes les sections existantes
  const allSections = document.querySelectorAll('.content-section');
  allSections.forEach(section => {
    section.classList.remove('active');
    section.style.display = 'none';
  });
  
  // Chercher si la section existe déjà
  let targetSection = document.getElementById(moduleName);
  
  if (!targetSection) {
    // La section n'existe pas, charger le module
    console.log(`📥 Chargement du module admin externe: ${moduleName}`);
    
    // Afficher un loader temporaire dans la zone de contenu
    const mainContent = document.querySelector('.main-content');
    if (mainContent && mainContent.innerHTML.trim() === '') {
      mainContent.innerHTML = `
        <div class="section-loader">
          <div class="section-loader-spinner"></div>
          <p style="color:var(--muted); font-size:1.1rem;">Préparation du module...</p>
        </div>
      `;
    }
    
    const moduleHTML = await loadAdminModule(moduleName);
    
    if (moduleHTML) {
      // Nettoyer le loader
      if (mainContent) mainContent.innerHTML = '';
      
      // Créer un conteneur temporaire
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = moduleHTML;
      
      // Injecter les styles
      injectAdminModuleStyles(moduleName, tempDiv);
      
      // Extraire la section
      const newSection = tempDiv.querySelector('.content-section');
      
      if (newSection) {
        // Ajouter la section au main-content
        if (mainContent) {
          mainContent.appendChild(newSection);
          targetSection = newSection;
        }
      }
    } else {
      console.error(`❌ Impossible de charger le module admin ${moduleName}`);
      if (mainContent) {
        mainContent.innerHTML = `
          <div style="padding:40px;text-align:center;background:rgba(231,76,60,.1);border:1px solid #e74c3c;border-radius:18px;">
            <h2 style="color:#e74c3c;">❌ Erreur de Chargement</h2>
            <p>Impossible de charger le module <strong>${moduleName}</strong>.</p>
            <button onclick="reloadAdminModule('${moduleName}')" style="margin-top:20px;padding:10px 20px;background:var(--violet);border:none;border-radius:50px;color:#fff;cursor:pointer;">Réessayer</button>
          </div>
        `;
      }
      return;
    }
  }
  
  // Afficher la section
  if (targetSection) {
    targetSection.style.display = 'block';
    targetSection.classList.add('active');
    
    // Déclencher l'événement de chargement du module
    const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
    document.dispatchEvent(event);
    
    console.log(`✅ Module admin ${moduleName} affiché`);
  }
}

// Gestion des clics sur les items du menu
document.addEventListener('DOMContentLoaded', () => {
  const menuItems = document.querySelectorAll('.menu-item');
  
  menuItems.forEach(item => {
    item.addEventListener('click', async (e) => {
      e.preventDefault();
      const moduleName = item.dataset.module;
      
      // Mettre à jour l'état actif du menu
      menuItems.forEach(mi => mi.classList.remove('active'));
      item.classList.add('active');
      
      // Charger et afficher le module
      await showAdminModule(moduleName);
      
      // Fermer le menu mobile si ouvert
      if (window.innerWidth <= 768) {
        document.querySelector('.sidebar-menu')?.classList.remove('mobile-open');
        document.getElementById('menu-toggle')?.classList.remove('active');
      }
    });
  });
  
  // Charger le module dashboard par défaut ou celui en mémoire
  const activeModule = localStorage.getItem('activeAdminModule') || 'dashboard';
  const targetItem = document.querySelector(`.menu-item[data-module="${activeModule}"]`);
  
  if (targetItem) {
    targetItem.click();
  } else {
    const firstMenuItem = document.querySelector('.menu-item[data-module="dashboard"]');
    if (firstMenuItem) {
      firstMenuItem.classList.add('active');
      showAdminModule('dashboard');
    }
  }
});

// Exposer les fonctions globalement
window.loadAdminModule = loadAdminModule;
window.showAdminModule = showAdminModule;
window.reloadAdminModule = reloadAdminModule;

// Auto-reload: Recharger automatiquement le module actif toutes les 2 secondes
let autoReloadInterval = null;
let lastModuleContent = {};

function startAutoReload() {
  if (autoReloadInterval) return;
  
  autoReloadInterval = setInterval(async () => {
    const activeMenuItem = document.querySelector('.menu-item.active');
    if (!activeMenuItem) return;
    
    const moduleName = activeMenuItem.dataset.module;
    if (!moduleName) return;
    
    // Charger le module avec un timestamp pour éviter le cache
    const timestamp = new Date().getTime();
    const modulePath = adminModules[moduleName];
    if (!modulePath) return;
    
    try {
      const response = await fetch(`${modulePath}?t=${timestamp}`);
      if (!response.ok) return;
      
      const newContent = await response.text();
      
      // Vérifier si le contenu a changé
      if (lastModuleContent[moduleName] && lastModuleContent[moduleName] !== newContent) {
        console.log(`🔄 Module ${moduleName} mis à jour automatiquement`);
        
        // Supprimer l'ancienne section
        const existingSection = document.getElementById(moduleName);
        if (existingSection) {
          existingSection.remove();
        }
        
        // Créer un conteneur temporaire
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newContent;
        
        // Injecter les nouveaux styles
        injectAdminModuleStyles(moduleName, tempDiv);
        
        // Extraire la nouvelle section
        const newSection = tempDiv.querySelector('.content-section');
        
        if (newSection) {
          // Ajouter la nouvelle section au main-content
          const mainContent = document.querySelector('.main-content');
          if (mainContent) {
            mainContent.appendChild(newSection);
            newSection.style.display = 'block';
            newSection.classList.add('active');
            
            // Déclencher l'événement de chargement du module
            const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
            document.dispatchEvent(event);
          }
        }
      }
      
      // Sauvegarder le contenu actuel
      lastModuleContent[moduleName] = newContent;
    } catch (error) {
      // Ignorer les erreurs silencieusement
    }
  }, 2000); // Vérifier toutes les 2 secondes
}

function stopAutoReload() {
  if (autoReloadInterval) {
    clearInterval(autoReloadInterval);
    autoReloadInterval = null;
  }
}

// Démarrer l'auto-reload au chargement
window.addEventListener('load', () => {
  startAutoReload();
  console.log('✅ Auto-reload activé - Les modifications s\'affichent automatiquement dans la zone principale');
});

// Exposer les fonctions d'auto-reload
window.startAutoReload = startAutoReload;
window.stopAutoReload = stopAutoReload;

// Ajouter un bouton de rechargement dans la navbar (optionnel)
document.addEventListener('DOMContentLoaded', () => {
  // Ajouter un bouton de rechargement rapide
  const navActions = document.querySelector('.nav-actions');
  if (navActions) {
    const reloadBtn = document.createElement('button');
    reloadBtn.id = 'reload-module-btn';
    reloadBtn.title = 'Recharger le module actuel';
    reloadBtn.style.cssText = 'background:var(--glass);border:1.5px solid rgba(91,62,150,.5);border-radius:50px;padding:6px 14px;color:var(--text);cursor:pointer;font-size:.82rem;transition:all .3s;backdrop-filter:blur(10px);display:inline-flex;align-items:center;gap:4px;margin-right:8px;';
    reloadBtn.innerHTML = '🔄 Recharger';
    reloadBtn.addEventListener('click', () => {
      const activeMenuItem = document.querySelector('.menu-item.active');
      if (activeMenuItem) {
        const moduleName = activeMenuItem.dataset.module;
        reloadAdminModule(moduleName);
        showToast('Module rechargé!', 'Module actualisé avec succès', 'success');
      }
    });
    reloadBtn.addEventListener('mouseenter', () => {
      reloadBtn.style.background = 'rgba(91,62,150,.2)';
      reloadBtn.style.transform = 'scale(1.05)';
      reloadBtn.style.borderColor = 'var(--violet)';
    });
    reloadBtn.addEventListener('mouseleave', () => {
      reloadBtn.style.background = 'var(--glass)';
      reloadBtn.style.transform = 'scale(1)';
      reloadBtn.style.borderColor = 'rgba(91,62,150,.5)';
    });
    navActions.insertBefore(reloadBtn, navActions.firstChild);
  }
});

// Événement personnalisé pour les modules admin
document.addEventListener('adminModuleLoaded', (e) => {
  const { moduleName } = e.detail;
  console.log(`🎉 Événement adminModuleLoaded déclenché pour: ${moduleName}`);
  
  // Animation séquentielle des éléments du module
  animateModuleElements(moduleName);
  
  // Initialiser les fonctionnalités spécifiques au module
  switch (moduleName) {
    case 'dashboard':
      // Charger les statistiques
      if (typeof loadAdminStats === 'function') {
        setTimeout(() => loadAdminStats(), 100);
      }
      break;
    case 'users':
      // Charger les utilisateurs
      if (typeof loadUsers === 'function') {
        setTimeout(() => loadUsers(), 100);
      }
      break;
    case 'planning':
      // Vider le cache pour forcer le rechargement du module
      delete adminModuleCache['planning'];
      setTimeout(() => loadPlanningData(), 200);
      break;
  }
});

// Fonctions utilitaires admin
function refreshUsers() {
  console.log('🔄 Actualisation des utilisateurs...');
  showToast('Utilisateurs actualisés', 'success');
}

function exportData(type) {
  console.log(`📥 Export des données: ${type}`);
  showToast(`Export ${type} en cours...`, 'info');
}

function addUser() {
  console.log('➕ Ajout d\'un utilisateur');
  showToast('Fonctionnalité à implémenter', 'info');
}

function editUser(id) {
  console.log(`✏️ Modification utilisateur ${id}`);
  showToast(`Modification utilisateur ${id}`, 'info');
}

function deleteUser(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')) {
    console.log(`🗑️ Suppression utilisateur ${id}`);
    showToast(`Utilisateur ${id} supprimé`, 'success');
  }
}

function refreshEvents() {
  console.log('🔄 Actualisation des événements...');
  showToast('Événements actualisés', 'success');
}

function addEvent() {
  console.log('➕ Ajout d\'un événement');
  showToast('Fonctionnalité à implémenter', 'info');
}

function editEvent(id) {
  console.log(`✏️ Modification événement ${id}`);
  showToast(`Modification événement ${id}`, 'info');
}

function deleteEvent(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet événement?')) {
    console.log(`🗑️ Suppression événement ${id}`);
    showToast(`Événement ${id} supprimé`, 'success');
  }
}

function refreshActivity() {
  console.log('🔄 Actualisation des logs...');
  showToast('Logs actualisés', 'success');
}

function exportLogs() {
  console.log('📥 Export des logs');
  showToast('Export des logs en cours...', 'info');
}

function exportAll() {
  console.log('📥 Export de toutes les données');
  showToast('Export complet en cours...', 'info');
}

// ==================== PLANNING FUNCTIONS ====================
let planningDataCache = [];

function loadPlanningData() {
  const tbody = document.getElementById('planningTableBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--muted);">⏳ Chargement...</td></tr>';

  // Utiliser XMLHttpRequest comme dans les challenges
  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'planning/listDemandeplanning.php', true);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  
  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        const data = JSON.parse(xhr.responseText);
        
        // Cache data for detail panel
        planningDataCache = data;

        const tb = document.getElementById('planningTableBody');
        if (!tb) return;

        if (!data.length) {
          tb.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">Aucune demande trouvée.</td></tr>';
        } else {
          tb.innerHTML = data.map(d => `
            <tr class="animate-in">
              <td>${d.id}</td>
              <td>${d.id_utilisateur}</td>
              <td>${d.calories} kcal</td>
              <td>${parseFloat(d.budget).toFixed(2)} € <span class="badge badge-user">${d.type_budget}</span></td>
              <td>${d.duree} <span class="badge badge-user">${d.type_duree}</span></td>
              <td>${d.date_demande || '—'}</td>
              <td>
                <div class="action-btns">
                  <button onclick="showDetailPanel(${d.id})" class="action-btn action-btn-view">👁️ Afficher</button>
                  <a href="javascript:void(0)" class="action-btn action-btn-delete"
                     onclick="deletePlanningAjax(${d.id}); return false;">🗑️ Supprimer</a>
                </div>
              </td>
            </tr>
          `).join('');
        }

        // Stats
        const s = id => document.getElementById(id);
        if (s('stat-total'))     s('stat-total').textContent     = data.length;
        if (s('stat-quotidien')) s('stat-quotidien').textContent = data.filter(d => d.type_budget === 'quotidien').length;
        if (s('stat-hebdo'))     s('stat-hebdo').textContent     = data.filter(d => d.type_budget === 'hebdomadaire').length;
        if (s('stat-jours'))     s('stat-jours').textContent     = data.filter(d => d.type_duree === 'jours').length;

      } catch(e) {
        const tb = document.getElementById('planningTableBody');
        if (tb) tb.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#e74c3c;padding:30px;">❌ Erreur de parsing : ${e.message}</td></tr>`;
      }
    } else {
      const tb = document.getElementById('planningTableBody');
      if (tb) tb.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#e74c3c;padding:30px;">❌ Erreur HTTP : ${xhr.status}</td></tr>`;
    }
  };
  
  xhr.onerror = function() {
    const tb = document.getElementById('planningTableBody');
    if (tb) tb.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#e74c3c;padding:30px;">❌ Erreur de connexion</td></tr>`;
  };
  
  xhr.send();
}

// Suppression AJAX sans rechargement de page
async function deletePlanningAjax(id) {
    if (!confirm('Supprimer cette demande ?')) return;
    
    try {
        const response = await fetch(`planning/deleteDemandeplanning.php?id=${id}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        if (result.success) {
            // Recharger le tableau (sans rafraîchir la page)
            loadPlanningData();
            // Optionnel : un petit toast
            if (typeof showToast === 'function') showToast('Demande supprimée', 'success');
        } else {
            alert('Erreur : ' + result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Erreur réseau');
    }
}

// Exposer la fonction globalement
window.deletePlanningAjax = deletePlanningAjax;

function showDetailPanel(id) {
  // Find demand in cache
  const demande = planningDataCache.find(d => d.id == id);
  if (!demande) {
    alert('Demande introuvable');
    return;
  }

  // Update detail panel
  const detailId = document.getElementById('detail-id');
  const detailBody = document.getElementById('detailTableBody');
  const detailPanel = document.getElementById('detailPanel');

  if (!detailId || !detailBody || !detailPanel) return;

  detailId.textContent = demande.id;
  
  detailBody.innerHTML = `
    <tr>
      <td style="font-weight:600; width:200px; background:rgba(91,62,150,.1);">🆔 ID</td>
      <td>${demande.id}</td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">👤 Utilisateur</td>
      <td>${demande.id_utilisateur}</td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">🔥 Calories</td>
      <td>${demande.calories} kcal</td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">💰 Budget</td>
      <td>${parseFloat(demande.budget).toFixed(2)} €</td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">📊 Type Budget</td>
      <td><span class="badge badge-user">${demande.type_budget}</span></td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">⏱️ Durée</td>
      <td>${demande.duree}</td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">📅 Type Durée</td>
      <td><span class="badge badge-user">${demande.type_duree}</span></td>
    </tr>
    <tr>
      <td style="font-weight:600; background:rgba(91,62,150,.1);">📆 Date Demande</td>
      <td>${demande.date_demande || '—'}</td>
    </tr>
  `;

  // Show panel with animation
  detailPanel.style.display = 'block';
  setTimeout(() => {
    detailPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 100);
}

function hideDetailPanel() {
  const detailPanel = document.getElementById('detailPanel');
  if (detailPanel) {
    detailPanel.style.display = 'none';
  }
}

function filterPlanningTable() {
  const val = (document.getElementById('planningSearchInput')?.value || '').toLowerCase();
  document.querySelectorAll('#planningTableBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
}

window.loadPlanningData = loadPlanningData;
window.showDetailPanel = showDetailPanel;
window.hideDetailPanel = hideDetailPanel;
window.filterPlanningTable = filterPlanningTable;

function filterByChip(chip, filter) {
  // Retirer la classe active de tous les chips
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  // Ajouter la classe active au chip cliqué
  chip.classList.add('active');
  console.log(`🔍 Filtre appliqué: ${filter}`);
}

function searchUsers() {
  const searchValue = document.getElementById('searchInput')?.value;
  console.log(`🔍 Recherche: ${searchValue}`);
}

function filterUsers() {
  const roleFilter = document.getElementById('roleFilter')?.value;
  console.log(`🔍 Filtre rôle: ${roleFilter}`);
}

// Fonction pour animer les éléments d'un module de manière séquentielle
function animateModuleElements(moduleName) {
  const container = document.getElementById(moduleName);
  if (!container) return;

  // Sélectionner tous les éléments à animer
  const selectors = '.stat-card, .section, .quick-stat, .data-card, tbody tr, .info-card';
  const elements = container.querySelectorAll(selectors);
  
  elements.forEach((el, index) => {
    // Délai progressif pour l'effet de cascade
    const delay = index * 50; 
    setTimeout(() => {
      el.classList.add('animate-in');
    }, delay);
  });
}

console.log('✅ Admin Module Loader prêt');
