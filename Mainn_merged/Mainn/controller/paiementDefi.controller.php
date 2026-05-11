<?php
require_once(__DIR__ . '/../config.php');

class PaiementDefiController {
    private bool $schemaReady = false;
    private array $allowedMethods = ['card', 'apple_pay', 'google_pay', 'paypal', 'flouci', 'd17', 'simulation'];

    private function ensureSchema(): void {
        if ($this->schemaReady) return;
        $db = Config::getConnexion();
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS paiement_defi (
                    id INT NOT NULL AUTO_INCREMENT,
                    id_challenge INT NOT NULL,
                    id_participant INT DEFAULT NULL,
                    nom VARCHAR(150) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    montant DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    statut ENUM('en_attente','paye','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
                    methode VARCHAR(50) NOT NULL DEFAULT 'simulation',
                    reference_transaction VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    INDEX idx_paiement_challenge (id_challenge),
                    INDEX idx_paiement_participant (id_participant),
                    INDEX idx_paiement_email (email),
                    CONSTRAINT fk_paiement_defi_challenge
                        FOREIGN KEY (id_challenge) REFERENCES challenge(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_paiement_defi_participant
                        FOREIGN KEY (id_participant) REFERENCES participant(id)
                        ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log('Erreur ensureSchema paiement_defi: ' . $e->getMessage());
        }
        $this->schemaReady = true;
    }

    public function enregistrerPaiementSimule(int $idChallenge, int $idParticipant, string $nom, string $email, float $montant, string $methode = 'simulation'): array {
        $this->ensureSchema();
        $db = Config::getConnexion();
        $reference = 'SIM-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $methode = strtolower(trim($methode));
        if (!in_array($methode, $this->allowedMethods, true)) {
            $methode = 'simulation';
        }

        try {
            $q = $db->prepare("
                INSERT INTO paiement_defi
                    (id_challenge, id_participant, nom, email, montant, statut, methode, reference_transaction)
                VALUES
                    (:id_challenge, :id_participant, :nom, :email, :montant, 'paye', :methode, :reference)
            ");
            $q->execute([
                'id_challenge' => $idChallenge,
                'id_participant' => $idParticipant > 0 ? $idParticipant : null,
                'nom' => Config::sanitizeInput($nom),
                'email' => Config::sanitizeInput($email),
                'montant' => max(0, $montant),
                'methode' => Config::sanitizeInput($methode),
                'reference' => $reference,
            ]);

            return [
                'success' => true,
                'id' => (int)$db->lastInsertId(),
                'reference' => $reference,
                'statut' => 'paye',
                'methode' => $methode,
            ];
        } catch (Exception $e) {
            error_log('Erreur enregistrerPaiementSimule: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Paiement non enregistré.'];
        }
    }

    public function paiementValidePourInscription(int $idChallenge, string $email, string $reference, float $montant): ?array {
        $this->ensureSchema();
        $db = Config::getConnexion();
        $reference = trim($reference);
        $email = strtolower(trim($email));
        if ($idChallenge <= 0 || $email === '' || $reference === '') return null;

        try {
            $q = $db->prepare("
                SELECT *
                FROM paiement_defi
                WHERE id_challenge = :id_challenge
                  AND LOWER(email) = :email
                  AND reference_transaction = :reference
                  AND statut = 'paye'
                  AND montant = :montant
                  AND (id_participant IS NULL OR id_participant = 0)
                LIMIT 1
            ");
            $q->execute([
                'id_challenge' => $idChallenge,
                'email' => $email,
                'reference' => $reference,
                'montant' => max(0, $montant),
            ]);
            $row = $q->fetch();
            return $row ?: null;
        } catch (Exception $e) {
            error_log('Erreur paiementValidePourInscription: ' . $e->getMessage());
            return null;
        }
    }

    public function lierParticipant(string $reference, int $idParticipant): bool {
        $this->ensureSchema();
        if ($reference === '' || $idParticipant <= 0) return false;
        $db = Config::getConnexion();
        try {
            $q = $db->prepare("
                UPDATE paiement_defi
                SET id_participant = :id_participant
                WHERE reference_transaction = :reference
                  AND statut = 'paye'
                  AND (id_participant IS NULL OR id_participant = 0)
                LIMIT 1
            ");
            $q->execute([
                'id_participant' => $idParticipant,
                'reference' => trim($reference),
            ]);
            return $q->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Erreur lierParticipant paiement: ' . $e->getMessage());
            return false;
        }
    }

    public function methodeAutorisee(string $methode): bool {
        return in_array(strtolower(trim($methode)), $this->allowedMethods, true);
    }

    public function getStats(): array {
        $this->ensureSchema();
        $db = Config::getConnexion();
        try {
            $row = $db->query("
                SELECT
                    COUNT(*) AS total_paiements,
                    SUM(statut = 'paye') AS paiements_valides,
                    COALESCE(SUM(CASE WHEN statut = 'paye' THEN montant ELSE 0 END), 0) AS revenus
                FROM paiement_defi
            ")->fetch();
            return $row ?: ['total_paiements' => 0, 'paiements_valides' => 0, 'revenus' => 0];
        } catch (Exception $e) {
            error_log('Erreur getStats paiement_defi: ' . $e->getMessage());
            return ['total_paiements' => 0, 'paiements_valides' => 0, 'revenus' => 0];
        }
    }
}
?>
