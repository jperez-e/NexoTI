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

        $payload = $this->obtenerPayloadUsuario();
        $error = $this->validarPayloadUsuario($payload, false, true);
        if ($error !== null) {
            $this->responderErrorJson($error);
        }

        $this->ejecutarPersistenciaUsuario(
            function () use ($payload): bool {
                $hash = password_hash((string) $payload['password'], PASSWORD_BCRYPT);
                return $this->model->insertar(
                    (string) $payload['nombre'],
                    (string) $payload['email'],
                    $hash,
                    (int) $payload['rol_id'],
                    1
                );
            },
            'Usuario creado correctamente.',
            'No se pudo crear el usuario.',
            'No se pudo crear el usuario. Verifica que el correo no exista.'
        );
    }

    public function actualizar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $payload = $this->obtenerPayloadUsuario();
        $error = $this->validarPayloadUsuario($payload, true, false);
        if ($error !== null) {
            $this->responderErrorJson($error);
        }

        $id = (int) $payload['id'];
        $user = $this->model->obtenerPorId($id);
        if (!$user) {
            $this->responderErrorJson('Usuario no encontrado.');
        }

        $this->ejecutarPersistenciaUsuario(
            function () use ($payload, $id): bool {
                $password = trim((string) $payload['password']);
                $hash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;
                return $this->model->actualizar(
                    $id,
                    (string) $payload['nombre'],
                    (string) $payload['email'],
                    (int) $payload['rol_id'],
                    $hash,
                    1
                );
            },
            'Usuario actualizado correctamente.',
            'No se pudo actualizar el usuario.',
            'No se pudo actualizar el usuario. Verifica el correo y los datos.'
        );
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

    /**
     * @return array{id:int,nombre:string,email:string,password:string,rol_id:int}
     */
    private function obtenerPayloadUsuario(): array
    {
        $payload = $this->obtenerDatosSolicitud();

        return [
            'id' => (int) ($payload['id'] ?? 0),
            'nombre' => trim(strip_tags((string) ($payload['nombre'] ?? ''))),
            'email' => trim((string) ($payload['email'] ?? '')),
            'password' => (string) ($payload['password'] ?? ''),
            'rol_id' => (int) ($payload['rol_id'] ?? 0),
        ];
    }

    private function validarPayloadUsuario(array $payload, bool $requiereId, bool $requierePassword): ?string
    {
        if ($requiereId && (int) ($payload['id'] ?? 0) <= 0) {
            return 'Datos invalidos para actualizar.';
        }

        $nombre = (string) ($payload['nombre'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $rolId = (int) ($payload['rol_id'] ?? 0);
        if ($nombre === '' || $email === '' || $rolId <= 0) {
            return $requiereId ? 'Datos invalidos para actualizar.' : 'Completa todos los campos obligatorios.';
        }

        if ($requierePassword && (string) ($payload['password'] ?? '') === '') {
            return 'Completa todos los campos obligatorios.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Correo invalido.';
        }

        return null;
    }

    private function ejecutarPersistenciaUsuario(
        callable $operacion,
        string $mensajeOk,
        string $mensajeError,
        string $mensajeConflicto
    ): void {
        try {
            $ok = (bool) $operacion();
            if ($ok) {
                $this->responderOkJson($mensajeOk);
            }
            $this->responderErrorJson($mensajeError);
        } catch (Throwable $e) {
            $this->responderErrorJson($mensajeConflicto, 409);
        }
    }
}
