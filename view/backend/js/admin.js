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
  const saved = localStorage.getItem('gaialumen-theme') || 'dark';
  
  html.setAttribute('data-theme', saved);
  if (btn) btn.textContent = saved === 'dark' ? '☀️ Clair' : '🌙 Sombre';
  
  if (btn) {
    btn.addEventListener('click', () => {
      const n = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', n);
      localStorage.setItem('gaialumen-theme', n);
      btn.textContent = n === 'dark' ? '☀️ Clair' : '🌙 Sombre';
    });
  }
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
  const colors = { success: '#2ecc71', error: '#e74c3c', info: '#3498db', warning: '#f39c12' };
  const icons  = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  const color  = colors[type] || colors.info;
  const icon   = icons[type]  || icons.info;

  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed;top:20px;right:20px;z-index:99999;
    background:#1e1e2e;border:1px solid ${color};border-left:4px solid ${color};
    border-radius:10px;padding:14px 18px;min-width:280px;max-width:380px;
    display:flex;align-items:flex-start;gap:12px;
    box-shadow:0 8px 32px rgba(0,0,0,.4);
    opacity:0;transform:translateX(120%);transition:all .35s ease;
    color:#f0f0f0;font-family:inherit;font-size:14px;
  `;
  toast.innerHTML = `
    <span style="font-size:1.2rem;margin-top:2px;flex-shrink:0">${icon}</span>
    <div style="flex:1;min-width:0">
      <div style="font-weight:600;font-size:.95rem;margin-bottom:4px">${title}</div>
      <div style="font-size:.85rem;opacity:.8;line-height:1.4">${message}</div>
    </div>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:1.2rem;padding:0;line-height:1;flex-shrink:0">×</button>
  `;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; }, 50);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(120%)';
    setTimeout(() => toast.remove(), 350);
  }, 4000);
};

// Modale Ajouter Utilisateur
window.showAddUserModal = function() {
  // Supprimer une ancienne modale si elle existe
  document.getElementById('modal-add-user')?.remove();

  const modal = document.createElement('div');
  modal.id = 'modal-add-user';
  modal.style.cssText = `
    position:fixed;inset:0;z-index:99998;
    background:rgba(0,0,0,.7);backdrop-filter:blur(4px);
    display:flex;align-items:center;justify-content:center;
    opacity:0;transition:opacity .3s ease;
  `;
  modal.innerHTML = `
    <div style="
      background:#1e1e2e;border:1px solid rgba(91,62,150,.5);
      border-radius:18px;padding:36px;width:100%;max-width:480px;
      box-shadow:0 24px 64px rgba(0,0,0,.6);
      transform:translateY(20px);transition:transform .3s ease;
      color:#f0f0f0;font-family:inherit;
    " id="modal-add-user-box">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h2 style="margin:0;font-size:1.3rem;font-weight:700">➕ Ajouter un utilisateur</h2>
        <button onclick="document.getElementById('modal-add-user').remove()" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:1.4rem;line-height:1">×</button>
      </div>
      <div id="modal-add-user-errors" style="display:none;background:rgba(231,76,60,.1);border:1px solid #e74c3c;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.85rem;color:#e74c3c"></div>
      <div style="display:grid;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div>
            <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Nom</label>
            <input id="au-nom" type="text" placeholder="Farhani" style="${inputStyle()}">
          </div>
          <div>
            <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Prénom</label>
            <input id="au-prenom" type="text" placeholder="Ahmed" style="${inputStyle()}">
          </div>
        </div>
        <div>
          <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Email</label>
          <input id="au-email" type="email" placeholder="Farhani.Ahmed@email.com" style="${inputStyle()}">
        </div>
        <div>
          <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Mot de passe (min. 6 caractères)</label>
          <input id="au-mdp" type="password" placeholder="••••••••" style="${inputStyle()}">
        </div>
        <div>
          <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Confirmer le mot de passe</label>
          <input id="au-mdp2" type="password" placeholder="••••••••" style="${inputStyle()}">
        </div>
        <div>
          <label style="display:block;font-size:.82rem;opacity:.7;margin-bottom:6px">Rôle</label>
          <select id="au-role" style="${inputStyle()}">
            <option value="utilisateur"style="color: #000000;">Utilisateur</option>
            <option value="nutritionniste" style="color: #000000;">Nutritionniste</option>
            <option value="ecologiste" style="color: #000000;">Écologiste</option>
            <option value="admin" style="color: #000000;" >Admin</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:12px;margin-top:24px;justify-content:flex-end">
        <button onclick="document.getElementById('modal-add-user').remove()" style="
          padding:10px 22px;border-radius:50px;border:1px solid rgba(255,255,255,.2);
          background:transparent;color:#f0f0f0;cursor:pointer;font-size:.9rem;
        ">Annuler</button>
        <button onclick="submitAddUser()" style="
          padding:10px 26px;border-radius:50px;border:none;
          background:linear-gradient(135deg,#5B3E96,#3A86C4);
          color:#fff;cursor:pointer;font-size:.9rem;font-weight:600;
          box-shadow:0 4px 16px rgba(91,62,150,.4);
        ">Ajouter</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  setTimeout(() => {
    modal.style.opacity = '1';
    document.getElementById('modal-add-user-box').style.transform = 'translateY(0)';
  }, 10);

  // Fermer en cliquant sur le fond
  modal.addEventListener('click', e => {
    if (e.target === modal) modal.remove();
  });
};

function inputStyle() {
  return `
    width:100%;box-sizing:border-box;padding:10px 14px;
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.15);
    border-radius:8px;color:#f0f0f0;font-size:.9rem;font-family:inherit;
    outline:none;transition:border-color .2s;
  `;
}

window.submitAddUser = async function() {
  const nom    = document.getElementById('au-nom')?.value.trim();
  const prenom = document.getElementById('au-prenom')?.value.trim();
  const email  = document.getElementById('au-email')?.value.trim();
  const mdp    = document.getElementById('au-mdp')?.value;
  const mdp2 = document.getElementById('au-mdp2')?.value;
  const role   = document.getElementById('au-role')?.value;
  const errDiv = document.getElementById('modal-add-user-errors');
  const errors = [];

if (!nom || !prenom || !email || !mdp) {
  errors.push('Tous les champs sont requis');
}

if (errors.length === 0) {
  if (/\d/.test(nom) || /\d/.test(prenom))
    errors.push('Le nom et le prénom ne doivent pas contenir de chiffres');

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
    errors.push('Email invalide');

  if (mdp.length < 6)
    errors.push('Le mot de passe doit contenir au moins 6 caractères');
  if (mdp !== mdp2)
  errors.push('Les mots de passe ne correspondent pas');
}

  if (errors.length > 0) {
    errDiv.style.display = 'block';
    errDiv.innerHTML = errors.map(e => `• ${e}`).join('<br>');
    return;
  }

  errDiv.style.display = 'none';

  // Envoi vers addUser.php via fetch
  const formData = new FormData();
  formData.append('nom', nom);
  formData.append('prenom', prenom);
  formData.append('email', email);
  formData.append('mdp', mdp);
  formData.append('role', role);

  try {
    const response = await fetch('users/addUser.php', { method: 'POST', body: formData });

    const text = await response.text();

    if (response.ok) {
  document.getElementById('modal-add-user')?.remove();
  showToast('Succès', 'Utilisateur ajouté avec succès !', 'success');
} else {
  errDiv.style.display = 'block';
  errDiv.innerHTML = '• Email déjà utilisé ou erreur serveur';
}
  } catch (err) {
    errDiv.style.display = 'block';
    errDiv.innerHTML = '• Erreur réseau, veuillez réessayer';
  }
};

// Gestion des modales
window.openModal = function(id) {
  document.getElementById(id)?.classList.add('active');
};

window.closeModal = function(id) {
  document.getElementById(id)?.classList.remove('active');
};