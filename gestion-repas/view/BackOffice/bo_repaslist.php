<?php
/* ============================================================
   view/BackOffice/repaslist.php — BACK OFFICE Repas

   RÔLE : Vue READ uniquement des repas pour l'administrateur.
   L'admin CONSULTE les repas créés par les utilisateurs mais
   ne les crée, modifie, ni supprime pas — ce sont les
   utilisateurs qui gèrent leurs propres repas.

   LOGIQUE MVC :
     Back Office repas = READ seulement
     Front Office repas = CRUD complet

   Affiche pour chaque repas :
     - Nom, date, utilisateur
     - Composition (aliments via jointure repas_aliments)
     - Totaux nutritionnels calculés
   ============================================================ */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/repas_model.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';


$repasModel = new Repas();
/* Lecture de TOUS les repas (tous utilisateurs) */
$tousRepas  = $repasModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Back Office — Repas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }
        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        body { background:var(--sable); color:var(--vert); display:flex; min-height:100vh; }

        /* ── Sidebar verticale ────────────────────────────── */
        .sidebar { width:240px;min-height:100vh;background:linear-gradient(180deg,var(--vert) 0%,#11241f 100%);position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,.15); }
        .sidebar-logo { padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px;text-decoration:none; }
        .sidebar-logo span { font-family:'Cormorant Garamond',serif;font-size:22px;color:white;letter-spacing:-.03em; }
        .sidebar-badge { margin:0 20px 16px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--violet);padding:5px 10px;background:rgba(167,139,250,.15);border-radius:6px;text-align:center; }
        .sidebar-section { font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.35);padding:14px 20px 6px; }
        .nav-item { display:flex;align-items:center;gap:10px;padding:11px 20px;color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;font-weight:500;transition:all .18s;border-left:3px solid transparent; }
        .nav-item:hover { background:rgba(255,255,255,.07);color:white; }
        .nav-item.active { background:rgba(167,139,250,.18);color:var(--violet);border-left-color:var(--violet); }
        .nav-item i { width:16px;text-align:center;font-size:13px; }
        .sidebar-footer { margin-top:auto;padding:16px 20px;border-top:1px solid rgba(255,255,255,.1);font-size:11px;color:rgba(255,255,255,.35); }

        .main-content { margin-left:240px;flex:1;display:flex;flex-direction:column; }
        .topbar { background:linear-gradient(135deg,var(--vert) 0%,#11241f 100%);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50; }

        /* ── Hero ────────────────────────────────────────── */
        .hero { background-image:linear-gradient(rgba(26,55,47,.72),rgba(26,55,47,.72)),url('assets/images/1000051721.jpg');background-size:cover;background-position:center;padding:40px 28px 32px; }

        /* ── Tableau ─────────────────────────────────────── */
        .tbl-wrap { background:white;border-radius:20px;overflow:hidden;border:1px solid rgba(26,55,47,.1); }
        .badge { display:inline-block;font-size:10px;padding:2px 8px;border-radius:99px;font-weight:600; }
        .sp { background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* Recherche */
        .si { padding:8px 14px 8px 36px;border-radius:99px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);font-size:12px;color:white;outline:none;width:200px; }
        .si::placeholder { color:rgba(255,255,255,.5); }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:11px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="../../index.html" class="sidebar-logo">
        <svg width="30" height="30" viewBox="0 0 60 60" fill="none"><circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/><defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs><path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/></svg>
        <span>GaiaLumen</span>
    </a>
    <div class="sidebar-badge">⚙ Back Office</div>

    <div class="sidebar-section">Module Repas</div>
    <a href="bo_alimentlist.php" class="nav-item"><i class="fas fa-carrot"></i> Aliments</a>
    <a href="bo_repaslist.php"   class="nav-item active"><i class="fas fa-utensils"></i> Repas (consultation)</a>

    <div class="sidebar-section">Site</div>
    <a href="../../index.html" class="nav-item"><i class="fas fa-home"></i> Accueil</a>
    <a href="../FrontOffice/fo_repaslist.php" class="nav-item"><i class="fas fa-eye"></i> Vue utilisateur</a>

    <div class="sidebar-footer">GaiaLumen © <?= date('Y') ?></div>
</aside>

<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="text-white text-sm font-medium flex items-center gap-2">
            <i class="fas fa-shield-alt text-[#a78bfa]"></i>
            Administration › Consultation des Repas
        </div>
        <!-- Note : pas de bouton "Créer" — le BO ne crée pas de repas -->
        <span style="font-size:11px;color:rgba(255,255,255,.5);font-style:italic;">Lecture seule — les repas sont créés par les utilisateurs</span>
    </div>

    <!-- Hero -->
    <section class="hero">
        <h1 class="hf" style="font-size:44px;color:white;line-height:1;margin-bottom:8px;">Consultation des Repas</h1>
        <p style="font-size:13px;color:rgba(255,255,255,.7);margin-bottom:16px;">
            Vue administrative — tous les repas créés par les utilisateurs.
        </p>
        <?php
        $totalRepas   = count($tousRepas);
        $totalAlimSel = array_sum(array_column($tousRepas,'nb_aliments'));
        ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
            <span class="sp"><b><?= $totalRepas ?></b> repas au total</span>
            <span class="sp"><b><?= $totalAlimSel ?></b> sélections d'aliments</span>
        </div>

        <!-- ══ HORS CRUD : Recherche (filtre affichage uniquement) ══ -->
        <div class="sw">
            <i class="fas fa-search"></i>
            <input id="sq" type="text" class="si" placeholder="Rechercher un repas…" oninput="applySearch()">
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         READ ONLY — Tableau des repas avec composition
         ══════════════════════════════════════════════════════ -->
    <div style="padding:20px 28px 40px;">

        <?php if (empty($tousRepas)): ?>
        <div style="background:white;border-radius:20px;padding:48px;text-align:center;color:#9ca3af;">
            <i class="fas fa-utensils" style="font-size:2.5rem;display:block;margin-bottom:14px;color:#d0c8be;"></i>
            Aucun repas n'a encore été créé par les utilisateurs.
        </div>
        <?php else: ?>

        <div style="display:flex;flex-direction:column;gap:14px;" id="repasList">
        <?php foreach ($tousRepas as $r):
            $alimRepas = $repasModel->getAlimentsOfRepas((int)$r['id_repas']);
            $totaux    = $repasModel->getTotauxNutritionnels((int)$r['id_repas']);
        ?>
        <div class="tbl-wrap" data-nom="<?= strtolower(htmlspecialchars($r['nom_repas'])) ?>">
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr auto;align-items:start;gap:16px;">

                <!-- Infos repas -->
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <h3 class="hf" style="font-size:20px;color:var(--vert);margin:0;"><?= htmlspecialchars($r['nom_repas']) ?></h3>
                        <span style="background:#f4ede4;color:#6b7280;font-size:10px;padding:2px 8px;border-radius:99px;">#<?= $r['id_repas'] ?></span>
                    </div>
                    <p style="font-size:12px;color:#9ca3af;margin-bottom:10px;">
                        <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>
                        <?= date('d/m/Y à H:i', strtotime($r['date_repas'])) ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-user" style="margin-right:4px;"></i>
                        Utilisateur #<?= $r['id_utilisateur'] ?>
                        &nbsp;·&nbsp;
                        <?= $r['nb_aliments'] ?> aliment(s)
                    </p>

                    <!-- Aliments du repas (via jointure) -->
                    <?php if (!empty($alimRepas)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
                    <?php foreach ($alimRepas as $al):
                        $c = typeConfig($al['type']);
                    ?>
                        <span style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;padding:3px 10px;border-radius:99px;font-size:11px;">
                            <?= htmlspecialchars($al['nom']) ?> <span style="opacity:.65;">(<?= $al['quantite'] ?>g)</span>
                        </span>
                    <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="font-size:12px;color:#c4bdb5;font-style:italic;">Aucun aliment enregistré.</p>
                    <?php endif; ?>
                </div>

                <!-- Totaux nutritionnels (colonne droite) -->
                <?php if (!empty($totaux) && $totaux['total_calories'] > 0): ?>
                <div style="min-width:200px;">
                    <p style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Apports nutritionnels</p>
                    <?php
                    $tots = [
                        ['Calories',  round($totaux['total_calories'],1),  'kcal'],
                        ['Protéines', round($totaux['total_proteines'],1), 'g'],
                        ['Glucides',  round($totaux['total_glucides'],1),  'g'],
                        ['Lipides',   round($totaux['total_lipides'],1),   'g'],
                        ['Fibres',    round($totaux['total_fibres'],1),    'g'],
                        ['CO₂ total', round($totaux['total_co2'],2),      'kg'],
                    ];
                    foreach ($tots as [$l,$v,$u]):
                    ?>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px;">
                        <span style="color:#6b7280;"><?= $l ?></span>
                        <span style="font-weight:600;color:var(--vert);"><?= $v ?> <?= $u ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div id="noResult" style="display:none;text-align:center;padding:40px;color:#9ca3af;">Aucun résultat.</div>
        </div>
        <?php endif; ?>

        <div style="margin-top:12px;font-size:11px;color:#9ca3af;text-align:right;"><?= count($tousRepas) ?> repas · GaiaLumen Back Office © <?= date('Y') ?></div>
    </div>
</div>

<script>
/* Recherche filtrante (hors CRUD) */
function applySearch() {
    const q = document.getElementById('sq').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#repasList [data-nom]');
    let vis = 0;
    rows.forEach(r => {
        const show = !q || r.dataset.nom.includes(q);
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    document.getElementById('noResult').style.display = vis === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
