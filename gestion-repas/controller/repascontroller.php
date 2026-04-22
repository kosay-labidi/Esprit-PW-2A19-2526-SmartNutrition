<?php
/* ============================================================
   controller/repascontroller.php — CONTRÔLEUR Repas (MVC)

   RÔLE : Reçoit les actions POST/GET venant des formulaires,
          valide les données, appelle le Model, redirige.

   ACTIONS GÉRÉES :
     POST action=create  → crée un repas + attache les aliments
     POST action=update  → modifie un repas + reattache aliments
     GET  action=delete  → supprime un repas (avec sa jointure)

   CE FICHIER NE CONTIENT PAS DE HTML.
   ============================================================ */

require_once __DIR__ . '/../model/repas_model.php';

$repasModel = new Repas();

/* ==========================================================
   TRAITEMENT POST : Create + Update
   ========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* --------------------------------------------------------
       CREATE — Ajouter un nouveau repas
       Formulaire envoyé depuis : view/FrontOffice/repaslist.php
       -------------------------------------------------------- */
    if ($action === 'create') {

        $errors = [];

        /* Validation : nom_repas obligatoire */
        $nom = trim($_POST['nom_repas'] ?? '');
        if (strlen($nom) < 2) {
            $errors[] = 'Le nom du repas est obligatoire (minimum 2 caractères).';
        }

        /* Validation : date_repas obligatoire */
        $date = trim($_POST['date_repas'] ?? '');
        if (empty($date)) {
            $errors[] = 'La date du repas est obligatoire.';
        }

        /* Validation : au moins un aliment sélectionné */
        $aliments = $_POST['aliments'] ?? [];
        if (empty($aliments)) {
            $errors[] = 'Veuillez sélectionner au moins un aliment.';
        }

        /* Vérification doublon : même nom + même date + même user */
        $idUser = 1; /* Remplacer par session utilisateur quand auth disponible */
        if (empty($errors) && $repasModel->existsByNomDate($nom, $date, $idUser)) {
            $errors[] = 'Un repas avec ce nom existe déjà à cette date.';
        }

        if (!empty($errors)) {
            $msg = urlencode(implode(' | ', $errors));
            header("Location: ../view/FrontOffice/fo_repaslist.php?error=" . $msg);
            exit;
        }

        /* 1. Créer le repas (entête) dans la table repas */
        $idRepas = $repasModel->create([
            'nom_repas'      => $nom,
            'date_repas'     => $date,
            'id_utilisateur' => $idUser,
        ]);

        /* 2. Attacher les aliments via la table de jointure repas_aliments */
        $quantites = $_POST['quantites'] ?? [];
        $repasModel->attachAliments($idRepas, $aliments, $quantites);

        header("Location: ../view/FrontOffice/fo_repaslist.php?success=created");
        exit;
    }

    /* --------------------------------------------------------
       UPDATE — Modifier un repas existant
       Formulaire envoyé depuis : view/FrontOffice/updaterepas.php
       -------------------------------------------------------- */
    if ($action === 'update') {

        $id   = (int) ($_POST['id_repas'] ?? 0);
        $nom  = trim($_POST['nom_repas']  ?? '');
        $date = trim($_POST['date_repas'] ?? '');

        if ($id <= 0 || strlen($nom) < 2 || empty($date)) {
            header("Location: ../view/FrontOffice/fo_updaterepas.php?id=$id&error=validation");
            exit;
        }

        /* 1. Mettre à jour l'entête du repas */
        $repasModel->update($id, ['nom_repas' => $nom, 'date_repas' => $date]);

        /* 2. Réattacher les aliments (detach ancien + attach nouveau) */
        $aliments  = $_POST['aliments']  ?? [];
        $quantites = $_POST['quantites'] ?? [];
        $repasModel->attachAliments($id, $aliments, $quantites);

        header("Location: ../view/FrontOffice/fo_repaslist.php?success=updated");
        exit;
    }
}

/* ==========================================================
   TRAITEMENT GET : Delete
   Lien depuis : view/FrontOffice/repaslist.php?action=delete&id=X
   ========================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        /* La suppression cascade supprime aussi repas_aliments (FK) */
        $repasModel->delete($id);
    }
    header("Location: ../view/FrontOffice/fo_repaslist.php?success=deleted");
    exit;
}
?>
