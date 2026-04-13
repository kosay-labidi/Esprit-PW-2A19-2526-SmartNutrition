<?php
// VÉRIFICATION EN TOUT PREMIER - avant tout code
if (!class_exists('config', false)) {
    class config {
        private static $pdo = null;
        
        public static function getConnexion() {
            if (self::$pdo === null) {
                try {
                    $host = 'localhost';
                    $dbname = 'ds_gaialumen';
                    $username = 'root';
                    $password = '';
                    
                    self::$pdo = new PDO(
                        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                        $username,
                        $password,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]
                    );
                    
                } catch (PDOException $e) {
                    error_log("Erreur connexion DB: " . $e->getMessage());
                    die(json_encode([
                        'success' => false,
                        'message' => 'Erreur de connexion à la base de données'
                    ]));
                }
            }
            return self::$pdo;
        }
    }
}
?>