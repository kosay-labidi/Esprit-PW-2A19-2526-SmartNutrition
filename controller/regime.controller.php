<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Regime.php';

class RegimeController {

    public function list() {
        $sql = "SELECT * FROM regimes ORDER BY nom_regime";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id) {
        $sql = "SELECT * FROM regimes WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(Regime $r) {
        $sql = "INSERT INTO regimes (nom_regime, slug, description, type_regime, niveau_difficulte, aliments_interdits, aliments_recommandes, apport_calorique_moyen) 
                VALUES (:nom, :slug, :desc, :type, :niveau, :interdits, :recommandes, :cal)";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nom' => $r->getNomRegime(),
            ':slug' => $r->getSlug(),
            ':desc' => $r->getDescription(),
            ':type' => $r->getTypeRegime(),
            ':niveau' => $r->getNiveauDifficulte(),
            ':interdits' => $r->getAlimentsInterdits(),
            ':recommandes' => $r->getAlimentsRecommandes(),
            ':cal' => $r->getApportCalorique()
        ]);
    }

    public function update(Regime $r, $id) {
        $sql = "UPDATE regimes SET nom_regime=:nom, slug=:slug, description=:desc, type_regime=:type, niveau_difficulte=:niveau, aliments_interdits=:interdits, aliments_recommandes=:recommandes, apport_calorique_moyen=:cal WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nom' => $r->getNomRegime(),
            ':slug' => $r->getSlug(),
            ':desc' => $r->getDescription(),
            ':type' => $r->getTypeRegime(),
            ':niveau' => $r->getNiveauDifficulte(),
            ':interdits' => $r->getAlimentsInterdits(),
            ':recommandes' => $r->getAlimentsRecommandes(),
            ':cal' => $r->getApportCalorique()
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM regimes WHERE id_regime = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
?>