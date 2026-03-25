<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class PrioridadModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre, int $nivel): bool
    {
        $sql = "INSERT INTO prioridades (nombre, nivel) VALUES (:nombre, :nivel)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre" => $nombre,
            ":nivel" => $nivel,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre, nivel FROM prioridades ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function update(int $id, string $nombre, int $nivel): bool
    {
        $sql = "UPDATE prioridades SET nombre = :nombre, nivel = :nivel WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
            ":nombre" => $nombre,
            ":nivel" => $nivel,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM prioridades WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
        ]);
    }
}
