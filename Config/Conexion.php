<?php
declare(strict_types=1);

class Conexion
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = "127.0.0.1";
        $port = "3306";
        $dbName = "nexoti_db";
        $user = "root";
        $pass = "";
        $charset = "utf8mb4";

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instance;
    }
}
