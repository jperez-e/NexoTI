<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/Conexion.php";

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre, string $email, string $claveHash, int $rolId, int $activo = 1): bool
    {
        $sql = "INSERT INTO usuarios (nombre, email, clave_hash, rol_id, activo)
                VALUES (:nombre, :email, :clave_hash, :rol_id, :activo)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre" => $nombre,
            ":email" => $email,
            ":clave_hash" => $claveHash,
            ":rol_id" => $rolId,
            ":activo" => $activo,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre, email, rol_id, activo, creado_en
                FROM usuarios
                ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
