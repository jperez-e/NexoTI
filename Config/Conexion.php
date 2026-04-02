<?php
declare(strict_types=1);

class Conexion
{
    private static ?PDO $instancia = null;
    private static bool $entornoCargado = false;

    private static function cargarArchivoEntorno(): void
    {
        if (self::$entornoCargado) {
            return;
        }

        $envPath = dirname(__DIR__) . '/.env';
        if (!is_file($envPath)) {
            throw new RuntimeException('No se encontro el archivo .env en la raiz del proyecto.');
        }

        // El proyecto carga .env manualmente para no depender de una libreria externa en el entorno academico.
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException('No se pudo leer el archivo .env.');
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separatorPosition = strpos($line, '=');
            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPosition));
            $value = trim(substr($line, $separatorPosition + 1));
            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }

        self::$entornoCargado = true;
    }

    private static function entorno(string $key, bool $allowEmpty = false): string
    {
        self::cargarArchivoEntorno();

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || (!$allowEmpty && $value === '')) {
            throw new RuntimeException('Falta la variable de entorno requerida: ' . $key);
        }

        return (string) $value;
    }

    public static function obtener(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $host = self::entorno('NEXOTI_DB_HOST');
        $port = self::entorno('NEXOTI_DB_PORT');
        $dbName = self::entorno('NEXOTI_DB_NAME');
        $user = self::entorno('NEXOTI_DB_USER');
        $pass = self::entorno('NEXOTI_DB_PASS', true);
        $charset = self::entorno('NEXOTI_DB_CHARSET');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        self::$instancia = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instancia;
    }
}
