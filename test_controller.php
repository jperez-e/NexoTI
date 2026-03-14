<?php
declare(strict_types=1);

// Test controller router (temporal). Remove when no longer needed.
session_start();

// Fake session for testing protected endpoints.
$_SESSION["user_id"] = 1;
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
            $_POST = ["nombre" => "Maria Gomez", "email" => "maria.gomez@nexoti.local", "password" => "123456", "rol_id" => "3"];
            break;
        case "ticket":
            $_POST = [
                "codigo" => "TCK-" . date("YmdHis"),
                "titulo" => "Prueba desde test_controller",
                "descripcion" => "Ticket generado desde el router de pruebas.",
                "usuario_id" => "1",
                "categoria_id" => "1",
                "prioridad_id" => "1",
                "estado_id" => "1"
            ];
            break;
        case "comentario":
            $_POST = ["ticket_id" => "1", "usuario_id" => "1", "comentario" => "Comentario de prueba"];
            break;
    }
}

header("Content-Type: application/json; charset=utf-8");
$instance->{$method}();
