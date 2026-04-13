// Module Loader - Charge les modules HTML dynamiquement
console.log('🔄 Module Loader initialisé');

// Configuration des modules
const modules = {
  welcome: 'modules/welcome.html',
  users: 'modules/users.html',
  planning: 'modules/planning.html',
  events: 'modules/evenement.php',
  meals: 'modules/meals.html',
  health: 'modules/health.html',
  challenges: 'modules/challenges.html'
};

// Cache des modules chargés
const moduleCache = {};

// Fonction pour charger un module (avec option de forcer le rechargement)
async function loadModule(moduleName, forceReload = false) {
  console.log(`📥 Chargement du module: ${moduleName}`);
  
  // Vérifier si le module est en cache (sauf si forceReload)
  if (moduleCache[moduleName] && !forceReload) {
    console.log(`✅ Module ${moduleName} chargé depuis le cache`);
    return moduleCache[moduleName];
  }
  
  // Charger le module depuis le fichier
  const modulePath = modules[moduleName];
  if (!modulePath) {
    console.error(`❌ Module ${moduleName} non trouvé`);
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
    moduleCache[moduleName] = html;
    lastModuleContent[moduleName] = html; // Initialiser pour l'auto-reload
    console.log(`✅ Module ${moduleName} chargé avec succès`);
    
    return html;
  } catch (error) {
    console.error(`❌ Erreur lors du chargement du module ${moduleName}:`, error);
    return null;
  }
}

// Fonction pour recharger un module (vider le cache et recharger)
async function reloadModule(moduleName) {
  console.log(`🔄 Rechargement du module: ${moduleName}`);
  
  // Supprimer du cache
  delete moduleCache[moduleName];
  
  // Supprimer la section existante
  const existingSection = document.getElementById(moduleName);
  if (existingSection) {
    existingSection.remove();
  }
  
  // Recharger le module
  await showModule(moduleName);
}

// Fonction pour injecter les styles d'un module
function injectModuleStyles(moduleName, container) {
  const styles = container.querySelectorAll('style, link[rel="stylesheet"]');
  const styleId = `style-${moduleName}`;
  
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

// Fonction pour exécuter les scripts d'un module
function executeModuleScripts(container) {
  const scripts = container.querySelectorAll('script');
  scripts.forEach(oldScript => {
    const newScript = document.createElement('script');
    Array.from(oldScript.attributes).forEach(attr => {
      newScript.setAttribute(attr.name, attr.value);
    });
    
    // Si le script a un src, ajouter un timestamp pour éviter le cache
    if (oldScript.src) {
      const url = new URL(oldScript.src, window.location.href);
      url.searchParams.set('v', Date.now());
      newScript.src = url.href;
    } else {
      newScript.textContent = oldScript.textContent;
    }
    
    document.body.appendChild(newScript);
    // Supprimer le script après exécution pour éviter de polluer le DOM (facultatif)
    // if (!oldScript.src) newScript.remove();
  });
}

// Fonction pour afficher un module
async function showModule(moduleName) {
  // Sauvegarder le module actif
  localStorage.setItem('activeModule', moduleName);
  
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
    console.log(`📥 Chargement du module externe: ${moduleName}`);
    
    const moduleHTML = await loadModule(moduleName);
    
    if (moduleHTML) {
      // Créer un conteneur temporaire pour manipuler le HTML
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = moduleHTML;
      
      // Injecter les styles
      injectModuleStyles(moduleName, tempDiv);
      
      // Récupérer la section principale
      const newSection = tempDiv.querySelector('.content-section');
      
      if (newSection) {
        // Ajouter la section au main-content
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
          mainContent.appendChild(newSection);
          targetSection = newSection;
          
          // Exécuter les scripts du module (APRÈS l'ajout au DOM)
          executeModuleScripts(tempDiv);
        }
      }
    } else {
      console.error(`❌ Impossible de charger le module ${moduleName}`);
      return;
    }
  }
  
  // Afficher la section
  if (targetSection) {
    targetSection.style.display = 'block';
    targetSection.classList.add('active');
    
    // Déclencher l'événement de chargement du module
    const event = new CustomEvent('moduleLoaded', { detail: { moduleName } });
    document.dispatchEvent(event);
    
    console.log(`✅ Module ${moduleName} affiché`);
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
      await showModule(moduleName);
      
      // Fermer le menu mobile si ouvert
      if (window.innerWidth <= 768) {
        document.querySelector('.sidebar-menu')?.classList.remove('mobile-open');
        document.getElementById('menu-toggle')?.classList.remove('active');
      }
    });
  });
  
  // Gestion des boutons CTA dans welcome
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.welcome-btn[data-module]');
    if (btn) {
      e.preventDefault();
      const moduleName = btn.dataset.module;
      const targetItem = document.querySelector(`.menu-item[data-module="${moduleName}"]`);
      if (targetItem) {
        targetItem.click();
      }
    }
  });
  
  // Charger le module d'accueil par défaut
  const activeModule = localStorage.getItem('activeModule') || 'welcome';
  const targetItem = document.querySelector(`.menu-item[data-module="${activeModule}"]`);
  
  if (targetItem) {
    targetItem.click();
  } else {
    const firstMenuItem = document.querySelector('.menu-item[data-module="welcome"]');
    if (firstMenuItem) {
      firstMenuItem.classList.add('active');
      showModule('welcome');
    }
  }
});

// Exposer les fonctions globalement
window.loadModule = loadModule;
window.showModule = showModule;
window.reloadModule = reloadModule;

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
    const modulePath = modules[moduleName];
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
        injectModuleStyles(moduleName, tempDiv);
        
        // Extraire la nouvelle section
        const newSection = tempDiv.querySelector('.content-section');
        
        if (newSection) {
          // Ajouter la nouvelle section au main-content
          const mainContent = document.querySelector('.main-content');
          if (mainContent) {
            mainContent.appendChild(newSection);
            newSection.style.display = 'block';
            newSection.classList.add('active');
            
            // Exécuter les scripts du module (APRÈS l'ajout au DOM)
            executeModuleScripts(tempDiv);
            
            // Déclencher l'événement de chargement du module
            const event = new CustomEvent('moduleLoaded', { detail: { moduleName } });
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
        reloadModule(moduleName);
        showToast('Module rechargé!', 'success');
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

// Événement personnalisé pour les modules
document.addEventListener('moduleLoaded', (e) => {
  const { moduleName } = e.detail;
  console.log(`🎉 Événement moduleLoaded déclenché pour: ${moduleName}`);
  
  // Initialiser les fonctionnalités spécifiques au module
  switch (moduleName) {
    case 'events':
      // Charger les événements si la fonction existe
      if (typeof loadEvents === 'function') {
        setTimeout(() => loadEvents(), 100);
      }
      break;
    case 'challenges':
      // Charger les défis si la fonction existe
      if (typeof initChallenges === 'function') {
        setTimeout(() => initChallenges(), 100);
      } else if (typeof loadChallenges === 'function') {
        setTimeout(() => loadChallenges(), 100);
      }
      break;
  }
});

console.log('✅ Module Loader prêt');
