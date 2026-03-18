<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class AdjuntoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(int $ticketId, string $archivo, string $nombreOriginal): bool
    {
        $sql = "INSERT INTO ticket_adjuntos (ticket_id, archivo, nombre_original)
                VALUES (:ticket_id, :archivo, :nombre_original)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":ticket_id" => $ticketId,
            ":archivo" => $archivo,
            ":nombre_original" => $nombreOriginal,
        ]);
    }

    public function getByTicket(int $ticketId): array
    {
        $sql = "SELECT id, ticket_id, archivo, nombre_original, creado_en
                FROM ticket_adjuntos
                WHERE ticket_id = :ticket_id
                ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":ticket_id" => $ticketId]);
        return $stmt->fetchAll();
    }
}
