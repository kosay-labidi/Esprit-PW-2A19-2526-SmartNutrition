<?php
/**
 * Controller : SportSommeilController
 * Génère un planning COMPLET (repas + sport + sommeil) sur N jours
 * via bibliothèque interne, prêt pour intégration module repas.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/SportSommeil.php';
require_once __DIR__ . '/../Model/Planning.php';
require_once __DIR__ . '/../controller/Demandeplanning.controller.php';

class SportSommeilController {

    private static array $BIBLIOTHEQUE = [
        'petit-dejeuner' => [
            ['nom' => 'Yaourt nature + fruits rouges',          'cal' => 180],
            ['nom' => 'Smoothie épinards + pomme',              'cal' => 250],
            ['nom' => 'Oeufs brouillés + toast complet',       'cal' => 350],
            ['nom' => 'Avoine + banane + miel',                 'cal' => 450],
            ['nom' => 'Pain complet + beurre de cacahuète',     'cal' => 520],
            ['nom' => 'Granola + lait entier + fruits secs',    'cal' => 620],
            ['nom' => 'Pancakes + sirop + oeufs brouillés',     'cal' => 750],
            ['nom' => 'Bagel + cream cheese + saumon fumé',     'cal' => 850],
            ['nom' => 'Oeufs bénédicte + pain brioché',        'cal' => 980],
            ['nom' => 'Porridge protéiné + noix + beurre',     'cal' => 1100],
        ],
        'dejeuner' => [
            ['nom' => 'Salade de thon + haricots verts',        'cal' => 380],
            ['nom' => 'Poulet grillé + riz basmati + brocoli',  'cal' => 520],
            ['nom' => 'Bowl saumon + quinoa + avocat',          'cal' => 650],
            ['nom' => 'Pâtes bolognaise maison',                'cal' => 780],
            ['nom' => 'Couscous poulet + légumes + pois chiches','cal' => 900],
            ['nom' => 'Burger maison + frites four + salade',   'cal' => 1050],
            ['nom' => 'Riz + poulet + légumes sautés + sauce',  'cal' => 1200],
            ['nom' => 'Pâtes + viande hachée + fromage gratiné','cal' => 1400],
        ],
        'diner' => [
            ['nom' => 'Soupe de légumes + pain complet',        'cal' => 280],
            ['nom' => 'Omelette aux légumes + salade verte',    'cal' => 380],
            ['nom' => 'Risotto aux champignons',                'cal' => 500],
            ['nom' => 'Wrap poulet + légumes rôtis',            'cal' => 630],
            ['nom' => 'Gratin dauphinois + steak haché',        'cal' => 820],
            ['nom' => 'Pizza maison + salade composée',         'cal' => 980],
            ['nom' => 'Lasagnes maison + pain à l\'ail',        'cal' => 1200],
        ],
    ];

    public function addSportSommeil(SportSommeil $ss): int {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                INSERT INTO sportsommeil
                    (id_demande, activite_sportive, duree_sport_hebdo,
                     heure_coucher, heure_reveil, qualite_sommeil)
                VALUES
                    (:id_demande, :activite_sportive, :duree_sport_hebdo,
                     :heure_coucher, :heure_reveil, :qualite_sommeil)
            ");
            $stmt->execute([
                ':id_demande'        => $ss->getIdDemande(),
                ':activite_sportive' => $ss->getActiviteSportive(),
                ':duree_sport_hebdo' => $ss->getDureSportHebdo(),
                ':heure_coucher'     => $ss->getHeureCoucher(),
                ':heure_reveil'      => $ss->getHeureReveil(),
                ':qualite_sommeil'   => $ss->getQualiteSommeil(),
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur addSportSommeil : " . $e->getMessage());
        }
    }

    public function getSportSommeilByDemande(int $idDemande): ?array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                SELECT * FROM sportsommeil
                WHERE id_demande = :id_demande
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([':id_demande' => $idDemande]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function listAllSportSommeil(): array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->query("SELECT * FROM sportsommeil ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function deleteSportSommeil(int $id): bool {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("DELETE FROM sportsommeil WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function genererPlanningComplet(int $idDemande): array {
        $demandeCtrl = new DemandeplanningController();
        $demande     = $demandeCtrl->getDemandeById($idDemande);
        if (!$demande) {
            throw new RuntimeException("Demande #$idDemande introuvable.");
        }

        $ss = $this->getSportSommeilByDemande($idDemande);
        if (!$ss) {
            throw new RuntimeException("SportSommeil non trouvé pour la demande #$idDemande. Complétez d'abord l'étape 2.");
        }

        $this->deletePlanningByDemande($idDemande);

        $nbJours = (int) $demande['duree'];
        if ($demande['type_duree'] === 'semaines') {
            $nbJours *= 7;
        }
        $dt           = new DateTime('today');
        $toutesLignes = [];
        $indexJour    = 0;

        for ($i = 0; $i < $nbJours; $i++) {
            $date   = $dt->format('Y-m-d');
            $lignes = $this->construireLignesDuJour($ss, $demande, $date, $indexJour);
            foreach ($lignes as $ligne) {
                $this->insertLignePlanning($ligne);
            }
            $toutesLignes = array_merge($toutesLignes, $lignes);
            $dt->modify('+1 day');
            $indexJour++;
        }

        return $toutesLignes;
    }

    private function construireLignesDuJour(array $ss, array $demande, string $date, int $indexJour): array {
        $lignes    = [];
        $idDemande = (int) $demande['id'];
        $calories  = (int) $demande['calories'];
        $budget    = (float) $demande['budget'];

        $repasTypes = [
            ['type' => 'petit-dejeuner', 'ratio_cal' => 0.25, 'ratio_bud' => 0.20],
            ['type' => 'dejeuner',       'ratio_cal' => 0.40, 'ratio_bud' => 0.45],
            ['type' => 'diner',          'ratio_cal' => 0.35, 'ratio_bud' => 0.35],
        ];

        foreach ($repasTypes as $rt) {
            $type     = $rt['type'];
            $calCible = (int)($calories * $rt['ratio_cal']);
            $budCible = round($budget * $rt['ratio_bud'], 2);
            $repas    = $this->choisirRepas($type, $calCible, $budCible, $indexJour);
            $desc     = "{$repas['nom']} | {$repas['cal']} kcal | {$budCible} EUR";
            $lignes[] = new Planning(null, $idDemande, $date, 'repas', $desc);
        }

        // SPORT
        $minParJour = (int) round((int)$ss['duree_sport_hebdo'] / 7);
        $h          = intdiv($minParJour, 60);
        $m          = $minParJour % 60;
        $dureeLabel = $h > 0 ? ($m > 0 ? "{$h}h{$m}min" : "{$h}h") : "{$minParJour}min";
        $activite   = htmlspecialchars($ss['activite_sportive'], ENT_QUOTES, 'UTF-8');
        $lignes[]   = new Planning(null, $idDemande, $date, 'sport', "{$activite} — {$dureeLabel}");

        // SOMMEIL
        $coucher      = substr($ss['heure_coucher'], 0, 5);
        $reveil       = substr($ss['heure_reveil'],  0, 5);
        [$hC, $mC]    = array_map('intval', explode(':', $coucher));
        [$hR, $mR]    = array_map('intval', explode(':', $reveil));
        $minC         = $hC * 60 + $mC;
        $minR         = $hR * 60 + $mR;
        if ($minR <= $minC) $minR += 1440;
        $dureeSommeil = round(($minR - $minC) / 60, 1);
        $lignes[]     = new Planning(null, $idDemande, $date, 'sommeil', "{$coucher} - {$reveil} ({$dureeSommeil}h)");

        return $lignes;
    }

    private function choisirRepas(string $type, int $calCible, float $budCible, int $indexJour): array {
        $liste = self::$BIBLIOTHEQUE[$type] ?? [];
        if (empty($liste)) {
            return ['nom' => ucfirst($type) . ' à définir', 'cal' => $calCible];
        }

        // Trier par proximité calorique
        usort($liste, function($a, $b) use ($calCible) {
            return abs($a['cal'] - $calCible) - abs($b['cal'] - $calCible);
        });

        // Rotation sur les 3 meilleurs
        $topN  = min(3, count($liste));
        $repas = $liste[$indexJour % $topN];

        return [
            'nom' => $repas['nom'],
            'cal' => $repas['cal'],
        ];
    }
    public function getPlanningCompletByDemande(int $idDemande): array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                SELECT
                    p.id            AS planning_id,
                    p.date,
                    p.type_activite,
                    p.description,
                    p.created_at,
                    d.id            AS demande_id,
                    d.calories      AS objectif_calories,
                    d.budget        AS objectif_budget,
                    d.type_budget,
                    d.duree,
                    d.type_duree,
                    ss.activite_sportive,
                    ss.duree_sport_hebdo,
                    ss.heure_coucher,
                    ss.heure_reveil,
                    ss.qualite_sommeil
                FROM planning p
                INNER JOIN demandeplanning d  ON d.id         = p.id_demande
                LEFT  JOIN sportsommeil   ss ON ss.id_demande = p.id_demande
                WHERE p.id_demande = :id_demande
                ORDER BY p.date ASC,
                         FIELD(p.type_activite, 'repas', 'sport', 'sommeil')
            ");
            $stmt->execute([':id_demande' => $idDemande]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getPlanningGroupeParDate(int $idDemande): array {
        $lignes  = $this->getPlanningCompletByDemande($idDemande);
        $groupes = [];
        foreach ($lignes as $l) {
            $date = $l['date'];
            if (!isset($groupes[$date])) {
                $groupes[$date] = ['repas' => [], 'sport' => [], 'sommeil' => []];
            }
            $groupes[$date][$l['type_activite']][] = $l['description'];
        }
        return $groupes;
    }

    public function listPlanningByDemande(int $idDemande): array {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                SELECT * FROM planning
                WHERE id_demande = :id_demande
                ORDER BY date ASC, type_activite ASC
            ");
            $stmt->execute([':id_demande' => $idDemande]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function insertLignePlanning(Planning $p): int {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("
                INSERT INTO planning (id_demande, date, type_activite, description)
                VALUES (:id_demande, :date, :type_activite, :description)
            ");
            $stmt->execute([
                ':id_demande'    => $p->getIdDemande(),
                ':date'          => $p->getDate(),
                ':type_activite' => $p->getTypeActivite(),
                ':description'   => $p->getDescription(),
            ]);
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException("Erreur insertLignePlanning : " . $e->getMessage());
        }
    }

    public function countPlanningByDemande(int $idDemande): int {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("SELECT COUNT(*) AS total FROM planning WHERE id_demande = :id_demande");
            $stmt->execute([':id_demande' => $idDemande]);
            $row  = $stmt->fetch();
            return (int) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function deletePlanningByDemande(int $idDemande): bool {
        try {
            $db   = config::getConnexion();
            $stmt = $db->prepare("DELETE FROM planning WHERE id_demande = :id_demande");
            return $stmt->execute([':id_demande' => $idDemande]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>