<?php
// Este archivo PHP define el modelo de Comentario.
// Centraliza el acceso a base de datos para consultas y operaciones de persistencia relacionadas con esta entidad.
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class ComentarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function insertar(int $ticketId, int $usuarioId, string $comentario): bool
    {
        $sql = 'INSERT INTO comentarios_ticket (ticket_id, usuario_id, comentario) VALUES (:ticket_id, :usuario_id, :comentario)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id' => $ticketId,
            ':usuario_id' => $usuarioId,
            ':comentario' => $comentario,
        ]);
    }

    public function obtenerUltimoIdInsertado(): int
    {
        return (int) $this->db->lastInsertId();
    }

    public function obtenerTodos(): array
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

    public function obtenerPorUsuario(int $usuarioId): array
    {
        // El usuario final solo debe ver comentarios de tickets que le pertenecen.
        $sql = 'SELECT c.id, c.ticket_id, c.usuario_id, c.comentario, c.fecha, '
            . 'u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre, t.codigo AS ticket_codigo, t.titulo AS ticket_titulo, '
            . 't.usuario_id AS ticket_usuario_id, t.tecnico_id AS ticket_tecnico_id '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'INNER JOIN tickets t ON t.id = c.ticket_id '
            . 'WHERE (t.usuario_id = ? OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = ?)) '
            . 'ORDER BY c.fecha DESC, c.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuarioId, $usuarioId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorTecnico(int $tecnicoId): array
    {
        // El tecnico solo consulta comentarios de tickets asignados a el.
        $sql = 'SELECT c.id, c.ticket_id, c.usuario_id, c.comentario, c.fecha, '
            . 'u.nombre AS usuario_nombre, u.foto AS usuario_foto, r.nombre AS rol_nombre, t.codigo AS ticket_codigo, t.titulo AS ticket_titulo, '
            . 't.usuario_id AS ticket_usuario_id, t.tecnico_id AS ticket_tecnico_id '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'INNER JOIN tickets t ON t.id = c.ticket_id '
            . 'WHERE (t.tecnico_id = ? OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = ?) OR EXISTS (SELECT 1 FROM usuarios uc WHERE uc.id = t.usuario_id AND uc.rol_id = 2)) '
            . 'ORDER BY c.fecha DESC, c.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tecnicoId, $tecnicoId]);
        return $stmt->fetchAll();
    }
}
