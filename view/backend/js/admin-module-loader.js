// Admin Module Loader - Charge les modules admin dynamiquement
console.log('🛡️ Admin Module Loader initialisé');

// Configuration des modules admin
const adminModules = {
  dashboard:  'modules/dashboard-admin.html',
  users:      'modules/users-admin.html',
  planning:   'modules/planning-admin.html',
  events:     'modules/events-admin.html',
  meals:      'modules/meals-admin.html',
  health:     'modules/health-admin.html',
  challenges: 'modules/challenges-admin.html',
  activity:   'modules/activity-admin.html',
  export:     'modules/export-admin.html'
};

// Cache des modules chargés
const adminModuleCache = {};

// Fonction pour charger un module admin (avec option de forcer le rechargement)
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
    const response  = await fetch(`${modulePath}?t=${timestamp}`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const html = await response.text();
    adminModuleCache[moduleName] = html;
    console.log(`✅ Module admin ${moduleName} chargé avec succès`);
    return html;
  } catch (error) {
    console.error(`❌ Erreur lors du chargement du module admin ${moduleName}:`, error);
    return null;
  }
}

// Vider le cache et recharger un module
async function reloadAdminModule(moduleName) {
  console.log(`🔄 Rechargement du module admin: ${moduleName}`);
  delete adminModuleCache[moduleName];
  const existingSection = document.getElementById(moduleName);
  if (existingSection) existingSection.remove();
  await showAdminModule(moduleName);
}

// Injecter les <style> d'un module
function injectAdminModuleStyles(moduleName, container) {
  const styles  = container.querySelectorAll('style, link[rel="stylesheet"]');
  const styleId = `style-admin-${moduleName}`;
  const existing = document.getElementById(styleId);
  if (existing) existing.remove();
  styles.forEach((style, index) => {
    const cloned = style.cloneNode(true);
    cloned.id = index === 0 ? styleId : `${styleId}-${index}`;
    document.head.appendChild(cloned);
  });
}

// Afficher un module admin
async function showAdminModule(moduleName) {
  // Bloquer le protocole file://
  if (window.location.protocol === 'file:') {
    const mainContent = document.querySelector('.main-content');
    if (mainContent && mainContent.innerHTML.trim() === '') {
      mainContent.innerHTML = `
        <div style="padding:40px;text-align:center;background:rgba(231,76,60,.1);border:1px solid #e74c3c;border-radius:18px;margin-top:20px;">
          <h2 style="color:#e74c3c;margin-bottom:15px;">⚠️ Accès Restreint (CORS)</h2>
          <p>Ouvrez le projet via un serveur local (XAMPP / Apache) et non directement depuis le système de fichiers.</p>
        </div>`;
    }
    console.error('❌ Fetch bloqué par le protocole file://. Utilisez un serveur local.');
    return;
  }

  // Mémoriser le module actif
  localStorage.setItem('activeAdminModule', moduleName);

  // Cacher toutes les sections
  document.querySelectorAll('.content-section').forEach(s => {
    s.classList.remove('active');
    s.style.display = 'none';
  });

  let targetSection = document.getElementById(moduleName);

  if (!targetSection) {
    const mainContent = document.querySelector('.main-content');
    if (mainContent && mainContent.innerHTML.trim() === '') {
      mainContent.innerHTML = `
        <div class="section-loader">
          <div class="section-loader-spinner"></div>
          <p style="color:var(--muted);font-size:1.1rem;">Préparation du module…</p>
        </div>`;
    }

    const moduleHTML = await loadAdminModule(moduleName);

    if (moduleHTML) {
      if (mainContent) mainContent.innerHTML = '';

      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = moduleHTML;

      injectAdminModuleStyles(moduleName, tempDiv);

      // ── Exécuter les <script> contenus dans le module ──
      // On extrait et réexécute les balises <script> car innerHTML ne les exécute pas
      const scripts = tempDiv.querySelectorAll('script');
      scripts.forEach(s => s.remove()); // on les enlève du DOM temporaire

      const newSection = tempDiv.querySelector('.content-section');
      if (newSection) {
        if (mainContent) {
          mainContent.appendChild(newSection);
          targetSection = newSection;
        }

        // Réexécuter les scripts dans le contexte global
        scripts.forEach(orig => {
          const s = document.createElement('script');
          if (orig.src) {
            s.src = orig.src;
          } else {
            s.textContent = orig.textContent;
          }
          document.body.appendChild(s);
        });
      }
    } else {
      console.error(`❌ Impossible de charger le module admin ${moduleName}`);
      const mainContent = document.querySelector('.main-content');
      if (mainContent) {
        mainContent.innerHTML = `
          <div style="padding:40px;text-align:center;background:rgba(231,76,60,.1);border:1px solid #e74c3c;border-radius:18px;">
            <h2 style="color:#e74c3c;">❌ Erreur de Chargement</h2>
            <p>Impossible de charger le module <strong>${moduleName}</strong>.</p>
            <button onclick="reloadAdminModule('${moduleName}')"
                    style="margin-top:20px;padding:10px 20px;background:var(--violet);border:none;border-radius:50px;color:#fff;cursor:pointer;">
              Réessayer
            </button>
          </div>`;
      }
      return;
    }
  }

  // Afficher la section
  if (targetSection) {
    targetSection.style.display = 'block';
    targetSection.classList.add('active');

    const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
    document.dispatchEvent(event);

    console.log(`✅ Module admin ${moduleName} affiché`);
  }
}

// ── Gestion des clics sur le menu sidebar ──
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

  // Charger le module mémorisé (ou dashboard par défaut)
  const activeModule = localStorage.getItem('activeAdminModule') || 'dashboard';
  const targetItem   = document.querySelector(`.menu-item[data-module="${activeModule}"]`);

  if (targetItem) {
    targetItem.click();
  } else {
    const first = document.querySelector('.menu-item[data-module="dashboard"]');
    if (first) { first.classList.add('active'); showAdminModule('dashboard'); }
  }
});

// Exposer globalement
window.loadAdminModule   = loadAdminModule;
window.showAdminModule   = showAdminModule;
window.reloadAdminModule = reloadAdminModule;

// ── Auto-reload (vérifie les changements de fichier toutes les 2 s) ──
let autoReloadInterval = null;
let lastModuleContent  = {};

function startAutoReload() {
  if (autoReloadInterval) return;
  autoReloadInterval = setInterval(async () => {
    const activeMenuItem = document.querySelector('.menu-item.active');
    if (!activeMenuItem) return;
    const moduleName = activeMenuItem.dataset.module;
    if (!moduleName) return;
    const modulePath = adminModules[moduleName];
    if (!modulePath) return;

    try {
      const response = await fetch(`${modulePath}?t=${new Date().getTime()}`);
      if (!response.ok) return;
      const newContent = await response.text();

      if (lastModuleContent[moduleName] && lastModuleContent[moduleName] !== newContent) {
        console.log(`🔄 Module ${moduleName} mis à jour automatiquement`);
        delete adminModuleCache[moduleName];
        const existingSection = document.getElementById(moduleName);
        if (existingSection) existingSection.remove();

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newContent;
        injectAdminModuleStyles(moduleName, tempDiv);

        const scripts = tempDiv.querySelectorAll('script');
        scripts.forEach(s => s.remove());

        const newSection = tempDiv.querySelector('.content-section');
        if (newSection) {
          const mainContent = document.querySelector('.main-content');
          if (mainContent) {
            mainContent.appendChild(newSection);
            newSection.style.display = 'block';
            newSection.classList.add('active');

            scripts.forEach(orig => {
              const s = document.createElement('script');
              if (orig.src) { s.src = orig.src; } else { s.textContent = orig.textContent; }
              document.body.appendChild(s);
            });

            const event = new CustomEvent('adminModuleLoaded', { detail: { moduleName } });
            document.dispatchEvent(event);
          }
        }
      }
      lastModuleContent[moduleName] = newContent;
    } catch (_) { /* ignore silently */ }
  }, 2000);
}

function stopAutoReload() {
  if (autoReloadInterval) { clearInterval(autoReloadInterval); autoReloadInterval = null; }
}

window.addEventListener('load', () => {
  startAutoReload();
  console.log('✅ Auto-reload activé');
});

window.startAutoReload = startAutoReload;
window.stopAutoReload  = stopAutoReload;

// ── Bouton Recharger dans la navbar ──
document.addEventListener('DOMContentLoaded', () => {
  const navActions = document.querySelector('.nav-actions');
  if (navActions) {
    const reloadBtn = document.createElement('button');
    reloadBtn.id        = 'reload-module-btn';
    reloadBtn.title     = 'Recharger le module actuel';
    reloadBtn.innerHTML = '🔄 Recharger';
    reloadBtn.style.cssText = 'background:var(--glass);border:1.5px solid rgba(91,62,150,.5);border-radius:50px;padding:6px 14px;color:var(--text);cursor:pointer;font-size:.82rem;transition:all .3s;backdrop-filter:blur(10px);display:inline-flex;align-items:center;gap:4px;margin-right:8px;';
    reloadBtn.addEventListener('click', () => {
      const activeMenuItem = document.querySelector('.menu-item.active');
      if (activeMenuItem) {
        reloadAdminModule(activeMenuItem.dataset.module);
        if (typeof showToast === 'function') showToast('Module rechargé !', 'Module actualisé', 'success');
      }
    });
    reloadBtn.addEventListener('mouseenter', () => { reloadBtn.style.background = 'rgba(91,62,150,.2)'; reloadBtn.style.transform = 'scale(1.05)'; reloadBtn.style.borderColor = 'var(--violet)'; });
    reloadBtn.addEventListener('mouseleave', () => { reloadBtn.style.background = 'var(--glass)';       reloadBtn.style.transform = 'scale(1)';    reloadBtn.style.borderColor = 'rgba(91,62,150,.5)'; });
    navActions.insertBefore(reloadBtn, navActions.firstChild);
  }
});

// ── Événement adminModuleLoaded ──
document.addEventListener('adminModuleLoaded', (e) => {
  const { moduleName } = e.detail;
  console.log(`🎉 adminModuleLoaded: ${moduleName}`);

  animateModuleElements(moduleName);

  switch (moduleName) {
    case 'dashboard':
      if (typeof loadAdminStats === 'function') setTimeout(() => loadAdminStats(), 100);
      break;
    case 'users':
      if (typeof loadUsers === 'function') setTimeout(() => loadUsers(), 100);
      break;
    case 'health':
      // Les fonctions du module santé sont auto-exécutées via IIFE dans health-admin.html
      // On ne réinitialise pas ici pour éviter les doubles appels.
      console.log('✅ Module Santé chargé – données auto-chargées via IIFE interne');
      break;
  }
});

// ── Animation séquentielle des éléments ──
function animateModuleElements(moduleName) {
  const container = document.getElementById(moduleName);
  if (!container) return;
  const elements = container.querySelectorAll('.stat-card, .section, .quick-stat, .data-card, tbody tr, .info-card');
  elements.forEach((el, i) => {
    setTimeout(() => el.classList.add('animate-in'), i * 50);
  });
}

// ── Fonctions utilitaires admin (stubs) ──
function refreshUsers()       { if (typeof showToast === 'function') showToast('Utilisateurs actualisés','','success'); }
function exportData(type)     { if (typeof showToast === 'function') showToast(`Export ${type} en cours…`,'','info'); }
function addUser()            { if (typeof showToast === 'function') showToast('Fonctionnalité à implémenter','','info'); }
function editUser(id)         { if (typeof showToast === 'function') showToast(`Modification utilisateur ${id}`,'','info'); }
function deleteUser(id)       { if (confirm('Supprimer cet utilisateur ?')) { if (typeof showToast === 'function') showToast(`Utilisateur ${id} supprimé`,'','success'); } }
function refreshEvents()      { if (typeof showToast === 'function') showToast('Événements actualisés','','success'); }
function addEvent()           { if (typeof showToast === 'function') showToast('Fonctionnalité à implémenter','','info'); }
function editEvent(id)        { if (typeof showToast === 'function') showToast(`Modification événement ${id}`,'','info'); }
function deleteEvent(id)      { if (confirm('Supprimer cet événement ?')) { if (typeof showToast === 'function') showToast(`Événement ${id} supprimé`,'','success'); } }
function refreshActivity()    { if (typeof showToast === 'function') showToast('Logs actualisés','','success'); }
function exportLogs()         { if (typeof showToast === 'function') showToast('Export des logs en cours…','','info'); }
function exportAll()          { if (typeof showToast === 'function') showToast('Export complet en cours…','','info'); }
function filterByChip(chip, filter) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  chip.classList.add('active');
}
function searchUsers()  { const v = document.getElementById('searchInput')?.value; console.log('search users:', v); }
function filterUsers()  { const v = document.getElementById('roleFilter')?.value;  console.log('filter role:', v);  }

console.log('✅ Admin Module Loader prêt');
