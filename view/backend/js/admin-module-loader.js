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
      // Attendre que le DOM du module soit bien rendu avant de charger
      const waitForTable = setInterval(() => {
        if (document.getElementById('usersTableBody')) {
          clearInterval(waitForTable);
          loadUsers();
        }
      }, 50);
      // Timeout de sécurité après 3 secondes
      setTimeout(() => clearInterval(waitForTable), 3000);
      break;
  }
});

// Fonctions utilitaires admin
function refreshUsers() {
    // Effacer le terme de recherche
    localStorage.removeItem('userSearchTerm');
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    loadUsers();
    if (typeof showToast === 'function') {
        showToast('Actualisé', 'Liste mise à jour', 'success');
    }
}

// Exportation des données en CSV
async function exportData(type) {
    console.log(`📥 Export des données ${type} en cours...`);
    
    if (type === 'users') {
        await exportUsersToCSV();
    } else {
        showToast('Export', `Export des données ${type} en cours...`, 'info');
    }
}

// Fonction d'exportation des utilisateurs en CSV
async function exportUsersToCSV() {
    try {
        showToast('Export', 'Préparation de l\'export...', 'info');
        
        // Récupérer les données actuelles (avec les filtres appliqués)
        const savedOrder = localStorage.getItem('userSortOrder') || 'desc';
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${savedOrder}&t=${Date.now()}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            throw new Error('Impossible de récupérer les données');
        }
        
        let users = result.data;
        
        // Appliquer les filtres actifs (recherche + rôle)
        const savedSearchTerm = localStorage.getItem('userSearchTerm');
        if (savedSearchTerm && savedSearchTerm !== '') {
            const searchLower = savedSearchTerm.toLowerCase();
            users = users.filter(user => {
                const fullName = `${user.prenom || ''} ${user.nom || ''}`.toLowerCase();
                const email = (user.email || '').toLowerCase();
                return fullName.includes(searchLower) || email.includes(searchLower);
            });
        }
        
        const savedRoleFilter = localStorage.getItem('userRoleFilter');
        if (savedRoleFilter && savedRoleFilter !== '') {
            users = users.filter(user => user.role === savedRoleFilter);
        }
        
        if (users.length === 0) {
            showToast('Export', 'Aucune donnée à exporter', 'warning');
            return;
        }
        
        // Générer le CSV
        const csvData = convertUsersToCSV(users);
        
        // Télécharger le fichier
        downloadCSV(csvData, `utilisateurs_${formatDateForFilename()}.csv`);
        
        showToast('Export réussi', `${users.length} utilisateur(s) exporté(s)`, 'success');
        
    } catch (error) {
        console.error('❌ Erreur export:', error);
        showToast('Erreur', 'Impossible d\'exporter les données', 'error');
    }
}

// Convertir les utilisateurs en format CSV
// Convertir les utilisateurs en format CSV
function convertUsersToCSV(users) {
    // Définir les en-têtes
    const headers = [
        'ID',
        'Nom',
        'Prénom',
        'Email',
        'Rôle',
        'Date inscription',
        'Statut'
    ];
    
    // Créer les lignes de données
    const rows = users.map(user => {
        // Formater la date plus court (YYYY-MM-DD)
        let formattedDate = '';
        if (user.date_creation) {
            // Si la date est au format "2024-01-15 14:30:00" ou similaire
            formattedDate = user.date_creation.split(' ')[0]; // Ne garde que YYYY-MM-DD
        }
        
        // Traduire le rôle en français
        let roleFrench = '';
        switch(user.role) {
            case 'admin':
                roleFrench = 'Administrateur';
                break;
            case 'nutritionniste':
                roleFrench = 'Nutritionniste';
                break;
            case 'ecologiste':
                roleFrench = 'Écologiste';
                break;
            default:
                roleFrench = 'Utilisateur';
        }
        
        // Statut (par défaut "Actif" si non spécifié)
        const status = user.status === 'inactif' ? 'Inactif' : 'Actif';
        
        return [
            escapeCSVField(user.id || ''),
            escapeCSVField(user.nom || ''),
            escapeCSVField(user.prenom || ''),
            escapeCSVField(user.email || ''),
            escapeCSVField(roleFrench),
            escapeCSVField(formattedDate), // Date au format YYYY-MM-DD
            escapeCSVField(status)
        ];
    });
    
    // Combiner en-têtes et lignes
    const csvContent = [
        headers.join(';'),
        ...rows.map(row => row.join(';'))
    ].join('\n');
    
    return '\uFEFF' + csvContent;
}
// Échapper les caractères spéciaux pour le CSV
function escapeCSVField(field) {
    if (field === undefined || field === null) return '';
    
    // Convertir en string
    let stringField = String(field);
    
    // Si le champ contient des guillemets, points-virgules ou retours à la ligne
    if (stringField.includes('"') || stringField.includes(';') || stringField.includes('\n') || stringField.includes(',')) {
        // Remplacer les guillemets par des guillemets doubles
        stringField = stringField.replace(/"/g, '""');
        // Entourer le champ de guillemets
        return `"${stringField}"`;
    }
    
    return stringField;
}

// Formater la date pour le nom de fichier
function formatDateForFilename() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
}

// Télécharger le fichier CSV
function downloadCSV(csvContent, filename) {
    // Créer un blob avec le contenu CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // Créer un lien de téléchargement
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Nettoyer l'URL
    URL.revokeObjectURL(url);
}

// Exporter également pour les autres modules
function exportLogs() {
    showToast('Export', 'Export des logs en cours...', 'info');
}

function exportAll() {
    showToast('Export', 'Export complet en cours...', 'info');
}

function addUser() {
  window.showAddUserModal();
}

window.editUser = function(id) {
  showEditUserModal(id);
};

window.closeEditModal = function() {
    document.getElementById('editUserModal').style.display = 'none';
};

// Intercepter la soumission du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editUserForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/updateUser.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeEditModal();
                    await loadUsers();
                    showToast('Succès', result.message || 'Utilisateur modifié avec succès', 'success');
                } else {
                    showToast('Erreur', result.message || 'Échec de la mise à jour', 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur', 'Erreur lors de la modification', 'error');
            }
        });
    }
});

function deleteUser(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')) {
    showToast('Supprimé', `Utilisateur ${id} supprimé avec succès`, 'success');
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
window.deleteUser = async function(id) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
    try {
      // Afficher un toast de chargement
      showToast('Suppression', 'Suppression en cours...', 'info');
      
      // Appeler deleteUser.php en arrière-plan
      const response = await fetch(`http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/deleteUser.php?id=${id}`);
      
      // Recharger la liste des utilisateurs sans quitter la page
      await loadUsers();
      
      // Afficher un message de succès
      showToast('Succès', 'Utilisateur supprimé avec succès', 'success');
      
    } catch (error) {
      console.error('❌ Erreur:', error);
      showToast('Erreur', 'Impossible de supprimer l\'utilisateur', 'error');
    }
  }
};

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

function filterByChip(chip, filter) {
  // Retirer la classe active de tous les chips
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  // Ajouter la classe active au chip cliqué
  chip.classList.add('active');
  console.log(`🔍 Filtre appliqué: ${filter}`);
}

// Fonction de recherche en temps réel par nom
// Version CLIENT-SIDE - Pas besoin de nouveau fichier PHP
async function searchUsers() {
    console.log("🔍 Recherche en temps réel...");
    
    const searchTerm = document.getElementById('searchInput')?.value.trim().toLowerCase() || '';
    
    // Recharger les données depuis l'API existante
    try {
        // Utiliser l'API de tri existante
        const savedOrder = localStorage.getItem('userSortOrder') || 'desc';
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${savedOrder}&t=${Date.now()}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            let filteredUsers = result.data;
            
            // FILTRER CÔTÉ CLIENT (pas besoin de PHP)
            if (searchTerm !== '') {
                filteredUsers = filteredUsers.filter(user => {
                    const fullName = `${user.prenom || ''} ${user.nom || ''}`.toLowerCase();
                    const email = (user.email || '').toLowerCase();
                    return fullName.includes(searchTerm) || email.includes(searchTerm);
                });
            }
            
            // Appliquer le filtre rôle existant
            const roleFilter = document.getElementById('roleFilter');
            if (roleFilter && roleFilter.value !== '') {
                filteredUsers = filteredUsers.filter(user => user.role === roleFilter.value);
            }
            
            // Mettre à jour l'affichage
            updateUsersTableFromData(filteredUsers);
            
            // Message si aucun résultat
            if (filteredUsers.length === 0 && searchTerm !== '') {
                const tableBody = document.getElementById('usersTableBody');
                if (tableBody) {
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 40px;">🔍 Aucun utilisateur trouvé pour "${searchTerm}"</td></tr>`;
                }
            }
        }
    } catch (error) {
        console.error('Erreur recherche:', error);
    }
}

// Version debounced pour optimiser les performances
let searchTimeout;
function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchUsers();
    }, 300); // Délai de 300ms après la dernière frappe
}

// Fonction de filtre par rôle
async function filterUsers() {
    console.log("🔄 Function filterUsers() appelée");
    
    const roleFilter = document.getElementById('roleFilter');
    if (!roleFilter) {
        console.error("❌ Élément roleFilter non trouvé");
        return;
    }
    
    const role = roleFilter.value;
    console.log("📊 Rôle sélectionné:", role || 'Tous');
    
    // Afficher un indicateur de chargement
    const tableBody = document.getElementById('usersTableBody');
    if (tableBody) {
        tableBody.innerHTML = '</tr><td colspan="6" style="text-align:center;">⏳ Filtrage en cours...<\/td><\/tr>';
    }
    
    try {
        // Appeler triRole.php avec le paramètre role
        let url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/triRole.php?t=${Date.now()}`;
        if (role) {
            url += `&role=${encodeURIComponent(role)}`;
        }
        
        console.log("📡 Appel API:", url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log("📄 Réponse JSON reçue:", result);
        
        if (result.success && result.data) {
            // Mettre à jour le tableau
            updateUsersTableFromData(result.data);
            
            // Sauvegarder le filtre
            if (role) {
                localStorage.setItem('userRoleFilter', role);
            } else {
                localStorage.removeItem('userRoleFilter');
            }
            
            // Afficher un toast
            if (typeof showToast === 'function') {
                const message = role ? `Filtre: ${getRoleLabel(role)}` : 'Affichage de tous les utilisateurs';
                showToast('Filtre', message, 'success');
            }
            
            console.log(`✅ ${result.data.length} utilisateurs filtrés par rôle: ${role || 'tous'}`);
        } else {
            throw new Error(result.error || 'Erreur lors du filtrage');
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du filtrage:', error);
        if (typeof showToast === 'function') {
            showToast('Erreur', 'Impossible de filtrer les utilisateurs', 'error');
        }
        // Recharger la liste normale
        loadUsers();
    }
}

// Fonction utilitaire pour obtenir le libellé du rôle
function getRoleLabel(role) {
    const roles = {
        'user': 'Utilisateur',
        'admin': 'Administrateur',
        'nutritionniste': 'Nutritionniste',
        'ecologiste': 'Écologiste'
    };
    return roles[role] || role;
}
// Fonction de tri par date
// Fonction de tri par date
// Fonction de tri par date
// Fonction de tri par date
async function tri() {
    console.log("🔄 Function tri() appelée");
    
    const selectTri = document.getElementById('triDate');
    if (!selectTri) {
        console.error("❌ Élément triDate non trouvé");
        return;
    }
    
    const order = selectTri.value;
    console.log("📊 Ordre sélectionné:", order);
    
    // Si aucune option sélectionnée, ne rien faire
    if (order === '') {
        console.log("ℹ️ Aucun tri sélectionné");
        return;
    }
    
    // Afficher un indicateur de chargement
    const tableBody = document.getElementById('usersTableBody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">⏳ Tri en cours...</td></tr>';
    }
    
    try {
        // Appeler tri.php avec le paramètre order (API JSON)
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${order}&t=${Date.now()}`;
        console.log("📡 Appel API:", url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log("📄 Réponse JSON reçue:", result);
        
        if (result.success && result.data) {
            // Mettre à jour le tableau avec les données JSON
            updateUsersTableFromData(result.data);
            
            // Sauvegarder l'ordre
            localStorage.setItem('userSortOrder', order);
            
            // Afficher un toast
            if (typeof showToast === 'function') {
                const message = order === 'asc' ? 'Tri : plus ancien → plus récent' : 'Tri : plus récent → plus ancien';
                showToast('Tri', message, 'success');
            }
            
            console.log(`✅ Utilisateurs triés par date (${order === 'asc' ? 'croissant' : 'décroissant'}) - ${result.data.length} utilisateurs`);
        } else {
            throw new Error(result.error || 'Erreur lors du tri');
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du tri:', error);
        if (typeof showToast === 'function') {
            showToast('Erreur', 'Impossible de trier les utilisateurs', 'error');
        }
        // Recharger la liste normale
        loadUsers();
    }
}

// Nouvelle fonction pour mettre à jour le tableau à partir des données JSON
function updateUsersTableFromData(users) {
    console.log("📊 Mise à jour du tableau avec", users.length, "utilisateurs");
    
    const tableBody = document.getElementById('usersTableBody');
    if (!tableBody) {
        console.error("❌ usersTableBody non trouvé");
        return;
    }
    
    if (!users || users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Aucun utilisateur trouvé</td></tr>';
        return;
    }
    
    // Générer le HTML du tableau
    let html = '';
    users.forEach((user, index) => {
        // Déterminer la classe et l'icône du rôle
        let roleClass = '';
        let roleIcon = '👤';
        let roleLabel = '';
        
        switch(user.role) {
            case 'admin':
                roleClass = 'role-admin';
                roleIcon = '🛡️';
                roleLabel = 'Admin';
                break;
            case 'nutritionniste':
                roleClass = 'role-nutritionniste';
                roleIcon = '🥗';
                roleLabel = 'Nutritionniste';
                break;
            case 'ecologiste':
                roleClass = 'role-ecologiste';
                roleIcon = '🌱';
                roleLabel = 'Écologiste';
                break;
            default:
                roleClass = 'role-user';
                roleIcon = '👤';
                roleLabel = 'Utilisateur';
        }
        
        // Afficher le nom complet
        const fullName = `${user.prenom || ''} ${user.nom || ''}`.trim();
        
        html += `
            <tr style="opacity: 1; animation: fadeIn 0.3s ease-in;">
                <td>${user.id || ''}</td>
                <td>${fullName}</td>
                <td>${user.email || ''}</td>
                <td><span class="role-badge ${roleClass}">${roleIcon} ${roleLabel}</span></td>
                <td>${user.date_creation || ''}</td>
                <td class="actions-cell">
                   <button class="action-btn edit" onclick="editUser(${user.id})" title="Modifier">
                        ✏️
                    </button>
                    <button class="action-btn delete" onclick="deleteUser(${user.id})" title="Supprimer">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    console.log("✅ Tableau mis à jour");
    
    // Ajouter les styles CSS si nécessaire
    addTriTableStyles();
}
// Fonction pour ajouter les styles CSS du tableau trié
function addTriTableStyles() {
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
async function loadUsers() {
    console.log("📡 Chargement des utilisateurs...");

    const tableBody = document.getElementById("usersTableBody");
    
    if (!tableBody) {
        console.error("❌ usersTableBody introuvable !");
        setTimeout(loadUsers, 100);
        return;
    }

    try {
        tableBody.innerHTML = '<table><td colspan="6" style="text-align:center;">⏳ Chargement...<\/td><\/tr>';
        
        // Récupérer les paramètres sauvegardés
        const savedOrder = localStorage.getItem('userSortOrder');
        const savedRoleFilter = localStorage.getItem('userRoleFilter');
        const savedSearchTerm = localStorage.getItem('userSearchTerm');
        
        const selectTri = document.getElementById('triDate');
        const roleFilter = document.getElementById('roleFilter');
        const searchInput = document.getElementById('searchInput');
        
        // Restaurer le terme de recherche si présent
        if (savedSearchTerm && searchInput) {
            searchInput.value = savedSearchTerm;
        }
        
        // Construire l'URL de base
        let url = "http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php";
        
        // Appliquer le tri sauvegardé
        if (savedOrder && savedOrder !== '') {
            url += `?order=${savedOrder}`;
            if (selectTri) {
                selectTri.value = savedOrder;
            }
            console.log(`📊 Application du tri sauvegardé: ${savedOrder}`);
        } else {
            url += `?order=desc`;
            if (selectTri) {
                selectTri.value = 'desc';
            }
        }
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            let users = result.data;
            
            // Appliquer la recherche si présente
            if (savedSearchTerm && savedSearchTerm !== '') {
                const searchLower = savedSearchTerm.toLowerCase();
                users = users.filter(user => {
                    const fullName = `${user.prenom || ''} ${user.nom || ''}`.toLowerCase();
                    const email = (user.email || '').toLowerCase();
                    return fullName.includes(searchLower) || email.includes(searchLower);
                });
                console.log(`📊 Application de la recherche sauvegardée: "${savedSearchTerm}" -> ${users.length} résultats`);
            }
            
            // Appliquer le filtre par rôle si présent
            if (savedRoleFilter && savedRoleFilter !== '') {
                users = users.filter(user => user.role === savedRoleFilter);
                if (roleFilter) {
                    roleFilter.value = savedRoleFilter;
                }
                console.log(`📊 Application du filtre sauvegardé: ${savedRoleFilter}`);
            } else {
                if (roleFilter) {
                    roleFilter.value = '';
                }
            }
            
            updateUsersTableFromData(users);
            refreshUserStats();
            
            // Afficher un message si aucun résultat avec recherche
            if (users.length === 0 && savedSearchTerm) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 40px;">🔍 Aucun utilisateur trouvé pour "${savedSearchTerm}"<\/td><\/tr>`;
            }
        } else {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#e74c3c;">❌ Erreur: ${result.error || 'Données invalides'}<\/td><\/tr>`;
        }
        
    } catch (error) {
        console.error("❌ Erreur:", error);
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:#e74c3c;">❌ Erreur: ${error.message}<\/td><\/tr>`;
    }
}
// ===== STATISTIQUES DYNAMIQUES =====
let currentChart = null; // Pour stocker l'instance du graphique actuel
let currentChartType = 'pie'; // Type de graphique actuel ('pie' ou 'bar')

// Fonction pour calculer les statistiques des utilisateurs par rôle
function calculateUserStats(users) {
    const stats = {
        admin: { count: 0, label: 'Administrateurs', icon: '🛡️', color: '#e74c3c' },
        nutritionniste: { count: 0, label: 'Nutritionnistes', icon: '🥗', color: '#2ecc71' },
        ecologiste: { count: 0, label: 'Écologistes', icon: '🌱', color: '#3498db' },
        user: { count: 0, label: 'Utilisateurs', icon: '👤', color: '#9b59b6' }
    };
    
    // Compter les utilisateurs par rôle
    users.forEach(user => {
        const role = user.role;
        if (stats[role]) {
            stats[role].count++;
        } else {
            // Si rôle non reconnu, compter comme utilisateur standard
            stats.user.count++;
        }
    });
    
    const total = users.length;
    
    // Calculer les pourcentages
    const statsArray = [];
    for (const [key, value] of Object.entries(stats)) {
        if (value.count > 0) {
            statsArray.push({
                role: key,
                label: value.label,
                icon: value.icon,
                count: value.count,
                percent: total > 0 ? ((value.count / total) * 100).toFixed(1) : 0,
                color: value.color
            });
        }
    }
    
    // Trier par nombre décroissant
    statsArray.sort((a, b) => b.count - a.count);
    
    return { stats: statsArray, total };
}

// Fonction pour mettre à jour la légende des statistiques
// Fonction pour mettre à jour la légende des statistiques
// Fonction pour mettre à jour la légende des statistiques (version améliorée)
function updateStatsLegend(users) {
    const { stats, total } = calculateUserStats(users);
    const legendContainer = document.getElementById('userStatsLegend');
    
    if (!legendContainer) return;
    
    if (stats.length === 0) {
        legendContainer.innerHTML = '<div class="stat-item">Aucune donnée disponible</div>';
        return;
    }
    
    let html = '';
    stats.forEach(stat => {
        html += `
            <div class="stat-item-enhanced">
                <div class="stat-role-enhanced">
                    <div class="stat-icon-enhanced" style="background: ${stat.color}20; border-left: 3px solid ${stat.color};">
                        <span>${stat.icon}</span>
                        <span class="stat-label-enhanced">${stat.label}</span>
                    </div>
                </div>
                <div class="stat-percent-enhanced">
                    <span class="percent-value">${stat.percent}%</span>
                </div>
                <div class="stat-bar-enhanced">
                    <div class="stat-bar-fill-enhanced" style="width: ${stat.percent}%; background: ${stat.color};"></div>
                </div>
            </div>
        `;
    });
    
    html += `
        <div class="stats-total-enhanced">
            <span>📊 Total utilisateurs</span>
            <strong>${total}</strong>
        </div>
    `;
    
    legendContainer.innerHTML = html;
}

// Fonction pour créer/détruire un graphique Chart.js
function destroyChart() {
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }
}

// Fonction pour créer un graphique camembert (pie chart)
function createPieChart(users) {
    const { stats, total } = calculateUserStats(users);
    const ctx = document.getElementById('userPieChart')?.getContext('2d');
    
    if (!ctx) return;
    
    destroyChart();
    
    const labels = stats.map(s => `${s.label} (${s.percent}%)`);
    const data = stats.map(s => s.count);
    const colors = stats.map(s => s.color);
    
    currentChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderColor: 'rgba(15, 35, 24, 0.5)',
                borderWidth: 2,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getComputedStyle(document.body).getPropertyValue('--text'),
                        font: { size: 11 },
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = stats[context.dataIndex].label;
                            const value = context.raw;
                            const percent = stats[context.dataIndex].percent;
                            return `${label}: ${value} utilisateur(s) (${percent}%)`;
                        }
                    }
                }
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const index = activeElements[0].index;
                    const role = stats[index].role;
                    // Filtrer le tableau par ce rôle
                    const roleFilter = document.getElementById('roleFilter');
                    if (roleFilter) {
                        roleFilter.value = role;
                        filterUsers();
                    }
                }
            }
        }
    });
}

// Fonction pour créer un graphique à barres
function createBarChart(users) {
    const { stats, total } = calculateUserStats(users);
    const ctx = document.getElementById('userBarChart')?.getContext('2d');
    
    if (!ctx) return;
    
    // Si le graphique existe déjà, le détruire
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }
    
    const labels = stats.map(s => s.label);
    const data = stats.map(s => s.count);
    const colors = stats.map(s => s.color);
    
    currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre d\'utilisateurs',
                data: data,
                backgroundColor: colors,
                borderColor: 'rgba(15, 35, 24, 0.8)',
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const percent = stats[context.dataIndex].percent;
                            return `${value} utilisateur(s) (${percent}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(91, 62, 150, 0.1)'
                    },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--muted'),
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--text'),
                        font: { size: 12 }
                    }
                }
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const index = activeElements[0].index;
                    const role = stats[index].role;
                    const roleFilter = document.getElementById('roleFilter');
                    if (roleFilter) {
                        roleFilter.value = role;
                        filterUsers();
                    }
                }
            }
        }
    });
}

// Fonction pour basculer entre les types de graphiques
function switchChartType(type) {
    currentChartType = type;
    
    // Mettre à jour l'état des boutons
    const pieBtn = document.querySelector('.toggle-btn[onclick="switchChartType(\'pie\')"]');
    const barBtn = document.querySelector('.toggle-btn[onclick="switchChartType(\'bar\')"]');
    
    if (pieBtn) pieBtn.classList.remove('active');
    if (barBtn) barBtn.classList.remove('active');
    
    const pieContainer = document.getElementById('pieChartContainer');
    const barContainer = document.getElementById('barChartContainer');
    
    if (type === 'pie') {
        if (pieBtn) pieBtn.classList.add('active');
        pieContainer.style.display = 'block';
        barContainer.style.display = 'none';
        // Recharger les données actuelles pour le camembert
        loadUserStatsForCurrentData();
    } else {
        if (barBtn) barBtn.classList.add('active');
        pieContainer.style.display = 'none';
        barContainer.style.display = 'block';
        // Recharger les données actuelles pour les barres
        loadUserStatsForCurrentData();
    }
}

// Fonction pour charger les statistiques basées sur les données actuelles du tableau
async function loadUserStatsForCurrentData() {
    try {
        const savedOrder = localStorage.getItem('userSortOrder') || 'desc';
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${savedOrder}&t=${Date.now()}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            let users = result.data;
            
            // Appliquer le filtre de recherche si présent
            const savedSearchTerm = localStorage.getItem('userSearchTerm');
            if (savedSearchTerm && savedSearchTerm !== '') {
                const searchLower = savedSearchTerm.toLowerCase();
                users = users.filter(user => {
                    const fullName = `${user.prenom || ''} ${user.nom || ''}`.toLowerCase();
                    const email = (user.email || '').toLowerCase();
                    return fullName.includes(searchLower) || email.includes(searchLower);
                });
            }
            
            // Appliquer le filtre de rôle si présent
            const savedRoleFilter = localStorage.getItem('userRoleFilter');
            if (savedRoleFilter && savedRoleFilter !== '') {
                users = users.filter(user => user.role === savedRoleFilter);
            }
            
            // Mettre à jour les statistiques
            updateStatsLegend(users);
            
            // Créer le graphique selon le type actuel
            if (currentChartType === 'pie') {
                createPieChart(users);
            } else {
                createBarChart(users);
            }
        }
    } catch (error) {
        console.error('Erreur chargement stats:', error);
    }
}

// Fonction pour rafraîchir les statistiques (appelée après modification des données)
function refreshUserStats() {
    loadUserStatsForCurrentData();
}

// Exposer les fonctions globalement
window.switchChartType = switchChartType;
window.refreshUserStats = refreshUserStats;
// Fonction toast pour les notifications
// Fonction toast simplifiée (sans création dynamique de styles)
function showToast(title, message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    const colors = {
        success: '#2ecc71',
        error: '#e74c3c',
        info: '#3498db',
        warning: '#f39c12'
    };
    
    toast.style.cssText = `
        background: ${colors[type] || colors.info};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease-out;
        cursor: pointer;
        min-width: 250px;
        max-width: 350px;
    `;
    
    toast.innerHTML = `<strong>${title}</strong><br><small>${message}</small>`;
    toast.onclick = () => toast.remove();
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

// Ajouter les animations CSS pour les toasts

console.log('✅ Admin Module Loader prêt');