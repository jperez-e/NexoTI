<?php
// Este archivo PHP define el modelo de Usuario.
// Centraliza el acceso a base de datos para consultas y operaciones de persistencia relacionadas con esta entidad.
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function insertar(string $nombre, string $email, string $claveHash, int $rolId, int $activo = 1): bool
    {
        $sql = 'INSERT INTO usuarios (nombre, email, clave_hash, rol_id, activo) VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $email, $claveHash, $rolId, $activo]);
    }

    public function actualizar(int $id, string $nombre, string $email, int $rolId, ?string $claveHash = null, int $activo = 1): bool
    {
        // La clave solo se actualiza si el administrador escribe una nueva; si queda vacia, se conserva la actual.
        if ($claveHash !== null) {
            $sql = 'UPDATE usuarios SET nombre = ?, email = ?, clave_hash = ?, rol_id = ?, activo = ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $email, $claveHash, $rolId, $activo, $id]);
        }

        $sql = 'UPDATE usuarios SET nombre = ?, email = ?, rol_id = ?, activo = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $email, $rolId, $activo, $id]);
    }

    public function eliminar(int $id): bool
    {
        $sql = 'DELETE FROM usuarios WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function actualizarFoto(int $id, string $foto): bool
    {
        $sql = 'UPDATE usuarios SET foto = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$foto, $id]);
    }

    public function actualizarClave(int $id, string $claveHash): bool
    {
        $sql = 'UPDATE usuarios SET clave_hash = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$claveHash, $id]);
    }

    public function obtenerTodos(): array
    {
        // Se hace JOIN con roles para que la interfaz muestre el nombre del rol y no solo el id numerico.
        $sql = 'SELECT u.id, u.nombre, u.email, u.rol_id, r.nombre AS rol_nombre, u.activo, u.foto, u.creado_en '
            . 'FROM usuarios u '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'ORDER BY u.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerPorRol(int $rolId): array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, u.rol_id, r.nombre AS rol_nombre, u.activo '
            . 'FROM usuarios u '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'WHERE u.rol_id = ? AND u.activo = 1 '
            . 'ORDER BY u.nombre ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$rolId]);
        return $stmt->fetchAll();
    }

    public function obtenerCandidatosParticipantes(): array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, u.rol_id, r.nombre AS rol_nombre, u.activo, u.foto '
            . 'FROM usuarios u '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'WHERE u.activo = 1 '
            . 'ORDER BY u.nombre ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerIdsAdmin(): array
    {
        $rows = $this->obtenerPorRol(1);
        return array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, u.rol_id, r.nombre AS rol_nombre, u.activo, u.foto '
            . 'FROM usuarios u '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'WHERE u.id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function obtenerClaveHashPorId(int $id): ?string
    {
        $sql = 'SELECT clave_hash FROM usuarios WHERE id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || !isset($row['clave_hash'])) {
            return null;
        }
        return (string) $row['clave_hash'];
    }

    public function buscarPorLogin(string $login): ?array
    {
        $sql = 'SELECT id, nombre, email, clave_hash, rol_id, activo, foto FROM usuarios WHERE email = :login LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':login' => $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
