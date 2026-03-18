<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function getTicketsReporte(): array
    {
        $sql = "SELECT t.id, t.codigo, t.titulo,
                       u.nombre AS usuario,
                       tec.nombre AS tecnico,
                       c.nombre AS categoria,
                       p.nombre AS prioridad,
                       e.nombre AS estado,
                       t.fecha_creacion,
                       t.fecha_cierre
                FROM tickets t
                INNER JOIN usuarios u ON t.usuario_id = u.id
                LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
                INNER JOIN categorias c ON t.categoria_id = c.id
                INNER JOIN prioridades p ON t.prioridad_id = p.id
                INNER JOIN estados_ticket e ON t.estado_id = e.id
                ORDER BY t.id DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
