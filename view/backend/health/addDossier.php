<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl = new DossierMedicalController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $d = new DossierMedical(
            null, 1, null, null,
            $_POST['groupe_sanguin'] ?? null,
            floatval($_POST['poids'] ?? 0),
            floatval($_POST['taille'] ?? 0),
            null,
            $_POST['regime_special'] ?? null,
            $_POST['notes_medecin'] ?? null,
            $_POST['allergie'] ?? null,
            $_POST['gravite_allergie'] ?? null,
            $_POST['maladies'] ?? null,
            $_POST['traitement'] ?? null,
            $_POST['medecin'] ?? null,
            $_POST['contact_en_cas_durgence'] ?? null
        );
        $ctrl->add($d);
        $success = "Dossier médical ajouté avec succès!";
        header('Refresh: 2; url=../modules/health-admin.html');
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Dossier Médical</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
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
        .info-box {
            background: #f0f8ff;
            border-left: 4px solid var(--bleu);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #004085;
        }
        .imc-result {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            color: var(--vert);
            margin-top: 10px;
            display: none;
        }
        .imc-result.show { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Nouveau Dossier Médical</h1>
            <p>Enregistrez les informations médicales du patient</p>
        </div>

        <div class="card">
            <?php
            require_once '../../../config.php';
            require_once '../../../controller/dossierMedical.controller.php';
            require_once '../../../Model/DossierMedical.php';

            $ctrl = new DossierMedicalController();
            $error = '';
            $success = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get required fields
                $groupe_sanguin = $_POST['groupe_sanguin'] ?? '';
                $poids = $_POST['poids'] ?? '';
                $taille = $_POST['taille'] ?? '';
                $contact = $_POST['contact_en_cas_durgence'] ?? '';

                // Validate required fields
                if (empty($groupe_sanguin) || empty($poids) || empty($taille) || empty($contact)) {
                    $error = "❌ Tous les champs marqués d'une * sont obligatoires";
                } elseif (floatval($poids) <= 0 || floatval($taille) <= 0) {
                    $error = "❌ Le poids et la taille doivent être supérieurs à 0";
                } elseif (!preg_match('/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/', $contact)) {
                    $error = "❌ Format de téléphone requis: +XX XXXX XXXX";
                } else {
                    try {
                        $d = new DossierMedical(
                            null, 1, null, null,
                            $groupe_sanguin,
                            floatval($poids),
                            floatval($taille),
                            null,
                            $_POST['regime_special'] ?? null,
                            $_POST['notes_medecin'] ?? null,
                            $_POST['allergie'] ?? null,
                            $_POST['gravite_allergie'] ?? null,
                            $_POST['maladies'] ?? null,
                            $_POST['traitement'] ?? null,
                            $_POST['medecin'] ?? null,
                            $contact
                        );
                        $ctrl->add($d);
                        $success = "✅ Dossier médical enregistré avec succès!";
                        echo "<meta http-equiv='refresh' content='2; url=../modules/health-admin.html'>";
                    } catch (Exception $e) {
                        $error = "❌ " . htmlspecialchars($e->getMessage());
                    }
                }
            }
            ?>

            <?php if ($error): ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <!-- SECTION: Informations Biométriques -->
                <div class="form-section">
                    <div class="section-title">⚖️ Informations Biométriques</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="groupe">Groupe Sanguin *</label>
                            <select name="groupe_sanguin" id="groupe" required>
                                <option value="">Sélectionner...</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="poids">Poids (kg) *</label>
                            <input type="number" id="poids" name="poids" step="0.1" min="0" required onchange="calculateIMC()">
                        </div>
                        <div class="form-group">
                            <label for="taille">Taille (cm) *</label>
                            <input type="number" id="taille" name="taille" step="0.1" min="0" required onchange="calculateIMC()">
                        </div>
                        <div class="imc-result" id="imcResult"></div>
                    </div>
                </div>

                <!-- SECTION: Régime -->
                <div class="form-section">
                    <div class="section-title">🍽️ Régime Alimentaire</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="regime">Régime Spécial</label>
                            <input type="text" id="regime" name="regime_special" placeholder="ex. Végétarien, Sans gluten...">
                        </div>
                    </div>
                </div>

                <!-- SECTION: Allergies -->
                <div class="form-section">
                    <div class="section-title">⚠️ Allergies</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="allergie">Description des Allergies</label>
                            <textarea id="allergie" name="allergie" placeholder="Décrivez les allergies (ex: Arachides, lactose, pollen...)"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="gravite">Gravité</label>
                            <select name="gravite_allergie" id="gravite">
                                <option value="">Non spécifiée</option>
                                <option value="légère">Légère</option>
                                <option value="modérée">Modérée</option>
                                <option value="sévère">Sévère</option>
                                <option value="anaphylactique">Anaphylactique</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Conditions Médicales -->
                <div class="form-section">
                    <div class="section-title">🏥 Conditions Médicales</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="maladies">Maladies Chroniques</label>
                            <textarea id="maladies" name="maladies" placeholder="ex. Diabète type 2, Hypertension, Asthme..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="traitement">Traitement Actuel</label>
                            <textarea id="traitement" name="traitement" placeholder="Médicaments et dosages actuels" style="min-height: 100px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Contacts -->
                <div class="form-section">
                    <div class="section-title">📞 Informations de Contact</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="medecin">Médecin Référent</label>
                            <input type="text" id="medecin" name="medecin" placeholder="Dr. Nom Prénom">
                        </div>
                        <div class="form-group">
                            <label for="contact">Contact Urgence</label>
                            <input type="tel" id="contact" name="contact_en_cas_durgence" placeholder="+33 6 XX XX XX XX">
                        </div>
                    </div>
                </div>

                <!-- SECTION: Notes -->
                <div class="form-section">
                    <div class="section-title">📝 Notes Supplémentaires</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="notes">Notes du Médecin</label>
                            <textarea id="notes" name="notes_medecin" placeholder="Observations et recommandations supplémentaires..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer le dossier</button>
                    <a href="../modules/health-admin.html" class="btn btn-secondary">← Retour</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function calculateIMC() {
            const poids = parseFloat(document.getElementById('poids').value);
            const taille = parseFloat(document.getElementById('taille').value);
            
            if (poids > 0 && taille > 0) {
                const imcResult = document.getElementById('imcResult');
                const imc = (poids / ((taille / 100) ** 2)).toFixed(1);
                let category = '';
                
                if (imc < 18.5) category = 'Insuffisance pondérale';
                else if (imc < 25) category = 'Poids normal';
                else if (imc < 30) category = 'Surpoids';
                else category = 'Obésité';
                
                imcResult.innerHTML = `<strong>IMC: ${imc}</strong> - ${category}`;
                imcResult.classList.add('show');
            }
        }

        function validateForm() {
            const poids = parseFloat(document.getElementById('poids').value);
            const taille = parseFloat(document.getElementById('taille').value);
            
            if (poids <= 0) {
                alert('⚠️ Veuillez entrer un poids valide (> 0)');
                return false;
            }
            if (taille <= 0) {
                alert('⚠️ Veuillez entrer une taille valide (> 0)');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
        }
    </script>
</body>
</html>