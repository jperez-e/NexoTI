<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Config/Csrf.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/PerfilController.php';

// Se genera el token al entrar al sistema para que los formularios protegidos puedan reutilizarlo.
Csrf::token();

$route = trim((string) ($_GET['r'] ?? ''));
$auth = new AuthController();
$perfil = new PerfilController();

// Estas rutas solo deben estar disponibles para el administrador.
$adminRoutes = ['categorias', 'prioridades', 'estados', 'roles', 'usuarios', 'show-register', 'register', 'reportes'];
// Este mapa asocia cada ruta protegida con la vista que debe cargarse.
$protectedViewRoutes = [
    'tickets' => __DIR__ . '/Views/tickets/tickets.php',
    'perfil' => __DIR__ . '/Views/perfil/perfil.php',
    'categorias' => __DIR__ . '/Views/categorias/categorias.php',
    'prioridades' => __DIR__ . '/Views/prioridades/prioridades.php',
    'estados' => __DIR__ . '/Views/estados/estados.php',
    'roles' => __DIR__ . '/Views/roles/roles.php',
    'usuarios' => __DIR__ . '/Views/usuarios/usuarios.php',
    'reportes' => __DIR__ . '/Views/reportes/reportes.php',
];

if ($route === 'logout') {
    $auth->logout();
}

// Si no hay sesion iniciada, el usuario solo puede ver o procesar el login.
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

// Estas rutas ejecutan logica del controlador antes de decidir una vista final.
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
