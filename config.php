<?php
// VÉRIFICATION EN TOUT PREMIER - avant tout code
if (!class_exists('config', false)) {
    class config {
        private static $pdo = null;
        
        public static function getConnexion() {
            if (self::$pdo === null) {
                try {
                    $host = 'localhost';
                    $dbname = 'dsgaialumen';
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

// ── Groq AI ────────────────────────────────────
if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', 'CLE API');
    define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
    define('GROQ_MODEL',   'llama-3.3-70b-versatile');
}
?>