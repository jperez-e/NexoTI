<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre, string $email, string $claveHash, int $rolId, int $activo = 1): bool
    {
        $sql = 'INSERT INTO usuarios (nombre, email, clave_hash, rol_id, activo) VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $email, $claveHash, $rolId, $activo]);
    }

    public function update(int $id, string $nombre, string $email, int $rolId, ?string $claveHash = null, int $activo = 1): bool
    {
        if ($claveHash !== null) {
            $sql = 'UPDATE usuarios SET nombre = ?, email = ?, clave_hash = ?, rol_id = ?, activo = ? WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $email, $claveHash, $rolId, $activo, $id]);
        }

        $sql = 'UPDATE usuarios SET nombre = ?, email = ?, rol_id = ?, activo = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $email, $rolId, $activo, $id]);
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM usuarios WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function updateFoto(int $id, string $foto): bool
    {
        $sql = 'UPDATE usuarios SET foto = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$foto, $id]);
    }

    public function getAll(): array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, u.rol_id, r.nombre AS rol_nombre, u.activo, u.foto, u.creado_en '
            . 'FROM usuarios u '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'ORDER BY u.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function getByRol(int $rolId): array
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

    public function getById(int $id): ?array
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

    public function findByLogin(string $login): ?array
    {
        $sql = 'SELECT id, nombre, email, clave_hash, rol_id, activo, foto FROM usuarios WHERE email = :login LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':login' => $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
