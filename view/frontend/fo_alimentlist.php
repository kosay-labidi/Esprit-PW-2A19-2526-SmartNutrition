<?php



require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/alimentcontroller.php';
require_once __DIR__ . '/../../helpers/aliment_helpers.php';


global $pdo;
$aliments = aliment_getAll($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen · Nos Aliments</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');

        
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
            --hero-overlay:rgba(8,15,24,.55);
        }
        body { background:var(--bg-page)!important; color:var(--text-main)!important; transition:background .3s,color .3s; }
        body.dark * { color:inherit; }

        * { font-family:'Lato',sans-serif; box-sizing:border-box; margin:0; padding:0; }
        .hf { font-family:'Cormorant Garamond',serif; }
        

        /* Curseur personnalisé */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        /* Navbar horizontale */
        .navbar { background:var(--navbar-bg); position:sticky; top:0; z-index:50; transition:background .3s; }

        /* Contenu pleine largeur */
        .main-content { width:100%; }

        
        .hero {
            background: linear-gradient(rgba(26,55,47,.68), rgba(26,55,47,.68)) center/cover no-repeat,
                        url('../backend/assets/images/1000051721.jpg') center/cover no-repeat;
            padding: 52px 0 40px;
        }
        body.dark .hero {
            background: linear-gradient(rgba(8,15,24,.60), rgba(8,15,24,.60)) center/cover no-repeat,
                        url('../backend/assets/images/1000051721.jpg') center/cover no-repeat !important;
        }

        
        .fb { padding:7px 16px;border-radius:99px;border:1.5px solid var(--border-input);background:var(--bg-card);font-size:12px;color:var(--text-main);cursor:pointer;transition:all .18s;font-family:'Lato',sans-serif;font-weight:500; }
        .fb.on,.fb:hover { background:var(--vert);color:white;border-color:var(--vert); }

        /* ─ Cartes aliments  */
        .card { background:var(--bg-card);border-radius:20px;border:1px solid var(--border-card);overflow:hidden;transition:transform .2s,box-shadow .2s;cursor:pointer;text-decoration:none;display:block;color:inherit; }
        .card:hover { transform:translateY(-5px);box-shadow:0 16px 40px rgba(26,55,47,.13); }

        .badge  { display:inline-block;font-size:10px;padding:2px 9px;border-radius:99px;font-weight:600;white-space:nowrap; }
        .cbadge { font-size:10px;padding:1px 7px;border-radius:4px;background:var(--bg-page);color:#5a5850; }

        
        .mbar { height:5px;border-radius:3px;background:#ede8e0;overflow:hidden;margin:3px 0 7px; }
        .mfil { height:100%;border-radius:3px; }

        /*  Recherche  */
        .si { padding:10px 16px 10px 40px;border-radius:99px;border:1.5px solid var(--border-input);font-size:13px;width:220px;outline:none;font-family:'Lato',sans-serif;background:var(--bg-card); }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px; }

        /* Stat*/
        .sp { background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:4px; }
        .sp b { font-weight:700; }

        /* Toggle vue grille/liste  */
        .vbtn { padding:8px 14px;border-radius:10px;border:1.5px solid var(--border-input);background:var(--bg-card);cursor:pointer;font-size:12px;color:var(--text-muted);transition:all .15s; }
        .vbtn.on { background:var(--vert);color:white;border-color:var(--vert); }

        /*Grille de cartes */
        .gview { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px; }
        .gview.hide { display:none; }

        /* Vue liste*/
        .lview { display:none; }
        .lview.show { display:block; }
        .lhead { display:grid;grid-template-columns:48px 1.8fr .8fr .8fr .8fr .8fr .8fr 1.4fr;gap:0;padding:11px 16px;background:var(--vert);color:white;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-radius:16px 16px 0 0; }
        .lrow  { display:grid;grid-template-columns:48px 1.8fr .8fr .8fr .8fr .8fr .8fr 1.4fr;align-items:center;gap:0;padding:12px 16px;border-bottom:1px solid #f4ede4;transition:background .12s;text-decoration:none;color:inherit; }
        .lrow:hover { background:var(--bg-card2); }
        .lrow:last-child { border-bottom:none; }
        .lbody { background:var(--bg-card);border-radius:0 0 16px 16px;overflow:hidden;border:1px solid var(--border-card);border-top:none; }
    

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
        .lang-btn.active{border-color:var(--text-main);font-weight:700;}
        .lang-btn .flag{font-size:16px;}

        
        body.dark .lrow:hover { background:var(--bg-card2)!important; }
        body.dark .lbody, body.dark .lhead { background:var(--bg-card)!important; border-color:var(--border-card)!important; }
        body.dark .lrow { border-color:var(--border-card)!important; }
        body.dark input, body.dark select, body.dark textarea { background:var(--bg-input)!important; color:var(--text-main)!important; border-color:var(--border-input)!important; }
        
        body.dark .navbar { background:var(--navbar-bg) !important; }
        
        body.dark .hero { background-image:linear-gradient(rgba(8,15,24,.60),rgba(8,15,24,.60)),url('../backend/assets/images/1000051721.jpg') !important; }
        
        body.dark .on, body.dark .fb:hover { background:#60a5fa !important; border-color:#60a5fa !important; }
        body.dark .active { border-color:#60a5fa !important; background:rgba(96,165,250,.12) !important; color:#60a5fa !important; }
        
        body.dark .lrow [style*="color:var(--vert)"] { color:var(--text-main) !important; }
        body.dark .card [style*="color:var(--vert)"] { color:var(--text-main) !important; }
        body.dark [style*="color:var(--vert)"] { color:var(--text-main) !important; }
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



<!-- NAVBAR HORIZONTALE -->
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

<div class="main-content">


    <section class="hero">
      <div class="max-w-screen-xl mx-auto px-8">
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);padding:4px 14px;border-radius:99px;font-size:11px;color:rgba(255,255,255,.85);margin-bottom:14px;">
            <i class="fas fa-leaf" style="color:var(--violet);"></i> BIBLIOTHÈQUE NUTRITIONNELLE
        </span>
        <h1 class="hf" style="font-size:54px;color:white;line-height:1;margin-bottom:10px;">Nos Aliments</h1>
        <p style="font-size:15px;color:rgba(255,255,255,.75);margin-bottom:20px;max-width:520px;line-height:1.6;">
            Explorez notre sélection d'aliments sains, locaux et durables pour composer vos repas avec conscience.
        </p>

        <!-- Statistiques -->
        <?php
        $nb     = count($aliments);
        $nbt    = count(array_unique(array_column($aliments,'type')));
        $nb_bio = count(array_filter($aliments, fn($a) => !empty($a['label_ecologique'])));
        ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;">
            <span class="sp"><b><?= $nb ?></b> aliments</span>
            <span class="sp"><b><?= $nbt ?></b> catégories</span>
            <span class="sp"><b><?= $nb_bio ?></b> labellisés</span>
        </div>

        <!-- FONCTIONNALITÉS HORS CRUD -->
        <div style="background:rgba(255,255,255,.08);border-radius:16px;padding:16px 20px;border:1px solid rgba(255,255,255,.12);">
            <p style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em;">Filtrer et rechercher</p>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;" id="fbar">
               
                <button class="fb on" data-type="tous"               onclick="setFilter(this)">Tous</button>
                <button class="fb"    data-type="légume"             onclick="setFilter(this)">🌿 Légumes</button>
                <button class="fb"    data-type="fruit"              onclick="setFilter(this)">🍊 Fruits</button>
                <button class="fb"    data-type="protéines animales" onclick="setFilter(this)">🥩 Protéines</button>
                <button class="fb"    data-type="céréale"            onclick="setFilter(this)">🌾 Céréales</button>
                <button class="fb"    data-type="légumineuse"        onclick="setFilter(this)">🫘 Légumineuses</button>
                <button class="fb"    data-type="produit laitier"    onclick="setFilter(this)">🥛 Laitiers</button>
                <button class="fb"    data-type="épice"              onclick="setFilter(this)">🌶 Épices</button>
                <button class="fb"    data-type="huile"              onclick="setFilter(this)">🫙 Huiles</button>

                <!-- Champ de recherche par nom -->
                <div class="sw" style="margin-left:auto;">
                    <i class="fas fa-search" style="color:rgba(255,255,255,.5);"></i>
                    <input id="sq" type="text" class="si" placeholder="Rechercher…" oninput="applyFilters()"
                           style="border-color:rgba(255,255,255,.25);background:rgba(255,255,255,.1);color:white;">
                </div>
            </div>

            <!-- Boutons toggle vue grille/liste -->
            <div style="margin-top:10px;display:flex;gap:6px;justify-content:flex-end;">
                <button id="bg" class="vbtn on" onclick="setView('grid')"><i class="fas fa-th-large"></i></button>
                <button id="bl" class="vbtn"    onclick="setView('list')"><i class="fas fa-list"></i></button>
            </div>
        </div>
      </div><!-- fin max-w-screen-xl hero -->
    </section>




    <div class="max-w-screen-xl mx-auto px-8" style="padding-top:20px;padding-bottom:40px;">

        <!-- VUE GRILLE  -->
        <div id="vg" class="gview">
        <?php foreach ($aliments as $a):
            $c  = typeConfig($a['type']);
            $sv = alimentSVG($a['nom'], $a['type'], $c, 48);
            $co = co2Config((float)$a['co2']);
            $ns = nutriScore($a);
        ?>
        
        <a href="fo_alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="card"
           data-type="<?= htmlspecialchars($a['type']) ?>"
           data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

            <!-- En-tête carte : icône SVG + nom + badges + nutriscore -->
            <div style="padding:18px 18px 12px;display:flex;align-items:center;gap:12px;">
                <?= $sv ?>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:14px;font-weight:700;color:var(--text-main);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($a['nom']) ?></p>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <!-- Nutri-Score calculé par la fonction nutriScore() dans helpers -->
                <div style="width:30px;height:30px;border-radius:7px;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;" title="Nutri-Score <?= $ns['grade'] ?>"><?= $ns['grade'] ?></div>
            </div>

            <!-- Corps carte -->
            <div style="padding:0 18px 14px;">
                <?php
                
                $mc = [
                    ['Protéines', $a['proteines'], 50,  '#60a5fa', 'g'],
                    ['Glucides',  $a['glucides'],  100, '#c9a44a', 'g'],
                    ['Lipides',   $a['lipides'],   50,  '#a78bfa', 'g'],
                    ['Fibres',    $a['fibres'],    30,  '#1a372f', 'g'],
                ];
                foreach ($mc as [$l,$v,$m,$col,$u]):
                    $p = $m > 0 ? min(100, $v/$m*100) : 0;
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);">
                        <span><?= $l ?></span>
                        <span style="font-weight:600;color:var(--text-main);"><?= number_format($v,1) ?><?= $u ?></span>
                    </div>
                    <div class="mbar"><div class="mfil" style="width:<?= $p ?>%;background:<?= $col ?>;"></div></div>
                </div>
                <?php endforeach; ?>


                <div style="display:flex;gap:12px;margin-top:3px;">
                    <span style="font-size:10px;color:var(--text-muted);">Sucre: <b style="color:var(--text-main);"><?= number_format($a['sucre'],1) ?>g</b></span>
                    <span style="font-size:10px;color:var(--text-muted);">Sodium: <b style="color:var(--text-main);"><?= number_format($a['sodium'],1) ?>mg</b></span>
                </div>
            </div>

            
            <div style="padding:12px 18px;border-top:1px solid #f4ede4;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <p style="font-size:18px;font-weight:700;color:var(--text-main);font-family:'Cormorant Garamond',serif;margin:0;"><?= number_format($a['calories'],0) ?> <span style="font-size:10px;font-weight:400;color:var(--text-muted);font-family:'Lato',sans-serif;">kcal/100g</span></p>
                    <?php if ($a['prix'] > 0): ?>
                    <p style="font-size:11px;color:var(--text-muted);margin:2px 0 0;"><?= number_format($a['prix'],2) ?> TND/kg</p>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:9.5px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:3px 8px;border-radius:99px;font-weight:600;">CO₂ <?= $co['label'] ?></span>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:10px;color:var(--text-muted);margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <!-- État vide après filtrage -->
        <div id="emptyG" style="display:none;grid-column:1/-1;text-align:center;padding:60px;color:var(--text-muted);">
            <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:12px;color:#d0c8be;"></i>
            Aucun aliment trouvé pour ce filtre.
        </div>
        </div>

        <!--  VUE LISTE  -->
        <div id="vl" class="lview">
            <!-- En-tête de la vue liste -->
            <div class="lhead">
                <div></div>
                <div>Aliment</div>
                <div style="text-align:center">Cal.</div>
                <div style="text-align:center">Prot.</div>
                <div style="text-align:center">Gluc.</div>
                <div style="text-align:center">Lip.</div>
                <div style="text-align:center">Prix</div>
                <div>CO₂ / Infos</div>
            </div>

            <div class="lbody" id="lbody">
            <?php foreach ($aliments as $a):
                $c  = typeConfig($a['type']);
                $sv = alimentSVG($a['nom'], $a['type'], $c, 40);
                $co = co2Config((float)$a['co2']);
                $ns = nutriScore($a);
            ?>
            <!-- Ligne cliquable vers le détail -->
            <a href="fo_alimentdetail.php?id=<?= $a['id_aliment'] ?>" class="lrow"
               data-type="<?= htmlspecialchars($a['type']) ?>"
               data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">
                <div style="display:flex;align-items:center;justify-content:center;"><?= $sv ?></div>
                <div style="padding-left:10px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <p style="font-size:13px;font-weight:700;color:var(--text-main);margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                        <span style="font-size:11px;font-weight:700;background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>;padding:1px 6px;border-radius:5px;"><?= $ns['grade'] ?></span>
                    </div>
                    <div style="display:flex;gap:4px;margin-top:3px;">
                        <span class="badge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;font-size:9.5px;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                    </div>
                </div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= number_format($a['calories'],0) ?></p><p style="font-size:9px;color:var(--text-muted);margin:0;">kcal</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#1a5fa8;margin:0;"><?= number_format($a['proteines'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#8a6510;margin:0;"><?= number_format($a['glucides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:600;color:#7c5cbf;margin:0;"><?= number_format($a['lipides'],1) ?>g</p></div>
                <div style="text-align:center;"><p style="font-size:13px;font-weight:700;color:var(--vert);margin:0;"><?= $a['prix']>0 ? number_format($a['prix'],2).' TND' : '—' ?></p></div>
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                        <span style="font-size:10px;color:var(--text-muted);"><?= number_format($a['co2'],2) ?> kg</span>
                        <span style="font-size:9px;background:<?= $co['bg'] ?>;color:<?= $co['color'] ?>;padding:1px 6px;border-radius:99px;font-weight:600;"><?= $co['label'] ?></span>
                    </div>
                    <div style="height:4px;border-radius:2px;background:#ede8e0;overflow:hidden;">
                        <div style="width:<?= $co['pct'] ?>%;height:100%;background:<?= $co['color'] ?>;border-radius:2px;"></div>
                    </div>
                    <?php if (!empty($a['origine'])): ?>
                    <p style="font-size:9.5px;color:var(--text-muted);margin:3px 0 0;">📍 <?= htmlspecialchars($a['origine']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <div id="emptyL" style="display:none;padding:40px;text-align:center;color:var(--text-muted);">Aucun résultat.</div>
            </div>

            <!-- Pied tableau liste -->
            <div style="padding:10px 16px;background:#f9f6f2;border-radius:0 0 16px 16px;border:1px solid var(--border-card);border-top:none;display:flex;justify-content:space-between;">
                <span style="font-size:11px;color:var(--text-muted);" id="rc"><?= count($aliments) ?> aliment(s)</span>
                <span style="font-size:11px;color:var(--text-muted);">GaiaLumen © <?= date('Y') ?></span>
            </div>
        </div>

    </div>
</div>


<script>

/* Curseur personnalisé  */
(function(){
    const c=document.getElementById('cur'), t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button,input').forEach(el=>{
        el.addEventListener('mouseenter',()=>c.classList.add('h'));
        el.addEventListener('mouseleave',()=>c.classList.remove('h'));
    });
})();

/*  FONCTIONNALITÉS HORS CRUD */

let cv = 'grid'; /* vue courante */

/* Bascule grille ↔ liste */
function setView(v) {
    cv = v;
    document.getElementById('vg').classList.toggle('hide', v === 'list');
    document.getElementById('vl').classList.toggle('show', v === 'list');
    document.getElementById('bg').classList.toggle('on', v === 'grid');
    document.getElementById('bl').classList.toggle('on', v === 'list');
    applyFilters(); /* recalcule le compteur */
}

/* Active le bouton de filtre cliqué */
function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}


function applyFilters() {
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    let vg = 0, vl = 0;

    /* Filtre les cartes (vue grille) */
    document.querySelectorAll('#vg .card').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vg++;
    });

    /* Filtre les lignes (vue liste) */
    document.querySelectorAll('#lbody .lrow').forEach(r => {
        const ok = (type==='tous'||r.dataset.type===type) && (!query||r.dataset.nom.includes(query));
        r.style.display = ok ? '' : 'none';
        if (ok) vl++;
    });

    document.getElementById('emptyG').style.display = vg === 0 ? 'block' : 'none';
    document.getElementById('emptyL').style.display = vl === 0 ? 'block' : 'none';
    const rc = document.getElementById('rc');
    if (rc) rc.textContent = (cv==='grid' ? vg : vl) + ' aliment(s)';
}
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
</body>
</html>
