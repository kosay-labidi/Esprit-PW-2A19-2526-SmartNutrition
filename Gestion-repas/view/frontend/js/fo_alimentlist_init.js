/* fo_alimentlist_init.js — Traduction */

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