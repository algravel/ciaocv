<?php
/**
 * Connexion PDO MySQL pour le module gestion.
 * Utilise MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB depuis .env
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['MYSQL_HOST'] ?? 'localhost';
            $user = $_ENV['MYSQL_USER'] ?? '';
            $pass = $_ENV['MYSQL_PASS'] ?? '';
            $db   = $_ENV['MYSQL_DB'] ?? '';
            if ($user === '' || $db === '') {
                throw new RuntimeException('Configuration MySQL manquante dans .env (MYSQL_USER, MYSQL_DB)');
            }
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}
