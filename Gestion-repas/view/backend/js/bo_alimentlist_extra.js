/* bo_alimentlist_extra.js */

/* Dark mode + langue — synchronisés depuis bo_repaslist via localStorage */
function updateDarkUI(d) {
    var i = document.getElementById('darkIcon');
    if (i) i.className = d ? 'fas fa-sun' : 'fas fa-moon';
}
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('gl-dark') === '1') {
        document.body.classList.add('dark');
    }
    window.addEventListener('storage', function(e) {
        if (e.key === 'gl-dark') {
            document.body.classList.toggle('dark', e.newValue === '1');
        }
    });
});

function ouvrirModifAliment(id,nom,type,cat,cal,prot,gluc,lip,fib,suc,sod,co2,prix,vit,label,orig,allerg) {
    document.getElementById('u_id').value        = id;
    document.getElementById('u_nom').value       = nom;
    document.getElementById('u_vitamines').value = vit;
    document.getElementById('u_label').value     = label;
    document.getElementById('u_origine').value   = orig;
    document.getElementById('u_allergenes').value= allerg;
    document.getElementById('u_calories').value  = cal;
    document.getElementById('u_proteines').value = prot;
    document.getElementById('u_glucides').value  = gluc;
    document.getElementById('u_lipides').value   = lip;
    document.getElementById('u_fibres').value    = fib;
    document.getElementById('u_sucre').value     = suc;
    document.getElementById('u_sodium').value    = sod;
    document.getElementById('u_co2').value       = co2;
    document.getElementById('u_prix').value      = prix;
    /* Type et catégorie : sélectionner la bonne option */
    var selType = document.getElementById('u_type');
    for (var i=0;i<selType.options.length;i++) {
        if (selType.options[i].value === type) { selType.selectedIndex = i; break; }
    }
    var selCat = document.getElementById('u_cat');
    for (var i=0;i<selCat.options.length;i++) {
        if (selCat.options[i].value === cat) { selCat.selectedIndex = i; break; }
    }
    /* Ouvrir la modale */
    document.getElementById('updateModalOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fermerModifAliment() {
    document.getElementById('updateModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function validateAndSubmitUpdate() {
    var valid = true;
    function chk(id, errId, test) {
        var el = document.getElementById(id);
        var er = document.getElementById(errId);
        if (!test(el)) { el.classList.add('error'); if(er) er.classList.add('show'); valid = false; }
        else { el.classList.remove('error'); if(er) er.classList.remove('show'); }
    }
    chk('u_nom',      'ue_nom',      function(e){ return e.value.trim().length >= 2; });
    chk('u_type',     'ue_type',     function(e){ return e.value !== ''; });
    chk('u_cat',      'ue_cat',      function(e){ return e.value !== ''; });
    chk('u_calories', 'ue_calories', function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=9999; });
    chk('u_proteines','ue_proteines',function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    chk('u_glucides', 'ue_glucides', function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    chk('u_lipides',  'ue_lipides',  function(e){ var v=parseFloat(e.value); return !isNaN(v)&&v>=0&&v<=100; });
    if (valid) document.getElementById('updateForm').submit();
    else document.querySelector('#updateModalOverlay .error')?.scrollIntoView({behavior:'smooth',block:'center'});
}