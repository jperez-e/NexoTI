<?php  
declare(strict_types=1);  
  
require_once __DIR__ . '/../Config/Conexion.php';  
  
class TicketModel  
{  
    private PDO $db;  
  
    public function __construct()  
    {  
        $this->db = Conexion::get();  
    }  
  
    public function insert(  
        string $codigo,  
        string $titulo,  
        string $descripcion,  
        int $usuarioId,  
        int $categoriaId,  
        int $prioridadId,  
        int $estadoId,  
        ?string $fechaCreacion = null,
        ?int $tecnicoId = null,  
        ?string $fechaCierre = null  
    ): bool {  
        if ($fechaCreacion !== null) {
            $sql = 'INSERT INTO tickets (codigo, titulo, descripcion, usuario_id, tecnico_id, categoria_id, prioridad_id, estado_id, fecha_creacion, fecha_cierre) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $codigo,
                $titulo,
                $descripcion,
                $usuarioId,
                $tecnicoId,
                $categoriaId,
                $prioridadId,
                $estadoId,
                $fechaCreacion,
                $fechaCierre,
            ]);
        }

        $sql = 'INSERT INTO tickets (codigo, titulo, descripcion, usuario_id, tecnico_id, categoria_id, prioridad_id, estado_id, fecha_cierre) ' .  
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';  
        $stmt = $this->db->prepare($sql);  
        return $stmt->execute([  
            $codigo,  
            $titulo,  
            $descripcion,  
            $usuarioId,  
            $tecnicoId,  
            $categoriaId,  
            $prioridadId,  
            $estadoId,  
            $fechaCierre,  
        ]);  
    }  
  
    public function getLastInsertId(): int  
    {  
        return (int) $this->db->lastInsertId();  
    }  
 
    private function baseSelect(): string  
    {  
        return 'SELECT t.id, t.codigo, t.titulo, t.descripcion, ' .  
            't.usuario_id, u.nombre AS usuario_nombre, u.foto AS usuario_foto, ru.nombre AS usuario_rol_nombre, ' .  
            't.tecnico_id, ut.nombre AS tecnico_nombre, ut.foto AS tecnico_foto, rt.nombre AS tecnico_rol_nombre, ' .  
            't.categoria_id, c.nombre AS categoria_nombre, ' .  
            't.prioridad_id, p.nombre AS prioridad_nombre, ' .  
            't.estado_id, e.nombre AS estado_nombre, ' .  
            't.fecha_creacion, t.fecha_cierre ' .  
            'FROM tickets t ' .  
            'JOIN usuarios u ON t.usuario_id = u.id ' .  
            'JOIN roles ru ON u.rol_id = ru.id ' .  
            'LEFT JOIN usuarios ut ON t.tecnico_id = ut.id ' .  
            'LEFT JOIN roles rt ON ut.rol_id = rt.id ' .  
            'JOIN categorias c ON t.categoria_id = c.id ' .  
            'JOIN prioridades p ON t.prioridad_id = p.id ' .  
            'JOIN estados_ticket e ON t.estado_id = e.id';  
    }  
  
    public function getAll(): array  
    {  
        $sql = $this->baseSelect() . ' ORDER BY t.id DESC';  
        return $this->db->query($sql)->fetchAll();  
    }  
  
    public function getByUsuario(int $usuarioId): array  
    {  
        $sql = $this->baseSelect() . ' WHERE t.usuario_id = ? ORDER BY t.id DESC';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$usuarioId]);  
        return $stmt->fetchAll();  
    }  
  
    public function getByTecnico(int $tecnicoId): array  
    {  
        $sql = $this->baseSelect() . ' WHERE t.tecnico_id = ? ORDER BY t.id DESC';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$tecnicoId]);  
        return $stmt->fetchAll();  
    }  
 
    public function getById(int $ticketId): ?array  
    {  
        $sql = 'SELECT id, usuario_id, tecnico_id, estado_id FROM tickets WHERE id = ?';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$ticketId]);  
        $row = $stmt->fetch();  
        return $row ?: null;  
    }  
  
    public function assign(int $ticketId, ?int $tecnicoId, int $estadoId): bool  
    {  
        $sql = 'UPDATE tickets SET tecnico_id = ?, estado_id = ? WHERE id = ?';  
        $stmt = $this->db->prepare($sql);  
        return $stmt->execute([$tecnicoId, $estadoId, $ticketId]);  
    }  
  
    public function updateEstado(int $ticketId, int $estadoId, ?string $fechaCierre): bool  
    {  
        $sql = 'UPDATE tickets SET estado_id = ?, fecha_cierre = ? WHERE id = ?';  
        $stmt = $this->db->prepare($sql);  
        return $stmt->execute([$estadoId, $fechaCierre, $ticketId]);  
    }  
  
    public function getEstadoIdByNombre(string $nombre): ?int  
    {  
        $sql = 'SELECT id FROM estados_ticket WHERE nombre = ? LIMIT 1';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$nombre]);  
        $row = $stmt->fetch();  
        return $row ? (int) $row['id'] : null;  
    }  
 
    public function getDashboardCounts(int $rolId, int $userId): array  
    {  
        $joinExtra = '';  
        $params = [];  
        if ($rolId === 3) {  
            $joinExtra = ' AND t.usuario_id = ?';  
            $params[] = $userId;  
        } elseif ($rolId === 2) {  
            $joinExtra = ' AND t.tecnico_id = ?';  
            $params[] = $userId;  
        }  
  
        $sql = 'SELECT e.nombre, COUNT(t.id) AS total ' .  
            'FROM estados_ticket e ' .  
            'LEFT JOIN tickets t ON t.estado_id = e.id' . $joinExtra . ' ' .  
            'GROUP BY e.id, e.nombre ' .  
            'ORDER BY e.id';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute($params);  
        $rows = $stmt->fetchAll();  
  
        $counts = [];  
        $counts['abiertos'] = 0;  
        $counts['en_progreso'] = 0;  
        $counts['cerrados_hoy'] = 0;  
  
        foreach ($rows as $row) {  
            $name = mb_strtolower((string) $row['nombre']);  
            if ($name === 'abierto') {  
                $counts['abiertos'] = (int) $row['total'];  
            }  
            if ($name === 'en proceso') {  
                $counts['en_progreso'] = (int) $row['total'];  
            }  
        }  
  
        $sqlToday = 'SELECT COUNT(*) AS total ' .  
            'FROM tickets t ' .  
            'WHERE t.fecha_cierre IS NOT NULL ' .  
            'AND DATE(t.fecha_cierre) = CURDATE()';  
        if ($rolId === 3) {  
            $sqlToday .= ' AND t.usuario_id = ?';  
        } elseif ($rolId === 2) {  
            $sqlToday .= ' AND t.tecnico_id = ?';  
        }  
        $stmtToday = $this->db->prepare($sqlToday);  
        $stmtToday->execute($params);  
        $counts['cerrados_hoy'] = (int) ($stmtToday->fetch()['total'] ?? 0);  
  
        return $counts;  
    }  
} 
