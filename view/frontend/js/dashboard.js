/**
 * Dashboard User JavaScript
 * Version SESSION UNIQUEMENT - Utilise auto_login.php pour tout
 */

console.log('🌿 GaiaLumen Dashboard chargé');

// ✅ UNE SEULE FONCTION pour vérifier ET récupérer l'utilisateur
async function checkAuthAndGetUser() {
    try {
        const response = await fetch(
            'http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/auto_login.php',
            { 
                method: 'GET',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' }
            }
        );
        const data = await response.json();
        console.log('auto_login.php response:', data);

        if (data.success && data.data) {
            const user = data.data;

            // Vérification du rôle
            if (user.role === 'admin') {
                window.location.href = '../backend/admin.html';
                return null;
            }

            // Stocker dans localStorage uniquement pour l'affichage (optionnel)
            localStorage.setItem('gaialumen-user', JSON.stringify(user));
            localStorage.setItem('gaialumen-token', 'session-' + Date.now());
            
            return user;
        } else {
            // Non authentifié - redirection
            localStorage.removeItem('gaialumen-user');
            localStorage.removeItem('gaialumen-token');
            window.location.href = 'login.html';
            return null;
        }
    } catch (error) {
        console.error('Erreur vérification:', error);
        window.location.href = 'login.html';
        return null;
    }
}

// ✅ Mettre à jour le profil (via updateprofil.php)
async function updateProfile(userData) {
    try {
        const response = await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/updateprofil.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        const data = await response.json();
        if (data.success) {
            // Recharger l'utilisateur après mise à jour
            const updatedUser = await checkAuthAndGetUser();
            return updatedUser;
        }
        return null;
    } catch (error) {
        console.error('Erreur mise à jour:', error);
        return null;
    }
}

// ✅ Déconnexion
async function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        try {
            await fetch('http://localhost/Esprit-PW-2A19-2526-SmartNutrition/view/backend/users/logout.php', {
                method: 'POST',
                credentials: 'include'
            });
            localStorage.removeItem('gaialumen-user');
            localStorage.removeItem('gaialumen-token');
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Erreur déconnexion:', error);
            window.location.href = 'login.html';
        }
    }
}

// ========== FONCTIONS D'INTERFACE ==========

// Gestion du thème
function initTheme() {
    const btn = document.getElementById('theme-toggle');
    const html = document.documentElement;
    const saved = localStorage.getItem('gaialumen-theme') || 'dark';
    
    html.setAttribute('data-theme', saved);
    if (btn) btn.textContent = saved === 'dark' ? '☀️' : '🌙';
    
    if (btn) {
        btn.addEventListener('click', () => {
            const n = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', n);
            localStorage.setItem('gaialumen-theme', n);
            btn.textContent = n === 'dark' ? '☀️' : '🌙';
        });
    }
}

// Navbar au scroll
function initNavbar() {
    const nb = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (nb) nb.classList.toggle('scrolled', window.scrollY > 40);
    });
}

// Menu mobile
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const mobileClose = document.getElementById('mobile-menu-close');
    
    function openMobileMenu() {
        mobileMenu?.classList.add('open');
        mobileOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        mobileMenu?.classList.remove('open');
        mobileOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileMenu);
    if (mobileClose) mobileClose.addEventListener('click', closeMobileMenu);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileMenu);
    
    document.querySelectorAll('.mobile-menu-item').forEach(item => {
        item.addEventListener('click', closeMobileMenu);
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu?.classList.contains('open')) {
            closeMobileMenu();
        }
    });
}

// Indicateur actif
function updateActiveNavItem(moduleName) {
    document.querySelectorAll('.nav-module-item').forEach(item => {
        if (item.dataset.module === moduleName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    document.querySelectorAll('.mobile-menu-item').forEach(item => {
        if (item.dataset.module === moduleName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

// Assistant AI
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
            const lowerText = text.toLowerCase();
            let response = '';
            
            if (lowerText.includes('planning') || lowerText.includes('repas')) {
                response = '📅 Le module Planning vous permet d\'organiser vos repas de la semaine.';
            } else if (lowerText.includes('santé') || lowerText.includes('health')) {
                response = '❤️ Le module Santé vous aide à suivre vos indicateurs corporels.';
            } else if (lowerText.includes('défi') || lowerText.includes('challenge')) {
                response = '🏆 Relevez des défis écologiques dans le module "Défis" !';
            } else if (lowerText.includes('aide') || lowerText.includes('help')) {
                response = 'Je peux vous aider avec Planning, Santé, Défis, Events et Repas.';
            } else {
                const responses = [
                    'Je peux vous aider avec vos modules! Que recherchez-vous?',
                    'Consultez la section Planning pour organiser vos repas.',
                    'Besoin d\'aide? Je suis là pour vous guider! 🌿'
                ];
                response = responses[Math.floor(Math.random() * responses.length)];
            }
            addMessage(response, false);
        }, 500);
    }
    
    if (send) send.addEventListener('click', handleSend);
    if (input) input.addEventListener('keypress', e => {
        if (e.key === 'Enter') handleSend();
    });
}

// Toast notification
function showToast(message, type = 'info') {
    let toast = document.querySelector('.toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast-notification';
        document.body.appendChild(toast);
    }
    
    toast.textContent = message;
    toast.className = `toast-notification ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ========== INITIALISATION PRINCIPALE ==========
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Initialisation du dashboard...');
    
    // Récupérer l'utilisateur via SESSION UNIQUEMENT
    const user = await checkAuthAndGetUser();
    
    if (user) {
        console.log('✅ Utilisateur connecté (session):', user);
        
        // Afficher le nom
        const userNameElement = document.getElementById('user-name');
        if (userNameElement) {
            const fullName = `${user.prenom} ${user.nom}`.trim();
            userNameElement.textContent = fullName || user.email;
        }
        
        // Afficher l'email
        const userEmailElement = document.getElementById('user-email');
        if (userEmailElement) {
            userEmailElement.textContent = user.email;
        }
        
        // Afficher le rôle
        const userRoleElement = document.getElementById('user-role');
        if (userRoleElement) {
            userRoleElement.textContent = user.role;
        }
    }
    
    // Initialiser tous les composants
    initTheme();
    initNavbar();
    initMobileMenu();
    initAIAssistant();
    
    // Exposer les fonctions globalement
    window.updateActiveNavItem = updateActiveNavItem;
    window.logout = logout;
    window.updateProfile = updateProfile;
    window.showToast = showToast;
});