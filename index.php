<?php
declare(strict_types=1);

$controllerName = isset($_GET["c"]) ? strtolower(trim((string) $_GET["c"])) : "ticket";
$methodName = isset($_GET["m"]) ? trim((string) $_GET["m"]) : "index";

$map = [
    "ticket" => "TicketController",
];

if (!isset($map[$controllerName])) {
    http_response_code(404);
    echo "Controlador no encontrado.";
    exit;
}

$controllerClass = $map[$controllerName];
$controllerFile = __DIR__ . "/Controllers/" . $controllerClass . ".php";

if (!file_exists($controllerFile)) {
    http_response_code(500);
    echo "Archivo de controlador no encontrado.";
    exit;
}

require_once $controllerFile;

$controller = new $controllerClass();

if (!method_exists($controller, $methodName)) {
    http_response_code(404);
    echo "Metodo no encontrado.";
    exit;
}

$controller->{$methodName}();
