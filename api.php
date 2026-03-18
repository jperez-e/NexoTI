<?php
declare(strict_types=1);

session_start();

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
    echo json_encode(["status" => false, "message" => "Controlador no encontrado"]);
    exit;
}

$class = $map[$controller];
$file = __DIR__ . "/Controllers/" . $class . ".php";
if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "Archivo de controlador no encontrado"]);
    exit;
}

require_once $file;

if (!class_exists($class)) {
    http_response_code(500);
    echo json_encode(["status" => false, "message" => "Clase no encontrada"]);
    exit;
}

$instance = new $class();

if (!method_exists($instance, $method)) {
    http_response_code(404);
    echo json_encode(["status" => false, "message" => "Metodo no encontrado"]);
    exit;
}

header("Content-Type: application/json; charset=utf-8");
$instance->{$method}();
