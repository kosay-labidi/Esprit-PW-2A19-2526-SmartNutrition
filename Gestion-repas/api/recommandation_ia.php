<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
mb_internal_encoding('UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Methode non autorisee.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* CLE API — obtenir gratuitement sur https://aistudio.google.com/apikey */
define('GEMINI_API_KEY', 'AIzaSyDnub3xXxuhVanPOEZPLw8k1xxXqa11f4c');

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || empty($data['aliments'])) {
    echo json_encode(['error' => 'Donnees invalides.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$aliments  = $data['aliments'];
$totaux    = $data['totaux'];
$moment    = $data['moment']    ?? 'dejeuner';
$saison    = $data['saison']    ?? 'printemps';
$score_eco = (int)($data['score_eco'] ?? 0);

$lms = ['petit_dej'=>'petit-dejeuner','dejeuner'=>'dejeuner','diner'=>'diner','collation'=>'collation'];
$lss = ['printemps'=>'printemps','ete'=>'ete','automne'=>'automne','hiver'=>'hiver'];
$lm  = $lms[$moment] ?? 'repas';
$ls  = $lss[$saison]  ?? 'saison';

/* Si cURL absent ou cle non configuree -> fallback local */
if (!function_exists('curl_init') || GEMINI_API_KEY === 'VOTRE_CLE_GEMINI_ICI') {
    echo json_encode(fallback($totaux, $lm, $ls, $score_eco), JSON_UNESCAPED_UNICODE);
    exit;
}

$alimTexte = implode(', ', array_map(fn($a) => $a['nom'].' ('.$a['quantite'].'g)', $aliments));
$cal  = round($totaux['calories']  ?? 0, 1);
$prot = round($totaux['proteines'] ?? 0, 1);
$gluc = round($totaux['glucides']  ?? 0, 1);
$lip  = round($totaux['lipides']   ?? 0, 1);
$fib  = round($totaux['fibres']    ?? 0, 1);
$suc  = round($totaux['sucre']     ?? 0, 1);
$sod  = round($totaux['sodium']    ?? 0, 0);
$co2  = round($totaux['co2']       ?? 0, 2);

$prompt = "Tu es un nutritionniste expert.\nRepas ({$lm} en {$ls}) : {$alimTexte}\n"
        . "Calories:{$cal}kcal | Proteines:{$prot}g | Glucides:{$gluc}g | Lipides:{$lip}g\n"
        . "Fibres:{$fib}g | Sucre:{$suc}g | Sodium:{$sod}mg | CO2:{$co2}kg | Score eco:{$score_eco}/100\n\n"
        . "Reponds UNIQUEMENT en JSON valide (sans markdown), en francais :\n"
        . "{\"bilan\":\"phrase courte\",\"problemes\":[\"p1\"],\"recommandations\":"
        . "[{\"emoji\":\"e\",\"titre\":\"t\",\"texte\":\"conseil\"}],"
        . "\"conseil_saison\":{\"emoji\":\"e\",\"texte\":\"conseil saison\"},"
        . "\"note_eco\":\"note\"}";

$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key='.GEMINI_API_KEY);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS     => json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['temperature'=>0.7,'maxOutputTokens'=>800,'responseMimeType'=>'application/json']]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

/* Toute erreur -> fallback local */
if ($err || $code !== 200) {
    echo json_encode(fallback($totaux, $lm, $ls, $score_eco), JSON_UNESCAPED_UNICODE);
    exit;
}

$rd      = json_decode($response, true);
$content = $rd['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($content)) {
    echo json_encode(fallback($totaux, $lm, $ls, $score_eco), JSON_UNESCAPED_UNICODE);
    exit;
}

$content = trim(preg_replace('/^```json\s*/i','',preg_replace('/\s*```$/i','',$content)));
$parsed  = json_decode($content, true);

echo json_encode(
    json_last_error() === JSON_ERROR_NONE ? $parsed : fallback($totaux, $lm, $ls, $score_eco),
    JSON_UNESCAPED_UNICODE
);

/* ============================================================
   Fallback : recommandations calculees localement
   Activees si : cURL absent, cle invalide, erreur API
   ============================================================ */
function fallback(array $t, string $lm, string $ls, int $score): array {
    $cal  = (float)($t['calories']  ?? 0);
    $prot = (float)($t['proteines'] ?? 0);
    $lip  = (float)($t['lipides']   ?? 0);
    $fib  = (float)($t['fibres']    ?? 0);
    $suc  = (float)($t['sucre']     ?? 0);
    $gluc = max(1, (float)($t['glucides'] ?? 1));
    $sod  = (float)($t['sodium']    ?? 0);
    $co2  = (float)($t['co2']       ?? 0);

    $seuils = [
        'petit-dejeuner' => ['mx'=>600,'mn'=>250,'pm'=>10,'lm'=>20],
        'dejeuner'       => ['mx'=>900,'mn'=>400,'pm'=>20,'lm'=>35],
        'diner'          => ['mx'=>700,'mn'=>300,'pm'=>15,'lm'=>25],
        'collation'      => ['mx'=>300,'mn'=>50, 'pm'=>5, 'lm'=>15],
    ];
    $s = $seuils[$lm] ?? $seuils['dejeuner'];

    $problemes = [];
    $recos     = [];

    if ($cal > $s['mx']) {
        $problemes[] = "Repas trop calorique : {$cal} kcal (max {$s['mx']} kcal pour un {$lm}).";
        $recos[]     = ['emoji'=>'🥗','titre'=>'Allegez le repas','texte'=>"Remplacez un aliment calorique par des legumes vapeur ou une salade de saison ({$ls})."];
    }
    if ($cal > 0 && $cal < $s['mn']) {
        $problemes[] = "Repas trop leger : {$cal} kcal, insuffisant pour un {$lm}.";
        $recos[]     = ['emoji'=>'⚡','titre'=>'Enrichissez le repas','texte'=>"Ajoutez des cereales completes ou des legumineuses pour avoir l'energie necessaire."];
    }
    if ($prot < $s['pm'] && $cal > 0) {
        $problemes[] = "Manque de proteines : {$prot}g (minimum {$s['pm']}g pour un {$lm}).";
        $conseils    = ['petit-dejeuner'=>"des oeufs ou du yaourt grec",'dejeuner'=>"du poisson ou des pois chiches",'diner'=>"une proteine maigre ou du tofu",'collation'=>"des amandes ou du fromage blanc"];
        $recos[]     = ['emoji'=>'💪','titre'=>'Augmentez les proteines','texte'=>"Ajoutez ".($conseils[$lm]??"des proteines")." pour atteindre l'apport recommande."];
    }
    if ($lip > $s['lm']) {
        $problemes[] = "Trop de graisses : {$lip}g (max {$s['lm']}g).";
        $recos[]     = ['emoji'=>'🫒','titre'=>'Reduisez les graisses','texte'=>"Remplacez les graisses saturees par de l'huile d'olive. Evitez les fritures."];
    }
    if (($suc / $gluc) > 0.5) {
        $problemes[] = "Trop de sucre : {$suc}g = ".round($suc/$gluc*100)."% des glucides.";
        $recos[]     = ['emoji'=>'🍓','titre'=>'Limitez le sucre','texte'=>"Substituez les sucres ajoutes par des fruits frais de {$ls}."];
    }
    if ($cal > 200 && $fib < 5) {
        $problemes[] = "Manque de fibres : {$fib}g seulement.";
        $recos[]     = ['emoji'=>'🥦','titre'=>'Ajoutez des fibres','texte'=>"Integrez des legumes ou cereales completes pour enrichir ce repas."];
    }
    if ($co2 > 3) {
        $problemes[] = "Impact CO2 eleve : {$co2} kg.";
        $recos[]     = ['emoji'=>'🌍','titre'=>'Reduisez l\'empreinte carbone','texte'=>"Remplacez une proteine animale par des legumineuses (lentilles, pois chiches)."];
    }

    if (empty($recos))
        $recos[] = ['emoji'=>'✅','titre'=>'Bon equilibre !','texte'=>"Ce repas est bien equilibre pour un {$lm}. Continuez ainsi !"];

    $saisons = [
        'printemps' => ['emoji'=>'🌸','texte'=>"Au printemps : asperges, radis et petits pois sont ideaux."],
        'ete'       => ['emoji'=>'☀️', 'texte'=>"En ete : concombres, tomates et melon pour rester hydrate."],
        'automne'   => ['emoji'=>'🍂','texte'=>"En automne : courges et champignons, nutritifs et de saison."],
        'hiver'     => ['emoji'=>'❄️', 'texte'=>"En hiver : soupes de legumes racines pour l'immunite."],
    ];

    $bilan = $score >= 70 ? "Repas equilibre avec bon score ecologique ({$score}/100)."
           : ($score >= 40 ? "Repas acceptable, des ajustements sont possibles (score {$score}/100)."
                           : "Repas a ameliorer nutritionnellement et ecologiquement (score {$score}/100).");

    $note_eco = $co2 > 5 ? "CO2 tres eleve ({$co2} kg). Privilegiez les proteines vegetales."
              : ($co2 > 2 ? "Impact CO2 modere ({$co2} kg). Des substitutions ecologiques sont possibles."
                          : "Bon bilan carbone ({$co2} kg). Continuez a choisir des aliments durables.");

    return [
        'bilan'           => $bilan,
        'problemes'       => $problemes,
        'recommandations' => array_slice($recos, 0, 4),
        'conseil_saison'  => $saisons[$ls] ?? ['emoji'=>'🌿','texte'=>"Privilegiez les aliments locaux."],
        'note_eco'        => $note_eco,
    ];
}
?>
