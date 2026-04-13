<?php
require_once __DIR__ . '/../../../controller/Demandeplanning.controller.php';

$controller = new DemandeplanningController();

// Récupérer l'ID de la demande depuis l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si pas d'ID, rediriger vers la liste
if ($id <= 0) {
    header('Location: listMesDemandes.php');
    exit;
}

// Récupérer la demande via le contrôleur
$demande = $controller->getDemandeById($id);

// Si demande non trouvée, rediriger
if (!$demande) {
    header('Location: listMesDemandes.php?error=notfound');
    exit;
}

// Extraire les données avec valeurs par défaut
$id_demande = $demande['id_demande'] ?? $demande['id'] ?? $id;
$calories = $demande['calories'] ?? 0;
$budget = $demande['budget'] ?? 0;
$type_budget = $demande['type_budget'] ?? 'non défini';
$duree = $demande['duree'] ?? 0;
$type_duree = $demande['type_duree'] ?? 'non défini';
$date_demande = $demande['date_demande'] ?? date('Y-m-d H:i:s');

// Calculer le budget total selon le type
$budget_total = $budget;
if ($type_budget === 'quotidien' && $type_duree === 'jours') {
    $budget_total = $budget * $duree;
} elseif ($type_budget === 'hebdomadaire' && $type_duree === 'semaines') {
    $budget_total = $budget * $duree;
}

// Déterminer le statut (simulé pour l'instant)
$statut = 'En attente';
$statut_class = 'status-pending';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Détails de la Demande #<?php echo htmlspecialchars($id_demande); ?> - SmartNutrition</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../css/dashboard.css"/>
    <style>
        .container {
            max-width: 800px;
            margin: 100px auto 40px;
            padding: 40px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(91, 62, 150, .2);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(91, 62, 150, .2);
        }
        .page-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--text), var(--blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .page-subtitle {
            color: var(--muted);
            font-size: 0.95rem;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 12px;
        }
        .status-pending {
            background: rgba(243, 156, 18, .2);
            color: #f39c12;
        }
        .status-approved {
            background: rgba(39, 174, 96, .2);
            color: #27ae60;
        }
        .status-rejected {
            background: rgba(231, 76, 60, .2);
            color: #e74c3c;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 40px;
        }
        .detail-card {
            background: rgba(91, 62, 150, .1);
            border: 1px solid rgba(91, 62, 150, .2);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
        }
        .detail-card:hover {
            border-color: rgba(91, 62, 150, .4);
            transform: translateY(-2px);
        }
        .detail-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
        }
        .detail-label {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .detail-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            font-family: 'Cormorant Garamond', serif;
        }
        .detail-unit {
            font-size: 0.9rem;
            color: var(--muted);
            margin-left: 4px;
        }
        .detail-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 8px;
        }
        .badge-quotidien {
            background: rgba(58, 134, 196, .2);
            color: var(--blue);
        }
        .badge-hebdomadaire {
            background: rgba(91, 62, 150, .2);
            color: var(--violet);
        }
        .badge-jours {
            background: rgba(31, 61, 43, .4);
            color: var(--sand);
        }
        .badge-semaines {
            background: rgba(242, 232, 207, .2);
            color: var(--text);
        }
        .summary-section {
            background: linear-gradient(135deg, rgba(91, 62, 150, .15), rgba(58, 134, 196, .15));
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(91, 62, 150, .3);
        }
        .summary-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(91, 62, 150, .2);
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-label {
            color: var(--muted);
        }
        .summary-value {
            font-weight: 600;
            color: var(--text);
        }
        .summary-total {
            font-size: 1.3rem;
            color: var(--blue);
            font-weight: 700;
        }
        .actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--violet), var(--blue));
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(91, 62, 150, .5);
        }
        .btn-secondary {
            background: transparent;
            border: 1.5px solid rgba(91, 62, 150, .4);
            color: var(--text);
        }
        .btn-secondary:hover {
            background: rgba(91, 62, 150, .2);
            border-color: var(--violet);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .btn-back:hover {
            color: var(--text);
        }
        .info-section {
            background: rgba(58, 134, 196, .1);
            border-left: 4px solid var(--blue);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        .info-text {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        @media (max-width: 600px) {
            .container {
                margin: 80px 16px 20px;
                padding: 24px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
            .detail-value {
                font-size: 1.5rem;
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

<div class="container">
    <a href="listMesDemandes.php" class="btn-back">← Retour à mes demandes</a>
    
    <div class="page-header">
        <h1 class="page-title">📋 Demande de Planning #<?php echo htmlspecialchars($id_demande); ?></h1>
        <p class="page-subtitle">Demandée le <?php echo date('d/m/Y à H:i', strtotime($date_demande)); ?></p>
        <span class="status-badge <?php echo $statut_class; ?>">⏳ <?php echo $statut; ?></span>
    </div>
    
    <div class="info-section">
        <div class="info-title">📌 Récapitulatif de votre demande</div>
        <p class="info-text">
            Vous avez demandé un planning nutritionnel de <strong><?php echo number_format($calories, 0, ',', ' '); ?> kcal</strong> 
            sur une durée de <strong><?php echo $duree . ' ' . $type_duree; ?></strong> 
            avec un budget <strong><?php echo $type_budget; ?></strong> de <strong><?php echo number_format($budget, 2, ',', ' '); ?> €</strong>.
        </p>
    </div>
    
    <div class="details-grid">
        <div class="detail-card">
            <div class="detail-icon">🔥</div>
            <div class="detail-label">Objectif Calorique</div>
            <div class="detail-value">
                <?php echo number_format($calories, 0, ',', ' '); ?>
                <span class="detail-unit">kcal/jour</span>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">💰</div>
            <div class="detail-label">Budget <?php echo ucfirst($type_budget); ?></div>
            <div class="detail-value">
                <?php echo number_format($budget, 2, ',', ' '); ?>
                <span class="detail-unit">€</span>
            </div>
            <span class="detail-badge badge-<?php echo htmlspecialchars($type_budget); ?>">
                <?php echo ucfirst($type_budget); ?>
            </span>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">📅</div>
            <div class="detail-label">Durée</div>
            <div class="detail-value">
                <?php echo $duree; ?>
                <span class="detail-unit"><?php echo $type_duree; ?></span>
            </div>
            <span class="detail-badge badge-<?php echo htmlspecialchars($type_duree); ?>">
                <?php echo ucfirst($type_duree); ?>
            </span>
        </div>
        
        <div class="detail-card">
            <div class="detail-icon">💵</div>
            <div class="detail-label">Budget Total Estimé</div>
            <div class="detail-value">
                <?php echo number_format($budget_total, 2, ',', ' '); ?>
                <span class="detail-unit">€</span>
            </div>
        </div>
    </div>
    
    <div class="summary-section">
        <div class="summary-title">📊 Détails du calcul</div>
        <div class="summary-row">
            <span class="summary-label">Budget <?php echo $type_budget; ?></span>
            <span class="summary-value"><?php echo number_format($budget, 2, ',', ' '); ?> €</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Durée</span>
            <span class="summary-value"><?php echo $duree . ' ' . $type_duree; ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Multiplicateur</span>
            <span class="summary-value">× <?php echo $duree; ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Budget Total Estimé</span>
            <span class="summary-value summary-total"><?php echo number_format($budget_total, 2, ',', ' '); ?> €</span>
        </div>
    </div>
    
    <div class="actions">
        <a href="listMesDemandes.php" class="btn btn-secondary">← Retour à la liste</a>
        <a href="addDemandeplanning.php" class="btn btn-primary">➕ Nouvelle demande</a>
    </div>
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
    document.querySelectorAll('a,button').forEach(el=>{
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