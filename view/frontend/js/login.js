/**
 * Login Page JavaScript
 * Gestion de la connexion utilisateur + Remember Me
 */

// ── REMEMBER ME : Pré-remplir l'email au chargement ──────────────────────────
(function () {
  const savedEmail = localStorage.getItem('gaialumen-remember-email');
  if (savedEmail) {
    const emailInput = document.getElementById('email-input');
    const rememberCheckbox = document.getElementById('remember');
    if (emailInput) emailInput.value = savedEmail;
    if (rememberCheckbox) rememberCheckbox.checked = true;
  }
})();

// ── Gestion du formulaire de connexion ───────────────────────────────────────
async function handleLogin(event) {
  event.preventDefault();

  const email      = document.getElementById('email-input').value.trim();
  const password   = document.getElementById('password-input').value;
  const remember   = document.getElementById('remember')?.checked || false;
  const btn        = event.target.querySelector('.btn-submit');
  const errorDiv   = document.getElementById('login-error');

  // Validation basique
  errorDiv.style.display = 'none';
  if (!email || !password) {
    errorDiv.textContent = "L'email et le mot de passe sont obligatoires.";
    errorDiv.style.display = 'block';
    return;
  }

  btn.textContent = 'Connexion en cours...';
  btn.disabled = true;

  try {
    const response = await fetch(
      '../backend/users/login.php',
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',   // indispensable pour que le cookie soit déposé
        body: JSON.stringify({
          email,
          mdp: password,
          remember_me: remember   // ← envoyé au backend
        })
      }
    );

    const result = await response.json();
    console.log('Résultat login:', result);

    if (result.success) {
      // ── Sauvegarder / effacer l'email selon la case ──
      if (remember) {
        localStorage.setItem('gaialumen-remember-email', email);
      } else {
        localStorage.removeItem('gaialumen-remember-email');
      }

      // ── Stocker les infos utilisateur ──
      localStorage.setItem('gaialumen-user', JSON.stringify(result.data));
      localStorage.setItem('gaialumen-token', 'session-' + Date.now());

      btn.textContent = '✓ Connexion réussie!';

      setTimeout(() => {
        if (result.data.role === 'admin') {
          window.location.href = '../backend/admin.html';
        } else {
          window.location.href = 'dashboard.html';
        }
      }, 800);

    } else {
      btn.textContent = 'Se connecter';
      btn.disabled = false;
      errorDiv.textContent = result.message || 'Identifiants incorrects.';
      errorDiv.style.display = 'block';
    }

  } catch (error) {
    console.error('Erreur login:', error);
    btn.textContent = 'Se connecter';
    btn.disabled = false;
    errorDiv.textContent = 'Erreur de connexion au serveur.';
    errorDiv.style.display = 'block';
  }
}

// ── Gestion du thème ─────────────────────────────────────────────────────────
function initTheme() {
  const themeToggle = document.getElementById('theme-toggle');
  const html = document.documentElement;
  let isDark = localStorage.getItem('gaialumen-theme') !== 'light';

  html.setAttribute('data-theme', isDark ? 'dark' : 'light');
  if (themeToggle) {
    themeToggle.querySelector('svg').innerHTML = isDark
      ? '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'
      : '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';

    themeToggle.addEventListener('click', () => {
      isDark = !isDark;
      html.setAttribute('data-theme', isDark ? 'dark' : 'light');
      localStorage.setItem('gaialumen-theme', isDark ? 'dark' : 'light');
      themeToggle.querySelector('svg').innerHTML = isDark
        ? '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'
        : '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
    });
  }
}

// ── Initialisation ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  console.log('🔐 Login page loaded');
});