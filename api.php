<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Config/Csrf.php';

// La API comparte el mismo token CSRF del sistema web para proteger peticiones fetch().
Csrf::obtenerToken();

function responderErrorJson(string $mensaje, int $codigoEstado): void
{
    http_response_code($codigoEstado);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => false, 'message' => $mensaje]);
    exit;
}

$controlador = strtolower(trim((string) ($_GET['c'] ?? '')));
$metodoSolicitado = trim((string) ($_GET['m'] ?? ''));

// Alias para conservar compatibilidad con endpoints anteriores.
$aliasMetodos = [
    'list' => 'listar',
    'create' => 'crear',
    'update' => 'actualizar',
    'delete' => 'eliminar',
    'tecnicos' => 'listarTecnicos',
    'assign' => 'asignar',
    'updateStatus' => 'actualizarEstado',
    'closeTicket' => 'cerrarTicket',
    'listAdjuntos' => 'listarAdjuntos',
    'uploadAdjuntos' => 'subirAdjuntos',
    'listParticipantes' => 'listarParticipantes',
    'participantesCandidatos' => 'listarCandidatosParticipantes',
    'addParticipante' => 'agregarParticipante',
    'removeParticipante' => 'quitarParticipante',
    'read' => 'marcarLeida',
    'readAll' => 'marcarTodasLeidas',
    'ticketsCsv' => 'exportarTicketsCsv',
    'ticketsExcel' => 'exportarTicketsExcel',
    'ticketsPdf' => 'exportarTicketsPdf',
    'preview' => 'vistaPrevia',
    'index' => 'vistaPrevia',
];
$metodo = $aliasMetodos[$metodoSolicitado] ?? $metodoSolicitado;

// Este mapa define que controladores y metodos pueden exponerse publicamente por la API.
$mapaApi = [
    'categoria' => [
        'class' => 'CategoriaController',
        'methods' => ['listar', 'crear', 'actualizar', 'eliminar'],
    ],
    'prioridad' => [
        'class' => 'PrioridadController',
        'methods' => ['listar', 'crear', 'actualizar', 'eliminar'],
    ],
    'estado' => [
        'class' => 'EstadoTicketController',
        'methods' => ['listar', 'crear', 'actualizar', 'eliminar'],
    ],
    'rol' => [
        'class' => 'RolController',
        'methods' => ['listar', 'crear', 'actualizar', 'eliminar'],
    ],
    'usuario' => [
        'class' => 'UsuarioController',
        'methods' => ['listar', 'crear', 'actualizar', 'eliminar', 'listarTecnicos'],
    ],
    'ticket' => [
        'class' => 'TicketController',
        'methods' => [
            'listar',
            'crear',
            'asignar',
            'actualizarEstado',
            'cerrarTicket',
            'listarAdjuntos',
            'subirAdjuntos',
            'listarParticipantes',
            'listarCandidatosParticipantes',
            'agregarParticipante',
            'quitarParticipante',
        ],
    ],
    'comentario' => [
        'class' => 'ComentarioController',
        'methods' => ['listar', 'crear'],
    ],
    'notificacion' => [
        'class' => 'NotificacionController',
        'methods' => ['listar', 'marcarLeida', 'marcarTodasLeidas'],
    ],
    'reporte' => [
        'class' => 'ReporteController',
        'methods' => ['resumen', 'vistaPrevia', 'exportarTicketsCsv', 'exportarTicketsExcel', 'exportarTicketsPdf'],
    ],
];

if (!isset($mapaApi[$controlador])) {
    responderErrorJson('Controlador no encontrado', 404);
}

$clase = $mapaApi[$controlador]['class'];
$metodosPermitidos = $mapaApi[$controlador]['methods'];
$archivo = __DIR__ . '/Controllers/' . $clase . '.php';
if (!file_exists($archivo)) {
    responderErrorJson('Archivo de controlador no encontrado', 500);
}

require_once $archivo;

if (!class_exists($clase)) {
    responderErrorJson('Clase no encontrada', 500);
}

$instancia = new $clase();

// Aunque el metodo exista en la clase, solo se permite si tambien esta en la lista blanca del mapa.
if (!in_array($metodo, $metodosPermitidos, true) || !method_exists($instancia, $metodo)) {
    responderErrorJson('Metodo no encontrado', 404);
}

header('Content-Type: application/json; charset=utf-8');
$instancia->{$metodo}();
