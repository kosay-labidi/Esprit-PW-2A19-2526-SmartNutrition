// admin-translations.js - Système de traduction global unique avec traductions complètes pour les statistiques

// Langue actuelle
let currentAdminLang = localStorage.getItem('adminLanguage') || 'fr';

// Dictionnaire complet des traductions
const adminTranslations = {
    fr: {
        // Navigation
        adminTitle: '🛡️ Admin GaiaLumen',
        logout: 'Déconnexion',
        themeDark: '🌙 Sombre',
        themeLight: '☀️ Clair',
        reload: '🔄 Recharger',
        
        // Menu sidebar
        menuDashboard: ' Dashboard',
        menuUsers: 'GS Utilisateurs',
        menuPlanning: ' GS Planning',
        menuEvents: ' GS Events',
        menuMeals: ' GS Repas',
        menuHealth: ' GS Santé',
        menuChallenges: ' GS Défis',
        menuActivity: ' Logs Activité',
        
        // Module Users
        userManagement: '👥 Gestion des Utilisateurs',
        refresh: '🔄 Actualiser',
        export: '📥 Exporter',
        exportCSV: '📥 Exporter CSV',
        addUser: '➕ Ajouter',
        
        // Filtres
        all: 'Tous',
        active: '✅ Actifs',
        inactive: '⭕ Inactifs',
        suspended: '🚫 Suspendus',
        admins: '🛡️ Admins',
        users: '👤 Utilisateurs',
        
        // Placeholders
        searchPlaceholder: 'Rechercher par nom ou email...',
        
        // Selects
        allRoles: 'Tous les rôles',
        administrator: 'Administrateur',
        nutritionist: 'Nutritionniste',
        ecologist: 'Écologiste',
        standardUser: 'Utilisateur',
        
        sortByDate: '-- Trier par date --',
        newestFirst: '📅 Plus récent → Plus ancien',
        oldestFirst: '📅 Plus ancien → Plus récent',
        
        rowsPerPage: 'par page',
        
        // Tableau
        id: 'ID',
        fullName: 'Nom Complet',
        email: 'Email',
        role: 'Rôle',
        registrationDate: "Date d'inscription",
        status: 'Statut',
        actions: 'Actions',
        
        // Statuts
        statusActive: 'Actif',
        statusInactive: 'Inactif',
        statusSuspended: 'Suspendu',
        
        // Statistiques - Rôles
        userStats: '📊 Répartition des utilisateurs par rôle',
        summaryRoles: '📈 Résumé par rôle',
        pieChart: '🥧 Camembert',
        barChart: '📊 Barres',
        totalUsers: '📊 Total utilisateurs',
        
        // Statistiques - Statuts
        statusStats: '🔵 Répartition par statut',
        summaryStatus: '📈 Résumé par statut',
        
        // Libellés rôles
        roleAdmin: 'Administrateurs',
        roleNutritionist: 'Nutritionnistes',
        roleEcologist: 'Écologistes',
        roleUser: 'Utilisateurs',
        
        // Messages
        loading: 'Chargement...',
        noUsers: 'Aucun utilisateur trouvé',
        searchNoResults: 'Aucun utilisateur trouvé pour',
        
        // Pagination
        previous: '◀ Précédent',
        next: 'Suivant ▶',
        of: 'sur',
        usersLabel: 'utilisateurs',
        
        // Actions
        edit: 'Modifier',
        delete: 'Supprimer',
        suspend: '🚫 Suspendre',
        unsuspend: '✅ Réactiver',
        
        // Toasts
        refreshSuccess: 'Liste mise à jour',
        exportSuccess: 'Export réussi',
        exportError: 'Erreur lors de l\'export',
        
        chartUserCount: 'utilisateur(s)',
        chartUsersDataset: "Nombre d'utilisateurs",
        // Dans l'objet fr, ajoutez :
pieChartRole: '🥧 Répartition par rôle',
barChartRole: '📊 Répartition par rôle (barres)',
pieChartStatus: '🥧 Répartition par statut',
barChartStatus: '📊 Répartition par statut (barres)',
noData: '📭 Aucune donnée disponible',
total: 'Total',
usersCount: 'utilisateurs',
roleAdminShort: 'Admins',
roleNutritionistShort: 'Nutri.',
roleEcologistShort: 'Écolo.',
roleUserShort: 'Users',
statusActiveShort: 'Actif',
statusInactiveShort: 'Inactif',
statusSuspendedShort: 'Suspendu',
// Dans l'objet fr
roleAdmin: 'Administrateurs',
roleNutritionist: 'Nutritionnistes',
roleEcologist: 'Écologistes',
roleUser: 'Utilisateurs',
totalUsers: '📊 Total utilisateurs',
noData: '📭 Aucune donnée disponible'
    },
    en: {
        // Navigation
        adminTitle: '🛡️ GaiaLumen Admin',
        logout: 'Logout',
        themeDark: '🌙 Dark',
        themeLight: '☀️ Light',
        reload: '🔄 Reload',
        
        // Menu sidebar
        menuDashboard: ' Dashboard',
        menuUsers: ' User Management',
        menuPlanning: ' Planning Management',
        menuEvents: ' Events Management',
        menuMeals: ' Meals Management',
        menuHealth: ' Health Management',
        menuChallenges: ' Challenges Management',
        menuActivity: ' Activity Logs',
        
        // Module Users
        userManagement: '👥 User Management',
        refresh: '🔄 Refresh',
        export: '📥 Export',
        exportCSV: '📥 Export CSV',
        addUser: '➕ Add User',
        
        // Filters
        all: 'All',
        active: '✅ Active',
        inactive: '⭕ Inactive',
        suspended: '🚫 Suspended',
        admins: '🛡️ Admins',
        users: '👤 Users',
        
        // Placeholders
        searchPlaceholder: 'Search by name or email...',
        
        // Selects
        allRoles: 'All roles',
        administrator: 'Administrator',
        nutritionist: 'Nutritionist',
        ecologist: 'Ecologist',
        standardUser: 'User',
        
        sortByDate: '-- Sort by date --',
        newestFirst: '📅 Newest → Oldest',
        oldestFirst: '📅 Oldest → Newest',
        
        rowsPerPage: 'per page',
        
        // Table
        id: 'ID',
        fullName: 'Full Name',
        email: 'Email',
        role: 'Role',
        registrationDate: 'Registration Date',
        status: 'Status',
        actions: 'Actions',
        
        // Statuses
        statusActive: 'Active',
        statusInactive: 'Inactive',
        statusSuspended: 'Suspended',
        
        // Statistics - Roles
        userStats: '📊 User Distribution by Role',
        summaryRoles: '📈 Summary by Role',
        pieChart: '🥧 Pie Chart',
        barChart: '📊 Bar Chart',
        totalUsers: '📊 Total users',
        
        // Statistics - Status
        statusStats: '🔵 Distribution by Status',
        summaryStatus: '📈 Summary by Status',
        
        // Role labels
        roleAdmin: 'Administrators',
        roleNutritionist: 'Nutritionists',
        roleEcologist: 'Ecologists',
        roleUser: 'Users',
        
        // Messages
        loading: 'Loading...',
        noUsers: 'No users found',
        searchNoResults: 'No users found for',
        
        // Pagination
        previous: '◀ Previous',
        next: 'Next ▶',
        of: 'of',
        usersLabel: 'users',
        
        // Actions
        edit: 'Edit',
        delete: 'Delete',
        suspend: '🚫 Suspend',
        unsuspend: '✅ Reactivate',
        
        // Toasts
        refreshSuccess: 'List updated',
        exportSuccess: 'Export successful',
        exportError: 'Export error',
        
        chartUserCount: 'user(s)',
        chartUsersDataset: 'Number of users',
        // Dans l'objet en, ajoutez :
pieChartRole: '🥧 Role Distribution',
barChartRole: '📊 Role Distribution (bars)',
pieChartStatus: '🥧 Status Distribution',
barChartStatus: '📊 Status Distribution (bars)',
noData: '📭 No data available',
total: 'Total',
usersCount: 'users',
roleAdminShort: 'Admins',
roleNutritionistShort: 'Nutri.',
roleEcologistShort: 'Ecolo.',
roleUserShort: 'Users',
statusActiveShort: 'Active',
statusInactiveShort: 'Inactive',
statusSuspendedShort: 'Suspended',
// Dans l'objet en
roleAdmin: 'Administrators',
roleNutritionist: 'Nutritionists',
roleEcologist: 'Ecologists',
roleUser: 'Users',
totalUsers: '📊 Total users',
noData: '📭 No data available'
    }
};

// Fonction de traduction globale
function t(key) {
    return adminTranslations[currentAdminLang][key] || key;
}

// Mettre à jour l'interface (sidebar et éléments statiques)
function updateAdminUI() {
    // Mettre à jour le bouton de langue
    const langFlag = document.getElementById('lang-flag');
    const langLabel = document.getElementById('lang-label');
    if (langFlag && langLabel) {
        if (currentAdminLang === 'fr') {
            langFlag.innerHTML = '🌐';
            langLabel.innerHTML = 'FR';
        } else {
            langFlag.innerHTML = '🌐';
            langLabel.innerHTML = 'EN';
        }
    }
    
    // Logo
    const logoText = document.querySelector('.nav-logo-text');
    if (logoText) logoText.innerHTML = t('adminTitle');

    // Bouton thème
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        themeBtn.textContent = isDark ? t('themeLight') : t('themeDark');
    }

    // Bouton reload module
    const reloadBtn = document.getElementById('reload-module-btn');
    if (reloadBtn) reloadBtn.innerHTML = t('reload');
    
    // Bouton déconnexion (texte uniquement, pas le SVG)
    const logoutBtn = document.querySelector('.btn-logout');
    if (logoutBtn) {
        const textNode = Array.from(logoutBtn.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
        if (textNode) textNode.textContent = ' ' + t('logout');
    }
    
    // Menu sidebar
    const menuMapping = {
        'dashboard': 'menuDashboard',
        'users': 'menuUsers',
        'planning': 'menuPlanning',
        'events': 'menuEvents',
        'meals': 'menuMeals',
        'health': 'menuHealth',
        'challenges': 'menuChallenges',
        'activity': 'menuActivity'
    };
    
    document.querySelectorAll('.menu-item').forEach(item => {
        const module = item.dataset.module;
        if (module && menuMapping[module]) {
            const textSpan = item.querySelector('.menu-item-text');
            if (textSpan) textSpan.innerHTML = t(menuMapping[module]);
        }
    });
}

// Changer la langue
function switchLanguage() {
    currentAdminLang = currentAdminLang === 'fr' ? 'en' : 'fr';
    localStorage.setItem('adminLanguage', currentAdminLang);
    
    // Mettre à jour l'UI statique
    updateAdminUI();
    
    // Déclencher un événement pour informer les modules
    document.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: currentAdminLang } }));
    
    // Afficher un toast
    if (typeof showToast === 'function') {
        const msg = currentAdminLang === 'fr' ? '🌐 Langue : Français' : '🌐 Language: English';
        showToast('Langue', msg, 'success');
    }
}

// Mettre à jour la langue du module users
function setModuleLanguage(lang) {
    currentAdminLang = lang;
    updateAdminUI();
}

// Initialisation
function initAdminLanguage() {
    currentAdminLang = localStorage.getItem('adminLanguage') || 'fr';
    updateAdminUI();
    
    // Configurer le bouton de langue
    const langBtn = document.getElementById('lang-toggle');
    if (langBtn) {
        langBtn.removeEventListener('click', switchLanguage);
        langBtn.addEventListener('click', switchLanguage);
    }
}

// Exposer globalement
window.t = t;
window.switchLanguage = switchLanguage;
window.initAdminLanguage = initAdminLanguage;
window.setModuleLanguage = setModuleLanguage;
window.updateAdminUI = updateAdminUI;

console.log('🌐 Système de traduction initialisé, langue:', currentAdminLang);