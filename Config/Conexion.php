<?php
/*
Este archivo PHP define la clase Conexion, 
que se encarga de gestionar la conexión 
a la base de datos utilizando PDO.
La clase implementa el patrón singleton para asegurar que solo exista
una instancia de conexión a lo largo de la aplicación. 
Además, carga las variables de entorno desde un archivo .env 
para configurar los parámetros de conexión, como el host, puerto, 
nombre de la base de datos, usuario, contraseña y juego de caracteres. 
La clase proporciona un método estático obtener() 
para obtener la instancia de PDO configurada y lista para usar 
en las operaciones de la base de datos en toda la aplicación. 
*/

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

        $rutaEntorno = dirname(__DIR__) . '/.env';
        if (!is_file($rutaEntorno)) {
            throw new RuntimeException('No se encontro el archivo .env en la raiz del proyecto.');
        }

        // El proyecto carga .env manualmente para no depender de una libreria externa en el entorno academico.
        $lineas = file($rutaEntorno, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            throw new RuntimeException('No se pudo leer el archivo .env.');
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $posicionSeparador = strpos($linea, '=');
            if ($posicionSeparador === false) {
                continue;
            }

            $clave = trim(substr($linea, 0, $posicionSeparador));
            $valor = trim(substr($linea, $posicionSeparador + 1));
            $valor = trim($valor, "\"'");

            $_ENV[$clave] = $valor;
            $_SERVER[$clave] = $valor;
            putenv($clave . '=' . $valor);
        }

        self::$entornoCargado = true;
    }

    private static function entorno(string $clave, bool $permitirVacio = false): string
    {
        self::cargarArchivoEntorno();

        $valor = $_ENV[$clave] ?? $_SERVER[$clave] ?? getenv($clave);
        if ($valor === false || $valor === null || (!$permitirVacio && $valor === '')) {
            throw new RuntimeException('Falta la variable de entorno requerida: ' . $clave);
        }

        return (string) $valor;
    }

    public static function obtener(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $host = self::entorno('NEXOTI_DB_HOST');
        $puerto = self::entorno('NEXOTI_DB_PORT');
        $nombreBaseDatos = self::entorno('NEXOTI_DB_NAME');
        $usuario = self::entorno('NEXOTI_DB_USER');
        $contrasena = self::entorno('NEXOTI_DB_PASS', true);
        $juegoCaracteres = self::entorno('NEXOTI_DB_CHARSET');

        $dsn = "mysql:host={$host};port={$puerto};dbname={$nombreBaseDatos};charset={$juegoCaracteres}";

        self::$instancia = new PDO($dsn, $usuario, $contrasena, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instancia;
    }
}
