<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl       = new DossierMedicalController();
$regimeCtrl = new RegimeController();
$id         = isset($_GET['id']) ? intval($_GET['id']) : null;
$dossier    = $id ? $ctrl->show($id) : null;
$error      = '';
$success    = '';
$allRegimes = [];

try {
    $allRegimes = $regimeCtrl->list();
} catch (Exception $e) {
    $allRegimes = [];
}

if (!$dossier) {
    $error = "Dossier non trouvé.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dossier) {
    /* ── Server-side validation (mirrors JS – never rely on HTML5) ── */
    $groupe_sanguin = trim($_POST['groupe_sanguin'] ?? '');
    $poids          = $_POST['poids']  ?? '';
    $taille         = $_POST['taille'] ?? '';
    $contact        = trim($_POST['contact_en_cas_durgence'] ?? '');
    $id_regime_raw  = $_POST['id_regime'] ?? '';

    $validGS = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];

    if (empty($groupe_sanguin)) {
        $error = "Le groupe sanguin est obligatoire.";
    } elseif (!in_array($groupe_sanguin, $validGS)) {
        $error = "Groupe sanguin invalide.";
    } elseif ($poids === '' || floatval($poids) <= 0) {
        $error = "Le poids doit être supérieur à 0.";
    } elseif (floatval($poids) < 20 || floatval($poids) > 500) {
        $error = "Le poids doit être compris entre 20 et 500 kg.";
    } elseif ($taille === '' || floatval($taille) <= 0) {
        $error = "La taille doit être supérieure à 0.";
    } elseif (floatval($taille) < 50 || floatval($taille) > 250) {
        $error = "La taille doit être comprise entre 50 et 250 cm.";
    } elseif (empty($contact)) {
        $error = "Le contact d'urgence est obligatoire.";
    } elseif (!preg_match('/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/', $contact)) {
        $error = "Format du contact : +XX XXXX XXXX";
    } elseif (!empty($id_regime_raw) && (!is_numeric($id_regime_raw) || intval($id_regime_raw) <= 0)) {
        $error = "L'ID du régime doit être un entier positif.";
    } else {
        try {
            $id_regime_val = (!empty($id_regime_raw) && is_numeric($id_regime_raw))
                ? intval($id_regime_raw) : null;

            $d = new DossierMedical(
                $id,
                $dossier['id_utilisateur'],
                $id_regime_val,
                $dossier['date_creation'],
                null,
                $groupe_sanguin,
                floatval($poids),
                floatval($taille),
                null,
                $_POST['regime_special']          ?? null,
                $_POST['notes_medecin']           ?? null,
                $_POST['allergie']                ?? null,
                $_POST['gravite_allergie']        ?? null,
                $_POST['maladies']                ?? null,
                $_POST['traitement']              ?? null,
                $_POST['medecin']                 ?? null,
                $contact
            );

            $ctrl->update($d, $id);
            $success = "Dossier médical mis à jour avec succès !";
            $_POST   = [];
            $dossier = $ctrl->show($id); // refresh
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier Médical – Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body { display:block; padding:40px 20px; min-height:100vh; }
        .ud-container { max-width:880px; margin:0 auto; }

        .ud-header { text-align:center; margin-bottom:36px; }
        .ud-header h1 { font-size:clamp(1.8rem,3.5vw,2.4rem); color:var(--text); letter-spacing:.05em; }
        .ud-header p  { color:var(--muted); margin-top:8px; font-size:1rem; }

        .ud-card { background:var(--card-bg); backdrop-filter:blur(16px); border:1px solid rgba(91,62,150,.2); border-radius:var(--radius); padding:36px; margin-bottom:28px; }

        .ud-section-title { font-size:1.05rem; font-weight:700; color:var(--text); margin-bottom:18px; padding-bottom:10px; border-bottom:1px solid rgba(91,62,150,.25); display:flex; align-items:center; gap:10px; letter-spacing:.04em; }

        .ud-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .ud-grid.full { grid-template-columns:1fr; }
        .ud-group { display:flex; flex-direction:column; }

        .ud-label { font-weight:600; margin-bottom:7px; color:var(--text); font-size:.9rem; }
        .ud-label .req { color:var(--danger); }

        .ud-input, .ud-select, .ud-textarea {
            padding:11px 15px;
            background:rgba(31,61,43,.3);
            border:1.5px solid rgba(91,62,150,.3);
            border-radius:10px;
            font-size:.96rem;
            color:var(--text);
            font-family:'Lato',sans-serif;
            transition:all var(--tr);
            width:100%;
            box-sizing:border-box;
        }
        .ud-input::placeholder { color:rgba(242,232,207,.4); }
        .ud-input:focus, .ud-select:focus, .ud-textarea:focus {
            outline:none; border-color:var(--violet);
            background:rgba(31,61,43,.5); box-shadow:0 0 0 3px rgba(91,62,150,.15);
        }
        .ud-input.error, .ud-select.error { border-color:var(--danger); background:rgba(231,76,60,.07); }
        .ud-textarea { resize:vertical; min-height:90px; }
        .ud-field-err { color:var(--danger); font-size:.82rem; margin-top:4px; display:none; }

        .ud-imc-box { background:rgba(91,62,150,.1); border:1px solid rgba(91,62,150,.25); border-radius:10px; padding:12px; text-align:center; font-weight:700; color:var(--text); margin-top:8px; display:none; }
        .ud-imc-box.show { display:block; }

        .ud-alert { padding:14px 18px; border-radius:12px; margin-bottom:22px; font-weight:500; display:flex; align-items:center; gap:10px; }
        .ud-alert-success { background:rgba(46,204,113,.12); color:var(--success); border:1px solid rgba(46,204,113,.3); }
        .ud-alert-error   { background:rgba(231,76,60,.12);  color:var(--danger);  border:1px solid rgba(231,76,60,.3); }

        .ud-regime-block { background:rgba(91,62,150,.07); border:1px solid rgba(91,62,150,.2); border-radius:12px; padding:18px; }
        .ud-regime-current { font-size:.9rem; color:var(--muted); margin-top:10px; }
        .ud-regime-current strong { color:var(--text); }

        .ud-btn-group { display:flex; gap:14px; margin-top:36px; flex-wrap:wrap; }
        .ud-btn { padding:13px 28px; border:none; border-radius:50px; font-size:.96rem; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all var(--tr); letter-spacing:.03em; }
        .ud-btn-primary   { background:linear-gradient(135deg,var(--violet),var(--blue)); color:white; box-shadow:0 4px 16px rgba(91,62,150,.3); }
        .ud-btn-primary:hover   { transform:translateY(-2px); box-shadow:0 6px 24px rgba(91,62,150,.4); }
        .ud-btn-secondary { background:var(--glass); color:var(--text); border:1.5px solid rgba(91,62,150,.4); backdrop-filter:blur(10px); }
        .ud-btn-secondary:hover { background:rgba(91,62,150,.15); border-color:var(--violet); transform:translateY(-2px); }

        @media(max-width:640px) {
            body { padding:20px 12px; }
            .ud-grid { grid-template-columns:1fr; }
            .ud-card { padding:22px; }
        }
    </style>
</head>
<body>
<div class="ud-container">

    <div class="ud-header">
        <h1>✏️ Modifier Dossier Médical</h1>
        <p>Dossier #<?= htmlspecialchars((string)($id ?? '?')) ?> – mettez à jour les informations médicales</p>
    </div>

    <div class="ud-card">

        <?php if ($error):   ?><div class="ud-alert ud-alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="ud-alert ud-alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if ($dossier): ?>
        <!-- ── All HTML5 validation attributes intentionally removed; JS handles everything ── -->
        <form id="updateDossierForm" method="POST" novalidate>

            <!-- Biométrie -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">⚖️ Informations Biométriques</div>
                <div class="ud-grid">
                    <div class="ud-group">
                        <label class="ud-label">Groupe Sanguin <span class="req">*</span></label>
                        <div class="ud-field-err" id="err-groupe"></div>
                        <select name="groupe_sanguin" id="ud-groupe" class="ud-select">
                            <option value="">Sélectionner…</option>
                            <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $gs): ?>
                            <option value="<?= $gs ?>" <?= ($dossier['groupe_sanguin'] === $gs) ? 'selected' : '' ?>><?= $gs ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ud-group">
                        <label class="ud-label">Poids (kg) <span class="req">*</span></label>
                        <div class="ud-field-err" id="err-poids"></div>
                        <input type="number" id="ud-poids" name="poids" step="0.1"
                               class="ud-input"
                               value="<?= htmlspecialchars((string)($dossier['poids'] ?? '')) ?>">
                    </div>
                    <div class="ud-group">
                        <label class="ud-label">Taille (cm) <span class="req">*</span></label>
                        <div class="ud-field-err" id="err-taille"></div>
                        <input type="number" id="ud-taille" name="taille" step="0.1"
                               class="ud-input"
                               value="<?= htmlspecialchars((string)($dossier['taille'] ?? '')) ?>">
                    </div>
                    <div>
                        <div class="ud-imc-box" id="ud-imc-box"></div>
                    </div>
                </div>
            </div>

            <!-- Régime associé -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">🥗 Régime Alimentaire Associé</div>
                <div class="ud-regime-block">

                    <!-- Dropdown from existing regimes -->
                    <div class="ud-group" style="margin-bottom:14px;">
                        <label class="ud-label">Sélectionner un régime existant</label>
                        <select name="id_regime" id="ud-regime-select" class="ud-select">
                            <option value="">— Aucun régime associé —</option>
                            <?php foreach ($allRegimes as $r): ?>
                            <option value="<?= (int)$r['id_regime'] ?>"
                                <?= (!empty($dossier['id_regime']) && $dossier['id_regime'] == $r['id_regime']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nom_regime']) ?> (<?= htmlspecialchars($r['type_regime']) ?>) — ID <?= (int)$r['id_regime'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Direct ID input (has priority over dropdown) -->
                    <div class="ud-group">
                        <label class="ud-label">Ou saisir l'ID du régime directement</label>
                        <div class="ud-field-err" id="err-regime-id"></div>
                        <input type="number" id="ud-regime-id-input" class="ud-input"
                               placeholder="ex. 3  (prioritaire sur la liste ci-dessus)"
                               value="<?= (!empty($dossier['id_regime'])) ? htmlspecialchars((string)$dossier['id_regime']) : '' ?>">
                        <small style="color:var(--muted);font-size:.8rem;margin-top:5px;">
                            Si ce champ est rempli, il a priorité sur la liste déroulante.
                        </small>
                    </div>

                    <?php if (!empty($dossier['id_regime'])): ?>
                    <div class="ud-regime-current" style="margin-top:12px;">
                        Régime actuellement associé : <strong>ID <?= (int)$dossier['id_regime'] ?></strong>
                        <?php foreach ($allRegimes as $r): if ($r['id_regime'] == $dossier['id_regime']): ?>
                        – <?= htmlspecialchars($r['nom_regime']) ?> (<?= htmlspecialchars($r['type_regime']) ?>)
                        <?php endif; endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Régime spécial texte -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">📝 Régime Spécial (texte libre)</div>
                <div class="ud-group">
                    <label class="ud-label">Régime Spécial</label>
                    <input type="text" name="regime_special" class="ud-input"
                           placeholder="ex. Végétarien, Sans gluten…"
                           value="<?= htmlspecialchars($dossier['regime_special'] ?? '') ?>">
                </div>
            </div>

            <!-- Allergies -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">⚠️ Allergies</div>
                <div class="ud-grid full">
                    <div class="ud-group" style="margin-bottom:14px;">
                        <label class="ud-label">Description des Allergies</label>
                        <textarea name="allergie" class="ud-textarea"
                                  placeholder="ex. Arachides, lactose, pollen…"><?= htmlspecialchars($dossier['allergie'] ?? '') ?></textarea>
                    </div>
                    <div class="ud-group">
                        <label class="ud-label">Gravité</label>
                        <select name="gravite_allergie" class="ud-select">
                            <option value="">Non spécifiée</option>
                            <?php foreach (['légère','modérée','sévère','anaphylactique'] as $gv): ?>
                            <option value="<?= $gv ?>" <?= ($dossier['gravite_allergie'] === $gv) ? 'selected' : '' ?>><?= ucfirst($gv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Conditions médicales -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">🏥 Conditions Médicales</div>
                <div class="ud-grid full">
                    <div class="ud-group" style="margin-bottom:14px;">
                        <label class="ud-label">Maladies Chroniques</label>
                        <textarea name="maladies" class="ud-textarea"
                                  placeholder="ex. Diabète type 2, Hypertension, Asthme…"><?= htmlspecialchars($dossier['maladies'] ?? '') ?></textarea>
                    </div>
                    <div class="ud-group">
                        <label class="ud-label">Traitement Actuel</label>
                        <textarea name="traitement" class="ud-textarea"
                                  placeholder="Médicaments et dosages actuels…"><?= htmlspecialchars($dossier['traitement'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contacts -->
            <div style="margin-bottom:30px;">
                <div class="ud-section-title">📞 Informations de Contact</div>
                <div class="ud-grid">
                    <div class="ud-group">
                        <label class="ud-label">Médecin Référent</label>
                        <input type="text" name="medecin" class="ud-input"
                               placeholder="Dr. Nom Prénom"
                               value="<?= htmlspecialchars($dossier['medecin'] ?? '') ?>">
                    </div>
                    <div class="ud-group">
                        <label class="ud-label">Contact Urgence <span class="req">*</span></label>
                        <div class="ud-field-err" id="err-contact"></div>
                        <input type="tel" id="ud-contact" name="contact_en_cas_durgence" class="ud-input"
                               placeholder="+216 7000 0000"
                               value="<?= htmlspecialchars($dossier['contact_en_cas_durgence'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div style="margin-bottom:10px;">
                <div class="ud-section-title">📝 Notes Supplémentaires</div>
                <div class="ud-group">
                    <label class="ud-label">Notes du Médecin</label>
                    <textarea name="notes_medecin" class="ud-textarea"
                              placeholder="Observations et recommandations…"><?= htmlspecialchars($dossier['notes_medecin'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="ud-btn-group">
                <button type="button" class="ud-btn ud-btn-primary" onclick="udValidateAndSubmit()">💾 Enregistrer les modifications</button>
                <a href="../modules/health-admin.html" class="ud-btn ud-btn-secondary">← Retour</a>
            </div>
        </form>

        <?php else: ?>
            <div class="ud-alert ud-alert-error">❌ Dossier non trouvé (ID invalide).</div>
            <a href="../modules/health-admin.html" class="ud-btn ud-btn-secondary" style="margin-top:16px;display:inline-flex;">← Retour</a>
        <?php endif; ?>
    </div>
</div>

<!-- ── JavaScript Validation — NO HTML5 required/pattern attributes ── -->
<script>
(function () {
    'use strict';

    var VALID_GS = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];

    /* Helpers */
    function showErr(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent    = msg;
        el.style.display  = msg ? 'block' : 'none';
    }
    function clearAll() {
        ['err-groupe','err-poids','err-taille','err-contact','err-regime-id']
            .forEach(function (id) { showErr(id, ''); });
        ['ud-groupe','ud-poids','ud-taille','ud-contact']
            .forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('error');
            });
    }
    function markErr(fieldId, errId, msg) {
        showErr(errId, msg);
        var el = document.getElementById(fieldId);
        if (el) el.classList.add('error');
    }

    /* IMC auto-calc */
    function calcIMC() {
        var p   = parseFloat(document.getElementById('ud-poids').value);
        var t   = parseFloat(document.getElementById('ud-taille').value);
        var box = document.getElementById('ud-imc-box');
        if (!box) return;
        if (p > 0 && t > 0) {
            var imc = (p / Math.pow(t / 100, 2)).toFixed(1);
            var cat = imc < 18.5 ? 'Insuffisance pondérale' : imc < 25 ? 'Poids normal' : imc < 30 ? 'Surpoids' : 'Obésité';
            box.textContent = 'IMC calculé : ' + imc + ' – ' + cat;
            box.classList.add('show');
        } else {
            box.classList.remove('show');
        }
    }

    var poidsEl  = document.getElementById('ud-poids');
    var tailleEl = document.getElementById('ud-taille');
    if (poidsEl)  poidsEl.addEventListener('input', calcIMC);
    if (tailleEl) tailleEl.addEventListener('input', calcIMC);
    window.addEventListener('load', calcIMC);

    /* Sync ID input ↔ select */
    var idInput  = document.getElementById('ud-regime-id-input');
    var idSelect = document.getElementById('ud-regime-select');
    if (idInput && idSelect) {
        idInput.addEventListener('input', function () {
            var val = this.value.trim();
            for (var i = 0; i < idSelect.options.length; i++) {
                if (idSelect.options[i].value === val) {
                    idSelect.selectedIndex = i;
                    return;
                }
            }
            if (!val) idSelect.value = '';
        });
        idSelect.addEventListener('change', function () {
            if (idInput) idInput.value = this.value || '';
        });
    }

    /* Validate & submit */
    function udValidateAndSubmit() {
        clearAll();
        var valid = true;

        var groupe  = document.getElementById('ud-groupe');
        var poids   = document.getElementById('ud-poids');
        var taille  = document.getElementById('ud-taille');
        var contact = document.getElementById('ud-contact');
        var regimeIdRaw = idInput ? idInput.value.trim() : '';

        // Groupe sanguin
        if (!groupe || !groupe.value) {
            markErr('ud-groupe', 'err-groupe', 'Groupe sanguin requis.');
            valid = false;
        } else if (VALID_GS.indexOf(groupe.value) === -1) {
            markErr('ud-groupe', 'err-groupe', 'Groupe sanguin invalide.');
            valid = false;
        }

        // Poids
        var poidsVal = parseFloat(poids ? poids.value : '');
        if (!poids || !poids.value || isNaN(poidsVal) || poidsVal <= 0) {
            markErr('ud-poids', 'err-poids', 'Poids invalide (doit être > 0).');
            valid = false;
        } else if (poidsVal < 20 || poidsVal > 500) {
            markErr('ud-poids', 'err-poids', 'Poids entre 20 et 500 kg.');
            valid = false;
        }

        // Taille
        var tailleVal = parseFloat(taille ? taille.value : '');
        if (!taille || !taille.value || isNaN(tailleVal) || tailleVal <= 0) {
            markErr('ud-taille', 'err-taille', 'Taille invalide (doit être > 0).');
            valid = false;
        } else if (tailleVal < 50 || tailleVal > 250) {
            markErr('ud-taille', 'err-taille', 'Taille entre 50 et 250 cm.');
            valid = false;
        }

        // Contact urgence
        var ctv = contact ? contact.value.trim() : '';
        if (!ctv) {
            markErr('ud-contact', 'err-contact', 'Contact urgence requis.');
            valid = false;
        } else if (!/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/.test(ctv)) {
            markErr('ud-contact', 'err-contact', 'Format : +XX XXXX XXXX');
            valid = false;
        }

        // Regime ID (optional but must be integer > 0 if provided)
        if (regimeIdRaw !== '' && (isNaN(parseInt(regimeIdRaw)) || parseInt(regimeIdRaw) <= 0)) {
            showErr('err-regime-id', 'ID régime invalide (entier positif attendu).');
            valid = false;
        }

        if (!valid) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        /* Resolve final id_regime: direct input has priority over select */
        if (idInput && idSelect) {
            var resolved = regimeIdRaw !== '' ? regimeIdRaw : (idSelect.value || '');
            idSelect.value = resolved;
            idSelect.name  = 'id_regime';
            if (idInput) idInput.name = '';  // exclude from form submission
        }

        document.getElementById('updateDossierForm').submit();
    }

    window.udValidateAndSubmit = udValidateAndSubmit;
})();
</script>
</body>
</html>
