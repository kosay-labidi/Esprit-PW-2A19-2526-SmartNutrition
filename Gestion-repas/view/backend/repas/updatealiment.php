<?php

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/aliment.php';

$alimentModel = new Aliment();
$aliment = $alimentModel->getById($_GET['id'] ?? 0);

if (!$aliment) {
    header("Location: bo_alimentlist.php");
    exit;
}

// Helper pour selected/checked
function sel($val, $ref) { return $val === $ref ? 'selected' : ''; }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Aliment - GaiaLumen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        * { font-family: 'Lato', sans-serif; }
        .heading-font { font-family: 'Cormorant Garamond', serif; }
        input:focus, select:focus { outline: none; border-color: #a78bfa; }
        .header-bg { background: linear-gradient(90deg, #1a372f 0%, #11241f 100%); }
    </style>
</head>
<body class="bg-[#f4ede4] text-[#1a372f] min-h-screen">

    <!-- NAVBAR -->
    <nav class="header-bg text-white sticky top-0 z-50 shadow-xl">
        <div class="max-w-6xl mx-auto px-8 py-5 flex items-center justify-between">
            <a href="../../index.html" class="flex items-center gap-3">
                <svg width="38" height="38" viewBox="0 0 60 60" fill="none">
                    <circle cx="30" cy="30" r="28" stroke="url(#ag)" stroke-width="1.5" opacity="0.6"/>
                    <defs>
                        <radialGradient id="ag" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#3A86C4"/>
                            <stop offset="100%" stop-color="#5B3E96"/>
                        </radialGradient>
                    </defs>
                    <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
                </svg>
                <span class="heading-font text-4xl tracking-tighter">GaiaLumen</span>
            </a>
            <ul class="flex items-center gap-9 text-base font-medium">
                <li><a href="../../index.html" class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
                <li><a href="alimentlist.php" class="hover:text-[#a78bfa] transition-colors">Aliments</a></li>
            </ul>
        </div>
    </nav>

    <!-- FORMULAIRE -->
    <div class="max-w-3xl mx-auto px-8 py-12">

        <!-- En-tête -->
        <div class="flex items-center gap-4 mb-8">
            <a href="alimentlist.php" class="text-[#1a372f] hover:text-[#a78bfa] transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="heading-font text-4xl text-[#1a372f]">Modifier l'aliment</h2>
                <p class="text-gray-500 mt-1">ID : #<?= $aliment['id_aliment'] ?> — <?= htmlspecialchars($aliment['nom']) ?></p>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8">
            <form action="../../../controller/alimentcontroller.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_aliment" value="<?= $aliment['id_aliment'] ?>">

                <div class="grid grid-cols-2 gap-6">

                    <!-- Nom -->
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-2">Nom de l'aliment *</label>
                        <input type="text" name="nom" required
                               value="<?= htmlspecialchars($aliment['nom']) ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Type *</label>
                        <select name="type" required class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                            <option value="légume"            <?= sel($aliment['type'], 'légume') ?>>Légume</option>
                            <option value="fruit"             <?= sel($aliment['type'], 'fruit') ?>>Fruit</option>
                            <option value="céréale"           <?= sel($aliment['type'], 'céréale') ?>>Céréale</option>
                            <option value="protéines animales"<?= sel($aliment['type'], 'protéines animales') ?>>Protéines animales</option>
                            <option value="légumineuse"       <?= sel($aliment['type'], 'légumineuse') ?>>Légumineuse</option>
                            <option value="produit laitier"   <?= sel($aliment['type'], 'produit laitier') ?>>Produit laitier</option>
                            <option value="huile"             <?= sel($aliment['type'], 'huile') ?>>Huile</option>
                            <option value="épice"             <?= sel($aliment['type'], 'épice') ?>>Épice</option>
                            <option value="autre"             <?= sel($aliment['type'], 'autre') ?>>Autre</option>
                        </select>
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Catégorie *</label>
                        <select name="categorie" required class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                            <option value="frais"           <?= sel($aliment['categorie'], 'frais') ?>>Frais</option>
                            <option value="sec"             <?= sel($aliment['categorie'], 'sec') ?>>Sec</option>
                            <option value="transformé"      <?= sel($aliment['categorie'], 'transformé') ?>>Transformé</option>
                            <option value="ultra-transformé"<?= sel($aliment['categorie'], 'ultra-transformé') ?>>Ultra-transformé</option>
                        </select>
                    </div>

                    <!-- Valeurs nutritionnelles -->
                    <div>
                        <label class="block text-sm font-semibold mb-2">Calories (kcal/100g) *</label>
                        <input type="number" step="0.01" name="calories" required
                               value="<?= $aliment['calories'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Protéines (g/100g) *</label>
                        <input type="number" step="0.01" name="proteines" required
                               value="<?= $aliment['proteines'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Glucides (g/100g) *</label>
                        <input type="number" step="0.01" name="glucides" required
                               value="<?= $aliment['glucides'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Lipides (g/100g) *</label>
                        <input type="number" step="0.01" name="lipides" required
                               value="<?= $aliment['lipides'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Fibres (g/100g)</label>
                        <input type="number" step="0.01" name="fibres"
                               value="<?= $aliment['fibres'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Sucre (g/100g)</label>
                        <input type="number" step="0.01" name="sucre"
                               value="<?= $aliment['sucre'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Sodium (mg/100g)</label>
                        <input type="number" step="0.01" name="sodium"
                               value="<?= $aliment['sodium'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">CO₂ (kg CO₂eq/kg)</label>
                        <input type="number" step="0.01" name="co2"
                               value="<?= $aliment['co2'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>

                    <!-- Autres infos -->
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-2">Vitamines</label>
                        <input type="text" name="vitamines"
                               value="<?= htmlspecialchars($aliment['vitamines'] ?? '') ?>"
                               placeholder="ex: A, B12, C"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-2">Label écologique</label>
                        <input type="text" name="label_ecologique"
                               value="<?= htmlspecialchars($aliment['label_ecologique'] ?? '') ?>"
                               placeholder="bio, AOP, conventionnel..."
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Prix (TND/kg)</label>
                        <input type="number" step="0.01" name="prix"
                               value="<?= $aliment['prix'] ?>"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Origine</label>
                        <input type="text" name="origine"
                               value="<?= htmlspecialchars($aliment['origine'] ?? '') ?>"
                               placeholder="Tunisie, France..."
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-2">Allergènes</label>
                        <input type="text" name="allergenes"
                               value="<?= htmlspecialchars($aliment['allergenes'] ?? '') ?>"
                               placeholder="gluten, lait, arachide..."
                               class="w-full px-5 py-4 rounded-2xl border border-gray-200">
                    </div>

                </div>

                <!-- Boutons -->
                <div class="flex gap-4 mt-10">
                    <a href="alimentlist.php" 
                       class="flex-1 text-center py-4 rounded-3xl border border-gray-300 font-semibold hover:bg-gray-50 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="flex-1 py-4 rounded-3xl bg-[#1a372f] text-white font-semibold hover:bg-[#11241f] transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>