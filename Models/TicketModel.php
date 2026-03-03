<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/Database.php";

class TicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT t.id, t.codigo, t.titulo, t.descripcion, p.nombre AS prioridad, e.nombre AS estado, t.fecha_creacion
                FROM tickets t
                INNER JOIN prioridades p ON p.id = t.prioridad_id
                INNER JOIN estados_ticket e ON e.id = t.estado_id
                ORDER BY t.id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO tickets (codigo, titulo, descripcion, usuario_id, tecnico_id, categoria_id, prioridad_id, estado_id)
                VALUES (:codigo, :titulo, :descripcion, :usuario_id, NULL, :categoria_id, :prioridad_id, :estado_id)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":codigo" => $data["codigo"],
            ":titulo" => $data["titulo"],
            ":descripcion" => $data["descripcion"],
            ":usuario_id" => (int) $data["usuario_id"],
            ":categoria_id" => (int) $data["categoria_id"],
            ":prioridad_id" => (int) $data["prioridad_id"],
            ":estado_id" => (int) $data["estado_id"],
        ]);
    }

    public function getPrioridades(): array
    {
        return $this->db->query("SELECT id, nombre FROM prioridades ORDER BY nivel ASC")->fetchAll();
    }

    public function getCategorias(): array
    {
        return $this->db->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC")->fetchAll();
    }
}
