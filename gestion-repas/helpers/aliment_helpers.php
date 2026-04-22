<?php


//  Couleur par type d'aliment 
function typeConfig(string $type): array {
    $map = [
        'légume'             => ['bg'=>'#e8f0e9','stroke'=>'#1a372f','fill'=>'#1a372f','tag_bg'=>'#d4e4d5','tag_color'=>'#1a372f'],
        'fruit'              => ['bg'=>'#f0eaf8','stroke'=>'#a78bfa','fill'=>'#7c5cbf','tag_bg'=>'#e8dff5','tag_color'=>'#5b3e9e'],
        'céréale'            => ['bg'=>'#fdf5e8','stroke'=>'#c9a44a','fill'=>'#8a6a1e','tag_bg'=>'#f5e8c8','tag_color'=>'#7a5a10'],
        'protéines animales' => ['bg'=>'#e8f2fc','stroke'=>'#60a5fa','fill'=>'#1a5fa8','tag_bg'=>'#d0e8fa','tag_color'=>'#1a5fa8'],
        'légumineuse'        => ['bg'=>'#f4ede4','stroke'=>'#a78bfa','fill'=>'#5b3e9e','tag_bg'=>'#ede0f8','tag_color'=>'#5b3e9e'],
        'produit laitier'    => ['bg'=>'#eaf5f0','stroke'=>'#1a372f','fill'=>'#0d4a30','tag_bg'=>'#d0ebdf','tag_color'=>'#0d4a30'],
        'huile'              => ['bg'=>'#fdf5e8','stroke'=>'#d4a43a','fill'=>'#8a6510','tag_bg'=>'#f5e8c0','tag_color'=>'#7a5508'],
        'épice'              => ['bg'=>'#faeaf0','stroke'=>'#c060a0','fill'=>'#8a2060','tag_bg'=>'#f0d0e4','tag_color'=>'#7a1050'],
        'autre'              => ['bg'=>'#f4ede4','stroke'=>'#888780','fill'=>'#5a5850','tag_bg'=>'#e8e0d8','tag_color'=>'#4a4840'],
    ];
    return $map[$type] ?? $map['autre'];
}

// SVG par nom
function alimentSVG(string $nom, string $type, array $c, int $size = 44): string {
    $bg = $c['bg']; $s = $c['stroke']; $f = $c['fill'];
    $n  = strtolower(trim($nom));

    $inner = '';
    if (str_contains($n,'carotte'))
        $inner = "<path d='M22 30 L16 18 Q20 14 24 18 Z' fill='$f' opacity='.9'/><path d='M22 18 L20 11 M22 18 L24 10 M22 18 L26 13' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>";
    elseif (str_contains($n,'tomate'))
        $inner = "<circle cx='22' cy='24' r='9' fill='$f' opacity='.85'/><path d='M22 15 L22 11 M20 16 L17 12 M24 16 L27 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/><ellipse cx='19' cy='22' rx='2' ry='3' fill='$bg' opacity='.3'/>";
    elseif (str_contains($n,'brocoli'))
        $inner = "<rect x='20' y='24' width='4' height='7' rx='1' fill='$f' opacity='.7'/><circle cx='22' cy='21' r='5' fill='$f' opacity='.85'/><circle cx='17' cy='23' r='4' fill='$f' opacity='.75'/><circle cx='27' cy='23' r='4' fill='$f' opacity='.75'/>";
    elseif (str_contains($n,'épinard') || str_contains($n,'epinard'))
        $inner = "<path d='M22 32 C16 28 12 22 14 16 C18 10 26 12 28 18 C30 24 26 30 22 32Z' fill='$f' opacity='.85'/><line x1='22' y1='32' x2='22' y2='18' stroke='$bg' stroke-width='1.2' opacity='.6'/>";
    elseif (str_contains($n,'courgette'))
        $inner = "<ellipse cx='22' cy='24' rx='8' ry='5' fill='$f' opacity='.85' transform='rotate(-30 22 24)'/><path d='M26 14 L28 11 M28 16 L30 13' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>";
    elseif (str_contains($n,'aubergine'))
        $inner = "<ellipse cx='22' cy='25' rx='7' ry='9' fill='$f' opacity='.85'/><path d='M22 16 C22 16 20 11 24 10' stroke='$f' stroke-width='1.4' stroke-linecap='round' fill='none'/>";
    elseif (str_contains($n,'poivron'))
        $inner = "<path d='M15 20 C14 15 18 12 22 13 C26 12 30 15 29 20 C28 26 25 31 22 31 C19 31 16 26 15 20Z' fill='$f' opacity='.85'/><path d='M22 13 L22 10 M22 10 L20 8' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>";
    elseif (str_contains($n,'concombre'))
        $inner = "<ellipse cx='22' cy='23' rx='6' ry='10' fill='$f' opacity='.8' transform='rotate(20 22 23)'/><line x1='16' y1='16' x2='28' y2='30' stroke='$bg' stroke-width='1.2' opacity='.4'/>";
    elseif (str_contains($n,'pomme de terre') || str_contains($n,'patate'))
        $inner = "<ellipse cx='22' cy='23' rx='9' ry='7' fill='$f' opacity='.8'/><circle cx='17' cy='19' r='1.5' fill='$bg' opacity='.6'/><circle cx='26' cy='22' r='1.2' fill='$bg' opacity='.5'/><circle cx='20' cy='27' r='1' fill='$bg' opacity='.5'/>";
    elseif (str_contains($n,'oignon'))
        $inner = "<path d='M22 29 C16 29 13 24 14 19 C15 14 22 12 22 12 C22 12 29 14 30 19 C31 24 28 29 22 29Z' fill='$f' opacity='.8'/><path d='M19 12 C19 9 25 9 25 12' stroke='$f' stroke-width='1.2' fill='none'/>";
    elseif (str_contains($n,'ail') && !str_contains($n,'rail'))
        $inner = "<path d='M22 30 C17 30 13 26 14 21 C15 16 19 14 22 14 C25 14 29 16 30 21 C31 26 27 30 22 30Z' fill='$f' opacity='.75'/><path d='M22 14 L22 10' stroke='$f' stroke-width='1.5' stroke-linecap='round'/>";
    elseif (str_contains($n,'salade') || str_contains($n,'laitue'))
        $inner = "<path d='M22 30 C14 28 11 20 15 15 C19 10 25 12 22 18 C19 12 28 10 31 16 C34 22 30 29 22 30Z' fill='$f' opacity='.8'/><circle cx='22' cy='24' r='4' fill='$f' opacity='.6'/>";
    elseif (str_contains($n,'pomme') && !str_contains($n,'terre'))
        $inner = "<path d='M22 28 C16 28 13 23 14 18 C15 13 22 14 22 14 C22 14 29 13 30 18 C31 23 28 28 22 28Z' fill='$f' opacity='.85'/><path d='M22 14 L23 10 C24 8 27 9 27 9' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>";
    elseif (str_contains($n,'banane'))
        $inner = "<path d='M13 28 C13 20 16 13 22 12 C28 11 32 15 31 20 C30 25 26 28 22 28 C18 28 14 27 13 28Z' fill='$f' opacity='.8'/><path d='M22 12 L24 9' stroke='$f' stroke-width='1.4' stroke-linecap='round'/>";
    elseif (str_contains($n,'orange') || str_contains($n,'clémentine') || str_contains($n,'mandarine'))
        $inner = "<circle cx='22' cy='23' r='9' fill='$f' opacity='.85'/><path d='M22 14 L22 10 M20 15 L18 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/><line x1='22' y1='14' x2='22' y2='32' stroke='$bg' stroke-width='.8' opacity='.3'/><line x1='13' y1='23' x2='31' y2='23' stroke='$bg' stroke-width='.8' opacity='.3'/>";
    elseif (str_contains($n,'fraise'))
        $inner = "<path d='M22 31 C17 31 13 26 14 21 C15 16 22 14 22 14 C22 14 29 16 30 21 C31 26 27 31 22 31Z' fill='$f' opacity='.85'/><path d='M19 14 L17 11 M22 14 L22 10 M25 14 L27 11' stroke='$f' stroke-width='1.2' stroke-linecap='round'/><circle cx='19' cy='22' r='1' fill='$bg' opacity='.5'/><circle cx='25' cy='20' r='1' fill='$bg' opacity='.5'/><circle cx='22' cy='26' r='1' fill='$bg' opacity='.5'/>";
    elseif (str_contains($n,'raisin'))
        $inner = "<circle cx='18' cy='20' r='4' fill='$f' opacity='.9'/><circle cx='26' cy='20' r='4' fill='$f' opacity='.85'/><circle cx='22' cy='26' r='4' fill='$f' opacity='.8'/><circle cx='15' cy='26' r='3' fill='$f' opacity='.7'/><circle cx='29' cy='26' r='3' fill='$f' opacity='.7'/><path d='M22 16 L22 12 M22 12 L25 9' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>";
    elseif (str_contains($n,'melon') || str_contains($n,'pastèque'))
        $inner = "<path d='M12 22 A10 10 0 0 1 32 22 Z' fill='$f' opacity='.85'/><path d='M12 22 A10 10 0 0 0 32 22' stroke='$s' stroke-width='1' fill='none'/><line x1='17' y1='22' x2='20' y2='15' stroke='$bg' stroke-width='1' opacity='.4'/><line x1='22' y1='22' x2='22' y2='13' stroke='$bg' stroke-width='1' opacity='.4'/><line x1='27' y1='22' x2='24' y2='15' stroke='$bg' stroke-width='1' opacity='.4'/>";
    elseif (str_contains($n,'poulet'))
        $inner = "<ellipse cx='22' cy='25' rx='8' ry='6' fill='$f' opacity='.85'/><path d='M14 25 C12 20 13 16 17 15 C19 14 21 16 22 19 C23 16 25 14 27 15 C31 16 32 20 30 25' fill='$f' opacity='.6'/><path d='M20 15 C20 12 23 11 24 13' stroke='$f' stroke-width='1.2' fill='none' stroke-linecap='round'/>";
    elseif (str_contains($n,'boeuf') || str_contains($n,'bœuf') || str_contains($n,'viande'))
        $inner = "<path d='M14 28 C13 23 15 17 20 15 L24 15 C29 17 31 23 30 28 Z' fill='$f' opacity='.8'/><line x1='19' y1='29' x2='17' y2='34' stroke='$f' stroke-width='2' stroke-linecap='round'/><line x1='25' y1='29' x2='27' y2='34' stroke='$f' stroke-width='2' stroke-linecap='round'/>";
    elseif (str_contains($n,'poisson') || str_contains($n,'saumon') || str_contains($n,'thon') || str_contains($n,'sardine') || str_contains($n,'merlan'))
        $inner = "<path d='M30 22 C26 17 16 17 12 22 C16 27 26 27 30 22Z' fill='$f' opacity='.85'/><path d='M30 22 L35 17 L35 27 Z' fill='$f' opacity='.7'/><circle cx='14' cy='21' r='1.2' fill='$bg' opacity='.7'/><line x1='20' y1='18' x2='20' y2='26' stroke='$bg' stroke-width='.8' opacity='.3'/>";
    elseif (str_contains($n,'oeuf') || str_contains($n,'œuf'))
        $inner = "<path d='M22 13 C17 13 13 18 13 23 C13 28 17 32 22 32 C27 32 31 28 31 23 C31 18 27 13 22 13Z' fill='$f' opacity='.75'/><circle cx='22' cy='24' r='5' fill='$f' opacity='.9'/>";
    elseif (str_contains($n,'lait') && !str_contains($n,'laitier'))
        $inner = "<path d='M16 18 L16 30 Q16 32 18 32 L26 32 Q28 32 28 30 L28 18 Z' fill='$f' opacity='.8'/><path d='M14 18 L30 18 L28 14 L16 14 Z' fill='$f' opacity='.55'/><ellipse cx='22' cy='25' rx='4' ry='2' fill='$bg' opacity='.4'/>";
    elseif (str_contains($n,'yaourt') || str_contains($n,'yogourt'))
        $inner = "<path d='M15 17 L15 30 Q15 32 18 32 L26 32 Q29 32 29 30 L29 17 Z' fill='$f' opacity='.8'/><rect x='14' y='14' width='16' height='4' rx='2' fill='$f' opacity='.6'/><path d='M18 24 Q22 20 26 24' stroke='$bg' stroke-width='1.2' fill='none' opacity='.5' stroke-linecap='round'/>";
    elseif (str_contains($n,'fromage'))
        $inner = "<path d='M12 28 L22 14 L32 28 Z' fill='$f' opacity='.85'/><circle cx='20' cy='24' r='1.5' fill='$bg' opacity='.6'/><circle cx='25' cy='22' r='1.2' fill='$bg' opacity='.5'/><circle cx='17' cy='27' r='1' fill='$bg' opacity='.5'/>";
    elseif (str_contains($n,'riz'))
        $inner = "<ellipse cx='22' cy='22' rx='9' ry='7' fill='$f' opacity='.2'/><ellipse cx='18' cy='20' rx='2' ry='1' fill='$f' opacity='.9' transform='rotate(-20 18 20)'/><ellipse cx='22' cy='19' rx='2' ry='1' fill='$f' opacity='.9'/><ellipse cx='26' cy='20' rx='2' ry='1' fill='$f' opacity='.9' transform='rotate(20 26 20)'/><ellipse cx='19' cy='24' rx='2' ry='1' fill='$f' opacity='.85' transform='rotate(10 19 24)'/><ellipse cx='23' cy='25' rx='2' ry='1' fill='$f' opacity='.85' transform='rotate(-15 23 25)'/>";
    elseif (str_contains($n,'pain'))
        $inner = "<path d='M12 26 C12 18 16 14 22 14 C28 14 32 18 32 26 L30 28 L14 28 Z' fill='$f' opacity='.85'/><line x1='14' y1='28' x2='30' y2='28' stroke='$f' stroke-width='2' stroke-linecap='round'/><path d='M17 20 Q22 17 27 20' stroke='$bg' stroke-width='1' fill='none' opacity='.4'/>";
    elseif (str_contains($n,'pâte') || str_contains($n,'pasta') || str_contains($n,'spaghetti') || str_contains($n,'macaroni'))
        $inner = "<path d='M14 16 C16 20 14 26 16 30' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/><path d='M19 14 C21 18 19 24 21 28' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/><path d='M24 15 C26 19 24 25 26 29' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/>";
    elseif (str_contains($n,'lentille'))
        $inner = "<ellipse cx='17' cy='21' rx='4' ry='3' fill='$f' opacity='.9'/><ellipse cx='27' cy='21' rx='4' ry='3' fill='$f' opacity='.85'/><ellipse cx='22' cy='27' rx='4' ry='3' fill='$f' opacity='.8'/><ellipse cx='16' cy='28' rx='3' ry='2' fill='$f' opacity='.7'/><ellipse cx='28' cy='28' rx='3' ry='2' fill='$f' opacity='.7'/>";
    elseif (str_contains($n,'pois chiche'))
        $inner = "<circle cx='19' cy='21' r='5' fill='$f' opacity='.9'/><circle cx='27' cy='23' r='5' fill='$f' opacity='.85'/><path d='M19 16 C19 12 27 12 27 18' stroke='$f' stroke-width='1.2' fill='none' stroke-linecap='round'/>";
    elseif (str_contains($n,'haricot'))
        $inner = "<path d='M15 25 C13 19 16 13 21 13 C26 13 30 18 29 24 C28 30 22 32 18 29 C16 27 15 26 15 25Z' fill='$f' opacity='.8'/>";
    elseif (str_contains($n,'huile'))
        $inner = "<path d='M19 13 L19 17 L15 21 L15 29 Q15 31 18 31 L26 31 Q29 31 29 29 L29 21 L25 17 L25 13 Z' fill='$f' opacity='.75'/><line x1='19' y1='13' x2='25' y2='13' stroke='$f' stroke-width='1.5' stroke-linecap='round'/><ellipse cx='22' cy='26' rx='4' ry='2' fill='$bg' opacity='.3'/>";
    elseif (str_contains($n,'cumin') || str_contains($n,'cannelle') || str_contains($n,'curcuma') || str_contains($n,'gingembre') || str_contains($n,'paprika') || str_contains($n,'harissa'))
        $inner = "<path d='M22 11 L24.5 18 L31 18 L25.5 22.5 L27.5 30 L22 26 L16.5 30 L18.5 22.5 L13 18 L19.5 18 Z' fill='$f' opacity='.85'/>";
    elseif (str_contains($n,'avocat'))
        $inner = "<path d='M22 11 C17 11 13 17 13 23 C13 29 17 33 22 33 C27 33 31 29 31 23 C31 17 27 11 22 11Z' fill='$f' opacity='.75'/><ellipse cx='22' cy='25' rx='5' ry='6' fill='$f' opacity='.9'/>";
    elseif (str_contains($n,'noix') || str_contains($n,'amande') || str_contains($n,'noisette'))
        $inner = "<path d='M22 13 C17 13 13 18 14 24 C15 29 18 32 22 32 C26 32 29 29 30 24 C31 18 27 13 22 13Z' fill='$f' opacity='.8'/><line x1='22' y1='13' x2='22' y2='32' stroke='$bg' stroke-width='.9' opacity='.35'/>";
    else {
        // Fallback générique par type
        $fallbacks = [
            'légume'             => "<path d='M22 31 C13 27 13 15 22 13 C31 15 31 27 22 31Z' fill='$f' opacity='.85'/><path d='M22 13 L22 9 M19 15 L16 12 M25 15 L28 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>",
            'fruit'              => "<circle cx='22' cy='24' r='8' fill='$f' opacity='.85'/><path d='M22 16 C22 16 20 10 25 9' stroke='$f' stroke-width='1.5' stroke-linecap='round' fill='none'/>",
            'céréale'            => "<rect x='17' y='27' width='10' height='5' rx='2' fill='$f' opacity='.9'/><line x1='22' y1='27' x2='22' y2='13' stroke='$f' stroke-width='1.5'/><path d='M22 14 L19 18 M22 14 L25 18 M22 19 L19 23 M22 19 L25 23' stroke='$f' stroke-width='1.2' stroke-linecap='round'/>",
            'protéines animales' => "<ellipse cx='22' cy='25' rx='7' ry='5' fill='$f' opacity='.85'/><path d='M15 25 C13 21 14 17 17 16 C19 15 21 17 22 19 C23 17 25 15 27 16 C30 17 31 21 29 25' fill='$f' opacity='.6'/>",
            'légumineuse'        => "<ellipse cx='17' cy='24' rx='5' ry='6' fill='$f' opacity='.9'/><ellipse cx='27' cy='24' rx='5' ry='6' fill='$f' opacity='.7'/><path d='M17 18 C17 13 27 13 27 18' stroke='$f' stroke-width='1.3' fill='none' stroke-linecap='round'/>",
            'produit laitier'    => "<path d='M16 18 L16 29 Q16 31 18 31 L26 31 Q28 31 28 29 L28 18 Z' fill='$f' opacity='.85'/><path d='M14 18 L30 18 L28 15 L16 15 Z' fill='$f' opacity='.55'/>",
            'huile'              => "<path d='M19 13 L19 17 L15 21 L15 29 Q15 31 18 31 L26 31 Q29 31 29 29 L29 21 L25 17 L25 13 Z' fill='$f' opacity='.75'/><line x1='19' y1='13' x2='25' y2='13' stroke='$f' stroke-width='1.5' stroke-linecap='round'/>",
            'épice'              => "<path d='M22 11 L24.5 18 L31 18 L25.5 22.5 L27.5 30 L22 26 L16.5 30 L18.5 22.5 L13 18 L19.5 18 Z' fill='$f' opacity='.85'/>",
            'autre'              => "<circle cx='22' cy='22' r='8' fill='$f' opacity='.45'/><circle cx='22' cy='22' r='4' fill='$f' opacity='.85'/>",
        ];
        $inner = $fallbacks[$type] ?? $fallbacks['autre'];
    }

    return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='$size' height='$size'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        $inner
    </svg>";
}

// Couleur barre CO₂ 
function co2Config(float $co2): array {
    $pct = min(100, ($co2 / 10) * 100);
    if ($co2 < 1)  return ['pct'=>$pct,'color'=>'#1a372f','bg'=>'#e8f0e9','label'=>'Faible',  'desc'=>'Excellent choix pour la planète'];
    if ($co2 < 5)  return ['pct'=>$pct,'color'=>'#c9a44a','bg'=>'#fdf5e8','label'=>'Moyen',   'desc'=>'Impact modéré sur l\'environnement'];
    return             ['pct'=>$pct,'color'=>'#8a2020','bg'=>'#faeaea','label'=>'Élevé',    'desc'=>'Impact important sur l\'environnement'];
}

//  Nutri-Score simplifié 
function nutriScore(array $a): array {
    $score = 0;
    if ($a['calories']  < 100) $score += 2; elseif ($a['calories']  < 200) $score += 1; else $score -= 1;
    if ($a['proteines'] > 10)  $score += 2;
    if ($a['fibres']    > 3)   $score += 2;
    if ($a['sucre']     < 5)   $score += 1;
    if ($a['lipides']   < 5)   $score += 1;
    if ($a['sodium']    < 200) $score += 1;
    if ($score >= 7) return ['grade'=>'A','color'=>'#fff','bg'=>'#1a372f'];
    if ($score >= 5) return ['grade'=>'B','color'=>'#fff','bg'=>'#4a7a50'];
    if ($score >= 3) return ['grade'=>'C','color'=>'#1a372f','bg'=>'#f4ede4'];
    if ($score >= 1) return ['grade'=>'D','color'=>'#fff','bg'=>'#c9a44a'];
    return                 ['grade'=>'E','color'=>'#fff','bg'=>'#8a2020'];
}

// Répartition macros en % calorique 
function macroPercents(array $a): array {
    $total = ($a['proteines'] * 4) + ($a['glucides'] * 4) + ($a['lipides'] * 9);
    if ($total <= 0) return ['prot'=>0,'gluc'=>0,'lip'=>0];
    return [
        'prot' => round($a['proteines'] * 4 / $total * 100),
        'gluc' => round($a['glucides']  * 4 / $total * 100),
        'lip'  => round($a['lipides']   * 9 / $total * 100),
    ];
}
