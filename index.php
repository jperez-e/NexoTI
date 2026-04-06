<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Config/Csrf.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/PerfilController.php';

// Se genera el token al entrar al sistema para que los formularios protegidos puedan reutilizarlo.
Csrf::obtenerToken();

$rutaSolicitada = trim((string) ($_GET['r'] ?? ''));
$aliasRutas = [
    'iniciarSesion' => 'login',
    'cerrarSesion' => 'logout',
    'inicio' => 'home',
    'registrar' => 'register',
    'mostrar-registro' => 'show-register',
];
$ruta = $aliasRutas[$rutaSolicitada] ?? $rutaSolicitada;

$autenticacion = new AuthController();
$perfil = new PerfilController();

// Estas rutas solo deben estar disponibles para el administrador.
$rutasAdmin = ['categorias', 'prioridades', 'estados', 'roles', 'usuarios', 'show-register', 'register'];
// Este mapa asocia cada ruta protegida con la vista que debe cargarse.
$rutasVistasProtegidas = [
    'tickets' => __DIR__ . '/Views/tickets/tickets.php',
    'perfil' => __DIR__ . '/Views/perfil/perfil.php',
    'categorias' => __DIR__ . '/Views/categorias/categorias.php',
    'prioridades' => __DIR__ . '/Views/prioridades/prioridades.php',
    'estados' => __DIR__ . '/Views/estados/estados.php',
    'roles' => __DIR__ . '/Views/roles/roles.php',
    'usuarios' => __DIR__ . '/Views/usuarios/usuarios.php',
    'reportes' => __DIR__ . '/Views/reportes/reportes.php',
];

if ($ruta === 'logout') {
    $autenticacion->cerrarSesion();
}

// Si no hay sesion iniciada, el usuario solo puede ver o procesar el login.
if (!isset($_SESSION['user_id'])) {
    if ($ruta === 'login') {
        $autenticacion->iniciarSesion();
        exit;
    }

    $autenticacion->mostrarLogin();
    exit;
}

$idRol = (int) ($_SESSION['rol_id'] ?? 0);
if (in_array($ruta, $rutasAdmin, true) && $idRol !== 1) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

if ($ruta === '' || $ruta === 'home') {
    $autenticacion->inicio();
    exit;
}

// Estas rutas ejecutan logica del controlador antes de decidir una vista final.
$rutasControlador = [
    'perfil' => [$perfil, 'mostrar'],
    'perfil-update' => [$perfil, 'actualizar'],
    'cambiar-clave' => [$perfil, 'mostrarCambiarClave'],
    'cambiar-clave-update' => [$perfil, 'actualizarClave'],
    'show-register' => [$autenticacion, 'mostrarRegistro'],
    'register' => [$autenticacion, 'registrar'],
];

if (isset($rutasControlador[$ruta])) {
    $rutasControlador[$ruta]();
    exit;
}

if (isset($rutasVistasProtegidas[$ruta])) {
    require $rutasVistasProtegidas[$ruta];
    exit;
}

http_response_code(404);
echo 'Ruta no encontrada.';
