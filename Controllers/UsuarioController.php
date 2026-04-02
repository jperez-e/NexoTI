<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function listar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $rows = $this->model->obtenerTodos();
        $this->responderOkJson('Usuarios cargados', $rows);
    }

    public function crear(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        // Este controlador recibe formularios del admin y delega la persistencia al modelo de usuarios.
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        if ($nombre === '' || $email === '' || $password === '' || $rolId <= 0) {
            $this->responderErrorJson('Completa todos los campos obligatorios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responderErrorJson('Correo invalido.');
        }

        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ok = $this->model->insertar($nombre, $email, $hash, $rolId, 1);
            $ok ? $this->responderOkJson('Usuario creado correctamente.') : $this->responderErrorJson('No se pudo crear el usuario.');
        } catch (Throwable $e) {
            $this->responderErrorJson('No se pudo crear el usuario. Verifica que el correo no exista.', 409);
        }
    }

    public function actualizar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        if ($id <= 0 || $nombre === '' || $email === '' || $rolId <= 0) {
            $this->responderErrorJson('Datos invalidos para actualizar.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responderErrorJson('Correo invalido.');
        }

        $user = $this->model->obtenerPorId($id);
        if (!$user) {
            $this->responderErrorJson('Usuario no encontrado.');
        }

        try {
            $hash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;
            $ok = $this->model->actualizar($id, $nombre, $email, $rolId, $hash, 1);
            $ok ? $this->responderOkJson('Usuario actualizado correctamente.') : $this->responderErrorJson('No se pudo actualizar el usuario.');
        } catch (Throwable $e) {
            $this->responderErrorJson('No se pudo actualizar el usuario. Verifica el correo y los datos.', 409);
        }
    }

    public function eliminar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();

        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            $this->responderErrorJson('Usuario invalido.');
        }

        if ($id === $this->obtenerIdUsuarioActual()) {
            $this->responderErrorJson('No puedes eliminar tu propio usuario.');
        }

        $user = $this->model->obtenerPorId($id);
        if (!$user) {
            $this->responderErrorJson('Usuario no encontrado.');
        }

        try {
            $ok = $this->model->eliminar($id);
            $ok ? $this->responderOkJson('Usuario eliminado correctamente.') : $this->responderErrorJson('No se pudo eliminar el usuario.');
        } catch (Throwable $e) {
            $this->responderErrorJson('No se puede eliminar este usuario porque tiene informacion relacionada en el sistema.', 409);
        }
    }

    public function listarTecnicos(): void
    {
        $this->requerirSesion();
        // Se usa para llenar el combo de asignacion de tickets con usuarios que tienen rol tecnico.
        $rows = $this->model->obtenerPorRol(2);
        $this->responderOkJson('Tecnicos cargados', $rows);
    }
}
