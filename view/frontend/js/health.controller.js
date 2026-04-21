// Health Controller - Frontend JavaScript for Health Module
class HealthController {
    constructor() {
        this.currentUserId = 1; // Static user ID for now
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    bindEvents() {
        // Health assistant events
        const validateBtn = document.getElementById('validateAlimentBtn');
        if (validateBtn) {
            validateBtn.addEventListener('click', () => this.validateAliment());
        }

        const suggestBtn = document.getElementById('suggestRegimeBtn');
        if (suggestBtn) {
            suggestBtn.addEventListener('click', () => this.suggestRegime());
        }

        // Statistics tab events
        const statsTab = document.querySelector('[data-tab="statistics"]');
        if (statsTab) {
            statsTab.addEventListener('click', () => this.loadUserStatistics());
        }
    }

    loadInitialData() {
        this.loadHealthAssistant();
        // Load user statistics when statistics tab is clicked
    }

    async loadStatistics() {
        try {
            // Load dossier stats
            const dossierResponse = await fetch('controller/dossierMedical.controller.php?action=stats');
            if (dossierResponse.ok) {
                const dossierStats = await dossierResponse.json();
                this.updateDossierStats(dossierStats);
            }

            // Load regime stats
            const regimeResponse = await fetch('controller/regime.controller.php?action=stats');
            if (regimeResponse.ok) {
                const regimeStats = await regimeResponse.json();
                this.updateRegimeStats(regimeStats);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    updateDossierStats(stats) {
        const elements = {
            'stat-dossiers': stats.total_dossiers || 0,
            'stat-avg-imc': stats.avg_imc || 0,
            'stat-allergies': stats.allergies_count || 0
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id];
            }
        });

        // Update IMC distribution chart if exists
        if (stats.imc_distribution) {
            this.updateImcChart(stats.imc_distribution);
        }
    }

    updateRegimeStats(stats) {
        const elements = {
            'stat-regimes': stats.total_regimes || 0,
            'stat-avg-calories': stats.avg_calories || 0
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = elements[id];
            }
        });
    }

    updateImcChart(distribution) {
        // Simple chart update - could be enhanced with Chart.js
        const chartContainer = document.getElementById('imc-chart');
        if (!chartContainer) return;

        let html = '<div class="imc-bars">';
        distribution.forEach(item => {
            const percentage = (item.count / distribution.reduce((sum, d) => sum + d.count, 0)) * 100;
            html += `
                <div class="imc-bar">
                    <div class="bar" style="width: ${percentage}%"></div>
                    <span>${item.category}: ${item.count}</span>
                </div>
            `;
        });
        html += '</div>';
        chartContainer.innerHTML = html;
    }

    async validateAliment() {
        const alimentInput = document.getElementById('alimentInput');
        if (!alimentInput) return;

        const aliment = alimentInput.value.trim();
        if (!aliment) {
            this.showToast('Veuillez entrer un aliment', 'warning');
            return;
        }

        try {
            const response = await fetch(`controller/dossierMedical.controller.php?action=validate_aliment&aliment=${encodeURIComponent(aliment)}&user_id=${this.currentUserId}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            this.displayValidationResult(result, aliment);
        } catch (error) {
            console.error('Error validating aliment:', error);
            this.showToast('Erreur lors de la validation', 'error');
        }
    }

    displayValidationResult(result, aliment) {
        const resultDiv = document.getElementById('validationResult');
        if (!resultDiv) return;

        let html = `<h4>Résultat pour "${aliment}"</h4>`;

        if (result.allowed) {
            html += '<div class="alert alert-success">✅ Aliment autorisé</div>';
        } else {
            html += '<div class="alert alert-danger">❌ Aliment non recommandé</div>';
        }

        if (result.warnings && result.warnings.length > 0) {
            html += '<div class="warnings"><h5>Avertissements:</h5><ul>';
            result.warnings.forEach(warning => {
                html += `<li>${warning}</li>`;
            });
            html += '</ul></div>';
        }

        if (result.alternatives && result.alternatives.length > 0) {
            html += '<div class="alternatives"><h5>Alternatives suggérées:</h5><ul>';
            result.alternatives.forEach(alt => {
                html += `<li>${alt}</li>`;
            });
            html += '</ul></div>';
        }

        resultDiv.innerHTML = html;
        resultDiv.style.display = 'block';
    }

    async suggestRegime() {
        const goalSelect = document.getElementById('goalSelect');
        const restrictionsInput = document.getElementById('restrictionsInput');

        const goal = goalSelect ? goalSelect.value : '';
        const restrictions = restrictionsInput ? restrictionsInput.value : '';

        try {
            const response = await fetch(`controller/regime.controller.php?action=suggest&goal=${goal}&restrictions=${encodeURIComponent(restrictions)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            this.displaySuggestions(result.data || []);
        } catch (error) {
            console.error('Error suggesting regime:', error);
            this.showToast('Erreur lors des suggestions', 'error');
        }
    }

    displaySuggestions(suggestions) {
        const container = document.getElementById('suggestionsContainer');
        if (!container) return;

        if (suggestions.length === 0) {
            container.innerHTML = '<p>Aucune suggestion trouvée pour votre profil.</p>';
            return;
        }

        let html = '<div class="suggestions-grid">';
        suggestions.forEach(regime => {
            html += `
                <div class="suggestion-card">
                    <h4>${regime.nom_regime}</h4>
                    <p>${regime.description || 'Aucune description'}</p>
                    <div class="regime-meta">
                        <span class="badge">${regime.type_regime}</span>
                        <span class="badge">${regime.niveau_difficulte}</span>
                        <span class="calories">${regime.apport_calorique_moyen || 'N/A'} kcal</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    async search() {
        const queryInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const difficultyFilter = document.getElementById('difficultyFilter');

        const query = queryInput ? queryInput.value : '';
        const type = typeFilter ? typeFilter.value : '';
        const difficulty = difficultyFilter ? difficultyFilter.value : '';

        try {
            let url = `controller/regime.controller.php?action=search&q=${encodeURIComponent(query)}`;
            if (type) url += `&type_regime=${type}`;
            if (difficulty) url += `&niveau_difficulte=${difficulty}`;

            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            this.displaySearchResults(result.data || []);
        } catch (error) {
            console.error('Error searching:', error);
            this.showToast('Erreur lors de la recherche', 'error');
        }
    }

    displaySearchResults(results) {
        const container = document.getElementById('searchResults');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = '<p>Aucun résultat trouvé.</p>';
            return;
        }

        let html = '<div class="results-list">';
        results.forEach(regime => {
            html += `
                <div class="result-item">
                    <h4>${regime.nom_regime}</h4>
                    <p>${regime.description || ''}</p>
                    <div class="result-meta">
                        <span>${regime.type_regime}</span> |
                        <span>${regime.niveau_difficulte}</span> |
                        <span>${regime.apport_calorique_moyen || 'N/A'} kcal</span>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    }

    async sort() {
        const sortSelect = document.getElementById('sortSelect');
        if (!sortSelect) return;

        const value = sortSelect.value;
        const [field, direction] = value.split('-');

        try {
            const response = await fetch(`controller/regime.controller.php?action=sort&field=${field}&direction=${direction}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            this.displaySortedResults(result.data || []);
        } catch (error) {
            console.error('Error sorting:', error);
            this.showToast('Erreur lors du tri', 'error');
        }
    }

    displaySortedResults(results) {
        const container = document.getElementById('sortedResults');
        if (!container) return;

        // Reuse search results display
        this.displaySearchResults(results);
    }

    async exportData() {
        try {
            const response = await fetch('controller/dossierMedical.controller.php?action=export_pdf');
            if (!response.ok) throw new Error('Network response was not ok');

            const result = await response.json();
            if (result.success) {
                this.downloadPdf(result.html);
            }
        } catch (error) {
            console.error('Error exporting:', error);
            this.showToast('Erreur lors de l\'export', 'error');
        }
    }

    downloadPdf(html) {
        // Simple PDF download - in production, use a proper PDF library
        const blob = new Blob([html], { type: 'text/html' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'dossiers-medicaux.html';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    async loadHealthAssistant() {
        // Load common allergens and restrictions for suggestions
        const assistantContainer = document.getElementById('healthAssistant');
        if (!assistantContainer) return;

        assistantContainer.innerHTML = `
            <div class="assistant-section">
                <h3>🤖 Assistant Santé</h3>

                <div class="assistant-tool">
                    <h4>Valider un aliment</h4>
                    <input type="text" id="alimentInput" placeholder="Ex: arachides, gluten, lactose...">
                    <button id="validateAlimentBtn" class="btn-primary">Valider</button>
                    <div id="validationResult" class="result-container" style="display:none;"></div>
                </div>

                <div class="assistant-tool">
                    <h4>Suggérer un régime</h4>
                    <select id="goalSelect">
                        <option value="">Choisir un objectif</option>
                        <option value="lose_weight">Perdre du poids</option>
                        <option value="gain_weight">Prendre du poids</option>
                        <option value="maintain">Maintenir</option>
                    </select>
                    <input type="text" id="restrictionsInput" placeholder="Restrictions (séparées par des virgules)">
                    <button id="suggestRegimeBtn" class="btn-primary">Suggérer</button>
                    <div id="suggestionsContainer" class="result-container"></div>
                </div>
            </div>
        `;

        // Re-bind events for newly created elements
        this.bindEvents();
    }

    async loadUserStatistics() {
        try {
            // Load user's dossier
            const dossierResponse = await fetch('controller/dossierMedical.controller.php?action=read');
            if (dossierResponse.ok) {
                const dossierData = await dossierResponse.json();
                if (dossierData.success && dossierData.data && dossierData.data.length > 0) {
                    const dossier = dossierData.data[0];
                    this.updateUserStats(dossier);
                    document.getElementById('user-regimes-count').textContent = dossier.id_regime ? '1' : '0';
                }
            }

            // Load user's dossier count
            document.getElementById('user-dossiers-count').textContent = '1'; // Static for now

        } catch (error) {
            console.error('Error loading user statistics:', error);
        }
    }

    updateUserStats(dossier) {
        // Update IMC
        if (dossier.imc) {
            document.getElementById('current-imc').textContent = parseFloat(dossier.imc).toFixed(1);
        }

        // Calculate health score (simple algorithm)
        let healthScore = 50; // Base score

        if (dossier.imc) {
            const imc = parseFloat(dossier.imc);
            if (imc >= 18.5 && imc <= 24.9) healthScore += 20; // Normal IMC
            else if (imc >= 25 && imc <= 29.9) healthScore += 10; // Overweight
            else if (imc >= 30) healthScore -= 10; // Obese
            else healthScore -= 5; // Underweight
        }

        if (dossier.allergie) healthScore -= 5; // Allergies reduce score
        if (dossier.maladies) healthScore -= 10; // Diseases reduce score

        healthScore = Math.max(0, Math.min(100, healthScore)); // Clamp between 0-100

        document.getElementById('health-score').textContent = healthScore;

        // Color code the health score
        const scoreElement = document.getElementById('health-score');
        if (healthScore >= 80) scoreElement.style.color = '#27ae60'; // Green
        else if (healthScore >= 60) scoreElement.style.color = '#f39c12'; // Orange
        else scoreElement.style.color = '#e74c3c'; // Red
    }

    showToast(message, type = 'info') {
        // Simple toast implementation
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#3498db'};
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }, 100);
    }
}

// ==============================
// Validation Functions
// ==============================

// Validate Dossier Data (replaces HTML5 validation)
function validateDossierData(data) {
    const errors = [];
    
    if (!data.poids || data.poids === '') {
        errors.push('Le poids est obligatoire');
    } else {
        const poids = parseFloat(data.poids);
        if (isNaN(poids)) {
            errors.push('Le poids doit être un nombre');
        } else if (poids <= 0) {
            errors.push('Le poids doit être supérieur à 0');
        } else if (poids < 20 || poids > 300) {
            errors.push('Le poids doit être entre 20 et 300 kg');
        }
    }
    
    if (!data.taille || data.taille === '') {
        errors.push('La taille est obligatoire');
    } else {
        const taille = parseFloat(data.taille);
        if (isNaN(taille)) {
            errors.push('La taille doit être un nombre');
        } else if (taille <= 0) {
            errors.push('La taille doit être supérieure à 0');
        } else if (taille < 50 || taille > 250) {
            errors.push('La taille doit être entre 50 et 250 cm');
        }
    }
    
    if (data.groupe_sanguin && !/^(A|B|AB|O)[+-]$/.test(data.groupe_sanguin)) {
        errors.push('Le groupe sanguin doit être au format A+, B-, AB+, O-');
    }
    
    if (data.gravite_allergie && !data.allergie) {
        errors.push('Ajoutez une description d\'allergie avant de choisir sa gravité');
    }
    
    const validGravites = ['légère', 'modérée', 'sévère', 'anaphylactique'];
    if (data.gravite_allergie && !validGravites.includes(data.gravite_allergie)) {
        errors.push('La gravité doit être: légère, modérée, sévère ou anaphylactique');
    }
    
    return errors;
}

// Validate Regime Data
function validateRegimeData(data) {
    const errors = [];
    
    if (!data.nom_regime || data.nom_regime.trim().length < 2) {
        errors.push('Le nom du régime doit contenir au moins 2 caractères');
    }
    
    if (!data.type_regime) {
        errors.push('Le type de régime est obligatoire');
    }
    
    const validTypes = ['alimentaire', 'medical', 'sportif', 'perte_de_poids', 'prise_de_masse', 'autre'];
    if (data.type_regime && !validTypes.includes(data.type_regime)) {
        errors.push('Type de régime invalide');
    }
    
    if (!data.niveau_difficulte) {
        errors.push('Le niveau de difficulté est obligatoire');
    }
    
    const validNiveaux = ['facile', 'modere', 'avance'];
    if (data.niveau_difficulte && !validNiveaux.includes(data.niveau_difficulte)) {
        errors.push('Niveau de difficulté invalide');
    }
    
    if (data.apport_calorique_moyen) {
        const cal = parseFloat(data.apport_calorique_moyen);
        if (isNaN(cal) || cal < 500 || cal > 10000) {
            errors.push('L\'apport calorique doit être entre 500 et 10000 kcal');
        }
    }
    
    return errors;
}

// ==============================
// Ecological Score Calculation
// ==============================

function calculateEcologicalScore(regime) {
    let score = 50;
    
    if (!regime) return score;
    
    const type = regime.type_regime || regime.type;
    if (type === 'alimentaire') score += 20;
    else if (type === 'perte_de_poids') score += 10;
    else if (type === 'sportif') score += 15;
    
    const calories = parseFloat(regime.apport_calorique_moyen) || 0;
    if (calories < 1500) score += 10;
    else if (calories > 3000) score -= 10;
    
    const niveau = regime.niveau_difficulte || regime.niveau;
    if (niveau === 'facile') score += 10;
    else if (niveau === 'avance') score -= 5;
    
    return Math.max(0, Math.min(100, Math.round(score)));
}

// ==============================
// Nutritional Score Calculation
// ==============================

function calculateNutritionalScore(dossier, regime) {
    let score = 50;
    
    if (!dossier) return score;
    
    if (dossier.imc) {
        const imc = parseFloat(dossier.imc);
        if (imc >= 18.5 && imc <= 24.9) score += 20;
        else if (imc >= 25 && imc <= 29.9) score += 10;
        else if (imc >= 30) score -= 10;
        else if (imc < 18.5) score -= 5;
    }
    
    if (dossier.groupe_sanguin) score += 5;
    if (!dossier.allergie) score += 10;
    else score -= 5;
    
    if (!dossier.maladies) score += 5;
    
    if (regime) {
        const type = regime.type_regime || regime.type;
        if (type === 'medical') score += 15;
        else if (type === 'sportif') score += 10;
        else if (type === 'perte_de_poids') score += 5;
    }
    
    return Math.max(0, Math.min(100, Math.round(score)));
}

// ==============================
// Date Formatting
// ==============================

function formatDateFR(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Export functions to global scope
window.healthControllerValidation = {
    validateDossierData,
    validateRegimeData,
    calculateEcologicalScore,
    calculateNutritionalScore,
    formatDateFR
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.healthController = new HealthController();
});