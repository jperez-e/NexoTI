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
  
    private function requerirAdmin(): bool  
    {  
        // El registro abierto se cerro: solo el administrador puede crear nuevos usuarios desde la interfaz.
        return isset($_SESSION['rol_id']) && (int) $_SESSION['rol_id'] === 1;  
    }  
  
    public function mostrarLogin(?string $error = null): void  
    {  
        $error = $error ?? null;  
        require __DIR__ . '/../Views/auth/login/login.php';  
    }  
  
    public function iniciarSesion(): void  
    {  
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  
            $this->mostrarLogin();  
            return;  
        }  

        // Si el token falla, se redirige al login para regenerar el formulario y evitar un POST invalido.
        if (!Csrf::esSolicitudValida()) {
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
  
        $user = $this->usuarios->buscarPorLogin($login);  
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

        // Se regenera la sesion al iniciar para reducir riesgo de session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];  
        $_SESSION['nombre'] = $user['nombre'];  
        $_SESSION['rol_id'] = (int) $user['rol_id'];  
        $_SESSION['foto'] = $user['foto'] ?? null;  
  
        header('Location: index.php?r=home');  
        exit;  
    }  
 
    public function mostrarRegistro(?string $error = null): void  
    {  
        if (!$this->requerirAdmin()) {  
            http_response_code(403);  
            echo 'Registro solo disponible para administradores.';  
            return;  
        }  
        require __DIR__ . '/../Views/auth/register/register.php';  
    }  
  
    public function registrar(): void  
    {  
        if (!$this->requerirAdmin()) {  
            http_response_code(403);  
            echo 'Registro solo disponible para administradores.';  
            return;  
        }  
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  
            $this->mostrarRegistro();  
            return;  
        }  

        if (!Csrf::esSolicitudValida()) {
            $this->mostrarRegistro('La sesion del formulario expiro. Intenta de nuevo.');
            return;
        }
  
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));  
        $email = trim((string) ($_POST['email'] ?? ''));  
        $password = (string) ($_POST['password'] ?? '');  
  
        if ($nombre === '' or $email === '' or $password === '') {  
            $this->mostrarRegistro('Debes completar los campos.');  
            return;  
        }  
  
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {  
            $this->mostrarRegistro('Correo invalido.');  
            return;  
        }  
  
        $hash = password_hash($password, PASSWORD_BCRYPT);  
        // El rol 3 corresponde al usuario final, que es el rol base para nuevos registros creados por el admin.
        $ok = $this->usuarios->insertar($nombre, $email, $hash, 3, 1);  
        if (!$ok) {  
            $this->mostrarRegistro('No se pudo registrar el usuario.');  
            return;  
        }  
  
        header('Location: index.php?r=login');  
        exit;  
    }  
  
    public function cerrarSesion(): void  
    {  
        session_unset();  
        session_destroy();  
        session_start();
        session_regenerate_id(true);
        header('Location: index.php?r=login');  
        exit;  
    }  
  
    public function inicio(): void  
    {  
        if (!isset($_SESSION['user_id'], $_SESSION['rol_id'])) {  
            header('Location: index.php?r=login');  
            exit;  
        }  
  
        // El dashboard se calcula segun el rol para que cada usuario vea solo lo que le corresponde.
        $ticketModel = new TicketModel();  
        $dashboard = $ticketModel->obtenerConteosTablero(  
            (int) $_SESSION['rol_id'],  
            (int) $_SESSION['user_id']  
        );  
  
        require __DIR__ . '/../Views/home/home.php';  
    }  
} 
