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

    public function getAll(): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.archivo, a.nombre_original, a.creado_en
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                ORDER BY a.creado_en ASC, a.id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getByUsuario(int $usuarioId): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.archivo, a.nombre_original, a.creado_en
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                WHERE t.usuario_id = :usuario_id
                ORDER BY a.creado_en ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":usuario_id" => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function getByTecnico(int $tecnicoId): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.archivo, a.nombre_original, a.creado_en
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                WHERE t.tecnico_id = :tecnico_id
                ORDER BY a.creado_en ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":tecnico_id" => $tecnicoId]);
        return $stmt->fetchAll();
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
