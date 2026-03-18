<?php  
declare(strict_types=1);  
  
session_start();  
  
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/PerfilController.php';  
  
$route = isset($_GET['r']) ? trim((string) $_GET['r']) : '';  
$auth = new AuthController();
$perfil = new PerfilController();  
  
$isLogged = isset($_SESSION['user_id']);  
if ($route === 'logout') {  
    $auth->logout();  
}  
  
if (!$isLogged) {  
    if ($route === 'login') {  
        $auth->login();  
        exit;  
    }  
  
    $auth->showLogin();  
    exit;  
}  
$rolId = (int) ($_SESSION['rol_id'] ?? 0);  
$adminRoutes = ['categorias', 'prioridades', 'estados', 'roles', 'usuarios', 'show-register', 'register', 'reportes'];  
if (in_array($route, $adminRoutes, true) && $rolId !== 1) {  
    http_response_code(403);  
    echo 'Acceso denegado.';  
    exit;  
}  
if ($route === 'comentarios' && $rolId !== 1 && $rolId !== 2) {  
    http_response_code(403);  
    echo 'Acceso denegado.';  
    exit;  
}  
if ($route === 'home') {  
    $auth->home();  
    exit;  
}  
  
if ($route === '') {  
    $auth->home();  
    exit;  
}  
if ($route === 'tickets') {  
    require __DIR__ . '/Views/tickets/index.php';  
    exit;  
}  
  
if ($route === 'perfil') {
    $perfil->index();
    exit;
}

if ($route === 'perfil-update') {
    $perfil->update();
    exit;
}

if ($route === 'categorias') {  
    require __DIR__ . '/Views/categorias/index.php';  
    exit;  
}  
  
if ($route === 'prioridades') {  
    require __DIR__ . '/Views/prioridades/index.php';  
    exit;  
}  
if ($route === 'estados') {  
    require __DIR__ . '/Views/estados/index.php';  
    exit;  
}  
  
if ($route === 'roles') {  
    require __DIR__ . '/Views/roles/index.php';  
    exit;  
} 
  
if ($route === 'usuarios') {  
    require __DIR__ . '/Views/usuarios/index.php';  
    exit;  
}  
if ($route === 'comentarios') {  
    require __DIR__ . '/Views/comentarios/index.php';  
    exit;  
}  
if ($route === 'reportes') {  
    require __DIR__ . '/Views/reportes/index.php';  
    exit;  
}  
  
if ($route === 'show-register') {  
    $auth->showRegister();  
    exit;  
}  
  
if ($route === 'register') {  
    $auth->register();  
    exit;  
}  
  
http_response_code(404);  
echo 'Ruta no encontrada.'; 
