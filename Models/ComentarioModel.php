<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class ComentarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(int $ticketId, int $usuarioId, string $comentario): bool
    {
        $sql = 'INSERT INTO comentarios_ticket (ticket_id, usuario_id, comentario) VALUES (:ticket_id, :usuario_id, :comentario)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id' => $ticketId,
            ':usuario_id' => $usuarioId,
            ':comentario' => $comentario,
        ]);
    }

    public function getLastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = 'SELECT c.id, c.ticket_id, c.usuario_id, c.comentario, c.fecha, '
            . 'u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre, '
            . 't.codigo AS ticket_codigo, t.titulo AS ticket_titulo, t.usuario_id AS ticket_usuario_id, t.tecnico_id AS ticket_tecnico_id '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'INNER JOIN tickets t ON t.id = c.ticket_id '
            . 'ORDER BY c.fecha DESC, c.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function getByUsuario(int $usuarioId): array
    {
        // El usuario final solo debe ver comentarios de tickets que le pertenecen.
        $sql = 'SELECT c.id, c.ticket_id, c.usuario_id, c.comentario, c.fecha, '
            . 'u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre, t.codigo AS ticket_codigo, t.titulo AS ticket_titulo, '
            . 't.usuario_id AS ticket_usuario_id, t.tecnico_id AS ticket_tecnico_id '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'INNER JOIN tickets t ON t.id = c.ticket_id '
            . 'WHERE t.usuario_id = ? '
            . 'ORDER BY c.fecha DESC, c.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function getByTecnico(int $tecnicoId): array
    {
        // El tecnico solo consulta comentarios de tickets asignados a el.
        $sql = 'SELECT c.id, c.ticket_id, c.usuario_id, c.comentario, c.fecha, '
            . 'u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre, t.codigo AS ticket_codigo, t.titulo AS ticket_titulo, '
            . 't.usuario_id AS ticket_usuario_id, t.tecnico_id AS ticket_tecnico_id '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'INNER JOIN tickets t ON t.id = c.ticket_id '
            . 'WHERE t.tecnico_id = ? '
            . 'ORDER BY c.fecha DESC, c.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tecnicoId]);
        return $stmt->fetchAll();
    }
}
