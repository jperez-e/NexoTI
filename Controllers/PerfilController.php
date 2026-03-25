<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Csrf.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

class PerfilController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?r=login');
            exit;
        }
    }

    public function index(?string $message = null, ?string $error = null): void
    {
        $this->requireLogin();
        $usuario = $this->usuarios->getById((int) $_SESSION['user_id']);
        if (!$usuario) {
            http_response_code(404);
            echo 'Usuario no encontrado.';
            return;
        }

        require __DIR__ . '/../Views/perfil/perfil.php';
    }

    public function update(): void
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->index();
            return;
        }

        if (!Csrf::isValidRequest()) {
            $this->index(null, 'La sesion del formulario expiro. Intenta de nuevo.');
            return;
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $this->index(null, 'Selecciona una imagen valida.');
            return;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mime = mime_content_type($_FILES['foto']['tmp_name']) ?: '';
        if (!isset($allowed[$mime])) {
            $this->index(null, 'Solo se permiten imagenes JPG, PNG o WEBP.');
            return;
        }

        if ((int) $_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $this->index(null, 'La imagen no debe superar 2 MB.');
            return;
        }

        $dir = __DIR__ . '/../uploads/perfiles';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fileName = 'perfil_' . (int) $_SESSION['user_id'] . '_' . time() . '.' . $allowed[$mime];
        $destino = $dir . '/' . $fileName;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $this->index(null, 'No se pudo guardar la imagen.');
            return;
        }

        $rutaRelativa = 'uploads/perfiles/' . $fileName;
        if (!$this->usuarios->updateFoto((int) $_SESSION['user_id'], $rutaRelativa)) {
            $this->index(null, 'No se pudo actualizar la foto de perfil.');
            return;
        }

        $_SESSION['foto'] = $rutaRelativa;
        $this->index('Foto de perfil actualizada.', null);
    }
}
