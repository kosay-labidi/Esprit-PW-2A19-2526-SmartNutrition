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
let planningDataCache  = [];
let planningFiltreML   = 'all';
let planningSort       = { col: 'id', dir: 'desc' };

// ── Chargement & rendu ────────────────────────────────────────────────────
function loadPlanningData() {
  const tbody = document.getElementById('planningTableBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:var(--muted);">⏳ Chargement...</td></tr>';

  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'planning/listDemandeplanning.php?json=1', true);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

  xhr.onload = function() {
    const tb = document.getElementById('planningTableBody');
    if (xhr.status !== 200) {
      if (tb) tb.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#e74c3c;padding:30px;">❌ Erreur HTTP : ${xhr.status}</td></tr>`;
      return;
    }
    try {
      const result = JSON.parse(xhr.responseText);
      if (!result.success) throw new Error(result.error || 'Erreur serveur');
      const data = Array.isArray(result.data) ? result.data : [];
      planningDataCache = data;
      updatePlanningStats(data);
      renderPlanningRows(data);
    } catch(e) {
      if (tb) tb.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#e74c3c;padding:30px;">❌ ${e.message}</td></tr>`;
    }
  };

  xhr.onerror = function() {
    const tb = document.getElementById('planningTableBody');
    if (tb) tb.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#e74c3c;padding:30px;">❌ Erreur de connexion</td></tr>`;
  };

  xhr.send();
}

function updatePlanningStats(data) {
  const el = document.getElementById('adminPlanningStats');
  if (!el) return;

  // ── Calculs ────────────────────────────────────────────────────────────
  const total      = data.length;
  const approuves  = data.filter(d => d.statut === 'approuve').length;
  const enAttente  = data.filter(d => d.statut === 'en_attente').length;
  const rejetes    = data.filter(d => d.statut === 'rejete').length;
  const quotidien  = data.filter(d => d.type_budget === 'quotidien').length;
  const hebdo      = data.filter(d => d.type_budget === 'hebdomadaire').length;
  const totalJours = data.reduce((s,d) => s + (d.type_duree==='semaines'?(parseInt(d.duree)||0)*7:(parseInt(d.duree)||0)), 0);
  const avgCal     = total ? Math.round(data.reduce((s,d)=>s+(parseFloat(d.calories)||0),0)/total) : 0;
  const byDate     = data.slice().sort((a,b) => new Date(a.date_demande||0)-new Date(b.date_demande||0));

  const uid = Date.now();
  const pct = (n,d) => d ? Math.round(n/d*100) : 0;

  // ── Helper KPI ─────────────────────────────────────────────────────────
  const kpi = (ico,val,lbl,color,p) =>
    `<div class="ars-kpi">
      <div class="ars-kpi-glow" style="background:${color}"></div>
      <span class="ars-kpi-ico">${ico}</span>
      <div class="ars-kpi-val" data-t="${val}">0</div>
      <div class="ars-kpi-lbl">${lbl}</div>
      <div class="ars-kpi-bar"><div class="ars-kpi-fill" data-p="${p}" style="background:${color}"></div></div>
    </div>`;

  // ── Helper bar ─────────────────────────────────────────────────────────
  const barMax = Math.max(quotidien, hebdo, 1);
  const bar = (lbl,val,color) => {
    const h = Math.max(Math.round(val/barMax*62), val>0?4:0);
    return `<div class="ars-bar-col">
      <div class="ars-bar-val">${val}</div>
      <div class="ars-bar-rect" style="background:${color};height:${h}px" title="${lbl}: ${val}"></div>
      <div class="ars-bar-lbl">${lbl}</div>
    </div>`;
  };

  // ── HTML ────────────────────────────────────────────────────────────────
  el.innerHTML = `
  <div class="ars-wrap">
    <div class="ars-kpi-row">
      ${kpi('📋', total,      'Total demandes',    '#7b6eea', 100)}
      ${kpi('✅', approuves,  'Approuvés',         '#2ecc71', pct(approuves,total))}
      ${kpi('⏳', enAttente,  'En attente',        '#f39c12', pct(enAttente,total))}
      ${kpi('📆', totalJours, 'Jours planifiés',   '#3b82f6', Math.min(totalJours,100))}
    </div>
    <div class="ars-charts-row">

      <!-- Donut -->
      <div class="ars-chart-card">
        <div class="ars-chart-title">Répartition statuts</div>
        <div class="ars-donut-wrap">
          <div style="position:relative;width:100px;height:100px;flex-shrink:0">
            <svg id="ars-donut-${uid}" width="100" height="100" viewBox="0 0 100 100"></svg>
            <div class="ars-donut-center" id="ars-dc-${uid}">
              <span class="ars-dc-val">${total}</span>
              <span class="ars-dc-lbl">total</span>
            </div>
          </div>
          <div class="ars-donut-legend">
            <div class="ars-leg" data-seg="0" onmouseenter="arsHoverSeg(this,${uid})" onmouseleave="arsUnhoverSeg(${uid})">
              <span class="ars-leg-dot" style="background:#2ecc71"></span>
              <span class="ars-leg-lbl">Approuvés</span>
              <span class="ars-leg-val">${approuves}</span>
            </div>
            <div class="ars-leg" data-seg="1" onmouseenter="arsHoverSeg(this,${uid})" onmouseleave="arsUnhoverSeg(${uid})">
              <span class="ars-leg-dot" style="background:#f39c12"></span>
              <span class="ars-leg-lbl">En attente</span>
              <span class="ars-leg-val">${enAttente}</span>
            </div>
            <div class="ars-leg" data-seg="2" onmouseenter="arsHoverSeg(this,${uid})" onmouseleave="arsUnhoverSeg(${uid})">
              <span class="ars-leg-dot" style="background:#e74c3c"></span>
              <span class="ars-leg-lbl">Rejetés</span>
              <span class="ars-leg-val">${rejetes}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bars -->
      <div class="ars-chart-card">
        <div class="ars-chart-title">Type de budget</div>
        <div class="ars-bars-wrap">
          ${bar('Quotidien',    quotidien, '#7b6eea')}
          ${bar('Hebdomadaire', hebdo,     '#3b82f6')}
        </div>
        <div class="ars-avg-line">Moy. calories : <strong>${avgCal} kcal</strong></div>
      </div>

      <!-- Sparkline -->
      <div class="ars-chart-card" style="display:flex;flex-direction:column">
        <div class="ars-chart-title">Évolution calories</div>
        <div style="position:relative;flex:1">
          <svg id="ars-spark-${uid}" class="ars-spark-svg" viewBox="0 0 300 75" preserveAspectRatio="none"></svg>
          <div class="ars-tooltip" id="ars-tt-${uid}" style="display:none"></div>
        </div>
        <div class="ars-spark-footer" id="ars-sf-${uid}"></div>
      </div>

    </div>
  </div>`;

  // ── Animations & interactions ───────────────────────────────────────────
  setTimeout(() => {

    // 1. Compteurs animés
    el.querySelectorAll('.ars-kpi-val[data-t]').forEach(v => {
      const target = +v.dataset.t;
      let t0 = null;
      const raf = ts => {
        if (!t0) t0 = ts;
        const p = Math.min((ts-t0)/850,1), e = 1-Math.pow(2,-10*p);
        v.textContent = Math.round(target*e);
        if (p<1) requestAnimationFrame(raf);
      };
      requestAnimationFrame(raf);
    });

    // 2. Barres KPI fill
    el.querySelectorAll('.ars-kpi-fill[data-p]').forEach(b => {
      setTimeout(() => b.style.width = b.dataset.p+'%', 120);
    });

    // 3. Bar chart scaleY
    el.querySelectorAll('.ars-bar-rect').forEach((b,i) => {
      setTimeout(() => b.style.transform = 'scaleY(1)', 100+i*80);
    });

    // 4. Donut SVG interactif
    const donutSvg = document.getElementById(`ars-donut-${uid}`);
    const dcEl     = document.getElementById(`ars-dc-${uid}`);
    if (donutSvg && total > 0) {
      const cx=50, cy=50, r=34, sw=10, circ=2*Math.PI*r;
      const segs = [
        {val:approuves, color:'#2ecc71', lbl:'approuvés'},
        {val:enAttente, color:'#f39c12', lbl:'en attente'},
        {val:rejetes,   color:'#e74c3c', lbl:'rejetés'}
      ];
      donutSvg.innerHTML = `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="rgba(91,62,150,.1)" stroke-width="${sw}"/>`;
      const circles = [];
      let offset = 0;
      segs.forEach((seg, i) => {
        const len = (seg.val/total)*circ;
        if (len <= 0) { circles.push(null); return; }
        const c = document.createElementNS('http://www.w3.org/2000/svg','circle');
        c.setAttribute('cx',cx); c.setAttribute('cy',cy); c.setAttribute('r',r);
        c.setAttribute('fill','none'); c.setAttribute('stroke',seg.color);
        c.setAttribute('stroke-width',sw); c.setAttribute('stroke-linecap','round');
        c.setAttribute('stroke-dasharray',`${len} ${circ-len}`);
        c.style.transform = `rotate(-90deg)`; c.style.transformOrigin = `${cx}px ${cy}px`;
        c.style.strokeDashoffset = circ - offset;
        c.style.transition = 'stroke-dashoffset 0s, stroke-width .2s, opacity .2s';
        c.style.cursor = 'pointer';
        donutSvg.appendChild(c);
        const myOffset = offset;
        circles.push({el:c, i, seg, len});
        offset += len;
        setTimeout(() => {
          c.style.transition = `stroke-dashoffset .9s cubic-bezier(.4,0,.2,1), stroke-width .2s, opacity .2s`;
          c.style.strokeDashoffset = circ - myOffset - len;
        }, 60+i*120);
        // Hover arc
        c.addEventListener('mouseenter', () => {
          circles.forEach(x => { if(x && x.el!==c){ x.el.style.opacity='.3'; x.el.setAttribute('stroke-width',sw); }});
          c.setAttribute('stroke-width', sw+3);
          if (dcEl) { dcEl.querySelector('.ars-dc-val').textContent=seg.val; dcEl.querySelector('.ars-dc-lbl').textContent=seg.lbl; }
        });
        c.addEventListener('mouseleave', () => {
          circles.forEach(x => { if(x){ x.el.style.opacity='1'; x.el.setAttribute('stroke-width',sw); }});
          if (dcEl) { dcEl.querySelector('.ars-dc-val').textContent=total; dcEl.querySelector('.ars-dc-lbl').textContent='total'; }
        });
      });
      window[`_arsCircles${uid}`] = circles;
      window[`_arsDc${uid}`]      = dcEl;
      window[`_arsTotal${uid}`]   = total;
    }

    // 5. Sparkline bezier interactive
    const sparkSvg = document.getElementById(`ars-spark-${uid}`);
    const tooltip  = document.getElementById(`ars-tt-${uid}`);
    const footer   = document.getElementById(`ars-sf-${uid}`);
    if (sparkSvg && byDate.length > 0) {
      const W=300, H=75, px=10, py=8;
      const vals  = byDate.map(d => parseFloat(d.calories)||0);
      const dates = byDate.map(d => d.date_demande ? new Date(d.date_demande).toLocaleDateString('fr-FR') : '?');
      const minV  = Math.min(...vals), maxV = Math.max(...vals), rng = maxV-minV||1;
      const pts   = vals.map((v,i) => [
        vals.length===1 ? W/2 : px+(i/(vals.length-1))*(W-px*2),
        (H-py)-((v-minV)/rng)*(H-py*2)
      ]);
      // Bezier
      const bezier = pts => {
        if (pts.length===1) return `M${pts[0][0]} ${pts[0][1]}`;
        let d = `M${pts[0][0].toFixed(1)} ${pts[0][1].toFixed(1)}`;
        for (let i=0;i<pts.length-1;i++) {
          const cpx1=pts[i][0]+(pts[i+1][0]-pts[i][0])*.45, cpy1=pts[i][1];
          const cpx2=pts[i+1][0]-(pts[i+1][0]-pts[i][0])*.45, cpy2=pts[i+1][1];
          d+=` C${cpx1.toFixed(1)} ${cpy1.toFixed(1)},${cpx2.toFixed(1)} ${cpy2.toFixed(1)},${pts[i+1][0].toFixed(1)} ${pts[i+1][1].toFixed(1)}`;
        }
        return d;
      };
      const pathD = bezier(pts);
      const fillD = `${pathD} L${pts[pts.length-1][0]} ${H-py} L${pts[0][0]} ${H-py} Z`;
      sparkSvg.innerHTML = `
        <defs>
          <linearGradient id="arsg${uid}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#7b6eea" stop-opacity=".4"/>
            <stop offset="100%" stop-color="#7b6eea" stop-opacity="0"/>
          </linearGradient>
          <clipPath id="arscp${uid}"><rect x="0" y="0" width="0" height="${H}"/></clipPath>
        </defs>
        <path d="${fillD}" fill="url(#arsg${uid})" clip-path="url(#arscp${uid})" opacity=".7"/>
        <path id="arsp${uid}" d="${pathD}" fill="none" stroke="#7b6eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <line id="arscross${uid}" x1="0" y1="0" x2="0" y2="${H}" stroke="rgba(123,110,234,.5)" stroke-width="1" stroke-dasharray="3,3" style="display:none"/>`;
      // Animate
      const pathEl = document.getElementById(`arsp${uid}`);
      const clip   = sparkSvg.querySelector('clipPath rect');
      if (pathEl && clip) {
        const len = pathEl.getTotalLength ? pathEl.getTotalLength() : 400;
        pathEl.style.strokeDasharray = pathEl.style.strokeDashoffset = len;
        pathEl.style.transition = 'stroke-dashoffset 1.3s cubic-bezier(.4,0,.2,1)';
        setTimeout(() => {
          pathEl.style.strokeDashoffset = 0;
          clip.style.transition = 'width 1.3s cubic-bezier(.4,0,.2,1)';
          clip.setAttribute('width', W);
        }, 80);
      }
      // Dots
      const dotEls = pts.map((p,i) => {
        const c = document.createElementNS('http://www.w3.org/2000/svg','circle');
        c.setAttribute('cx',p[0]); c.setAttribute('cy',p[1]);
        c.setAttribute('r','3.5'); c.setAttribute('fill','#7b6eea');
        c.setAttribute('stroke','rgba(255,255,255,.15)'); c.setAttribute('stroke-width','1.5');
        c.classList.add('ars-spark-dot'); c.style.opacity='0';
        sparkSvg.appendChild(c);
        setTimeout(() => c.style.opacity='1', 1100+i*50);
        return c;
      });
      // Crosshair hover
      const cross = document.getElementById(`arscross${uid}`);
      sparkSvg.addEventListener('mousemove', ev => {
        const rect = sparkSvg.getBoundingClientRect();
        const mx   = (ev.clientX-rect.left)/rect.width*W;
        let minD=Infinity, ci=0;
        pts.forEach(([px],i) => { const d=Math.abs(px-mx); if(d<minD){minD=d;ci=i;} });
        const [cpx,cpy] = pts[ci];
        if (cross) { cross.setAttribute('x1',cpx); cross.setAttribute('x2',cpx); cross.style.display=''; }
        dotEls.forEach((d,i) => { d.setAttribute('r',i===ci?'5.5':'3.5'); d.setAttribute('fill',i===ci?'#a78bfa':'#7b6eea'); });
        if (tooltip) {
          tooltip.style.display='';
          tooltip.innerHTML = `<strong>${vals[ci]} kcal</strong><br>${dates[ci]}`;
          tooltip.style.left = (cpx/W*100)+'%';
          tooltip.style.top  = (cpy/H*rect.height-46)+'px';
        }
      });
      sparkSvg.addEventListener('mouseleave', () => {
        if (cross) cross.style.display='none';
        dotEls.forEach(d => { d.setAttribute('r','3.5'); d.setAttribute('fill','#7b6eea'); });
        if (tooltip) tooltip.style.display='none';
      });
      // Footer
      if (footer) footer.innerHTML = `<span>Min : ${minV} kcal</span><span>Moy : ${Math.round(vals.reduce((a,b)=>a+b,0)/vals.length)} kcal</span><span>Max : ${maxV} kcal</span>`;
    }

  }, 60);
}

// Hover donut via légende (exposé global car appelé depuis HTML inline)
window.arsHoverSeg = function(legEl, uid) {
  const i       = parseInt(legEl.dataset.seg,10);
  const circles = window[`_arsCircles${uid}`] || [];
  const dcEl    = window[`_arsDc${uid}`];
  const lbls    = ['approuvés','en attente','rejetés'];
  const valEl   = legEl.querySelector('.ars-leg-val');
  circles.forEach(x => { if(x){ x.el.style.opacity=x.i===i?'1':'.3'; x.el.setAttribute('stroke-width',x.i===i?'13':'10'); }});
  if (dcEl && valEl) { dcEl.querySelector('.ars-dc-val').textContent=valEl.textContent; dcEl.querySelector('.ars-dc-lbl').textContent=lbls[i]||''; }
};
window.arsUnhoverSeg = function(uid) {
  const circles = window[`_arsCircles${uid}`] || [];
  const dcEl    = window[`_arsDc${uid}`];
  const total   = window[`_arsTotal${uid}`] || 0;
  circles.forEach(x => { if(x){ x.el.style.opacity='1'; x.el.setAttribute('stroke-width','10'); }});
  if (dcEl) { dcEl.querySelector('.ars-dc-val').textContent=total; dcEl.querySelector('.ars-dc-lbl').textContent='total'; }
};

function renderPlanningRows(data) {
  const tb = document.getElementById('planningTableBody');
  if (!tb) return;

  // ── Recherche ciblée sur le champ sélectionné ─────────────────────────
  const searchInput    = document.getElementById('planningSearchInput');
  const searchFieldEl  = document.getElementById('planningSearchField');
  const searchVal      = searchInput   ? searchInput.value.trim()  : '';
  const searchField    = searchFieldEl ? searchFieldEl.value        : 'id';

  let filtered = data.filter(d => {
    // filtre statut
    if (planningFiltreML !== 'all' && (d.statut || 'en_attente') !== planningFiltreML) return false;
    // pas de texte → tout passe
    if (!searchVal) return true;

    let fieldVal = '';
    switch (searchField) {
      case 'id':       fieldVal = String(d.id       ?? ''); break;
      case 'budget':   fieldVal = String(d.budget   ?? '').replace(',', '.'); break;
      case 'calories': fieldVal = String(d.calories ?? ''); break;
      default:         fieldVal = '';
    }
    return fieldVal.toLowerCase().includes(searchVal.toLowerCase());
  });

  // ── Synchroniser la colonne de tri depuis le select ───────────────────
  const sortFieldEl = document.getElementById('planningSortField');
  if (sortFieldEl && sortFieldEl.value) planningSort.col = sortFieldEl.value;

  // ── Tri ───────────────────────────────────────────────────────────────
  const col = planningSort.col;
  const dir = planningSort.dir;

  filtered = filtered.slice().sort((a, b) => {
    let va, vb;
    switch (col) {
      case 'id':
        va = parseInt(a.id, 10)  || 0;
        vb = parseInt(b.id, 10)  || 0;
        break;
      case 'calories':
        va = parseFloat(a.calories) || 0;
        vb = parseFloat(b.calories) || 0;
        break;
      case 'budget':
        va = parseFloat(a.budget) || 0;
        vb = parseFloat(b.budget) || 0;
        break;
      case 'duree': {
        const toJ = x => x.type_duree === 'semaines'
          ? (parseInt(x.duree, 10) || 0) * 7
          : (parseInt(x.duree, 10) || 0);
        va = toJ(a); vb = toJ(b);
        break;
      }
      case 'statut': {
        const ord = { en_attente: 0, approuve: 1, rejete: 2 };
        va = ord[a.statut] ?? 9;
        vb = ord[b.statut] ?? 9;
        break;
      }
      case 'date':
        va = a.date_demande ? new Date(a.date_demande).getTime() : 0;
        vb = b.date_demande ? new Date(b.date_demande).getTime() : 0;
        break;
      default:
        va = 0; vb = 0;
    }
    if (va === vb) return 0;
    return dir === 'asc' ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
  });

  if (!filtered.length) {
    tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">Aucune demande trouvée.</td></tr>';
    return;
  }

  const badges = { en_attente:'⏳ En attente', approuve:'✅ Approuvé', rejete:'❌ Rejeté' };
  const bcls   = { en_attente:'badge-en_attente', approuve:'badge-approuve', rejete:'badge-rejete' };

  tb.innerHTML = filtered.map(d => {
    const statut   = d.statut || 'en_attente';
    const date     = d.date_demande ? new Date(d.date_demande).toLocaleDateString('fr-FR') : '—';
    const nbLignes = parseInt(d.nb_lignes_planning) || 0;
    const planning = nbLignes > 0
      ? `<span style="color:#2ecc71;font-weight:700">${nbLignes} lignes</span>`
      : '<span style="color:var(--muted)">—</span>';

    // Boutons selon statut — PAS de bouton delete dans le backend
    let actionBtns = `<button onclick="planningVoir(${d.id})" class="action-btn action-btn-view" style="margin-right:4px">👁️ Voir</button>`;
    if (statut === 'en_attente') {
      actionBtns += `
        <button class="btn-statut-sm btn-approuver" onclick="planningChangerStatut(${d.id},'approuve',this)" title="Approuver">✅</button>
        <button class="btn-statut-sm btn-rejeter"   onclick="planningChangerStatut(${d.id},'rejete',this)"  title="Rejeter">❌</button>`;
    } else if (statut === 'approuve') {
      actionBtns += `
        <button class="btn-statut-sm btn-regen"   onclick="planningRegen(${d.id},this)"                        title="Régénérer">🔄</button>
        <button class="btn-statut-sm btn-rejeter" onclick="planningChangerStatut(${d.id},'rejete',this)"        title="Rejeter">❌</button>`;
    } else if (statut === 'rejete') {
      actionBtns += `
        <button class="btn-statut-sm btn-remettre" onclick="planningChangerStatut(${d.id},'en_attente',this)" title="Remettre en attente">↩️</button>`;
    }

    return `<tr class="animate-in" data-statut="${statut}">
      <td><strong>#${d.id}</strong></td>
      <td>👤 ${d.id_utilisateur}</td>
      <td>${parseInt(d.calories).toLocaleString('fr')} kcal</td>
      <td>${parseFloat(d.budget).toFixed(2)} € <span class="badge badge-user">${d.type_budget}</span></td>
      <td>${d.duree} <span class="badge badge-user">${d.type_duree}</span></td>
      <td><span class="badge-statut ${bcls[statut]||''}">${badges[statut]||statut}</span></td>
      <td>${planning}</td>
      <td style="color:var(--muted);font-size:.8rem">${date}</td>
      <td><div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap">${actionBtns}</div></td>
    </tr>`;
  }).join('');
}

// ── Actions ───────────────────────────────────────────────────────────────

// ══════════════════════════════════════════════════════════════
// SPA — Vue liste ↔ Vue détail (sans nouvelle page, sans drawer)
// ══════════════════════════════════════════════════════════════

function planningVoir(id) {
  // Afficher la vue détail, cacher la liste
  document.getElementById('planningVueListe').style.display  = 'none';
  document.getElementById('planningVueDetail').style.display = 'block';
  document.getElementById('detailBreadcrumb').textContent    = `Demande #${id}`;
  document.getElementById('detailTopActions').innerHTML      = '';

  const contenu = document.getElementById('detailContenu');
  contenu.innerHTML = `<div class="detail-loading"><div class="detail-spinner"></div><p>Chargement...</p></div>`;

  fetch(`planning/showDemandeplanning.php?id=${id}&json=1`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
  .then(res => {
    if (!res.success) throw new Error(res.error || 'Erreur serveur');
    _renderDetail(res, id);
  })
  .catch(err => {
    contenu.innerHTML = `<div style="text-align:center;padding:60px;color:#e74c3c">
      ❌ ${err.message}<br>
      <button onclick="planningVoir(${id})" style="margin-top:16px;padding:8px 18px;
        background:#5B3E96;color:#fff;border:none;border-radius:8px;cursor:pointer">Réessayer</button>
    </div>`;
  });
}

window.planningRetourListe = function() {
  document.getElementById('planningVueDetail').style.display = 'none';
  document.getElementById('planningVueListe').style.display  = 'block';
};

function _renderDetail(res, id) {
  const d  = res.demande;
  const ss = res.sportsommeil;
  const pl = res.planning;
  const st = res.stats;

  const badges = { en_attente:'⏳ En attente', approuve:'✅ Approuvé', rejete:'❌ Rejeté' };
  const bcls   = { en_attente:'badge-en_attente', approuve:'badge-approuve', rejete:'badge-rejete' };
  const statut = d.statut || 'en_attente';
  const date   = d.date_demande ? new Date(d.date_demande).toLocaleDateString('fr-FR') : '—';

  document.getElementById('detailBreadcrumb').textContent = `Demande #${d.id}`;

  // Boutons d'action dans la topbar
  let actBtns = '';
  if (statut === 'en_attente') {
    actBtns = `<button class="btn-detail btn-detail-ok"  onclick="detailChangerStatut(${d.id},'approuve')">✅ Approuver</button>
               <button class="btn-detail btn-detail-err" onclick="detailChangerStatut(${d.id},'rejete')">❌ Rejeter</button>`;
  } else if (statut === 'approuve') {
    actBtns = `<button class="btn-detail btn-detail-blue" onclick="detailRegen(${d.id})">🔄 Régénérer</button>
               <button class="btn-detail btn-detail-err"  onclick="detailChangerStatut(${d.id},'rejete')">❌ Rejeter</button>`;
  } else if (statut === 'rejete') {
    actBtns = `<button class="btn-detail btn-detail-warn" onclick="detailChangerStatut(${d.id},'en_attente')">↩️ Remettre en attente</button>`;
  }
  document.getElementById('detailTopActions').innerHTML = actBtns;

  // ── Bloc 1 : Infos demande ──
  const bloc1 = `<div class="detail-bloc">
    <div class="detail-bloc-hd">📋 Informations de la demande</div>
    <div class="detail-bloc-bd">
      <div class="info-grid">
        <div class="info-item"><span class="info-lbl">👤 Utilisateur</span><span class="info-val">#${d.id_utilisateur}</span></div>
        <div class="info-item"><span class="info-lbl">📅 Date demande</span><span class="info-val">${date}</span></div>
        <div class="info-item"><span class="info-lbl">📊 Statut</span><span class="info-val"><span class="badge-statut ${bcls[statut]}">${badges[statut]||statut}</span></span></div>
        <div class="info-item"><span class="info-lbl">🔥 Calories</span><span class="info-val">${parseInt(d.calories).toLocaleString('fr')} kcal/jour</span></div>
        <div class="info-item"><span class="info-lbl">💰 Budget</span><span class="info-val">${parseFloat(d.budget).toFixed(2)} € <small style="color:var(--muted);font-weight:400">${d.type_budget}</small></span></div>
        <div class="info-item"><span class="info-lbl">⏱️ Durée</span><span class="info-val">${d.duree} ${d.type_duree}</span></div>
      </div>
    </div>
  </div>`;

  // ── Bloc 2 : Sport & Sommeil ──
  let bloc2 = '';
  if (ss) {
    const mpj = Math.round((ss.duree_sport_hebdo||0)/7);
    bloc2 = `<div class="detail-bloc">
      <div class="detail-bloc-hd">🏃 Sport & Sommeil</div>
      <div class="detail-bloc-bd">
        <div class="info-grid">
          <div class="info-item"><span class="info-lbl">🏋️ Activité sportive</span><span class="info-val">${ss.activite_sportive||'—'}</span></div>
          <div class="info-item"><span class="info-lbl">⏱️ Durée / semaine</span><span class="info-val">${ss.duree_sport_hebdo||0} min <small style="color:var(--muted)">(≈${mpj} min/j)</small></span></div>
          <div class="info-item"><span class="info-lbl">😴 Qualité sommeil</span><span class="info-val">${ss.qualite_sommeil||'—'}</span></div>
          <div class="info-item"><span class="info-lbl">🌙 Heure de coucher</span><span class="info-val">${(ss.heure_coucher||'—').substring(0,5)}</span></div>
          <div class="info-item"><span class="info-lbl">☀️ Heure de réveil</span><span class="info-val">${(ss.heure_reveil||'—').substring(0,5)}</span></div>
        </div>
      </div>
    </div>`;
  } else {
    bloc2 = `<div class="detail-bloc"><div class="detail-bloc-bd">
      <div style="padding:12px 16px;background:rgba(243,156,18,.08);border:1px solid rgba(243,156,18,.25);border-radius:10px;color:#f39c12;font-size:.87rem;">
        ⚠️ L'étape Sport & Sommeil n'a pas encore été remplie pour cette demande.
      </div>
    </div></div>`;
  }

  // ── Bloc 3 : Résumé stats ──
  const hasPlanning = pl && pl.length > 0;
  let bloc3 = '';
  if (hasPlanning) {
    bloc3 = `<div class="detail-bloc">
      <div class="detail-bloc-hd">📊 Résumé du planning</div>
      <div class="detail-pills">
        <div class="detail-pill">📆 <strong>${st.nb_jours}</strong> <span>jour(s)</span></div>
        <div class="detail-pill">🍽️ <strong>${st.nb_repas}</strong> <span>repas</span></div>
        <div class="detail-pill">🏃 <strong>${st.nb_sport}</strong> <span>séance(s) sport</span></div>
        <div class="detail-pill">🌙 <strong>${st.nb_sommeil}</strong> <span>nuit(s)</span></div>
        <div class="detail-pill">📋 <strong>${res.nb_lignes}</strong> <span>lignes total</span></div>
      </div>
    </div>`;
  }

  // ── Bloc 4 : Tableau planning calendrier PAR SEMAINE ──
  let bloc4 = '';
  if (hasPlanning) {
    window._planningDetailData = pl;
    window._planningDetailSemaine = 0;
    const nbSemaines = Math.ceil(pl.length / 7);

    window._renderSemaine = function(semIdx) {
      const debut = semIdx * 7;
      const slice = window._planningDetailData.slice(debut, debut + 7);
      const headers = slice.map(j =>
        `<th class="th-jour"><span style="font-weight:700;display:block">${j.jourFr}</span><span style="font-size:.68rem;color:var(--muted)">${j.dateAff}</span></th>`
      ).join('');
      const mkRepas = (ico, lbl, idx, extra) => {
        extra = extra || '';
        return `<tr class="cal-repas${extra}">
          <td class="td-act"><span style="display:block;font-size:.95rem">${ico}</span>${lbl}</td>
          ${slice.map(j=>{const v=(j.repas||[])[idx];return`<td>${v||'<span style="color:var(--muted)">—</span>'}</td>`;}).join('')}
        </tr>`;
      };
      const mkRow = (cls,ico,lbl,type) => `<tr class="${cls}">
        <td class="td-act"><span style="display:block;font-size:.95rem">${ico}</span>${lbl}</td>
        ${slice.map(j=>{const v=(j[type]||[])[0];return`<td>${v||'<span style="color:var(--muted)">—</span>'}</td>`;}).join('')}
      </tr>`;
      const tbl = document.getElementById('planningCalBody');
      if (tbl) tbl.innerHTML =
        `<thead><tr><th class="th-act">Activité</th>${headers}</tr></thead>
         <tbody>
           ${mkRepas('🍳','Petit-déj',0,' cal-repas-first')}
           ${mkRepas('🍽️','Déjeuner',1)}
           ${mkRepas('🌮','Dîner',2)}
           ${mkRow('cal-sport','🏃','Sport','sport')}
           ${mkRow('cal-sommeil','🌙','Sommeil','sommeil')}
         </tbody>`;
      const lbl = document.getElementById('planningWeekLabel');
      if (lbl) lbl.textContent = `Semaine ${semIdx + 1} / ${nbSemaines}`;
      const btnPrev = document.getElementById('planningWeekPrev');
      const btnNext = document.getElementById('planningWeekNext');
      if (btnPrev) btnPrev.disabled = semIdx === 0;
      if (btnNext) btnNext.disabled = semIdx >= nbSemaines - 1;
    };

    bloc4 = `<div class="detail-bloc">
      <div class="detail-bloc-hd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span>📅 Planning complet — ${st.nb_jours} jour(s)</span>
        <div style="display:flex;align-items:center;gap:10px">
          <button id="planningWeekPrev"
            onclick="window._planningDetailSemaine=Math.max(0,window._planningDetailSemaine-1);window._renderSemaine(window._planningDetailSemaine)"
            style="padding:5px 14px;border-radius:8px;border:1.5px solid rgba(91,62,150,.35);background:rgba(91,62,150,.1);color:var(--text);font-size:.85rem;font-weight:700;cursor:pointer;transition:all .2s"
            disabled>← Préc.</button>
          <span id="planningWeekLabel" style="font-size:.8rem;font-weight:700;color:var(--violet,#a78bfa);white-space:nowrap">Semaine 1 / ${nbSemaines}</span>
          <button id="planningWeekNext"
            onclick="window._planningDetailSemaine=Math.min(${nbSemaines-1},window._planningDetailSemaine+1);window._renderSemaine(window._planningDetailSemaine)"
            style="padding:5px 14px;border-radius:8px;border:1.5px solid rgba(91,62,150,.35);background:rgba(91,62,150,.1);color:var(--text);font-size:.85rem;font-weight:700;cursor:pointer;transition:all .2s"
            ${nbSemaines <= 1 ? 'disabled' : ''}>Suiv. →</button>
        </div>
      </div>
      <div class="planning-table-scroll">
        <table class="planning-cal" id="planningCalBody"></table>
      </div>
    </div>`;

    setTimeout(() => window._renderSemaine(0), 0);
  } else {
    bloc4 = `<div style="text-align:center;padding:40px 20px;border:2px dashed rgba(91,62,150,.2);border-radius:14px;color:var(--muted);margin-top:8px">
      <div style="font-size:2.2rem;margin-bottom:12px;opacity:.5">📅</div>
      <h4 style="margin:0 0 8px;color:var(--text)">Aucun planning généré</h4>
      <p style="font-size:.85rem;margin:0">${ss ? 'Approuvez la demande pour générer le planning.' : 'Complétez d\'abord l\'étape Sport & Sommeil.'}</p>
    </div>`;
  }

  document.getElementById('detailContenu').innerHTML = bloc1 + bloc2 + bloc3 + bloc4;
}

window.detailChangerStatut = function(id, val) {
  const labels = { approuve:'approuver', rejete:'rejeter', en_attente:'remettre en attente' };
  if (!confirm(`Confirmer : ${labels[val]||val} la demande #${id} ?`)) return;
  fetch(`planning/listDemandeplanning.php?json=1&action=statut&id=${id}&val=${val}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  }).then(r=>r.json()).then(res=>{
    if (!res.success) throw new Error(res.error||'Erreur');
    planningToast(res.message||'Statut mis à jour','ok');
    const d = planningDataCache.find(x=>x.id==id);
    if (d) { d.statut=val; if(res.nb_lignes) d.nb_lignes_planning=res.nb_lignes; }
    updatePlanningStats(planningDataCache);
    planningVoir(id); // rafraîchit la vue détail
  }).catch(err=>planningToast('Erreur : '+err.message,'err'));
};

window.detailRegen = function(id) {
  if (!confirm(`Régénérer le planning #${id} ?`)) return;
  fetch(`planning/listDemandeplanning.php?json=1&action=generer&id=${id}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  }).then(r=>r.json()).then(res=>{
    if (!res.success) throw new Error(res.error||'Erreur');
    planningToast(res.message,'ok');
    const d = planningDataCache.find(x=>x.id==id);
    if (d) d.nb_lignes_planning=res.nb_lignes;
    planningVoir(id);
  }).catch(err=>planningToast('Erreur : '+err.message,'err'));
};


function planningChangerStatut(id, val, btn) {
  const labels = { approuve:'approuver', rejete:'rejeter', en_attente:'remettre en attente' };
  if (!confirm(`Confirmer : ${labels[val]||val} la demande #${id} ?`)) return;
  const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳';

  fetch(`planning/listDemandeplanning.php?json=1&action=statut&id=${id}&val=${val}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    if (!res.success) throw new Error(res.error || res.message || 'Erreur');
    planningToast(res.message || 'Statut mis à jour', 'ok');
    // Mise à jour locale du cache
    const d = planningDataCache.find(x => x.id == id);
    if (d) { d.statut = val; if (res.nb_lignes) d.nb_lignes_planning = res.nb_lignes; }
    updatePlanningStats(planningDataCache);
    renderPlanningRows(planningDataCache);
  })
  .catch(err => {
    planningToast('Erreur : ' + err.message, 'err');
    btn.disabled = false; btn.textContent = orig;
  });
}

function planningRegen(id, btn) {
  if (!confirm(`Régénérer le planning #${id} ?`)) return;
  const orig = btn.textContent; btn.disabled = true; btn.textContent = '⏳';

  fetch(`planning/listDemandeplanning.php?json=1&action=generer&id=${id}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    if (!res.success) throw new Error(res.error || 'Erreur');
    planningToast(res.message, 'ok');
    const d = planningDataCache.find(x => x.id == id);
    if (d) d.nb_lignes_planning = res.nb_lignes;
    renderPlanningRows(planningDataCache);
  })
  .catch(err => {
    planningToast('Erreur : ' + err.message, 'err');
    btn.disabled = false; btn.textContent = orig;
  });
}

// ── Filtres / recherche ───────────────────────────────────────────────────

function filterPlanningTable() {
  renderPlanningRows(planningDataCache);
}

function setPlanningFilter(f, el) {
  planningFiltreML = f;
  document.querySelectorAll('.planning-chip').forEach(c => c.classList.remove('active'));
  if (el) el.classList.add('active');
  renderPlanningRows(planningDataCache);
}

function applySortPlanning() {
  // Le select a déjà sa nouvelle valeur — renderPlanningRows() la lira
  renderPlanningRows(planningDataCache);
}

function toggleSortDir() {
  planningSort.dir = planningSort.dir === 'asc' ? 'desc' : 'asc';
  const btn = document.getElementById('planningSortDirBtn');
  if (btn) {
    btn.textContent  = planningSort.dir === 'asc' ? '↑ ASC' : '↓ DESC';
    btn.style.color  = planningSort.dir === 'asc' ? '#2ecc71' : 'var(--violet, #a78bfa)';
  }
  renderPlanningRows(planningDataCache);
}

// ── Toast ─────────────────────────────────────────────────────────────────

function planningToast(msg, type) {
  let t = document.getElementById('planningToastML');
  if (!t) {
    t = document.createElement('div');
    t.id = 'planningToastML';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;font-weight:600;font-size:.88rem;z-index:9999;transition:opacity .3s';
    document.body.appendChild(t);
  }
  t.textContent    = msg;
  t.style.display  = 'block';
  t.style.opacity  = '1';
  t.style.background = type === 'ok' ? '#1a4731' : '#4a1515';
  t.style.border     = type === 'ok' ? '1px solid #2ecc71' : '1px solid #e74c3c';
  t.style.color      = type === 'ok' ? '#2ecc71' : '#e74c3c';
  clearTimeout(t._t);
  t._t = setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.style.display='none',350); }, 3200);
}

// ── Exposition globale ────────────────────────────────────────────────────
window.loadPlanningData      = loadPlanningData;
window.filterPlanningTable   = filterPlanningTable;
window.setPlanningFilter     = setPlanningFilter;
window.applySortPlanning     = applySortPlanning;
window.toggleSortDir         = toggleSortDir;
window.planningVoir          = planningVoir;
window.planningChangerStatut = planningChangerStatut;
window.planningRegen         = planningRegen;
window.planningRetourListe   = planningRetourListe;
window.detailChangerStatut   = detailChangerStatut;
window.detailRegen           = detailRegen;

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