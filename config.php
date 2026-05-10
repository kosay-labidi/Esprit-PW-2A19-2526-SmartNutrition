<?php
// --- Charger le fichier .env ---
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key) && !getenv($key)) {
                putenv("$key=$value");
                $_SERVER[$key] = $value;
                $_ENV[$key] = $value;
            }
        }
    }
}

// --- Configuration BDD ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'dsgaialumen');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Configuration GROQ ---
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: ($_SERVER['GROQ_API_KEY'] ?? ''));
define('GROQ_MODEL', getenv('GROQ_MODEL') ?: ($_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile'));
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

class Config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
                    'mysql:host=localhost;dbname=dsgaialumen',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]
                );
            } catch (Exception $e) {
                die('Erreur: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    // --- Sécurité ---
    public static function generateCSRFToken()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function sanitizeInput($data)
    {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}
