/* bo_repaslist.js */

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