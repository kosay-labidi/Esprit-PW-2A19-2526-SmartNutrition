<?php


/* ----------------------------------------------------------
   SECTION 1 — CHARGEMENT DES DÉPENDANCES
   Inclut la connexion BDD, le modèle Aliment et les fonctions
   utilitaires partagées (SVG, couleurs, nutriscore…)
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DES DONNÉES (appel au Model)
   On instancie le Model et on récupère tous les aliments triés
   par ordre alphabétique depuis la BDD.
   ---------------------------------------------------------- */
global $pdo;
$aliments = aliment_getAll($pdo);

/* ----------------------------------------------------------
   SECTION 3 — LECTURE DU MESSAGE DE SUCCÈS (GET param)
   Après une création/modification/suppression, le Controller
   redirige ici avec ?success=created|updated|deleted
   ---------------------------------------------------------- */
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Back Office — Aliments</title>

    <!-- Tailwind CSS  -->
    <!-- Font Awesome (icônes) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <link rel="stylesheet" href="/GSRepasVF2_final/Gestion-repas/view/backend/css/bo_alimentlist.css">
    <script src="/GSRepasVF2_final/Gestion-repas/view/backend/js/bo_alimentlist_extra.js"></script>
    <link rel="stylesheet" href="../css/bo_cursor.css">
</head>
<body>
<div id="cursor"></div><div id="cursor-trail"></div>

<section class="hero">
        <h1 class="hf" style="font-size:48px;color:white;line-height:1;margin-bottom:10px;">
            Gestion des Aliments
        </h1>
        <p style="font-size:14px;color:rgba(255,255,255,.7);margin-bottom:18px;">
            Entre nature et lumière, votre nutrition prend sens.
        </p>

        <?php
        $nb  = count($aliments);
        $nbt = count(array_unique(array_column($aliments,'type')));
        ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
            <span class="sp"><b><?= $nb ?></b> aliments</span>
            <span class="sp"><b><?= $nbt ?></b> types</span>
        </div>


        <button onclick="openModal()"
            id="btnCreerAliment" style="background:white;color:var(--vert);padding:12px 28px;border-radius:99px;font-size:14px;font-weight:700;border:2px solid transparent;cursor:pointer;display:inline-flex;align-items:center;gap:8px;margin-bottom:24px;font-family:'Lato',sans-serif;">
            <i class="fas fa-plus"></i> Créer un aliment
        </button>

        <!-- FONCTIONNALITÉS HORS CRUD -->
        <div style="margin-bottom:14px;">
            <p style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em;">Filtrer l'affichage</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;" id="fbar">
                <button class="fb on" data-type="tous"               onclick="setFilter(this)">Tous</button>
                <button class="fb"    data-type="légume"             onclick="setFilter(this)">Légumes</button>
                <button class="fb"    data-type="fruit"              onclick="setFilter(this)">Fruits</button>
                <button class="fb"    data-type="protéines animales" onclick="setFilter(this)">Protéines</button>
                <button class="fb"    data-type="céréale"            onclick="setFilter(this)">Céréales</button>
                <button class="fb"    data-type="légumineuse"        onclick="setFilter(this)">Légumineuses</button>
                <button class="fb"    data-type="produit laitier"    onclick="setFilter(this)">Laitiers</button>
                <button class="fb"    data-type="épice"              onclick="setFilter(this)">Épices</button>
                <button class="fb"    data-type="huile"              onclick="setFilter(this)">Huiles</button>

                <!-- Recherche par nom-->
                <div class="sw" style="margin-left:8px;">
                    <i class="fas fa-search"></i>
                    <input id="sq" type="text" class="si" placeholder="Rechercher un aliment…" oninput="applyFilters()">
                </div>
            </div>
        </div>
    </section>


    <div style="padding:0 28px;background:var(--bg-page);">
    <?php if ($success): ?>
        <div id="msgSucces" style="background:#e8f0e9;border:1px solid #1a372f30;color:var(--vert);padding:12px 20px;border-radius:12px;margin:16px 0;display:flex;align-items:center;gap:8px;font-size:13px;">
            <i class="fas fa-check-circle"></i>
            <?php
                if ($success==='created') echo 'Aliment ajouté avec succès.';
                if ($success==='updated') echo 'Aliment modifié avec succès.';
                if ($success==='deleted') echo 'Aliment supprimé.';
            ?>
        </div>
    <?php endif; ?>
    </div>

  
    <div style="padding:16px 28px 40px;background:var(--bg-page);">
        <!-- Breadcrumb concept 3 -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <p style="font-size:13px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-carrot" style="font-size:12px;"></i>
                Aliments
                <span style="opacity:.4;">›</span>
                <span id="breadcrumb-liste" style="color:var(--vert);font-weight:600;">Liste</span>
            </p>
            <a href="javascript:void(0)" onclick="if(window.parent&&window.parent.nav){window.parent.nav(null,'repas','Repas');}"
               style="font-size:12px;color:#9ca3af;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;"
               onmouseover="this.style.color='var(--violet)'"
               onmouseout="this.style.color='#9ca3af'">
                <i class="fas fa-arrow-left" style="font-size:11px;"></i> Retour aux repas
            </a>
        </div>
        <div class="tbl-wrap" style="overflow-x:auto;">

            <!-- En-tête du tableau -->
            <div class="thead" style="min-width:1100px;">
                <div></div>
                <div>Aliment</div>
                <div style="text-align:center" title="Calories pour 100g">Cal.</div>
                <div style="text-align:center" title="Protéines pour 100g">Prot.</div>
                <div style="text-align:center" title="Glucides pour 100g">Gluc.</div>
                <div style="text-align:center" title="Lipides pour 100g">Lip.</div>
                <div style="text-align:center" title="Fibres pour 100g">Fibres</div>
                <div style="text-align:center" title="Sucre pour 100g">Sucre</div>
                <div style="text-align:center" title="Sodium pour 100g">Sodium</div>
                <div style="text-align:center" title="Prix en TND par kg">Prix</div>
                <div title="Impact carbone">CO₂</div>
                <div title="Vitamines, origine, label, allergènes">Infos</div>
                <!-- Colonne Actions = Update (✏) + Delete (🗑) -->
                <div style="text-align:center">Actions</div>
            </div>

            <div id="tbody" style="min-width:1100px;">
            <?php if (empty($aliments)): ?>
                <!-- État vide : aucun aliment dans la BDD -->
                <div style="padding:48px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-seedling" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
                    Aucun aliment. Commencez par en créer un !
                </div>
            <?php else: ?>
                <?php foreach ($aliments as $a):

                    $c   = typeConfig($a['type']);
                    $svg = alimentSVG($a['nom'], $a['type'], $c, 40);
                    $co2 = co2Config((float)$a['co2']);
                ?>
                <div class="trow"
                     data-type="<?= htmlspecialchars($a['type']) ?>"
                     data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

                    <!-- Icône SVG générée dynamiquement selon le nom de l'aliment -->
                    <div style="display:flex;align-items:center;justify-content:center;"><?= $svg ?></div>


                    <div style="padding-left:10px;">
                        <p style="font-size:13px;font-weight:600;color:var(--text-main);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <div style="display:flex;gap:4px;margin-top:3px;flex-wrap:wrap;">
                            <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                            <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                        </div>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--text-main);margin:0;"><?= number_format($a['calories'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">kcal/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    <!-- Valeur : Fibres -->
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['fibres'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>


                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sucre'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">g/100g</p>
                    </div>

                    
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= number_format($a['sodium'],1) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">mg/100g</p>
                    </div>

                    
                    <div style="text-align:center;">
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['prix'],2) ?></p>
                        <p style="font-size:9px;color:#9ca3af;margin:0;">TND/kg</p>
                    </div>

                    
                    <div style="padding:0 6px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                            <span style="font-size:10px;color:#6b7280;"><?= number_format($a['co2'],2) ?> kg</span>
                            <span style="font-size:9px;background:<?= $co2['bg'] ?>;color:<?= $co2['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co2['label'] ?></span>
                        </div>
                        <div class="cobar"><div class="cofil" style="width:<?= $co2['pct'] ?>%;background:<?= $co2['color'] ?>;"></div></div>
                    </div>

                    
                    <div>
                        <?php if (!empty($a['vitamines'])): ?>
                        <p style="font-size:10px;color:#6b7280;margin:0 0 2px;"><span style="font-weight:600;">Vit:</span> <?= htmlspecialchars($a['vitamines']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['origine'])): ?>
                        <p style="font-size:10px;color:#6b7280;margin:0 0 2px;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['label_ecologique'])): ?>
                        <p style="font-size:10px;color:#1a372f;margin:0 0 2px;">🌱 <?= htmlspecialchars($a['label_ecologique']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['allergenes'])): ?>
                        <p style="font-size:10px;color:#8a2020;margin:0;">⚠ <?= htmlspecialchars($a['allergenes']) ?></p>
                        <?php endif; ?>
                        <?php if (empty($a['vitamines']) && empty($a['origine']) && empty($a['label_ecologique']) && empty($a['allergenes'])): ?>
                        <p style="font-size:10px;color:#c4bdb5;">—</p>
                        <?php endif; ?>
                    </div>


                    <div style="display:flex;gap:5px;justify-content:center;">
                        <!-- Bouton UPDATE (modifier) -->
                        <button class="bedit" title="Modifier cet aliment" onclick="ouvrirModifAliment('<?= $a['id_aliment'] ?>','<?= addslashes(htmlspecialchars($a['nom'])) ?>','<?= $a['type'] ?>','<?= $a['categorie'] ?>','<?= $a['calories'] ?>','<?= $a['proteines'] ?>','<?= $a['glucides'] ?>','<?= $a['lipides'] ?>','<?= $a['fibres'] ?>','<?= $a['sucre'] ?>','<?= $a['sodium'] ?>','<?= $a['co2'] ?>','<?= $a['prix'] ?>','<?= addslashes(htmlspecialchars($a['vitamines'])) ?>','<?= addslashes(htmlspecialchars($a['label_ecologique'])) ?>','<?= addslashes(htmlspecialchars($a['origine'])) ?>','<?= addslashes(htmlspecialchars($a['allergenes'])) ?>')">
                            <i class="fas fa-pen" style="font-size:10px;"></i>
                        </button>
                        <!-- Bouton DELETE (supprimer) avec confirmation JavaScript -->
                        <a href="#" onclick="deleteAliment(<?= $a['id_aliment'] ?>);return false;"
                           onclick="return confirm('Supprimer «<?= htmlspecialchars($a['nom']) ?>» ? Cette action est irréversible.')"
                           class="bdel" title="Supprimer cet aliment">
                            <i class="fas fa-trash" style="font-size:10px;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                
                <div id="noResult" style="display:none;padding:40px;text-align:center;color:#9ca3af;font-size:14px;">
                    <i class="fas fa-filter" style="font-size:1.8rem;display:block;margin-bottom:10px;color:#d0c8be;"></i>Aucun résultat pour ce filtre.
                </div>
            <?php endif; ?>
            </div>

            
            <div style="padding:10px 16px;background:#f9f6f2;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;" id="rowCount"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen Back Office © <?= date('Y') ?></span>
            </div>
        </div>
    </div>

</div>





<div id="modalOverlay" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div style="padding:28px 32px;">

            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Nouvel Aliment</h3>
                <button onclick="closeModal()" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>


            <form id="createForm" action="/GSRepasVF2_final/Gestion-repas/controller/alimentcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="create">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">


                <div style="grid-column:1/-1;">
                        <label class="lbl">Nom de l'aliment *</label>
                        <input type="text" name="nom" id="f_nom" class="mi" placeholder="ex: Carotte bio"
                               minlength="2" maxlength="200">
                        <p class="err-msg" id="e_nom">Le nom est obligatoire (min. 2 caractères).</p>
                    </div>


                    <div>
                        <label class="lbl">Type *</label>
                        <select name="type" id="f_type" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="légume">Légume</option>
                            <option value="fruit">Fruit</option>
                            <option value="céréale">Céréale</option>
                            <option value="protéines animales">Protéines animales</option>
                            <option value="légumineuse">Légumineuse</option>
                            <option value="produit laitier">Produit laitier</option>
                            <option value="huile">Huile</option>
                            <option value="épice">Épice</option>
                            <option value="autre">Autre</option>
                        </select>
                        <p class="err-msg" id="e_type">Veuillez choisir un type.</p>
                    </div>


                    <div>
                        <label class="lbl">Catégorie *</label>
                        <select name="categorie" id="f_cat" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="frais">Frais</option>
                            <option value="sec">Sec</option>
                            <option value="transformé">Transformé</option>
                            <option value="ultra-transformé">Ultra-transformé</option>
                        </select>
                        <p class="err-msg" id="e_cat">Veuillez choisir une catégorie.</p>
                    </div>

                    
                    <?php

                    $numFields = [
                        ['calories',  'Calories (kcal/100g)', true,  0, 9999],
                        ['proteines', 'Protéines (g/100g)',   true,  0, 100],
                        ['glucides',  'Glucides (g/100g)',    true,  0, 100],
                        ['lipides',   'Lipides (g/100g)',     true,  0, 100],
                        ['fibres',    'Fibres (g/100g)',      false, 0, 100],
                        ['sucre',     'Sucre (g/100g)',       false, 0, 100],
                        ['sodium',    'Sodium (mg/100g)',     false, 0, 5000],
                        ['co2',       'CO₂ (kg CO₂eq/kg)',   false, 0, 100],
                        ['prix',      'Prix (TND/kg)',        false, 0, 9999],
                    ];
                    foreach ($numFields as [$name, $label, $required, $min, $max]):
                    ?>
                    <div>
                        <label class="lbl"><?= $label ?> <?= $required ? '*' : '' ?></label>
                        <input type="number" step="0.01" name="<?= $name ?>" id="f_<?= $name ?>"
                               class="mi" value="0"
                               min="<?= $min ?>" max="<?= $max ?>"
                               <?= $required ? 'required' : '' ?>>
                        <p class="err-msg" id="e_<?= $name ?>">
                            Valeur entre <?= $min ?> et <?= $max ?>.
                        </p>
                    </div>
                    <?php endforeach; ?>


                    <div style="grid-column:1/-1;">
                        <label class="lbl">Vitamines</label>
                        <input type="text" name="vitamines" class="mi"
                               placeholder="ex: A, B12, C" maxlength="200">
                    </div>


                    <div style="grid-column:1/-1;">
                        <label class="lbl">Label écologique</label>
                        <input type="text" name="label_ecologique" class="mi"
                               placeholder="bio, AOP, conventionnel…" maxlength="100">
                    </div>


                    <div>
                        <label class="lbl">Origine</label>
                        <input type="text" name="origine" class="mi"
                               placeholder="Tunisie, France…" maxlength="100">
                    </div>


                    <div>
                        <label class="lbl">Allergènes</label>
                        <input type="text" name="allergenes" class="mi"
                               placeholder="gluten, lait, arachide…" maxlength="255">
                    </div>
                </div>


                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button id="btnAnnulerAdd" type="button" onclick="closeModal()"
                        style="flex:1;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <!-- Bouton soumet après validation JS -->
                    <button id="btnSaveAdd" type="button" onclick="validateAndSubmit()"
                        style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



    <script src="/GSRepasVF2_final/Gestion-repas/view/backend/js/bo_alimentlist.js"></script>

<!-- ═══════════════════════════════════════════════════════════
     MODALE MODIFICATION ALIMENT (identique à la modale ajout)
     ═══════════════════════════════════════════════════════════ -->
<div id="updateModalOverlay" class="modal-overlay" onclick="if(event.target===this)fermerModifAliment()">
    <div class="modal-box">
        <div style="padding:28px 32px;">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                <h3 class="hf" style="font-size:28px;color:var(--vert);">Modifier l'aliment</h3>
                <button onclick="fermerModifAliment()" style="font-size:22px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>

            <form id="updateForm" action="/GSRepasVF2_final/Gestion-repas/controller/alimentcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_aliment" id="u_id">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <div style="grid-column:1/-1;">
                        <label class="lbl">Nom de l'aliment *</label>
                        <input type="text" name="nom" id="u_nom" class="mi" placeholder="ex: Carotte bio" minlength="2" maxlength="200">
                        <p class="err-msg" id="ue_nom">Le nom est obligatoire (min. 2 caractères).</p>
                    </div>

                    <div>
                        <label class="lbl">Type *</label>
                        <select name="type" id="u_type" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="légume">Légume</option>
                            <option value="fruit">Fruit</option>
                            <option value="céréale">Céréale</option>
                            <option value="protéines animales">Protéines animales</option>
                            <option value="légumineuse">Légumineuse</option>
                            <option value="produit laitier">Produit laitier</option>
                            <option value="huile">Huile</option>
                            <option value="épice">Épice</option>
                            <option value="autre">Autre</option>
                        </select>
                        <p class="err-msg" id="ue_type">Veuillez choisir un type.</p>
                    </div>

                    <div>
                        <label class="lbl">Catégorie *</label>
                        <select name="categorie" id="u_cat" class="mi">
                            <option value="">— Choisir —</option>
                            <option value="frais">Frais</option>
                            <option value="sec">Sec</option>
                            <option value="transformé">Transformé</option>
                            <option value="ultra-transformé">Ultra-transformé</option>
                        </select>
                        <p class="err-msg" id="ue_cat">Veuillez choisir une catégorie.</p>
                    </div>

                    <div><label class="lbl">Calories (kcal/100g) *</label>
                        <input type="number" step="0.01" name="calories"  id="u_calories"  class="mi" min="0" max="9999" required>
                        <p class="err-msg" id="ue_calories">Valeur entre 0 et 9999.</p></div>
                    <div><label class="lbl">Protéines (g/100g) *</label>
                        <input type="number" step="0.01" name="proteines" id="u_proteines" class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_proteines">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Glucides (g/100g) *</label>
                        <input type="number" step="0.01" name="glucides"  id="u_glucides"  class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_glucides">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Lipides (g/100g) *</label>
                        <input type="number" step="0.01" name="lipides"   id="u_lipides"   class="mi" min="0" max="100" required>
                        <p class="err-msg" id="ue_lipides">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Fibres (g/100g)</label>
                        <input type="number" step="0.01" name="fibres"    id="u_fibres"    class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_fibres">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Sucre (g/100g)</label>
                        <input type="number" step="0.01" name="sucre"     id="u_sucre"     class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_sucre">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Sodium (mg/100g)</label>
                        <input type="number" step="0.01" name="sodium"    id="u_sodium"    class="mi" min="0" max="5000">
                        <p class="err-msg" id="ue_sodium">Valeur entre 0 et 5000.</p></div>
                    <div><label class="lbl">CO₂ (kg CO₂eq/kg)</label>
                        <input type="number" step="0.01" name="co2"       id="u_co2"       class="mi" min="0" max="100">
                        <p class="err-msg" id="ue_co2">Valeur entre 0 et 100.</p></div>
                    <div><label class="lbl">Prix (TND/kg)</label>
                        <input type="number" step="0.01" name="prix"      id="u_prix"      class="mi" min="0" max="9999">
                        <p class="err-msg" id="ue_prix">Valeur entre 0 et 9999.</p></div>

                    <div style="grid-column:1/-1;">
                        <label class="lbl">Vitamines</label>
                        <input type="text" name="vitamines" id="u_vitamines" class="mi" placeholder="ex: A, B12, C" maxlength="200">
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="lbl">Label écologique</label>
                        <input type="text" name="label_ecologique" id="u_label" class="mi" placeholder="bio, AOP, conventionnel…" maxlength="100">
                    </div>
                    <div>
                        <label class="lbl">Origine</label>
                        <input type="text" name="origine" id="u_origine" class="mi" placeholder="Tunisie, France…" maxlength="100">
                    </div>
                    <div>
                        <label class="lbl">Allergènes</label>
                        <input type="text" name="allergenes" id="u_allergenes" class="mi" placeholder="gluten, lait, arachide…" maxlength="255">
                    </div>
                </div>

                <div style="display:flex;gap:10px;margin-top:22px;">
                    <button id="btnAnnulerUpdate" type="button" onclick="fermerModifAliment()"
                        style="flex:1;padding:13px;border-radius:99px;border:1.5px solid #e8e0d8;background:white;font-weight:600;font-size:13px;cursor:pointer;color:var(--vert);">
                        Annuler
                    </button>
                    <button id="btnSaveUpdate" type="button" onclick="validateAndSubmitUpdate()"
                        style="flex:1;padding:13px;border-radius:99px;background:var(--vert);color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script src="../js/cursor.js"></script>
</body>
</html>


