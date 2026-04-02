<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Config/Csrf.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

class PerfilController extends BaseController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function mostrar(?string $mensaje = null, ?string $error = null): void
    {
        $this->requerirSesion();
        $usuario = $this->usuarios->obtenerPorId((int) $_SESSION['user_id']);
        if (!$usuario) {
            http_response_code(404);
            echo 'Usuario no encontrado.';
            return;
        }

        // Compatibilidad con la vista actual mientras se completa la estandarizacion.
        $message = $mensaje;
        require __DIR__ . '/../Views/perfil/perfil.php';
    }

    public function actualizar(): void
    {
        $this->requerirSesion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->mostrar();
            return;
        }

        if (!Csrf::esSolicitudValida()) {
            $this->mostrar(null, 'La sesion del formulario expiro. Intenta de nuevo.');
            return;
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $this->mostrar(null, 'Selecciona una imagen valida.');
            return;
        }

        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        // Se valida el tipo real del archivo para evitar que una extension falsa pase como imagen valida.
        $mime = mime_content_type($_FILES['foto']['tmp_name']) ?: '';
        if (!isset($permitidos[$mime])) {
            $this->mostrar(null, 'Solo se permiten imagenes JPG, PNG o WEBP.');
            return;
        }

        if ((int) $_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $this->mostrar(null, 'La imagen no debe superar 2 MB.');
            return;
        }

        $directorio = __DIR__ . '/../uploads/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $nombreArchivo = 'perfil_' . (int) $_SESSION['user_id'] . '_' . time() . '.' . $permitidos[$mime];
        $destino = $directorio . '/' . $nombreArchivo;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $this->mostrar(null, 'No se pudo guardar la imagen.');
            return;
        }

        $rutaRelativa = 'uploads/perfiles/' . $nombreArchivo;
        if (!$this->usuarios->actualizarFoto((int) $_SESSION['user_id'], $rutaRelativa)) {
            $this->mostrar(null, 'No se pudo actualizar la foto de perfil.');
            return;
        }

        $_SESSION['foto'] = $rutaRelativa;
        $this->mostrar('Foto de perfil actualizada.', null);
    }
}
