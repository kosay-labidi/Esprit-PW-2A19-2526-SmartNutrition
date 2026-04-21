/**
 * Dashboard User JavaScript
 * UNIQUEMENT avec sessions PHP - Sans fichiers supplémentaires
 */

console.log('🌿 GaiaLumen Dashboard chargé');

// ✅ Vérifier l'authentification via un endpoint existant
async function checkAuth() {
    try {
        // Utiliser getUserById ou un appel qui nécessite l'authentification
        const response = await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/updateprofil.php?action=get&id=0', {
            method: 'GET',
            credentials: 'include'  // Important pour les cookies de session
        });
        
        const data = await response.json();
        
        // Si non authentifié, updateprofil.php retournera une erreur 401
        if (!data.success && data.message === 'Non authentifié. Veuillez vous connecter.') {
            window.location.href = 'login.html';
            return false;
        }
        
        // Récupérer l'utilisateur depuis la réponse si possible
        if (data.user) {
            return data.user;
        }
        
        return true;
    } catch (error) {
        console.error('Erreur vérification session:', error);
        window.location.href = 'login.html';
        return false;
    }
}

// ✅ Récupérer l'utilisateur connecté via un appel API
async function getCurrentUser() {
    try {
        // Option 1: Utiliser updateprofil.php avec un ID invalide
        const response = await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/updateprofil.php?action=get&id=0', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success && data.user) {
            return data.user;
        }
        
        // Option 2: Si ça ne marche pas, essayer de récupérer depuis la page
        return null;
    } catch (error) {
        return null;
    }
}

// ✅ Déconnexion - utilise logout.php existant
async function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        try {
            await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/logout.php', {
                method: 'POST',
                credentials: 'include'
            });
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Erreur déconnexion:', error);
            window.location.href = 'login.html';
        }
    }
}

// Gestion du thème (garder pour l'UI uniquement)
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
    
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            sidebar?.classList.toggle('mobile-open');
        });
    }
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

// Initialisation principale
document.addEventListener('DOMContentLoaded', async () => {
    // Vérifier l'authentification
    const isAuthenticated = await checkAuth();
    if (!isAuthenticated) return;
    
    // Optionnel: essayer de récupérer l'utilisateur
    const user = await getCurrentUser();
    if (user) {
        console.log('👤 Utilisateur connecté:', user);
        const userNameElement = document.getElementById('user-name');
        if (userNameElement && user.prenom) {
            userNameElement.textContent = `${user.prenom} ${user.nom}`;
        }
    }
    
    initTheme();
    initNavbar();
    initMobileMenu();
    initSidebarToggle();
    initAIAssistant();
});