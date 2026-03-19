<?php
declare(strict_types=1);

class Conexion
{
    private static ?PDO $instance = null;

    private static function env(string $key, string $default): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function get(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = self::env('NEXOTI_DB_HOST', '127.0.0.1');
        $port = self::env('NEXOTI_DB_PORT', '3306');
        $dbName = self::env('NEXOTI_DB_NAME', 'nexoti_db');
        $user = self::env('NEXOTI_DB_USER', 'root');
        $pass = self::env('NEXOTI_DB_PASS', '');
        $charset = self::env('NEXOTI_DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instance;
    }
}
