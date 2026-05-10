<?php
/**
 * reset_password.php  —  view/frontend/users/
 * GET  ?token=xxx  → affiche le formulaire si token valide
 * POST ?token=xxx  → met à jour le mot de passe
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../controller/Passwordreset.controller.php';  // Modifier cette ligne
$resetCtrl = new PasswordResetController();
$token     = trim($_GET['token'] ?? '');
$error     = '';
$success   = false;

$emailFromToken = $resetCtrl->validateToken($token);
$tokenValid     = ($emailFromToken !== null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken  = trim($_POST['token'] ?? '');
    $newMdp     = $_POST['new_mdp']     ?? '';
    $confirmMdp = $_POST['confirm_mdp'] ?? '';

    $emailFromToken = $resetCtrl->validateToken($postToken);

    if (!$emailFromToken) {
        $error = 'Ce lien est invalide ou a expiré.';
    }
    elseif (strlen($newMdp) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    elseif (!preg_match('/[A-Z]/', $newMdp)) {
        $error = 'Le mot de passe doit contenir au moins une majuscule.';
    }
    elseif (!preg_match('/[0-9]/', $newMdp)) {
        $error = 'Le mot de passe doit contenir au moins un chiffre.';
    }
    elseif ($newMdp !== $confirmMdp) {
        $error = 'Les mots de passe ne correspondent pas.';
    }
    // ✅ NOUVELLE VÉRIFICATION : Ancien mot de passe
    elseif ($resetCtrl->isSameAsOldPassword($emailFromToken, $newMdp)) {
        $error = '❌ Ce mot de passe est identique à votre ancien mot de passe. Veuillez en choisir un nouveau.';
    }
    else {
        if ($resetCtrl->updatePassword($emailFromToken, $newMdp)) {
            $resetCtrl->consumeToken($postToken);
            $success = true;
        } else {
            $error = 'Erreur lors de la mise à jour. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Nouveau mot de passe — GaiaLumen</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<style>
:root{
  --green:#1F3D2B;--sand:#F2E8CF;--violet:#5B3E96;--blue:#3A86C4;
  --bg:#0a1a10;--text:#F2E8CF;--muted:#a8b8a0;
  --card-bg:rgba(15,35,24,0.85);--glass:rgba(31,61,43,0.45);
  --shadow:0 20px 60px rgba(0,0,0,.5);
}
[data-theme="light"]{
  --bg:#f5f0e8;--text:#1F3D2B;--muted:#5a6e5a;
  --card-bg:rgba(242,232,207,0.85);
  --shadow:0 20px 60px rgba(31,61,43,.15);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Lato',sans-serif;background:var(--bg);color:var(--text);
     min-height:100vh;display:flex;align-items:center;justify-content:center;
     overflow:hidden;cursor:none;transition:background .3s,color .3s;}
h1,h2,h3{font-family:'Cormorant Garamond',serif;}

/* CURSEUR */
#cursor{position:fixed;top:0;left:0;z-index:9999;pointer-events:none;
  width:14px;height:14px;border-radius:50%;background:var(--violet);
  box-shadow:0 0 12px var(--violet),0 0 24px var(--blue);
  transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen;}
#cursor.hover{width:28px;height:28px;background:var(--blue);}
#cursor-trail{position:fixed;top:0;left:0;z-index:9998;pointer-events:none;
  width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(91,62,150,.5);
  transform:translate(-50%,-50%);}

/* PRELOADER */
#preloader{position:fixed;inset:0;z-index:10000;background:#050e08;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  transition:opacity .8s,visibility .8s;}
#preloader.hidden{opacity:0;visibility:hidden;pointer-events:none;}
#pl-canvas{width:140px;height:140px;}
#pl-text{margin-top:20px;font-family:'Cormorant Garamond',serif;
  font-size:1.5rem;letter-spacing:.3em;color:var(--sand);
  animation:pulse 1.5s ease-in-out infinite;}
@keyframes pulse{0%,100%{opacity:.4}50%{opacity:1}}

#bg-canvas{position:fixed;inset:0;width:100%;height:100%;z-index:0;}

/* THEME TOGGLE */
#theme-toggle{position:fixed;top:20px;right:20px;z-index:1000;
  width:50px;height:50px;border-radius:50%;
  border:2px solid rgba(91,62,150,.4);background:var(--glass);
  backdrop-filter:blur(10px);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:all .3s;}
#theme-toggle:hover{transform:scale(1.1);border-color:var(--violet);}

/* CONTAINER */
.container{position:relative;z-index:2;max-width:450px;width:90%;
  background:var(--card-bg);backdrop-filter:blur(20px) saturate(1.4);
  border-radius:20px;padding:40px 35px;box-shadow:var(--shadow);
  border:1px solid rgba(91,62,150,.3);animation:slideUp .8s ease;
  margin-left:auto;margin-right:10%;}
@keyframes slideUp{from{opacity:0;transform:translateY(40px);}to{opacity:1;transform:translateY(0);}}

/* LOGO */
.logo{text-align:center;margin-bottom:28px;}
.logo svg{width:50px;height:50px;margin-bottom:12px;}
.logo h1{font-size:1.8rem;
  background:linear-gradient(135deg,var(--sand),var(--blue));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
.logo p{color:rgba(242,232,207,.7);font-size:.85rem;}

/* STEPS */
.steps{display:flex;justify-content:space-between;margin-bottom:28px;position:relative;}
.steps::before{content:'';position:absolute;top:15px;left:15px;right:15px;height:2px;
  background:rgba(91,62,150,.3);z-index:0;}
.step{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:6px;}
.step-circle{width:28px;height:28px;border-radius:50%;
  background:rgba(91,62,150,.3);border:2px solid rgba(91,62,150,.5);
  display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;transition:all .3s;}
.step.done .step-circle,.step.active .step-circle{
  background:linear-gradient(135deg,var(--violet),var(--blue));
  border-color:var(--blue);box-shadow:0 0 20px rgba(91,62,150,.5);}
.step-label{font-size:.7rem;color:var(--muted);text-align:center;}
.step.done .step-label,.step.active .step-label{color:var(--sand);font-weight:600;}

/* FORM */
.form-group{margin-bottom:20px;}
.form-group label{display:block;margin-bottom:6px;font-size:.85rem;color:var(--sand);font-weight:600;}
.input-wrap{position:relative;}
.input-wrap input{width:100%;padding:12px 44px 12px 14px;
  background:rgba(31,61,43,.4);border:1px solid rgba(91,62,150,.3);border-radius:10px;
  color:var(--sand);font-size:.95rem;transition:all .3s;}
.input-wrap input:focus{outline:none;border-color:var(--violet);background:rgba(31,61,43,.6);box-shadow:0 0 20px rgba(91,62,150,.3);}
.input-wrap input::placeholder{color:rgba(242,232,207,.4);}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--muted);cursor:pointer;padding:4px;display:flex;align-items:center;}

/* STRENGTH */
.strength-bar{height:4px;border-radius:4px;background:rgba(91,62,150,.2);margin-top:8px;overflow:hidden;}
.strength-fill{height:100%;width:0;transition:width .3s,background .3s;border-radius:4px;}
.strength-text{font-size:.75rem;color:var(--muted);margin-top:4px;}
.req-list{list-style:none;margin-top:8px;padding:0;}
.req-list li{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:6px;margin-bottom:3px;}
.req-list li::before{content:'○';font-size:.7rem;}
.req-list li.ok{color:#27ae60;}
.req-list li.ok::before{content:'✓';}

/* BOUTON */
.btn-submit{width:100%;padding:12px;
  background:linear-gradient(135deg,var(--violet),var(--blue));
  border:none;border-radius:10px;color:#fff;font-size:.95rem;font-weight:600;
  cursor:none;transition:transform .2s,box-shadow .3s;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(91,62,150,.5);}
.btn-submit:disabled{opacity:.6;transform:none;}

/* ALERTS */
.alert{padding:14px 16px;border-radius:10px;margin-bottom:18px;font-size:.87rem;line-height:1.6;}
.alert-error{background:rgba(231,76,60,.15);border-left:4px solid #e74c3c;color:#e74c3c;}
.alert-success{background:rgba(39,174,96,.15);border-left:4px solid #27ae60;}
.alert-invalid{background:rgba(231,76,60,.1);border-left:4px solid #e74c3c;}

/* LINKS */
.links{text-align:center;margin-top:20px;}
.links a{color:var(--blue);text-decoration:none;font-size:.85rem;transition:color .3s;
  display:inline-flex;align-items:center;gap:6px;}
.links a:hover{color:var(--violet);text-decoration:underline;}

@media(max-width:600px){
  .container{margin:20px auto;padding:30px 25px;max-width:95%;}
  .steps{flex-direction:column;gap:16px;}
  .steps::before{display:none;}
}
</style>
</head>
<body>

<div id="cursor"></div>
<div id="cursor-trail"></div>

<div id="preloader">
  <canvas id="pl-canvas"></canvas>
  <div id="pl-text">GAIALUMEN</div>
</div>

<canvas id="bg-canvas"></canvas>

<button id="theme-toggle" aria-label="Changer le thème">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text)" stroke-width="2">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
  </svg>
</button>

<div class="container">
  <div class="logo">
    <svg viewBox="0 0 60 60" fill="none">
      <circle cx="30" cy="30" r="28" stroke="url(#ag)" stroke-width="1.5" opacity=".6"/>
      <defs>
        <radialGradient id="ag" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#3A86C4"/><stop offset="100%" stop-color="#5B3E96"/>
        </radialGradient>
        <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#1F3D2B"/><stop offset="100%" stop-color="#3A86C4"/>
        </linearGradient>
      </defs>
      <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="url(#lg)"/>
      <path d="M30 14 L30 46" stroke="rgba(242,232,207,.5)" stroke-width="1" stroke-linecap="round"/>
    </svg>
    <h1>🔑 Nouveau mot de passe</h1>
    <p>Choisissez un mot de passe sécurisé</p>
  </div>

  <!-- STEPS -->
  <div class="steps">
    <div class="step done"><div class="step-circle">✓</div><div class="step-label">Email</div></div>
    <div class="step done"><div class="step-circle">✓</div><div class="step-label">Vérification</div></div>
    <div class="step active"><div class="step-circle">3</div><div class="step-label">Nouveau MDP</div></div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <strong>✅ Mot de passe mis à jour avec succès !</strong><br/>
      <span style="color:var(--muted);">Redirection dans <span id="countdown">5</span> secondes…</span>
    </div>
    <div class="links">
      <a href="../../frontend/login.html">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
          <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
        </svg>Aller à la connexion
      </a>
    </div>
    <script>
      let c=5;const el=document.getElementById('countdown');
      const iv=setInterval(()=>{c--;if(el)el.textContent=c;if(c<=0){clearInterval(iv);window.location.href='../../frontend/login.html';}},1000);
    </script>

  <?php elseif (!$tokenValid): ?>
    <div class="alert alert-invalid">
      <p style="color:#e74c3c;font-weight:700;margin-bottom:8px;">❌ Lien invalide ou expiré</p>
      <p style="color:var(--muted);font-size:.87rem;line-height:1.6;">
        Ce lien a expiré (> 60 min), est invalide ou a déjà été utilisé.<br/>
        Faites une nouvelle demande de réinitialisation.
      </p>
    </div>
    <div class="links">
      <a href="../forgot.html">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>Nouvelle demande
      </a>
    </div>

  <?php else: ?>
    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="?token=<?= urlencode($token) ?>" id="reset-form">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"/>

      <div class="form-group">
        <label for="new_mdp">Nouveau mot de passe</label>
        <div class="input-wrap">
          <input type="password" id="new_mdp" name="new_mdp" placeholder="••••••••" required autocomplete="new-password"/>
          <button type="button" class="toggle-pw" onclick="togglePw('new_mdp',this)" aria-label="Voir">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
        <div class="strength-text" id="strength-text"></div>
        <ul class="req-list">
          <li id="req-len">Au moins 8 caractères</li>
          <li id="req-upper">Au moins une majuscule</li>
          <li id="req-num">Au moins un chiffre</li>
        </ul>
      </div>

      <div class="form-group">
        <label for="confirm_mdp">Confirmer le mot de passe</label>
        <div class="input-wrap">
          <input type="password" id="confirm_mdp" name="confirm_mdp" placeholder="••••••••" required autocomplete="new-password"/>
          <button type="button" class="toggle-pw" onclick="togglePw('confirm_mdp',this)" aria-label="Voir">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <div class="strength-text" id="match-text"></div>
      </div>
      <div class="info" style="background:rgba(231,76,60,0.1); border-left-color:#e74c3c; margin-top:15px;">
    <div class="info-icon">⚠️</div>
    <div>
        Vous ne pouvez pas réutiliser votre ancien mot de passe.
    </div>
</div>
      <button type="submit" class="btn-submit" id="submit-btn">
        🔒 Enregistrer le nouveau mot de passe
      </button>
    </form>

    <div class="links">
      <a href="../../frontend/login.html">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>Retour à la connexion
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
/* PRELOADER */
(function(){const c=document.getElementById('pl-canvas');if(!c)return;const ctx=c.getContext('2d');c.width=140;c.height=140;let a=0,prog=0;function draw(){prog=Math.min(prog+2,100);a+=.04;ctx.clearRect(0,0,140,140);ctx.save();ctx.translate(70,70);ctx.rotate(a);for(let i=0;i<3;i++){ctx.beginPath();ctx.arc(0,0,48-i*10,0,Math.PI*2);ctx.strokeStyle=`rgba(${i===0?'58,134,196':i===1?'91,62,150':'31,61,43'},${.6-i*.15})`;ctx.lineWidth=1.5;ctx.stroke();}ctx.rotate(-a*.3);const g=ctx.createLinearGradient(-18,-26,18,26);g.addColorStop(0,'#1F3D2B');g.addColorStop(1,'#3A86C4');ctx.beginPath();ctx.fillStyle=g;ctx.moveTo(0,-26);ctx.bezierCurveTo(19,-12,22,7,0,26);ctx.bezierCurveTo(-22,7,-19,-12,0,-26);ctx.fill();ctx.restore();if(prog<100)requestAnimationFrame(draw);else setTimeout(()=>{const p=document.getElementById('preloader');if(p)p.classList.add('hidden');},300);}requestAnimationFrame(draw);})();

/* CURSEUR */
(function(){const cur=document.getElementById('cursor'),trail=document.getElementById('cursor-trail');if(!cur||!trail)return;let mx=0,my=0,tx=0,ty=0;document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});(function loop(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;trail.style.left=tx+'px';trail.style.top=ty+'px';requestAnimationFrame(loop);})();document.querySelectorAll('a,button,input').forEach(el=>{el.addEventListener('mouseenter',()=>cur.classList.add('hover'));el.addEventListener('mouseleave',()=>cur.classList.remove('hover'));});})();

/* THREE.JS BACKGROUND */
window.addEventListener('load',function(){const canvas=document.getElementById('bg-canvas');if(!canvas||typeof THREE==='undefined')return;const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));const scene=new THREE.Scene();const camera=new THREE.PerspectiveCamera(60,window.innerWidth/window.innerHeight,.1,200);camera.position.set(0,0,6);function resize(){const w=window.innerWidth,h=window.innerHeight;renderer.setSize(w,h);camera.aspect=w/h;camera.updateProjectionMatrix();}resize();window.addEventListener('resize',resize);scene.add(new THREE.AmbientLight(0xffffff,.3));const dl=new THREE.DirectionalLight(0x3A86C4,1.2);dl.position.set(5,5,5);scene.add(dl);const loader=new THREE.TextureLoader();const earth=new THREE.Mesh(new THREE.SphereGeometry(2.8,64,64),new THREE.MeshPhongMaterial({map:loader.load('https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg'),specular:new THREE.Color(0x3A86C4),shininess:28}));earth.position.set(-4,0,-2);scene.add(earth);const clouds=new THREE.Mesh(new THREE.SphereGeometry(2.83,64,64),new THREE.MeshPhongMaterial({map:loader.load('https://threejs.org/examples/textures/planets/earth_clouds_1024.png'),transparent:true,opacity:.28}));clouds.position.copy(earth.position);scene.add(clouds);const PC=1200,pp=new Float32Array(PC*3);for(let i=0;i<PC;i++){pp[i*3]=(Math.random()-.5)*25;pp[i*3+1]=(Math.random()-.5)*25;pp[i*3+2]=(Math.random()-.5)*12;}const pGeo=new THREE.BufferGeometry();pGeo.setAttribute('position',new THREE.BufferAttribute(pp,3));scene.add(new THREE.Points(pGeo,new THREE.PointsMaterial({size:.025,color:0x3A86C4,transparent:true,opacity:.5})));let t=0,mx=0,my=0;document.addEventListener('mousemove',e=>{mx=(e.clientX/window.innerWidth-.5)*2;my=(e.clientY/window.innerHeight-.5)*2;});(function anim(){requestAnimationFrame(anim);t+=.01;earth.rotation.y+=.002;clouds.rotation.y+=.0025;camera.position.x+=(mx*.5-camera.position.x)*.05;camera.position.y+=(-my*.5-camera.position.y)*.05;camera.lookAt(0,0,0);renderer.render(scene,camera);})();});

/* THEME */
(function(){const btn=document.getElementById('theme-toggle'),html=document.documentElement;let dark=localStorage.getItem('gaialumen-theme')!=='light';html.setAttribute('data-theme',dark?'dark':'light');btn&&btn.addEventListener('click',()=>{dark=!dark;html.setAttribute('data-theme',dark?'dark':'light');localStorage.setItem('gaialumen-theme',dark?'dark':'light');});})();

/* SHOW/HIDE PASSWORD */
function togglePw(id,btn){const inp=document.getElementById(id);if(!inp)return;inp.type=inp.type==='password'?'text':'password';btn.innerHTML=inp.type==='text'?'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>':'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';}

/* FORCE MOT DE PASSE */
document.addEventListener('DOMContentLoaded',function(){
  const pw=document.getElementById('new_mdp'),conf=document.getElementById('confirm_mdp');
  const fill=document.getElementById('strength-fill'),stTxt=document.getElementById('strength-text');
  const matchTxt=document.getElementById('match-text');
  if(!pw)return;
  pw.addEventListener('input',function(){
    const v=this.value;
    let s=0;
    const len=v.length>=8,upper=/[A-Z]/.test(v),num=/[0-9]/.test(v),spec=/[^A-Za-z0-9]/.test(v);
    if(len)s++;if(upper)s++;if(num)s++;if(spec)s++;
    document.getElementById('req-len')?.classList.toggle('ok',len);
    document.getElementById('req-upper')?.classList.toggle('ok',upper);
    document.getElementById('req-num')?.classList.toggle('ok',num);
    if(fill){const pct=[0,25,50,75,100][s];const col=['#e74c3c','#e67e22','#f1c40f','#2ecc71','#27ae60'][s];fill.style.width=pct+'%';fill.style.background=col;}
    if(stTxt){const lbl=['','Très faible','Faible','Moyen','Fort'];stTxt.textContent=v.length?lbl[s]||'Excellent':'';}
    checkMatch();
  });
  conf?.addEventListener('input',checkMatch);
  function checkMatch(){if(!conf||!matchTxt)return;if(!conf.value){matchTxt.textContent='';return;}const ok=conf.value===pw.value;matchTxt.textContent=ok?'✓ Mots de passe identiques':'✗ Mots de passe différents';matchTxt.style.color=ok?'#27ae60':'#e74c3c';}
  document.getElementById('reset-form')?.addEventListener('submit',function(){const b=document.getElementById('submit-btn');if(b){b.textContent='Enregistrement…';b.disabled=true;}});
});
</script>
</body>
</html>