<?php
error_reporting(0);
ini_set('display_errors', '0');
require_once __DIR__ . '/../../../controller/Sportsommeil.controller.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); die('Planning introuvable'); }

$ctrl  = new SportSommeilController();
$lignes = $ctrl->getPlanningCompletByDemande($id);
if (empty($lignes)) { http_response_code(404); die('Planning introuvable ou vide.'); }

// Même logique que planning.html : getMoment() sur la description
function getMoment($desc) {
    $d = mb_strtolower($desc ?? '');
    if (preg_match('/petit.d[eé]j|matin|breakfast/i', $d)) return 'petit-dejeuner';
    if (preg_match('/d[eé]jeuner|midi|lunch/i', $d))       return 'dejeuner';
    if (preg_match('/d[iî]ner|soir|dinner/i', $d))         return 'diner';
    if (preg_match('/collation|snack|go[uû]ter/i', $d))    return 'collation';
    return null;
}

function parseKcal($d) {
    if (preg_match('/(\d+)\s*kcal/i', $d, $m)) return (int)$m[1];
    return 0;
}
function parseEur($d) {
    if (preg_match('/([\d]+(?:[.,]\d+)?)\s*[€e]/i', $d, $m)) return (float)str_replace(',','.',$m[1]);
    return 0;
}
function cleanDesc($d) {
    $d = preg_replace('/^[^:]+:\s*/u', '', $d); // retire "Petit-déjeuner : " en début
    $d = preg_replace('/\(\s*\d+\s*kcal[^)]*\)/i', '', $d);
    $d = preg_replace('/\(\s*[\d.,]+\s*[€e][^)]*\)/i', '', $d);
    return trim($d, ' ,:');
}

$cats = [
    'petit-dejeuner' => ['label'=>'Petit-déjeuner', 'color'=>'#e8a838', 'emoji'=>'🌅'],
    'dejeuner'       => ['label'=>'Déjeuner',        'color'=>'#3db87a', 'emoji'=>'☀️'],
    'diner'          => ['label'=>'Dîner',            'color'=>'#7b6eea', 'emoji'=>'🌙'],
    'collation'      => ['label'=>'Collation',        'color'=>'#f39c12', 'emoji'=>'🍎'],
    'sport'          => ['label'=>'Sport',            'color'=>'#e85757', 'emoji'=>'🏃'],
    'sommeil'        => ['label'=>'Sommeil',          'color'=>'#4aa8d8', 'emoji'=>'💤'],
];
$ORDRE = ['petit-dejeuner','dejeuner','diner','collation'];

// Grouper par date
$groupes   = [];
$rawRepas  = [];
$dates     = [];

foreach ($lignes as $l) {
    $date = substr($l['date'] ?? '', 0, 10);
    $type = trim($l['type_activite'] ?? '');
    $desc = trim($l['description'] ?? '');
    if (!$date || !$desc) continue;

    if (!isset($groupes[$date])) {
        $groupes[$date] = ['petit-dejeuner'=>[],'dejeuner'=>[],'diner'=>[],'collation'=>[],'sport'=>[],'sommeil'=>[]];
        $rawRepas[$date] = [];
        $dates[] = $date;
    }

    if ($type === 'repas')   $rawRepas[$date][] = $desc;
    elseif ($type === 'sport')   $groupes[$date]['sport'][]   = $desc;
    elseif ($type === 'sommeil') $groupes[$date]['sommeil'][] = $desc;
}

$dates = array_unique($dates);
sort($dates);

// Distribuer les repas dans petit-dej / dejeuner / diner
foreach ($dates as $date) {
    $avecCle = []; $sansCle = [];
    foreach ($rawRepas[$date] as $desc) {
        $m = getMoment($desc);
        if ($m) $avecCle[] = ['desc'=>$desc,'moment'=>$m];
        else    $sansCle[] = $desc;
    }
    foreach ($avecCle as $item) $groupes[$date][$item['moment']][] = $item['desc'];
    $slots = array_filter($ORDRE, fn($k) => empty($groupes[$date][$k]));
    $slots = array_values($slots);
    foreach ($sansCle as $i => $desc) {
        $slot = $slots[$i] ?? 'dejeuner';
        $groupes[$date][$slot][] = $desc;
    }
}

$DNs = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
$today = date('Y-m-d');

// Stats pour l'en-tête
$totalK = 0; $totalE = 0;
foreach ($lignes as $l) {
    if (($l['type_activite']??'') === 'repas') {
        $totalK += parseKcal($l['description']??'');
        $totalE += parseEur($l['description']??'');
    }
}
$nbJ  = count($dates);
$moyK = $nbJ > 0 ? round($totalK / $nbJ) : 0;
$moyE = $nbJ > 0 ? round($totalE / $nbJ, 2) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Planning #<?= $id ?> — SmartNutrition</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0d1117;color:#e8e4d8;min-height:100vh;padding:16px}
.header{text-align:center;padding:20px 0 16px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:20px}
.header h1{font-size:1.4rem;font-weight:700}
.header p{color:#888;font-size:.82rem;margin-top:4px}
.badge{display:inline-block;background:rgba(46,204,113,.15);color:#2ecc71;border:1px solid rgba(46,204,113,.3);border-radius:20px;padding:3px 12px;font-size:.73rem;font-weight:700;margin-top:8px}
.stats{display:flex;justify-content:center;gap:20px;margin:14px 0 20px;flex-wrap:wrap}
.stat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:10px 18px;text-align:center}
.stat-val{font-size:1.1rem;font-weight:700;color:#e8e4d8}
.stat-lbl{font-size:.7rem;color:#888;margin-top:2px}
.days{display:flex;gap:12px;overflow-x:auto;padding-bottom:10px}
.days::-webkit-scrollbar{height:4px}
.days::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}
.day-col{flex:0 0 210px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:12px;overflow:hidden}
.day-head{padding:10px 14px;font-weight:700;font-size:.83rem;text-align:center;border-bottom:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.05)}
.day-head.today{background:rgba(26,115,232,.2);color:#6ab0f5;border-bottom-color:rgba(26,115,232,.3)}
.cat-block{padding:9px 11px;border-bottom:1px solid rgba(255,255,255,.04)}
.cat-block:last-child{border-bottom:none}
.cat-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;display:flex;align-items:center;gap:4px}
.cat-item{font-size:.76rem;color:#c8c4b8;line-height:1.45;padding:2px 0 2px 10px;position:relative}
.cat-item:before{content:'•';position:absolute;left:0;opacity:.35}
.cat-badges{display:flex;gap:4px;margin-top:3px;flex-wrap:wrap}
.bk{font-size:.65rem;padding:1px 6px;border-radius:8px;font-weight:700}
.bk-k{background:rgba(255,140,0,.12);color:#ffa040}
.bk-e{background:rgba(46,204,113,.1);color:#2ecc71}
.footer{text-align:center;margin-top:24px;padding-top:14px;border-top:1px solid rgba(255,255,255,.05);font-size:.75rem;color:#555}
@media(max-width:600px){.day-col{flex:0 0 82vw}.stats{gap:10px}}
</style>
</head>
<body>

<div class="header">
  <h1>📅 Planning #<?= $id ?></h1>
  <p>Planning nutritionnel personnalisé — SmartNutrition · <?= $nbJ ?> jours</p>
  <span class="badge">✅ Approuvé</span>
</div>

<div class="stats">
  <div class="stat"><div class="stat-val"><?= $moyK ? number_format($moyK,0,',',' ').' kcal' : '—' ?></div><div class="stat-lbl">Moy. kcal/jour</div></div>
  <div class="stat"><div class="stat-val"><?= $moyE ? $moyE.' €' : '—' ?></div><div class="stat-lbl">Budget/jour</div></div>
  <div class="stat"><div class="stat-val"><?= $nbJ ?> jours</div><div class="stat-lbl">Durée</div></div>
</div>

<div class="days">
<?php foreach ($dates as $ds):
  $dt     = new DateTime($ds);
  $dn     = $DNs[(int)$dt->format('w')];
  $dnum   = $dt->format('d/m');
  $isToday = ($ds === $today);
?>
  <div class="day-col">
    <div class="day-head<?= $isToday ? ' today' : '' ?>">
      <?= $dn ?> <?= $dnum ?><?= $isToday ? ' · Aujourd\'hui' : '' ?>
    </div>

    <?php foreach ($cats as $key => $cat):
      $items = $groupes[$ds][$key] ?? [];
      if (empty($items)) continue;
    ?>
    <div class="cat-block">
      <div class="cat-label" style="color:<?= $cat['color'] ?>"><?= $cat['emoji'] ?> <?= $cat['label'] ?></div>
      <?php foreach ($items as $desc):
        $k = parseKcal($desc);
        $e = parseEur($desc);
        $t = cleanDesc($desc);
      ?>
        <div class="cat-item"><?= htmlspecialchars($t) ?></div>
        <?php if ($k || $e): ?>
        <div class="cat-badges">
          <?php if ($k): ?><span class="bk bk-k">🔥 <?= $k ?> kcal</span><?php endif; ?>
          <?php if ($e): ?><span class="bk bk-e">💰 <?= number_format($e,2,',',' ') ?> €</span><?php endif; ?>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>

<div class="footer">GaiaLumen · SmartNutrition</div>
</body>
</html>