<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class RolModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre): bool
    {
        $sql = "INSERT INTO roles (nombre) VALUES (:nombre)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre" => $nombre,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre FROM roles ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function update(int $id, string $nombre): bool
    {
        $sql = "UPDATE roles SET nombre = :nombre WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
            ":nombre" => $nombre,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM roles WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
        ]);
    }
}
