<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class AdjuntoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function insertar(
        int $ticketId,
        string $archivo,
        string $nombreOriginal,
        ?int $usuarioId = null,
        ?int $comentarioId = null
    ): bool
    {
        $sql = "INSERT INTO ticket_adjuntos (ticket_id, comentario_id, usuario_id, archivo, nombre_original)
                VALUES (:ticket_id, :comentario_id, :usuario_id, :archivo, :nombre_original)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":ticket_id" => $ticketId,
            ":comentario_id" => $comentarioId,
            ":usuario_id" => $usuarioId,
            ":archivo" => $archivo,
            ":nombre_original" => $nombreOriginal,
        ]);
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.comentario_id, a.usuario_id, a.archivo, a.nombre_original, a.creado_en,
                       u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                LEFT JOIN roles r ON r.id = u.rol_id
                ORDER BY a.creado_en ASC, a.id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerPorUsuario(int $usuarioId): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.comentario_id, a.usuario_id, a.archivo, a.nombre_original, a.creado_en,
                       u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                LEFT JOIN roles r ON r.id = u.rol_id
                WHERE (t.usuario_id = :usuario_id OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = :usuario_id_participante))
                ORDER BY a.creado_en ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":usuario_id" => $usuarioId, ":usuario_id_participante" => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorTecnico(int $tecnicoId): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.comentario_id, a.usuario_id, a.archivo, a.nombre_original, a.creado_en,
                       u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre
                FROM ticket_adjuntos a
                INNER JOIN tickets t ON t.id = a.ticket_id
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                LEFT JOIN roles r ON r.id = u.rol_id
                WHERE (t.tecnico_id = :tecnico_id OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = :tecnico_id_participante))
                ORDER BY a.creado_en ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":tecnico_id" => $tecnicoId, ":tecnico_id_participante" => $tecnicoId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorTicket(int $ticketId): array
    {
        $sql = "SELECT a.id, a.ticket_id, a.comentario_id, a.usuario_id, a.archivo, a.nombre_original, a.creado_en,
                       u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre
                FROM ticket_adjuntos a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                LEFT JOIN roles r ON r.id = u.rol_id
                WHERE a.ticket_id = :ticket_id
                ORDER BY a.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":ticket_id" => $ticketId]);
        return $stmt->fetchAll();
    }
}
