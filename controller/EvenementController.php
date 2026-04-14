<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Evenement.php';  // Changé include → require_once

class EvenementController {
    public function listEvenements() {
        $sql = "SELECT * FROM evenement";
        $db = config::getConnexion();
        return $db->query($sql);
    }

    public function deleteEvenement($id) {
        $sql = "DELETE FROM evenement WHERE id_event = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    public function addEvenement(Evenement $evenement) {
        $sql = "INSERT INTO evenement (titre, description, date, heure, type) 
                VALUES (:titre, :description, :date, :heure, :type)";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'titre' => $evenement->getTitre(),
            'description' => $evenement->getDescription(),
            'date' => $evenement->getDate()->format('Y-m-d'),
            'heure' => $evenement->getHeure(),
            'type' => $evenement->getType()
        ]);
    }

    public function updateEvenement(Evenement $evenement, $id) {
        $sql = "UPDATE evenement SET titre=:titre, description=:description, 
                date=:date, heure=:heure, type=:type WHERE id_event=:id";
        $db = config::getConnexion();
        $query = $db->prepare($sql);
        $query->execute([
            'id' => $id,
            'titre' => $evenement->getTitre(),
            'description' => $evenement->getDescription(),
            'date' => $evenement->getDate()->format('Y-m-d'),
            'heure' => $evenement->getHeure(),
            'type' => $evenement->getType()
        ]);
    }

    public function showEvenement($id) {
        $sql = "SELECT * FROM evenement WHERE id_event = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }
}
?>