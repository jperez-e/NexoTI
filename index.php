<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . "/Controllers/AuthController.php";

$route = isset($_GET["r"]) ? trim((string) $_GET["r"]) : "";
$auth = new AuthController();

$isLogged = isset($_SESSION["user_id"]);

if ($route === "logout") {
    $auth->logout();
}

if (!$isLogged) {
    if ($route === "login") {
        $auth->login();
        exit;
    }

    $auth->showLogin();
    exit;
}

if ($route === "home" || $route === "") {
    $auth->home();
    exit;
}

if ($route === "show-register" || $route === "register") {
    $auth->{$route === "register" ? "register" : "showRegister"}();
    exit;
}

http_response_code(404);
echo "Ruta no encontrada.";
