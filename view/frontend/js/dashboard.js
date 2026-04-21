/**
 * Dashboard User JavaScript
 * Gestion du dashboard utilisateur
 */

console.log('🌿 GaiaLumen Dashboard chargé');

// Vérifier l'authentification
function checkAuth() {
  const token = localStorage.getItem('gaialumen-token');
  if (!token) {
    // Rediriger vers la page de connexion
    console.log('❌ Pas de token - Redirection vers login');
    window.location.href = 'login.html';
    return false;
  }
  return true;
}

// Récupérer l'utilisateur connecté
function getCurrentUser() {
  const userStr = localStorage.getItem('gaialumen-user');
  return userStr ? JSON.parse(userStr) : null;
}

// Déconnexion
function logout() {
  localStorage.removeItem('gaialumen-token');
  localStorage.removeItem('gaialumen-user');
  window.location.href = 'login.html';
}

// Gestion du thème
function initTheme() {
  const btn = document.getElementById('theme-toggle');
  const html = document.documentElement;
  const saved = localStorage.getItem('gaialumen-theme') || 'dark';
  
  const updateBtn = (theme) => {
    if (!btn) return;
    // Plus intuitif : l'icône représente le thème actuel
    const icon = theme === 'dark' ? '🌙' : '☀️';
    const text = theme === 'dark' ? 'Sombre' : 'Clair';
    btn.innerHTML = `
      <span class="menu-item-icon">${icon}</span>
      <span class="menu-item-text">${text}</span>
    `;
  };

  html.setAttribute('data-theme', saved);
  updateBtn(saved);
  
  if (btn) {
    btn.addEventListener('click', () => {
      const n = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', n);
      localStorage.setItem('gaialumen-theme', n);
      updateBtn(n);
    });
  }
}

// Gestion de la navbar au scroll
function initNavbar() {
  const nb = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    if (nb) nb.classList.toggle('scrolled', window.scrollY > 40);
  });
}

// Gestion du menu mobile
function initMobileMenu() {
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.querySelector('.sidebar-menu');
  const mobilePanel = document.getElementById('mobileMenuPanel');
  const mobileOverlay = document.getElementById('mobileMenuOverlay');
  const mobileClose = document.getElementById('mobileMenuClose');
  
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      mobilePanel?.classList.toggle('active');
      mobileOverlay?.classList.toggle('active');
    });
  }
  
  if (mobileOverlay) {
    mobileOverlay.addEventListener('click', () => {
      menuToggle?.classList.remove('active');
      mobilePanel?.classList.remove('active');
      mobileOverlay?.classList.remove('active');
    });
  }
  
  if (mobileClose) {
    mobileClose.addEventListener('click', () => {
      menuToggle?.classList.remove('active');
      mobilePanel?.classList.remove('active');
      mobileOverlay?.classList.remove('active');
    });
  }
  
  document.querySelectorAll('.mobile-menu-item[data-module]').forEach(item => {
    item.addEventListener('click', () => {
      menuToggle?.classList.remove('active');
      mobilePanel?.classList.remove('active');
      mobileOverlay?.classList.remove('active');
    });
  });
}

// Gestion du toggle sidebar
function initSidebarToggle() {
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.sidebar-menu');
  const mainContent = document.querySelector('.main-content');
  
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      sidebarToggle.classList.toggle('collapsed');
      sidebar?.classList.toggle('mobile-hidden');
      mainContent?.classList.toggle('full-width');
    });
  }
}

// Navigation entre modules (DÉSACTIVÉ - Géré par module-loader.js)
function initModuleNavigation() {
  console.log('ℹ️ Navigation gérée par module-loader.js');
}

// Gestion de l'assistant AI
function initAIAssistant() {
  const btn = document.getElementById('ai-btn');
  const panel = document.getElementById('ai-panel');
  const close = document.querySelector('.ai-close');
  const send = document.querySelector('.ai-send');
  const input = document.querySelector('.ai-input');
  const body = document.querySelector('.ai-body');
  
  if (btn) btn.addEventListener('click', () => panel?.classList.toggle('active'));
  if (close) close.addEventListener('click', () => panel?.classList.remove('active'));
  
  function addMessage(text, isUser) {
    if (!body) return;
    const msg = document.createElement('div');
    msg.className = `ai-message ${isUser ? 'user' : 'bot'}`;
    msg.textContent = text;
    body.appendChild(msg);
    body.scrollTop = body.scrollHeight;
  }
  
  function handleSend() {
    const text = input?.value.trim();
    if (!text) return;
    addMessage(text, true);
    if (input) input.value = '';
    
    setTimeout(() => {
      let response = '';
      const lowerText = text.toLowerCase();
      
      if (lowerText.includes('planning') || lowerText.includes('repas')) {
        response = '📅 Le module Planning vous permet d\'organiser vos repas de la semaine. Cliquez sur "GS Planning" dans le menu pour commencer!';
      } else if (lowerText.includes('santé') || lowerText.includes('health')) {
        response = '❤️ Le module Santé vous aide à suivre vos indicateurs corporels, glycémie et hydratation. Consultez "GS Santé" pour plus de détails.';
      } else if (lowerText.includes('défi') || lowerText.includes('challenge')) {
        response = '🏆 Relevez des défis écologiques dans le module "GS Défis" et gagnez des badges en réduisant votre empreinte carbone!';
      } else if (lowerText.includes('aide') || lowerText.includes('help')) {
        response = 'Je peux vous aider avec tous les modules GaiaLumen: Utilisateurs, Planning, Events, Repas, Santé et Défis. Que souhaitez-vous savoir?';
      } else if (lowerText.includes('merci') || lowerText.includes('thanks')) {
        response = 'Avec plaisir! 😊 N\'hésitez pas si vous avez d\'autres questions.';
      } else {
        const responses = [
          'Je peux vous aider avec vos modules! Que recherchez-vous?',
          'Consultez la section Planning pour organiser vos repas de la semaine.',
          'Le module Santé vous permet de suivre vos indicateurs corporels.',
          'Besoin d\'aide? Je suis là pour vous guider! 🌿'
        ];
        response = responses[Math.floor(Math.random() * responses.length)];
      }
      addMessage(response, false);
    }, 800);
  }
  
  if (send) send.addEventListener('click', handleSend);
  if (input) input.addEventListener('keypress', e => {
    if (e.key === 'Enter') handleSend();
  });
}

// Gestion des événements
let allEvents = [];

function loadEvents() {
  const eventsGrid = document.getElementById('events-grid');
  const eventsLoading = document.getElementById('events-loading');
  const eventsEmpty = document.getElementById('events-empty');
  
  if (!eventsGrid || !eventsLoading || !eventsEmpty) return;
  
  eventsLoading.style.display = 'block';
  eventsGrid.style.display = 'none';
  eventsEmpty.style.display = 'none';
  
  // Données d'exemple (à remplacer par API)
  const sampleEvents = [
    {id:1,titre:'Atelier Compostage Urbain',description:'Apprenez à créer votre propre compost en appartement et réduisez vos déchets organiques.',type:'atelier',date:'2026-04-15T14:00:00',lieu:'Centre Écologique Paris 15e',image:'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800',capacite:25,participants_count:18},
    {id:2,titre:'Conférence Climat 2026',description:'Rencontre avec des experts du climat pour discuter des enjeux environnementaux actuels.',type:'conférence',date:'2026-04-20T18:30:00',lieu:'Auditorium Gaia, Lyon',image:'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',capacite:150,participants_count:89},
    {id:3,titre:'Sensibilisation Zéro Déchet',description:'Découvrez les gestes simples pour adopter un mode de vie zéro déchet au quotidien.',type:'sensibilisation',date:'2026-04-25T10:00:00',lieu:'Maison de l\'Environnement, Marseille',image:'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800',capacite:40,participants_count:35},
    {id:4,titre:'Atelier Cuisine Végétale',description:'Cuisinez des plats végétariens délicieux et découvrez les alternatives à la viande.',type:'atelier',date:'2026-05-02T16:00:00',lieu:'Espace Culinaire Bio, Bordeaux',image:'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=800',capacite:20,participants_count:12},
    {id:5,titre:'Conférence Biodiversité',description:'L\'importance de la biodiversité et comment la protéger dans nos villes.',type:'conférence',date:'2026-05-10T19:00:00',lieu:'Université Verte, Toulouse',image:'https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?w=800',capacite:100,participants_count:67},
    {id:6,titre:'Atelier Permaculture',description:'Initiez-vous aux principes de la permaculture et créez votre jardin écologique.',type:'atelier',date:'2026-05-15T09:00:00',lieu:'Jardin Partagé, Nantes',image:'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=800',capacite:30,participants_count:22}
  ];
  
  setTimeout(() => {
    allEvents = sampleEvents;
    filterEvents();
    eventsLoading.style.display = 'none';
  }, 800);
}

function filterEvents() {
  const searchInput = document.getElementById('event-search');
  const typeFilter = document.getElementById('event-type-filter');
  const eventsGrid = document.getElementById('events-grid');
  const eventsEmpty = document.getElementById('events-empty');
  
  if (!eventsGrid || !eventsEmpty) return;
  
  const search = searchInput?.value.toLowerCase() || '';
  const type = typeFilter?.value || '';
  
  const filtered = allEvents.filter(e => {
    const matchSearch = e.titre.toLowerCase().includes(search) || 
                       e.description.toLowerCase().includes(search) || 
                       e.lieu.toLowerCase().includes(search);
    const matchType = !type || e.type === type;
    return matchSearch && matchType;
  });

  if (filtered.length === 0) {
    eventsGrid.style.display = 'none';
    eventsEmpty.style.display = 'block';
  } else {
    eventsEmpty.style.display = 'none';
    eventsGrid.style.display = 'grid';
    renderEvents(filtered);
  }
}

function renderEvents(events) {
  const eventsGrid = document.getElementById('events-grid');
  if (!eventsGrid) return;
  
  const typeIcons = {atelier:'🛠️', conférence:'🎤', sensibilisation:'🌱'};
  
  eventsGrid.innerHTML = events.map(e => {
    const eventDate = new Date(e.date);
    const placesRestantes = e.capacite - e.participants_count;
    const isComplet = placesRestantes <= 0;
    const isPast = eventDate < new Date();
    
    return `
      <div class="info-card event-card" data-event-id="${e.id}">
        <div class="event-type-badge">
          ${typeIcons[e.type] || '📅'} ${e.type}
        </div>
        ${e.image ? `<img src="${e.image}" alt="${e.titre}" class="event-image">` : ''}
        <h3 class="event-title">${e.titre}</h3>
        <p class="event-desc">${e.description.substring(0,120)}...</p>
        <div class="event-info-list">
          <div class="event-info-item">
            <span>📅</span>
            <span>${eventDate.toLocaleDateString('fr-FR',{weekday:'short',day:'numeric',month:'short',year:'numeric'})}</span>
          </div>
          <div class="event-info-item">
            <span>🕐</span>
            <span>${eventDate.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}</span>
          </div>
          <div class="event-info-item">
            <span>📍</span>
            <span>${e.lieu}</span>
          </div>
          <div class="event-info-item">
            <span>👥</span>
            <span>${e.participants_count}/${e.capacite} participants</span>
          </div>
        </div>
        <div class="progress-indicator" style="margin-bottom:12px;">
          <div class="progress-fill" style="width:${(e.participants_count/e.capacite*100).toFixed(0)}%;background:${isComplet?'#e74c3c':'linear-gradient(90deg,var(--violet),var(--blue))'}"></div>
        </div>
        ${isComplet ? '<span class="feature-badge" style="background:rgba(231,76,60,.2);border-color:#e74c3c;color:#e74c3c;">🔒 Complet</span>' : 
          isPast ? '<span class="feature-badge" style="background:rgba(149,165,166,.2);border-color:#95a5a6;color:#95a5a6;">⏰ Terminé</span>' : 
          `<span class="feature-badge" style="background:rgba(46,204,113,.2);border-color:#2ecc71;color:#2ecc71;">✅ ${placesRestantes} places</span>`}
      </div>
    `;
  }).join('');

  document.querySelectorAll('.event-card').forEach(card => {
    card.addEventListener('click', () => {
      const eventId = parseInt(card.dataset.eventId);
      showEventDetail(eventId);
    });
  });
}

function showEventDetail(eventId) {
  const event = allEvents.find(e => e.id === eventId);
  const modal = document.getElementById('event-modal');
  const modalContent = document.getElementById('modal-content');
  
  if (!event || !modal || !modalContent) return;

  const eventDate = new Date(event.date);
  const placesRestantes = event.capacite - event.participants_count;
  const isComplet = placesRestantes <= 0;
  const isPast = eventDate < new Date();
  const typeIcons = {atelier:'🛠️', conférence:'🎤', sensibilisation:'🌱'};

  modalContent.innerHTML = `
    ${event.image ? `<img src="${event.image}" alt="${event.titre}" class="event-detail-image">` : ''}
    <div class="event-detail-body">
      <div class="event-detail-type">
        ${typeIcons[event.type] || '📅'} ${event.type.toUpperCase()}
      </div>
      <h2 class="event-detail-title">${event.titre}</h2>
      <p class="event-detail-desc">${event.description}</p>
      
      <div class="event-detail-grid">
        <div>
          <div class="event-detail-stat-label">📅 Date</div>
          <div class="event-detail-stat-value">${eventDate.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}</div>
        </div>
        <div>
          <div class="event-detail-stat-label">🕐 Heure</div>
          <div class="event-detail-stat-value">${eventDate.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}</div>
        </div>
        <div>
          <div class="event-detail-stat-label">📍 Lieu</div>
          <div class="event-detail-stat-value">${event.lieu}</div>
        </div>
        <div>
          <div class="event-detail-stat-label">👥 Participants</div>
          <div class="event-detail-stat-value">${event.participants_count}/${event.capacite}</div>
        </div>
      </div>

      <div class="progress-indicator" style="margin-bottom:24px;">
        <div class="progress-fill" style="width:${(event.participants_count/event.capacite*100).toFixed(0)}%;background:${isComplet?'#e74c3c':'linear-gradient(90deg,var(--violet),var(--blue))'}"></div>
      </div>

      ${!isPast && !isComplet ? `
        <button onclick="inscriptionEvent(${event.id})" class="event-btn-register">
          ✅ S'inscrire à cet événement
        </button>
      ` : isPast ? `
        <div class="event-status-msg past">
          ⏰ Cet événement est terminé
        </div>
      ` : `
        <div class="event-status-msg full">
          🔒 Événement complet
        </div>
      `}
    </div>
  `;

  modal.style.display = 'flex';
}

window.inscriptionEvent = function(eventId) {
  const event = allEvents.find(e => e.id === eventId);
  if (!event) return;
  
  event.participants_count++;
  
  // Toast notification
  const toast = document.createElement('div');
  toast.style.cssText = 'position:fixed;top:100px;right:30px;z-index:10000;background:linear-gradient(135deg,var(--violet),var(--blue));color:#fff;padding:16px 24px;border-radius:12px;box-shadow:0 8px 30px rgba(91,62,150,.5);animation:slideIn .3s ease;';
  toast.innerHTML = `<div style="display:flex;align-items:center;gap:12px;"><span style="font-size:1.5rem;">✅</span><div><div style="font-weight:600;margin-bottom:4px;">Inscription confirmée!</div><div style="font-size:.85rem;opacity:.9;">Vous êtes inscrit à ${event.titre}</div></div></div>`;
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideOut .3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);

  document.getElementById('event-modal').style.display = 'none';
  filterEvents();
};

function initEventsModule() {
  const modalClose = document.getElementById('modal-close');
  const modal = document.getElementById('event-modal');
  const searchInput = document.getElementById('event-search');
  const typeFilter = document.getElementById('event-type-filter');
  const refreshBtn = document.getElementById('event-refresh');
  
  if (modalClose) modalClose.addEventListener('click', () => modal.style.display = 'none');
  if (modal) modal.addEventListener('click', e => {
    if (e.target === modal) modal.style.display = 'none';
  });
  if (searchInput) searchInput.addEventListener('input', filterEvents);
  if (typeFilter) typeFilter.addEventListener('change', filterEvents);
  if (refreshBtn) refreshBtn.addEventListener('click', loadEvents);

  // Charger les événements au clic sur le menu
  const eventsMenuItem = document.querySelector('.menu-item[data-module="events"]');
  if (eventsMenuItem) {
    eventsMenuItem.addEventListener('click', () => {
      if (allEvents.length === 0) loadEvents();
    });
  }
  
  // Ajouter les styles d'animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
    @keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
    @keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}
    #event-search:focus,#event-type-filter:focus{border-color:var(--violet);box-shadow:0 0 0 3px rgba(91,62,150,.1);}
    #event-refresh:hover{transform:translateY(-2px);box-shadow:0 6px 25px rgba(91,62,150,.5);}
    #modal-close:hover{background:rgba(91,62,150,.4);transform:scale(1.1);}
    .event-card{transition:all .3s ease;}
    .event-card:hover{transform:translateY(-8px);box-shadow:0 16px 50px rgba(91,62,150,.4);}
  `;
  document.head.appendChild(style);
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
  if (!checkAuth()) return;
  
  const user = getCurrentUser();
  console.log('👤 Utilisateur connecté:', user);
  
  initTheme();
  initNavbar();
  initMobileMenu();
  initSidebarToggle();
  initModuleNavigation();
  initAIAssistant();
  initEventsModule();
});
