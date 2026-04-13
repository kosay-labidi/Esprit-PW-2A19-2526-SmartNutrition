<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/aliment.php';

$alimentModel = new Aliment();
$aliments = $alimentModel->getAll();
$success = $_GET['success'] ?? '';

// ── Couleurs par type ──────────────────────────────────────────────
function typeConfig($type) {
    $map = [
        'légume'             => ['bg'=>'#EAF3DE','stroke'=>'#639922','fill'=>'#3B6D11','tag_bg'=>'#EAF3DE','tag_color'=>'#3B6D11'],
        'fruit'              => ['bg'=>'#FAECE7','stroke'=>'#D85A30','fill'=>'#993C1D','tag_bg'=>'#FAECE7','tag_color'=>'#993C1D'],
        'céréale'            => ['bg'=>'#FAEEDA','stroke'=>'#BA7517','fill'=>'#854F0B','tag_bg'=>'#FAEEDA','tag_color'=>'#854F0B'],
        'protéines animales' => ['bg'=>'#E6F1FB','stroke'=>'#378ADD','fill'=>'#185FA5','tag_bg'=>'#E6F1FB','tag_color'=>'#185FA5'],
        'légumineuse'        => ['bg'=>'#EEEDFE','stroke'=>'#7F77DD','fill'=>'#3C3489','tag_bg'=>'#EEEDFE','tag_color'=>'#3C3489'],
        'produit laitier'    => ['bg'=>'#E1F5EE','stroke'=>'#1D9E75','fill'=>'#085041','tag_bg'=>'#E1F5EE','tag_color'=>'#085041'],
        'huile'              => ['bg'=>'#FAEEDA','stroke'=>'#EF9F27','fill'=>'#633806','tag_bg'=>'#FAEEDA','tag_color'=>'#633806'],
        'épice'              => ['bg'=>'#FBEAF0','stroke'=>'#D4537E','fill'=>'#4B1528','tag_bg'=>'#FBEAF0','tag_color'=>'#4B1528'],
        'autre'              => ['bg'=>'#F1EFE8','stroke'=>'#888780','fill'=>'#5F5E5A','tag_bg'=>'#F1EFE8','tag_color'=>'#5F5E5A'],
    ];
    return $map[$type] ?? $map['autre'];
}

// ── SVG par nom d'aliment (avec fallback par type) ───────────────
function alimentSVG($nom, $type, $c) {
    $bg = $c['bg']; $s = $c['stroke']; $f = $c['fill'];
    $n = strtolower(trim($nom));

    // ── Bibliothèque par nom ──────────────────────────────────────
    // Carotte
    if (str_contains($n,'carotte')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 30 L16 18 Q20 14 24 18 Z' fill='$f' opacity='.9'/>
        <path d='M22 18 L20 11 M22 18 L24 10 M22 18 L26 13' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>
    </svg>";

    // Tomate
    if (str_contains($n,'tomate')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <circle cx='22' cy='24' r='9' fill='$f' opacity='.85'/>
        <path d='M22 15 L22 11 M20 16 L17 12 M24 16 L27 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>
        <ellipse cx='19' cy='22' rx='2' ry='3' fill='$bg' opacity='.3'/>
    </svg>";

    // Brocoli / brocolis
    if (str_contains($n,'brocoli')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <rect x='20' y='24' width='4' height='7' rx='1' fill='$f' opacity='.7'/>
        <circle cx='22' cy='21' r='5' fill='$f' opacity='.85'/>
        <circle cx='17' cy='23' r='4' fill='$f' opacity='.75'/>
        <circle cx='27' cy='23' r='4' fill='$f' opacity='.75'/>
    </svg>";

    // Épinard / épinards
    if (str_contains($n,'épinard') || str_contains($n,'epinard')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 32 C16 28 12 22 14 16 C18 10 26 12 28 18 C30 24 26 30 22 32Z' fill='$f' opacity='.85'/>
        <line x1='22' y1='32' x2='22' y2='18' stroke='$bg' stroke-width='1.2' opacity='.6'/>
        <path d='M22 24 L17 20 M22 28 L27 24' stroke='$bg' stroke-width='.9' opacity='.5'/>
    </svg>";

    // Courgette
    if (str_contains($n,'courgette')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='24' rx='8' ry='5' fill='$f' opacity='.85' transform='rotate(-30 22 24)'/>
        <path d='M26 14 L28 11 M28 16 L30 13' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>
        <line x1='14' y1='24' x2='30' y2='20' stroke='$bg' stroke-width='1' opacity='.4'/>
    </svg>";

    // Aubergine
    if (str_contains($n,'aubergine')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='25' rx='7' ry='9' fill='$f' opacity='.85'/>
        <path d='M22 16 C22 16 20 11 24 10' stroke='$f' stroke-width='1.4' stroke-linecap='round' fill='none'/>
        <ellipse cx='20' cy='23' rx='1.5' ry='3' fill='$bg' opacity='.25'/>
    </svg>";

    // Poivron
    if (str_contains($n,'poivron')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M15 20 C14 15 18 12 22 13 C26 12 30 15 29 20 C28 26 25 31 22 31 C19 31 16 26 15 20Z' fill='$f' opacity='.85'/>
        <path d='M22 13 L22 10 M22 10 L20 8' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>
        <line x1='22' y1='17' x2='22' y2='28' stroke='$bg' stroke-width='1' opacity='.35'/>
    </svg>";

    // Concombre
    if (str_contains($n,'concombre')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='23' rx='6' ry='10' fill='$f' opacity='.8' transform='rotate(20 22 23)'/>
        <line x1='16' y1='16' x2='28' y2='30' stroke='$bg' stroke-width='1.2' opacity='.4'/>
        <line x1='18' y1='20' x2='26' y2='28' stroke='$bg' stroke-width='1' opacity='.3'/>
    </svg>";

    // Pomme de terre / patate
    if (str_contains($n,'pomme de terre') || str_contains($n,'patate')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='23' rx='9' ry='7' fill='$f' opacity='.8'/>
        <circle cx='17' cy='19' r='1.5' fill='$bg' opacity='.6'/>
        <circle cx='26' cy='22' r='1.2' fill='$bg' opacity='.5'/>
        <circle cx='20' cy='27' r='1' fill='$bg' opacity='.5'/>
    </svg>";

    // Oignon
    if (str_contains($n,'oignon')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 29 C16 29 13 24 14 19 C15 14 22 12 22 12 C22 12 29 14 30 19 C31 24 28 29 22 29Z' fill='$f' opacity='.8'/>
        <path d='M19 12 C19 9 25 9 25 12' stroke='$f' stroke-width='1.2' fill='none'/>
        <path d='M17 21 C17 18 27 18 27 21' stroke='$bg' stroke-width='1' fill='none' opacity='.4'/>
        <path d='M17 24 C17 21 27 21 27 24' stroke='$bg' stroke-width='1' fill='none' opacity='.3'/>
    </svg>";

    // Ail
    if (str_contains($n,'ail') && !str_contains($n,'rail')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 30 C17 30 13 26 14 21 C15 16 19 14 22 14 C25 14 29 16 30 21 C31 26 27 30 22 30Z' fill='$f' opacity='.75'/>
        <path d='M22 14 L22 10' stroke='$f' stroke-width='1.5' stroke-linecap='round'/>
        <path d='M18 17 C18 14 26 14 26 17' stroke='$f' stroke-width='1.2' fill='$f' opacity='.5'/>
        <line x1='19' y1='22' x2='25' y2='22' stroke='$bg' stroke-width='.9' opacity='.4'/>
        <line x1='18' y1='25' x2='26' y2='25' stroke='$bg' stroke-width='.9' opacity='.4'/>
    </svg>";

    // Salade / laitue
    if (str_contains($n,'salade') || str_contains($n,'laitue')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 30 C14 28 11 20 15 15 C19 10 25 12 22 18 C19 12 28 10 31 16 C34 22 30 29 22 30Z' fill='$f' opacity='.8'/>
        <circle cx='22' cy='24' r='4' fill='$f' opacity='.6'/>
    </svg>";

    // Pomme
    if (str_contains($n,'pomme') && !str_contains($n,'terre')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 28 C16 28 13 23 14 18 C15 13 22 14 22 14 C22 14 29 13 30 18 C31 23 28 28 22 28Z' fill='$f' opacity='.85'/>
        <path d='M22 14 L23 10 C24 8 27 9 27 9' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>
        <path d='M19 21 C19 18 25 18 25 21' stroke='$bg' stroke-width='.9' fill='none' opacity='.35'/>
    </svg>";

    // Banane
    if (str_contains($n,'banane')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M13 28 C13 20 16 13 22 12 C28 11 32 15 31 20 C30 25 26 28 22 28 C18 28 14 27 13 28Z' fill='$f' opacity='.8'/>
        <path d='M22 12 L24 9' stroke='$f' stroke-width='1.4' stroke-linecap='round'/>
    </svg>";

    // Orange / clémentine / mandarine
    if (str_contains($n,'orange') || str_contains($n,'clémentine') || str_contains($n,'mandarine')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <circle cx='22' cy='23' r='9' fill='$f' opacity='.85'/>
        <path d='M22 14 L22 10 M20 15 L18 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>
        <line x1='22' y1='14' x2='22' y2='32' stroke='$bg' stroke-width='.8' opacity='.3'/>
        <line x1='13' y1='23' x2='31' y2='23' stroke='$bg' stroke-width='.8' opacity='.3'/>
    </svg>";

    // Fraise
    if (str_contains($n,'fraise')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 31 C17 31 13 26 14 21 C15 16 22 14 22 14 C22 14 29 16 30 21 C31 26 27 31 22 31Z' fill='$f' opacity='.85'/>
        <path d='M19 14 L17 11 M22 14 L22 10 M25 14 L27 11' stroke='$f' stroke-width='1.2' stroke-linecap='round'/>
        <circle cx='19' cy='22' r='1' fill='$bg' opacity='.5'/>
        <circle cx='25' cy='20' r='1' fill='$bg' opacity='.5'/>
        <circle cx='22' cy='26' r='1' fill='$bg' opacity='.5'/>
    </svg>";

    // Raisin
    if (str_contains($n,'raisin')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <circle cx='18' cy='20' r='4' fill='$f' opacity='.9'/>
        <circle cx='26' cy='20' r='4' fill='$f' opacity='.85'/>
        <circle cx='22' cy='26' r='4' fill='$f' opacity='.8'/>
        <circle cx='15' cy='26' r='3' fill='$f' opacity='.7'/>
        <circle cx='29' cy='26' r='3' fill='$f' opacity='.7'/>
        <path d='M22 16 L22 12 M22 12 L25 9' stroke='$f' stroke-width='1.3' stroke-linecap='round' fill='none'/>
    </svg>";

    // Melon / pastèque
    if (str_contains($n,'melon') || str_contains($n,'pastèque')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M12 22 A10 10 0 0 1 32 22 Z' fill='$f' opacity='.85'/>
        <path d='M12 22 A10 10 0 0 0 32 22' stroke='$s' stroke-width='1' fill='none'/>
        <line x1='17' y1='22' x2='20' y2='15' stroke='$bg' stroke-width='1' opacity='.4'/>
        <line x1='22' y1='22' x2='22' y2='13' stroke='$bg' stroke-width='1' opacity='.4'/>
        <line x1='27' y1='22' x2='24' y2='15' stroke='$bg' stroke-width='1' opacity='.4'/>
    </svg>";

    // Poulet
    if (str_contains($n,'poulet')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='25' rx='8' ry='6' fill='$f' opacity='.85'/>
        <path d='M14 25 C12 20 13 16 17 15 C19 14 21 16 22 19 C23 16 25 14 27 15 C31 16 32 20 30 25' fill='$f' opacity='.6'/>
        <path d='M20 15 C20 12 23 11 24 13' stroke='$f' stroke-width='1.2' fill='none' stroke-linecap='round'/>
    </svg>";

    // Boeuf / bœuf / viande
    if (str_contains($n,'boeuf') || str_contains($n,'bœuf') || str_contains($n,'viande')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M14 28 C13 23 15 17 20 15 L24 15 C29 17 31 23 30 28 Z' fill='$f' opacity='.8'/>
        <line x1='19' y1='29' x2='17' y2='34' stroke='$f' stroke-width='2' stroke-linecap='round'/>
        <line x1='25' y1='29' x2='27' y2='34' stroke='$f' stroke-width='2' stroke-linecap='round'/>
        <ellipse cx='19' cy='22' rx='2' ry='3' fill='$bg' opacity='.25'/>
    </svg>";

    // Poisson / saumon / thon / sardine / merlan
    if (str_contains($n,'poisson') || str_contains($n,'saumon') || str_contains($n,'thon') || str_contains($n,'sardine') || str_contains($n,'merlan')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M30 22 C26 17 16 17 12 22 C16 27 26 27 30 22Z' fill='$f' opacity='.85'/>
        <path d='M30 22 L35 17 L35 27 Z' fill='$f' opacity='.7'/>
        <circle cx='14' cy='21' r='1.2' fill='$bg' opacity='.7'/>
        <line x1='20' y1='18' x2='20' y2='26' stroke='$bg' stroke-width='.8' opacity='.3'/>
        <line x1='24' y1='18' x2='24' y2='26' stroke='$bg' stroke-width='.8' opacity='.3'/>
    </svg>";

    // Oeuf / œuf
    if (str_contains($n,'oeuf') || str_contains($n,'œuf')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 13 C17 13 13 18 13 23 C13 28 17 32 22 32 C27 32 31 28 31 23 C31 18 27 13 22 13Z' fill='$f' opacity='.75'/>
        <circle cx='22' cy='24' r='5' fill='$f' opacity='.9'/>
    </svg>";

    // Lait
    if (str_contains($n,'lait') && !str_contains($n,'laitier')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M16 18 L16 30 Q16 32 18 32 L26 32 Q28 32 28 30 L28 18 Z' fill='$f' opacity='.8'/>
        <path d='M14 18 L30 18 L28 14 L16 14 Z' fill='$f' opacity='.55'/>
        <ellipse cx='22' cy='25' rx='4' ry='2' fill='$bg' opacity='.4'/>
    </svg>";

    // Yaourt / yogourt
    if (str_contains($n,'yaourt') || str_contains($n,'yogourt')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M15 17 L15 30 Q15 32 18 32 L26 32 Q29 32 29 30 L29 17 Z' fill='$f' opacity='.8'/>
        <rect x='14' y='14' width='16' height='4' rx='2' fill='$f' opacity='.6'/>
        <path d='M18 24 Q22 20 26 24' stroke='$bg' stroke-width='1.2' fill='none' opacity='.5' stroke-linecap='round'/>
    </svg>";

    // Fromage
    if (str_contains($n,'fromage')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M12 28 L22 14 L32 28 Z' fill='$f' opacity='.85'/>
        <circle cx='20' cy='24' r='1.5' fill='$bg' opacity='.6'/>
        <circle cx='25' cy='22' r='1.2' fill='$bg' opacity='.5'/>
        <circle cx='17' cy='27' r='1' fill='$bg' opacity='.5'/>
    </svg>";

    // Riz
    if (str_contains($n,'riz')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='22' cy='22' rx='9' ry='7' fill='$f' opacity='.2'/>
        <ellipse cx='18' cy='20' rx='2' ry='1' fill='$f' opacity='.9' transform='rotate(-20 18 20)'/>
        <ellipse cx='22' cy='19' rx='2' ry='1' fill='$f' opacity='.9'/>
        <ellipse cx='26' cy='20' rx='2' ry='1' fill='$f' opacity='.9' transform='rotate(20 26 20)'/>
        <ellipse cx='19' cy='24' rx='2' ry='1' fill='$f' opacity='.85' transform='rotate(10 19 24)'/>
        <ellipse cx='23' cy='25' rx='2' ry='1' fill='$f' opacity='.85' transform='rotate(-15 23 25)'/>
    </svg>";

    // Pain
    if (str_contains($n,'pain')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M12 26 C12 18 16 14 22 14 C28 14 32 18 32 26 L30 28 L14 28 Z' fill='$f' opacity='.85'/>
        <line x1='14' y1='28' x2='30' y2='28' stroke='$f' stroke-width='2' stroke-linecap='round'/>
        <path d='M17 20 Q22 17 27 20' stroke='$bg' stroke-width='1' fill='none' opacity='.4'/>
    </svg>";

    // Pâtes / pasta / spaghetti / tagliatelle
    if (str_contains($n,'pâte') || str_contains($n,'pasta') || str_contains($n,'spaghetti') || str_contains($n,'tagliatelle') || str_contains($n,'macaroni')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M14 16 C16 20 14 26 16 30' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/>
        <path d='M19 14 C21 18 19 24 21 28' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/>
        <path d='M24 15 C26 19 24 25 26 29' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/>
        <path d='M29 17 C31 21 29 27 31 31' stroke='$f' stroke-width='1.5' fill='none' stroke-linecap='round'/>
    </svg>";

    // Lentilles
    if (str_contains($n,'lentille')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <ellipse cx='17' cy='21' rx='4' ry='3' fill='$f' opacity='.9'/>
        <ellipse cx='27' cy='21' rx='4' ry='3' fill='$f' opacity='.85'/>
        <ellipse cx='22' cy='27' rx='4' ry='3' fill='$f' opacity='.8'/>
        <ellipse cx='16' cy='28' rx='3' ry='2' fill='$f' opacity='.7'/>
        <ellipse cx='28' cy='28' rx='3' ry='2' fill='$f' opacity='.7'/>
    </svg>";

    // Pois chiche
    if (str_contains($n,'pois chiche') || str_contains($n,'pois-chiche')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <circle cx='19' cy='21' r='5' fill='$f' opacity='.9'/>
        <circle cx='27' cy='23' r='5' fill='$f' opacity='.85'/>
        <path d='M19 16 C19 12 27 12 27 18' stroke='$f' stroke-width='1.2' fill='none' stroke-linecap='round'/>
    </svg>";

    // Haricot
    if (str_contains($n,'haricot')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M15 25 C13 19 16 13 21 13 C26 13 30 18 29 24 C28 30 22 32 18 29 C16 27 15 26 15 25Z' fill='$f' opacity='.8'/>
        <path d='M20 14 C22 11 27 11 28 14' stroke='$f' stroke-width='1' fill='none' opacity='.5'/>
    </svg>";

    // Huile d'olive / huile
    if (str_contains($n,'huile')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M19 12 L19 16 L15 20 L15 29 Q15 32 18 32 L26 32 Q29 32 29 29 L29 20 L25 16 L25 12 Z' fill='$f' opacity='.75'/>
        <line x1='19' y1='12' x2='25' y2='12' stroke='$f' stroke-width='1.5' stroke-linecap='round'/>
        <ellipse cx='22' cy='26' rx='4' ry='2' fill='$bg' opacity='.3'/>
        <path d='M20 9 C21 7 24 7 25 9' stroke='$f' stroke-width='1.2' fill='none' stroke-linecap='round'/>
    </svg>";

    // Cumin / cannelle / curcuma / gingembre / paprika / coriandre
    if (str_contains($n,'cumin') || str_contains($n,'cannelle') || str_contains($n,'curcuma') || str_contains($n,'gingembre') || str_contains($n,'paprika') || str_contains($n,'coriandre') || str_contains($n,'harissa')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 11 L24.5 18 L31 18 L25.5 22.5 L27.5 30 L22 26 L16.5 30 L18.5 22.5 L13 18 L19.5 18 Z' fill='$f' opacity='.85'/>
    </svg>";

    // Avocat
    if (str_contains($n,'avocat')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 11 C17 11 13 17 13 23 C13 29 17 33 22 33 C27 33 31 29 31 23 C31 17 27 11 22 11Z' fill='$f' opacity='.75'/>
        <ellipse cx='22' cy='25' rx='5' ry='6' fill='$f' opacity='.9'/>
        <path d='M22 11 L22 14' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>
    </svg>";

    // Noix / amande / cacahuète / noisette
    if (str_contains($n,'noix') || str_contains($n,'amande') || str_contains($n,'cacahuète') || str_contains($n,'noisette')) return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>
        <circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
        <path d='M22 13 C17 13 13 18 14 24 C15 29 18 32 22 32 C26 32 29 29 30 24 C31 18 27 13 22 13Z' fill='$f' opacity='.8'/>
        <line x1='22' y1='13' x2='22' y2='32' stroke='$bg' stroke-width='.9' opacity='.35'/>
        <path d='M14 22 Q22 26 30 22' stroke='$bg' stroke-width='.9' fill='none' opacity='.35'/>
    </svg>";

    // ── Fallback : forme générique par type ───────────────────────
    $fallback = [
        'légume' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <path d='M22 31 C13 27 13 15 22 13 C31 15 31 27 22 31Z' fill='$f' opacity='.85'/>
            <path d='M22 13 L22 9 M19 15 L16 12 M25 15 L28 12' stroke='$f' stroke-width='1.3' stroke-linecap='round'/>",
        'fruit' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <circle cx='22' cy='24' r='8' fill='$f' opacity='.85'/>
            <path d='M22 16 C22 16 20 10 25 9' stroke='$f' stroke-width='1.5' stroke-linecap='round' fill='none'/>",
        'céréale' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <rect x='17' y='27' width='10' height='5' rx='2' fill='$f' opacity='.9'/>
            <line x1='22' y1='27' x2='22' y2='13' stroke='$f' stroke-width='1.5'/>
            <path d='M22 14 L19 18 M22 14 L25 18 M22 19 L19 23 M22 19 L25 23' stroke='$f' stroke-width='1.2' stroke-linecap='round'/>",
        'protéines animales' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <ellipse cx='22' cy='25' rx='7' ry='5' fill='$f' opacity='.85'/>
            <path d='M15 25 C13 21 14 17 17 16 C19 15 21 17 22 19 C23 17 25 15 27 16 C30 17 31 21 29 25' fill='$f' opacity='.6'/>",
        'légumineuse' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <ellipse cx='17' cy='24' rx='5' ry='6' fill='$f' opacity='.9'/>
            <ellipse cx='27' cy='24' rx='5' ry='6' fill='$f' opacity='.7'/>
            <path d='M17 18 C17 13 27 13 27 18' stroke='$f' stroke-width='1.3' fill='none' stroke-linecap='round'/>",
        'produit laitier' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <path d='M16 18 L16 29 Q16 31 18 31 L26 31 Q28 31 28 29 L28 18 Z' fill='$f' opacity='.85'/>
            <path d='M14 18 L30 18 L28 15 L16 15 Z' fill='$f' opacity='.55'/>",
        'huile' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <path d='M19 13 L19 17 L15 21 L15 29 Q15 31 18 31 L26 31 Q29 31 29 29 L29 21 L25 17 L25 13 Z' fill='$f' opacity='.75'/>
            <line x1='19' y1='13' x2='25' y2='13' stroke='$f' stroke-width='1.5' stroke-linecap='round'/>",
        'épice' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <path d='M22 11 L24.5 18 L31 18 L25.5 22.5 L27.5 30 L22 26 L16.5 30 L18.5 22.5 L13 18 L19.5 18 Z' fill='$f' opacity='.85'/>",
        'autre' => "<circle cx='22' cy='22' r='20' fill='$bg' stroke='$s' stroke-width='1.2'/>
            <circle cx='22' cy='22' r='8' fill='$f' opacity='.45'/>
            <circle cx='22' cy='22' r='4' fill='$f' opacity='.85'/>",
    ];
    $p = $fallback[$type] ?? $fallback['autre'];
    return "<svg viewBox='0 0 44 44' xmlns='http://www.w3.org/2000/svg' width='44' height='44'>$p</svg>";
}

// ── Barre CO₂ ─────────────────────────────────────────────────────
function co2Bar($co2) {
    $pct = min(100, ($co2 / 10) * 100);
    if ($pct < 25)  $color = '#639922';
    elseif ($pct < 55) $color = '#EF9F27';
    else $color = '#E24B4A';
    return ['pct' => $pct, 'color' => $color];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GaiaLumen • Aliments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Lato:wght@400;500;700&display=swap');
        :root { --vert:#1a372f; --sable:#f4ede4; --violet:#a78bfa; --bleu:#60a5fa; }
        * { font-family:'Lato',sans-serif; box-sizing:border-box; }
        .hf { font-family:'Cormorant Garamond',serif; }

        /* Curseur */
        #cur  { position:fixed;top:0;left:0;z-index:9999;pointer-events:none;width:14px;height:14px;border-radius:50%;background:var(--violet);box-shadow:0 0 12px var(--violet),0 0 24px var(--bleu);transform:translate(-50%,-50%);transition:width .2s,height .2s;mix-blend-mode:screen; }
        #cur.h{ width:28px;height:28px;background:var(--bleu); }
        #curt { position:fixed;top:0;left:0;z-index:9998;pointer-events:none;width:36px;height:36px;border-radius:50%;border:1.5px solid rgba(167,139,250,.4);transform:translate(-50%,-50%); }

        .navbar { background:linear-gradient(90deg,var(--vert) 0%,#11241f 100%); }

        .hero-bg {
            background-image:linear-gradient(rgba(26,55,47,.68),rgba(26,55,47,.68)),
                             url('assets/images/1000051721.jpg');
            background-size:cover; background-position:center; height:50vh;
        }

        /* Filtres */
        .fb { padding:6px 16px;border-radius:99px;border:1px solid #d1d5db;background:white;font-size:12px;color:#4b5563;cursor:pointer;transition:all .18s;font-family:'Lato',sans-serif; }
        .fb.on,.fb:hover { background:var(--vert);color:white;border-color:var(--vert); }

        /* Tableau */
        .thead { display:grid;grid-template-columns:52px 2.4fr 0.9fr 0.9fr 0.9fr 0.8fr 1.7fr 96px;gap:0;padding:11px 18px;background:var(--vert);color:white;font-size:10.5px;font-weight:600;letter-spacing:.07em;text-transform:uppercase; }
        .trow  { display:grid;grid-template-columns:52px 2.4fr 0.9fr 0.9fr 0.9fr 0.8fr 1.7fr 96px;align-items:center;gap:0;padding:13px 18px;border-bottom:1px solid #f0ece5;transition:background .13s; }
        .trow:hover { background:#fdf9f5; }
        .trow:last-child { border-bottom:none; }

        /* Badges */
        .tbadge { display:inline-block;font-size:10px;padding:2px 8px;border-radius:99px;font-weight:600;margin-top:3px; }
        .cbadge { font-size:10px;padding:1px 7px;border-radius:4px;background:#f3f4f6;color:#6b7280; }

        /* Barres */
        .cobar { height:6px;border-radius:3px;background:#e5e7eb;overflow:hidden;margin-top:4px; }
        .cofil { height:100%;border-radius:3px; }

        /* Boutons action */
        .bedit { display:inline-flex;align-items:center;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid #60a5fa;color:#185FA5;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bedit:hover { background:#E6F1FB; }
        .bdel  { display:inline-flex;align-items:center;padding:5px 11px;border-radius:8px;font-size:11px;border:1px solid #F7C1C1;color:#A32D2D;background:transparent;cursor:pointer;transition:all .14s;text-decoration:none; }
        .bdel:hover  { background:#FCEBEB; }

        /* Recherche */
        .si { padding:8px 14px 8px 36px;border-radius:99px;border:1px solid #e5e7eb;font-size:13px;width:220px;outline:none;font-family:'Lato',sans-serif; }
        .si:focus { border-color:var(--violet); }
        .sw { position:relative;display:inline-block; }
        .sw i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px; }

        /* Stat pills (hero) */
        .sp { background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:99px;padding:5px 14px;font-size:12px;color:white;display:inline-flex;align-items:center;gap:5px; }
        .sp b { font-weight:700; }

        /* Empty */
        .empty { padding:44px 20px;text-align:center;color:#9ca3af;font-size:14px; }

        /* Modal inputs */
        .mi { width:100%;padding:10px 16px;border-radius:14px;border:1px solid #e5e7eb;font-family:'Lato',sans-serif;font-size:13px;outline:none; }
        .mi:focus { border-color:var(--violet); }
    </style>
</head>
<body class="bg-[#f4ede4] overflow-x-hidden">
<div id="cur"></div><div id="curt"></div>

<!-- NAVBAR -->
<nav class="navbar text-white sticky top-0 z-50 shadow-xl">
    <div class="max-w-7xl mx-auto px-8 py-5 flex items-center justify-between">
        <a href="../../index.html" class="flex items-center gap-3">
            <svg width="36" height="36" viewBox="0 0 60 60" fill="none">
                <circle cx="30" cy="30" r="28" stroke="url(#gn)" stroke-width="1.5" opacity=".6"/>
                <defs><radialGradient id="gn" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="#3A86C4"/><stop offset="100%" stop-color="#5B3E96"/></radialGradient></defs>
                <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="#1F3D2B"/>
            </svg>
            <span class="hf text-4xl tracking-tighter">GaiaLumen</span>
        </a>
        <ul class="flex items-center gap-8 text-sm font-medium">
            <li><a href="../../index.html" class="hover:text-[#a78bfa] transition-colors">Accueil</a></li>
            <li><a href="alimentlist.php" class="text-[#a78bfa] font-semibold">Aliments</a></li>
            <li><a href="#" class="hover:text-[#a78bfa] transition-colors">Défis</a></li>
            <li><a href="#" class="hover:text-[#a78bfa] transition-colors">Planning</a></li>
        </ul>
        <button onclick="toggleTheme()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-3xl text-xs font-medium flex items-center gap-2">
            <span>🌙</span> Sombre
        </button>
    </div>
</nav>

<!-- HERO -->
<section class="hero-bg flex items-end">
    <div class="max-w-7xl mx-auto px-8 pb-10 w-full">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div class="text-white">
                <span class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-3xl text-xs mb-4">
                    <i class="fas fa-carrot text-[#60a5fa]"></i> BACK OFFICE · ALIMENTS
                </span>
                <h2 class="hf text-6xl leading-none mb-4">Gestion des Aliments</h2>
                <div class="flex gap-3 flex-wrap">
                    <?php
                    $nb  = count($aliments);
                    $nbt = count(array_unique(array_column($aliments,'type')));
                    ?>
                    <span class="sp"><b><?= $nb ?></b> aliments</span>
                    <span class="sp"><b><?= $nbt ?></b> types différents</span>
                </div>
            </div>
            <button onclick="openModal()"
                class="flex items-center gap-2 bg-white text-[#1a372f] px-7 py-3.5 rounded-2xl font-semibold text-sm hover:shadow-xl transition-all shrink-0">
                <i class="fas fa-plus"></i> Nouvel aliment
            </button>
        </div>
    </div>
</section>

<!-- CONTENU -->
<div class="max-w-7xl mx-auto px-8 py-8">

    <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-5 py-3.5 rounded-2xl mb-6 flex items-center gap-3 text-sm">
        <i class="fas fa-check-circle text-green-500"></i>
        <?php
            if ($success==='created') echo 'Aliment ajouté avec succès.';
            if ($success==='updated') echo 'Aliment modifié avec succès.';
            if ($success==='deleted') echo 'Aliment supprimé.';
        ?>
    </div>
    <?php endif; ?>

    <!-- FILTRES + RECHERCHE -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
        <div class="flex gap-2 flex-wrap" id="fbar">
            <button class="fb on" data-type="tous"               onclick="setFilter(this)">Tous</button>
            <button class="fb"    data-type="légume"             onclick="setFilter(this)">🌿 Légumes</button>
            <button class="fb"    data-type="fruit"              onclick="setFilter(this)">🍊 Fruits</button>
            <button class="fb"    data-type="protéines animales" onclick="setFilter(this)">🥩 Protéines</button>
            <button class="fb"    data-type="céréale"            onclick="setFilter(this)">🌾 Céréales</button>
            <button class="fb"    data-type="légumineuse"        onclick="setFilter(this)">🫘 Légumineuses</button>
            <button class="fb"    data-type="produit laitier"    onclick="setFilter(this)">🥛 Laitiers</button>
            <button class="fb"    data-type="épice"              onclick="setFilter(this)">🌶 Épices</button>
            <button class="fb"    data-type="huile"              onclick="setFilter(this)">🫙 Huiles</button>
        </div>
        <div class="sw">
            <i class="fas fa-search"></i>
            <input id="sq" type="text" class="si" placeholder="Rechercher…" oninput="applyFilters()">
        </div>
    </div>

    <!-- TABLEAU -->
    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
        <div class="thead">
            <div></div>
            <div>Aliment</div>
            <div style="text-align:center">Calories</div>
            <div style="text-align:center">Protéines</div>
            <div style="text-align:center">Glucides</div>
            <div style="text-align:center">Prix</div>
            <div>Impact CO₂</div>
            <div style="text-align:center">Actions</div>
        </div>

        <div id="tbody">
        <?php if (empty($aliments)): ?>
            <div class="empty"><i class="fas fa-seedling text-3xl text-gray-300 mb-3 block"></i>Aucun aliment. Créez-en un !</div>
        <?php else: ?>
            <?php foreach ($aliments as $a):
                $c   = typeConfig($a['type']);
                $svg = alimentSVG($a['nom'], $a['type'], $c);
                $co2 = co2Bar((float)$a['co2']);
            ?>
            <div class="trow" data-type="<?= htmlspecialchars($a['type']) ?>" data-nom="<?= strtolower(htmlspecialchars($a['nom'])) ?>">

                <!-- Icône SVG -->
                <div style="display:flex;align-items:center;justify-content:center;"><?= $svg ?></div>

                <!-- Nom + tags -->
                <div style="padding-left:12px;">
                    <p style="font-size:13.5px;font-weight:600;color:#1a372f;margin:0;"><?= htmlspecialchars($a['nom']) ?></p>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap;">
                        <span class="tbadge" style="background:<?= $c['tag_bg'] ?>;color:<?= $c['tag_color'] ?>;"><?= htmlspecialchars($a['type']) ?></span>
                        <span class="cbadge"><?= htmlspecialchars($a['categorie']) ?></span>
                        <?php if (!empty($a['origine'])): ?>
                        <span class="cbadge" style="background:#f0fdf4;color:#166534;">📍 <?= htmlspecialchars($a['origine']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['label_ecologique'])): ?>
                        <span class="cbadge" style="background:#EAF3DE;color:#3B6D11;">🌱 <?= htmlspecialchars($a['label_ecologique']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Calories -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:600;color:#1a372f;margin:0;"><?= number_format($a['calories'],1) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:0;">kcal/100g</p>
                </div>

                <!-- Protéines -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:600;color:#1a372f;margin:0;"><?= number_format($a['proteines'],1) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:0;">g/100g</p>
                </div>

                <!-- Glucides -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:600;color:#1a372f;margin:0;"><?= number_format($a['glucides'],1) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:0;">g/100g</p>
                </div>

                <!-- Prix -->
                <div style="text-align:center;">
                    <p style="font-size:14px;font-weight:600;color:#1a372f;margin:0;"><?= number_format($a['prix'],2) ?></p>
                    <p style="font-size:10px;color:#9ca3af;margin:0;">TND/kg</p>
                </div>

                <!-- Barre CO₂ -->
                <div style="padding:0 8px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px;">
                        <span style="font-size:11.5px;color:#6b7280;"><?= number_format($a['co2'],2) ?> kg</span>
                        <?php if ($a['co2'] < 1): ?>
                            <span style="font-size:9.5px;background:#EAF3DE;color:#3B6D11;padding:1px 6px;border-radius:99px;">faible</span>
                        <?php elseif ($a['co2'] < 5): ?>
                            <span style="font-size:9.5px;background:#FAEEDA;color:#854F0B;padding:1px 6px;border-radius:99px;">moyen</span>
                        <?php else: ?>
                            <span style="font-size:9.5px;background:#FCEBEB;color:#A32D2D;padding:1px 6px;border-radius:99px;">élevé</span>
                        <?php endif; ?>
                    </div>
                    <div class="cobar"><div class="cofil" style="width:<?= $co2['pct'] ?>%;background:<?= $co2['color'] ?>;"></div></div>
                </div>

                <!-- Actions -->
                <div style="display:flex;gap:5px;justify-content:center;">
                    <a href="updatealiment.php?id=<?= $a['id_aliment'] ?>" class="bedit" title="Modifier">
                        <i class="fas fa-pen" style="font-size:11px;"></i>
                    </a>
                    <a href="../../controller/alimentcontroller.php?action=delete&id=<?= $a['id_aliment'] ?>"
                       onclick="return confirm('Supprimer «<?= htmlspecialchars($a['nom']) ?>» ?')"
                       class="bdel" title="Supprimer">
                        <i class="fas fa-trash" style="font-size:11px;"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <div id="noResult" style="display:none;" class="empty">
                <i class="fas fa-filter text-3xl text-gray-300 mb-3 block"></i>Aucun résultat.
            </div>
        <?php endif; ?>
        </div>

        <!-- Pied -->
        <?php if (!empty($aliments)): ?>
        <div style="padding:10px 18px;background:#f9fafb;border-top:1px solid #f0ece5;display:flex;justify-content:space-between;">
            <span style="font-size:11.5px;color:#9ca3af;" id="rowCount"><?= count($aliments) ?> aliment(s)</span>
            <span style="font-size:11.5px;color:#9ca3af;">GaiaLumen © <?= date('Y') ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════ MODAL CRÉATION ══════════════════ -->
<div id="modal" class="hidden fixed inset-0 bg-black/70 items-center justify-center z-[100]" onclick="if(event.target===this)closeModal()">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-auto">
        <div class="p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="hf text-3xl text-[#1a372f]">Nouvel Aliment</h3>
                <button onclick="closeModal()" style="font-size:24px;color:#9ca3af;background:none;border:none;cursor:pointer;line-height:1;">&times;</button>
            </div>
            <form action="../../controller/alimentcontroller.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Nom de l'aliment *</label>
                        <input type="text" name="nom" required class="mi">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Type *</label>
                        <select name="type" required class="mi">
                            <option value="légume">Légume</option><option value="fruit">Fruit</option>
                            <option value="céréale">Céréale</option><option value="protéines animales">Protéines animales</option>
                            <option value="légumineuse">Légumineuse</option><option value="produit laitier">Produit laitier</option>
                            <option value="huile">Huile</option><option value="épice">Épice</option><option value="autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Catégorie *</label>
                        <select name="categorie" required class="mi">
                            <option value="frais">Frais</option><option value="sec">Sec</option>
                            <option value="transformé">Transformé</option><option value="ultra-transformé">Ultra-transformé</option>
                        </select>
                    </div>
                    <?php
                    $fields = [
                        ['calories','Calories (kcal/100g)','number','0.01',true],
                        ['proteines','Protéines (g/100g)','number','0.01',true],
                        ['glucides','Glucides (g/100g)','number','0.01',true],
                        ['lipides','Lipides (g/100g)','number','0.01',true],
                        ['fibres','Fibres (g/100g)','number','0.01',false],
                        ['sucre','Sucre (g/100g)','number','0.01',false],
                        ['sodium','Sodium (mg/100g)','number','0.01',false],
                        ['co2','CO₂ (kg CO₂eq/kg)','number','0.01',false],
                    ];
                    foreach($fields as [$name,$label,$type2,$step,$req]): ?>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]"><?= $label ?> <?= $req?'*':'' ?></label>
                        <input type="<?= $type2 ?>" step="<?= $step ?>" name="<?= $name ?>" value="0" <?= $req?'required':'' ?> class="mi">
                    </div>
                    <?php endforeach; ?>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Vitamines</label>
                        <input type="text" name="vitamines" placeholder="ex: A, B12, C" class="mi">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Label écologique</label>
                        <input type="text" name="label_ecologique" placeholder="bio, AOP…" class="mi">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Prix (TND/kg)</label>
                        <input type="number" step="0.01" name="prix" value="0" class="mi">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Origine</label>
                        <input type="text" name="origine" placeholder="Tunisie…" class="mi">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold mb-1.5 text-[#1a372f]">Allergènes</label>
                        <input type="text" name="allergenes" placeholder="gluten, lait…" class="mi">
                    </div>
                </div>
                <div class="flex gap-3 mt-7">
                    <button type="button" onclick="closeModal()" style="flex:1;padding:14px;border-radius:99px;border:1px solid #e5e7eb;font-weight:600;font-size:13px;cursor:pointer;background:white;">Annuler</button>
                    <button type="submit" style="flex:1;padding:14px;border-radius:99px;background:#1a372f;color:white;font-weight:600;font-size:13px;cursor:pointer;border:none;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Curseur
(function(){
    const c=document.getElementById('cur'),t=document.getElementById('curt');
    let mx=0,my=0,tx=0,ty=0;
    document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;c.style.left=mx+'px';c.style.top=my+'px';});
    (function l(){tx+=(mx-tx)*.12;ty+=(my-ty)*.12;t.style.left=tx+'px';t.style.top=ty+'px';requestAnimationFrame(l);})();
    document.querySelectorAll('a,button').forEach(el=>{
        el.addEventListener('mouseenter',()=>c.classList.add('h'));
        el.addEventListener('mouseleave',()=>c.classList.remove('h'));
    });
})();

// Modal
function openModal(){const m=document.getElementById('modal');m.classList.remove('hidden');m.classList.add('flex');}
function closeModal(){const m=document.getElementById('modal');m.classList.add('hidden');m.classList.remove('flex');}

// Filtres
function setFilter(btn){
    document.querySelectorAll('.fb').forEach(b=>b.classList.remove('on'));
    btn.classList.add('on');
    applyFilters();
}
function applyFilters(){
    const type  = document.querySelector('.fb.on')?.dataset.type || 'tous';
    const query = document.getElementById('sq').value.toLowerCase().trim();
    const rows  = document.querySelectorAll('.trow');
    let vis=0;
    rows.forEach(r=>{
        const mt = type==='tous'||r.dataset.type===type;
        const mq = !query||r.dataset.nom.includes(query);
        r.style.display = mt&&mq?'':'none';
        if(mt&&mq) vis++;
    });
    const nr=document.getElementById('noResult');
    if(nr) nr.style.display=vis===0?'block':'none';
    const rc=document.getElementById('rowCount');
    if(rc) rc.textContent=vis+' aliment(s)';
}

function toggleTheme(){}
</script>
</body>
</html>
