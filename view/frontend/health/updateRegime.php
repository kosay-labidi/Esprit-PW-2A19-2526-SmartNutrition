<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';

$ctrl = new RegimeController();
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: health.html');
    exit;
}

$regime = $ctrl->show($id);   // Votre controller doit avoir show($id)

if (!$regime) {
    header('Location: health.html');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_regime           = trim($_POST['nom_regime'] ?? '');
    $description          = trim($_POST['description'] ?? '');
    $type_regime          = $_POST['type_regime'] ?? 'alimentaire';
    $niveau_difficulte    = $_POST['niveau_difficulte'] ?? 'modere';
    $aliments_interdits   = trim($_POST['aliments_interdits'] ?? '[]');
    $aliments_recommandes = trim($_POST['aliments_recommandes'] ?? '[]');
    $apport_calorique     = !empty($_POST['apport_calorique']) ? (float)$_POST['apport_calorique'] : null;

    if (empty($nom_regime)) {
        $error = "Le nom du régime est obligatoire.";
    } else {
        $aliments_interdits   = json_encode(json_decode($aliments_interdits, true) ?: []);
        $aliments_recommandes = json_encode(json_decode($aliments_recommandes, true) ?: []);

        $updatedRegime = new Regime(
            $id,
            $nom_regime,
            $regime['slug'] ?? strtolower(str_replace(' ', '-', $nom_regime)),
            $description,
            $type_regime,
            $niveau_difficulte,
            $aliments_interdits,
            $aliments_recommandes,
            $apport_calorique
        );

        $result = $ctrl->update($updatedRegime, $id);

        if ($result) {
            // Respect return URL if provided (safe redirect)
            if (!empty($_GET['return'])) {
                $return = rawurldecode($_GET['return']);
                // Only accept internal relative paths starting with /project_v0/
                // and refuse returning to form pages to avoid loops or wrong pages
                $blocked = preg_match('/updateRegime\.php|addRegime\.php|updateDossier\.php|addDossier\.php/i', $return);
                if (strpos($return, '/project_v0/') === 0 && !$blocked) {
                    header('Location: ' . $return);
                    exit;
                }
            }
            header('Location: ../modules/health.html?msg=regime_updated');
            exit;
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Régime - GaiaLumen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --green: #1F3D2B;
            --sand: #F2E8CF;
            --violet: #5B3E96;
            --blue: #3A86C4;
            --bg: #0a1a10;
            --surface: #0f2318;
            --text: #F2E8CF;
            --muted: #a8b8a0;
            --card-bg: rgba(15, 35, 24, 0.72);
            --glass: rgba(31, 61, 43, 0.45);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            --radius: 18px;
            --tr: 0.4s cubic-bezier(.4, 0, .2, 1);
            --danger: #e74c3c;
            --success: #2ecc71;
        }
        
        [data-theme="light"] {
            --bg: #f5f0e8;
            --surface: #ede5d0;
            --text: #1F3D2B;
            --muted: #5a6e5a;
            --card-bg: rgba(242, 232, 207, 0.75);
            --glass: rgba(242, 232, 207, 0.6);
            --shadow: 0 8px 32px rgba(31, 61, 43, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            transition: background var(--tr), color var(--tr);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: clamp(2rem, 4vw, 2.5rem);
            margin-bottom: 12px;
            letter-spacing: .05em;
        }
        
        .header p {
            color: var(--muted);
            font-size: 1.05rem;
        }
        
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(91, 62, 150, .2);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.15);
            color: var(--danger);
            border-left-color: var(--danger);
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--text);
            letter-spacing: .04em;
            text-transform: uppercase;
            opacity: 0.9;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-grid.full {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 0.95rem;
            letter-spacing: .02em;
        }
        
        label .required {
            color: var(--danger);
            margin-left: 4px;
        }
        
        input, select, textarea {
            padding: 12px 16px;
            background: rgba(31, 61, 43, 0.3);
            border: 1px solid rgba(91, 62, 150, .3);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--text);
            font-family: 'Lato', sans-serif;
            transition: all var(--tr);
        }
        
        input::placeholder, textarea::placeholder {
            color: rgba(242, 232, 207, 0.5);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            background: rgba(31, 61, 43, 0.5);
            border-color: var(--violet);
            box-shadow: 0 0 16px rgba(91, 62, 150, .2);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .button-group {
            display: flex;
            gap: 16px;
            margin-top: 40px;
        }
        
        .btn {
            flex: 1;
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--tr);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: .04em;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--violet), var(--blue));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(91, 62, 150, 0.4);
        }
        
        .btn-secondary {
            background: var(--glass);
            color: var(--text);
            border: 1.5px solid rgba(91, 62, 150, 0.5);
            flex: 0.4;
        }
        
        .btn-secondary:hover {
            background: rgba(91, 62, 150, 0.15);
            border-color: var(--violet);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 24px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-secondary {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Modifier Régime</h1>
            <p>ID: <?php echo htmlspecialchars($id); ?> — <?php echo htmlspecialchars($regime['nom_regime'] ?? ''); ?></p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Informations Générales -->
                <div class="form-section">
                    <h3 class="section-title">📋 Informations Générales</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom_regime">Nom du Régime <span class="required">*</span></label>
                            <input type="text" id="nom_regime" name="nom_regime" required
                                   value="<?php echo htmlspecialchars($regime['nom_regime'] ?? $_POST['nom_regime'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="type_regime">Type <span class="required">*</span></label>
                            <select id="type_regime" name="type_regime" required>
                                <option value="alimentaire" <?php echo ($regime['type_regime'] ?? '') === 'alimentaire' ? 'selected' : ''; ?>>Alimentaire</option>
                                <option value="medical" <?php echo ($regime['type_regime'] ?? '') === 'medical' ? 'selected' : ''; ?>>Médical</option>
                                <option value="sportif" <?php echo ($regime['type_regime'] ?? '') === 'sportif' ? 'selected' : ''; ?>>Sportif</option>
                                <option value="perte_de_poids" <?php echo ($regime['type_regime'] ?? '') === 'perte_de_poids' ? 'selected' : ''; ?>>Perte de poids</option>
                                <option value="prise_de_masse" <?php echo ($regime['type_regime'] ?? '') === 'prise_de_masse' ? 'selected' : ''; ?>>Prise de masse</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="niveau_difficulte">Niveau de Difficulté <span class="required">*</span></label>
                            <select id="niveau_difficulte" name="niveau_difficulte" required>
                                <option value="facile" <?php echo ($regime['niveau_difficulte'] ?? '') === 'facile' ? 'selected' : ''; ?>>Facile</option>
                                <option value="modere" <?php echo ($regime['niveau_difficulte'] ?? '') === 'modere' ? 'selected' : ''; ?>>Modéré</option>
                                <option value="difficile" <?php echo ($regime['niveau_difficulte'] ?? '') === 'difficile' ? 'selected' : ''; ?>>Difficile</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="apport_calorique">Apport Calorique Moyen (kcal/jour)</label>
                            <input type="number" id="apport_calorique" name="apport_calorique" min="0" step="50"
                                   value="<?php echo htmlspecialchars($regime['apport_calorique_moyen'] ?? $_POST['apport_calorique'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($regime['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Aliments -->
                <div class="form-section">
                    <h3 class="section-title">🥗 Aliments</h3>
                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="aliments_interdits">Aliments Interdits (JSON ou séparés par des virgules)</label>
                            <textarea id="aliments_interdits" name="aliments_interdits" placeholder='["chocolat", "sucre", "graisse"]'><?php echo htmlspecialchars($regime['aliments_interdits'] ?? $_POST['aliments_interdits'] ?? '[]'); ?></textarea>
                        </div>
                    </div>

                    <div class="form-grid full">
                        <div class="form-group">
                            <label for="aliments_recommandes">Aliments Recommandés (JSON ou séparés par des virgules)</label>
                            <textarea id="aliments_recommandes" name="aliments_recommandes" placeholder='["fruits", "légumes", "protéines"]'><?php echo htmlspecialchars($regime['aliments_recommandes'] ?? $_POST['aliments_recommandes'] ?? '[]'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Mettre à Jour</button>
                   
                </div>
            </form>
        </div>
    </div>
</body>
</html>