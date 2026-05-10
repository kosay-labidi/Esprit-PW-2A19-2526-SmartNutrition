<?php
/* ============================================================
   view/FrontOffice/fo_alimentdetail.php — FRONT OFFICE
   RÔLE : Fiche complète d'UN seul aliment.

   VALEUR AJOUTÉE par rapport à fo_alimentlist.php :
     - fo_alimentlist affiche un RÉSUMÉ de TOUS les aliments
       (nom, type, 4 macros, CO₂, prix) → vue de parcours
     - fo_alimentdetail affiche le DÉTAIL COMPLET d'UN aliment
       (toutes les valeurs nutritionnelles avec graphique donut,
        sucre, sodium, vitamines, label écologique, origine,
        allergènes, impact CO₂ détaillé) → vue de consultation
     - Contient le bouton "Ajouter à un repas" qui renvoie
       vers fo_repaslist.php avec l'aliment pré-sélectionné

   COMMENT Y ACCÉDER :
     En cliquant sur une carte dans fo_alimentlist.php
     URL : fo_alimentdetail.php?id=X

   ARCHITECTURE MVC :
     Model      : model/aliment.php  (getById)
     View       : ce fichier
     Controller : aucun (lecture seule, pas d'action CRUD)
   ============================================================ */

/* ----------------------------------------------------------
   SECTION 1 — DÉPENDANCES (chemins relatifs depuis FrontOffice/)
   ../../ remonte de FrontOffice/ → view/ → gestion-repas/
   ---------------------------------------------------------- */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/aliment.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';

/* ----------------------------------------------------------
   SECTION 2 — RÉCUPÉRATION DE L'ALIMENT (Model)
   On récupère UN seul aliment via son id passé en GET.
   Si l'id est invalide → redirection vers la liste.
   ---------------------------------------------------------- */
$alimentModel = new Aliment();
$a = $alimentModel->getById((int)($_GET['id'] ?? 0));

if (!$a) {
    header("Location: fo_alimentlist.php");
    exit;
}

/* ----------------------------------------------------------
   SECTION 3 — CALCULS POUR L'AFFICHAGE (helpers)
   Ces fonctions sont dans helpers/aliment_helpers.php
   ---------------------------------------------------------- */
$c   = typeConfig($a['type']);          /* couleurs selon le type */
$co2 = co2Config((float)$a['co2']);    /* couleur/label CO₂      */
$ns  = nutriScore($a);                  /* grade Nutri-Score A→E  */
$mp  = macroPercents($a);               /* % calorique macros     */
$svg = alimentSVG($a['nom'], $a['type'], $c, 90); /* icône SVG   */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · <?= htmlspecialchars($a['nom']) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js pour le graphique donut des macros -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    <style>
        /* ── Polices GaiaLumen ──────────────────────────────── */
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');

        /* ── Palette officielle ─────────────────────────────── */
        :root {
            --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa;
            --bg-page:#f4ede4; --bg-card:white; --bg-card2:#f9f6f2;
            --bg-input:white; --text-main:#1a372f; --text-muted:#6b7280;
            --text-label:#1a372f; --border-card:#ede8e0; --border-input:#d0c8be;
            --navbar-bg:linear-gradient(90deg,#1a372f 0%,#11241f 100%);
        }
        body.dark {
            --bg-page:#0f1623; --bg-card:#1a2433; --bg-card2:#1e2a3a;
            --bg-input:#1e2a3a; --text-main:#e2e8f0; --text-muted:#64748b;
            --text-label:#94a3b8; --border-card:#243040; --border-input:#2d3f54;
            --navbar-bg:linear-gradient(90deg,#0d1520 0%,#0a1018 100%);
        }
        body { background:var(--bg-page)!important; color:var(--text-main)!important; transition:background .3s,color .3s; }
        body.dark * { color:inherit; }

        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; } /* titres */
        

        /* ── Curseur personnalisé ─────────────────────────── */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* ── Navbar horizontale ─────────────────────────────── */
        .navbar { background:var(--navbar-bg); position:sticky; top:0; z-index:50; transition:background .3s; }

        /* ── Hero de l'aliment ──────────────────────────────── */
        .aliment-hero {
            background: linear-gradient(135deg, <?= $c['bg'] ?> 0%, white 70%);
            border-bottom: 1px solid <?= $c['stroke'] ?>33;
        }

        /* ── Cartes de section ──────────────────────────────── */
        .card { background:var(--bg-card); border-radius:20px; border:1px solid var(--border-card); padding:24px; }

        /* ── Badges ─────────────────────────────────────────── */
        .badge { display:inline-block; font-size:11px; padding:3px 10px; border-radius:99px; font-weight:600; white-space:nowrap; }
        .cbadge { font-size:11px; padding:2px 8px; border-radius:5px; background:var(--bg-page); color:#5a5850; }

        /* ── Barres de macronutriments ──────────────────────── */
        .mrow   { display:flex; align-items:center; gap:10px; margin-bottom:11px; }
        .mtrack { flex:1; height:8px; border-radius:4px; background:#ede8e0; overflow:hidden; }
        .mfill  { height:100%; border-radius:4px; transition:width .9s ease; }

        /* ── Boîtes statistiques (hero) ─────────────────────── */
        .stat-box { background:var(--bg-page); border-radius:14px; padding:14px 18px; text-align:center; }
        .stat-val { font-size:24px; font-weight:700; font-family:'Cormorant Garamond',serif; }
        .stat-lbl { font-size:11px; color:var(--text-muted); margin-top:2px; }

        /* ── Graphique donut (macros) ───────────────────────── */
        .donut-wrap   { position:relative; width:150px; height:150px; flex-shrink:0; }
        .donut-center { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; }

        /* ── Nutri-Score lettre ─────────────────────────────── */
        .ns-box { width:60px; height:60px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:700; font-family:'Cormorant Garamond',serif; }
    

        /* ── Dark mode pour pages aliments ─── */
        body.dark [style*="background:var(--bg-card)"]{background:var(--bg-card)!important;}
        body.dark [style*="background:var(--bg-card2)"]{background:#1a2433!important;}
        body.dark [style*="background:var(--bg-card2)"]{background:#1e2a3a!important;}
        body.dark [style*="background:var(--bg-page)"]{background:#1a2433!important;}
        body.dark [style*="color:var(--text-muted)"]{color:var(--text-muted)!important;}
        body.dark [style*="border:1px solid var(--border-card)"]{border-color:var(--border-card)!important;}
        body.dark [style*="border:1.5px solid var(--border-card)"]{border-color:var(--border-card)!important;}
        body.dark input,body.dark select,body.dark textarea{background:var(--bg-input)!important;color:var(--text-main)!important;border-color:var(--border-input)!important;}
        body.dark .lbody{background:var(--bg-card)!important;border-color:var(--border-card)!important;}
        body.dark .lrow{border-color:var(--border-card)!important;}
        body.dark .lrow:hover{background:var(--bg-card2)!important;}
        body.dark .lhead{background:var(--bg-card2)!important;border-color:var(--border-card)!important;}
        /* Modal langue */
        #langModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:var(--modal-overlay,rgba(0,0,0,.5));z-index:9999;align-items:center;justify-content:center;}
        #langModal.open{display:flex;}
        #langBox{background:var(--bg-card,white);border-radius:24px;width:90%;max-width:560px;max-height:80vh;overflow:hidden;border:1px solid var(--border-card,#ede8e0);box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column;}
        #langHead{padding:20px 24px 14px;border-bottom:1px solid var(--border-card,#ede8e0);display:flex;align-items:center;justify-content:space-between;}
        #langHead h2{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--text-main,#1a372f);margin:0;}
        #langSearch{width:100%;padding:10px 14px;border:1.5px solid var(--border-input,#d0c8be);border-radius:10px;font-size:13px;background:var(--bg-input,white);color:var(--text-main,#1a372f);outline:none;margin-bottom:12px;}
        #langList{overflow-y:auto;padding:16px 20px 20px;flex:1;}
        .region-title{font-size:11px;font-weight:700;color:var(--text-muted,#6b7280);text-transform:uppercase;letter-spacing:.06em;margin:12px 0 6px;}
        .lang-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;}
        .lang-btn{padding:8px 10px;border-radius:10px;border:1px solid var(--border-card,#ede8e0);background:var(--bg-card2,white);cursor:pointer;display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text-main,#1a372f);font-family:'Lato',sans-serif;transition:all .15s;}
        .lang-btn:hover{border-color:#a78bfa;}
        .lang-btn.active{border-color:#60a5fa;color:#60a5fa;font-weight:700;background:rgba(96,165,250,.12);}
        .lang-btn .flag{font-size:16px;}

        
        body.dark .lrow:hover { background:var(--bg-card2)!important; }
        body.dark .lbody, body.dark .lhead { background:var(--bg-card)!important; border-color:var(--border-card)!important; }
        body.dark .lrow { border-color:var(--border-card)!important; }
        body.dark input, body.dark select, body.dark textarea { background:var(--bg-input)!important; color:var(--text-main)!important; border-color:var(--border-input)!important; }
        
        body.dark .navbar { background:var(--navbar-bg) !important; }
        
        body.dark .active { border-color:#60a5fa !important; background:rgba(96,165,250,.12) !important; color:#60a5fa !important; }
        body.dark a.btn-add-repas { background:#60a5fa !important; border-color:#60a5fa !important; }
        
        body.dark .alim-header, body.dark [style*="background:white;border-radius:20px"] { background:var(--bg-card2) !important; }
        body.dark [style*="background:white"][style*="border-radius"] { background:var(--bg-card2) !important; }
        body.dark h2[style*="color:var(--vert)"] { color:var(--text-main) !important; }
        body.dark h3[style*="color:var(--vert)"] { color:var(--text-main) !important; }
        body.dark [style*="color:var(--vert)"] { color:var(--text-main) !important; }
        body.dark .prix-val, body.dark [style*="font-size:2.5rem"] { color:var(--text-main) !important; }
        body.dark [style*="font-size:2rem"][style*="font-weight:700"] { color:var(--text-main) !important; }
        body.dark a[href="fo_alimentlist.php"] { color:var(--text-main) !important; border-color:var(--border-card) !important; }
        body.dark a[href="fo_alimentlist.php"]:hover { border-color:#60a5fa !important; color:#60a5fa !important; }
        
        body.dark a[href^="fo_repaslist.php?add_aliment"] { background:#60a5fa !important; }
        
        body.dark p[style*="font-weight:700"][style*="color:var(--vert)"] { color:#93c5fd !important; }
        body.dark h2[style*="color:var(--vert)"] { color:#93c5fd !important; }
        body.dark i[style*="color:var(--vert)"] { color:#93c5fd !important; }
        
        body.dark h1.hf { color:#cbd5e1 !important; }
        </style>

<script>

/* Dark mode synchronisé depuis fo_repaslist via localStorage */
(function() {
    if (localStorage.getItem('gl-dark') === '1') {
        document.documentElement.classList.add('dark-pending');
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('gl-dark') === '1') {
        document.body.classList.add('dark');
    }
    document.documentElement.classList.remove('dark-pending');
    
    /* Écouter les changements depuis d'autres onglets */
    window.addEventListener('storage', function(e) {
        if (e.key === 'gl-dark') {
            if (e.newValue === '1') document.body.classList.add('dark');
            else document.body.classList.remove('dark');
        }
    });
});
function translatePage(code, label, flag) {
    /* ── 1. Collecter tous les nœuds texte visibles ── */
    var walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_ELEMENT,
        null,
        false
    );
    var toTranslate = [];
    var seen = new Set();
    while (walker.nextNode()) {
        var el = walker.currentNode;
        /* Ignorer les éléments hors-traduction */
        if (el.closest('#langModal') || el.closest('script') || el.closest('style')
            || el.id === 'darkIcon' || el.id === 'currentLangLabel'
            || el.id === 'langActiveBadge' || el.id === 'cultureTypeLabel') continue;
        /* Prendre seulement les feuilles (pas d'enfants éléments) */
        var hasChildElements = false;
        for (var i = 0; i < el.childNodes.length; i++) {
            if (el.childNodes[i].nodeType === 1) { hasChildElements = true; break; }
        }
        if (hasChildElements) continue;
        var txt = el.textContent.trim();
        if (txt.length < 2 || txt.length > 150) continue;
        /* Ignorer les chiffres/codes purs */
        if (/^[\d\s.,;:!?%°€$+\-\/\\]+$/.test(txt)) continue;
        if (seen.has(el)) continue;
        seen.add(el);
        toTranslate.push(el);
    }

    if (toTranslate.length === 0) {
        showToastLang('Rien à traduire.', '#9ca3af');
        return;
    }

    /* ── 2. Dédupliquer les textes ── */
    var uniqueTexts = [];
    var textToEls   = {};
    toTranslate.forEach(function(el) {
        var txt = el.textContent.trim();
        if (!textToEls[txt]) {
            textToEls[txt] = [];
            uniqueTexts.push(txt);
        }
        textToEls[txt].push(el);
    });

    showToastLang('\u23f3 Traduction en cours\u2026', '#7c5cbf');

    /* ── 3. Envoyer par lots de 50 au Controller ── */
    var BATCH   = 50;
    var batchIdx = 0;
    var done     = 0;
    var total    = Math.ceil(uniqueTexts.length / BATCH);

    function applyTranslations(originals, translations) {
        originals.forEach(function(orig, i) {
            var tr = (translations[i] || '').trim();
            if (!tr || tr === orig) return;
            var els = textToEls[orig] || [];
            els.forEach(function(el) {
                if (!el.dataset.orig) el.dataset.orig = orig;
                el.textContent = tr;
            });
        });
    }

    function sendBatch(batchTexts, callback) {
        fetch('../../controller/translate_controller.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                texts:       batchTexts,
                targetLang:  code,
                targetLabel: label
            })
        })
        .then(function(r) {
            /* Lire le texte brut pour pouvoir déboguer même si ce n'est pas du JSON valide */
            return r.text().then(function(txt) {
                return { status: r.status, text: txt };
            });
        })
        .then(function(res) {
            if (res.status !== 200) {
                showToastLang('⚠️ Erreur serveur ' + res.status, '#e24b4a');
                callback(new Error(res.text));
                return;
            }
            var data;
            try { data = JSON.parse(res.text); }
            catch(e) {
                showToastLang('⚠️ Réponse invalide du serveur', '#e24b4a');
                console.error('[Traduction] Réponse non-JSON:', res.text.slice(0, 200));
                callback(e);
                return;
            }
            if (data.error) {
                showToastLang('⚠️ ' + data.error, '#e24b4a');
                console.warn('[Traduction] Erreur controller:', data.error);
            }
            if (data.translations && data.translations.length > 0) {
                applyTranslations(batchTexts, data.translations);
            }
            callback(null);
        })
        .catch(function(err) {
            showToastLang('⚠️ Connexion impossible au serveur', '#e24b4a');
            console.error('[Traduction] Fetch erreur:', err);
            callback(err);
        });
    }

    function nextBatch() {
        if (batchIdx >= uniqueTexts.length) {
            showToastLang('\u2705 Traduit en ' + label + ' ' + flag, '#1a372f');
            return;
        }
        var batch = uniqueTexts.slice(batchIdx, batchIdx + BATCH);
        batchIdx += BATCH;
        done++;
        if (total > 1) showToastLang('\u23f3 Traduction ' + done + '/' + total + '\u2026', '#7c5cbf');
        sendBatch(batch, nextBatch);
    }

    nextBatch();
}


function showToastLang(msg, color) {
    var t = document.getElementById('toastLang');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toastLang';
        t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);padding:10px 20px;border-radius:12px;color:white;font-size:13px;font-weight:600;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,.2);transition:opacity .3s;font-family:Lato,sans-serif;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.background = color || '#1a372f';
    t.style.opacity = '1';
    clearTimeout(t._t);
    t._t = setTimeout(function() { t.style.opacity = '0'; }, 3000);
}

/* ── Auto-traduction au chargement si langue sauvegardée ── */
document.addEventListener('DOMContentLoaded', function() {
    var savedLang = localStorage.getItem('gl-lang');
    if (savedLang && savedLang !== 'fr') {
        var LANGUAGES_DATA = [
            {code:'fr',label:'Français',flag:'🇫🇷'},
            {code:'ar',label:'العربية',flag:'🇸🇦'},
            {code:'en',label:'English',flag:'🇬🇧'},
            {code:'de',label:'Deutsch',flag:'🇩🇪'},
            {code:'es',label:'Español',flag:'🇪🇸'},
            {code:'it',label:'Italiano',flag:'🇮🇹'},
            {code:'pt',label:'Português',flag:'🇵🇹'},
            {code:'nl',label:'Nederlands',flag:'🇳🇱'},
            {code:'pl',label:'Polski',flag:'🇵🇱'},
            {code:'ru',label:'Русский',flag:'🇷🇺'},
            {code:'sv',label:'Svenska',flag:'🇸🇪'},
            {code:'tr',label:'Türkçe',flag:'🇹🇷'},
            {code:'zh-CN',label:'中文',flag:'🇨🇳'},
            {code:'ja',label:'日本語',flag:'🇯🇵'},
            {code:'ko',label:'한국어',flag:'🇰🇷'},
            {code:'ar',label:'العربية',flag:'🇸🇦'},
            {code:'he',label:'עברית',flag:'🇮🇱'},
            {code:'hi',label:'हिन्दी',flag:'🇮🇳'},
            {code:'vi',label:'Tiếng Việt',flag:'🇻🇳'},
            {code:'id',label:'Indonesia',flag:'🇮🇩'},
            {code:'pt-br',label:'Português BR',flag:'🇧🇷'},
        ];
        var found = LANGUAGES_DATA.find(function(l){ return l.code === savedLang; });
        var lbl   = found ? found.label : savedLang;
        var flag  = found ? found.flag  : '🌐';
        /* Petit délai pour que le DOM soit stable */
        setTimeout(function() {
            translatePage(savedLang, lbl, flag);
        }, 300);
    }
});

</script>
</head>
<body>
<div id="cur"></div>
<div id="curt"></div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 4 — NAVBAR HORIZONTALE
     ══════════════════════════════════════════════════════════ -->
<nav class="navbar text-white shadow-xl">
    <div class="max-w-screen-xl mx-auto px-8 py-4 flex items-center justify-between">
        <a href="../../index.html" class="flex items-center gap-3">
            <svg width="32" height="32" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
                <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#60a5fa"/><stop offset="100%" stop-color="#a78bfa"/></radialGradient></defs>
                <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
            </svg>
            <span class="hf text-3xl tracking-tighter">GaiaLumen</span>
        </a>
        <ul class="flex items-center gap-7 text-sm font-medium">
            <li><a href="../../index.html"   class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
            <li><a href="fo_repaslist.php"   class="hover:text-[#a78bfa] transition-colors">Mes Repas</a></li>
            <li><a href="#"                  class="hover:text-[#a78bfa] transition-colors">Défis</a></li>
        </ul>
        <div style="display:flex;align-items:center;gap:10px;">
            <!-- Mon compte -->
            <a href="#" style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:500;color:rgba(255,255,255,.85);background:rgba(255,255,255,.1);padding:7px 16px;border-radius:99px;text-decoration:none;">
                <i class="fas fa-user-circle"></i> Mon compte
            </a>
        </div>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     SECTION 5 — FIL D'ARIANE
     Accueil > Aliments > [Nom de l'aliment]
     ══════════════════════════════════════════════════════════ -->
<div class="max-w-screen-xl mx-auto px-8 pt-5">
    <nav style="font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
        <a href="fo_alimentlist.php" style="color:var(--vert);text-decoration:none;font-weight:600;">Aliments</a>
        <span>/</span>
        <span><?= htmlspecialchars($a['nom']) ?></span>
    </nav>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 6 — HERO DE L'ALIMENT
     Affiche le nom, les 8 stats clés et le Nutri-Score.
     Couleur de fond adaptée au type d'aliment (helpers).
     ══════════════════════════════════════════════════════════ -->
<div class="aliment-hero" style="margin-top:10px;">
    <div class="max-w-screen-xl mx-auto px-8 py-10">
        <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;">

            <!-- Icône SVG de l'aliment (générée par alimentSVG()) -->
            <div style="background:<?= $c['bg'] ?>;border-radius:22px;padding:14px;border:2px solid <?= $c['stroke'] ?>44;flex-shrink:0;">
                <?= $svg ?>
            </div>

            <!-- Informations principales -->
            <div style="flex:1;min-width:260px;">

                <!-- Badges type + catégorie + label + origine -->
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                    <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    <?php if (!empty($a['label_ecologique'])): ?>
                    <span class="badge" style="background:#e8f0e9;color:var(--vert);">🌱 <?= htmlspecialchars($a['label_ecologique']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($a['origine'])): ?>
                    <span class="badge" style="background:var(--bg-page);color:var(--vert);">📍 <?= htmlspecialchars($a['origine']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Nom de l'aliment (titre Cormorant Garamond) -->
                <h1 class="hf" style="font-size:54px;color:var(--vert);line-height:1;margin:0 0 16px;">
                    <?= htmlspecialchars($a['nom']) ?>
                </h1>

                <!-- ── 8 stats clés en grille ───────────────────
                     VALEUR AJOUTÉE vs fo_alimentlist :
                     La liste ne montre que calories + 4 macros.
                     Ici on affiche TOUTES les valeurs y compris
                     sucre, sodium et prix.
                     ─────────────────────────────────────────── -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(90px,1fr));gap:10px;max-width:620px;">
                    <?php
                    $stats = [
                        ['Calories',   number_format($a['calories'],1),  'kcal/100g', 'var(--vert)'],
                        ['Protéines',  number_format($a['proteines'],1), 'g/100g',    '#1a5fa8'],
                        ['Glucides',   number_format($a['glucides'],1),  'g/100g',    '#8a6510'],
                        ['Lipides',    number_format($a['lipides'],1),   'g/100g',    '#7c5cbf'],
                        ['Fibres',     number_format($a['fibres'],1),    'g/100g',    'var(--vert)'],
                        ['Sucre',      number_format($a['sucre'],1),     'g/100g',    'var(--vert)'],
                        ['Sodium',     number_format($a['sodium'],1),    'mg/100g',   'var(--vert)'],
                        ['Prix',       $a['prix']>0 ? number_format($a['prix'],2) : '—', 'TND/kg', 'var(--vert)'],
                    ];
                    foreach ($stats as [$lbl,$val,$unit,$color]):
                    ?>
                    <div class="stat-box">
                        <div class="stat-val" style="color:<?= $color ?>;"><?= $val ?></div>
                        <div class="stat-lbl"><?= $lbl ?></div>
                        <div style="font-size:10px;color:#b0a898;"><?= $unit ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nutri-Score (calculé par nutriScore() dans helpers) -->
            <div style="text-align:center;flex-shrink:0;">
                <div class="ns-box" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;margin:0 auto 6px;">
                    <?= $ns['grade'] ?>
                </div>
                <p style="font-size:11px;color:var(--text-muted);">Nutri-Score</p>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 7 — DÉTAIL COMPLET (2 colonnes)
     VALEUR AJOUTÉE principale vs fo_alimentlist :
       - Graphique donut interactif (Chart.js)
       - Toutes les barres nutritionnelles détaillées
       - Section allergènes visuellement mise en avant
       - Section impact écologique détaillée
       - Bouton "Ajouter à un repas" fonctionnel
     ══════════════════════════════════════════════════════════ -->
<div class="max-w-screen-xl mx-auto px-8 py-10">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- ── COLONNE GAUCHE ─────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Carte : Valeurs nutritionnelles complètes + donut -->
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:18px;">
                    Valeurs nutritionnelles
                    <span style="font-size:14px;font-weight:400;color:var(--text-muted);font-family:'Lato',sans-serif;">pour 100g</span>
                </h2>

                <!-- Graphique donut : répartition calorique des macros
                     VALEUR AJOUTÉE : absent de fo_alimentlist       -->
                <div style="display:flex;align-items:center;gap:24px;margin-bottom:22px;">
                    <div class="donut-wrap">
                        <canvas id="donut" width="150" height="150"></canvas>
                        <div class="donut-center">
                            <p style="font-size:18px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;margin:0;">
                                <?= number_format($a['calories'],0) ?>
                            </p>
                            <p style="font-size:10px;color:var(--text-muted);margin:0;">kcal</p>
                        </div>
                    </div>
                    <!-- Légende du donut -->
                    <div style="flex:1;">
                        <?php
                        $legend = [
                            ['#60a5fa', 'Protéines', $mp['prot']],
                            ['#c9a44a', 'Glucides',  $mp['gluc']],
                            ['#a78bfa', 'Lipides',   $mp['lip']],
                        ];
                        foreach ($legend as [$col,$lbl,$pct]):
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <div style="width:10px;height:10px;border-radius:50%;background:<?= $col ?>;flex-shrink:0;"></div>
                            <span style="font-size:12px;color:var(--text-muted);flex:1;"><?= $lbl ?></span>
                            <span style="font-size:13px;font-weight:700;color:var(--vert);"><?= $pct ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Barres détaillées de TOUS les nutriments
                     VALEUR AJOUTÉE : fo_alimentlist ne montre
                     que 4 barres. Ici on a tout + sodium.       -->
                <?php
                $macros = [
                    ['Protéines',  $a['proteines'], 50,   '#60a5fa', 'g/100g'],
                    ['Glucides',   $a['glucides'],  100,  '#c9a44a', 'g/100g'],
                    ['dont sucre', $a['sucre'],     100,  '#e8c06a', 'g/100g'],
                    ['Lipides',    $a['lipides'],   50,   '#a78bfa', 'g/100g'],
                    ['Fibres',     $a['fibres'],    30,   '#1a372f', 'g/100g'],
                    ['Sodium',     $a['sodium'],    2300, '#888780', 'mg/100g'],
                ];
                foreach ($macros as [$lbl, $val, $max, $color, $unit]):
                    $pct    = $max > 0 ? min(100, $val / $max * 100) : 0;
                ?>
                <div class="mrow">
                    <span style="font-size:12px;color:var(--text-muted);width:100px;flex-shrink:0;<?= str_starts_with($lbl,'dont') ? 'font-style:italic;font-size:11px;' : '' ?>"><?= $lbl ?></span>
                    <div class="mtrack"><div class="mfill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
                    <span style="font-size:12px;font-weight:700;color:var(--vert);width:75px;text-align:right;flex-shrink:0;">
                        <?= number_format($val,1) ?> <?= $unit ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Vitamines (absent de fo_alimentlist) -->
                <?php if (!empty($a['vitamines'])): ?>
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f4ede4;">
                    <p style="font-size:11px;color:var(--text-muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;font-weight:600;">Vitamines</p>
                    <p style="font-size:14px;font-weight:500;color:var(--vert);"><?= htmlspecialchars($a['vitamines']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Carte : Allergènes (absent de fo_alimentlist)
                 VALEUR AJOUTÉE : information de sécurité critique -->
            <?php if (!empty($a['allergenes'])): ?>
            <div class="card" style="border-color:#c09090;background:#fdf5f5;">
                <h2 class="hf" style="font-size:22px;color:#8a2020;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:16px;"></i> Allergènes
                </h2>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach (explode(',', $a['allergenes']) as $al): ?>
                    <span style="background:#faeaea;color:#8a2020;padding:5px 14px;border-radius:99px;font-size:13px;font-weight:500;">
                        <?= htmlspecialchars(trim($al)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── COLONNE DROITE ─────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Carte : Impact écologique détaillé
                 VALEUR AJOUTÉE : fo_alimentlist montre juste un
                 badge CO₂ (Faible/Moyen/Élevé). Ici on a la
                 valeur exacte, la barre, la description.        -->
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:18px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-leaf" style="font-size:16px;color:var(--vert);"></i> Impact écologique
                </h2>

                <!-- Bloc CO₂ -->
                <div style="background:<?= $co2['bg'] ?>;border-radius:14px;padding:18px;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                        <div>
                            <p style="font-size:28px;font-weight:700;color:<?= $co2['color'] ?>;font-family:'Cormorant Garamond',serif;margin:0;">
                                <?= number_format($a['co2'],2) ?> kg CO₂eq
                            </p>
                            <p style="font-size:12px;color:<?= $co2['color'] ?>;opacity:.75;margin:3px 0 0;">par kg d'aliment</p>
                        </div>
                        <span style="background:<?= $co2['color'] ?>;color:white;padding:6px 16px;border-radius:99px;font-size:13px;font-weight:600;">
                            <?= $co2['label'] ?>
                        </span>
                    </div>
                    <!-- Barre de progression CO₂ -->
                    <div style="height:8px;border-radius:4px;background:rgba(0,0,0,.1);overflow:hidden;">
                        <div style="width:<?= $co2['pct'] ?>%;height:100%;background:<?= $co2['color'] ?>;border-radius:4px;"></div>
                    </div>
                    <p style="font-size:12px;color:<?= $co2['color'] ?>;margin:8px 0 0;opacity:.8;"><?= $co2['desc'] ?></p>
                </div>

                <!-- Label écologique -->
                <?php if (!empty($a['label_ecologique'])): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#e8f0e9;border-radius:12px;margin-bottom:10px;">
                    <i class="fas fa-certificate" style="color:var(--vert);font-size:22px;"></i>
                    <div>
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;">Label écologique</p>
                        <p style="font-size:13px;color:#4a7a50;margin:2px 0 0;"><?= htmlspecialchars($a['label_ecologique']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Origine -->
                <?php if (!empty($a['origine'])): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--sable);border-radius:12px;">
                    <i class="fas fa-map-marker-alt" style="color:var(--vert);font-size:20px;"></i>
                    <div>
                        <p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;">Origine</p>
                        <p style="font-size:13px;color:var(--text-muted);margin:2px 0 0;"><?= htmlspecialchars($a['origine']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Carte : Prix -->
            <?php if ($a['prix'] > 0): ?>
            <div class="card">
                <h2 class="hf" style="font-size:24px;color:var(--vert);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-tag" style="font-size:16px;"></i> Prix indicatif
                </h2>
                <div style="display:flex;align-items:baseline;gap:6px;">
                    <span style="font-size:40px;font-weight:700;color:var(--vert);font-family:'Cormorant Garamond',serif;">
                        <?= number_format($a['prix'],2) ?>
                    </span>
                    <span style="font-size:15px;color:var(--text-muted);">TND / kg</span>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">Prix moyen indicatif sur le marché tunisien</p>
            </div>
            <?php endif; ?>

            <!-- ══ BOUTON AJOUTER À UN REPAS ════════════════
                 VALEUR AJOUTÉE principale :
                 Depuis fo_alimentlist on ne peut QUE consulter.
                 Depuis fo_alimentdetail on peut AJOUTER
                 directement cet aliment à un repas.
                 Ce bouton renvoie vers fo_repaslist.php avec
                 l'id de l'aliment pré-sélectionné.
                 ══════════════════════════════════════════════ -->
            <div class="card" style="background:linear-gradient(135deg,<?= $c['bg'] ?> 0%,white 100%);border-color:<?= $c['stroke'] ?>44;">
                <h2 class="hf" style="font-size:22px;color:var(--vert);margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-utensils" style="font-size:15px;"></i> Utiliser cet aliment
                </h2>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;line-height:1.6;">
                    Ajoutez <strong><?= htmlspecialchars($a['nom']) ?></strong> à l'un de vos repas
                    pour suivre votre apport nutritionnel et votre impact CO₂.
                </p>
                <!-- Lien vers fo_repaslist.php avec l'aliment pré-sélectionné -->
                <a href="fo_repaslist.php?add_aliment=<?= $a['id_aliment'] ?>"
                   style="display:inline-flex;align-items:center;gap:8px;background:var(--vert);color:white;padding:11px 22px;border-radius:99px;font-size:13px;font-weight:600;text-decoration:none;">
                    <i class="fas fa-plus"></i> Ajouter à un repas
                </a>
            </div>

            <!-- Lien retour vers la liste -->
                        <a href="fo_alimentlist.php"
               style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border-radius:16px;border:1.5px solid #d0c8be;background:var(--bg-card);font-size:14px;font-weight:500;color:#4b5563;text-decoration:none;transition:all .15s;"
               onmouseover="var d=document.body.classList.contains('dark');this.style.borderColor=d?'#60a5fa':'var(--vert)';this.style.color=d?'#60a5fa':'var(--vert)'"
               onmouseout="this.style.borderColor='#d0c8be';this.style.color='#4b5563'">
                <i class="fas fa-arrow-left" style="font-size:12px;"></i> Retour à la liste des aliments
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SECTION 8 — JAVASCRIPT
     Curseur + graphique donut Chart.js
     ══════════════════════════════════════════════════════════ -->
<script>
/* ── Curseur personnalisé ─────────────────────────────────── */
(function(){
    const c=document.getElementById('cur'), t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button').forEach(el=>{
        el.addEventListener('mouseenter',()=>c.classList.add('h'));
        el.addEventListener('mouseleave',()=>c.classList.remove('h'));
    });
})();

/* ── Graphique donut — répartition calorique des macros ─────
   Les données viennent de macroPercents() calculé en PHP.
   VALEUR AJOUTÉE : visualisation absente de fo_alimentlist   */
new Chart(document.getElementById('donut').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Protéines', 'Glucides', 'Lipides'],
        datasets: [{
            data: [<?= $mp['prot'] ?>, <?= $mp['gluc'] ?>, <?= $mp['lip'] ?>],
            backgroundColor: ['#60a5fa', '#c9a44a', '#a78bfa'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label} : ${ctx.raw}%` } }
        },
        animation: { duration: 900 }
    }
});
</script>

<!-- MODALE LANGUE -->
<div id="langModal">
    <div id="langBox">
        <div id="langHead">
            <h2>Choisir une langue</h2>
            <button onclick="closeLang()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted);line-height:1;padding:0;">&#x2715;</button>
        </div>
        <div id="langList">
            <input id="langSearch" type="text" placeholder="Rechercher une langue..." oninput="filterLangs(this.value)"/>
            <div id="langsContainer"></div>
        </div>
    </div>
</div>
<div id="google_translate_element" style="display:none;"></div>

<!-- ══════════════════════════════════════════════════════════
     MODALE MODIFICATION ALIMENT
     ══════════════════════════════════════════════════════════ -->




</body>
</html>
