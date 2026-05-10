<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
mb_internal_encoding('UTF-8');

/* Cle API Gemini — https://aistudio.google.com/apikey */
define('GEMINI_API_KEY', 'AIzaSyCmYcPVYIUpbVfAz-GsGsViuD4cM4EWp4I<');

/* Types de contenu disponibles */
$types = ['mythe', 'proverbe', 'fait_historique', 'chiffre', 'etude_scientifique'];

/* Forcer un type via GET ou choisir aleatoirement */
$type = $_GET['type'] ?? $types[array_rand($types)];

$labels = [
    'mythe'              => 'Mythe',
    'proverbe'           => 'Proverbe',
    'fait_historique'    => 'Fait historique',
    'chiffre'            => 'Chiffre surprenant',
    'etude_scientifique' => 'Etude scientifique',
];

/* ── Prompt selon le type ─────────────────────────────────── */
$prompts = [
    'mythe' =>
        "Genere un mythe alimentaire ou nutritionnel interessant que beaucoup de personnes croient encore. "
        ."Il doit etre lie a la nourriture, aux aliments, a la sante ou a la nutrition. "
        ."Explique pourquoi c'est un mythe avec des elements scientifiques.",

    'proverbe' =>
        "Genere un proverbe ou citation celebre lie a l'alimentation, la nutrition ou la sante. "
        ."Il peut etre en arabe, tunisien, francais ou latin avec sa traduction. "
        ."Explique sa signification et son lien avec la science moderne.",

    'fait_historique' =>
        "Genere un fait historique fascinant et peu connu lie a un aliment, une pratique alimentaire "
        ."ou un evenement historique qui a change notre facon de manger. "
        ."Il doit etre verifiable et surprenant.",

    'chiffre' =>
        "Genere un chiffre ou statistique surprenante et verifiable lie a la nutrition, "
        ."l'alimentation mondiale, la sante, ou l'impact ecologique des aliments. "
        ."Le chiffre doit etre etonnant et faire reflechir.",

    'etude_scientifique' =>
        "Genere une decouverte ou etude scientifique recente (2020-2024) sur la nutrition, "
        ."les aliments ou la sante. Elle doit etre accessible, fascinante et changer "
        ."potentiellement la facon dont on percoit un aliment courant.",
];

$prompt = "Tu es un expert en nutrition, histoire alimentaire et sante.\n"
        . $prompts[$type] . "\n\n"
        . "Reponds UNIQUEMENT en JSON valide (sans markdown, sans backticks) en francais :\n"
        . "{\n"
        . "  \"type\": \"" . $type . "\",\n"
        . "  \"titre\": \"Titre accrocheur (max 10 mots)\",\n"
        . "  \"resume\": \"Texte court affiché dans le panneau (2-3 phrases max, 60 mots max)\",\n"
        . "  \"detail\": \"Explication complete et approfondie (5-8 phrases, elements scientifiques, contexte)\",\n"
        . "  \"chiffre_cle\": \"Un chiffre ou donnee marquante lié au contenu (ex: 73%, 1850, 2.4 kg) ou null\",\n"
        . "  \"source\": \"Source ou auteur (ex: OMS 2023, Harvard Medical School, Hippocrate)\",\n"
        . "  \"sentiment\": {\n"
        . "    \"ton\": \"inspirant|alarmant|surprenant|neutre\",\n"
        . "    \"score\": 75\n"
        . "  },\n"
        . "  \"defi\": \"Un defi concret et simple que l'utilisateur peut faire aujourd'hui\"\n"
        . "}";

/* ── Fallback local si API indisponible ───────────────────── */
if (!function_exists('curl_init') || GEMINI_API_KEY === 'VOTRE_CLE_GEMINI_ICI') {
    echo json_encode(fallbackCulture($type), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Appel Gemini ─────────────────────────────────────────── */
$apiUrl  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . GEMINI_API_KEY;
$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature' => 0.9, 'maxOutputTokens' => 700,
        'responseMimeType' => 'application/json',
    ],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200) {
    echo json_encode(fallbackCulture($type), JSON_UNESCAPED_UNICODE);
    exit;
}

$rd      = json_decode($response, true);
$content = $rd['candidates'][0]['content']['parts'][0]['text'] ?? '';
$content = trim(preg_replace(['/^```json\s*/i','/\s*```$/i'], '', $content));
$parsed  = json_decode($content, true);

echo json_encode(
    json_last_error() === JSON_ERROR_NONE ? $parsed : fallbackCulture($type),
    JSON_UNESCAPED_UNICODE
);

/* ── Fallback : contenus statiques de qualite ─────────────── */
function fallbackCulture(string $type): array {
    $contenus = [
        'mythe' => [
            'type'       => 'mythe',
            'titre'      => 'Manger des carottes ameliore la vision nocturne',
            'resume'     => 'Ce mythe vient d\'une campagne de propagande britannique de 1940. La RAF inventait des histoires sur ses pilotes pour cacher l\'existence du radar.',
            'detail'     => 'En 1940, la RAF britannique commenca a detruire les avions ennemis grace au radar, une technologie top secrete. Pour cacher cette avancee technologique, le ministere de l\'information lanca une campagne affirmant que les pilotes voyaient si bien la nuit grace a leur regime riche en carottes. Les carottes contiennent effectivement de la beta-carotene, precurseur de la vitamine A, necessaire pour la vision en faible lumiere. Mais elles ne donnent de supervisions qu\'en cas de carence avancee, ce qui est tres rare.',
            'chiffre_cle'=> '1940',
            'source'     => 'Imperial War Museum, Londres',
            'sentiment'  => ['ton' => 'surprenant', 'score' => 80],
            'defi'       => 'Mangez une carotte aujourd\'hui, non pas pour voir dans le noir, mais pour votre apport en antioxydants.',
        ],
        'proverbe' => [
            'type'       => 'proverbe',
            'titre'      => 'Que ton aliment soit ta seule medecine',
            'resume'     => 'Ce principe d\'Hippocrate, pere de la medecine, est aujourd\'hui confirme par la nutrigénomique : certains aliments modifient l\'expression de nos genes.',
            'detail'     => 'Hippocrate, medecin grec du Ve siecle avant J.-C., avait compris bien avant la science moderne que ce que nous mangeons influence directement notre sante. La nutrigénomique, discipline nee dans les annees 2000, etudie comment les nutriments interagissent avec nos genes. Par exemple, la curcumine du curcuma inhibe les genes pro-inflammatoires, les acides gras omega-3 activent les genes anti-inflammatoires, et le sulforaphane du brocoli active des genes de protection contre le cancer.',
            'chiffre_cle'=> '400 av. J.-C.',
            'source'     => 'Hippocrate, Corpus Hippocraticum',
            'sentiment'  => ['ton' => 'inspirant', 'score' => 85],
            'defi'       => 'Ajoutez du curcuma et du poivre noir dans votre repas d\'aujourd\'hui pour maximiser l\'absorption de curcumine.',
        ],
        'fait_historique' => [
            'type'       => 'fait_historique',
            'titre'      => 'Le pain blanc : symbole de modernite devenu probleme de sante',
            'resume'     => 'Pendant des millenaires, les humains mangeaient du pain complet. C\'est l\'industrialisation du XIXe siecle qui a impose le pain blanc raffine, supprimant le son et le germe.',
            'detail'     => 'Jusqu\'au XIXe siecle, tout le monde mangeait du pain complet ou semi-complet. Avec l\'avenement des moulins industriels capables de produire une farine tres blanche, le pain blanc devint un symbole de richesse et de modernite. Les classes aisees l\'adopterent en masse. Le probleme : ce processus elimine 80% des fibres, 70% des vitamines B et 60% des mineraux. Ce n\'est que dans les annees 1970 que les chercheurs ont commence a documenter les effets negatifs de cette transition alimentaire sur la sante digestive et metabolique.',
            'chiffre_cle'=> '80%',
            'source'     => 'OMS, History of Food Processing',
            'sentiment'  => ['ton' => 'alarmant', 'score' => 65],
            'defi'       => 'Remplacez votre pain habituel par du pain complet ou au cereales pendant 7 jours.',
        ],
        'chiffre' => [
            'type'       => 'chiffre',
            'titre'      => '73% des adultes manquent de vitamine D',
            'resume'     => 'Selon l\'OMS 2023, plus de 3 adultes sur 4 dans le monde presentent une carence ou insuffisance en vitamine D, liee a la depression, aux maladies cardiaques et au risque de cancer.',
            'detail'     => 'La vitamine D est souvent appelee "vitamine du soleil" car notre peau la synthetise sous l\'effet des rayons UV. Mais avec le mode de vie moderne (travail en interieur, ecrans solaires, zones peu ensoleillees), la carence est devenue epidemique. Les consequences incluent une immunite affaiblie, un risque accru de depression saisonniere, d\'osteoporose et de maladies cardiovasculaires. Les aliments riches en vitamine D incluent les poissons gras, les oeufs et les champignons exposes au soleil.',
            'chiffre_cle'=> '73%',
            'source'     => 'OMS, Rapport mondial sur la nutrition 2023',
            'sentiment'  => ['ton' => 'alarmant', 'score' => 60],
            'defi'       => 'Passez 20 minutes en plein soleil aujourd\'hui (avant 11h ou apres 16h), c\'est votre dose quotidienne de vitamine D.',
        ],
        'etude_scientifique' => [
            'type'       => 'etude_scientifique',
            'titre'      => 'Le microbiome intestinal : notre deuxieme cerveau',
            'resume'     => 'Une etude de Stanford (2022) montre qu\'un regime riche en fibres fermentees diversifie le microbiome intestinal en 2 semaines et reduit les marqueurs d\'inflammation de 17%.',
            'detail'     => 'Le microbiome intestinal contient 38 000 milliards de bacteries, soit autant que nos cellules humaines. Des chercheurs de Stanford ont montre en 2022 que 10 portions quotidiennes de vegetaux fermentees ou riches en fibres suffisent a transformer radicalement la composition bacterienne intestinale. Cette diversification reduit les cytokines pro-inflammatoires associees au diabete de type 2, aux maladies cardiaques et a certains cancers. Le plus etonnant : ces changements apparaissent des la deuxieme semaine.',
            'chiffre_cle'=> '17%',
            'source'     => 'Stanford Medicine, Cell Journal 2022',
            'sentiment'  => ['ton' => 'inspirant', 'score' => 82],
            'defi'       => 'Ajoutez un aliment fermente a votre repas aujourd\'hui : yaourt, kefir, choucroute ou kimchi.',
        ],
    ];
    return $contenus[$type] ?? $contenus['mythe'];
}
?>
