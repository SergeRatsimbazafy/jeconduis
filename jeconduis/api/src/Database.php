<?php
/**
 * api/src/Database.php
 * Connexion PDO singleton — une seule connexion par requête PHP.
 */

class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host   = $_ENV['DB_HOST']     ?? 'localhost';
        $dbname = $_ENV['DB_NAME']     ?? 'jeconduis';
        $user   = $_ENV['DB_USER']     ?? 'root';
        $pass   = $_ENV['DB_PASSWORD'] ?? '';
        $port   = $_ENV['DB_PORT']     ?? '3306';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // On log l'erreur mais on ne bloque pas l'utilisateur
            error_log('[JeConduis DB] Connexion échouée : ' . $e->getMessage());
            throw $e;
        }

        return self::$instance;
    }
}
