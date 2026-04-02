<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class TicketParticipanteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ticket_participantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            usuario_id INT NOT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_ticket_usuario (ticket_id, usuario_id),
            KEY idx_ticket (ticket_id),
            KEY idx_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $this->db->exec($sql);
    }

    public function isParticipant(int $ticketId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM ticket_participantes WHERE ticket_id = ? AND usuario_id = ? LIMIT 1');
        $stmt->execute([$ticketId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function add(int $ticketId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare('INSERT INTO ticket_participantes (ticket_id, usuario_id) VALUES (?, ?)');
        return $stmt->execute([$ticketId, $usuarioId]);
    }

    public function remove(int $ticketId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ticket_participantes WHERE ticket_id = ? AND usuario_id = ?');
        return $stmt->execute([$ticketId, $usuarioId]);
    }

    public function getByTicketIds(array $ticketIds): array
    {
        if ($ticketIds === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ticketIds)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT tp.id, tp.ticket_id, tp.usuario_id, tp.creado_en, '
            . 'u.nombre AS usuario_nombre, u.email AS usuario_email, u.foto AS usuario_foto, '
            . 'r.nombre AS rol_nombre '
            . 'FROM ticket_participantes tp '
            . 'INNER JOIN usuarios u ON u.id = tp.usuario_id '
            . 'INNER JOIN roles r ON r.id = u.rol_id '
            . 'WHERE tp.ticket_id IN (' . $placeholders . ') '
            . 'ORDER BY tp.creado_en ASC, tp.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}

