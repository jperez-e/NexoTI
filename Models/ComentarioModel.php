<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class ComentarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(int $ticketId, int $usuarioId, string $comentario): bool
    {
        $sql = "INSERT INTO comentarios_ticket (ticket_id, usuario_id, comentario)
                VALUES (:ticket_id, :usuario_id, :comentario)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":ticket_id" => $ticketId,
            ":usuario_id" => $usuarioId,
            ":comentario" => $comentario,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, ticket_id, usuario_id, comentario, fecha
                FROM comentarios_ticket
                ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
