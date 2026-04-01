<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class NotificacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(
        int $usuarioId,
        ?int $ticketId,
        ?int $actorId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): bool {
        $sql = 'INSERT INTO notificaciones (usuario_id, ticket_id, actor_id, tipo, titulo, mensaje) '
            . 'VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$usuarioId, $ticketId, $actorId, $tipo, $titulo, $mensaje]);
    }

    public function getByUsuario(int $usuarioId, int $limit = 8): array
    {
        $sql = 'SELECT n.id, n.usuario_id, n.ticket_id, n.actor_id, n.tipo, n.titulo, n.mensaje, n.leida, '
            . 'n.creada_en, n.leida_en, t.codigo AS ticket_codigo, t.titulo AS ticket_titulo '
            . 'FROM notificaciones n '
            . 'LEFT JOIN tickets t ON t.id = n.ticket_id '
            . 'WHERE n.usuario_id = ? '
            . 'ORDER BY n.leida ASC, n.creada_en DESC, n.id DESC '
            . 'LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnreadByUsuario(int $usuarioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM notificaciones WHERE usuario_id = ? AND leida = 0');
        $stmt->execute([$usuarioId]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    public function markAsRead(int $id, int $usuarioId): bool
    {
        $sql = 'UPDATE notificaciones SET leida = 1, leida_en = NOW() '
            . 'WHERE id = ? AND usuario_id = ? AND leida = 0';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $usuarioId]);
    }

    public function markAllAsRead(int $usuarioId): bool
    {
        $sql = 'UPDATE notificaciones SET leida = 1, leida_en = NOW() '
            . 'WHERE usuario_id = ? AND leida = 0';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$usuarioId]);
    }
}
