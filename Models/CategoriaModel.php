<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/Conexion.php";

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
}
