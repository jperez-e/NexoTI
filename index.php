<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Config/Csrf.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/PerfilController.php';

Csrf::token();

$route = trim((string) ($_GET['r'] ?? ''));
$auth = new AuthController();
$perfil = new PerfilController();

$adminRoutes = ['categorias', 'prioridades', 'estados', 'roles', 'usuarios', 'show-register', 'register', 'reportes'];
$protectedViewRoutes = [
    'tickets' => __DIR__ . '/Views/tickets/index.php',
    'categorias' => __DIR__ . '/Views/categorias/index.php',
    'prioridades' => __DIR__ . '/Views/prioridades/index.php',
    'estados' => __DIR__ . '/Views/estados/index.php',
    'roles' => __DIR__ . '/Views/roles/index.php',
    'usuarios' => __DIR__ . '/Views/usuarios/index.php',
    'reportes' => __DIR__ . '/Views/reportes/index.php',
];

if ($route === 'logout') {
    $auth->logout();
}

if (!isset($_SESSION['user_id'])) {
    if ($route === 'login') {
        $auth->login();
        exit;
    }

    $auth->showLogin();
    exit;
}

$rolId = (int) ($_SESSION['rol_id'] ?? 0);
if (in_array($route, $adminRoutes, true) && $rolId !== 1) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

if ($route === '' || $route === 'home') {
    $auth->home();
    exit;
}

$controllerRoutes = [
    'perfil' => [$perfil, 'index'],
    'perfil-update' => [$perfil, 'update'],
    'show-register' => [$auth, 'showRegister'],
    'register' => [$auth, 'register'],
];

if (isset($controllerRoutes[$route])) {
    $controllerRoutes[$route]();
    exit;
}

if (isset($protectedViewRoutes[$route])) {
    require $protectedViewRoutes[$route];
    exit;
}

http_response_code(404);
echo 'Ruta no encontrada.';
