/**
 * Forgot Password JavaScript
 * Gestion de la récupération de mot de passe
 */

// Gestion du formulaire de récupération
async function handleForgotPassword(event) {
  event.preventDefault();
  
  const email = document.getElementById('email').value;
  const btn = event.target.querySelector('.btn-submit') || event.target.querySelector('.btn');
  const message = document.getElementById('message');
  
  btn.textContent = 'Envoi en cours...';
  btn.disabled = true;
  
  try {
    const response = await fetch('../backend/users/forgot_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    
    const data = await response.json();
    
    if (data.success) {
      message.className = 'message success';
      message.textContent = '✓ Email envoyé! Vérifiez votre boîte de réception.';
      message.style.display = 'block';
      
      btn.textContent = '✓ Email envoyé!';
      
      // Afficher le lien de développement dans la console
      if (data.dev_reset_link) {
        console.log('🔗 Lien de réinitialisation (développement) :', data.dev_reset_link);
        message.textContent += ' (Voir la console pour le lien de test)';
      }
      
      // Redirection après 5 secondes pour laisser le temps de copier le lien
      setTimeout(() => {
        window.location.href = 'login.html';
      }, 5000);
    } else {
      message.className = 'message error';
      message.textContent = data.message || 'Erreur lors de l\'envoi de l\'email';
      message.style.display = 'block';
      
      btn.textContent = 'Envoyer le lien';
      btn.disabled = false;
    }
  } catch (error) {
    console.error('Erreur:', error);
    message.className = 'message error';
    message.textContent = 'Erreur de connexion au serveur';
    message.style.display = 'block';
    
    btn.textContent = 'Envoyer le lien';
    btn.disabled = false;
  }
}

// Gestion du thème
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

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  
  const form = document.getElementById('forgotForm') || document.getElementById('form-forgot');
  if (form) {
    form.addEventListener('submit', handleForgotPassword);
  }
  
  console.log('🔓 Forgot Password page loaded');
});
