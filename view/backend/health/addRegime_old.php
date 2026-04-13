<?php
require_once '../../../config.php';
require_once '../../../controller/regime.controller.php';
require_once '../../../Model/Regime.php';

$ctrl = new RegimeController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $alimentsInterdits = !empty($_POST['aliments_interdits']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_interdits']))) : [];
        $alimentsRecommandes = !empty($_POST['aliments_recommandes']) ? array_filter(array_map('trim', explode(',', $_POST['aliments_recommandes']))) : [];
        
        $slug = strtolower(str_replace(' ', '-', $_POST['nom_regime'] ?? ''));
        
        $r = new Regime(
            null,
            $_POST['nom_regime'] ?? null,
            $slug,
            $_POST['description'] ?? null,
            $_POST['type_regime'] ?? null,
            $_POST['niveau_difficulte'] ?? null,
            json_encode($alimentsInterdits),
            json_encode($alimentsRecommandes),
            floatval($_POST['apport_calorique_moyen'] ?? 0)
        );
        $ctrl->add($r);
        $success = "✅ Régime ajouté avec succès!";
        $_POST = [];
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
    <title>Ajouter Régime</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>:root{--vert:#013220;--sable:#CBBD93;--violet:#BA5BED;--bleu:#77B5FE;}</style>
</head>
<body class="bg-[#CBBD93]">
    <main class="main-wrapper">
        <div class="glass p-10 max-w-3xl mx-auto mt-12 rounded-3xl">
            <h1 class="text-3xl font-bold text-[#013220] mb-8">🍽️ Ajouter un Nouveau Régime</h1>
            
            <?php if ($error): ?><div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            
            <form id="regimeForm" method="POST" class="grid grid-cols-2 gap-6">
                <div class="col-span-2"><label class="block text-sm mb-2 font-semibold">Nom du régime</label><input type="text" name="nom_regime" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Végétarien" required></div>
                
                <div>
                    <label class="block text-sm mb-2 font-semibold">Type de régime</label>
                    <select name="type_regime" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                        <option value="">— Sélectionner —</option>
                        <option value="alimentaire">Alimentaire</option>
                        <option value="medical">Médical</option>
                        <option value="sportif">Sportif</option>
                        <option value="perte_de_poids">Perte de poids</option>
                        <option value="prise_de_masse">Prise de masse</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm mb-2 font-semibold">Niveau de difficulté</label>
                    <select name="niveau_difficulte" class="w-full rounded-2xl border border-[#77B5FE] p-4" required>
                        <option value="">— Sélectionner —</option>
                        <option value="facile">Facile</option>
                        <option value="modere">Modéré</option>
                        <option value="avance">Avancé</option>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Description</label>
                    <textarea name="description" rows="4" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="Détails du régime..."></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Aliments interdits (séparés par des virgules)</label>
                    <textarea name="aliments_interdits" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Viande rouge, produits laitiers, sucre"></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Aliments recommandés (séparés par des virgules)</label>
                    <textarea name="aliments_recommandes" rows="3" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. Fruits, légumes, poisson"></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm mb-2 font-semibold">Apport calorique moyen (Kcal)</label>
                    <input type="number" step="10" name="apport_calorique_moyen" class="w-full rounded-2xl border border-[#77B5FE] p-4" placeholder="ex. 2000">
                </div>

                <div class="col-span-2"><button type="submit" class="w-full bg-[#BA5BED] text-white py-5 rounded-2xl text-xl font-semibold">💾 Ajouter le régime</button></div>
            </form>
            <a href="../modules/health-admin.html" class="mt-6 inline-block text-[#013220] hover:underline">← Retour au tableau de bord</a>
        </div>
    </main>
</body>
</html>

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Régime - Admin GaiaLumen</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        :root { --primary: #00b894; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f5f0e8;
            color: #2d3436;
            min-height: 100vh;
        }

        .header {
            background: #f5f0e8;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e0d9cc;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .logo { display: flex; align-items: center; gap: 12px; font-size: 26px; font-weight: 700; }
        .logo span { color: var(--primary); }
        .nav-buttons { margin-left: auto; display: flex; gap: 12px; }
        .nav-buttons button {
            padding: 8px 22px;
            border-radius: 50px;
            border: 1px solid #d1c7b8;
            background: white;
            font-size: 14px;
            cursor: pointer;
        }
        .nav-buttons button:hover { background: #fff; transform: translateY(-1px); }
        .deconnexion {
            background: linear-gradient(90deg, #00b894, #00a080) !important;
            color: white;
            border: none !important;
        }

        .content {
            max-width: 780px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: var(--primary);
            color: white;
            padding: 25px 30px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-body { padding: 40px 35px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3436;
        }
        input, select, textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0d9cc;
            border-radius: 10px;
            font-size: 16px;
            margin-bottom: 22px;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        textarea { resize: vertical; min-height: 110px; }

        button[type="submit"] {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            margin-top: 10px;
        }
        button[type="submit"]:hover { background: #00a080; }
    </style>
</head>
<body>

    <!-- HEADER (same as your admin.html) -->
    <div class="header">
        <div class="logo">
            🛡️ <span>Admin</span> GaiaLumen
        </div>
        <div class="nav-buttons">
            <button>🔄 Recharger</button>
            <button>🌙 Sombre</button>
            <button class="deconnexion">➔ Déconnexion</button>
        </div>
    </div>

    <!-- FORM -->
    <div class="content">
        <div class="card">
            <div class="card-header">
                🍏 Ajouter un Régime
            </div>
            <div class="card-body">
                <form method="POST">
                    <label>Nom du régime</label>
                    <input type="text" name="nom_regime" required>

                    <label>Description</label>
                    <textarea name="description" placeholder="Décrivez le régime..." required></textarea>

                    <label>Type de régime</label>
                    <select name="type_regime" required>
                        <option value="">Choisir un type...</option>
                        <option value="Perte de poids">Perte de poids</option>
                        <option value="Prise de masse">Prise de masse</option>
                        <option value="Équilibré">Équilibré</option>
                        <option value="Kéto">Kéto</option>
                        <option value="Végétarien">Végétarien</option>
                        <option value="Végan">Végan</option>
                        <option value="Sans gluten">Sans gluten</option>
                    </select>

                    <label>Aliments interdits</label>
                    <textarea name="aliments_interdits" placeholder="ex: sucre, pain blanc..."></textarea>

                    <label>Aliments recommandés</label>
                    <textarea name="aliments_recommandes" placeholder="ex: avocat, noix, légumes..."></textarea>

                    <label>Niveau de difficulté</label>
                    <select name="niveau_difficulte" required>
                        <option value="">Choisir...</option>
                        <option value="Facile">Facile</option>
                        <option value="Moyen">Moyen</option>
                        <option value="Difficile">Difficile</option>
                    </select>

                    <label>Apport calorique moyen (kcal/jour)</label>
                    <input type="number" name="apport_calorique_moyen" step="0.01" placeholder="1800" required>

                    <button type="submit">Enregistrer Régime</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>