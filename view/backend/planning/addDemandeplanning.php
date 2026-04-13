<?php
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

$controller = new DemandeplanningController();
$result = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->addDemande();
    
    if ($result['success']) {
        header('Location: listDemandeplanning.php');
        exit;
    }
}

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
    <title>Ajouter Demande Planning - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../css/admin.css"/>
    <style>
        .admin-container {
            max-width: 600px;
            margin: 100px auto 40px;
            padding: 40px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(91, 62, 150, .2);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .admin-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--text), var(--blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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
            margin-top: 10px;
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
        }
        .error-container {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.4);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .error-title {
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div id="cursor"></div>
<div id="cursor-trail"></div>

<nav id="navbar">
    <a href="../admin.html" class="nav-logo">
        <span class="nav-logo-text">SmartNutrition Admin</span>
    </a>
    <div class="nav-actions">
        <button id="theme-toggle">🌙 Sombre</button>
        <a href="listDemandeplanning.php" class="btn-logout">← Retour</a>
    </div>
</nav>

<div class="admin-container">
    <h1 class="admin-title">➕ Ajouter une Demande de Planning</h1>
    
    <?php if (!empty($result['errors'])): ?>
    <div class="error-container">
        <div class="error-title">⚠️ Erreurs:</div>
        <ul>
            <?php foreach ($result['errors'] as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>ID Utilisateur *</label>
            <input type="number" name="id_utilisateur" value="<?php echo $id_utilisateur; ?>" required min="1"/>
        </div>
        
        <div class="form-group">
            <label>Calories (kcal) *</label>
            <input type="number" name="calories" value="<?php echo $calories; ?>" required min="1"/>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Budget (€) *</label>
                <input type="number" step="0.01" name="budget" value="<?php echo $budget; ?>" required min="0.01"/>
            </div>
            <div class="form-group">
                <label>Type Budget *</label>
                <select name="type_budget" required>
                    <option value="">-- Choisir --</option>
                    <option value="daily" <?php echo $type_budget === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $type_budget === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $type_budget === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Durée *</label>
                <input type="number" name="duree" value="<?php echo $duree; ?>" required min="1"/>
            </div>
            <div class="form-group">
                <label>Type Durée *</label>
                <select name="type_duree" required>
                    <option value="">-- Choisir --</option>
                    <option value="days" <?php echo $type_duree === 'days' ? 'selected' : ''; ?>>Days</option>
                    <option value="weeks" <?php echo $type_duree === 'weeks' ? 'selected' : ''; ?>>Weeks</option>
                    <option value="months" <?php echo $type_duree === 'months' ? 'selected' : ''; ?>>Months</option>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn-submit">✨ Créer la Demande</button>
        <a href="listDemandeplanning.php" class="btn-cancel">Annuler</a>
    </form>
</div>

<script>
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