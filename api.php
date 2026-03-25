<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Config/Csrf.php';

Csrf::token();

function respondJsonError(string $message, int $statusCode): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => false, 'message' => $message]);
    exit;
}

$controller = strtolower(trim((string) ($_GET['c'] ?? '')));
$method = trim((string) ($_GET['m'] ?? ''));

$map = [
    'categoria' => [
        'class' => 'CategoriaController',
        'methods' => ['list', 'create', 'update', 'delete'],
    ],
    'prioridad' => [
        'class' => 'PrioridadController',
        'methods' => ['list', 'create', 'update', 'delete'],
    ],
    'estado' => [
        'class' => 'EstadoTicketController',
        'methods' => ['list', 'create', 'update', 'delete'],
    ],
    'rol' => [
        'class' => 'RolController',
        'methods' => ['list', 'create', 'update', 'delete'],
    ],
    'usuario' => [
        'class' => 'UsuarioController',
        'methods' => ['list', 'create', 'update', 'delete', 'tecnicos'],
    ],
    'ticket' => [
        'class' => 'TicketController',
        'methods' => ['list', 'create', 'assign', 'updateStatus', 'closeTicket'],
    ],
    'comentario' => [
        'class' => 'ComentarioController',
        'methods' => ['list', 'create'],
    ],
    'reporte' => [
        'class' => 'ReporteController',
        'methods' => ['index', 'ticketsCsv', 'ticketsPdf', 'resumen', 'preview'],
    ],
];

if (!isset($map[$controller])) {
    respondJsonError('Controlador no encontrado', 404);
}

$class = $map[$controller]['class'];
$allowedMethods = $map[$controller]['methods'];
$file = __DIR__ . '/Controllers/' . $class . '.php';
if (!file_exists($file)) {
    respondJsonError('Archivo de controlador no encontrado', 500);
}

require_once $file;

if (!class_exists($class)) {
    respondJsonError('Clase no encontrada', 500);
}

$instance = new $class();

if (!in_array($method, $allowedMethods, true) || !method_exists($instance, $method)) {
    respondJsonError('Metodo no encontrado', 404);
}

header('Content-Type: application/json; charset=utf-8');
$instance->{$method}();
