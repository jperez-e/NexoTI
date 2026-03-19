<?php  
declare(strict_types=1);  
  
require_once __DIR__ . '/../Config/Csrf.php';  
require_once __DIR__ . '/../Models/UsuarioModel.php';  
require_once __DIR__ . '/../Models/TicketModel.php';  
  
class AuthController  
{  
    private UsuarioModel $usuarios;  
  
    public function __construct()  
    {  
        $this->usuarios = new UsuarioModel();  
    }  
  
    private function requireAdmin(): bool  
    {  
        return isset($_SESSION['rol_id']) && (int) $_SESSION['rol_id'] === 1;  
    }  
  
    public function showLogin(?string $error = null): void  
    {  
        $error = $error ?? null;  
        require __DIR__ . '/../Views/auth/login/login.php';  
    }  
  
    public function login(): void  
    {  
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  
            $this->showLogin();  
            return;  
        }  

        if (!Csrf::isValidRequest()) {
            $_SESSION['flash_error'] = 'La sesion del formulario expiro. Intenta de nuevo.';
            header('Location: index.php?r=login');
            exit;
        }
  
        $login = trim(strip_tags((string) ($_POST['login'] ?? '')));  
        $password = (string) ($_POST['password'] ?? '');  
  
        if ($login === '' or $password === '') {  
            $_SESSION['flash_error'] = 'Debes completar los campos.';  
            header('Location: index.php?r=login');  
            exit;  
        }  
  
        $user = $this->usuarios->findByLogin($login);  
        if (!$user or (int) $user['activo'] !== 1) {  
            $_SESSION['flash_error'] = 'Credenciales invalidas.';  
            header('Location: index.php?r=login');  
            exit;  
        }  
  
        if (!password_verify($password, $user['clave_hash'])) {  
            $_SESSION['flash_error'] = 'Credenciales invalidas.';  
            header('Location: index.php?r=login');  
            exit;  
        }  

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];  
        $_SESSION['nombre'] = $user['nombre'];  
        $_SESSION['rol_id'] = (int) $user['rol_id'];  
        $_SESSION['foto'] = $user['foto'] ?? null;  
  
        header('Location: index.php?r=home');  
        exit;  
    }  
 
    public function showRegister(?string $error = null): void  
    {  
        if (!$this->requireAdmin()) {  
            http_response_code(403);  
            echo 'Registro solo disponible para administradores.';  
            return;  
        }  
        require __DIR__ . '/../Views/auth/register/register.php';  
    }  
  
    public function register(): void  
    {  
        if (!$this->requireAdmin()) {  
            http_response_code(403);  
            echo 'Registro solo disponible para administradores.';  
            return;  
        }  
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  
            $this->showRegister();  
            return;  
        }  

        if (!Csrf::isValidRequest()) {
            $this->showRegister('La sesion del formulario expiro. Intenta de nuevo.');
            return;
        }
  
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));  
        $email = trim((string) ($_POST['email'] ?? ''));  
        $password = (string) ($_POST['password'] ?? '');  
  
        if ($nombre === '' or $email === '' or $password === '') {  
            $this->showRegister('Debes completar los campos.');  
            return;  
        }  
  
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {  
            $this->showRegister('Correo invalido.');  
            return;  
        }  
  
        $hash = password_hash($password, PASSWORD_BCRYPT);  
        $ok = $this->usuarios->insert($nombre, $email, $hash, 3, 1);  
        if (!$ok) {  
            $this->showRegister('No se pudo registrar el usuario.');  
            return;  
        }  
  
        header('Location: index.php?r=login');  
        exit;  
    }  
  
    public function logout(): void  
    {  
        session_unset();  
        session_destroy();  
        session_start();
        session_regenerate_id(true);
        header('Location: index.php?r=login');  
        exit;  
    }  
  
    public function home(): void  
    {  
        if (!isset($_SESSION['user_id'], $_SESSION['rol_id'])) {  
            header('Location: index.php?r=login');  
            exit;  
        }  
  
        $ticketModel = new TicketModel();  
        $dashboard = $ticketModel->getDashboardCounts(  
            (int) $_SESSION['rol_id'],  
            (int) $_SESSION['user_id']  
        );  
  
        require __DIR__ . '/../Views/home/home.php';  
    }  
} 
