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

        $mensaje = null;
        $error = null;
        $usuarioId = (int) $_SESSION['user_id'];

        $passwordActual = trim((string) ($_POST['password_actual'] ?? ''));
        $passwordNueva = trim((string) ($_POST['password_nueva'] ?? ''));
        $passwordConfirmacion = trim((string) ($_POST['password_confirmacion'] ?? ''));
        $solicitaCambioClave = ($passwordActual !== '' || $passwordNueva !== '' || $passwordConfirmacion !== '');
        $solicitaCambioFoto = isset($_FILES['foto']) && (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if (!$solicitaCambioClave && !$solicitaCambioFoto) {
            $this->mostrar(null, 'No se detectaron cambios para guardar.');
            return;
        }

        if ($solicitaCambioClave) {
            $resultadoClave = $this->actualizarClavePerfil($usuarioId, $passwordActual, $passwordNueva, $passwordConfirmacion);
            if ($resultadoClave['error'] !== null) {
                $error = $resultadoClave['error'];
            } elseif ($resultadoClave['mensaje'] !== null) {
                $mensaje = $resultadoClave['mensaje'];
            }
        }

        if ($solicitaCambioFoto) {
            $resultadoFoto = $this->actualizarFotoPerfil($usuarioId);
            if ($resultadoFoto['error'] !== null) {
                $error = $resultadoFoto['error'];
            } elseif ($resultadoFoto['mensaje'] !== null) {
                $mensaje = $mensaje !== null ? $mensaje . ' ' . $resultadoFoto['mensaje'] : $resultadoFoto['mensaje'];
            }
        }

        $this->mostrar($mensaje, $error);
    }

    private function actualizarClavePerfil(int $usuarioId, string $passwordActual, string $passwordNueva, string $passwordConfirmacion): array
    {
        if ($passwordActual === '' || $passwordNueva === '' || $passwordConfirmacion === '') {
            return ['mensaje' => null, 'error' => 'Completa los tres campos de contraseña para actualizarla.'];
        }

        if ($passwordNueva !== $passwordConfirmacion) {
            return ['mensaje' => null, 'error' => 'La nueva contraseña y la confirmación no coinciden.'];
        }

        if (mb_strlen($passwordNueva) < 8) {
            return ['mensaje' => null, 'error' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
        }

        $hashActual = $this->usuarios->obtenerClaveHashPorId($usuarioId);
        if ($hashActual === null || !password_verify($passwordActual, $hashActual)) {
            return ['mensaje' => null, 'error' => 'La contraseña actual no es correcta.'];
        }

        if (password_verify($passwordNueva, $hashActual)) {
            return ['mensaje' => null, 'error' => 'La nueva contraseña no puede ser igual a la actual.'];
        }

        $hashNuevo = password_hash($passwordNueva, PASSWORD_BCRYPT);
        if (!$this->usuarios->actualizarClave($usuarioId, $hashNuevo)) {
            return ['mensaje' => null, 'error' => 'No se pudo actualizar la contraseña.'];
        }

        return ['mensaje' => 'Contraseña actualizada.', 'error' => null];
    }

    private function actualizarFotoPerfil(int $usuarioId): array
    {
        if (!isset($_FILES['foto']) || (int) ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['mensaje' => null, 'error' => 'Selecciona una imagen válida.'];
        }

        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        // Se valida el tipo real del archivo para evitar que una extension falsa pase como imagen valida.
        $mime = mime_content_type((string) $_FILES['foto']['tmp_name']) ?: '';
        if (!isset($permitidos[$mime])) {
            return ['mensaje' => null, 'error' => 'Solo se permiten imagenes JPG, PNG o WEBP.'];
        }

        if ((int) $_FILES['foto']['size'] > 2 * 1024 * 1024) {
            return ['mensaje' => null, 'error' => 'La imagen no debe superar 2 MB.'];
        }

        $directorio = __DIR__ . '/../uploads/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $nombreArchivo = 'perfil_' . $usuarioId . '_' . time() . '.' . $permitidos[$mime];
        $destino = $directorio . '/' . $nombreArchivo;
        if (!move_uploaded_file((string) $_FILES['foto']['tmp_name'], $destino)) {
            return ['mensaje' => null, 'error' => 'No se pudo guardar la imagen.'];
        }

        $rutaRelativa = 'uploads/perfiles/' . $nombreArchivo;
        if (!$this->usuarios->actualizarFoto($usuarioId, $rutaRelativa)) {
            return ['mensaje' => null, 'error' => 'No se pudo actualizar la foto de perfil.'];
        }

        $_SESSION['foto'] = $rutaRelativa;
        return ['mensaje' => 'Foto de perfil actualizada.', 'error' => null];
    }
}
