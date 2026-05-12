/* bo_alimentlist.js */

/*  Curseur personnalisé */
(function(){
    const c = document.getElementById('cur');
    const t = document.getElementById('curt');
    let mx=0, my=0, tx=0, ty=0;
    document.addEventListener('mousemove', e => {
        mx = e.clientX; my = e.clientY;
        c.style.left = mx+'px'; c.style.top = my+'px';
    });
    (function loop() {
        tx += (mx-tx) * .12; ty += (my-ty) * .12;
        t.style.left = tx+'px'; t.style.top = ty+'px';
        requestAnimationFrame(loop);
    })();
    /* Agrandit le curseur au survol des éléments interactifs */
    document.querySelectorAll('a,button,input,select').forEach(el => {
        el.addEventListener('mouseenter', () => c.classList.add('h'));
        el.addEventListener('mouseleave', () => c.classList.remove('h'));
    });
})();


function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    /* Réinitialise les marqueurs d'erreur */
    document.querySelectorAll('.mi.error').forEach(el => el.classList.remove('error'));
    document.querySelectorAll('.err-msg.show').forEach(el => el.classList.remove('show'));
}


function validateAndSubmit() {
    let valid = true;


    function setError(inputId, errId, show) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(errId);
        if (!input || !err) return;
        if (show) {
            input.classList.add('error');
            err.classList.add('show');
            valid = false;
        } else {
            input.classList.remove('error');
            err.classList.remove('show');
        }
    }

    const nom = document.getElementById('f_nom').value.trim();
    setError('f_nom', 'e_nom', nom.length < 2);

    const type = document.getElementById('f_type').value;
    setError('f_type', 'e_type', type === '');

    const cat = document.getElementById('f_cat').value;
    setError('f_cat', 'e_cat', cat === '');

    const numChecks = [
        { id:'f_calories',  errId:'e_calories',  min:0,    max:9999, req:true  },
        { id:'f_proteines', errId:'e_proteines', min:0,    max:100,  req:true  },
        { id:'f_glucides',  errId:'e_glucides',  min:0,    max:100,  req:true  },
        { id:'f_lipides',   errId:'e_lipides',   min:0,    max:100,  req:true  },
        { id:'f_fibres',    errId:'e_fibres',    min:0,    max:100,  req:false },
        { id:'f_sucre',     errId:'e_sucre',     min:0,    max:100,  req:false },
        { id:'f_sodium',    errId:'e_sodium',    min:0,    max:5000, req:false },
        { id:'f_co2',       errId:'e_co2',       min:0,    max:100,  req:false },
        { id:'f_prix',      errId:'e_prix',      min:0,    max:9999, req:false },
    ];

    numChecks.forEach(({ id, errId, min, max, req }) => {
        const el  = document.getElementById(id);
        const val = parseFloat(el.value);

        const invalid = isNaN(val) || val < min || val > max;
        setError(id, errId, req ? invalid : (!isNaN(val) && invalid));
    });

    if (valid) {
        const fd = new FormData(document.getElementById('createForm'));
        fetch(document.getElementById('createForm').action, {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                showToast('Aliment ajouté avec succès !', 'ok');
                setTimeout(() => location.reload(), 900);
            }
        })
        .catch(() => document.getElementById('createForm').submit());
    } else {
        /* Scroll vers le premier champ en erreur */
        const firstError = document.querySelector('.mi.error');
        if (firstError) firstError.scrollIntoView({ behavior:'smooth', block:'center' });
    }
}


/* FONCTIONNALITÉS HORS CRUD (filtre + recherche) */


function setFilter(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}


function applyFilters() {
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    const rows  = document.querySelectorAll('.trow');
    let visible = 0;

    rows.forEach(row => {
        const matchType   = (type === 'tous') || (row.dataset.type === type);
        const matchSearch = !query || row.dataset.nom.includes(query);
        const show        = matchType && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });


    document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
    const rc = document.getElementById('rowCount');
    if (rc) rc.textContent = visible + ' aliment(s)';
}

/* ── AJAX Delete + Toast ─────────────────────────────── */
function deleteAliment(id) {
    if (!confirm('Supprimer cet aliment ?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id_aliment', id);
    fetch('/GSRepasVF2_final/Gestion-repas/controller/alimentcontroller.php', {
        method: 'POST', body: fd,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Aliment supprimé.', 'del');
            setTimeout(() => location.reload(), 900);
        }
    })
    .catch(() => {
        window.location.href = '/GSRepasVF2_final/Gestion-repas/controller/alimentcontroller.php?action=delete&id=' + id;
    });
}

function showToast(msg, type) {
    var t = document.getElementById('gl-toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'gl-toast';
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 22px;border-radius:12px;font-size:13px;font-weight:600;color:white;box-shadow:0 8px 24px rgba(0,0,0,.2);transition:opacity .3s;font-family:Lato,sans-serif;';
        document.body.appendChild(t);
    }
    t.style.background = type === 'del' ? '#ef4444' : '#1a372f';
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(function(){ t.style.opacity = '0'; }, 2500);
}
