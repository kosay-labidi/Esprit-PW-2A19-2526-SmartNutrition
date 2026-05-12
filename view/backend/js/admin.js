/**
 * Admin Dashboard Main Script - GaiaLumen
 * Version modularisée et harmonisée
 */

console.log('🛡️ GaiaLumen Admin Dashboard chargé');

// Initialisation globale au chargement du document
document.addEventListener('DOMContentLoaded', () => {
  initPreloader();
  initCursor();
  initTheme();
  initNavbar();
  initMobileMenu();
  initSidebarToggle();
  initStatsProgression();
});

/* ═══════════════════════════════════════════════════════════
   PRELOADER
   ═══════════════════════════════════════════════════════════ */
function initPreloader() {
  const c = document.getElementById('pl-canvas');
  if (!c) return;
  const ctx = c.getContext('2d');
  c.width = 140; c.height = 140;
  let a = 0, prog = 0;
  
  function draw() {
    prog = Math.min(prog + 2, 100);
    a += .04;
    ctx.clearRect(0, 0, 140, 140);
    ctx.save(); ctx.translate(70, 70); ctx.rotate(a);
    for (let i = 0; i < 3; i++) {
      ctx.beginPath(); ctx.arc(0, 0, 48 - i * 10, 0, Math.PI * 2);
      ctx.strokeStyle = `rgba(${i === 0 ? '58,134,196' : i === 1 ? '91,62,150' : '31,61,43'},${.6 - i * .15})`;
      ctx.lineWidth = 1.5; ctx.stroke();
    }
    ctx.rotate(-a * .3);
    const g = ctx.createLinearGradient(-18, -26, 18, 26);
    g.addColorStop(0, '#1F3D2B'); g.addColorStop(1, '#3A86C4');
    ctx.beginPath(); ctx.fillStyle = g;
    ctx.moveTo(0, -26); ctx.bezierCurveTo(19, -12, 22, 7, 0, 26);
    ctx.bezierCurveTo(-22, 7, -19, -12, 0, -26); ctx.fill();
    ctx.beginPath(); ctx.moveTo(0, -24); ctx.lineTo(0, 24);
    ctx.strokeStyle = 'rgba(242,232,207,.5)'; ctx.lineWidth = 1; ctx.stroke();
    ctx.restore();
    
    if (prog < 100) {
      requestAnimationFrame(draw);
    } else {
      setTimeout(() => {
        const pl = document.getElementById('preloader');
        if (pl) pl.classList.add('hidden');
      }, 300);
    }
  }
  requestAnimationFrame(draw);
}

/* ═══════════════════════════════════════════════════════════
   CURSEUR PERSONNALISÉ
   ═══════════════════════════════════════════════════════════ */
function initCursor() {
  const cur = document.getElementById('cursor');
  const trail = document.getElementById('cursor-trail');
  if (!cur || !trail) return;
  let mx = 0, my = 0, tx = 0, ty = 0;
  
  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cur.style.left = mx + 'px'; cur.style.top = my + 'px';
  });
  
  (function loop() {
    tx += (mx - tx) * .12; ty += (my - ty) * .12;
    trail.style.left = tx + 'px'; trail.style.top = ty + 'px';
    requestAnimationFrame(loop);
  })();
  
  // Effet hover sur les éléments interactifs
  const updateHovers = () => {
    document.querySelectorAll('a, button, input, select, .stat-card, .menu-item, .action-btn, .chip, .data-card, .page-btn').forEach(el => {
      if (!el.dataset.hasCursorEvents) {
        el.addEventListener('mouseenter', () => cur.classList.add('hover'));
        el.addEventListener('mouseleave', () => cur.classList.remove('hover'));
        el.dataset.hasCursorEvents = "true";
      }
    });
  };
  
  updateHovers();
  // Observer les changements dans le DOM pour les nouveaux éléments (modules chargés dynamiquement)
  const observer = new MutationObserver(updateHovers);
  observer.observe(document.body, { childList: true, subtree: true });
}

/* ═══════════════════════════════════════════════════════════
   GESTION DU THÈME
   ═══════════════════════════════════════════════════════════ */
function initTheme() {
  const btn = document.getElementById('theme-toggle');
  const html = document.documentElement;
  const normalizeTheme = (value) => value === 'light' ? 'light' : 'dark';
  const applyTheme = (theme) => {
    const next = normalizeTheme(theme);
    html.setAttribute('data-theme', next);
    document.body?.setAttribute('data-theme', next);
    localStorage.setItem('gaialumen-theme', next);
    localStorage.setItem('theme', next);
    if (btn) btn.textContent = next === 'dark' ? '🌙 Sombre' : '☀️ Clair';
  };
  const saved = normalizeTheme(localStorage.getItem('gaialumen-theme') || localStorage.getItem('theme') || 'dark');
  
  applyTheme(saved);
  
  if (btn) {
    btn.addEventListener('click', () => {
      applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
  }

  window.addEventListener('storage', (event) => {
    if (event.key === 'gaialumen-theme' || event.key === 'theme') {
      applyTheme(event.newValue);
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   NAVBAR & SIDEBAR
   ═══════════════════════════════════════════════════════════ */
function initNavbar() {
  const nb = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    if (nb) nb.classList.toggle('scrolled', window.scrollY > 40);
  });
}

function initMobileMenu() {
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.querySelector('.sidebar-menu');
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      sidebar?.classList.toggle('mobile-open');
    });
  }
}

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

/* ═══════════════════════════════════════════════════════════
   UTILITAIRES ADMIN
   ═══════════════════════════════════════════════════════════ */
function initStatsProgression() {
  const progressBar = document.getElementById('progressBar');
  if (progressBar) {
    window.addEventListener('scroll', () => {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + "%";
    });
  }
}

// Fonctions globales pour l'interface admin
window.logout = function() {
  if (confirm('Êtes-vous sûr de vouloir vous déconnecter?')) {
    localStorage.removeItem('gaialumen-token');
    localStorage.removeItem('gaialumen-user');
    window.location.href = '../frontend/index.html';
  }
};

window.showToast = function(title, message, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || 'ℹ️'}</div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="this.parentElement.remove()">×</button>
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.classList.add('show'), 100);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 4000);
};

// Gestion des modales
window.openModal = function(id) {
  document.getElementById(id)?.classList.add('active');
};

window.closeModal = function(id) {
  document.getElementById(id)?.classList.remove('active');
};
