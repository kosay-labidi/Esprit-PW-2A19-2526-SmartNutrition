<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier Médical</title>
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
        input.error {
            border-color: #dc3545;
            background: #ffe0e0;
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
        .error-msg {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Modifier Dossier Médical</h1>
            <p>Mettez à jour les informations médicales du patient</p>
        </div>

        <div class="card">
            <?php
            require_once '../../../config.php';
            require_once '../../../controller/dossierMedical.controller.php';
            require_once '../../../Model/DossierMedical.php';

            $ctrl = new DossierMedicalController();
            $id = $_GET['id'] ?? null;
            $dossier = $id ? $ctrl->show($id) : null;
            $error = '';
            $success = '';

            if (!$dossier) {
                $error = "Dossier non trouvé.";
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
                $groupe_sanguin = $_POST['groupe_sanguin'] ?? '';
                $poids = $_POST['poids'] ?? '';
                $taille = $_POST['taille'] ?? '';
                $contact = $_POST['contact_en_cas_durgence'] ?? '';

                // Only groupe_sanguin, poids, taille are required — JS handles the rest
                if (empty($groupe_sanguin) || empty($poids) || empty($taille)) {
                    $error = "❌ Le groupe sanguin, le poids et la taille sont obligatoires";
                } elseif (floatval($poids) <= 0 || floatval($taille) <= 0) {
                    $error = "❌ Le poids et la taille doivent être supérieurs à 0";
                } else {
                    try {
                        $d = new DossierMedical(
                            $id,                                        // id_dossier
                            $dossier['id_utilisateur'],                 // id_utilisateur
                            $dossier['id_regime'] ?? null,              // id_regime
                            $dossier['date_creation'] ?? null,          // date_creation
                            null,                                       // date_mise_a_jour
                            $groupe_sanguin,                            // groupe_sanguin
                            floatval($poids),                           // poids
                            floatval($taille),                          // taille
                            null,                                       // imc
                            $_POST['regime_special'] ?? null,           // regime_special
                            $_POST['notes_medecin'] ?? null,            // notes_medecin
                            $_POST['allergie'] ?? null,                 // allergie
                            $_POST['gravite_allergie'] ?? null,         // gravite_allergie
                            $_POST['maladies'] ?? null,                 // maladies
                            $_POST['traitement'] ?? null,               // traitement
                            $_POST['medecin'] ?? null,                  // medecin
                            $contact                                    // contact_en_cas_durgence
                        );
                        $ctrl->update($d, $id);
                        $success = "✅ Dossier médical mis à jour avec succès!";
                        $_POST = [];
                        $dossier = $ctrl->show($id);
                    } catch (Exception $e) {
                        $error = "❌ Erreur lors de la mise à jour : " . $e->getMessage();
                    }
                }
            }
            ?>

            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <?php if ($dossier): ?>
            <form method="POST" onsubmit="return validateForm()">
                <!-- SECTION: Informations Biométriques -->
                <div class="form-section">
                    <div class="section-title">⚖️ Informations Biométriques</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="groupe">Groupe Sanguin <span class="required">*</span></label>
                            <select name="groupe_sanguin" id="groupe">
                                <option value="">Sélectionner...</option>
                                <option value="O+" <?php echo ($dossier['groupe_sanguin'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($dossier['groupe_sanguin'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                                <option value="A+" <?php echo ($dossier['groupe_sanguin'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($dossier['groupe_sanguin'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($dossier['groupe_sanguin'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($dossier['groupe_sanguin'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($dossier['groupe_sanguin'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($dossier['groupe_sanguin'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="poids">Poids (kg) <span class="required">*</span></label>
                            <input type="number" id="poids" name="poids" step="0.1" min="0" value="<?php echo $dossier['poids']; ?>" onchange="calculateIMC()">
                        </div>
                        <div class="form-group">
                            <label for="taille">Taille (cm) <span class="required">*</span></label>
                            <input type="number" id="taille" name="taille" step="0.1" min="0" value="<?php echo $dossier['taille']; ?>" onchange="calculateIMC()">
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
                            <input type="text" id="regime" name="regime_special" placeholder="ex. Végétarien, Sans gluten..." value="<?php echo htmlspecialchars($dossier['regime_special'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- SECTION: Allergies -->
                <div class="form-section">
                    <div class="section-title">⚠️ Allergies</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="allergie">Description des Allergies</label>
                            <textarea id="allergie" name="allergie" placeholder="Décrivez les allergies (ex: Arachides, lactose, pollen...)"><?php echo htmlspecialchars($dossier['allergie'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="gravite">Gravité</label>
                            <select name="gravite_allergie" id="gravite">
                                <option value="">Non spécifiée</option>
                                <option value="légère" <?php echo ($dossier['gravite_allergie'] === 'légère') ? 'selected' : ''; ?>>Légère</option>
                                <option value="modérée" <?php echo ($dossier['gravite_allergie'] === 'modérée') ? 'selected' : ''; ?>>Modérée</option>
                                <option value="sévère" <?php echo ($dossier['gravite_allergie'] === 'sévère') ? 'selected' : ''; ?>>Sévère</option>
                                <option value="anaphylactique" <?php echo ($dossier['gravite_allergie'] === 'anaphylactique') ? 'selected' : ''; ?>>Anaphylactique</option>
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
                            <textarea id="maladies" name="maladies" placeholder="ex. Diabète type 2, Hypertension, Asthme..."><?php echo htmlspecialchars($dossier['maladies'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="traitement">Traitement Actuel</label>
                            <textarea id="traitement" name="traitement" placeholder="Médicaments et dosages actuels" style="min-height: 100px;"><?php echo htmlspecialchars($dossier['traitement'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Contacts -->
                <div class="form-section">
                    <div class="section-title">📞 Informations de Contact</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="medecin">Médecin Référent</label>
                            <input type="text" id="medecin" name="medecin" placeholder="Dr. Nom Prénom" value="<?php echo htmlspecialchars($dossier['medecin'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact">Contact Urgence <span class="required">*</span></label>
                            <input type="tel" id="contact" name="contact_en_cas_durgence" placeholder="+33 6 12 34 56 78" value="<?php echo htmlspecialchars($dossier['contact_en_cas_durgence'] ?? ''); ?>">
                            <div class="error-msg" id="contactError">Format requis: +XX XXXX XXXX</div>
                        </div>
                    </div>
                </div>

                <!-- SECTION: Notes -->
                <div class="form-section">
                    <div class="section-title">📝 Notes Supplémentaires</div>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="notes">Notes du Médecin</label>
                            <textarea id="notes" name="notes_medecin" placeholder="Observations et recommandations supplémentaires..."><?php echo htmlspecialchars($dossier['notes_medecin'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
                    <a href="/crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html" class="btn btn-secondary">← Retour</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-error">❌ Dossier non trouvé</div>
                <a href="/crud/Esprit-PW-2A19-2526-SmartNutrition/view/backend/admin.html" class="btn btn-secondary" style="margin-top: 20px;">← Retour au tableau de bord</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function calculateIMC() {
            const poids = parseFloat(document.getElementById('poids').value);
            const taille = parseFloat(document.getElementById('taille').value);
            if (poids > 0 && taille > 0) {
                const imcResult = document.getElementById('imcResult');
                const imc = (poids / ((taille / 100) ** 2)).toFixed(1);
                let category = imc < 18.5 ? 'Insuffisance pondérale' : imc < 25 ? 'Poids normal' : imc < 30 ? 'Surpoids' : 'Obésité';
                imcResult.innerHTML = `<strong>IMC: ${imc}</strong> — ${category}`;
                imcResult.classList.add('show');
            }
        }

        function validateForm() {
            const groupe  = document.getElementById('groupe').value;
            const poids   = parseFloat(document.getElementById('poids').value);
            const taille  = parseFloat(document.getElementById('taille').value);
            const contact = document.getElementById('contact').value.trim();
            const contactInput  = document.getElementById('contact');
            const contactError  = document.getElementById('contactError');

            contactInput.classList.remove('error');
            contactError.style.display = 'none';

            if (!groupe) { alert('⚠️ Le groupe sanguin est obligatoire.'); return false; }
            if (!poids || poids < 10 || poids > 500) {
                alert('⚠️ Le poids doit être compris entre 10 et 500 kg.');
                return false;
            }
            if (!taille || taille < 50 || taille > 250) {
                alert('⚠️ La taille doit être comprise entre 50 et 250 cm.');
                return false;
            }
            if (contact && !/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/.test(contact)) {
                contactInput.classList.add('error');
                contactError.style.display = 'block';
                return false;
            }
            return true;
        }

        window.addEventListener('load', calculateIMC);
    </script>
</body>
</html>