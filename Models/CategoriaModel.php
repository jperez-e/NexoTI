<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class CategoriaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre, ?string $descripcion = null): bool
    {
        $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre" => $nombre,
            ":descripcion" => $descripcion,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre, descripcion FROM categorias ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function update(int $id, string $nombre, ?string $descripcion = null): bool
    {
        $sql = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
            ":nombre" => $nombre,
            ":descripcion" => $descripcion,
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM categorias WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":id" => $id,
        ]);
    }
}
