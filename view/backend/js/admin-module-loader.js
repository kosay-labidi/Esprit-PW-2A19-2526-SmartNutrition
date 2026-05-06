console.log('🛡️ Admin Module Loader initialisé');

// Variables de pagination
let currentPage = 1;
let rowsPerPage = 5;
let currentUsersData = [];   // SOURCE COMPLÈTE — jamais écrasée par les filtres
let currentFilteredData = []; // VUE COURANTE filtrée — utilisée par la pagination
let totalPages = 1;
let activeChipFilter = 'all'; // Chip actif courant

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

// Normalise le statut (gère les cas anglais/français)
function normalizeStatus(status) {
    if (!status) return 'actif';
    
    const statusMap = {
        'actif': 'actif',
        'active': 'actif',
        'inactif': 'inactif',
        'inactive': 'inactif',
        'suspendu': 'suspendu',
        'suspended': 'suspendu'
    };
    
    return statusMap[status.toLowerCase()] || 'actif';
}

async function loadAdminModule(moduleName, forceReload = false) {
  console.log(`📥 Chargement du module admin: ${moduleName}`);
  
  if (adminModuleCache[moduleName] && !forceReload) {
    console.log(`✅ Module ${moduleName} chargé depuis le cache`);
    return adminModuleCache[moduleName];
  }
  
  const modulePath = adminModules[moduleName];
  if (!modulePath) {
    console.error(`❌ Module admin ${moduleName} non trouvé`);
    return null;
  }
  
  try {
    const timestamp = new Date().getTime();
    const response = await fetch(`${modulePath}?t=${timestamp}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const html = await response.text();
    
    adminModuleCache[moduleName] = html;
    console.log(`✅ Module admin ${moduleName} chargé avec succès`);
    
    return html;
  } catch (error) {
    console.error(`❌ Erreur lors du chargement du module admin ${moduleName}:`, error);
    return null;
  }
}

async function reloadAdminModule(moduleName) {
  console.log(`🔄 Rechargement du module admin: ${moduleName}`);
  
  delete adminModuleCache[moduleName];
  
  const existingSection = document.getElementById(moduleName);
  if (existingSection) {
    existingSection.remove();
  }
  
  await showAdminModule(moduleName);
}

function injectAdminModuleStyles(moduleName, container) {
  const styles = container.querySelectorAll('style, link[rel="stylesheet"]');
  const styleId = `style-admin-${moduleName}`;
  
  const existingStyle = document.getElementById(styleId);
  if (existingStyle) {
    existingStyle.remove();
  }

  styles.forEach((style, index) => {
    const clonedStyle = style.cloneNode(true);
    clonedStyle.id = index === 0 ? styleId : `${styleId}-${index}`;
    document.head.appendChild(clonedStyle);
  });
}

async function showAdminModule(moduleName) {
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

  localStorage.setItem('activeAdminModule', moduleName);
  
  const allSections = document.querySelectorAll('.content-section');
  allSections.forEach(section => {
    section.classList.remove('active');
    section.style.display = 'none';
  });
  
  let targetSection = document.getElementById(moduleName);
  
  if (!targetSection) {
    console.log(`📥 Chargement du module admin externe: ${moduleName}`);
    
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
      if (mainContent) mainContent.innerHTML = '';
      
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = moduleHTML;
      
      injectAdminModuleStyles(moduleName, tempDiv);
      
      const newSection = tempDiv.querySelector('.content-section');
      
      if (newSection) {
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
  
  if (targetSection) {
    targetSection.style.display = 'block';
    targetSection.classList.add('active');
    
    const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
    document.dispatchEvent(event);
    
    console.log(`✅ Module admin ${moduleName} affiché`);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const menuItems = document.querySelectorAll('.menu-item');
  
  menuItems.forEach(item => {
    item.addEventListener('click', async (e) => {
      e.preventDefault();
      const moduleName = item.dataset.module;
      
      menuItems.forEach(mi => mi.classList.remove('active'));
      item.classList.add('active');
      
      await showAdminModule(moduleName);
      
      if (window.innerWidth <= 768) {
        document.querySelector('.sidebar-menu')?.classList.remove('mobile-open');
        document.getElementById('menu-toggle')?.classList.remove('active');
      }
    });
  });
  
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

window.loadAdminModule = loadAdminModule;
window.showAdminModule = showAdminModule;
window.reloadAdminModule = reloadAdminModule;

let autoReloadInterval = null;
let lastModuleContent = {};

function startAutoReload() {
  if (autoReloadInterval) return;
  
  autoReloadInterval = setInterval(async () => {
    const activeMenuItem = document.querySelector('.menu-item.active');
    if (!activeMenuItem) return;
    
    const moduleName = activeMenuItem.dataset.module;
    if (!moduleName) return;
    
    const timestamp = new Date().getTime();
    const modulePath = adminModules[moduleName];
    if (!modulePath) return;
    
    try {
      const response = await fetch(`${modulePath}?t=${timestamp}`);
      if (!response.ok) return;
      
      const newContent = await response.text();
      
      if (lastModuleContent[moduleName] && lastModuleContent[moduleName] !== newContent) {
        console.log(`🔄 Module ${moduleName} mis à jour automatiquement`);
        
        const existingSection = document.getElementById(moduleName);
        if (existingSection) {
          existingSection.remove();
        }
        
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newContent;
        
        injectAdminModuleStyles(moduleName, tempDiv);
        
        const newSection = tempDiv.querySelector('.content-section');
        
        if (newSection) {
          const mainContent = document.querySelector('.main-content');
          if (mainContent) {
            mainContent.appendChild(newSection);
            newSection.style.display = 'block';
            newSection.classList.add('active');
            
            const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
            document.dispatchEvent(event);
          }
        }
      }
      
      lastModuleContent[moduleName] = newContent;
    } catch (error) {
      // Ignorer les erreurs silencieusement
    }
  }, 2000);
}

function stopAutoReload() {
  if (autoReloadInterval) {
    clearInterval(autoReloadInterval);
    autoReloadInterval = null;
  }
}

window.addEventListener('load', () => {
  startAutoReload();
  console.log('✅ Auto-reload activé - Les modifications s\'affichent automatiquement dans la zone principale');
});

window.startAutoReload = startAutoReload;
window.stopAutoReload = stopAutoReload;

document.addEventListener('DOMContentLoaded', () => {
  const navActions = document.querySelector('.nav-actions');
  if (navActions) {
    const reloadBtn = document.createElement('button');
    reloadBtn.id = 'reload-module-btn';
    reloadBtn.title = 'Recharger le module actuel';
    reloadBtn.style.cssText = 'background:var(--glass);border:1.5px solid rgba(91,62,150,.5);border-radius:50px;padding:6px 14px;color:var(--text);cursor:pointer;font-size:.82rem;transition:all .3s;backdrop-filter:blur(10px);display:inline-flex;align-items:center;gap:4px;margin-right:8px;';
    reloadBtn.innerHTML = (typeof t === 'function') ? t('reload') : '🔄 Recharger';
    reloadBtn.addEventListener('click', () => {
      const activeMenuItem = document.querySelector('.menu-item.active');
      if (activeMenuItem) {
        const moduleName = activeMenuItem.dataset.module;
        reloadAdminModule(moduleName);
        if (typeof showToast === 'function') {
          showToast('Module rechargé!', 'Module actualisé avec succès', 'success');
        }
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

document.addEventListener('adminModuleLoaded', (e) => {
  const { moduleName } = e.detail;
  console.log(`🎉 Événement adminModuleLoaded déclenché pour: ${moduleName}`);
  
  animateModuleElements(moduleName);
  
  switch (moduleName) {
    case 'dashboard':
      if (typeof loadAdminStats === 'function') {
        setTimeout(() => loadAdminStats(), 100);
      }
      break;
    case 'users':
      const waitForTable = setInterval(() => {
        if (document.getElementById('usersTableBody')) {
          clearInterval(waitForTable);
          loadUsers();
          initUserModuleLanguage();
        }
      }, 50);
      setTimeout(() => clearInterval(waitForTable), 3000);
      break;
  }
});

// ==================== FONCTIONS UTILITAIRES ====================

function animateModuleElements(moduleName) {
  const container = document.getElementById(moduleName);
  if (!container) return;

  const selectors = '.stat-card, .section, .quick-stat, .data-card, tbody tr, .info-card';
  const elements = container.querySelectorAll(selectors);
  
  elements.forEach((el, index) => {
    const delay = index * 50;
    setTimeout(() => {
      el.classList.add('animate-in');
    }, delay);
  });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== GESTION DES UTILISATEURS ====================

async function loadUsers() {
    console.log("📡 Chargement des utilisateurs...");

    const tableBody = document.getElementById("usersTableBody");
    
    if (!tableBody) {
        console.error("❌ usersTableBody introuvable !");
        setTimeout(loadUsers, 100);
        return;
    }

    try {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">⏳ Chargement...<\/td><\/tr>';
        
        const timestamp = Date.now();
        let url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?t=${timestamp}`;
        
        const savedOrder = localStorage.getItem('userSortOrder');
        if (savedOrder && savedOrder !== '') {
            url += `&order=${savedOrder}`;
        } else {
            url += `&order=desc`;
        }
        
        console.log("📡 Appel API:", url);
        
        const response = await fetch(url, {
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log("📄 Données reçues:", result);
        
        if (result.success && result.data) {
            // ── SOURCE COMPLÈTE : ne jamais filtrer avant de stocker ──
            currentUsersData = result.data.slice();

            // Réinitialiser le chip actif à "Tous" lors d'un rechargement API complet
            activeChipFilter = 'all';
            document.querySelectorAll('#users .filter-chips .chip').forEach((c, i) => {
                c.classList.toggle('active', i === 0);
            });

            // Construire la vue initiale en appliquant les filtres éventuellement sauvegardés
            let users = currentUsersData.slice();

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
                const roleFilter = document.getElementById('roleFilter');
                if (roleFilter) roleFilter.value = savedRoleFilter;
            }
            
            updateUsersTableFromData(users);
            refreshUserStats();
            refreshStatusStats(currentUsersData);
            
        } else {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#e74c3c;">❌ Erreur: ${result.error || 'Données invalides'}<\/td><\/tr>`;
        }
        
    } catch (error) {
        console.error("❌ Erreur:", error);
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:#e74c3c;">❌ Erreur: ${error.message}<\/td><\/tr>`;
    }
}

function updateUsersTableFromData(users) {
    console.log("📊 Mise à jour du tableau avec", users.length, "utilisateurs");
    
    const tableBody = document.getElementById('usersTableBody');
    if (!tableBody) {
        console.error("❌ usersTableBody non trouvé");
        return;
    }
    
    // Ne PAS écraser currentUsersData ici : c'est la source complète.
    // On stocke la vue filtrée courante pour la pagination.
    currentFilteredData = users;

    totalPages = Math.ceil(users.length / rowsPerPage);
    if (totalPages === 0) totalPages = 1;
    
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }
    
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, users.length);
    const pageUsers = users.slice(startIndex, endIndex);
    
    if (pageUsers.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Aucun utilisateur trouvé</td></tr>';
        updatePaginationButtons();
        return;
    }
    
    let html = '';
    pageUsers.forEach((user) => {
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
        
        const fullName = `${user.prenom || ''} ${user.nom || ''}`.trim();
        
        // ========== CORRECTION CRITIQUE : GESTION DES STATUTS ==========
        const rawStatus = user.status || 'actif';
        const status = normalizeStatus(rawStatus);
        
        let statusClass = '';
        let statusIcon = '';
        let statusLabel = '';
        
        switch(status) {
            case 'actif':
                statusClass = 'status-actif';
                statusIcon = '✅';
                statusLabel = typeof t === 'function' ? t('statusActive') : 'Actif';
                break;
            case 'inactif':
                statusClass = 'status-inactif';
                statusIcon = '⭕';
                statusLabel = typeof t === 'function' ? t('statusInactive') : 'Inactif';
                break;
            case 'suspendu':
                statusClass = 'status-suspendu';
                statusIcon = '🚫';
                statusLabel = typeof t === 'function' ? t('statusSuspended') : 'Suspendu';
                break;
            default:
                statusClass = 'status-actif';
                statusIcon = '✅';
                statusLabel = 'Actif';
                console.warn(`⚠️ Statut inconnu pour l'utilisateur ${user.id}: "${status}"`);
        }
        // ========== FIN CORRECTION ==========
        
        html += `
            <tr style="opacity: 1; animation: fadeIn 0.3s ease-in;">
                <td>${user.id || user.id_utilisateur || ''}</td>
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(user.email || '')}</td>
                <td><span class="role-badge ${roleClass}">${roleIcon} ${roleLabel}</span></td>
                <td>${user.date_creation || ''}</td>
                <td><span class="status-badge ${statusClass}">${statusIcon} ${statusLabel}</span></td>
                <td class="actions-cell">
                    <button class="action-btn edit" onclick="editUser(${user.id || user.id_utilisateur})" title="Modifier">
                        ✏️
                    </button>
                    ${user.role !== 'admin' ? `
                    <button class="action-btn suspend ${status === 'suspendu' ? 'unsuspend' : ''}" 
                            onclick="suspendUser(${user.id || user.id_utilisateur}, '${status}')" 
                            title="${status === 'suspendu' ? 'Réactiver' : 'Suspendre'}">
                        ${status === 'suspendu' ? '✅' : '🚫'}
                    </button>` : ''}
                    <button class="action-btn delete" onclick="deleteUser(${user.id || user.id_utilisateur})" title="Supprimer">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
    updatePaginationButtons();
    refreshStatusStats(users);
    
    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px;">📭 Aucun utilisateur trouvé</td></tr>';
    } else {
        const infoSpan = document.getElementById('paginationInfo');
        if (infoSpan) {
            const start = startIndex + 1;
            const end = endIndex;
            infoSpan.innerHTML = `${start}-${end} sur ${users.length} utilisateurs`;
        }
    }
    
    console.log(`✅ Page ${currentPage}/${totalPages} - Affichage de ${pageUsers.length} utilisateurs`);
}

function updatePaginationButtons() {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer) return;
    
    const infoSpan = document.getElementById('paginationInfo');
    if (infoSpan && currentFilteredData) {
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, currentFilteredData.length);
        const start = startIndex + 1;
        const end = endIndex;
        infoSpan.innerHTML = `${start}-${end} sur ${currentFilteredData.length} utilisateurs`;
    }
    
    let html = '';
    
    html += `<button class="page-btn" onclick="previousPage()" ${currentPage === 1 ? 'disabled' : ''}>
        Précédent
    </button>`;
    
    const maxButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    if (startPage > 1) {
        html += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            html += `<button class="page-btn disabled" disabled>...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
            ${i}
        </button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<button class="page-btn disabled" disabled>...</button>`;
        }
        html += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }
    
    html += `<button class="page-btn" onclick="nextPage()" ${currentPage === totalPages ? 'disabled' : ''}>
        Suivant
    </button>`;
    
    paginationContainer.innerHTML = html;
}

function goToPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    updateUsersTableFromData(currentFilteredData);
}

function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        updateUsersTableFromData(currentFilteredData);
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        updateUsersTableFromData(currentFilteredData);
    }
}

function setRowsPerPage(rows) {
    rowsPerPage = rows;
    currentPage = 1;
    updateUsersTableFromData(currentFilteredData);
}

function refreshUsers() {
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

async function exportData(type) {
    console.log(`📥 Export des données ${type} en cours...`);
    
    if (type === 'users') {
        await exportUsersToCSV();
    } else {
        if (typeof showToast === 'function') {
            showToast('Export', `Export des données ${type} en cours...`, 'info');
        }
    }
}

async function exportUsersToCSV() {
    try {
        if (typeof showToast === 'function') {
            showToast('Export', 'Préparation de l\'export...', 'info');
        }
        
        const savedOrder = localStorage.getItem('userSortOrder') || 'desc';
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${savedOrder}&t=${Date.now()}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            throw new Error('Impossible de récupérer les données');
        }
        
        let users = result.data;
        
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
            if (typeof showToast === 'function') {
                showToast('Export', 'Aucune donnée à exporter', 'warning');
            }
            return;
        }
        
        const csvData = convertUsersToCSV(users);
        downloadCSV(csvData, `utilisateurs_${formatDateForFilename()}.csv`);
        
        if (typeof showToast === 'function') {
            showToast('Export réussi', `${users.length} utilisateur(s) exporté(s)`, 'success');
        }
        
    } catch (error) {
        console.error('❌ Erreur export:', error);
        if (typeof showToast === 'function') {
            showToast('Erreur', 'Impossible d\'exporter les données', 'error');
        }
    }
}

function convertUsersToCSV(users) {
    const headers = [
        'ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Date inscription', 'Statut'
    ];
    
    const rows = users.map(user => {
        let formattedDate = '';
        if (user.date_creation) {
            formattedDate = user.date_creation.split(' ')[0];
        }
        
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
        
        const status = user.status === 'suspendu' ? 'Suspendu' : (user.status === 'inactif' ? 'Inactif' : 'Actif');
        
        return [
            escapeCSVField(user.id || user.id_utilisateur || ''),
            escapeCSVField(user.nom || ''),
            escapeCSVField(user.prenom || ''),
            escapeCSVField(user.email || ''),
            escapeCSVField(roleFrench),
            escapeCSVField(formattedDate),
            escapeCSVField(status)
        ];
    });
    
    const csvContent = [
        headers.join(';'),
        ...rows.map(row => row.join(';'))
    ].join('\n');
    
    return '\uFEFF' + csvContent;
}

function escapeCSVField(field) {
    if (field === undefined || field === null) return '';
    
    let stringField = String(field);
    
    if (stringField.includes('"') || stringField.includes(';') || stringField.includes('\n') || stringField.includes(',')) {
        stringField = stringField.replace(/"/g, '""');
        return `"${stringField}"`;
    }
    
    return stringField;
}

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

function downloadCSV(csvContent, filename) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

function addUser() {
    if (typeof window.showAddUserModal === 'function') {
        window.showAddUserModal();
    }
}

function editUser(id) {
    if (typeof window.showEditUserModal === 'function') {
        window.showEditUserModal(id);
    }
}

// ==================== SUSPENSION / RÉACTIVATION (CORRIGÉ) ====================
window.suspendUser = async function(id, currentStatus) {
    console.log(`🔍 suspendUser appelé - id: ${id}, currentStatus: ${currentStatus}`);
    
    const isSuspended = currentStatus === 'suspendu';
    const action = isSuspended ? 'réactiver' : 'suspendre';
    const newStatus = isSuspended ? 'actif' : 'suspendu';

    if (!confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) return;

    try {
        if (typeof showToast === 'function') {
            showToast(isSuspended ? 'Réactivation' : 'Suspension', 'En cours...', 'info');
        }

        const response = await fetch(
            'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/suspendUser.php',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ id: parseInt(id), status: newStatus })
            }
        );

        const result = await response.json();
        console.log("📡 Réponse suspendUser:", result);

        if (result.success) {
            await loadUsers();
            if (typeof showToast === 'function') {
                showToast(
                    'Succès',
                    result.message || (isSuspended ? 'Utilisateur réactivé avec succès' : 'Utilisateur suspendu avec succès'),
                    'success'
                );
            }
        } else {
            if (typeof showToast === 'function') {
                showToast('Erreur', result.message || 'Impossible de modifier le statut', 'error');
            }
        }
    } catch (error) {
        console.error('❌ Erreur suspend:', error);
        if (typeof showToast === 'function') {
            showToast('Erreur réseau', 'Impossible de contacter le serveur', 'error');
        }
    }
};

// ==================== SUPPRESSION ====================
window.deleteUser = async function(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        try {
            if (typeof showToast === 'function') {
                showToast('Suppression', 'Suppression en cours...', 'info');
            }
            const response = await fetch(`http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/deleteUser.php?id=${id}`);
            await loadUsers();
            if (typeof showToast === 'function') {
                showToast('Succès', 'Utilisateur supprimé avec succès', 'success');
            }
        } catch (error) {
            console.error('❌ Erreur:', error);
            if (typeof showToast === 'function') {
                showToast('Erreur', 'Impossible de supprimer l\'utilisateur', 'error');
            }
        }
    }
};

// ==================== FILTRES ET RECHERCHE ====================

// État courant du chip sélectionné (déclaré en haut du fichier)

/**
 * Filtre le tableau par chip (statut ou rôle) en opérant sur currentUsersData côté client.
 * Ne touche pas la recherche dynamique (searchUsers).
 *
 * @param {HTMLElement} chipEl  - L'élément chip cliqué
 * @param {string}      filter  - 'all' | 'active' | 'inactive' | 'suspended' | 'admin' | 'user'
 */
function filterByChip(chipEl, filter) {
    // 1. Mettre à jour l'état actif visuel
    document.querySelectorAll('#users .filter-chips .chip').forEach(c => c.classList.remove('active'));
    if (chipEl) chipEl.classList.add('active');
    activeChipFilter = filter || 'all';

    // 2. Toujours partir de la SOURCE COMPLÈTE (jamais de la vue filtrée)
    if (!currentUsersData || currentUsersData.length === 0) return;
    let filtered = currentUsersData.slice();

    // 3. Filtrer par chip
    switch (activeChipFilter) {
        case 'active':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'actif');
            break;
        case 'inactive':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'inactif');
            break;
        case 'suspended':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'suspendu');
            break;
        case 'admin':
            filtered = filtered.filter(u => u.role === 'admin');
            break;
        case 'user':
            filtered = filtered.filter(u => !u.role || u.role === 'utilisateur' || u.role === 'user');
            break;
        case 'all':
        default:
            // pas de filtre supplémentaire
            break;
    }

    // 4. Appliquer le filtre rôle du <select> si actif
    const roleSelect = document.getElementById('roleFilter');
    if (roleSelect && roleSelect.value !== '') {
        filtered = filtered.filter(u => u.role === roleSelect.value);
    }

    // 5. Appliquer la recherche texte si active (sans toucher à searchUsers)
    const searchTerm = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
    if (searchTerm !== '') {
        filtered = filtered.filter(u => {
            const fullName = `${u.prenom || ''} ${u.nom || ''}`.toLowerCase();
            const email = (u.email || '').toLowerCase();
            return fullName.includes(searchTerm) || email.includes(searchTerm);
        });
    }

    // 6. Remettre en page 1 et afficher
    currentPage = 1;
    updateUsersTableFromData(filtered);

    if (typeof showToast === 'function') {
        const labels = {
            all: 'Tous les utilisateurs',
            active: 'Utilisateurs actifs',
            inactive: 'Utilisateurs inactifs',
            suspended: 'Utilisateurs suspendus',
            admin: 'Administrateurs',
            user: 'Utilisateurs standard'
        };
        showToast('Filtre chip', labels[activeChipFilter] || activeChipFilter, 'info');
    }
}

async function searchUsers() {
    console.log("🔍 Recherche en temps réel...");

    const searchTerm = document.getElementById('searchInput')?.value.trim().toLowerCase() || '';
    localStorage.setItem('userSearchTerm', searchTerm);

    // Si la source complète n'est pas encore chargée, faire un fetch initial
    if (!currentUsersData || currentUsersData.length === 0) {
        await loadUsers();
        return;
    }

    // Partir toujours de la SOURCE COMPLÈTE
    let filtered = currentUsersData.slice();

    // Appliquer le chip actif
    switch (activeChipFilter) {
        case 'active':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'actif');
            break;
        case 'inactive':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'inactif');
            break;
        case 'suspended':
            filtered = filtered.filter(u => normalizeStatus(u.status) === 'suspendu');
            break;
        case 'admin':
            filtered = filtered.filter(u => u.role === 'admin');
            break;
        case 'user':
            filtered = filtered.filter(u => !u.role || u.role === 'utilisateur' || u.role === 'user');
            break;
        // 'all' : pas de filtre supplémentaire
    }

    // Appliquer le filtre rôle du <select>
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter && roleFilter.value !== '') {
        filtered = filtered.filter(u => u.role === roleFilter.value);
        localStorage.setItem('userRoleFilter', roleFilter.value);
    } else {
        localStorage.removeItem('userRoleFilter');
    }

    // Appliquer la recherche texte
    if (searchTerm !== '') {
        filtered = filtered.filter(user => {
            const fullName = `${user.prenom || ''} ${user.nom || ''}`.toLowerCase();
            const email = (user.email || '').toLowerCase();
            return fullName.includes(searchTerm) || email.includes(searchTerm);
        });
    }

    currentPage = 1;
    updateUsersTableFromData(filtered);

    if (filtered.length === 0 && searchTerm !== '') {
        const tableBody = document.getElementById('usersTableBody');
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 40px;">🔍 Aucun utilisateur trouvé pour "${escapeHtml(searchTerm)}"</td></tr>`;
        }
    }
}

let searchTimeout;
function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchUsers();
    }, 300);
}

// searchUsers est exposé directement (le HTML appelle searchUsers() via onkeyup)
// debouncedSearch est disponible si besoin mais n'est plus l'alias principal

function filterUsers() {
    console.log("🔄 Function filterUsers() appelée");

    const roleFilter = document.getElementById('roleFilter');
    if (!roleFilter) {
        console.error("❌ Élément roleFilter non trouvé");
        return;
    }

    const role = roleFilter.value;
    console.log("📊 Rôle sélectionné:", role || 'Tous');

    if (role) {
        localStorage.setItem('userRoleFilter', role);
    } else {
        localStorage.removeItem('userRoleFilter');
    }

    if (!currentUsersData || currentUsersData.length === 0) {
        loadUsers();
        return;
    }

    // Partir de la SOURCE COMPLÈTE
    let filtered = currentUsersData.slice();

    // Appliquer le chip actif
    switch (activeChipFilter) {
        case 'active':    filtered = filtered.filter(u => normalizeStatus(u.status) === 'actif');    break;
        case 'inactive':  filtered = filtered.filter(u => normalizeStatus(u.status) === 'inactif');  break;
        case 'suspended': filtered = filtered.filter(u => normalizeStatus(u.status) === 'suspendu'); break;
        case 'admin':     filtered = filtered.filter(u => u.role === 'admin');                       break;
        case 'user':      filtered = filtered.filter(u => !u.role || u.role === 'utilisateur' || u.role === 'user'); break;
    }

    // Appliquer le filtre rôle du <select>
    if (role !== '') {
        filtered = filtered.filter(u => u.role === role);
    }

    // Appliquer la recherche texte
    const searchTerm = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
    if (searchTerm !== '') {
        filtered = filtered.filter(u => {
            const fullName = `${u.prenom || ''} ${u.nom || ''}`.toLowerCase();
            const email = (u.email || '').toLowerCase();
            return fullName.includes(searchTerm) || email.includes(searchTerm);
        });
    }

    currentPage = 1;
    updateUsersTableFromData(filtered);

    if (typeof showToast === 'function') {
        const message = role ? `Filtre: ${getRoleLabel(role)}` : 'Affichage de tous les utilisateurs';
        showToast('Filtre', message, 'success');
    }

    console.log(`✅ ${filtered.length} utilisateurs filtrés par rôle: ${role || 'tous'}`);
}

function getRoleLabel(role) {
    const roles = {
        'user': 'Utilisateur',
        'admin': 'Administrateur',
        'nutritionniste': 'Nutritionniste',
        'ecologiste': 'Écologiste'
    };
    return roles[role] || role;
}

async function tri() {
    console.log("🔄 Function tri() appelée");
    
    const selectTri = document.getElementById('triDate');
    if (!selectTri) {
        console.error("❌ Élément triDate non trouvé");
        return;
    }
    
    const order = selectTri.value;
    console.log("📊 Ordre sélectionné:", order);
    
    if (order === '') {
        console.log("ℹ️ Aucun tri sélectionné");
        return;
    }
    
    localStorage.setItem('userSortOrder', order);
    
    const tableBody = document.getElementById('usersTableBody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">⏳ Tri en cours...<\/td><\/tr>';
    }
    
    try {
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${order}&t=${Date.now()}`;
        console.log("📡 Appel API:", url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log("📄 Réponse JSON reçue:", result);
        
        if (result.success && result.data) {
            // Mettre à jour la source complète avec les données triées
            currentUsersData = result.data.slice();
            currentPage = 1;
            updateUsersTableFromData(currentUsersData);
            
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
        loadUsers();
    }
}

// ==================== STATISTIQUES PAR RÔLE ====================
let currentChart = null;
let currentChartType = 'pie';

function calculateUserStats(users) {
    const stats = {
        admin:          { count: 0, label: 'Administrateurs', icon: '🛡️', color: '#e74c3c' },
        nutritionniste: { count: 0, label: 'Nutritionnistes', icon: '🥗', color: '#2ecc71' },
        ecologiste:     { count: 0, label: 'Écologistes',     icon: '🌱', color: '#3498db' },
        user:           { count: 0, label: 'Utilisateurs',    icon: '👤', color: '#9b59b6' }
    };
    
    users.forEach(user => {
        const role = user.role;
        if (stats[role]) {
            stats[role].count++;
        } else {
            stats.user.count++;
        }
    });
    
    const total = users.length;
    
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
    
    statsArray.sort((a, b) => b.count - a.count);
    
    return { stats: statsArray, total };
}

function updateStatsLegend(users) {
    const { stats, total } = calculateUserStats(users);
    const legendContainer = document.getElementById('userStatsLegend');
    
    if (!legendContainer) return;
    
    if (stats.length === 0) {
        legendContainer.innerHTML = `<div class="stat-item">${t('noData')}</div>`;
        return;
    }
    
    let html = '';
    stats.forEach(stat => {
        // Traduction du libellé complet pour l'affichage
        let fullLabel = '';
        switch(stat.role) {
            case 'admin': fullLabel = t('roleAdmin'); break;
            case 'nutritionniste': fullLabel = t('roleNutritionist'); break;
            case 'ecologiste': fullLabel = t('roleEcologist'); break;
            default: fullLabel = t('roleUser');
        }
        
        html += `
            <div class="stat-item-enhanced">
                <div class="stat-role-enhanced">
                    <div class="stat-icon-enhanced" style="background: ${stat.color}20; border-left: 3px solid ${stat.color};">
                        <span>${stat.icon}</span>
                        <span class="stat-label-enhanced">${fullLabel}</span>
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
            <span>${t('totalUsers')}</span>
            <strong>${total}</strong>
        </div>
    `;
    
    legendContainer.innerHTML = html;
}

function destroyChart() {
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }
}

function createPieChart(users) {
    const { stats, total } = calculateUserStats(users);
    const ctx = document.getElementById('userPieChart')?.getContext('2d');
    
    if (!ctx) return;
    
    destroyChart();
    
    // Labels traduits avec noms courts
    const labels = stats.map(s => {
        let shortLabel = '';
        switch(s.role) {
            case 'admin': shortLabel = t('roleAdminShort'); break;
            case 'nutritionniste': shortLabel = t('roleNutritionistShort'); break;
            case 'ecologiste': shortLabel = t('roleEcologistShort'); break;
            default: shortLabel = t('roleUserShort');
        }
        return `${shortLabel} (${s.percent}%)`;
    });
    
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
                borderWidth: 1.5,
                hoverOffset: 10
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
                        font: { size: 10 },
                        padding: 8,
                        boxWidth: 10,
                        boxHeight: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const s = stats[context.dataIndex];
                            return `${s.label}: ${s.count} ${t('usersCount')} (${s.percent}%)`;
                        }
                    }
                }
            },
            layout: {
                padding: { top: 5, bottom: 5, left: 5, right: 5 }
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

function createBarChart(users) {
    const { stats, total } = calculateUserStats(users);
    const ctx = document.getElementById('userBarChart')?.getContext('2d');
    
    if (!ctx) return;
    
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }
    
    // Labels courts pour les barres
    const labels = stats.map(s => {
        switch(s.role) {
            case 'admin': return t('roleAdminShort');
            case 'nutritionniste': return t('roleNutritionistShort');
            case 'ecologiste': return t('roleEcologistShort');
            default: return t('roleUserShort');
        }
    });
    
    const data = stats.map(s => s.count);
    const colors = stats.map(s => s.color);
    
    currentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: t('chartUsersDataset'),
                data: data,
                backgroundColor: colors,
                borderColor: 'rgba(15, 35, 24, 0.8)',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.65,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const s = stats[context.dataIndex];
                            return `${s.label}: ${context.raw} ${t('usersCount')} (${s.percent}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(91, 62, 150, 0.08)' },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--muted'),
                        stepSize: 1,
                        font: { size: 10 }
                    },
                    title: {
                        display: true,
                        text: t('chartUsersDataset'),
                        color: getComputedStyle(document.body).getPropertyValue('--muted'),
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--text'),
                        font: { size: 11 }
                    }
                }
            },
            layout: {
                padding: { top: 5, bottom: 5, left: 5, right: 5 }
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

function switchChartType(type) {
    currentChartType = type;
    
    const pieBtn = document.querySelector('.toggle-btn[onclick="switchChartType(\'pie\')"]');
    const barBtn = document.querySelector('.toggle-btn[onclick="switchChartType(\'bar\')"]');
    
    if (pieBtn) pieBtn.classList.remove('active');
    if (barBtn) barBtn.classList.remove('active');
    
    const pieContainer = document.getElementById('pieChartContainer');
    const barContainer = document.getElementById('barChartContainer');
    
    if (type === 'pie') {
        if (pieBtn) pieBtn.classList.add('active');
        if (pieContainer) pieContainer.style.display = 'block';
        if (barContainer) barContainer.style.display = 'none';
        loadUserStatsForCurrentData();
    } else {
        if (barBtn) barBtn.classList.add('active');
        if (pieContainer) pieContainer.style.display = 'none';
        if (barContainer) barContainer.style.display = 'block';
        loadUserStatsForCurrentData();
    }
}

async function loadUserStatsForCurrentData() {
    try {
        const savedOrder = localStorage.getItem('userSortOrder') || 'desc';
        const url = `http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/tri.php?order=${savedOrder}&t=${Date.now()}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            let users = result.data;
            
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
            
            updateStatsLegend(users);
            
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

function refreshUserStats() {
    loadUserStatsForCurrentData();
}

// ==================== STATISTIQUES PAR STATUT (CORRIGÉ) ====================
let currentStatusChart = null;
let currentStatusChartType = 'pie';

function calculateStatusStats(users) {
    const stats = {
        actif:    { count: 0, label: 'Actif',    icon: '✅', color: '#2ecc71' },
        inactif:  { count: 0, label: 'Inactif',  icon: '⭕', color: '#95a5a6' },
        suspendu: { count: 0, label: 'Suspendu', icon: '🚫', color: '#e74c3c' }
    };

    users.forEach(user => {
        const s = user.status || 'actif';
        if (stats[s]) stats[s].count++;
        else stats.actif.count++;
    });

    const total = users.length;
    const statsArray = Object.entries(stats)
        .filter(([, v]) => v.count > 0)
        .map(([key, v]) => ({
            status: key,
            label: v.label,
            icon: v.icon,
            count: v.count,
            percent: total > 0 ? ((v.count / total) * 100).toFixed(1) : 0,
            color: v.color
        }))
        .sort((a, b) => b.count - a.count);

    return { stats: statsArray, total };
}

function updateStatusStatsLegend(users) {
    const { stats, total } = calculateStatusStats(users);
    const container = document.getElementById('userStatusStatsLegend');
    if (!container) return;

    if (stats.length === 0) {
        container.innerHTML = `<div class="stat-item">${t('noData')}</div>`;
        return;
    }

    let html = stats.map(s => {
        // Traduction du libellé complet du statut
        let fullLabel = '';
        switch(s.status) {
            case 'actif': fullLabel = t('statusActive'); break;
            case 'inactif': fullLabel = t('statusInactive'); break;
            case 'suspendu': fullLabel = t('statusSuspended'); break;
            default: fullLabel = s.label;
        }
        
        return `
            <div class="stat-item-enhanced">
                <div class="stat-role-enhanced">
                    <div class="stat-icon-enhanced" style="background:${s.color}20;border-left:3px solid ${s.color};">
                        <span>${s.icon}</span>
                        <span class="stat-label-enhanced">${fullLabel}</span>
                    </div>
                </div>
                <div class="stat-percent-enhanced"><span class="percent-value">${s.percent}%</span></div>
                <div class="stat-bar-enhanced">
                    <div class="stat-bar-fill-enhanced" style="width:${s.percent}%;background:${s.color};"></div>
                </div>
            </div>
        `;
    }).join('');

    html += `<div class="stats-total-enhanced"><span>${t('totalUsers')}</span><strong>${total}</strong></div>`;
    container.innerHTML = html;
}
function destroyStatusChart() {
    if (currentStatusChart) {
        currentStatusChart.destroy();
        currentStatusChart = null;
    }
}

function createStatusPieChart(users) {
    const { stats } = calculateStatusStats(users);
    const ctx = document.getElementById('userStatusPieChart')?.getContext('2d');
    if (!ctx) return;
    destroyStatusChart();

    // Labels courts pour les statuts
    const labels = stats.map(s => {
        let shortLabel = '';
        switch(s.status) {
            case 'actif': shortLabel = t('statusActiveShort'); break;
            case 'inactif': shortLabel = t('statusInactiveShort'); break;
            case 'suspendu': shortLabel = t('statusSuspendedShort'); break;
            default: shortLabel = s.label;
        }
        return `${shortLabel} (${s.percent}%)`;
    });

    currentStatusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: stats.map(s => s.count),
                backgroundColor: stats.map(s => s.color),
                borderColor: 'rgba(15,35,24,0.5)',
                borderWidth: 1.5,
                hoverOffset: 10
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
                        font: { size: 10 },
                        padding: 8,
                        boxWidth: 10,
                        boxHeight: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx2 => {
                            const s = stats[ctx2.dataIndex];
                            return `${s.label}: ${ctx2.raw} ${t('usersCount')} (${s.percent}%)`;
                        }
                    }
                }
            },
            layout: {
                padding: { top: 5, bottom: 5, left: 5, right: 5 }
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const s = stats[activeElements[0].index].status;
                    const chipMap = { actif: 'active', inactif: 'inactive', suspendu: 'suspended' };
                    const chip = document.querySelector(`.chip[onclick*="'${chipMap[s]}'"]`);
                    if (chip) filterByChip(chip, chipMap[s]);
                }
            }
        }
    });
}

function createStatusBarChart(users) {
    const { stats } = calculateStatusStats(users);
    const ctx = document.getElementById('userStatusBarChart')?.getContext('2d');
    if (!ctx) return;
    destroyStatusChart();

    // Labels courts pour les barres
    const labels = stats.map(s => {
        switch(s.status) {
            case 'actif': return t('statusActiveShort');
            case 'inactif': return t('statusInactiveShort');
            case 'suspendu': return t('statusSuspendedShort');
            default: return s.label;
        }
    });

    currentStatusChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: t('chartUsersDataset'),
                data: stats.map(s => s.count),
                backgroundColor: stats.map(s => s.color),
                borderColor: 'rgba(15,35,24,0.8)',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.65,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx2 => {
                            const s = stats[ctx2.dataIndex];
                            return `${s.label}: ${ctx2.raw} ${t('usersCount')} (${s.percent}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(91,62,150,0.08)' },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--muted'),
                        stepSize: 1,
                        font: { size: 10 }
                    },
                    title: {
                        display: true,
                        text: t('chartUsersDataset'),
                        color: getComputedStyle(document.body).getPropertyValue('--muted'),
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: getComputedStyle(document.body).getPropertyValue('--text'),
                        font: { size: 11 }
                    }
                }
            },
            layout: {
                padding: { top: 5, bottom: 5, left: 5, right: 5 }
            },
            onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                    const s = stats[activeElements[0].index].status;
                    const chipMap = { actif: 'active', inactif: 'inactive', suspendu: 'suspended' };
                    const chip = document.querySelector(`.chip[onclick*="'${chipMap[s]}'"]`);
                    if (chip) filterByChip(chip, chipMap[s]);
                }
            }
        }
    });
}

function switchStatusChartType(type) {
    currentStatusChartType = type;
    const pieBtn = document.querySelector('.toggle-btn[onclick="switchStatusChartType(\'pie\')"]');
    const barBtn = document.querySelector('.toggle-btn[onclick="switchStatusChartType(\'bar\')"]');
    const pieC = document.getElementById('statusPieChartContainer');
    const barC = document.getElementById('statusBarChartContainer');

    if (pieBtn) pieBtn.classList.remove('active');
    if (barBtn) barBtn.classList.remove('active');

    if (type === 'pie') {
        if (pieBtn) pieBtn.classList.add('active');
        if (pieC) pieC.style.display = 'block';
        if (barC) barC.style.display = 'none';
        if (currentUsersData.length > 0) createStatusPieChart(currentUsersData);
    } else {
        if (barBtn) barBtn.classList.add('active');
        if (pieC) pieC.style.display = 'none';
        if (barC) barC.style.display = 'block';
        if (currentUsersData.length > 0) createStatusBarChart(currentUsersData);
    }
}

function refreshStatusStats(users) {
    const data = users || currentUsersData;
    if (!data || data.length === 0) return;
    updateStatusStatsLegend(data);
    if (currentStatusChartType === 'pie') createStatusPieChart(data);
    else createStatusBarChart(data);
}

// ==================== TRADUCTION ====================

function applyUsersTranslations() {
    if (typeof t !== 'function') return;
    
    // Titre principal
    const moduleTitle = document.querySelector('#users .module-title');
    if (moduleTitle) moduleTitle.innerHTML = t('userManagement');

    // Boutons
    document.querySelectorAll('#users .btn').forEach(btn => {
        const oc = btn.getAttribute('onclick') || '';
        if (oc.includes('refreshUsers')) btn.innerHTML = t('refresh');
        else if (oc.includes('exportData')) btn.innerHTML = t('exportCSV');
        else if (oc.includes('addUser')) btn.innerHTML = t('addUser');
    });

    // Chips
    const chips = document.querySelectorAll('#users .filter-chips .chip');
    if (chips.length >= 6) {
        chips[0].textContent = t('all');
        chips[1].innerHTML = t('active');
        chips[2].innerHTML = t('inactive');
        chips[3].innerHTML = t('suspended');
        chips[4].innerHTML = t('admins');
        chips[5].innerHTML = t('users');
    }

    // Barre de recherche
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.placeholder = t('searchPlaceholder');

    // Filtre rôle
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter && roleFilter.options.length >= 5) {
        roleFilter.options[0].text = t('allRoles');
        roleFilter.options[1].text = '👤 ' + t('standardUser');
        roleFilter.options[2].text = '🛡️ ' + t('administrator');
        roleFilter.options[3].text = '🥗 ' + t('nutritionist');
        roleFilter.options[4].text = '🌱 ' + t('ecologist');
    }

    // Tri date
    const triDate = document.getElementById('triDate');
    if (triDate && triDate.options.length >= 3) {
        triDate.options[0].text = t('sortByDate');
        triDate.options[1].text = t('newestFirst');
        triDate.options[2].text = t('oldestFirst');
    }

    // Lignes par page
    const rowsSelect = document.getElementById('rowsPerPageSelect');
    if (rowsSelect) {
        Array.from(rowsSelect.options).forEach(opt => {
            opt.text = opt.value + ' ' + t('rowsPerPage');
        });
    }

    // En-têtes tableau
    const ths = document.querySelectorAll('#users .data-table thead th');
    if (ths.length >= 7) {
        ths[0].textContent = t('id');
        ths[1].textContent = t('fullName');
        ths[2].textContent = t('email');
        ths[3].textContent = t('role');
        ths[4].textContent = t('registrationDate');
        ths[5].textContent = t('status');
        ths[6].textContent = t('actions');
    }

    // Statistiques par rôle
    const statsTitle = document.querySelector('#users .stats-section:first-of-type .stats-header h3');
    if (statsTitle) statsTitle.innerHTML = t('userStats');

    // Statistiques par statut
    const statusStatsTitle = document.querySelector('#users .stats-section:last-of-type .stats-header h3');
    if (statusStatsTitle) statusStatsTitle.innerHTML = t('statusStats');

    // Légende
    const legendTitles = document.querySelectorAll('#users .legend-title');
    if (legendTitles.length >= 1) legendTitles[0].innerHTML = t('summaryRoles');
    if (legendTitles.length >= 2) legendTitles[1].innerHTML = t('summaryStatus');

    // Mise à jour des graphiques existants
    if (currentUsersData && currentUsersData.length > 0) {
        updateStatsLegend(currentUsersData);
        updateStatusStatsLegend(currentUsersData);
        updatePaginationButtons();
        
        if (currentChartType === 'pie') {
            createPieChart(currentUsersData);
        } else {
            createBarChart(currentUsersData);
        }
        
        if (currentStatusChartType === 'pie') {
            createStatusPieChart(currentUsersData);
        } else {
            createStatusBarChart(currentUsersData);
        }
    }
}

function initUserModuleLanguage() {
    if (typeof t === 'function') {
        applyUsersTranslations();
    }

    if (!window._usersLangListenerAttached) {
        document.addEventListener('languageChanged', () => {
            if (typeof t === 'function') {
                applyUsersTranslations();
            }
        });
        window._usersLangListenerAttached = true;
    }
}

// Exposer les fonctions globalement
window.switchChartType = switchChartType;
window.switchStatusChartType = switchStatusChartType;
window.refreshUserStats = refreshUserStats;
window.filterByChip = filterByChip;
window.searchUsers = searchUsers;   // ← directement la vraie fonction
window.debouncedSearch = debouncedSearch;
window.filterUsers = filterUsers;
window.tri = tri;
window.setRowsPerPage = setRowsPerPage;
window.previousPage = previousPage;
window.nextPage = nextPage;
window.goToPage = goToPage;
window.refreshUsers = refreshUsers;
window.exportData = exportData;
window.addUser = addUser;
window.editUser = editUser;
window.initUserModuleLanguage = initUserModuleLanguage;

console.log('✅ Admin Module Loader prêt (version corrigée avec statut suspendu)');