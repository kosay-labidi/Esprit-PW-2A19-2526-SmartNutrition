<?php
if (!class_exists('config')) {
    class config {
        private static $pdo = null;

        public static function getConnexion() {
            if (!isset(self::$pdo)) {
                $servername = "localhost";
                $username   = "root";
                $password   = "";
                $dbname     = "gaialumen";

                try {
                    self::$pdo = new PDO(
                        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
                        $username,
                        $password,
                        array(
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_AUTOCOMMIT => true
                        )
                    );
                } catch (Exception $e) {
                    die('Erreur de connexion : ' . $e->getMessage());
                }
            }
            return self::$pdo;
        }
    }
}
?>