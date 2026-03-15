<?php
declare(strict_types=1);

// Test controller router (temporal). Remove when no longer needed.
session_start();

require_once __DIR__ . "/Config/Conexion.php";

function fetchIdBy(string $table, string $column, string $value): ?int
{
    $db = Conexion::get();
    $sql = "SELECT id FROM {$table} WHERE {$column} = :value LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([":value" => $value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row["id"] : null;
}

function fetchFirstId(string $table): ?int
{
    $db = Conexion::get();
    $sql = "SELECT id FROM {$table} ORDER BY id ASC LIMIT 1";
    $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row["id"] : null;
}

function jsonError(string $message, array $context = []): void
{
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(400);
    echo json_encode(["status" => false, "message" => $message, "context" => $context], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fake session for testing protected endpoints (use real admin if exists).
$adminId = fetchIdBy("usuarios", "email", "admin@nexoti.com") ?? 1;
$_SESSION["user_id"] = $adminId;
$_SESSION["rol_id"] = 1;
$_SESSION["nombre"] = "Admin Test";

$controller = strtolower(trim((string) ($_GET["c"] ?? "")));
$method = trim((string) ($_GET["m"] ?? ""));

$map = [
    "categoria" => "CategoriaController",
    "prioridad" => "PrioridadController",
    "estado" => "EstadoTicketController",
    "rol" => "RolController",
    "usuario" => "UsuarioController",
    "ticket" => "TicketController",
    "comentario" => "ComentarioController",
];

if (!isset($map[$controller])) {
    http_response_code(404);
    echo "Controlador no encontrado.";
    exit;
}

$class = $map[$controller];
$file = __DIR__ . "/Controllers/" . $class . ".php";
if (!file_exists($file)) {
    http_response_code(500);
    echo "Archivo de controlador no encontrado.";
    exit;
}

require_once $file;

if (!class_exists($class)) {
    http_response_code(500);
    echo "Clase no encontrada.";
    exit;
}

$instance = new $class();

if (!method_exists($instance, $method)) {
    http_response_code(404);
    echo "Metodo no encontrado.";
    exit;
}

// Example POST payloads (use ?m=list for GET or send POST for create).
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST)) {
    // Use sample defaults if no POST data is provided.
    switch ($controller) {
        case "categoria":
            $_POST = ["nombre" => "Soporte", "descripcion" => "Solicitudes generales"];
            break;
        case "prioridad":
            $_POST = ["nombre" => "Media", "nivel" => "2"];
            break;
        case "estado":
            $_POST = ["nombre" => "En Proceso"];
            break;
        case "rol":
            $_POST = ["nombre" => "Supervisor"];
            break;
        case "usuario":
            $suffix = date("YmdHis");
            $_POST = [
                "nombre" => "Usuario Prueba",
                "email" => "usuario.prueba{$suffix}@nexoti.com",
                "password" => "Usuario123*",
                "rol_id" => "3"
            ];
            break;
        case "ticket":
            $usuarioId = fetchIdBy("usuarios", "email", "usuario@nexoti.com")
                ?? fetchIdBy("usuarios", "email", "admin@nexoti.com")
                ?? fetchFirstId("usuarios");
            $categoriaId = fetchIdBy("categorias", "nombre", "Soporte") ?? fetchFirstId("categorias");
            $prioridadId = fetchIdBy("prioridades", "nombre", "Media") ?? fetchFirstId("prioridades");
            $estadoId = fetchIdBy("estados_ticket", "nombre", "En Proceso") ?? fetchFirstId("estados_ticket");

            if (!$usuarioId || !$categoriaId || !$prioridadId || !$estadoId) {
                jsonError("Faltan datos base para crear ticket.", [
                    "usuario_id" => $usuarioId,
                    "categoria_id" => $categoriaId,
                    "prioridad_id" => $prioridadId,
                    "estado_id" => $estadoId
                ]);
            }
            $_POST = [
                "codigo" => "TCK-" . date("YmdHis"),
                "titulo" => "Impresora sin conexion en Oficina 2",
                "descripcion" => "La impresora HP LaserJet de la Oficina 2 no imprime desde las 9:30 AM. Se reinicio el equipo y el router, pero sigue sin responder.",
                "usuario_id" => (string) $usuarioId,
                "categoria_id" => (string) $categoriaId,
                "prioridad_id" => (string) $prioridadId,
                "estado_id" => (string) $estadoId
            ];
            break;
        case "comentario":
            $ticketId = fetchFirstId("tickets");
            $usuarioId = fetchIdBy("usuarios", "email", "usuario@nexoti.com")
                ?? fetchIdBy("usuarios", "email", "admin@nexoti.com")
                ?? fetchFirstId("usuarios");
            if (!$ticketId || !$usuarioId) {
                jsonError("Faltan datos base para crear comentario.", [
                    "ticket_id" => $ticketId,
                    "usuario_id" => $usuarioId
                ]);
            }
            $_POST = [
                "ticket_id" => (string) $ticketId,
                "usuario_id" => (string) $usuarioId,
                "comentario" => "Comentario de prueba"
            ];
            break;
    }
}

header("Content-Type: application/json; charset=utf-8");
$instance->{$method}();
