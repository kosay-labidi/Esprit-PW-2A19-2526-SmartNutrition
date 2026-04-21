<?php
require_once '../../../config.php';
require_once '../../../controller/dossierMedical.controller.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../Model/DossierMedical.php';

$ctrl       = new DossierMedicalController();
$regimeCtrl = new RegimeController();
$error      = '';
$success    = '';
$allRegimes = [];

try {
    $allRegimes = $regimeCtrl->list();
} catch (Exception $e) {
    $allRegimes = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ── Server-side validation (mirrors JS; never rely on HTML5) ── */
    $id_utilisateur = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    $groupe        = trim($_POST['groupe_sanguin']          ?? '');
    $poids         = $_POST['poids']                         ?? '';
    $taille        = $_POST['taille']                        ?? '';
    $contact       = trim($_POST['contact_en_cas_durgence']  ?? '');
    $id_regime_raw = $_POST['id_regime']                     ?? '';

    $validGS = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];

    if ($poids === '' || floatval($poids) <= 0) {
        $error = "Le poids doit être supérieur à 0.";
    } elseif (floatval($poids) < 20 || floatval($poids) > 500) {
        $error = "Le poids doit être compris entre 20 et 500 kg.";
    } elseif ($taille === '' || floatval($taille) <= 0) {
        $error = "La taille doit être supérieure à 0.";
    } elseif (floatval($taille) < 50 || floatval($taille) > 250) {
        $error = "La taille doit être comprise entre 50 et 250 cm.";
    } elseif (!empty($groupe) && !in_array($groupe, $validGS)) {
        $error = "Groupe sanguin invalide.";
    } elseif (!empty($contact) && !preg_match('/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/', $contact)) {
        $error = "Format du contact : +XX XXXX XXXX";
    } elseif (!empty($id_regime_raw) && (!is_numeric($id_regime_raw) || intval($id_regime_raw) <= 0)) {
        $error = "L'ID du régime doit être un entier positif.";
    } else {
        try {
            $id_regime_val = (!empty($id_regime_raw) && is_numeric($id_regime_raw))
                ? intval($id_regime_raw) : null;

            $dossier = new DossierMedical(
                null,
                $id_utilisateur,
                $id_regime_val,
                null,
                null,
                $groupe ?: null,
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
                $contact ?: null
            );

            $ctrl->add($dossier);
            $success = "Dossier médical enregistré avec succès !";
            // Redirect back to health page after 2 seconds
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
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
        :root { --vert:#013220; --sable:#CBBD93; --violet:#BA5BED; --bleu:#77B5FE; }
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',Tahoma,sans-serif; background:var(--sable); color:var(--vert); min-height:100vh; padding:40px 20px; }

        .glass { background:rgba(255,255,255,.97); border-radius:22px; padding:40px; max-width:920px; margin:0 auto; box-shadow:0 15px 50px rgba(1,50,32,.18); }

        h1 { color:var(--vert); margin-bottom:8px; font-size:clamp(1.6rem,3vw,2.2rem); }
        .subtitle { color:#666; margin-bottom:28px; font-size:.97rem; }

        .alert { padding:14px 18px; border-radius:12px; margin-bottom:22px; font-weight:500; }
        .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
        .alert-error   { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }

        .section-title { font-size:1.05rem; font-weight:700; color:var(--vert); margin-bottom:16px; margin-top:24px; padding-bottom:8px; border-bottom:2px solid rgba(119,181,254,.5); display:flex; align-items:center; gap:8px; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-grid.single { grid-template-columns:1fr; }
        .form-group { display:flex; flex-direction:column; }

        label { font-weight:600; margin-bottom:6px; color:var(--vert); font-size:.92rem; }
        label .req { color:#dc3545; }
        .field-err { color:#dc3545; font-size:.82rem; margin-top:3px; display:none; }

        /* No type="number" min/max/required HTML5 constraints — JS only */
        input[type="text"],
        input[type="number"],
        input[type="tel"],
        select,
        textarea {
            padding:11px 15px; border:2px solid #dce3ea; border-radius:11px;
            font-size:.97rem; font-family:inherit; transition:border .25s,box-shadow .25s;
            width:100%; color:var(--vert);
        }
        input:focus, select:focus, textarea:focus { border-color:var(--bleu); outline:none; box-shadow:0 0 0 3px rgba(119,181,254,.18); }
        input.error, select.error { border-color:#dc3545; background:#fff5f5; }
        textarea { resize:vertical; min-height:90px; }

        .regime-box { background:rgba(119,181,254,.08); border:1.5px solid rgba(119,181,254,.4); border-radius:14px; padding:20px; }
        .or-divider { text-align:center; color:#999; margin:10px 0; font-size:.88rem; }

        .imc-preview { background:rgba(1,50,32,.07); border-radius:10px; padding:12px; text-align:center; font-weight:700; color:var(--vert); display:none; margin-top:8px; }
        .imc-preview.show { display:block; }

        .btn-row { display:flex; gap:14px; margin-top:32px; flex-wrap:wrap; }
        .btn { padding:13px 26px; border:none; border-radius:50px; font-size:.97rem; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .28s; }
        .btn-primary   { background:linear-gradient(135deg,var(--violet),#9d4dd4); color:white; box-shadow:0 4px 15px rgba(186,91,237,.3); }
        .btn-primary:hover   { transform:translateY(-2px); box-shadow:0 6px 22px rgba(186,91,237,.4); }
        .btn-secondary { background:var(--bleu); color:white; }
        .btn-secondary:hover { background:#4a9ee8; transform:translateY(-2px); }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9998; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:white; border-radius:20px; padding:36px; width:min(500px,95vw); max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.3); }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; border-bottom:2px solid rgba(1,50,32,.1); padding-bottom:14px; }
        .modal-title { font-size:1.5rem; color:var(--vert); margin:0; }
        .modal-close { background:none; border:none; font-size:24px; color:#999; cursor:pointer; transition:color .3s; }
        .modal-close:hover { color:var(--vert); }
        .modal-group { margin-bottom:16px; }
        .modal-label { display:block; margin-bottom:6px; font-weight:600; color:var(--vert); font-size:.92rem; }
        .modal-input,.modal-select,.modal-textarea { width:100%; padding:11px 14px; border:2px solid #dce3ea; border-radius:11px; font-size:.97rem; font-family:inherit; transition:border .25s; box-sizing:border-box; }
        .modal-input:focus,.modal-select:focus,.modal-textarea:focus { border-color:var(--bleu); outline:none; }
        .modal-input.error,.modal-select.error { border-color:#dc3545; background:#fff5f5; }
        .modal-textarea { resize:vertical; min-height:75px; }
        .modal-actions { display:flex; gap:12px; margin-top:22px; justify-content:flex-end; }
        .modal-btn-cancel { padding:10px 20px; border:none; border-radius:50px; background:#f0f0f0; color:var(--vert); font-weight:600; cursor:pointer; }
        .modal-btn-submit { padding:10px 20px; border:none; border-radius:50px; background:linear-gradient(135deg,var(--bleu),#4a9ee8); color:white; font-weight:600; cursor:pointer; transition:all .3s; }
        .modal-btn-submit:hover:not(:disabled) { transform:translateY(-2px); }
        .modal-btn-submit:disabled { opacity:.6; cursor:not-allowed; }

        /* Toast */
        .toast { position:fixed; bottom:22px; right:22px; padding:13px 20px; border-radius:12px; color:white; z-index:11000; font-weight:600; box-shadow:0 10px 30px rgba(0,0,0,.25); transform:translateY(70px); opacity:0; transition:all .35s; pointer-events:none; }
        .toast.show { transform:translateY(0); opacity:1; }

        @media(max-width:640px) {
            .form-grid { grid-template-columns:1fr; }
            .glass { padding:24px 18px; }
        }
    </style>
</head>
<body>

<div class="glass">
    <h1>➕ Ajouter un Dossier Médical</h1>
    <p class="subtitle">Remplissez les informations médicales. Vous pouvez associer un régime existant ou en créer un nouveau.</p>

    <?php if ($error):   ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <script>setTimeout(function(){ window.location.href='../../frontend/modules/health.html'; }, 2000);</script>
    <?php endif; ?>

    <!-- novalidate: all validation handled by JS -->
    <form id="addDossierForm" method="POST" novalidate>

        <!-- Biométrie -->
        <div class="section-title">⚖️ Informations Biométriques</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Groupe sanguin</label>
                <select name="groupe_sanguin" id="fd-groupe">
                    <option value="">— Sélectionner —</option>
                    <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $gs): ?>
                    <option value="<?= $gs ?>" <?= (($_POST['groupe_sanguin'] ?? '') === $gs) ? 'selected' : '' ?>><?= $gs ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Poids (kg) <span class="req">*</span></label>
                <div class="field-err" id="err-poids"></div>
                <input type="number" id="fd-poids" name="poids" step="0.1"
                       value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>" placeholder="ex. 70">
            </div>
            <div class="form-group">
                <label>Taille (cm) <span class="req">*</span></label>
                <div class="field-err" id="err-taille"></div>
                <input type="number" id="fd-taille" name="taille" step="0.1"
                       value="<?= htmlspecialchars($_POST['taille'] ?? '') ?>" placeholder="ex. 175">
            </div>
            <div>
                <div class="imc-preview" id="fd-imc-box"></div>
            </div>
        </div>

        <!-- Régime Associé -->
        <div class="section-title">🍽️ Régime Associé</div>
        <div class="regime-box">
            <div class="form-group" style="margin-bottom:12px;">
                <label>Sélectionner un régime existant</label>
                <select name="id_regime" id="fd-regime-select">
                    <option value="">— Choisir un régime —</option>
                    <?php foreach ($allRegimes as $r): ?>
                    <option value="<?= (int)$r['id_regime'] ?>"
                        <?= (isset($_POST['id_regime']) && $_POST['id_regime'] == $r['id_regime']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['nom_regime']) ?> (<?= htmlspecialchars($r['type_regime']) ?>) — ID <?= (int)$r['id_regime'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="or-divider">— ou —</p>
            <button type="button" class="btn btn-secondary" style="width:100%;border-radius:12px;" onclick="openCreateRegimeModal()">
                ➕ Créer un nouveau régime
            </button>
            <p style="color:#888;font-size:.82rem;margin-top:8px;text-align:center;">
                Après création, le nouveau régime sera automatiquement sélectionné.
            </p>
        </div>

        <!-- Allergies -->
        <div class="section-title">⚠️ Allergies</div>
        <div class="form-grid single">
            <div class="form-group" style="margin-bottom:14px;">
                <label>Description des Allergies</label>
                <textarea name="allergie" placeholder="ex. Arachides, lactose, pollen…"><?= htmlspecialchars($_POST['allergie'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Gravité</label>
                <select name="gravite_allergie">
                    <option value="">Non spécifiée</option>
                    <?php foreach (['légère','modérée','sévère','anaphylactique'] as $gv): ?>
                    <option value="<?= $gv ?>" <?= (($_POST['gravite_allergie'] ?? '') === $gv) ? 'selected' : '' ?>><?= ucfirst($gv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Maladies / Traitement -->
        <div class="section-title">🏥 Conditions Médicales</div>
        <div class="form-group" style="margin-bottom:14px;">
            <label>Maladies Chroniques</label>
            <textarea name="maladies" placeholder="ex. Diabète type 2, hypertension, asthme…"><?= htmlspecialchars($_POST['maladies'] ?? '') ?></textarea>
        </div>
        <div class="form-group" style="margin-bottom:14px;">
            <label>Traitement Actuel</label>
            <textarea name="traitement" placeholder="Médicaments et dosages actuels…"><?= htmlspecialchars($_POST['traitement'] ?? '') ?></textarea>
        </div>

        <!-- Contacts -->
        <div class="section-title">📞 Informations de Contact</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Médecin Référent</label>
                <input type="text" name="medecin" placeholder="Dr. Nom Prénom"
                       value="<?= htmlspecialchars($_POST['medecin'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contact Urgence <span class="req">*</span></label>
                <div class="field-err" id="err-contact"></div>
                <input type="tel" id="fd-contact" name="contact_en_cas_durgence"
                       placeholder="+216 7000 0000"
                       value="<?= htmlspecialchars($_POST['contact_en_cas_durgence'] ?? '') ?>">
            </div>
        </div>

        <!-- Notes -->
        <div class="section-title">📝 Notes</div>
        <div class="form-group">
            <label>Notes du Médecin</label>
            <textarea name="notes_medecin" placeholder="Observations et recommandations…"><?= htmlspecialchars($_POST['notes_medecin'] ?? '') ?></textarea>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-primary" onclick="fdValidateAndSubmit()">💾 Enregistrer le dossier</button>
            <a href="../../frontend/modules/health.html" class="btn btn-secondary">← Retour</a>
        </div>
    </form>
</div>

<!-- ── Modal: Créer un régime ── -->
<div class="modal-overlay" id="createRegimeModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">🍽️ Créer un régime</h2>
            <button type="button" class="modal-close" onclick="closeCreateRegimeModal()">✕</button>
        </div>

        <div class="modal-group">
            <label class="modal-label">Nom du régime <span style="color:#dc3545;">*</span></label>
            <div class="field-err" id="err-rnom"></div>
            <input type="text" id="mr-nom" class="modal-input" placeholder="ex. Régime Méditerranéen">
        </div>
        <div class="modal-group">
            <label class="modal-label">Description</label>
            <textarea id="mr-desc" class="modal-textarea" placeholder="Principes et bénéfices…"></textarea>
        </div>
        <div class="modal-group">
            <label class="modal-label">Type <span style="color:#dc3545;">*</span></label>
            <div class="field-err" id="err-rtype"></div>
            <select id="mr-type" class="modal-select">
                <option value="">— Sélectionner —</option>
                <option value="alimentaire">Alimentaire</option>
                <option value="perte_de_poids">Perte de poids</option>
                <option value="prise_de_masse">Prise de masse</option>
                <option value="sportif">Sport/Performance</option>
                <option value="medical">Médical</option>
            </select>
        </div>
        <div class="modal-group">
            <label class="modal-label">Niveau de difficulté <span style="color:#dc3545;">*</span></label>
            <div class="field-err" id="err-rniveau"></div>
            <select id="mr-niveau" class="modal-select">
                <option value="">— Sélectionner —</option>
                <option value="facile">Facile</option>
                <option value="modere">Modéré</option>
                <option value="avance">Avancé</option>
            </select>
        </div>
        <div class="modal-group">
            <label class="modal-label">Apport calorique moyen (kcal/jour)</label>
            <div class="field-err" id="err-rcal"></div>
            <input type="number" id="mr-cal" class="modal-input" placeholder="ex. 2000">
        </div>

        <div class="modal-actions">
            <button type="button" class="modal-btn-cancel" onclick="closeCreateRegimeModal()">Annuler</button>
            <button type="button" id="mr-submit-btn" class="modal-btn-submit" onclick="submitCreateRegime()">✓ Créer le régime</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="fd-toast" class="toast"></div>

<!-- ── JavaScript — all validation here, zero HTML5 constraints ── -->
<script>
(function () {
    'use strict';

    /* Detect controller path from current URL */
    var _p     = window.location.pathname; // …/view/frontend/health/addDossier.php
    var _idx   = _p.indexOf('/view/frontend/');
    var API_BASE = (_idx !== -1)
        ? _p.substring(0, _idx) + '/controller'
        : '../../../controller';

    /* ── Toast ── */
    var _tt = null;
    function toast(msg, type) {
        var t = document.getElementById('fd-toast');
        if (!t) return;
        t.textContent     = msg;
        t.style.background = (type === 'success') ? '#013220' : '#e74c3c';
        t.classList.add('show');
        clearTimeout(_tt);
        _tt = setTimeout(function () { t.classList.remove('show'); }, 4000);
    }

    /* ── Field helpers ── */
    function showErr(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent   = msg;
        el.style.display = msg ? 'block' : 'none';
    }
    function clearErrs() {
        ['err-poids','err-taille','err-contact','err-rnom','err-rtype','err-rniveau','err-rcal']
            .forEach(function(id){ showErr(id,''); });
        ['fd-poids','fd-taille','fd-contact','mr-nom','mr-type','mr-niveau','mr-cal']
            .forEach(function(id){ var el=document.getElementById(id); if(el) el.classList.remove('error'); });
    }
    function markErr(inputId, errId, msg) {
        showErr(errId, msg);
        var el = document.getElementById(inputId);
        if (el) el.classList.add('error');
    }

    /* ── IMC auto-calc ── */
    function calcIMC() {
        var p   = parseFloat(document.getElementById('fd-poids').value);
        var t   = parseFloat(document.getElementById('fd-taille').value);
        var box = document.getElementById('fd-imc-box');
        if (p > 0 && t > 0) {
            var imc = (p / Math.pow(t / 100, 2)).toFixed(1);
            var cat = imc < 18.5 ? 'Insuffisance pondérale' : imc < 25 ? 'Poids normal' : imc < 30 ? 'Surpoids' : 'Obésité';
            box.textContent = 'IMC calculé : ' + imc + ' – ' + cat;
            box.classList.add('show');
        } else {
            box.classList.remove('show');
        }
    }
    document.getElementById('fd-poids').addEventListener('input', calcIMC);
    document.getElementById('fd-taille').addEventListener('input', calcIMC);

    /* ── Validate & submit main form ── */
    function fdValidateAndSubmit() {
        clearErrs();
        var valid = true;

        var poidsEl   = document.getElementById('fd-poids');
        var tailleEl  = document.getElementById('fd-taille');
        var contactEl = document.getElementById('fd-contact');

        var poidsVal  = parseFloat(poidsEl.value);
        var tailleVal = parseFloat(tailleEl.value);
        var contactV  = contactEl.value.trim();

        // Poids
        if (!poidsEl.value || isNaN(poidsVal) || poidsVal <= 0) {
            markErr('fd-poids','err-poids','Poids invalide (doit être > 0).');
            valid = false;
        } else if (poidsVal < 20 || poidsVal > 500) {
            markErr('fd-poids','err-poids','Poids entre 20 et 500 kg.');
            valid = false;
        }

        // Taille
        if (!tailleEl.value || isNaN(tailleVal) || tailleVal <= 0) {
            markErr('fd-taille','err-taille','Taille invalide (doit être > 0).');
            valid = false;
        } else if (tailleVal < 50 || tailleVal > 250) {
            markErr('fd-taille','err-taille','Taille entre 50 et 250 cm.');
            valid = false;
        }

        // Contact (optional but must match format if provided)
        if (contactV && !/^\+\d{1,3}\s\d{4,6}\s\d{4,6}$/.test(contactV)) {
            markErr('fd-contact','err-contact','Format : +XX XXXX XXXX');
            valid = false;
        }

        if (!valid) { window.scrollTo({ top:0, behavior:'smooth' }); return; }
        document.getElementById('addDossierForm').submit();
    }
    window.fdValidateAndSubmit = fdValidateAndSubmit;

    /* ── Create Regime Modal ── */
    function openCreateRegimeModal() {
        clearErrs();
        ['mr-nom','mr-desc','mr-type','mr-niveau','mr-cal'].forEach(function(id){
            var el = document.getElementById(id);
            if (el) { el.value = ''; el.classList.remove('error'); }
        });
        document.getElementById('createRegimeModal').classList.add('active');
    }
    function closeCreateRegimeModal() {
        document.getElementById('createRegimeModal').classList.remove('active');
    }
    window.openCreateRegimeModal  = openCreateRegimeModal;
    window.closeCreateRegimeModal = closeCreateRegimeModal;

    async function submitCreateRegime() {
        // Clear modal errors
        ['err-rnom','err-rtype','err-rniveau','err-rcal'].forEach(function(id){ showErr(id,''); });
        ['mr-nom','mr-type','mr-niveau','mr-cal'].forEach(function(id){ var el=document.getElementById(id); if(el) el.classList.remove('error'); });

        var nom    = document.getElementById('mr-nom').value.trim();
        var type   = document.getElementById('mr-type').value;
        var niveau = document.getElementById('mr-niveau').value;
        var cal    = document.getElementById('mr-cal').value.trim();
        var valid  = true;

        // JS validation — no HTML5 attributes
        if (!nom) {
            showErr('err-rnom','Nom requis (min. 2 caractères).');
            document.getElementById('mr-nom').classList.add('error');
            valid = false;
        } else if (nom.length < 2 || nom.length > 100) {
            showErr('err-rnom','Nom entre 2 et 100 caractères.');
            document.getElementById('mr-nom').classList.add('error');
            valid = false;
        }
        if (!type) {
            showErr('err-rtype','Type requis.');
            document.getElementById('mr-type').classList.add('error');
            valid = false;
        }
        if (!niveau) {
            showErr('err-rniveau','Niveau de difficulté requis.');
            document.getElementById('mr-niveau').classList.add('error');
            valid = false;
        }
        if (cal !== '') {
            var calN = parseInt(cal, 10);
            if (isNaN(calN) || calN < 500 || calN > 10000) {
                showErr('err-rcal','Calories entre 500 et 10 000.');
                document.getElementById('mr-cal').classList.add('error');
                valid = false;
            }
        }
        if (!valid) return;

        var btn = document.getElementById('mr-submit-btn');
        btn.disabled = true;

        var fd = new FormData();
        fd.append('action',            'add');
        fd.append('nom_regime',        nom);
        fd.append('description',       document.getElementById('mr-desc').value);
        fd.append('type_regime',       type);
        fd.append('niveau_difficulte', niveau);
        if (cal) fd.append('apport_calorique_moyen', parseInt(cal, 10));

        try {
            var res  = await fetch(API_BASE + '/regime.controller.php', { method:'POST', body:fd });
            var data = await res.json();
            if (data.success) {
                toast('✅ Régime créé ! Sélectionné automatiquement.', 'success');
                closeCreateRegimeModal();

                // Auto-add and select the new regime in the dropdown
                var sel = document.getElementById('fd-regime-select');
                if (sel && data.id_regime) {
                    var o = document.createElement('option');
                    o.value       = data.id_regime;
                    o.textContent = nom + ' (' + type + ') — ID ' + data.id_regime;
                    o.selected    = true;
                    sel.appendChild(o);
                } else {
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            } else {
                toast('❌ ' + (data.message || 'Erreur lors de la création'), 'error');
            }
        } catch (e) {
            toast('❌ Erreur réseau', 'error');
        } finally {
            btn.disabled = false;
        }
    }
    window.submitCreateRegime = submitCreateRegime;

    // Close modal on overlay click
    document.getElementById('createRegimeModal').addEventListener('click', function (e) {
        if (e.target === this) closeCreateRegimeModal();
    });

})();
</script>
</body>
</html>
