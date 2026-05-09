// users.js - JavaScript pour le module utilisateur (version simplifiée)

// ==================== INITIALISATION ====================

document.addEventListener('DOMContentLoaded', () => {
  initUsersModule();
});

document.addEventListener('moduleLoaded', (e) => {
  if (e.detail.moduleName === 'users') {
    initUsersModule();
  }
});

function initUsersModule() {
  console.log('📱 Module Utilisateurs initialisé');
  animateStats();
}

// Animation des statistiques
function animateStats() {
  const statValues = document.querySelectorAll('.stat-value');
  statValues.forEach(stat => {
    const targetValue = parseFloat(stat.innerText);
    if (isNaN(targetValue)) return;
    let currentValue = 0;
    const duration = 1000;
    const stepTime = 20;
    const steps = duration / stepTime;
    const increment = targetValue / steps;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= targetValue) {
        stat.innerText = targetValue;
        clearInterval(timer);
      } else {
        stat.innerText = Math.floor(currentValue);
      }
    }, stepTime);
  });
}

// ==================== FONCTIONS UTILITAIRES ====================

function getBaseUrl() {
  return '..';
}

// ==================== NAVIGATION ====================

function goToProfile() {
  window.location.href = 'users/profile.html';
}

function goToPreferences() {
  window.location.href = 'users/preference.html';
}

// ==================== EXPORTS GLOBAUX ====================

window.goToProfile = goToProfile;
window.goToPreferences = goToPreferences;
window.showToast = function(message, type) {
  console.log(message);
};

console.log('✅ Module Users chargé - Version simplifiée');