<?php
/* ================================================================
   helpers/repas_helpers.php
   RÔLE : Fonctions utilitaires pour les 3 fonctionnalités
          innovantes du module Gestion des Repas.

   CE FICHIER NE CONTIENT PAS DE HTML ni de logique BDD.
   Il est inclus par les Vues (fo_repaslist.php) et le Controller.

   ┌──────────────────────────────────────────────────────────────┐
   │  FONCTIONNALITÉ 1 : Score écologique du repas               │
   │    → scoreEcologique()  : calcule le score 0–100            │
   │    → labelEcologique()  : retourne label + couleur          │
   ├──────────────────────────────────────────────────────────────┤
   │  FONCTIONNALITÉ 2 : Recommandations & détection d'erreurs   │
   │    → analyseNutritionnelle() : détecte les déséquilibres    │
   │    → recommandations()       : suggestions contextuelles    │
   ├──────────────────────────────────────────────────────────────┤
   │  FONCTIONNALITÉ 3 : Description automatique du repas        │
   │    → descriptionRepas() : texte après validation            │
   └──────────────────────────────────────────────────────────────┘
   ================================================================ */


/* ================================================================
   ══ FONCTIONNALITÉ 1 — SCORE ÉCOLOGIQUE ══

   Calcule un score entre 0 (très polluant) et 100 (très écologique)
   à partir des totaux nutritionnels du repas.

   Critères utilisés :
     - CO₂ total du repas         (poids le plus important : 60%)
     - Fibres totales              (favorise les végétaux : 20%)
     - Proportion de sucre/glucides(transformés = polluants : 20%)
   ================================================================ */

/**
 * scoreEcologique() — Calcule le score écologique (0-100)
 *
 * @param array $totaux  Résultat de getTotauxNutritionnels()
 *                       (total_co2, total_fibres, total_sucre,
 *                        total_glucides, total_poids)
 * @return int  Score entre 0 (nul) et 100 (parfait)
 */
function scoreEcologique(array $totaux): int {
    if (empty($totaux) || ($totaux['total_poids'] ?? 0) <= 0) return 0;

    $poids = (float) $totaux['total_poids'];

    /* ── Critère 1 : CO₂ ramené à 1 kg d'aliment ───────────────
       Seuils : < 1 kg CO₂/kg → excellent, > 5 kg → très mauvais */
    $co2ParKg  = ($totaux['total_co2'] ?? 0) / ($poids / 1000);
    $scoreCo2  = max(0, min(100, 100 - ($co2ParKg / 6) * 100));

    /* ── Critère 2 : Fibres totales ─────────────────────────────
       Objectif OMS : ≥ 25g/jour → > 10g dans un repas = bon     */
    $fibres      = (float) ($totaux['total_fibres'] ?? 0);
    $scoreFibres = min(100, ($fibres / 10) * 100);

    /* ── Critère 3 : Rapport sucre / glucides ───────────────────
       Si > 50% des glucides sont du sucre = mauvais score        */
    $glucides = (float) ($totaux['total_glucides'] ?? 1);
    $sucre    = (float) ($totaux['total_sucre']    ?? 0);
    $ratioSucre  = $glucides > 0 ? $sucre / $glucides : 0;
    $scoreSucre  = max(0, min(100, 100 - ($ratioSucre * 100)));

    /* ── Score final pondéré ─────────────────────────────────── */
    $score = ($scoreCo2 * 0.60) + ($scoreFibres * 0.20) + ($scoreSucre * 0.20);

    return (int) round($score);
}

/**
 * labelEcologique() — Retourne le label + couleurs selon le score
 *
 * @param  int   $score  Résultat de scoreEcologique()
 * @return array { label, emoji, color, bg, desc }
 */
function labelEcologique(int $score): array {
    if ($score >= 80) return [
        'label' => 'Repas écologique',
        'emoji' => '🌿',
        'color' => '#1a372f',
        'bg'    => '#e8f0e9',
        'bar'   => '#4caf50',
        'desc'  => 'Excellent choix pour la planète !',
    ];
    if ($score >= 60) return [
        'label' => 'Repas acceptable',
        'emoji' => '🌱',
        'color' => '#4a7a50',
        'bg'    => '#f1f8e9',
        'bar'   => '#8bc34a',
        'desc'  => 'Bon bilan, quelques ajustements possibles.',
    ];
    if ($score >= 40) return [
        'label' => 'Repas à améliorer',
        'emoji' => '⚠️',
        'color' => '#8a6510',
        'bg'    => '#fff9e6',
        'bar'   => '#ffc107',
        'desc'  => 'Impact modéré, des substitutions sont conseillées.',
    ];
    if ($score >= 20) return [
        'label' => 'Repas polluant',
        'emoji' => '🌫️',
        'color' => '#c07020',
        'bg'    => '#fff3e0',
        'bar'   => '#ff9800',
        'desc'  => 'Impact élevé, privilégiez des aliments locaux.',
    ];
    return [
        'label' => 'Repas très polluant',
        'emoji' => '🔴',
        'color' => '#8a2020',
        'bg'    => '#faeaea',
        'bar'   => '#f44336',
        'desc'  => 'Impact très fort. Revoyez la composition du repas.',
    ];
}


/* ================================================================
   ══ FONCTIONNALITÉ 2 — RECOMMANDATIONS & DÉTECTION D'ERREURS ══

   Analyse la composition nutritionnelle du repas et produit :
     - Des alertes (problèmes détectés)
     - Des recommandations contextuelles selon :
         • l'heure (petit déj / déjeuner / dîner)
         • la saison (printemps / été / automne / hiver)
         • le poids total (gaspillage potentiel)
   ================================================================ */

/**
 * detecterMomentJournee() — Détermine le moment de la journée
 * selon l'heure de la date du repas.
 *
 * @param  string $dateRepas  Format "Y-m-d H:i:s" ou "Y-m-d\TH:i"
 * @return string 'petit_dej' | 'dejeuner' | 'diner' | 'collation'
 */
function detecterMomentJournee(string $dateRepas): string {
    $h = (int) date('H', strtotime($dateRepas));
    if ($h >= 6  && $h < 10) return 'petit_dej';
    if ($h >= 11 && $h < 15) return 'dejeuner';
    if ($h >= 18 && $h < 23) return 'diner';
    return 'collation';
}

/**
 * detecterSaison() — Détecte la saison selon le mois
 *
 * @param  string $dateRepas
 * @return string 'printemps' | 'ete' | 'automne' | 'hiver'
 */
function detecterSaison(string $dateRepas): string {
    $m = (int) date('m', strtotime($dateRepas));
    if (in_array($m, [3,4,5]))  return 'printemps';
    if (in_array($m, [6,7,8]))  return 'ete';
    if (in_array($m, [9,10,11]))return 'automne';
    return 'hiver';
}

/**
 * analyseNutritionnelle() — Détecte les déséquilibres du repas
 *
 * Retourne un tableau d'alertes, chacune avec :
 *   type    : 'erreur' | 'warning' | 'gaspillage'
 *   emoji   : icône représentative
 *   message : description du problème détecté
 *
 * @param  array  $totaux   Résultat de getTotauxNutritionnels()
 * @param  string $moment   Résultat de detecterMomentJournee()
 * @return array  Liste des alertes
 */
function analyseNutritionnelle(array $totaux, string $moment): array {
    $alertes = [];
    if (empty($totaux)) return $alertes;

    $cal    = (float)($totaux['total_calories']  ?? 0);
    $prot   = (float)($totaux['total_proteines'] ?? 0);
    $gluc   = (float)($totaux['total_glucides']  ?? 0);
    $lip    = (float)($totaux['total_lipides']   ?? 0);
    $fibres = (float)($totaux['total_fibres']    ?? 0);
    $sucre  = (float)($totaux['total_sucre']     ?? 0);
    $sodium = (float)($totaux['total_sodium']    ?? 0);
    $poids  = (float)($totaux['total_poids']     ?? 0);

    /* Objectifs nutritionnels par moment de la journée */
    $objectifs = [
        'petit_dej' => ['cal_max'=>600,  'cal_min'=>250, 'prot_min'=>10, 'lip_max'=>20],
        'dejeuner'  => ['cal_max'=>900,  'cal_min'=>400, 'prot_min'=>20, 'lip_max'=>35],
        'diner'     => ['cal_max'=>700,  'cal_min'=>300, 'prot_min'=>15, 'lip_max'=>25],
        'collation' => ['cal_max'=>300,  'cal_min'=>50,  'prot_min'=>5,  'lip_max'=>15],
    ];
    $obj = $objectifs[$moment];

    /* ── Détection des erreurs nutritionnelles ─────────────── */

    /* Trop de calories */
    if ($cal > $obj['cal_max']) {
        $alertes[] = [
            'type'    => 'erreur',
            'emoji'   => '🔥',
            'message' => "Trop calorique : " . round($cal) . " kcal (max recommandé : {$obj['cal_max']} kcal pour ce repas).",
        ];
    }
    /* Pas assez de calories */
    if ($cal > 0 && $cal < $obj['cal_min']) {
        $alertes[] = [
            'type'    => 'warning',
            'emoji'   => '⚡',
            'message' => "Repas trop léger : " . round($cal) . " kcal. Ajoutez des aliments énergétiques.",
        ];
    }
    /* Manque de protéines */
    if ($cal > 0 && $prot < $obj['prot_min']) {
        $alertes[] = [
            'type'    => 'erreur',
            'emoji'   => '💪',
            'message' => "Manque de protéines : " . round($prot,1) . "g (minimum recommandé : {$obj['prot_min']}g).",
        ];
    }
    /* Trop de lipides */
    if ($lip > $obj['lip_max']) {
        $alertes[] = [
            'type'    => 'erreur',
            'emoji'   => '🧈',
            'message' => "Trop de gras : " . round($lip,1) . "g (max recommandé : {$obj['lip_max']}g).",
        ];
    }
    /* Trop de sucre */
    if ($sucre > 0 && $gluc > 0 && ($sucre / $gluc) > 0.5) {
        $alertes[] = [
            'type'    => 'erreur',
            'emoji'   => '🍬',
            'message' => "Trop de sucre : " . round($sucre,1) . "g représentent " . round($sucre/$gluc*100) . "% des glucides.",
        ];
    }
    /* Manque de fibres */
    if ($cal > 200 && $fibres < 5) {
        $alertes[] = [
            'type'    => 'warning',
            'emoji'   => '🥦',
            'message' => "Manque de fibres : " . round($fibres,1) . "g. Ajoutez des légumes ou céréales complètes.",
        ];
    }
    /* Excès de sodium */
    if ($sodium > 800) {
        $alertes[] = [
            'type'    => 'warning',
            'emoji'   => '🧂',
            'message' => "Trop de sodium : " . round($sodium) . "mg. Réduisez les aliments transformés.",
        ];
    }

    /* ── Détection du gaspillage potentiel ─────────────────── */
    if ($poids > 1200) {
        $alertes[] = [
            'type'    => 'gaspillage',
            'emoji'   => '♻️',
            'message' => "Quantité importante : " . round($poids) . "g au total. Tu risques de gaspiller certains aliments.",
        ];
    }

    return $alertes;
}

/**
 * recommandations() — Suggestions contextuelles d'amélioration
 *
 * Produit des recommandations adaptées selon :
 *   - les alertes détectées
 *   - le moment de la journée
 *   - la saison
 *
 * @param  array  $alertes   Résultat de analyseNutritionnelle()
 * @param  string $moment    'petit_dej' | 'dejeuner' | 'diner' | 'collation'
 * @param  string $saison    'printemps' | 'ete' | 'automne' | 'hiver'
 * @return array  Liste de suggestions { emoji, texte }
 */
function recommandations(array $alertes, string $moment, string $saison): array {
    $recs = [];

    /* Libellés lisibles pour les messages */
    $labelsMoment = [
        'petit_dej' => 'petit-déjeuner',
        'dejeuner'  => 'déjeuner',
        'diner'     => 'dîner',
        'collation' => 'collation',
    ];
    $labelsSaison = [
        'printemps' => 'printemps', 'ete' => 'été',
        'automne'   => 'automne',   'hiver' => 'hiver',
    ];
    $lm = $labelsMoment[$moment] ?? 'repas';
    $ls = $labelsSaison[$saison] ?? '';

    /* ── Recommandations selon les erreurs détectées ────────── */
    foreach ($alertes as $alerte) {
        switch (true) {

            case str_contains($alerte['message'], 'Trop calorique'):
                $recs[] = ['emoji'=>'🥗', 'texte'=>"Pour un $lm, remplacez un aliment riche en calories par une salade ou des légumes vapeur."];
                break;

            case str_contains($alerte['message'], 'trop léger'):
                if ($moment === 'petit_dej')
                    $recs[] = ['emoji'=>'🥚', 'texte'=>"Ajoutez des œufs ou du fromage blanc pour un petit-déjeuner complet."];
                elseif ($moment === 'dejeuner')
                    $recs[] = ['emoji'=>'🫘', 'texte'=>"Ajoutez des légumineuses (lentilles, pois chiches) pour un déjeuner plus rassasiant."];
                else
                    $recs[] = ['emoji'=>'🍲', 'texte'=>"Enrichissez votre repas avec des céréales complètes ou des protéines maigres."];
                break;

            case str_contains($alerte['message'], 'protéines'):
                if ($moment === 'petit_dej')
                    $recs[] = ['emoji'=>'🥛', 'texte'=>"Pour un petit-déjeuner protéiné, ajoutez du yaourt grec, des œufs ou des amandes."];
                elseif ($moment === 'dejeuner')
                    $recs[] = ['emoji'=>'🐟', 'texte'=>"Pour le déjeuner, privilégiez le poisson, le poulet ou les légumineuses comme source de protéines."];
                else
                    $recs[] = ['emoji'=>'🍗', 'texte'=>"Au dîner, une portion de viande maigre ou de tofu apportera les protéines manquantes."];
                break;

            case str_contains($alerte['message'], 'gras'):
                $recs[] = ['emoji'=>'🫒', 'texte'=>"Remplacez les matières grasses saturées par de l'huile d'olive. Évitez les fritures."];
                break;

            case str_contains($alerte['message'], 'sucre'):
                $recs[] = ['emoji'=>'🍓', 'texte'=>"Substituez les sucres ajoutés par des fruits frais de saison ($ls)."];
                break;

            case str_contains($alerte['message'], 'fibres'):
                $saisons_legumes = [
                    'printemps' => 'asperges, petits pois ou épinards frais',
                    'ete'       => 'courgettes, tomates ou haricots verts',
                    'automne'   => 'carottes, courges ou poireaux',
                    'hiver'     => 'brocoli, chou ou navets',
                ];
                $legumes = $saisons_legumes[$saison] ?? 'légumes frais';
                $recs[] = ['emoji'=>'🥦', 'texte'=>"En $ls, ajoutez des $legumes pour enrichir votre repas en fibres."];
                break;

            case str_contains($alerte['message'], 'sodium'):
                $recs[] = ['emoji'=>'🌿', 'texte'=>"Remplacez le sel par des herbes aromatiques fraîches : basilic, menthe, coriandre."];
                break;

            case str_contains($alerte['message'], 'gaspiller'):
                $recs[] = ['emoji'=>'📦', 'texte'=>"Pensez à conserver les restes au réfrigérateur. Un repas pour 2 personnes ne devrait pas dépasser 800g."];
                $recs[] = ['emoji'=>'♻️', 'texte'=>"Planifiez vos quantités : préférez plusieurs petits repas équilibrés à un seul très copieux."];
                break;
        }
    }

    /* ── Recommandations contextuelles par moment ───────────── */
    if (empty($alertes)) {
        /* Si aucune erreur, on donne quand même un conseil contextuel */
        if ($moment === 'petit_dej') {
            $recs[] = ['emoji'=>'☀️', 'texte'=>"Parfait pour bien démarrer la journée ! Pensez à bien vous hydrater."];
        } elseif ($moment === 'dejeuner') {
            $recs[] = ['emoji'=>'🌿', 'texte'=>"Excellent déjeuner ! Une courte marche après le repas optimise la digestion."];
        } elseif ($moment === 'diner') {
            $recs[] = ['emoji'=>'🌙', 'texte'=>"Bon dîner ! Évitez les activités intenses dans l'heure qui suit."];
        }
    }

    /* ── Conseil saisonnier toujours présent ─────────────────── */
    $conseilsSaison = [
        'printemps' => ['emoji'=>'🌸', 'texte'=>"Au printemps, profitez des légumes nouveaux : radis, asperges et petits pois sont de saison."],
        'ete'       => ['emoji'=>'☀️', 'texte'=>"En été, hydratez-vous avec des aliments riches en eau : concombres, pastèques, tomates."],
        'automne'   => ['emoji'=>'🍂', 'texte'=>"En automne, les courges et les champignons sont excellents et riches en nutriments."],
        'hiver'     => ['emoji'=>'❄️', 'texte'=>"En hiver, privilégiez les soupes et les légumes racines pour vous réchauffer et renforcer l'immunité."],
    ];
    if (isset($conseilsSaison[$saison])) {
        $recs[] = $conseilsSaison[$saison];
    }

    return $recs;
}


/* ================================================================
   ══ FONCTIONNALITÉ 3 — DESCRIPTION AUTOMATIQUE DU REPAS ══

   Génère une description narrative du repas après sa validation,
   affichée quand l'utilisateur clique sur un repas.
   ================================================================ */

/**
 * descriptionRepas() — Génère la description textuelle du repas
 *
 * @param  array  $totaux   Résultat de getTotauxNutritionnels()
 * @param  array  $aliments Résultat de getAlimentsOfRepas()
 * @param  int    $score    Résultat de scoreEcologique()
 * @param  string $moment   Résultat de detecterMomentJournee()
 * @return array  { resume, details[], badges[] }
 */
function descriptionRepas(array $totaux, array $aliments, int $score, string $moment): array {
    if (empty($totaux) || empty($aliments)) {
        return ['resume'=>'Repas sans données nutritionnelles.', 'details'=>[], 'badges'=>[]];
    }

    $cal    = (float)($totaux['total_calories']  ?? 0);
    $prot   = (float)($totaux['total_proteines'] ?? 0);
    $fibres = (float)($totaux['total_fibres']    ?? 0);
    $lip    = (float)($totaux['total_lipides']   ?? 0);
    $sucre  = (float)($totaux['total_sucre']     ?? 0);
    $co2    = (float)($totaux['total_co2']       ?? 0);

    $details = [];
    $badges  = [];

    /* ── Résumé de la richesse nutritionnelle ───────────────── */
    $resume = "C'est un repas ";
    $parties = [];

    /* Caractère calorique */
    if ($cal < 300)       $parties[] = "léger";
    elseif ($cal < 600)   $parties[] = "modéré";
    elseif ($cal < 900)   $parties[] = "copieux";
    else                  $parties[] = "très calorique";

    /* Richesse en fibres */
    if ($fibres >= 10) {
        $parties[] = "riche en fibres";
        $badges[]  = ['emoji'=>'🌾', 'label'=>'Riche en fibres', 'color'=>'#1a372f', 'bg'=>'#e8f0e9'];
    }
    /* Richesse en protéines */
    if ($prot >= 20) {
        $parties[] = "riche en protéines";
        $badges[]  = ['emoji'=>'💪', 'label'=>'Riche en protéines', 'color'=>'#1a5fa8', 'bg'=>'#e8f2fc'];
    }
    /* Pauvre en graisses */
    if ($lip < 10) {
        $parties[] = "faible en graisses";
        $badges[]  = ['emoji'=>'✅', 'label'=>'Faible en graisses', 'color'=>'#4a7a50', 'bg'=>'#f1f8e9'];
    }

    $resume .= implode(', ', $parties) . ". ";

    /* ── Impact écologique ───────────────────────────────────── */
    $lbl = labelEcologique($score);
    if ($score >= 80) {
        $resume .= "Il a un impact écologique très faible.";
        $badges[] = ['emoji'=>'🌿', 'label'=>'Très écologique', 'color'=>'#1a372f', 'bg'=>'#e8f0e9'];
    } elseif ($score >= 60) {
        $resume .= "Son impact écologique est acceptable.";
        $badges[] = ['emoji'=>'🌱', 'label'=>'Écologique', 'color'=>'#4a7a50', 'bg'=>'#f1f8e9'];
    } elseif ($score >= 40) {
        $resume .= "Il a un impact écologique modéré.";
        $badges[] = ['emoji'=>'⚠️', 'label'=>'Impact modéré', 'color'=>'#8a6510', 'bg'=>'#fff9e6'];
    } else {
        $resume .= "Son impact écologique est élevé.";
        $badges[] = ['emoji'=>'🔴', 'label'=>'Impact élevé', 'color'=>'#8a2020', 'bg'=>'#faeaea'];
    }

    /* ── Détails complémentaires ─────────────────────────────── */
    $details[] = "🍽️ " . count($aliments) . " aliment(s) · " . round($totaux['total_poids'] ?? 0) . "g au total";
    $details[] = "🔥 " . round($cal) . " kcal · " . round($prot,1) . "g protéines · " . round($lip,1) . "g lipides";
    $details[] = "🌍 Empreinte carbone estimée : " . round($co2, 2) . " kg CO₂eq";

    /* ── Adapté au moment ────────────────────────────────────── */
    $labMoment = [
        'petit_dej' => '☀️ Petit-déjeuner',
        'dejeuner'  => '🌤️ Déjeuner',
        'diner'     => '🌙 Dîner',
        'collation' => '🍎 Collation',
    ];
    $badges[] = ['emoji'=>'🕐', 'label'=> $labMoment[$moment] ?? 'Repas', 'color'=>'#5a5850', 'bg'=>'#f4ede4'];

    return compact('resume', 'details', 'badges');
}
?>
