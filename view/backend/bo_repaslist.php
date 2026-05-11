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
    require_once __DIR__ . '/../../controller/repascontroller.php';
    require_once __DIR__ . '/../../helpers/aliment_helpers.php';
    require_once __DIR__ . '/../../helpers/repas_helpers.php';
    global $pdo;
    $tousRepas   = repas_getAll($pdo);
    $repaDetails = [];
    foreach ($tousRepas as $r) {
        $id = (int)$r['id_repas'];
        $repaDetails[$id] = [
            'aliments' => repas_getAlimentsOfRepas($pdo, $id),
            'totaux'   => repas_getTotauxNutritionnels($pdo, $id),
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
    
        /* ── Dark mode variables ──────────────────────────────── */
        :root {
            --bg-page:#f4ede4; --bg-card:white; --bg-card2:#f9f6f2;
            --bg-input:white; --text-main:#1a372f; --text-muted:#6b7280;
            --border-card:#ede8e0; --border-input:#d0c8be;
        }
        body.dark {
            --bg-page:#0f1623; --bg-card:#1a2433; --bg-card2:#1e2a3a;
            --bg-input:#1e2a3a; --text-main:#e2e8f0; --text-muted:#64748b;
            --border-card:#243040; --border-input:#2d3f54;
        }
        body { background:var(--bg-page); color:var(--text-main);
               display:flex; min-height:100vh; transition:background .3s,color .3s; }

        /* ── Dark mode overrides BO ───────────────────────────── */
        body.dark .topbar  { background:linear-gradient(135deg,#0d1520 0%,#0a1018 100%) !important; }
        body.dark .sidebar { background:linear-gradient(180deg,#0d1520 0%,#08100c 100%) !important; }
        body.dark .sidebar-footer, body.dark .sidebar-section { border-color:rgba(255,255,255,.06) !important; }
        body.dark .main-content { background:var(--bg-page); }
        body.dark .hero { background:linear-gradient(rgba(8,15,24,.60),rgba(8,15,24,.60))
                          center/cover no-repeat,
                          url('assets/images/1000051721.jpg') center/cover no-repeat !important; }
        body.dark .tbl-wrap { background:var(--bg-card) !important; border-color:var(--border-card) !important; }
        body.dark .thead  { background:var(--bg-card2) !important; color:var(--text-muted) !important; }
        body.dark .trow   { border-color:var(--border-card) !important; color:var(--text-main) !important; }
        body.dark .trow:hover { background:var(--bg-card2) !important; }
        body.dark .nav-item { color:rgba(255,255,255,.6) !important; }
        body.dark .nav-item:hover { background:rgba(255,255,255,.07) !important; color:white !important; }
        body.dark .nav-item.active { background:rgba(96,165,250,.18) !important; color:#60a5fa !important; }
        body.dark .modal-box { background:var(--bg-card) !important; }
        body.dark .mi { background:var(--bg-input) !important; color:var(--text-main) !important; border-color:var(--border-input) !important; }
        body.dark select.mi { background:var(--bg-input) !important; color:var(--text-main) !important; }
        body.dark [style*="background:white"] { background:var(--bg-card) !important; }
        body.dark [style*="color:#1a372f"]   { color:var(--text-main) !important; }
        body.dark [style*="color:#6b7280"]   { color:var(--text-muted) !important; }
        body.dark [style*="border:1px solid #ede8e0"] { border-color:var(--border-card) !important; }

        /* ── Modale langue ────────────────────────────────────── */
        #langModal { display:none;position:fixed;top:0;left:0;width:100%;height:100%;
                     background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center; }
        #langModal.open { display:flex; }
        #langBox { background:var(--bg-card);border-radius:24px;width:90%;max-width:560px;
                   max-height:80vh;overflow:hidden;border:1px solid var(--border-card);
                   box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column; }
        #langHead { padding:20px 24px 14px;border-bottom:1px solid var(--border-card);
                    display:flex;align-items:center;justify-content:space-between; }
        #langHead h2 { font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--text-main);margin:0; }
        #langSearch { width:100%;padding:10px 14px;border:1.5px solid var(--border-input);
                      border-radius:10px;font-size:13px;background:var(--bg-input);
                      color:var(--text-main);outline:none;margin-bottom:12px; }
        #langList { overflow-y:auto;padding:16px 20px 20px;flex:1; }
        .region-title { font-size:11px;font-weight:700;color:var(--text-muted);
                        text-transform:uppercase;letter-spacing:.06em;margin:12px 0 6px; }
        .lang-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:6px; }
        .lang-btn { padding:8px 10px;border-radius:10px;border:1px solid var(--border-card);
                    background:var(--bg-card);cursor:pointer;display:flex;align-items:center;
                    gap:7px;font-size:12px;color:var(--text-main);font-family:'Lato',sans-serif; }
        .lang-btn:hover { border-color:#a78bfa; }
        .lang-btn.active { border-color:#1a372f;font-weight:700; }
        .lang-btn .flag { font-size:16px; }

        body.dark #breadcrumb-liste { color:var(--text-main) !important; }
        
        body.dark .trow * { color:var(--text-main) !important; }
        body.dark .trow .badge { color:#94a3b8 !important; }
        body.dark .trow .alim-pill { color:var(--text-main) !important; background:rgba(96,165,250,.1) !important; }
        
        /* ── Badges CO2/score → texte noir lisible en dark ── */
        body.dark span[style*="font-size:9px"][style*="background:"] { color:#111111 !important; font-weight:700 !important; }
        
        body.dark .badge-id { color:#111111 !important; }
        </style>
<script>
/* ════════════════════════════════════════════════
   DARK MODE — Dark B (Ardoise slate)
   Fonctions globales, init dans DOMContentLoaded
   ════════════════════════════════════════════════ */
function toggleDark() {
    var d = document.body.classList.toggle('dark');
    localStorage.setItem('gl-dark', d ? '1' : '0');
    updateDarkUI(d);
    /* Mettre à jour badge couleur selon mode */
    var savedLang = localStorage.getItem('gl-lang');
    if (savedLang && savedLang !== 'fr') {
        var btn = document.getElementById('langBtn');
        if (btn) {
            btn.style.background = d ? 'rgba(96,165,250,.12)' : 'rgba(255,255,255,.18)';
            btn.style.borderColor = d ? 'rgba(96,165,250,.5)' : 'rgba(96,165,250,.6)';
            btn.style.color = d ? '#60a5fa' : 'white';
        }
        var badge = document.getElementById('langActiveBadge');
        if (badge) badge.style.borderColor = d ? '#0d1520' : 'rgba(26,55,47,.9)';
    }
}
function updateDarkUI(d) {
    var i = document.getElementById('darkIcon');
    var l = document.getElementById('darkLabel');
    if (i) i.className = d ? 'fas fa-sun'  : 'fas fa-moon';
    if (l) l.textContent = d ? 'Clair' : 'Sombre';
}

/* ════════════════════════════════════════════════
   TRADUCTION — Concept 4 : Drapeau + Nom + Badge
   ════════════════════════════════════════════════ */
var LANGUAGES = [
  {region:'Fréquentes', langs:[
    {code:'fr',    label:'Français',      flag:'🇫🇷'},
    {code:'ar',    label:'العربية',       flag:'🇸🇦'},
    {code:'en',    label:'English',       flag:'🇬🇧'},
    {code:'de',    label:'Deutsch',       flag:'🇩🇪'},
    {code:'es',    label:'Español',       flag:'🇪🇸'},
    {code:'it',    label:'Italiano',      flag:'🇮🇹'},
  ]},
  {region:'Europe', langs:[
    {code:'pt',    label:'Português',     flag:'🇵🇹'},
    {code:'nl',    label:'Nederlands',    flag:'🇳🇱'},
    {code:'pl',    label:'Polski',        flag:'🇵🇱'},
    {code:'ru',    label:'Русский',       flag:'🇷🇺'},
    {code:'sv',    label:'Svenska',       flag:'🇸🇪'},
    {code:'no',    label:'Norsk',         flag:'🇳🇴'},
    {code:'da',    label:'Dansk',         flag:'🇩🇰'},
    {code:'fi',    label:'Suomi',         flag:'🇫🇮'},
    {code:'el',    label:'Ελληνικά',      flag:'🇬🇷'},
    {code:'cs',    label:'Čeština',       flag:'🇨🇿'},
    {code:'ro',    label:'Română',        flag:'🇷🇴'},
    {code:'hu',    label:'Magyar',        flag:'🇭🇺'},
    {code:'uk',    label:'Українська',    flag:'🇺🇦'},
    {code:'tr',    label:'Türkçe',        flag:'🇹🇷'},
  ]},
  {region:'Moyen-Orient & Afrique', langs:[
    {code:'he',    label:'עברית',         flag:'🇮🇱'},
    {code:'fa',    label:'فارسی',         flag:'🇮🇷'},
    {code:'sw',    label:'Kiswahili',     flag:'🇰🇪'},
    {code:'am',    label:'አማርኛ',         flag:'🇪🇹'},
  ]},
  {region:'Asie', langs:[
    {code:'zh-CN', label:'中文 简体',      flag:'🇨🇳'},
    {code:'zh-TW', label:'中文 繁體',      flag:'🇹🇼'},
    {code:'ja',    label:'日本語',         flag:'🇯🇵'},
    {code:'ko',    label:'한국어',         flag:'🇰🇷'},
    {code:'hi',    label:'हिन्दी',        flag:'🇮🇳'},
    {code:'bn',    label:'বাংলা',         flag:'🇧🇩'},
    {code:'vi',    label:'Tiếng Việt',    flag:'🇻🇳'},
    {code:'th',    label:'ภาษาไทย',       flag:'🇹🇭'},
    {code:'id',    label:'Indonesia',     flag:'🇮🇩'},
    {code:'ms',    label:'Melayu',        flag:'🇲🇾'},
  ]},
  {region:'Amériques', langs:[
    {code:'pt-br', label:'Português BR',  flag:'🇧🇷'},
    {code:'ht',    label:'Kreyòl',        flag:'🇭🇹'},
  ]},
];

var currentLang = 'fr';

/* Concept 4 : met à jour le bouton + badge */
function updateLangBtn(code, label, flag) {
    var lbl   = document.getElementById('currentLangLabel');
    var badge = document.getElementById('langActiveBadge');
    var btn   = document.getElementById('langBtn');
    var dark  = document.body && document.body.classList.contains('dark');
    var actif = (code !== 'fr');
    if (lbl)   lbl.innerHTML = flag + ' <span>' + label + '</span>';
    if (badge) badge.style.display = actif ? 'block' : 'none';
    if (btn) {
        if (actif) {
            btn.style.background   = dark ? 'rgba(96,165,250,.12)' : 'rgba(255,255,255,.18)';
            btn.style.borderColor  = dark ? 'rgba(96,165,250,.5)'  : 'rgba(96,165,250,.6)';
            btn.style.color        = dark ? '#60a5fa'              : 'white';
        } else {
            btn.style.background   = 'rgba(255,255,255,.08)';
            btn.style.borderColor  = 'rgba(255,255,255,.2)';
            btn.style.color        = 'rgba(255,255,255,.85)';
        }
    }
}

function buildLangList(f) {
    var ct = document.getElementById('langsContainer');
    if (!ct) return;
    var fl = (f || '').toLowerCase(), html = '';
    LANGUAGES.forEach(function(g) {
        var ls = g.langs.filter(function(l) {
            return !fl || l.label.toLowerCase().includes(fl) || l.code.includes(fl);
        });
        if (!ls.length) return;
        html += '<p class="region-title">' + g.region + '</p><div class="lang-grid">';
        ls.forEach(function(l) {
            var act = l.code === currentLang ? ' active' : '';
            html += '<button class="lang-btn' + act + '" onclick="selectLang(\''
                 + l.code + '\',\'' + l.label + '\',\'' + l.flag + '\')">'
                 + '<span class="flag">' + l.flag + '</span>' + l.label + '</button>';
        });
        html += '</div>';
    });
    ct.innerHTML = html || '<p style="color:var(--text-muted);text-align:center;padding:20px;">Aucune langue trouvée.</p>';
}

function filterLangs(q) { buildLangList(q); }

function openLang() {
    var m = document.getElementById('langModal');
    if (m) { m.classList.add('open'); buildLangList(''); }
}
function closeLang() {
    var m = document.getElementById('langModal');
    if (m) m.classList.remove('open');
}

function selectLang(code, label, flag) {
    currentLang = code;
    localStorage.setItem('gl-lang', code);
    updateLangBtn(code, label, flag);
    closeLang();
    if (code === 'fr') {
        document.querySelectorAll('[data-orig]').forEach(function(el) {
            el.textContent = el.dataset.orig;
        });
        showToastLang('Langue : Français', '#1a372f');
        return;
    }
    translatePage(code, label, flag);
}

function translatePage(code, label, flag) {
    var elements = [];
    var selectors = ['h1','h2','h3','button:not([onclick*="toggleDark"]):not([onclick*="openLang"]):not([onclick*="closeLang"]):not([onclick*="selectLang"])','p','label','span:not(.flag)','[placeholder]'];
    selectors.forEach(function(sel) {
        try {
            document.querySelectorAll(sel).forEach(function(el) {
                if (el.children.length === 0 && el.textContent.trim().length > 1 && el.textContent.trim().length < 200 && !el.closest('script') && !el.closest('style') && !el.id.includes('Lang') && !el.id.includes('current')) {
                    elements.push(el);
                }
            });
        } catch(e) {}
    });
    var seen = new Set(), toTranslate = [];
    elements.forEach(function(el) {
        if (!seen.has(el) && el.textContent.trim().length > 1) { seen.add(el); toTranslate.push(el); }
    });
    showToastLang('Traduction en cours (' + label + ')...', '#7c5cbf');
    function translateBatch(batch, callback) {
        var query = batch.map(function(el) { return el.textContent.trim(); }).join(' | ');
        var url = 'https://api.mymemory.translated.net/get?q=' + encodeURIComponent(query) + '&langpair=fr|' + code;
        fetch(url).then(function(r) { return r.json(); }).then(function(data) {
            if (data.responseStatus === 200) {
                var translated = data.responseData.translatedText.split(' | ');
                batch.forEach(function(el, i) {
                    if (!el.dataset.orig) el.dataset.orig = el.textContent.trim();
                    if (translated[i] && translated[i].trim()) el.textContent = translated[i].trim();
                });
            }
            callback();
        }).catch(function() { callback(); });
    }
    var i = 0;
    function nextBatch() {
        if (i >= toTranslate.length) { showToastLang('Traduit en ' + label + ' ' + flag, '#1a372f'); return; }
        var batch = toTranslate.slice(i, i + 5); i += 5;
        setTimeout(function() { translateBatch(batch, nextBatch); }, 150);
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

/* ── Initialisation après chargement du DOM ── */
document.addEventListener('DOMContentLoaded', function() {
    /* Dark mode */
    var dark = localStorage.getItem('gl-dark') === '1';
    if (dark) { document.body.classList.add('dark'); updateDarkUI(true); }

    /* Langue (Concept 4) */
    var savedLang = localStorage.getItem('gl-lang');
    if (savedLang && savedLang !== 'fr') {
        currentLang = savedLang;
        var allLangs = [].concat.apply([], LANGUAGES.map(function(g) { return g.langs; }));
        var found = allLangs.find(function(l) { return l.code === savedLang; });
        if (found) updateLangBtn(found.code, found.label, found.flag);
    }

    /* Fermer modale au clic extérieur */
    var modal = document.getElementById('langModal');
    if (modal) modal.addEventListener('click', function(e) { if (e.target === this) closeLang(); });

    /* Synchro onglets */
    window.addEventListener('storage', function(e) {
        if (e.key === 'gl-dark') {
            var d = e.newValue === '1';
            document.body.classList.toggle('dark', d);
            updateDarkUI(d);
        }
    });
});
</script>
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
    
    <a href="bo_repaslist.php"    class="nav-item active"><i class="fas fa-utensils"></i> Repas</a>
    <div class="sidebar-section">Site</div>
    <a href="../../index.html"                 class="nav-item"><i class="fas fa-home"></i> Accueil</a>
    <a href="../frontend/fo_repaslist.php"  class="nav-item"><i class="fas fa-eye"></i> Vue utilisateur</a>
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
        <div style="display:flex;align-items:center;gap:8px;">
            <!-- Bouton dark : ovale compact (icône seule) -->
            <button id="darkToggle" onclick="toggleDark()" title="Mode clair/sombre"
                style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);font-size:15px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;">
                <i class="fas fa-moon" id="darkIcon"></i>
            </button>
            <!-- Bouton langue : allongé avec drapeau + nom + badge (Concept 4) -->
            <div style="position:relative;display:inline-flex;">
                <button id="langBtn" onclick="openLang()" title="Changer la langue"
                    style="display:inline-flex;align-items:center;gap:7px;padding:7px 16px;border-radius:99px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);font-size:13px;font-weight:500;cursor:pointer;font-family:'Lato',sans-serif;transition:all .2s;height:38px;">
                    <span id="currentLangLabel" style="display:flex;align-items:center;gap:6px;">🇫🇷 Français</span>
                </button>
                <span id="langActiveBadge" style="display:none;position:absolute;top:-4px;right:-4px;width:10px;height:10px;border-radius:50%;background:#60a5fa;border:2px solid rgba(26,55,47,.9);"></span>
            </div>
            <!-- Bouton compte : ovale compact (icône seule) -->
            <a href="#" title="Mon compte"
                style="width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);font-size:15px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s;flex-shrink:0;">
                <i class="fas fa-user-circle"></i>
            </a>
        </div>
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
        <!-- Breadcrumb concept 3 -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <p style="font-size:13px;color:#9ca3af;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-utensils" style="font-size:12px;"></i>
                Repas
                <span style="opacity:.4;">›</span>
                <span id="breadcrumb-liste" style="color:var(--vert);font-weight:600;">Liste</span>
            </p>
            <a href="bo_alimentlist.php"
               style="font-size:12px;color:#9ca3af;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:color .2s;"
               onmouseover="this.style.color='var(--violet)'"
               onmouseout="this.style.color='#9ca3af'">
                Passer aux aliments <i class="fas fa-arrow-right" style="font-size:11px;"></i>
            </a>
        </div>
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
                    <span style="font-size:11px;background:#f4ede4;color:#6b7280;padding:3px 8px;border-radius:6px;font-weight:600;" class="badge-id">#<?= $id ?></span>
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
