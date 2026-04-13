// =============================================
// PLANNING ADMIN JS - Version ultra robuste
// =============================================

console.log('📅 Planning Admin JS chargé (version robuste)');

let planningAllData = [];
let planningInitialized = false;

// Fonction principale d'initialisation
function initPlanningModule() {
    console.log('🚀 initPlanningModule() appelé');

    const tbody = document.getElementById('plannings-tbody');
    if (!tbody) {
        console.warn('⚠️ #plannings-tbody non trouvé dans le DOM');
        return;
    }

    if (planningInitialized) {
        console.log('⏭️ Déjà initialisé → on recharge juste les données');
        loadPlanningData();
        return;
    }

    planningInitialized = true;
    console.log('✅ Module Planning détecté et initialisé');
    loadPlanningData();
}

// Charger les données depuis le backend
function loadPlanningData() {
    const tbody = document.getElementById('plannings-tbody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="8" style="text-align:center;padding:60px;">
                <div style="margin:0 auto 20px;width:40px;height:40px;border:4px solid #ddd;border-top-color:#5B3E96;border-radius:50%;animation:spin 1s linear infinite;"></div>
                Chargement des demandes...
            </td>
        </tr>`;

    fetch('planning/listDemandeplanning.php')
        .then(r => r.json())
        .then(result => {
            console.log('✅ Données reçues :', result);

            if (result.success && Array.isArray(result.data)) {
                planningAllData = result.data;
                renderPlanningTable();
            } else {
                throw new Error(result.error || 'Données invalides');
            }
        })
        .catch(err => {
            console.error('❌ Erreur fetch :', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align:center;padding:60px;color:#e74c3c;">
                        ❌ Erreur de chargement<br>
                        <button onclick="loadPlanningData()" style="margin-top:15px;padding:10px 20px;background:#5B3E96;color:white;border:none;border-radius:8px;cursor:pointer;">Réessayer</button>
                    </td>
                </tr>`;
        });
}

// Rendu du tableau + stats
function renderPlanningTable() {
    const data = planningAllData;
    const tbody = document.getElementById('plannings-tbody');

    // Mise à jour des stats
    document.getElementById('stat-total').textContent = data.length;
    const uniqueUsers = new Set(data.map(item => item.id_utilisateur)).size;
    document.getElementById('stat-users').textContent = uniqueUsers;

    const avgCalories = data.length > 0 
        ? Math.round(data.reduce((sum, item) => sum + parseInt(item.calories || 0), 0) / data.length) 
        : 0;
    document.getElementById('stat-calories').textContent = avgCalories;

    const avgBudget = data.length > 0 
        ? (data.reduce((sum, item) => sum + parseFloat(item.budget || 0), 0) / data.length).toFixed(2) 
        : '0.00';
    document.getElementById('stat-budget').textContent = avgBudget + ' €';

    // Rendu du tableau
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:60px;color:#999;">Aucune demande trouvée</td></tr>`;
        return;
    }

    tbody.innerHTML = data.map(d => `
        <tr>
            <td><strong>#${d.id_demande}</strong></td>
            <td>Utilisateur ${d.id_utilisateur}</td>
            <td><strong>${parseInt(d.calories).toLocaleString()}</strong> kcal</td>
            <td><strong>${parseFloat(d.budget).toFixed(2)} €</strong></td>
            <td>${d.type_budget}</td>
            <td>${d.duree}</td>
            <td>${d.type_duree}</td>
            <td>${new Date(d.date_demande).toLocaleString('fr-FR')}</td>
        </tr>
    `).join('');
}

// Fonctions appelées par les boutons/filtres
window.refreshPlannings = function() {
    loadPlanningData();
};

window.searchPlannings = function() { /* Pour l'instant on recharge tout */ loadPlanningData(); };
window.filterPlannings = function() { loadPlanningData(); };
window.filterByChip = function(el, type) {
    document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    loadPlanningData(); // On recharge simplement (filtre avancé à faire plus tard)
};

// === AUTO-INITIALISATION ROBUSTE ===
function autoInitPlanning() {
    const planningSection = document.getElementById('planning');
    if (planningSection && !planningInitialized) {
        console.log('🔍 Module planning détecté via auto-init');
        initPlanningModule();
    }
}

// 1. Écoute l'événement du module loader
document.addEventListener('adminModuleLoaded', function(e) {
    if (e.detail.moduleName === 'planning') {
        console.log('🎯 Événement adminModuleLoaded reçu pour planning');
        setTimeout(initPlanningModule, 100);
    }
});

// 2. MutationObserver (fallback très fiable)
const observer = new MutationObserver(autoInitPlanning);
observer.observe(document.querySelector('.main-content') || document.body, {
    childList: true,
    subtree: true
});

// 3. Tentative immédiate au chargement
window.addEventListener('load', () => {
    setTimeout(autoInitPlanning, 300);
    setTimeout(autoInitPlanning, 800);   // double sécurité
});

console.log('✅ Planning Admin JS initialisé avec succès (mode robuste)');