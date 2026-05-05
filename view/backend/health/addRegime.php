<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../Model/Regime.php';

$ctrl = new RegimeController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_regime = $_POST['nom_regime'] ?? '';
    $type_regime = $_POST['type_regime'] ?? '';
    $niveau_difficulte = $_POST['niveau_difficulte'] ?? '';

    if (empty($nom_regime) || empty($type_regime) || empty($niveau_difficulte)) {
        $error = "❌ Tous les champs marqués d'une * sont obligatoires";
    } else {
        try {
            $alimentsInterdits = !empty($_POST['aliments_interdits']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_interdits']))) : [];
            $alimentsRecommandes = !empty($_POST['aliments_recommandes']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_recommandes']))) : [];

            $r = new Regime(
                null,
                $nom_regime,
                Regime::generateSlug($nom_regime),
                $_POST['description'] ?? '',
                $type_regime,
                $niveau_difficulte,
                json_encode($alimentsInterdits),
                json_encode($alimentsRecommandes),
                floatval($_POST['apport_calorique_moyen'] ?? 0)
            );
            $ctrl->add($r);
            $success = "✅ Régime ajouté avec succès!";
            header('Refresh: 2; url=/crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html');
        } catch (Exception $e) {
            $error = "❌ Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Régime Alimentaire</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --vert: #013220;
            --sable: #CBBD93;
            --violet: #BA5BED;
            --bleu: #77B5FE;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--sable) 0%, #b8a478 100%);
            color: var(--vert);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            color: var(--vert);
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header p {
            font-size: 1.1rem;
            color: #555;
            font-weight: 500;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-top: 5px solid var(--vert);
        }
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .form-section {
            margin-bottom: 35px;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--vert);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--bleu);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-grid.full { grid-template-columns: 1fr; }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--vert);
            font-size: 0.95rem;
        }
        label .required {
            color: #dc3545;
        }
        input, select, textarea {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--bleu);
            box-shadow: 0 0 0 3px rgba(119, 181, 254, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }
        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--violet) 0%, #9d4dd4 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(186, 91, 237, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(186, 91, 237, 0.4);
        }
        .btn-secondary {
            background: var(--bleu);
            color: white;
            flex: 0.5;
        }
        .btn-secondary:hover {
            background: #4a9ee8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ Ajouter Régime Alimentaire</h1>
            <p>Créez un nouveau régime alimentaire</p>
        </div>

        <div class="card">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <!-- SECTION: Identification du Régime -->
                <div class="form-section">
                    <div class="section-title">📋 Identification du Régime</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="nom">Nom du Régime <span class="required">*</span></label>
                            <input type="text" id="nom" name="nom_regime" placeholder="ex. Régime sans gluten">
                        </div>
                    </div>
                </div>

                <!-- SECTION: Caractéristiques -->
                <div class="form-section">
                    <div class="section-title">⚙️ Caractéristiques</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="type">Type de Régime <span class="required">*</span></label>
                            <select name="type_regime" id="type">
                                <option value="">Sélectionner...</option>
                                <option value="alimentaire">Alimentaire</option>
                                <option value="medical">Médical</option>
                                <option value="perte_de_poids">Perte de poids</option>
                                <option value="prise_de_masse">Prise de masse</option>
                                <option value="sportif">Sportif</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="niveau">Niveau de Difficulté <span class="required">*</span></label>
                            <select name="niveau_difficulte" id="niveau">
                                <option value="">Sélectionner...</option>
                                <option value="facile">Facile</option>
                                <option value="modere">Modéré</option>
                                <option value="avance">Avancé</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Description -->
                <div class="form-section">
                    <div class="section-title">📝 Description</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="description">Description du Régime</label>
                            <textarea id="description" name="description" placeholder="Décrivez les principes et objectifs du régime..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Aliments -->
                <div class="form-section">
                    <div class="section-title">🥗 Aliments</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="recommandes">Aliments Recommandés</label>
                            <textarea id="recommandes" name="aliments_recommandes" placeholder="Entrez les aliments séparés par des virgules" style="min-height: 80px;"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="interdits">Aliments Interdits</label>
                            <textarea id="interdits" name="aliments_interdits" placeholder="Entrez les aliments séparés par des virgules" style="min-height: 80px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Nutrition -->
                <div class="form-section">
                    <div class="section-title">⚡ Nutrition</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="calories">Apport Calorique Moyen (kcal/jour) <span class="required">*</span></label>
                            <input type="number" id="calories" name="apport_calorique_moyen" step="0.1" min="0" placeholder="ex. 2000">
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Ajouter le régime</button>
                    <a href="/crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html" class="btn btn-secondary">← Retour</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateForm() {
            const nom     = document.getElementById('nom').value.trim();
            const type    = document.getElementById('type').value;
            const niveau  = document.getElementById('niveau').value;
            const calEl   = document.getElementById('calories');
            const cal     = calEl.value;

            if (!nom || nom.length < 3) {
                alert('⚠️ Le nom du régime doit contenir au moins 3 caractères.');
                return false;
            }
            if (!type) {
                alert('⚠️ Veuillez sélectionner un type de régime.');
                return false;
            }
            if (!niveau) {
                alert('⚠️ Veuillez sélectionner un niveau de difficulté.');
                return false;
            }
            if (cal === '' || isNaN(parseFloat(cal))) {
                alert('⚠️ L\'apport calorique est obligatoire.');
                calEl.focus();
                return false;
            }
            if (parseFloat(cal) < 1000) {
                alert('⚠️ L\'apport calorique minimum est de 1000 kcal/jour.');
                calEl.focus();
                return false;
            }
            if (parseFloat(cal) > 10000) {
                alert('⚠️ L\'apport calorique ne peut pas dépasser 10 000 kcal/jour.');
                calEl.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>