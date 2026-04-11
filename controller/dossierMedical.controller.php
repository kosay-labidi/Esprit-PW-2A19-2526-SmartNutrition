<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/DossierMedical.php';

class DossierMedicalController {

    public function list() {
        $sql = "SELECT * FROM dossier_medical ORDER BY date_creation DESC";
        $db = config::getConnexion();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id) {
        $sql = "SELECT * FROM dossier_medical WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(DossierMedical $d) {
        $sql = "INSERT INTO dossier_medical (id_utilisateur, groupe_sanguin, poids, taille, regime_special, notes_medecin, allergie, gravite_allergie, maladies, traitement, medecin, contact_en_cas_durgence) 
                VALUES (:id_user, :groupe, :poids, :taille, :regime, :notes, :allergie, :gravite, :maladies, :traitement, :medecin, :contact)";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_user' => $d->getIdUtilisateur() ?? 1,
            ':groupe' => $d->getGroupeSanguin(),
            ':poids' => $d->getPoids(),
            ':taille' => $d->getTaille(),
            ':regime' => $d->getRegimeSpecial(),
            ':notes' => $d->getNotesMedecin(),
            ':allergie' => $d->getAllergie(),
            ':gravite' => $d->getGraviteAllergie(),
            ':maladies' => $d->getMaladies(),
            ':traitement' => $d->getTraitement(),
            ':medecin' => $d->getMedecin(),
            ':contact' => $d->getContactUrgence()
        ]);
    }

    public function update(DossierMedical $d, $id) {
        $sql = "UPDATE dossier_medical SET groupe_sanguin=:groupe, poids=:poids, taille=:taille, regime_special=:regime, notes_medecin=:notes, allergie=:allergie, gravite_allergie=:gravite, maladies=:maladies, traitement=:traitement, medecin=:medecin, contact_en_cas_durgence=:contact WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':groupe' => $d->getGroupeSanguin(),
            ':poids' => $d->getPoids(),
            ':taille' => $d->getTaille(),
            ':regime' => $d->getRegimeSpecial(),
            ':notes' => $d->getNotesMedecin(),
            ':allergie' => $d->getAllergie(),
            ':gravite' => $d->getGraviteAllergie(),
            ':maladies' => $d->getMaladies(),
            ':traitement' => $d->getTraitement(),
            ':medecin' => $d->getMedecin(),
            ':contact' => $d->getContactUrgence()
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM dossier_medical WHERE id_dossier = :id";
        $db = config::getConnexion();
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
?>