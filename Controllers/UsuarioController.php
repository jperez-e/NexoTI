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

    public function list(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $rows = $this->model->getAll();
        $this->jsonOk('Usuarios cargados', $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        // Este controlador recibe formularios del admin y delega la persistencia al modelo de usuarios.
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        if ($nombre === '' || $email === '' || $password === '' || $rolId <= 0) {
            $this->jsonError('Completa todos los campos obligatorios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Correo invalido.');
        }

        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ok = $this->model->insert($nombre, $email, $hash, $rolId, 1);
            $ok ? $this->jsonOk('Usuario creado correctamente.') : $this->jsonError('No se pudo crear el usuario.');
        } catch (Throwable $e) {
            $this->jsonError('No se pudo crear el usuario. Verifica que el correo no exista.', 409);
        }
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $rolId = (int) ($_POST['rol_id'] ?? 0);

        if ($id <= 0 || $nombre === '' || $email === '' || $rolId <= 0) {
            $this->jsonError('Datos invalidos para actualizar.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Correo invalido.');
        }

        $user = $this->model->getById($id);
        if (!$user) {
            $this->jsonError('Usuario no encontrado.');
        }

        try {
            $hash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;
            $ok = $this->model->update($id, $nombre, $email, $rolId, $hash, 1);
            $ok ? $this->jsonOk('Usuario actualizado correctamente.') : $this->jsonError('No se pudo actualizar el usuario.');
        } catch (Throwable $e) {
            $this->jsonError('No se pudo actualizar el usuario. Verifica el correo y los datos.', 409);
        }
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();

        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Usuario invalido.');
        }

        if ($id === $this->currentUserId()) {
            $this->jsonError('No puedes eliminar tu propio usuario.');
        }

        $user = $this->model->getById($id);
        if (!$user) {
            $this->jsonError('Usuario no encontrado.');
        }

        try {
            $ok = $this->model->delete($id);
            $ok ? $this->jsonOk('Usuario eliminado correctamente.') : $this->jsonError('No se pudo eliminar el usuario.');
        } catch (Throwable $e) {
            $this->jsonError('No se puede eliminar este usuario porque tiene informacion relacionada en el sistema.', 409);
        }
    }

    public function tecnicos(): void
    {
        $this->requireLogin();
        // Se usa para llenar el combo de asignacion de tickets con usuarios que tienen rol tecnico.
        $rows = $this->model->getByRol(2);
        $this->jsonOk('Tecnicos cargados', $rows);
    }
}
