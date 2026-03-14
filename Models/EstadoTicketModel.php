<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class EstadoTicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(string $nombre): bool
    {
        $sql = "INSERT INTO estados_ticket (nombre) VALUES (:nombre)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":nombre" => $nombre,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, nombre FROM estados_ticket ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
