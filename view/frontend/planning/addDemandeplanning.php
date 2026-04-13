<?php
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

$controller = new DemandeplanningController();
$result = ['success' => false, 'errors' => []];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->addDemande();
    
    if ($result['success']) {
    // Afficher un message et rediriger après un court délai
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Succès</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: #1e2a2e;
                color: #fff;
                text-align: center;
            }
            .message {
                background: #2c3e2f;
                padding: 20px 40px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                animation: fadeOut 2s ease-in-out forwards;
                animation-delay: 1.5s;
            }
            @keyframes fadeOut {
                0% { opacity: 1; }
                100% { opacity: 0; visibility: hidden; }
            }
        </style>
    </head>
    <body>
        <div class="message">
            ✅ Demande enregistrée avec succès !<br>
            Redirection vers le planning...
        </div>
        <script>
            setTimeout(function() {
                window.location.href = "../dashboard.html?module=planning";
            }, 2000);
        </script>
    </body>
    </html>';
    exit;
}
}

// Form persistence
$id_utilisateur = isset($_POST['id_utilisateur']) ? htmlspecialchars($_POST['id_utilisateur']) : '';
$calories = isset($_POST['calories']) ? htmlspecialchars($_POST['calories']) : '';
$budget = isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : '';
$type_budget = isset($_POST['type_budget']) ? htmlspecialchars($_POST['type_budget']) : '';
$duree = isset($_POST['duree']) ? htmlspecialchars($_POST['duree']) : '';
$type_duree = isset($_POST['type_duree']) ? htmlspecialchars($_POST['type_duree']) : '';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Nouvelle Demande Planning - SmartNutrition</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../css/dashboard.css"/>
    <style>
        .form-container {
            max-width: 600px;
            margin: 100px auto 40px;
            padding: 40px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(91, 62, 150, .2);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .form-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            margin-bottom: 8px;
            text-align: center;
            background: linear-gradient(135deg, var(--text), var(--blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-subtitle {
            text-align: center;
            color: var(--muted);
            margin-bottom: 32px;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(31, 61, 43, 0.4);
            border: 1px solid rgba(91, 62, 150, 0.3);
            border-radius: 10px;
            color: var(--text);
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--violet);
            background: rgba(31, 61, 43, 0.6);
            box-shadow: 0 0 20px rgba(91, 62, 150, 0.3);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--violet), var(--blue));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: none;
            transition: all 0.3s;
            margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(91, 62, 150, 0.5);
        }
        .btn-cancel {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            text-align: center;
            background: transparent;
            border: 1px solid rgba(91, 62, 150, 0.3);
            border-radius: 10px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            border-color: var(--violet);
            color: var(--text);
            background: rgba(91, 62, 150, 0.1);
        }
        .error-container {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.4);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .error-title {
            color: #e74c3c;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .error-list {
            margin: 0;
            padding-left: 20px;
            color: #e74c3c;
            font-size: 0.9rem;
        }
        @media (max-width: 600px) {
            .form-container {
                margin: 80px 16px 20px;
                padding: 24px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div id="cursor"></div>
<div id="cursor-trail"></div>

<nav id="navbar">
    <a href="../index.html" class="nav-logo">
        <svg viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="28" stroke="url(#ag)" stroke-width="1.5" opacity=".6"/>
            <circle cx="30" cy="30" r="22" stroke="url(#ag)" stroke-width=".8" opacity=".3"/>
            <defs>
                <radialGradient id="ag" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#3A86C4"/>
                    <stop offset="100%" stop-color="#5B3E96"/>
                </radialGradient>
                <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#1F3D2B"/>
                    <stop offset="100%" stop-color="#3A86C4"/>
                </linearGradient>
            </defs>
            <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="url(#lg)"/>
            <path d="M30 14 L30 46" stroke="rgba(242,232,207,.5)" stroke-width="1" stroke-linecap="round"/>
        </svg>
        <span class="nav-logo-text">SmartNutrition</span>
    </a>
    <div class="nav-actions">
        <button id="theme-toggle" title="Changer le thème">🌙 Sombre</button>
        <a href="../dashboard.html" class="btn-logout">Dashboard</a>
    </div>
</nav>

<div class="form-container">
    <h1 class="form-title">📅 Nouvelle Demande de Planning</h1>
    <p class="form-subtitle">Définissez vos objectifs nutritionnels personnalisés</p>
    
    <?php if (!empty($result['errors'])): ?>
    <div class="error-container">
        <div class="error-title">⚠️ Erreurs de validation</div>
        <ul class="error-list">
            <?php foreach ($result['errors'] as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" novalidate>
        <div class="form-group">
            <label for="id_utilisateur">Identifiant Utilisateur *</label>
            <input type="number" id="id_utilisateur" name="id_utilisateur" 
                   value="<?php echo $id_utilisateur; ?>" placeholder="Ex: 123" required min="1"/>
        </div>
        
        <div class="form-group">
            <label for="calories">Objectif Calories (kcal) *</label>
            <input type="number" id="calories" name="calories" 
                   value="<?php echo $calories; ?>" placeholder="Ex: 2000" required min="1"/>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="budget">Budget (€) *</label>
                <input type="number" step="0.01" id="budget" name="budget" 
                       value="<?php echo $budget; ?>" placeholder="Ex: 50.00" required min="0.01"/>
            </div>
            <div class="form-group">
                <label for="type_budget">Type de Budget *</label>
                <select id="type_budget" name="type_budget" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="quotidien" <?php echo $type_budget === 'quotidien' ? 'selected' : ''; ?>>Quotidien</option>
                    <option value="hebdomadaire" <?php echo $type_budget === 'hebdomadaire' ? 'selected' : ''; ?>>Hebdomadaire</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="duree">Durée *</label>
                <input type="number" id="duree" name="duree" 
                       value="<?php echo $duree; ?>" placeholder="Ex: 7" required min="1"/>
            </div>
            <div class="form-group">
                <label for="type_duree">Unité de Durée *</label>
                <select id="type_duree" name="type_duree" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="jours" <?php echo $type_duree === 'jours' ? 'selected' : ''; ?>>Jours</option>
                    <option value="semaines" <?php echo $type_duree === 'semaines' ? 'selected' : ''; ?>>Semaines</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn-submit">✨ Créer ma Demande</button>
        <a href="../dashboard.html?module=planning" class="btn-cancel">Annuler</a>
    </form>
</div>

<script>
// Custom cursor
(function(){
    const cur=document.getElementById('cursor');
    const trail=document.getElementById('cursor-trail');
    if(!cur||!trail)return;
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{
        mx=e.clientX;my=e.clientY;
        cur.style.left=mx+'px';cur.style.top=my+'px';
    });
    (function loop(){
        tx+=(mx-tx)*.12;ty+=(my-ty)*.12;
        trail.style.left=tx+'px';trail.style.top=ty+'px';
        requestAnimationFrame(loop);
    })();
    document.querySelectorAll('a,button,input,select').forEach(el=>{
        el.addEventListener('mouseenter',()=>cur.classList.add('hover'));
        el.addEventListener('mouseleave',()=>cur.classList.remove('hover'));
    });
})();

// Theme toggle
(function(){
    const btn=document.getElementById('theme-toggle');
    const html=document.documentElement;
    const saved=localStorage.getItem('gaialumen-theme')||'dark';
    html.setAttribute('data-theme',saved);
    if(btn)btn.textContent=saved==='dark'?'☀️ Clair':'🌙 Sombre';
    if(btn)btn.addEventListener('click',()=>{
        const n=html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme',n);
        localStorage.setItem('gaialumen-theme',n);
        btn.textContent=n==='dark'?'☀️ Clair':'🌙 Sombre';
    });
})();
</script>
</body>
</html>