<?php
/* ============================================================
   view/BackOffice/bo_repaslist.php — BACK OFFICE Repas

   RÔLE : Vue READ des repas pour l'administrateur.
          Affiche un tableau avec la jointure repas ↔ aliments.

   CORRECTION MVC :
     Les données ($tousRepas, $repaDetails) sont préparées
     par le Controller (repascontroller.php?action=list_back)
     et passées ici via include.
     Cette vue NE fait PAS de requêtes SQL directement.

   URL D'ACCÈS : controller/repascontroller.php?action=list_back
   ============================================================ */

/* Sécurité : si la vue est appelée sans le Controller */
if (!isset($tousRepas)) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../model/repas_model.php';
    require_once __DIR__ . '/../../helpers/aliment_helpers.php';
    require_once __DIR__ . '/../../helpers/repas_helpers.php';
    $repasModel  = new Repas();
    $tousRepas   = $repasModel->getAll();
    $repaDetails = [];
    foreach ($tousRepas as $r) {
        $id = (int)$r['id_repas'];
        $repaDetails[$id] = [
            'aliments' => $repasModel->getAlimentsOfRepas($id),
            'totaux'   => $repasModel->getTotauxNutritionnels($id),
        ];
    }
} else {
    require_once __DIR__ . '/../../helpers/aliment_helpers.php';
    require_once __DIR__ . '/../../helpers/repas_helpers.php';
}
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

        /* ── Sidebar (identique au bo_alimentlist) ──────────── */
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

        /* ── Layout ─────────────────────────────────────────── */
        .main-content { margin-left:240px; flex:1; display:flex; flex-direction:column; }
        .topbar { background:linear-gradient(135deg,var(--vert) 0%,#11241f 100%);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50; }
        .hero { background-image:linear-gradient(rgba(26,55,47,.72),rgba(26,55,47,.72)),url('assets/images/1000051721.jpg');background-size:cover;background-position:center;padding:40px 28px 32px; }

        /* ── Tableau principal (comme bo_alimentlist) ───────── */
        .tbl-wrap { background:white; border-radius:20px; overflow:hidden; border:1px solid rgba(26,55,47,.1); }

        /* En-tête du tableau — 8 colonnes */
        .thead {
            display:grid;
            grid-template-columns: 60px 1.6fr 1fr 0.8fr 0.8fr 0.7fr 0.7fr 1.6fr;
            align-items:center; padding:11px 16px;
            background:var(--vert); color:white;
            font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        }
        /* Ligne de données */
        .trow {
            display:grid;
            grid-template-columns: 60px 1.6fr 1fr 0.8fr 0.8fr 0.7fr 0.7fr 1.6fr;
            align-items:start; padding:13px 16px;
            border-bottom:1px solid #f4ede4; transition:background .12s;
        }
        .trow:hover { background:#faf7f3; }
        .trow:last-child { border-bottom:none; }

        /* ── Badges ─────────────────────────────────────────── */
        .badge { display:inline-block;font-size:9.5px;padding:2px 8px;border-radius:99px;font-weight:600;white-space:nowrap; }

        /* ── Barre CO₂ ──────────────────────────────────────── */
        .cobar { height:4px; border-radius:2px; background:#ede9e3; overflow:hidden; margin-top:3px; }
        .cofil { height:100%; border-radius:2px; }

        /* ── Score écologique dans le tableau ───────────────── */
        .eco-circle { width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column;border:2px solid currentColor;flex-shrink:0; }

        /* ── Jointure : aliments dans le tableau ────────────── */
        .alim-pill { display:inline-flex;align-items:center;gap:4px;font-size:10px;padding:2px 8px;border-radius:99px;margin:2px 2px 0 0;font-weight:500; }

        /* ── Recherche ──────────────────────────────────────── */
        .si { padding:8px 14px 8px 36px;border-radius:99px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);font-size:12px;color:white;outline:none;width:210px; }
        .si::placeholder { color:rgba(255,255,255,.5); }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:11px; }

        /* ── Stats hero ─────────────────────────────────────── */
        .sp { background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* ── Curseur ────────────────────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }
    </style>
</head>
<body>
<div id="cur"></div><div id="curt"></div>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="../../index.html" class="sidebar-logo">
        <svg width="30" height="30" viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
            <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs>
            <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
        </svg>
        <span>GaiaLumen</span>
    </a>
    <div class="sidebar-badge">⚙ Back Office</div>
    <div class="sidebar-section">Module Repas</div>
    <a href="bo_alimentlist.php"  class="nav-item"><i class="fas fa-carrot"></i> Aliments</a>
    <a href="bo_repaslist.php"    class="nav-item active"><i class="fas fa-utensils"></i> Repas</a>
    <div class="sidebar-section">Site</div>
    <a href="../../index.html"                 class="nav-item"><i class="fas fa-home"></i> Accueil</a>
    <a href="../FrontOffice/fo_repaslist.php"  class="nav-item"><i class="fas fa-eye"></i> Vue utilisateur</a>
    <div class="sidebar-footer">GaiaLumen © <?= date('Y') ?></div>
</aside>

<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="text-white text-sm font-medium flex items-center gap-2">
            <i class="fas fa-shield-alt text-[#a78bfa]"></i>
            Administration › Consultation des Repas
        </div>
        <span style="font-size:11px;color:rgba(255,255,255,.5);font-style:italic;">
            Lecture seule — les repas sont créés par les utilisateurs
        </span>
    </div>

    <!-- Hero -->
    <section class="hero">
        <h1 class="hf" style="font-size:44px;color:white;line-height:1;margin-bottom:8px;">Consultation des Repas</h1>
        <p style="font-size:13px;color:rgba(255,255,255,.7);margin-bottom:16px;">
            Vue administrative — tous les repas créés par les utilisateurs.
        </p>
        <?php
        $totalRepas   = count($tousRepas);
        $totalAlimSel = array_sum(array_column($tousRepas, 'nb_aliments'));
        ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
            <span class="sp"><b><?= $totalRepas ?></b> repas au total</span>
            <span class="sp"><b><?= $totalAlimSel ?></b> sélections d'aliments</span>
        </div>

        <!-- Recherche (hors CRUD — filtre affichage uniquement) -->
        <div class="sw">
            <i class="fas fa-search"></i>
            <input id="sq" type="text" class="si" placeholder="Rechercher un repas…" oninput="applySearch()">
        </div>
    </section>

    <!-- TABLEAU DES REPAS avec jointure visible -->
    <div style="padding:20px 28px 40px;">
        <div class="tbl-wrap" style="overflow-x:auto;">

            <?php if (empty($tousRepas)): ?>
            <div style="padding:48px;text-align:center;color:#9ca3af;">
                <i class="fas fa-utensils" style="font-size:2.5rem;display:block;margin-bottom:14px;color:#d0c8be;"></i>
                Aucun repas n'a encore été créé par les utilisateurs.
            </div>
            <?php else: ?>

            <!-- En-tête du tableau -->
            <div class="thead" style="min-width:900px;">
                <div style="text-align:center;">#</div>
                <div>Repas</div>
                <div>Date & heure</div>
                <div style="text-align:center;">Calories</div>
                <div style="text-align:center;">Protéines</div>
                <div style="text-align:center;">Score éco.</div>
                <div>CO₂ total</div>
                <!-- Colonne JOINTURE : composition du repas -->
                <div>Composition (aliments)</div>
            </div>

            <!-- Corps du tableau -->
            <div id="tbody" style="min-width:900px;">
            <?php foreach ($tousRepas as $r):
                $id     = (int) $r['id_repas'];
                $detail = $repaDetails[$id] ?? ['aliments'=>[], 'totaux'=>[]];
                $alims  = $detail['aliments'];
                $tot    = $detail['totaux'];

                /* Score écologique via helpers */
                $score  = scoreEcologique($tot);
                $lbl    = labelEcologique($score);
            ?>
            <div class="trow" data-nom="<?= strtolower(htmlspecialchars($r['nom_repas'])) ?>">

                <!-- ID -->
                <div style="text-align:center;">
                    <span style="font-size:11px;background:#f4ede4;color:#6b7280;padding:3px 8px;border-radius:6px;font-weight:600;">#<?= $id ?></span>
                </div>

                <!-- Nom du repas + utilisateur -->
                <div>
                    <p style="font-size:13px;font-weight:600;color:var(--vert);margin:0;"><?= htmlspecialchars($r['nom_repas']) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">
                        <i class="fas fa-user" style="margin-right:3px;"></i>Utilisateur #<?= $r['id_utilisateur'] ?>
                        · <?= $r['nb_aliments'] ?> aliment(s)
                    </p>
                </div>

                <!-- Date -->
                <div>
                    <p style="font-size:12px;font-weight:500;color:var(--vert);margin:0;">
                        <?= date('d/m/Y', strtotime($r['date_repas'])) ?>
                    </p>
                    <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">
                        <?= date('H:i', strtotime($r['date_repas'])) ?>
                    </p>
                </div>

                <!-- Calories totales -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;margin:0;">
                        <?= !empty($tot) ? round($tot['total_calories'],0) : '—' ?>
                    </p>
                    <p style="font-size:9px;color:#9ca3af;margin:0;">kcal</p>
                </div>

                <!-- Protéines totales -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:700;color:#1a5fa8;font-family:'Cormorant Garamond',serif;margin:0;">
                        <?= !empty($tot) ? round($tot['total_proteines'],1) : '—' ?>
                    </p>
                    <p style="font-size:9px;color:#9ca3af;margin:0;">g</p>
                </div>

                <!-- Score écologique (Fonctionnalité 1) -->
                <div style="display:flex;align-items:center;justify-content:center;">
                    <div class="eco-circle" style="color:<?= $lbl['color'] ?>;border-color:<?= $lbl['color'] ?>;">
                        <span style="font-size:13px;font-weight:700;font-family:'Cormorant Garamond',serif;"><?= $score ?></span>
                        <span style="font-size:7px;opacity:.7;">/100</span>
                    </div>
                </div>

                <!-- CO₂ avec barre -->
                <div>
                    <?php if (!empty($tot)): ?>
                    <p style="font-size:12px;font-weight:600;color:<?= $lbl['color'] ?>;margin:0;">
                        <?= round($tot['total_co2'],2) ?> kg
                    </p>
                    <div class="cobar">
                        <div class="cofil" style="width:<?= min(100,$tot['total_co2']/5*100) ?>%;background:<?= $lbl['bar'] ?>;"></div>
                    </div>
                    <span style="font-size:9px;background:<?= $lbl['bg'] ?>;color:<?= $lbl['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $lbl['emoji'] ?> <?= $lbl['label'] ?></span>
                    <?php else: ?>
                    <p style="font-size:11px;color:#c4bdb5;">—</p>
                    <?php endif; ?>
                </div>

                <!-- JOINTURE VISIBLE : aliments qui composent le repas
                     Résultat de : SELECT a.*, ra.quantite
                                   FROM repas_aliments ra
                                   INNER JOIN aliments a ON ra.id_aliment = a.id_aliment
                                   WHERE ra.id_repas = ?                              -->
                <div>
                    <?php if (empty($alims)): ?>
                        <p style="font-size:11px;color:#c4bdb5;font-style:italic;">Aucun aliment</p>
                    <?php else: ?>
                        <?php foreach ($alims as $al):
                            $c = typeConfig($al['type']); ?>
                        <span class="alim-pill" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;">
                            <?= htmlspecialchars($al['nom']) ?>
                            <span style="opacity:.65;">(<?= $al['quantite'] ?>g)</span>
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>

            <div id="noResult" style="display:none;padding:40px;text-align:center;color:#9ca3af;">
                <i class="fas fa-filter" style="display:block;margin-bottom:10px;font-size:1.8rem;color:#d0c8be;"></i>
                Aucun résultat.
            </div>
            </div>

            <!-- Pied de tableau -->
            <div style="padding:10px 16px;background:#f9f6f2;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#9ca3af;" id="rowCount"><?= count($tousRepas) ?> repas</span>
                <span style="font-size:11px;color:#9ca3af;">GaiaLumen Back Office © <?= date('Y') ?></span>
            </div>
            <?php endif; ?>
        </div>


    </div>

</div>

<script>
/* Curseur */
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button').forEach(el=>{el.addEventListener('mouseenter',()=>c.classList.add('h'));el.addEventListener('mouseleave',()=>c.classList.remove('h'));});
})();

/* Recherche (hors CRUD — filtre affichage uniquement) */
function applySearch() {
    const q   = document.getElementById('sq').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.trow');
    let vis = 0;
    rows.forEach(r => {
        const show = !q || r.dataset.nom.includes(q);
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    document.getElementById('noResult').style.display = vis === 0 ? 'block' : 'none';
    document.getElementById('rowCount').textContent = vis + ' repas';
}
</script>
</body>
</html>
