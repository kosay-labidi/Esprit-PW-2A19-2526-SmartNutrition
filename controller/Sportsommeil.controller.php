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

    // ── Bibliothèque interne de repas ───────────────────────────
    // Clé = type_repas, valeur = tableau de repas avec calories et budget estimé
    private static array $BIBLIOTHEQUE = [
        'petit-dejeuner' => [
            ['nom' => 'Yaourt nature + fruits rouges',     'cal' => 180, 'bud' => 1.20],
            ['nom' => 'Oeufs brouillés + toast complet',  'cal' => 250, 'bud' => 1.80],
            ['nom' => 'Avoine au lait + banane',           'cal' => 290, 'bud' => 1.50],
            ['nom' => 'Smoothie épinards + pomme',         'cal' => 160, 'bud' => 1.60],
            ['nom' => 'Pain complet + beurre de cacahuète','cal' => 320, 'bud' => 1.40],
            ['nom' => 'Fromage blanc + miel + noix',       'cal' => 210, 'bud' => 1.70],
            ['nom' => 'Crêpes légères + confiture',        'cal' => 280, 'bud' => 1.30],
        ],
        'dejeuner' => [
            ['nom' => 'Poulet grillé + riz basmati + brocoli',  'cal' => 520, 'bud' => 4.50],
            ['nom' => 'Salade de thon + haricots verts',        'cal' => 380, 'bud' => 3.20],
            ['nom' => 'Pâtes bolognaise maison',                'cal' => 560, 'bud' => 3.80],
            ['nom' => 'Filet de poisson + légumes vapeur',      'cal' => 420, 'bud' => 5.00],
            ['nom' => 'Quiche légumes + salade verte',          'cal' => 450, 'bud' => 3.50],
            ['nom' => 'Couscous poulet + légumes',              'cal' => 580, 'bud' => 4.20],
            ['nom' => 'Bowl saumon + avocat + quinoa',          'cal' => 490, 'bud' => 5.50],
        ],
        'diner' => [
            ['nom' => 'Soupe de légumes + pain complet',        'cal' => 280, 'bud' => 2.00],
            ['nom' => 'Omelette aux légumes + salade',          'cal' => 320, 'bud' => 2.50],
            ['nom' => 'Gratin de courgettes léger',             'cal' => 350, 'bud' => 2.80],
            ['nom' => 'Salade composée protéinée',              'cal' => 300, 'bud' => 3.00],
            ['nom' => 'Risotto aux champignons',                'cal' => 410, 'bud' => 3.20],
            ['nom' => 'Wrap poulet + légumes rôtis',            'cal' => 370, 'bud' => 3.50],
            ['nom' => 'Dhal de lentilles + riz',               'cal' => 390, 'bud' => 2.20],
        ],
    ];

    // ══════════════════════════════════════════════════════════
    //  CRUD SPORTSOMMEIL
    // ══════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════
    //  GÉNÉRATION DU PLANNING COMPLET — appelée par le backend
    //  après confirmation admin dans showDemandeplanning.php
    // ══════════════════════════════════════════════════════════

    /**
     * Génère le planning COMPLET pour une demande :
     * - Supprime d'abord les anciennes lignes (régénération propre)
     * - Boucle sur N jours
     * - 5 lignes par jour : 3 repas (bibliothèque) + 1 sport + 1 sommeil
     *
     * @param int $idDemande ID de la DemandePlanning
     * @return array Les lignes générées pour affichage
     */
    public function genererPlanningComplet(int $idDemande): array {
        // 1. Récupérer la demande (calories, budget, duree, type_duree)
        $demandeCtrl = new DemandeplanningController();
        $demande     = $demandeCtrl->getDemandeById($idDemande);
        if (!$demande) {
            throw new RuntimeException("Demande #$idDemande introuvable.");
        }

        // 2. Récupérer SportSommeil lié
        $ss = $this->getSportSommeilByDemande($idDemande);
        if (!$ss) {
            throw new RuntimeException("SportSommeil non trouvé pour la demande #$idDemande. Complétez d'abord l'étape 2.");
        }

        // 3. Supprimer l'ancien planning (régénération propre)
        $this->deletePlanningByDemande($idDemande);

        // 4. Calculer le nombre de jours
        $nbJours = (int) $demande['duree'];
        if ($demande['type_duree'] === 'semaines') {
            $nbJours *= 7;
        }

        // 5. Boucle sur N jours
        $dt          = new DateTime();
        $toutesLignes = [];
        $indexJour   = 0; // Pour varier les repas de jour en jour

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

    /**
     * Construit 5 lignes pour un jour donné :
     * 3 repas depuis bibliothèque interne + 1 sport + 1 sommeil
     * Variation des repas selon $indexJour (rotation dans la bibliothèque)
     */
    private function construireLignesDuJour(array $ss, array $demande, string $date, int $indexJour): array {
        $lignes    = [];
        $idDemande = (int) $demande['id'];
        $calories  = (int) $demande['calories'];
        $budget    = (float) $demande['budget'];

        // ── 3 REPAS depuis bibliothèque interne ───────────────
        // Répartition calorique : 25% PdJ / 40% Déj / 35% Dîner
        $repasTypes = [
            ['type' => 'petit-dejeuner', 'ratio_cal' => 0.25, 'ratio_bud' => 0.20],
            ['type' => 'dejeuner',       'ratio_cal' => 0.40, 'ratio_bud' => 0.45],
            ['type' => 'diner',          'ratio_cal' => 0.35, 'ratio_bud' => 0.35],
        ];

        foreach ($repasTypes as $rt) {
            $type         = $rt['type'];
            $calCible     = (int)($calories * $rt['ratio_cal']);
            $budCible     = round($budget * $rt['ratio_bud'], 2);
            $repas        = $this->choisirRepas($type, $calCible, $indexJour);
            $nomRepas     = $repas['nom'];
            $calRepas     = $repas['cal'];
            $budRepas     = $repas['bud'];

            $desc = "{$nomRepas} | {$calRepas} kcal | {$budRepas} EUR";
            $lignes[] = new Planning(null, $idDemande, $date, 'repas', $desc);
        }

        // ── SPORT ─────────────────────────────────────────────
        $minParJour   = (int) round((int)$ss['duree_sport_hebdo'] / 7);
        $h            = intdiv($minParJour, 60);
        $m            = $minParJour % 60;
        $dureeLabel   = $h > 0 ? ($m > 0 ? "{$h}h{$m}min" : "{$h}h") : "{$minParJour}min";
        $activite     = htmlspecialchars($ss['activite_sportive'], ENT_QUOTES, 'UTF-8');
        $lignes[]     = new Planning(null, $idDemande, $date, 'sport', "{$activite} — {$dureeLabel}");

        // ── SOMMEIL ───────────────────────────────────────────
        $coucher      = substr($ss['heure_coucher'], 0, 5);
        $reveil       = substr($ss['heure_reveil'],  0, 5);
        // Calcul durée sommeil
        [$hC, $mC]    = array_map('intval', explode(':', $coucher));
        [$hR, $mR]    = array_map('intval', explode(':', $reveil));
        $minC         = $hC * 60 + $mC;
        $minR         = $hR * 60 + $mR;
        if ($minR <= $minC) $minR += 1440;
        $dureeSommeil = round(($minR - $minC) / 60, 1);
        $lignes[]     = new Planning(null, $idDemande, $date, 'sommeil', "{$coucher} - {$reveil} ({$dureeSommeil}h)");

        return $lignes; // 5 lignes : 3 repas + 1 sport + 1 sommeil
    }

    /**
     * Choisit un repas dans la bibliothèque interne.
     * Rotation selon $indexJour pour varier chaque jour.
     * Futur : remplacer par une requête SQL vers table repas du module ami.
     */
    private function choisirRepas(string $type, int $calCible, int $indexJour): array {
        $liste = self::$BIBLIOTHEQUE[$type] ?? [];
        if (empty($liste)) {
            return ['nom' => ucfirst($type) . ' à définir', 'cal' => $calCible, 'bud' => 0];
        }
        // Rotation simple : jour 0 → repas[0], jour 1 → repas[1], etc.
        $index = $indexJour % count($liste);
        return $liste[$index];
    }

    // ══════════════════════════════════════════════════════════
    //  LECTURE — JOINTURE (workshop)
    // ══════════════════════════════════════════════════════════

    /**
     * Jointure planning + demandeplanning + sportsommeil
     * Retourne toutes les lignes enrichies pour affichage tableau
     */
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
                INNER JOIN demandeplanning d  ON d.id          = p.id_demande
                LEFT  JOIN sportsommeil   ss ON ss.id_demande  = p.id_demande
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

    /**
     * Regroupe les lignes par date → tableau hebdomadaire
     * Structure : [ 'YYYY-MM-DD' => [ 'repas'=>[], 'sport'=>[], 'sommeil'=>[] ] ]
     */
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